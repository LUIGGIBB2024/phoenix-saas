<script setup lang="ts">
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { VBtn } from 'vuetify/components/VBtn'
import { VCard, VCardActions, VCardText, VCardTitle } from 'vuetify/components/VCard'
import { VCol } from 'vuetify/components/VGrid' // VCol pertenece a VGrid

// import type { Product } from './type'

const archivos = ref<File[]>([])

const isFocused = ref(false)

// const certificateNombre = ref('')
const certificateFile = ref<File | null>(null)
const certificateFileModel = ref<File | File[] | null>(null)

const inputRef = ref<HTMLInputElement | null>(null)

const tipodeusuario = ref(localStorage.getItem('tipo_de_usuario'))

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files)
    archivos.value = Array.from(target.files)
}

// 🔹 Filtros y variables de estado
const searchQuery = ref('')
const selectedRows = ref([])

// 🔹 Opciones del datatable
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const hoy = new Date().toISOString().split('T')[0]
const token = localStorage.getItem('auth_token')

// const token = localStorage.getItem('auth_token')

const accessToken = useCookie('accessToken', { path: '/' })

// accessToken.value = response.data.token // ← el que te devuelve Laravel

// 🔹 Actualizar opciones de orden
// const updateOptions = (options: any) => {
//   sortBy.value = options.sortBy[0]?.key
//   orderBy.value = options.sortBy[0]?.order
// }

const updateOptions = async (options: any) => {
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// 🔹 Encabezados de la tabla
export interface Customer {
  id?: number
  nit?: string
  branch?: string
  dv?: string
  patient_id?: string
  code?: string
  provider_code?: string
  name?: string
  firstname?: string
  lastname?: string
  comercial_name?: string
  address?: string
  phone?: string
  email?: string
  latitude?: string
  longitude?: string
  zip_code?: string
  nit_representative?: string
  contact_phone?: string
  name_contact?: string
  email_contact?: string
  health_contract_number?: string
  health_policy_number?: string
  credit_quota?: number
  deadline_days?: number
  point?: number
  accumulated_points?: number
  birthday?: string | Date
  last_purchase_date?: string | Date
  creation_date?: string | Date
  economic_activity?: string
  business_registration?: string
  sales_account?: string
  center?: string
  scenter?: string
  health_service_coverage_id?: number
  health_payment_method_id?: number
  branch_id?: number
  route_id?: number
  zone_id?: number
  type_id?: number
  neighborhood_id?: number
  price_list_id?: number
  municipalities_id?: number
  sellers_id?: number
  type_document_identification_id?: number
  companies_id?: number
  type_regime_id?: number
  type_liability_id?: number
  sex?: 'Masculino' | 'Femenino' | 'Otro'
  state?: 'Activo' | 'Inactivo'
  typeofcurrency?: 'Pesos' | 'Dólares'
  retesource?: 'Si' | 'No'
  reteiva?: 'Si' | 'No'
  reteica?: 'Si' | 'No'
  declare_income?: 'Si' | 'No'
  control_points?: 'Si' | 'No'
  capture_signature?: 'Si' | 'No'
}

// --- 🔹 Modal y formulario de creación ---
const showDialog = ref(false)
const editMode = ref(false) // 👈 false = crear, true = editar

const newRecord = ref<Customer>({
  id: 0,
  nit: '',
  dv: '',
  branch: '',
  patient_id: '',
  code: '',
  provider_code: '',
  name: '',
  firstname: '',
  lastname: '',
  comercial_name: '',
  address: '',
  phone: '',
  email: '',
  latitude: '',
  longitude: '',
  zip_code: '',
  nit_representative: '',
  contact_phone: '',
  name_contact: '',
  email_contact: '',
  health_contract_number: '',
  health_policy_number: '',
  credit_quota: 0,
  deadline_days: 0,
  point: 0,
  accumulated_points: 0,
  birthday: (hoy),
  last_purchase_date: (hoy),
  creation_date: (hoy),
  economic_activity: '',
  business_registration: '',
  sales_account: '',
  center: '',
  scenter: '',
  health_service_coverage_id: null,
  health_payment_method_id: null,
  branch_id: null,
  route_id: null,
  zone_id: null,
  type_id: null,
  neighborhood_id: null,
  price_list_id: null,
  municipalities_id: null,
  sellers_id: undefined,
  type_document_identification_id: null,
  companies_id: 0,
  type_regime_id: null,
  type_liability_id: null,
  sex: 'Masculino',
  state: 'Activo',
  typeofcurrency: 'Pesos',
  retesource: 'No',
  reteiva: 'No',
  reteica: 'No',
  declare_income: 'No',
  control_points: 'No',
  capture_signature: 'No',

})

// watch(archivos, files => {
//   if (files.length > 0)
//     newProduct.value.certificatename = files[0]
// })

// 🔹 Snackbar (toast)
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

const responseData = ref({
  data: [],
  regimen: [],
  tydocument: [],
  sellers: [],
  total: 0,
  page: 1,
  per_page: 10,
  totaldctos: 0,
})

// 🔹 Diálogo de confirmación de eliminación
const regimenes = ref([])
const typedocument = ref([])
const lists = ref([])
const sellers = ref([])
const zones = ref([])
const routes = ref([])
const typecust = ref([])
const neighborhoods = ref([])
const municipalities = ref([])
const liabilities = ref([])
const nameRecordToDelete = ref('')
const showConfirmDialog = ref(false)
const recordToDelete = ref<number | null>(null)

// 🔹 Reglas de validación
const rules = {
  required: (value: string) => !!value || 'Este campo es obligatorio',
  email: (value: string) =>
    !value || /^[^\s@]+@[^\s@][^\s.@]*\.[^\s@]+$/.test(value) || 'Correo inválido',
  phone: (value: string) =>
    !value || value.length >= 7 || 'Debe tener al menos 7 dígitos',
}

// 🔹 Observa el estado del diálogo
watch(showDialog, isOpen => {
  if (isOpen && !editMode.value) {
    // Se abre el diálogo → limpiar los campos
    newRecord.value = {
      id: null,
      nit: '',
      dv: '',
      branch: '01',
      patient_id: '',
      code: '000001',
      provider_code: '',
      name: '',
      firstname: '',
      lastname: '',
      comercial_name: '',
      phone: '',
      email: '',
      latitude: '',
      longitude: '',
      zip_code: '000000',
      nit_representative: '',
      contact_phone: '',
      name_contact: '',
      email_contact: '',
      health_contract_number: '',
      health_policy_number: '',
      credit_quota: 0,
      deadline_days: 0,
      point: 0,
      accumulated_points: 0,
      birthday: (hoy),
      last_purchase_date: (hoy),
      creation_date: (hoy),
      economic_activity: '7110',
      business_registration: '111111',
      sales_account: '',
      center: '',
      scenter: '',
      health_service_coverage_id: null,
      health_payment_method_id: null,
      branch_id: null,
      route_id: 1,
      zone_id: 1,
      type_id: 1,
      neighborhood_id: null,
      price_list_id: 1,
      municipalities_id: null,
      sellers_id: 1,
      type_document_identification_id: 3,
      companies_id: 0,
      type_regime_id: null,
      type_liability_id: null,
      sex: 'Masculino',
      state: 'Activo',
      typeofcurrency: 'Pesos',
      retesource: 'No',
      reteiva: 'No',
      reteica: 'No',
      declare_income: 'No',
      control_points: 'No',
      capture_signature: 'No',
    }
  }
})

const loadInfo = async () => {
  try {
    const response = await axios.get('/api/getcustomers', {
      params: {
        q: searchQuery.value,
        itemsPerPage: itemsPerPage.value,
        page: page.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        company_id: localStorage.getItem('company_id'),
      },
      headers: {
        Authorization: `Bearer ${token}`,
      }, // <-- Ahora vive dentro del mismo objeto de configuración
    })

    responseData.value = response.data
    regimenes.value = response.data.regimen
    sellers.value = response.data.sellers
    lists.value = response.data.lists
    zones.value = response.data.zones
    routes.value = response.data.routes
    typecust.value = response.data.typecust
    neighborhoods.value = response.data.neighborhoods
    municipalities.value = response.data.municipalities
    liabilities.value = response.data.liabilities
    typedocument.value = JSON.parse(JSON.stringify(response.data.typedocument))

    console.log('Soy Rutas :', routes.value)

    // sgrupos.value = response.data.sgrupos
    // unidades.value = response.data.unidades
  }
  catch (error) {
    console.error('Error al intentar enviar correo :', error)
  }
}

// 🔹 Ejecutar al montar
onMounted(() => loadInfo())

// 🔍 Escucha cambios en la barra de búsqueda
// watch(searchQuery, () => {
//   console.log('🔍 Búsqueda cambiada:', searchQuery.value)
//   page.value = 1
//   loadInfo()
// })

// 🔹 Computed para acceder fácilmente a los datos
// const infoData = computed(() => responseData.value?.data ?? [])

const infoData = computed(() => {
  const data = responseData.value.data ?? []

  console.log('Soy Data 001 - 999 : ', JSON.parse(JSON.stringify(data)))

  return data.map(p => ({
    ...p,
    regimen: p.regimen,
    typedocument: p.typedocument,
    sellers: p.sellers,

  }))
})

const totalRecords = computed(() => responseData.value?.total ?? 0)
const perPage = computed(() => responseData.value.per_page ?? itemsPerPage.value)
const currentPage = computed(() => responseData.value.page ?? page.value)

console.log('Información Regimen:', responseData.value.regimen)

const saveRecord = async () => {
  const formData = new FormData()

  formData.append('company_id', localStorage.getItem('company_id') || '')

  Object.entries(newRecord.value).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '')
      formData.append(key, value as string)
  })

  // 🔍 DEBUG: confirmar qué se está enviando realmente
  console.log('--- FormData enviado ---')
  for (const [key, value] of formData.entries())
    console.log(key, ':', value)

  try {
    const url = newRecord.value.id
      ? `/api/customers/${newRecord.value.id}`
      : '/api/customers'

    const { data } = await axios.post(url, formData)

    // 🔍 1. Confirmar qué llega del backend
    console.log('1. data.customers desde backend:', data.customers.municipalities_id)

    // 🔎 Buscamos los nombres correspondientes a los códigos seleccionados
    const ciudadSeleccionada = municipalities.value.find(
      g => String(g.id) === String(newRecord.value.municipalities_id),
    )

    console.log('3. Ciudad Seleccionada:', ciudadSeleccionada)

    // 🧩 Mergeamos el producto crudo del backend con los nombres frescos
    const clienteActualizado = {
      ...data.customers,
      city_name: ciudadSeleccionada?.name ?? null,
    }

    if (newRecord.value.id) {
      const index = responseData.value.data.findIndex(
        (c: any) => c.id === newRecord.value.id,
      )

      if (index !== -1) {
        responseData.value.data = [
          ...responseData.value.data.slice(0, index),
          { ...clienteActualizado }, // ✅ sin .value
          ...responseData.value.data.slice(index + 1),
        ]
      }
    }
    else {
      responseData.value.data = [...responseData.value.data, { ...clienteActualizado }] // ✅ sin .value
    }

    snackbarMessage.value = newRecord.value.id
      ? 'Cliente actualizado correctamente'
      : 'Cliente creado correctamente'
    snackbarColor.value = 'success'

    certificateFileModel.value = null
    showDialog.value = false
  }
  catch (error: any) {
    console.error('Error:', error.response?.data)
    snackbarMessage.value = 'Error al guardar el Cliente'
    snackbarColor.value = 'error'
  }
  finally {
    showSnackbar.value = true
  }
}

