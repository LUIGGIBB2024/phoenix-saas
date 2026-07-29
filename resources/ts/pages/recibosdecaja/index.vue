<script setup lang="ts">
import axios from 'axios'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { VIcon } from 'vuetify/components'
import { VCard, VCardText } from 'vuetify/components/VCard'
import { VCol } from 'vuetify/components/VGrid'
import type { CxPPayment } from './components/CrearEgresosDialog.vue' // Ajusta la ruta de tu diálogo
// import ConsultarEgresosFacturasDialog from './vdialogs/ConsultarEgresosFacturasDialog.vue'
// import ConsultarEgresosOtrosPagosDialog from './vdialogs/ConsultarEgresosOtrosPagosDialog.vue'
// import CrearEgresosDialog from './vdialogs/CrearEgresosDialog.vue'

const emit = defineEmits(['save', 'close'])
const isDialogOpen = ref(false)
const egresosList = ref([])
const isSaving = ref(false)

const selectedDocument = ref('')
const NombreDelCliente = ref('')
const DocumentoSeleccionado = ref('')

// 1. Declaramos la variable reactiva
const tipoDeEgreso = ref<string>('')

const crearRecibosoDialog = ref<InstanceType<typeof CrearEgresosDialog> | null>(null)
const consultarRecibosDialog = ref<InstanceType<typeof ConsultarEgresosFacturasDialog> | null>(null)
const consultarRecibosOtrosDialog = ref<InstanceType<typeof ConsultarEgresosOtrosPagosDialog> | null>(null)

// import type { Product } from './type'

const archivos = ref<File[]>([])

const isFocused = ref(false)
const isDialogActive = ref(false)
const infofactura = ref('')

isDialogActive.value = false

const showDetailsPayment = ref(false)
const showDetailsOtherPayment = ref(false)

// const certificateNombre = ref('')
const certificateFile = ref<File | null>(null)
const certificateFileModel = ref<File | File[] | null>(null)

const inputRef = ref<HTMLInputElement | null>(null)
const autocompleteProductoKey = ref<number>(0)

const tipodeusuario = ref(localStorage.getItem('tipo_de_usuario'))
const process_year = ref(localStorage.getItem('process_year'))

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files)
    archivos.value = Array.from(target.files)
}

function formatoMoneda1(valor: number): string {
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(valor || 0)
}

function formatoMoneda(valor: number): string {
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(valor || 0)
}

// 🔹 Filtros y variables de estado
const purchaseinvoice = ref('')
const searchQuery = ref('')
const selectedRows = ref([])
const costoActual = ref(0)
const newregistro = ref(0)

newregistro.value = 0

// Propiedad computada que el input va a leer
const costoFormateado = computed(() => {
  return formatCurrency(costoActual.value, 2)
})

const dialog = ref(false)
const valid = ref(true)
const form = ref<HTMLFormElement | null>(null)

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const hoy = new Date().toISOString().split('T')[0]
const token = localStorage.getItem('auth_token')

// const token = localStorage.getItem('auth_token')

const accessToken = useCookie('accessToken', { path: '/' })

const productoSeleccionado = ref<Producto | null>(null)
const productoInfo = ref<Producto | null>(null)

// accessToken.value = response.data.token // ← el que te devuelve Laravel