// 🔧 Helper para normalizar IDs a número (o null si no aplica)
const toId = (val: any) => {
  if (val === null || val === undefined || val === '')
    return null

  return Number(val)
}

const openEditDialog = _infoData => {
  editMode.value = true
  certificateFileModel.value = null

  newRecord.value = {
    id: _infoData.id,
    nit: _infoData.nit,
    dv: _infoData.dv,
    branch: _infoData.branch,
    patient_id: _infoData.patient_id,
    code: _infoData.code,
    provider_code: _infoData.provider_code,
    name: _infoData.name,
    firstname: _infoData.firstname,
    lastname: _infoData.lastname,
    comercial_name: _infoData.comercial_name,
    address: _infoData.address,
    phone: _infoData.phone,
    email: _infoData.email,
    latitude: _infoData.latitude,
    longitude: _infoData.longitude,
    zip_code: _infoData.zip_code,
    nit_representative: _infoData.nit_representative,
    contact_phone: _infoData.contact_phone,
    name_contact: _infoData.name_contact,
    email_contact: _infoData.email_contact,
    health_contract_number: _infoData.health_contract_number,
    health_policy_number: _infoData.health_policy_number,
    credit_quota: _infoData.credit_quota,
    deadline_days: _infoData.deadline_days,
    point: _infoData.point,
    accumulated_points: _infoData.accumulated_points,
    birthday: _infoData.birthday,
    last_purchase_date: _infoData.last_purchase_date,
    creation_date: _infoData.creation_date,
    economic_activity: _infoData.economic_activity,
    business_registration: _infoData.business_registration,
    sales_account: _infoData.sales_account,
    center: _infoData.center,
    scenter: _infoData.scenter,

    // ⚠️ Todos estos son FKs que alimentan AppSelect -> normalizamos a número
    health_service_coverage_id: toId(_infoData.health_service_coverage_id),
    health_payment_method_id: toId(_infoData.health_payment_method_id),
    branch_id: toId(_infoData.branch_id),
    route_id: toId(_infoData.route_id),
    zone_id: toId(_infoData.zone_id),
    type_id: toId(_infoData.type_id),
    neighborhood_id: toId(_infoData.neighborhood_id),
    price_list_id: toId(_infoData.price_list_id),
    municipalities_id: toId(_infoData.municipalities_id),
    sellers_id: toId(_infoData.sellers_id),
    type_document_identification_id: toId(_infoData.type_document_identification_id),
    companies_id: toId(_infoData.companies_id),
    type_regime_id: toId(_infoData.type_regime_id),
    type_liability_id: toId(_infoData.type_liability_id),

    sex: _infoData.sex,
    state: _infoData.state,
    typeofcurrency: _infoData.typeofcurrency,
    retesource: _infoData.retesource,
    reteiva: _infoData.reteiva,
    reteica: _infoData.reteica,
    declare_income: _infoData.declare_income,
    control_points: _infoData.control_points,
    capture_signature: _infoData.capture_signature,
    usercreate: _infoData.usercreate,
    userupdate: _infoData.userupdate,
  }

  showDialog.value = true
}

// 🔹 Abrir modal en modo edición
const openEditDialog1 = _infoData => {
  editMode.value = true
  certificateFileModel.value = null

  // console.log('Soy Grupo y SubGrupo :', _infoData.group_name, '-', _infoData.sgroup_name)
  newRecord.value = {
    id: _infoData.id,
    nit: _infoData.nit,
    dv: _infoData.dv,
    branch: _infoData.branch,
    patient_id: _infoData.patient_id,
    code: _infoData.code,
    provider_code: _infoData.provider_code,
    name: _infoData.name,
    firstname: _infoData.firstname,
    lastname: _infoData.lastname,
    comercial_name: _infoData.comercial_name,
    address: _infoData.address,
    phone: _infoData.phone,
    email: _infoData.email,
    latitude: _infoData.latitude,
    longitude: _infoData.longitude,
    zip_code: _infoData.zip_code,
    nit_representative: _infoData.nit_representative,
    contact_phone: _infoData.contact_phone,
    name_contact: _infoData.name_contact,
    email_contact: _infoData.email_contact,
    health_contract_number: _infoData.health_contract_number,
    health_policy_number: _infoData.health_policy_number,
    credit_quota: _infoData.credit_quota,
    deadline_days: _infoData.deadline_days,
    point: _infoData.point,
    accumulated_points: _infoData.accumulated_points,
    birthday: _infoData.birthday,
    last_purchase_date: _infoData.last_purchase_date,
    creation_date: _infoData.creation_date,
    economic_activity: _infoData.economic_activity,
    business_registration: _infoData.business_registration,
    sales_account: _infoData.sales_account,
    center: _infoData.center,
    scenter: _infoData.scenter,
    health_service_coverage_id: _infoData.health_service_coverage_id,
    health_payment_method_id: _infoData.health_payment_method_id,
    branch_id: _infoData.branch_id,
    route_id: _infoData.route_id,
    zone_id: _infoData.zone_id,
    type_id: _infoData.type_id,
    neighborhood_id: _infoData.neighborhood_id,
    price_list_id: _infoData.price_list_id,
    municipalities_id: _infoData.municipalities_id,
    sellers_id: _infoData.sellers_id,
    type_document_identification_id: _infoData.type_document_identification_id,
    companies_id: _infoData.companies_id,
    type_regime_id: _infoData.type_regime_id,
    type_liability_id: _infoData.type_liability_id,
    sex: _infoData.sex,
    state: _infoData.state,
    typeofcurrency: _infoData.typeofcurrency,
    retesource: _infoData.retesource,
    reteiva: _infoData.reteiva,
    reteica: _infoData.reteica,
    declare_income: _infoData.declare_income,
    control_points: _infoData.control_points,
    capture_signature: _infoData.capture_signature,
    usercreate: _infoData.usercreate,
    userupdate: _infoData.userupdate,
  }

  // ✅ Verificar que el valor llega
  console.log('Soy Id Edit :', _infoData.id)

  showDialog.value = true
}

// 🔹 Abrir modal en modo creación
const openCreateDialog = () => {
  editMode.value = false
  newRecord.value = {
    id: null,
    nit: '',
    branch: '01',
    dv: '',
    patient_id: '',
    code: '',
    provider_code: '',
    name: '',
    firstname: '',
    lastname: '',
    comercial_name: '',
    phone: '',
    email: '',
    latitude: '',
    longitude: '',
    zip_code: '',
    nit_representative: '',
    contact_phone: '',
    name_contact: '',
    email_contact: '',
    health_contract_number: '',
    health_policy_number: '',
    credit_quota: 0,
    deadline_days: 0,
    point: 0,
    accumulated_points: 0,
    birthday: (hoy),
    last_purchase_date: (hoy),
    creation_date: (hoy),
    economic_activity: '',
    business_registration: '',
    sales_account: '',
    center: '',
    scenter: '',
    health_service_coverage_id: null,
    health_payment_method_id: null,
    branch_id: null,
    route_id: null,
    zone_id: null,
    type_id: null,
    neighborhood_id: null,
    price_list_id: null,
    municipalities_id: null,
    sellers_id: undefined,
    type_document_identification_id: 3,
    companies_id: 0,
    type_regime_id: null,
    type_liability_id: null,
    sex: 'Masculino',
    state: 'Activo',
    typeofcurrency: 'Pesos',
    retesource: 'No',
    reteiva: 'No',
    reteica: 'No',
    declare_income: 'No',
    control_points: 'No',
    capture_signature: 'No',
  }

  // console.log('🆕 Abriendo modal para nuevo clientes :', newRecord.value.type_document_identification_id, ' TypeIdent:', typedocument.value)
  showDialog.value = true
}

// 🔹 Abrir confirmación de eliminación
const confirmDelete = (id: number) => {
  console.log('🛑 Confirmar eliminación del Cliente ID:', id)
  recordToDelete.value = id
  nameRecordToDelete.value = infoData.value.find(c => c.id === id)?.name || ''
  showConfirmDialog.value = true
}