const updateOptions = async (options: any) => {
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// const editedItem = reactive<CxPPayment>({ ...defaultItem })

const nitRules = [
  (v: string) => (v && v.length <= 20) || 'El NIT no debe exceder los 20 caracteres',
]

const lapseRules = [
  (v: string) => (v && /^\d{6}$/.test(v)) || 'El Periodo debe ser un número de 6 dígitos (YYYYMM)',
]

const dateRules = [
  (v: string) => (v && /^\d{4}-\d{2}-\d{2}$/.test(v)) || 'Formato de fecha inválido (YYYY-MM-DD)',
]

const valueRules = [
  (v: number) => (v !== null && v >= 0) || 'El valor debe ser un número positivo',
]

const open = (item: CxPPayment | null = null) => {
  if (item)
    Object.assign(editedItem, item)
  else
    Object.assign(editedItem, defaultItem)

  dialog.value = true
}

const close = () => {
  dialog.value = false
  form.value?.resetValidation()
  emit('close')
}

const save = async () => {
  const { valid } = await form.value!.validate()
  if (valid) {
    emit('save', editedItem)
    close()
  }
}

// Exponer la función open para que el componente padre pueda llamarla
defineExpose({
  open,
})

// --- 🔹 Modal y formulario de creación ---
const showDialog = ref(false)
const editMode = ref(false) // 👈 false = crear, true = editar
const showComprasDialog = ref(false) // Formulario de Compras
const showCarguesDialog = ref(false)
const showSaveChargesDialog = ref(false)
const showConfirmDialogCompras = ref(false)

const newRecord = ref<Document>({
  id: 0,
  nit: '',
  branch: '',
  name: '',
  number: 0,
  concept_inv: '',
  concept_class: '001',
  report_date: (hoy),
  purchase_invoice: 0,
  prefix: '',
  documento_purchase: '',
  order_number: '',
  date_from: (hoy),
  date_to: (hoy),
  subtotal: 0,
  vatvalue: 0,
  reteiva: 0,
  reteica: 0,
  products_discount: 0,
  additional_discounts: 0,
  additional_value: 0,
  freight: 0,
  total_purchases: 0,
  plate: '',
  type: 'Otras',
  type_of_purchase: 'Contado',
  state: 'Activo',
  state01: '',
  state02: '',
  state03: '',
  companies_id: null,
  proyect: '',
  sproyect: '',
  center: '',
  activity: '',
  observations: '',
  created_at: new Date(),
  updated_at: new Date(),
  usercreate: 'System',
  userupdate: 'System',
})

// 🔹 Snackbar (toast)
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

const responseData = ref({
  data: [],
  payments: [],
  customers: [],
  sources: [],
  otherexpenses: [],
  docspayments: [],
  docspaymentsothers: [],
  details: [],
  totaldocumentos: 0,
  page: 1,
  per_page: 10,
})

// 🔹 Diálogo de confirmación de eliminación
const payments = ref([])
const customers = ref([])
const otherexpenses = ref([])
const sources = ref([])
const docspayments = ref([])
const docspaymentsothers = ref([])
const details = ref([])
const detailsothr = ref([])

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

// ===================== Watchers =====================
// Al seleccionar un producto, se sugiere su precio de inventario, pero queda editable
// watch(productoSeleccionado, producto => {
//   precioUnitarioManual.value = producto ? aNumero(producto.price) : 0
// })

// 🔹 Observa el estado del diálogo
watch(showDialog, isOpen => {
  if (isOpen && !editMode.value) {
    // Se abre el diálogo → limpiar los campos
    newRecord.value = {
      id: null,
      nit: '',
      branch: '01',
      name: '',
      number: 0,
      concept_inv: '',
      concept_class: '001',
      report_date: (hoy),
      purchase_invoice: 0,
      prefix: '',
      documento_purchase: '',
      order_number: '',
      date_from: (hoy),
      date_to: (hoy),
      subtotal: 0,
      vatvalue: 0,
      reteiva: 0,
      reteica: 0,
      products_discount: 0,
      additional_discounts: 0,
      additional_value: 0,
      freight: 0,
      total_purchases: 0,
      plate: '',
      type: 'Otras',
      type_of_purchase: 'Crédito',
      state: 'Activo',
      state01: '',
      state02: '',
      state03: '',
      companies_id: 1,
      proyect: '',
      sproyect: '',
      center: '',
      activity: '',
      observations: '',
      created_at: new Date(),
      updated_at: new Date(),
      usercreate: 'System',
      userupdate: 'System',
    }
  }
})

const loadInfo = async () => {
  try {
    const response = await axios.get('/api/getcustomerpayments', {
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

    payments.value = responseData.value.data
    customers.value = responseData.value.customers
    sources.value = responseData.value.sources
    docspayments.value = responseData.value.docspayments
    docspaymentsothers.value = responseData.value.docspaymentsothers
    otherexpenses.value = responseData.value.otherexpenses
    details.value = responseData.value.details
  }
  catch (error) {
    console.error('Error al intentar enviar correo :', error)
  }
}

// 🔹 Ejecutar al montar
onMounted(() => loadInfo())

export interface Supplier {
  id: number
  name: string
  nit: string
  branch: string
  dv: string
}

export interface Concept {
  id: number
  code: string
  name: string
}

export interface Dctoscxp {
  id: number
  code: string
  name: string
}

const totalRecords = computed(() => responseData.value?.totaldocumentos ?? 0)

const infoData = computed(() => {
  const data = responseData.value.payments ?? []

  // console.log('Soy Data 001 - 999 : ', JSON.parse(JSON.stringify(data)))

  return data.map(p => ({
    ...p,
    regimen: p.regimen,
    typedocument: p.typedocument,

  }))
})

const perPage = computed(() => responseData.value.per_page ?? itemsPerPage.value)
const currentPage = computed(() => responseData.value.page ?? page.value)

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
    branch: _infoData.branch,
    name: _infoData.name,
    number: _infoData.number,
    concept_inv: _infoData.concept_inv,
    concept_class: _infoData.concept_class,
    report_date: _infoData.report_date,
    purchase_invoice: _infoData.purchase_invoice,
    prefix: _infoData.prefix,
    documento_purchase: _infoData.documento_purchase,
    order_number: _infoData.order_numer,
    date_from: _infoData.date_from,
    date_to: _infoData.date_to,
    subtotal: _infoData.subtotal,
    vatvalue: _infoData.vatvalue,
    reteiva: _infoData.reteiva,
    reteica: _infoData.reteica,
    products_discount: _infoData.products_discount,
    additional_discounts: _infoData.additional_discounts,
    additional_value: _infoData.additional_value,
    freight: _infoData.freight,
    total_purchases: _infoData.total_purchases,
    plate: _infoData.plate,
    type: _infoData.type,
    type_of_purchase: _infoData.type_of_purchase,
    state: _infoData.state,
    state01: _infoData.state01,
    state02: _infoData.state02,
    state03: _infoData.state03,
    proyect: _infoData.proyect,
    sproyect: _infoData.sproyect,
    center: _infoData.center,
    activity: _infoData.activity,

    // ⚠️ FK normalizada a número para alimentar AppSelect
    companies_id: toId(_infoData.companies_id),
    observactions: _infoData.observactions,

    created_at: _infoData.created_at,
    updated_at: _infoData.updated_at,
    usercreate: _infoData.usercreate,
    userupdate: _infoData.userupdate,
  }

  showDialog.value = true
}

// 🔹 Abrir modal en modo creación
const openCreateDialog = (tipo: string) => {
  editMode.value = false
  newRecord.value = {
    id: null,
    nit: '',
    branch: '01',
    lapse: '',
    report_date: (hoy),
    check_date: '',
    delivery_date: (hoy),
    consecutive: 0,
    document: '',
    supplier_name: '',
    value_cxp: 0,
    others_payments: 0,
    observations: '',
    payment_method: '',
    check_number: 0,
    payment_type: 'PagosFacturas', // Valor inicial por defecto dentro de las opciones válidas
    state: 'Activo', // Valor inicial por defecto dentro de las opciones válidas
    state01: '',
    state02: '',
    state03: '',
    proyect: '',
    sproyect: '',
    center: '',
    activity: '',
    companies_id: 1,
    customers_id: null,
    created_at: hoy,
    updated_at: hoy,
    usercreate: 'System',
    userupdate: 'System',
  }

  // console.log('🆕 Abriendo modal para nuevo clientes :', newRecord.value.type_document_identification_id, ' TypeIdent:', typedocument.value)
  showDialog.value = true

  tipoDeEgreso.value = tipo

  // 4. CONEXIÓN: Llamas al método expuesto por el hijo pasándole los datos
  if (crearEgresoDialog.value)
    crearEgresoDialog.value.open({ ...newRecord.value })
}

const openCreateDialogOther = (tipo: string) => {
  editMode.value = false
  newRecord.value = {
    id: null,
    nit: '',
    branch: '01',
    lapse: '',
    report_date: (hoy),
    check_date: '',
    delivery_date: (hoy),
    consecutive: 0,
    document: '',
    supplier_name: '',
    value_cxp: 0,
    others_payments: 0,
    observations: '',
    payment_method: '',
    check_number: 0,
    payment_type: 'OtrosPagos', // Valor inicial por defecto dentro de las opciones válidas
    state: 'Activo', // Valor inicial por defecto dentro de las opciones válidas
    state01: '',
    state02: '',
    state03: '',
    proyect: '',
    sproyect: '',
    center: '',
    activity: '',
    companies_id: 1,
    customers_id: null,
    created_at: hoy,
    updated_at: hoy,
    usercreate: 'System',
    userupdate: 'System',
  }

  // console.log('🆕 Abriendo modal para nuevo clientes :', newRecord.value.type_document_identification_id, ' TypeIdent:', typedocument.value)
  showDialog.value = true

  tipoDeEgreso.value = tipo

  // 4. CONEXIÓN: Llamas al método expuesto por el hijo pasándole los datos
  if (crearEgresoDialog.value)
    crearEgresoDialog.value.open({ ...newRecord.value })
}

const confirmSaveCharges = (id: number, Purchase_Invoice: string) => {
  console.log('🛑 Confirmar Cargue de Productos :', id, 'Purchase:', Purchase_Invoice)
  showSaveChargesDialog.value = true
}

const confirmPurchases = (id: number, Purchase_Invoice: string) => {
  const facturacompra = Purchase_Invoice ?? ''

  itemDetalle.value = []
  infofactura.value = `Factura :${facturacompra.trim}`
  console.log('🛑 Confirmar Ingreso de Compras :', id, 'Purchase:', Purchase_Invoice)
  showComprasDialog.value = true
  purchaseinvoice.value = Purchase_Invoice
  recordComprasT.value.id = id
}

const cargarproductosDialog = () => {
  console.log('🛑 Confirmar Cargue de Productos :')
  showCarguesDialog.value = true
}

// 🔹 Abrir confirmación de eliminación
const confirmDelete = (id: number) => {
  console.log('🛑 Confirmar eliminación del Cliente ID:', id)
  recordToDelete.value = id
  nameRecordToDelete.value = infoData.value.find(c => c.id === id)?.name || ''
  showConfirmDialog.value = true
}

const confirmDeleteCompras = (id: number) => {
  console.log('🛑 Confirmar eliminación del Temporal Cargues :', id)
  recordToDelete.value = id
  nameRecordToDelete.value = infoData.value.find(c => c.id === id)?.name || ''
  showConfirmDialogCompras.value = true
}

// 🔹 Eliminar empresa
const deleteRecordCargues = () => {
  if (recordToDelete.value === null)
    return

  // 1. Buscar el índice del producto dentro de itemDetalle usando el ID guardado
  const index = itemDetalle.value.findIndex(item => item.id === recordToDelete.value)

  if (index !== -1) {
    // 2. Eliminar el registro del array de forma reactiva
    itemDetalle.value.splice(index, 1)

    console.log('✅ Producto eliminado del detalle localmente')
  }

  // 3. Cerrar el diálogo y limpiar el ID temporal
  showConfirmDialogCompras.value = false
  recordToDelete.value = null
}

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

const handleSaveEgreso = async (egreso: CxPPayment) => {
  // console.log('Soy Egreso:', egreso)
  try {
    const response = await axios.post('/api/supplierpayment', {
      ...egreso, // Ahora sí, este 'egreso' es un objeto JavaScript 100% normal
      company_id: localStorage.getItem('company_id'),
      process_year: localStorage.getItem('process_year'),
    },
    {
      headers: { Authorization: `Bearer ${token}` },
    })

    console.log('Soy Response.date', response.data)

    const registroActualizado = { ...response.data.payments }

    if (egreso.id) {
      // MODO EDICIÓN
      responseData.value.payments = responseData.value.payments.map((item: any) =>
        item.id === registroActualizado.id ? registroActualizado : item,
      )
    }
    else {
      // MODO CREACIÓN
      responseData.value.payments = [registroActualizado, ...responseData.value.payments]

      // Actualizamos el contador total (verifica el nombre real de esta key también)
      responseData.value.totaldocumentos += 1
    }
  }
  catch (error) {
    console.error('Error al intentar guardar:', error)
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

const formatCurrency = (value: number | string, fractionDigits: number = 2) => {
  const num = Number(value) || 0

  return num.toLocaleString('en-US', {
    minimumFractionDigits: fractionDigits,
    maximumFractionDigits: fractionDigits,
  })
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
}

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

function onProductoSeleccionado(producto: Producto | null): void {
  productoInfo.value = producto || null
  if (producto) {
    newRecordCargue.value.code = producto.code
    newRecordCargue.value.name = producto.name

    // Asignación reactiva (cuando cargas el producto)
    costoActual.value = Number.parseFloat(producto.cost) || 0
  }
}

const headers = [
  { title: '#Id', key: 'id', width: 50 },
  { title: 'Fecha', key: 'report_date', sortable: true, width: 95 }, // Espacio justo para "AAAA-MM-DD"
  { title: 'Consecut.', key: 'consecutive', sortable: true, width: 70, align: 'end' },
  { title: 'Dcto', key: 'document', sortable: true, width: 70, align: 'start' },

  // A estas dos NO les pongas width para que absorban el espacio flexible y puedan hacer salto de línea
  { title: 'Descripción', key: 'document_name', sortable: true },
  { title: 'Origen de Pago', key: 'origin_name', sortable: true },
  {
    title: 'Nit/Cédula',
    key: 'nit',
    sortable: true,
    width: 110,
    cellProps: { class: 'd-none d-lg-table-cell' },
    headerProps: { class: 'd-none d-lg-table-cell' },
  },
  { title: 'Nombre del Cliente', key: 'supplier_name', sortable: true },
  { title: 'Tipo de Recibo', key: 'payment_type', sortable: true },

  // Columnas numéricas con un ancho fijo prudente
  { title: 'Valor Recibo', key: 'value_cxp', sortable: true, width: 80, aling: 'end' },
  { title: 'Estado', key: 'state', sortable: true, width: 90 },
  { title: 'Acciones', key: 'actions', sortable: false, width: 120, aling: 'center' }, // Espacio optimizado para tus 3 IconBtn compactos
]

function onEgresoGuardado({ esEdicion, registro }: { esEdicion: boolean; registro: any }) {
  if (esEdicion) {
    // MODO EDICIÓN
    responseData.value.payments = responseData.value.payments.map((item: any) =>
      item.id === registro.id ? registro : item,
    )
  }
  else {
    // MODO CREACIÓN
    responseData.value.payments = [registro, ...responseData.value.payments]
    responseData.value.totaldocumentos += 1
  }
}

const ShowDetailPaymentDialog = async (item: any, paymenttype: string) => {
  selectedDocument.value = item
  NombreDelCliente.value = item.name
  console.log('Id Company:', localStorage.getItem('company_id'))

  // showDetailsPayment.value = true

  const urlbase = (paymenttype === 'PagosFacturas') ? '/api/getpayments-detail' : '/api/getpayments-detail-othr'
  try {
    // onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
    const { data } = await axios.post(urlbase, {
      url_token: localStorage.getItem('auth_token'),
      company_id: localStorage.getItem('company_id'),
      document: selectedDocument.value,
      page: page.value,
      per_page: itemsPerPage.value,
    },
    {
      headers: { Authorization: `Bearer ${token}` },
    },
    )

    NombreDelCliente.value = `${item.supplier_name} - Egreso No:${item.consecutive} - (${paymenttype})`

    // invoiceDetData.value = data
    console.log('Respuesta DetDocument :', data)
    details.value = data.details

    // console.log('Soy Nombre del Cliente: ', NombreDelCliente.value)

    if (paymenttype === 'PagosFacturas') {
      showDetailsPayment.value = true
      showDetailsOtherPayment.value = false
      consultarEgresosDialog.value?.open()
    }
    else {
      detailsothr.value = data.details
      showDetailsPayment.value = false
      showDetailsOtherPayment.value = true
      consultarEgresosOtrosDialog.value?.open()
    }
  }
  catch (error) {
    console.error(error)
  }

  // finally {
  //   loading.value = false
  // }

  // editMode.value = true
  // showDialog.value = true
}
</script>

<template>
  <!-- <VCardText class="d-flex justify-space-between align-center flex-wrap gap-4 toolbar-header">  -->
  <VCard class="mb-1 mt-1 py-3 px-4 justify-space-berween">
    <VRow class="align-center">
      <VCol
        cols="12"
        md="9"
        class="d-flex align-left flex-column"
      >
        <h4 class="text-primary mb-2">
          Movimientos de RcCaja
          <span>
            : (<strong class="text-success">{{ process_year }}</strong>)
          </span>
        </h4>
        <VCardText class="d-flex align-center flex-wrap gap-4 pa-0">
          <VTextField
            v-model="searchQuery"
            placeholder="Buscar..."
            density="compact"
            prepend-inner-icon="tabler-search"
            variant="outlined"
            clearable
            hide-details
            style="inline-size: 20em; max-inline-size: 300px;"
          />
        </VCardText>
      </VCol>

      <VCol
        cols="12"
        md="3"
        class="d-flex justify-space-around  align-center gap-8 mt-4 mt-md-0"
      >
        <div class="d-flex flex-column align-center">
          <VTooltip
            text="Pagos de Facturas"
            location="top"
          >
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                color="white"
                width="40"
                height="40"
                icon
                class="mb-1"
                style="background-color: #3903fc !important; color: #fff;"
                @click="openCreateDialog('Pagos de Facturas')"
              >
                <VIcon
                  icon="tabler-plus"
                  size="30"
                />
              </VBtn>
            </template>
          </VTooltip>
          <span style="font-family: tahoma, verdana, sans-serif !important; font-size: 11px;">Pagos de Facturas</span>
        </div>

        <div
          class="d-flex flex-column align-center"
          style="margin-inline-start: 24px;"
        >
          <VTooltip
            text="Otros Pagos"
            location="top"
          >
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                color="white"
                width="40"
                height="40"
                icon
                class="mb-1"
                style="background-color: rgb(252, 3, 15) !important; color: #fff;"
                @click="openCreateDialogOther('Otros Pagos')"
              >
                <VIcon
                  icon="tabler-plus"
                  size="30"
                />
              </VBtn>
            </template>
          </VTooltip>
          <span style="font-family: tahoma, verdana, sans-serif !important; font-size: 11px;">Otros Pagos</span>
        </div>
      </VCol>
    </VRow>
  </VCard>
  <!-- <section v-if="companies && companies.length"></section> -->
  <section>
    <VCard id="grid-list">
      <VDivider />

      <VDataTable
        :key="tableKey"
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
        item-key="id"
        class="text-no-wrap text-body-2 grid-table"
        dense
        @update:options="updateOptions"
      >
        <template #item.id="{ item }">
          <div class="cell-wrap text-column">
            {{ item.id }}
          </div>
        </template>

        <template #item.report_date="{ item }">
          <div class="cell-wrap text-column">
            {{ item.report_date }}
          </div>
        </template>

        <template #item.consecutive="{ item }">
          <div class="cell-wrap text-column">
            {{ item.consecutive }}
          </div>
        </template>

        <template #item.document="{ item }">
          <div class="cell-wrap text-column">
            {{ item.document }}
          </div>
        </template>

        <template #item.document_name="{ item }">
          <div class="cell-wrap text-column">
            {{ item.document_name }}
          </div>
        </template>

        <template #item.origin_name="{ item }">
          <div class="cell-wrap text-column">
            {{ item.origin_name }}
          </div>
        </template>

        <template #item.supplier_name="{ item }">
          <div class="cell-wrap text-column">
            {{ item.supplier_name }}
          </div>
        </template>

        <template #item.nit="{ item }">
          <div class="cell-wrap text-column">
            {{ item.nit }}
          </div>
        </template>

        <template #item.payment_type="{ item }">
          <div class="cell-wrap text-column">
            {{ item.payment_type }}
          </div>
        </template>

        <template #item.value_cxp="{ item }">
          <div class="cell-wrap text-column text-right">
            {{ formatCurrency(item.value_cxp, 0) }}
          </div>
        </template>

        <template #item.state="{ item }">
          <div class="cell-wrap text-column">
            {{ item.state }}
          </div>
        </template>

        <template #item.actions="{ item }">
          <IconBtn
            density="compact"
            class="ma-0"
            @click="openEditDialog(item)"
          >
            <VIcon
              icon="tabler-edit"
              color="primary"
            />
          </IconBtn>

          <IconBtn
            density="compact"
            class="ma-0"
            :disabled="tipodeusuario === 'Operador'"
            @click="confirmDelete(item.id)"
          >
            <VIcon
              icon="tabler-trash"
              :color="tipodeusuario === 'Operador' ? 'grey' : 'error'"
            />
          </IconBtn>
          <IconBtn
            density="compact"
            class="ma-0"
            @click="item.payment_type === 'PagosFacturas'
              ? ShowDetailPaymentDialog(item, 'PagosFacturas')
              : ShowDetailPaymentDialog(item, 'OtrosPagos')"
          >
            <VIcon
              icon="tabler-list-check"
              :color="item.state !== 'Activo' ? 'error' : 'success'"
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
  </section>

  <CrearEgresosDialog
    ref="crearEgresoDialog"
    :customers="customers"
    :tipo-de-egreso="tipoDeEgreso"
    :sources="sources"
    :otherexpenses="otherexpenses"
    :docspayments="docspayments"
    :docspaymentsothers="docspaymentsothers"
    @save="handleSaveEgreso"
    @egreso-guardado="onEgresoGuardado"
  />

  <ConsultarEgresosFacturasDialog
    ref="consultarEgresosDialog"
    v-model:dialogdetpayment="showDetailsPayment"
    :details="details"
    :titledetails="NombreDelCliente"
  />

  <ConsultarEgresosOtrosPagosDialog
    ref="consultarEgresosOtrosDialog"
    v-model:dialogotherpayment="showDetailsOtherPayment"
    :detailsothr="detailsothr"
    :titledetails="NombreDelCliente"
  />

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
        ¿Está seguro que desea eliminar esta Compra ?<br>
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

  <!-- ❗ Diálogo de confirmación de eliminación -->
  <VDialog
    v-model="showConfirmDialogCompras"
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
        ¿Está seguro que desea eliminar este Registro ?<br>
        <strong>Esta acción no se puede deshacer.</strong>
      </VCardText>
      <VCardActions class="justify-center pb-4">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="showConfirmDialogCompras = false"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="error"
          variant="flat"
          @click="deleteRecordCargues"
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
  white-space: normal; /* permite salto de línea */
  word-wrap: break-word;    /* divide palabras largas */
}

.columna_name2 {
  display: block;
  font-size: 0.78em;
  line-height: 1.3;         /* mejora legibilidad */
  overflow-wrap: break-word;

  // max-width: 600px;         /* ancho fijo */
  white-space: normal; /* permite salto de línea */
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

.aligned-field input {
  text-align: end !important;
}

/* Aplica color gris a las filas pares */
.products-gridc tbody tr:nth-child(even) {
  background-color: #f5f5f5 !important; /* Un gris muy claro */
}

/* Opcional: Cambiar el color al pasar el mouse (hover) */
.products-gridc tbody tr:hover {
  background-color: #eee !important;
}

.text-column {
   font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif, sans-serif;
   font-size: 0.85em;
   line-height: 1 !important;
   margin-block-start: 1 !important;
 }

@media (max-width: 1400px) {
  :deep(.v-data-table) {
    font-size: 0.85em !important;
  }
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

/* Forzar el fondo negro en el encabezado de esta tabla específica */
</style>