// 🔹 Eliminar empresa
const deleteRecord = async () => {
  if (!recordToDelete.value)
    return

  try {
    await $api(`/api/customers/${recordToDelete.value}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    })
    loadInfo()
    snackbarMessage.value = '✅ Cliente eliminado correctamente'
    snackbarColor.value = 'success'
  }
  catch (error) {
    console.error('❌ Error al eliminar el Cliente:', error)
    snackbarMessage.value = '❌ Error al eliminar el Cliente'
    snackbarColor.value = 'error'
  }
  finally {
    showConfirmDialog.value = false
    recordToDelete.value = null

    showSnackbar.value = false
    nextTick(() => (showSnackbar.value = true))
  }
}

const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (file)
    certificateFile.value = file
}

const validateFileSize = (v: File | File[] | null) => {
  if (!v)
    return true // Sin archivo, válido
  const file = Array.isArray(v) ? v[0] : v // Maneja array o archivo directo
  if (!file)
    return true

  return file.size < 2048000 || 'El archivo no debe superar 2MB'
}

// ✅ Computed de items filtrados
const registrosFiltrados = computed(() => {
  // 1. Si no hay búsqueda o no hay datos, devolvemos el array original (o vacío si es null/undefined)
  if (!searchQuery.value || !infoData.value?.length)
    return infoData.value ?? []

  // 2. Si pasa el filtro anterior, normalizamos la búsqueda en minúsculas
  const q = searchQuery.value.toLowerCase()

  // 3. Filtramos los registros de forma segura
  return infoData.value.filter(item =>
    Object.values(item).some(val =>
      String(val ?? '').toLowerCase().includes(q)),
  )
})

//   const file = Array.isArray(certificateFileModel.value)
//     ? certificateFileModel.value[0]
//     : certificateFileModel.value as File

//   return file?.name ?? newCompany.value.certificatename
// })

const formatCurrency = (value: number | string) => {
  const num = Number(value) || 0

  return num.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function useNumericField(targetObject, propertyName, maxDecimals = 2) {
  // const isFocused = ref(false)

  const formattedValue = computed({
    get() {
      const value = targetObject.value[propertyName]
      if (value === null || value === undefined || value === '')
        return ''
      if (isFocused.value)
        return value

      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: maxDecimals,
      }).format(value)
    },
    set(newValue) {
      const parsed = Number.parseFloat(newValue)

      targetObject.value[propertyName] = isNaN(parsed) ? null : parsed
    },
  })

  const onlyNumbersAndDot = event => {
    const charCode = event.which ? event.which : event.keyCode
    if (charCode === 46) {
      if (String(formattedValue.value).includes('.'))
        event.preventDefault()

      return true
    }
    if (charCode >= 48 && charCode <= 57)
      return true
    event.preventDefault()
  }

  return { formattedValue, onlyNumbersAndDot, isFocused }
}// Instancias la función para cada campo de tu formulario
const cupoField = useNumericField(newRecord, 'credit_quota')
const plazoField = useNumericField(newRecord, 'deadline_days')
const puntosField = useNumericField(newRecord, 'point')
const acumpuntosField = useNumericField(newRecord, 'accumulated_points')

// const renField = useNumericField(newRecord, 'profitability')
// const stckminField = useNumericField(newRecord, 'minimum_stocky')
// const stckmaxField = useNumericField(newRecord, 'maximum_stock')
// const ivaField = useNumericField(newRecord, 'percent')

// // const totalField = useNumericField(newRecord, 'total_amount')
// const saleslField = useNumericField(newRecord, 'sale_value')
// const costField = useNumericField(newRecord, 'cost')
// const factField = useNumericField(newRecord, 'conversion_factor')
// const weightField = useNumericField(newRecord, 'weight_volume')
// const unitxpackField = useNumericField(newRecord, 'units_per_packaging')

// Función que calcula el Dígito de Verificación oficial de la DIAN
const calcularDV = nit => {
  if (!nit || isNaN(nit))
    return ''

  const vpri = [0, 71, 67, 59, 53, 47, 43, 41, 37, 29, 23, 19, 17, 13, 7, 3]
  let x = 0
  let y = 0

  // Limpiar el nit por si llega a tener puntos o guiones
  const nitString = nit.toString().replace(/\D/g, '')
  const len = nitString.length

  for (let i = 0; i < len; i++) {
    y = Number.parseInt(nitString.substr(i, 1), 10)
    x += y * vpri[16 - len + i]
  }

  y = x % 11

  if (y > 1)
    return (11 - y).toString()
  else
    return y.toString()
}

// Watcher interactivo: Escucha los cambios en newRecord.nit
watch(
  () => newRecord.value.nit, // Si usas "reactive" cambia a: () => newRecord.nit
  nuevoNit => {
    if (nuevoNit) {
      // Calcula el DV y lo asigna automáticamente al campo de tu formulario
      newRecord.value.dv = calcularDV(nuevoNit)
    }
    else {
      newRecord.value.dv = ''
    }
  },
)

// Watcher que escucha los cambios en nombres, apellidos y el tipo de documento
watch(
  () => [
    newRecord.value.firstname,
    newRecord.value.lastname,
    newRecord.value.type_document_identification_id,
  ],
  ([nuevoNombre, nuevoApellido, tipoDocumento]) => {
    // CONDICIÓN: Solo une los campos si el documento es diferente a 6 (NIT)
    if (tipoDocumento !== 6) {
      const apellido = (nuevoApellido || '').trim()
      const nombre = (nuevoNombre || '').trim()

      // Secuencia de asignación: Apellidos + Nombres
      // Si ambos existen se separan con espacio, si no, se muestra el que esté disponible
      if (apellido && nombre)
        newRecord.value.name = `${apellido} ${nombre}`
      else
        newRecord.value.name = apellido || nombre
    }
  })

const headers = [
  { title: '#', key: 'id' },
  { title: 'Nit/Cédula', key: 'nit', sortable: true, width: '10%' },
  { title: 'Suc', key: 'branch', sortable: true, width: '10%' },
  { title: 'Nombre', key: 'name', sortable: true, width: '35%' },
  { title: 'dirección', key: 'address', sortable: true, width: '10%' },
  { title: 'Teléfono', key: 'phone', sortable: true },
  { title: 'email', key: 'email', sortable: true },
  { title: 'Ciudad', key: 'city_name', sortable: true, align: 'start' },
  { title: 'Estado', key: 'state', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false },
]
</script>

<template>
  <!-- <VCardText class="d-flex justify-space-between align-center flex-wrap gap-4 toolbar-header">  -->
  <VCard class="mb-1 mt-1 py-3 px-4 justify-space-berween">
    <VRow class="align-center">
      <VCol
        cols="12"
        md="2"
        class="d-flex align-left flex-column"
      >
        <h3 class="text-primary mb-2">
          Mantenimiento de Clientes
        </h3>
        <VCardText class="d-flex align-center flex-wrap gap-4 pa-0">
          <VTextField
            v-model="searchQuery"
            placeholder="Buscar..."
            density="compact"
            prepend-inner-icon="tabler-search"
            variant="outlined"
            clearable
            hide-details
            style="inline-size: 20em;max-inline-size: 300px;"
          />
        </VCardText>
      </VCol>

      <VCol
        cols="12"
        md="8"
        class="d-flex align-center flex-column"
      />

      <VCol
        cols="12"
        md="2"
        class="d-flex align-right justify-start mt-md-5 mt-2"
      >
        <VBtn
          rounded="pill"
          color="primary"
          variant="flat"
          block
          @click="openCreateDialog"
        >
          <template #prepend>
            <VIcon
              icon="tabler-plus"
              size="20"
            />
          </template>
          Nuevo Cliente
        </VBtn>
      </VCol>
    </VRow>
  </VCard>
  <!-- <section v-if="companies && companies.length"></section> -->
  <section>
    <VCard id="grid-list">
      <VDivider />

      <VDataTable
        v-model:model-value="selectedRows"
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        density="compact"
        border-cells="true"
        show-select
        :striped-rows="true"
        :headers="headers"
        :items="infoData"
        :search="searchQuery"
        item-value="id"
        class="text-no-wrap text-body-2 grid-table"
        @update:options="updateOptions"
      >
        <!--
          <template #item.id="{ item }">
          <div class="cell-wrap columna_size">
          {{ item.id }}
          </div>
          </template>

          <template #item.code="{ item }">
          <div class="cell-wrap columna_name">
          {{ item.code }}
          </div>
          </template>

          <template #item.name="{ item }">
          <div class="cell-wrap columna_name">
          {{ item.name }}
          </div>
          </template>

          <template #item.codereference="{ item }">
          <div class="cell-wrap columna_name">
          {{ item.codereference }}
          </div>
          </template>

          <template #item.group_name="{ item }">
          <div class="cell-wrap columna_name">
          {{ item.group_name }}
          </div>
          </template>

          <template #item.presentation="{ item }">
          <div class="cell-wrap columna_name">
          {{ item.presentation }}
          </div>
          </template>

          <template #item.percent="{ item }">
          <div class="cell-wrap columna_name">
          {{ item.percent }}
          </div>
          </template>

          <template #item.sale_value="{ item }">
          <div class="cell-wrap columna_name">
          {{ formatCurrency(item.sale_value) }}
          </div>
          </template>

          <template #item.cost="{ item }">
          <div class="cell-wrap columna_name">
          {{ formatCurrency(item.cost) }}
          </div>
          </template>
        -->

        <template #item.state="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.state }}
          </div>
        </template>

        <template #item.actions="{ item }">
          <IconBtn @click="openEditDialog(item)">
            <VIcon
              icon="tabler-edit"
              color="primary"
            />
          </IconBtn>

          <IconBtn
            :disabled="tipodeusuario === 'Operador'"
            @click="confirmDelete(item.id)"
          >
            <VIcon
              icon="tabler-trash"
              :color="tipodeusuario === 'Operador' ? 'grey' : 'error'"
            />
          </IconBtn>
        </template>

        <template #bottom>
          <VDivider />
          <VRow class="mt-2 mx-0 pb-2 align-center">
            <VCol
              cols="12"
              md="4"
            >
              <div class="text-caption text-medium-emphasis ps-4">
                Mostrando
                <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                <strong>{{ Math.min(currentPage * perPage, totalRecords) }}</strong>
                de <strong>{{ totalRecords }}</strong> registros
              </div>
            </VCol>
            <VCol
              cols="12"
              md="4"
              class="d-flex justify-center pagination-wrapper"
            >
              <VPagination
                v-model="page"
                :length="Math.ceil(totalRecords / perPage)"
                rounded="circle"
                size="large"
                :total-visible="5"
              />
            </VCol>
          </VRow>
        </template>
      </VDataTable>
    </VCard>

    <VSnackbar
      v-model="showSnackbar"
      :color="snackbarColor"
      location="center"
      timeout="3000"
      multi-line
      elevation="2"
    >
      <div class="d-flex align-center">
        <VIcon
          :icon="snackbarColor === 'success' ? 'tabler-check' : 'tabler-alert-triangle'"
          size="25"
          class="me-2"
        />
        <span class="text-lg">{{ snackbarMessage }}</span>
      </div>
    </VSnackbar>

    <!-- 🌟 Popup Modal para nueva empresa -->
  </section>

  <VDialog
    v-model="showDialog"
    persistent
    max-width="1100px"
    attach="#app"
  >
    <VCard>
      <!-- <VCardTitle class="text-h5 bg-primary text-white py-4 px-4">Agregar nueva empresa</VCardTitle> -->
      <VCardTitle class="modal-title d-flex align-center text-h5">
        <VIcon
          icon="tabler-building"
          size="28"
          color="white"
          class="me-3"
        />
        {{ newRecord.id ? 'Actualizando un Cliente' : 'Agregando un Cliente' }}
        <span
          class="text-h5 font-weight-bold ml-2"
          style="color: #f7fb2d !important;"
        >
          ID: {{ String(newRecord.id || 0).padStart(8, '0') }}
        </span>
      </VCardTitle>

      <VCardText
        mb="4"
        class="pt-4 pb-2"
      >
        <VDefaultsProvider
          :defaults="{
            AppTextField: {
              density: 'comfortable',
              variant: 'outlined',
              hideDetails: true,
              class: 'mb-3 text_size aligned-field', // También puedes incluir clases comunes
            },
            AppSelect: {
              density: 'comfortable',
              variant: 'outlined',
              hideDetails: true,
              class: 'mb-3 text_size aligned-field',
            },
          }"
        >
          <VForm @submit.prevent="saveRecord">
            <VRow
              dense
              align="center"
              class="g-2"
            >
              <!-- Nit/Cédula -->
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.nit"
                  label="Nit/Cédula"
                  autofocus
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Nit/Cédula"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.nit = val.replace(/\D/g, '')"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-qrcode"
                      color="primary"
                      size="18"
                      class="me-1"
                    />
                  </template>
                </AppTextField>
              </VCol>

              <!-- DV -->
              <VCol
                cols="12"
                md="1"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.dv"
                  label="DV"
                  disabled
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="DV"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                />
              </VCol>

              <!-- Sucursal (VSelect) -->
              <VCol
                cols="12"
                md="1"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.branch"
                  :items="['01', '02', '03']"
                  label="Sucursal"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-3 text_size aligned-field"
                  v-bind="$attrs"
                />
              </VCol>
              <VCol
                cols="12"
                md="1"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.type_document_identification_id"
                  :items="typedocument"
                  label="Tipo Dcto"
                  item-title="code_show"
                  item-value="id"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field select-compact"
                  v-bind="$attrs"
                />
              </VCol>

              <!-- Descripción del Producto -->
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.firstname"
                  label="Nombres del Cliente / Nombre Empresa"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Ingrese Nombre del Cliente"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.firstname = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-user-screen"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.lastname"
                  label="Apellidos del Cliente"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Ingrese Apellidos del Cliente"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.lastname = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-user-screen"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
            </VRow>
            <VRow
              dense
              align="center"
              class="g-2 mt-0"
            >
              <VCol
                cols="12"
                md="6"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.name"
                  label="Nombre Completo del Cliente"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Ingrese NOmbre Completo del Cliente"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.name = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-user-screen"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="6"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.comercial_name"
                  label="Razón Social de la Empresa"
                  required
                  class="mb-3 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Ingrese Razón Social de la Empresa"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.comercial_name = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-building-factory-2"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
            </VRow>
            <VRow
              dense
              align="center"
              class="g-2 mt-0"
            >
              <VCol
                cols="12"
                md="6"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.address"
                  label="Dirección del Cliente"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Ingrese Dirección de la Cliente"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.address = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-map-pin"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="6"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.email"
                  label="Correo del Cliente"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="ejemplo@correo.com"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.email = val.trim().toLowerCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-mail"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
            </VRow>
            <VRow
              dense
              align="center"
              class="g-2 mt-0"
            >
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.phone"
                  label="Telefóno del Cliente"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Teléfono del Cliente"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.phone = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-phone"
                      color="primary"
                      size="15"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>

              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.code"
                  label="Código Cliente"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Ingrese Código"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.code = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-code"
                      color="primary"
                      size="15"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.contact_phone"
                  label="Telefóno del Contacto"
                  required
                  class="mb-2 text_size aligned-field"
                  placeholder="Teléfono del Contacto"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.contact_phone = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-phone"
                      color="primary"
                      size="15"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="6"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.name_contact"
                  label="Nombre del Contacto"
                  required
                  class="mb-3 text_size aligned-field"
                  placeholder="Ingrese Nombre del contacto"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.name_contact = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-user-circle"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
            </VRow>
            <VRow
              dense
              align="center"
              class="g-2 mt-0"
            >
              <VCol
                cols="12"
                md="6"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.email_contact"
                  label="Correo Electrónico del Contacto"
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.email]"
                  placeholder="ejemplo@correo.com"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.email_contact = val.trim().toLowerCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-mail"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>

              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.zip_code"
                  label="Código Postal"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Ingrese Código Postal"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.zipcode = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-brand-pnpm"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.latitude"
                  label="Latitud (Ubicación)"
                  class="mb-2 text_size aligned-field"
                  placeholder="Ingrese Latitud"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.latitude = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-current-location"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.longitude"
                  label="Longitud (Ubicación)"
                  class="mb-3 text_size aligned-field"
                  placeholder="Ingrese Longitud"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.longitude = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-current-location"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
            </VRow>
            <VRow
              dense
              align="center"
              class="g-2 mt-0"
            >
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppDateTimePicker
                  v-model="newRecord.birthday"
                  label="Fecha de Nacimiento :"
                  placeholder="Seleccionar Fecha"
                  class="text-center-input mb-2"
                  variant="outlined"
                  prepend-inner-icon="tabler-calendar"
                  :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
                />
              </VCol>

              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppDateTimePicker
                  v-model="newRecord.creation_date"
                  label="Fecha de Creación :"
                  placeholder="Seleccionar Fecha"
                  class="text-center-input mb-2"
                  variant="outlined"
                  prepend-inner-icon="tabler-calendar"
                  :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppDateTimePicker
                  v-model="newRecord.last_purchase_date"
                  label="Fecha Ultima Compra :"
                  placeholder="Seleccionar Fecha"
                  class="text-center-input mb-2"
                  variant="outlined"
                  prepend-inner-icon="tabler-calendar"
                  :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="cupoField.formattedValue.value"
                  label="Cupo de Cartera"
                  class="mb-2 text_size"
                  placeholder="Ingrese Cupo Cartera"
                  @keypress="cupoField.onlyNumbersAndDot"
                  @focus="cupoField.isFocused.value = true"
                  @blur="cupoField.isFocused.value = false"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-currency-dollar"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="plazoField.formattedValue.value"
                  label="Días de Plazo"
                  class="mb-2 text_size"
                  placeholder="Días de Plazo"
                  @keypress="plazoField.onlyNumbersAndDot"
                  @focus="plazoField.isFocused.value = true"
                  @blur="plazoField.isFocused.value = false"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-clock-12"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="acumpuntosField.formattedValue.value"
                  label="Puntos Acumulados"
                  class="mb-2 text_size"
                  placeholder="Puntos Acumulados"
                  @keypress="acumpuntosField.onlyNumbersAndDot"
                  @focus="acumpuntosField.isFocused.value = true"
                  @blur="acumpuntosField.isFocused.value = false"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-sum"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
            </VRow>
            <VRow
              dense
              align="center"
              class="g-2 mt-0"
            >
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.sellers_id"
                  :items="sellers"
                  label="Vendedor"
                  item-title="name"
                  item-value="id"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field cfg_select"
                  v-bind="$attrs"
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.price_list_id"
                  :items="lists"
                  label="Lista de Precios"
                  item-title="name"
                  item-value="id"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field cfg_select"
                  v-bind="$attrs"
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.type_id"
                  :items="typecust"
                  item-title="name"
                  item-value="id"
                  label="Tipo de Cliente"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field cfg_select"
                  v-bind="$attrs"
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.zone_id"
                  :items="zones"
                  item-title="name"
                  item-value="id"
                  label="Zona de Venta"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field cfg_select"
                  v-bind="$attrs"
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.route_id"
                  :items="routes"
                  item-title="name"
                  item-value="id"
                  label="Ruta de Venta"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field cfg_select"
                  v-bind="$attrs"
                />
              </VCol>
            </VRow>
            <VRow
              dense
              align="center"
              class="g-2 mt-0"
            >
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.municipalities_id"
                  :items="municipalities"
                  item-title="name"
                  item-value="id"
                  label="Ciudad"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field cfg_select"
                  v-bind="$attrs"
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.neighborhood_id"
                  :items="neighborhoods"
                  item-title="name"
                  item-value="id"
                  label="Barrios"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field cfg_select"
                  v-bind="$attrs"
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.sex"
                  :items="typecustomers"
                  item-title="name"
                  item-value="id"
                  label="Sexo"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field cfg_select"
                  v-bind="$attrs"
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.state"
                  :items="zones"
                  label="Estado"
                  item-title="name"
                  item-value="id"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field cfg_select"
                  v-bind="$attrs"
                />
              </VCol>
            </VRow>
            <VRow
              dense
              align="center"
              class="g-2 mt-0"
            >
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.economic_activity"
                  label="Activdad Económica"
                  required
                  class="mb-2 text_size aligned-field mt-0"
                  :rules="[rules.required]"
                  placeholder="Actividad Económica"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.economic_activity = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-database-dollar"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.zip_code"
                  label="Código Postal"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Código Postal"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.zip_code = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-brand-pnpm"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.business_registration"
                  label="Matrícula Mercantil"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Matricula Mercantil"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.business_registration = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-dialpad"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.type_regime_id"
                  :items="regimenes"
                  item-title="name"
                  item-value="id"
                  label="Tipo de Régimen"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field"
                  v-bind="$attrs"
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.type_liability_id"
                  :items="liabilities"
                  item-title="name"
                  item-value="id"
                  label="Responsabilidad Fiscal"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 text_size aligned-field"
                  v-bind="$attrs"
                />
              </VCol>
            </VRow>
            <VDivider
              color="dark"
              thickness="2"
            />

            <VCardActions class="justify-end mt-2">
              <VBtn
                color="success"
                variant="flat"
                class="text-white"
                @click="showDialog = false"
              >
                Cancelar
              </VBtn>
              <VBtn
                color="primary"
                variant="flat"
                class="text-white"
                @click="saveRecord"
              >
                Guardar
              </VBtn>
            </VCardActions>
          </vform>
        </VDefaultsProvider>
      </vcardtext>
    </VCard>
  </VDialog>

  <!-- ❗ Diálogo de confirmación de eliminación -->
  <VDialog
    v-model="showConfirmDialog"
    max-width="400px"
  >
    <VCard>
      <VCardTitle class="text-h6 text-center pt-4">
        <VIcon
          icon="tabler-alert-circle"
          color="warning"
          size="26"
          class="me-2"
        />
        Confirmar eliminación <br>
        {{ nameRecordToDelete }}
      </VCardTitle>
      <VCardText class="text-center">
        ¿Está seguro que desea eliminar este Cliente ?<br>
        <strong>Esta acción no se puede deshacer.</strong>
      </VCardText>
      <VCardActions class="justify-center pb-4">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="showConfirmDialog = false"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="error"
          variant="flat"
          @click="deleteRecord"
        >
          Eliminar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
#company-list {
  .company-list-filter {
    inline-size: 12rem;
  }
}

/* Paginación circular */

/* 1. Estilos para el componente VPagination (el que tienes en el slot #bottom) */
.pagination-wrapper {
  .v-pagination__first,
  .v-pagination__item,
  .v-pagination__next,
  .v-pagination__prev,
  .v-pagination__last {
    .v-btn {
      background-color: rgb(247, 58, 206) !important;

      /* Cambia el color de los iconos de flecha y números */
      // color: #0EE920 !important;

      .v-icon {
        color: rgb(250, 253, 245) !important;
      }
    }
  }
}

.modal-title {
  margin: 0;
  background-color: rgb(var(--v-theme-primary)); /* color primario del tema */
  border-start-end-radius: 6px;
  border-start-start-radius: 6px;
  color: white; /* texto blanco */
  font-size: 1.25rem;
  font-weight: 600;
  padding-block: 16px;
  padding-inline: 24px;
}

.columna_name {
  display: block;
  font-size: 0.85em;
  line-height: 1.3;         /* mejora legibilidad */
  overflow-wrap: break-word;

  // max-width: 600px;         /* ancho fijo */
  white-space: normal !important; /* permite salto de línea */
  word-wrap: break-word;    /* divide palabras largas */
}

/* Evita que el resto de columnas se vean afectadas */
// .company-table :deep(td),
// .company-table :deep(th) {
//   white-space: nowrap;
// }

/* 🌟 Bordes verticales para VDataTableServer */
thead th {
  background-color: rgb(247, 58, 206) !important;
  color: white !important;
}

.v-data-table__thead th {
  color: white !important;
}

/* Apunta directamente al elemento input nativo dentro de tu componente */
/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.text_size :deep(input) {
  font-size: 14px !important;
}

.--v-field-padding-start {
  font-size: 6px !important;
}

/* Opcional: Si también quieres cambiar el tamaño de la etiqueta (Label) */
/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.text_size :deep(.v-label) {
  font-size: 10px !important;
}

/* Quita el borde derecho en la última columna */
.grid-table :deep(.v-data-table__td:last-child),
.grid-table :deep(.v-data-table__th:last-child) {
  border-inline-end: none;
}

/* Opcional: bordes suaves inferiores */
.grid-table :deep(.v-data-table__td) {
  border-block-end: 1px solid rgba(0, 0, 0, 8%) !important;
}

.grid-table :deep(.v-data-table__wrapper) {
  overflow: visible !important;
}

.grid-table :deep(.v-data-table__td),
.grid-table :deep(.v-data-table__th) {
  border-inline-end: 1px solid rgba(var(--v-theme-on-surface), 0.15) !important;
}

/* Botón mejor alineado */
.toolbar-header .v-btn {
  block-size: 40px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 8%);
  font-weight: 500;
}

.v-overlay {
  position: fixed !important;
  z-index: 9999 !important;
}

.textarea {
  font-size: 12px !important;
  line-height: 1.4;
}

textarea {
  block-size: 80px !important;
  font-size: 0.85em !important;
}

.v-field__input {
  font-size: 0.84em !important;
}

.columna_size {
  display: block;
  font-size: 0.9em;
  line-height: 1.3;         /* mejora legibilidad */
  overflow-wrap: break-word;
  white-space: normal !important; /* permite salto de línea */
  word-wrap: break-word;    /* divide palabras largas */
}

.column_date_size {
  font-size: 0.9em;
  line-height: 1.3;         /* mejora legibilidad */

  // min-height: 56px!important;
  margin-block-start: 0 !important;
  padding-block-start: 0 !important;

  // width: 20em !important;
  white-space: normal !important; /* permite salto de línea */
}

.text-center-input input {
  cursor: pointer;
  text-align: center !important;
}

/* Forzar que el calendario de Flatpickr esté sobre el VDialog */
.flatpickr-calendar {
  z-index: 10000 !important;
}

.v-data-table thead th {
  text-transform: capitalize !important;
}

.v-data-table thead th .v-table {
  color: white !important;
}

.v-data-table-header__content {
  color: white !important;
}

.row-uniform-margin > .v-col > * {
  margin-block-start: 12px !important; /* o el valor que quieras */
}

//   .v-col {
//   display: flex;
//   align-items: center;
// }

/* Corrige el colapso del VSelect y lo nivela con el AppTextField */
:deep(.custom-select-height .v-field) {
  align-items: center !important;
  block-size: 44px !important; /* Ajusta a 40px o 42px si notas que queda un poco más alto que el NIT */
}

:deep(.custom-select-height .v-field__input) {
  min-block-size: 100% !important;
  padding-block: 0 !important;
}

/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
:deep(.v-file-upload) {
  min-block-size: 120px !important;
  padding-block: 8px !important;
  padding-inline: 16px !important;
}

/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
:deep(.v-file-upload-divider) {
  margin-block: 4px !important;
  margin-inline: 0 !important;
}

/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.custom-select-height :deep(.v-field) {
  min-block-size: 32px !important;
}

 /* stylelint-disable-next-line @stylistic/indentation */
 /* stylelint-disable-next-line selector-pseudo-class-no-unknown */
 :deep(.v-label) {
   /* stylelint-disable-next-line @stylistic/indentation */
   font-size: 0.4 rem !important;
}

  /* Forzar la misma altura para todos los campos */
  :deep(.custom-field .v-field),
  :deep(.custom-select .v-field) {
    max-block-size: 48px !important;
    min-block-size: 48px !important;
  }

  /* Alinear el input y el label */
  :deep(.custom-field .v-field__input),
  :deep(.custom-select .v-field__input) {
    min-block-size: 48px !important;
    padding-block: 4px !important;
  }

  /* Ajustar el label flotante */
  :deep(.custom-select .v-label) {
    inset-block-start: 12px !important;
    transform-origin: left center !important;
  }

  /* Cuando el label está flotando (arriba) */
  :deep(.custom-select .v-field--focused .v-label),
  :deep(.custom-select .v-field--dirty .v-label) {
    inset-block-start: 4px !important;
    transform: scale(0.75) !important;
  }

  /* Ajustar el placeholder */
  :deep(.custom-select .v-field .v-field__input input::placeholder) {
    opacity: 0;
  }

  /* Para mantener consistencia con AppTextField */
  :deep(.custom-field .v-label),
  :deep(.custom-select .v-label) {
    font-size: 14px !important;
  }

 /* stylelint-disable-next-line @stylistic/indentation */
 .contenedor-alineado {
  display: flex; /* Activa la caja flexible */
  flex-direction: row; /* Coloca los componentes uno al lado del otro */
  align-items: flex-end; /* Alinea todos los componentes exactamente en la misma línea superior */
  gap: 20px; /* Espacio opcional entre los componentes */
}

.contenedor-alineado1 {
  display: inline-flex;
  align-items: flex-start; /* Garantiza el mismo nivel top para todos */
  justify-content: center; /* Centra los componentes horizontalmente */
  gap: 50px; /* Separación horizontal */
}

/* ========== SOLUCIÓN DEFINITIVA ========== */

/* 1. Forzar la misma altura para todos los campos */
.aligned-field :deep(.v-field),
.aligned-select :deep(.v-field) {
  block-size: 48px !important;
  max-block-size: 48px !important;
  min-block-size: 48px !important;
}

/* 2. Alinear el padding interno */
.aligned-field :deep(.v-field__input),
.aligned-select :deep(.v-field__input) {
  display: flex !important;
  align-items: center !important;
  min-block-size: 48px !important;
  padding-block: 4px !important;
}

/* 3. Ajustar el label para que esté alineado con los demás */
.aligned-field :deep(.v-label),
.aligned-select :deep(.v-label) {
  font-size: 14px !important;
  inset-block-start: 12px !important;
  transform-origin: left center !important;
}

/* 4. Cuando el label está flotando (arriba) */
.aligned-field :deep(.v-field--focused .v-label),
.aligned-field :deep(.v-field--dirty .v-label),
.aligned-select :deep(.v-field--focused .v-label),
.aligned-select :deep(.v-field--dirty .v-label) {
  inset-block-start: 4px !important;
  transform: scale(0.75) !important;
}

/* 5. Ajustar el placeholder (ocultarlo para que no se solape) */
.aligned-field :deep(.v-field .v-field__input input::placeholder),
.aligned-select :deep(.v-field .v-field__input input::placeholder) {
  opacity: 0 !important;
}

/* 6. Ajustar el prepend-inner para que esté alineado verticalmente */
.aligned-field :deep(.v-field__prepend-inner),
.aligned-select :deep(.v-field__prepend-inner) {
  align-self: flex-start !important;
  padding-block-start: 12px !important;
}

/* 7. Envoltorio para el VSelect (control extra) */
.select-wrapper {
  display: flex;
  align-items: flex-start;
  block-size: 48px;
  padding-block-start: 0;
}

/* 8. Opcional: eliminar el margen inferior del VSelect para que coincida */
.aligned-select {
  margin-block-end: 0 !important;
}

/* Ajuste fino para que todos los campos tengan el mismo aspecto */
.aligned-field,
.aligned-select {
  inline-size: 100%;
}

/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.select-compact :deep(.v-field__input) {
  min-inline-size: 0 !important;
  padding-inline: 6px 0 !important;
}

/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.select-compact :deep(.v-field__field) {
  padding-inline: 0 !important;
}

/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.select-compact :deep(.v-select__selection) {
  overflow: visible !important;
  margin-inline-end: 0 !important;
  text-overflow: unset !important;
  white-space: nowrap !important;
}

/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.select-compact :deep(.v-field__append-inner) {
  margin-inline-start: -4px !important; /* acerca la flecha del dropdown */
  padding-inline-start: 0 !important;
}

.select-compact :deep(.v-field__append-inner .v-icon) {
  font-size: 16px;
}
</style>

<style>
/* Estilo GLOBAL, sin scoped, para que penetre cualquier wrapper */
.aligned-field .v-select__selection-text {
  /* overflow: visible !important; */

  /* text-overflow: unset !important;
  white-space: nowrap !important; */
}

.aligned-field .v-field__input {
  padding-inline: 8px !important;
}

.cfg_select .v-select__selection-text {
  font-size: 0.65rem;
}
</style>
