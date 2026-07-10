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
const isDialogActive = ref(false)
const infofactura = ref('')

isDialogActive.value = false

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

// 🔹 Opciones del datatable

interface ComprasT {
  id: number
  subtotal: number
  vatvalue: number
  retefuente: number
  reteiva: number
  discount: number
  total: number
}

interface Producto {
  id: number
  products_id: number
  companies_id: number
  year: string
  code: string
  store: string
  batch: string
  group_name: string
  name: string
  quantity: string
  quantity1: string
  price: string
  cost: string
  lastcost: string
  measure_name: string
  previous_balance: number | null
  subtotal: string
}

const _subtotal = ref(0)
const _descuentos = ref(0)
const _valoriva = ref(0)
const _valorretenciones = ref(0)
const _total = ref(0)

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

const recordComprasT = ref<ComprasT>(
  {
    id: 0,
    subtotal: 0,
    vatvalue: 0,
    retefuente: 0,
    reteiva: 0,
    discount: 0,
    total: 0,
  })

const newRecordCargue = ref<Cargue>({
  id: 0,
  code: '',
  name: '',
  store: '1',
  quantity: 0,
  vat: 0,
  discount: 0,
  cost: 0,
  valueprevious: 0,
  valuediscount: 0,
  subtotal: 0,
  total: 0,
})

export interface recDetalle {
  code: string
  name: string
  stoe: string
  quantity: number
  vat: number
  discount: number
  cost: number
  valueprevious: number
  valuediscount: number
  subtotal: number
  total: number
}

export interface Cargue {
  id: number
  code: string
  name: string
  store: string
  quantity: number
  vat: number
  discount: number
  cost: number
  valueprevious: number
  valuediscount: number
  subtotal: number
  total: number
}

const itemDetalle = ref<Cargue []>([])

// Propiedad computada que recorre el array y calcula los totales
const totalesFactura = computed(() => {
  let subtotalGeneral = 0
  let descuentoGeneral = 0
  let ivaGeneral = 0
  let totalGeneral = 0

  itemDetalle.value.forEach(item => {
    // 1. Subtotal de la línea: Cantidad * Costo
    const subtotalLinea = item.quantity * item.cost

    // 2. Valor del Descuento: (Cantidad * Costo) * (Porcentaje / 100)
    // Nota: Corregí tu fórmula matemática original para que calcule el valor real a restar.
    const descuentoLinea = subtotalLinea * (item.discount / 100)

    // Base imponible para el IVA (Subtotal menos el descuento aplicado)
    const baseImponible = subtotalLinea - descuentoLinea

    // 3. Valor del IVA: Base Imponible * (Porcentaje IVA / 100)
    const ivaLinea = baseImponible * (item.vat / 100)

    // 4. Total de la línea
    const totalLinea = baseImponible + ivaLinea

    // Acumular los valores en los totales generales
    subtotalGeneral += subtotalLinea
    descuentoGeneral += descuentoLinea
    ivaGeneral += ivaLinea
    totalGeneral += totalLinea
  })

  // Retornamos un objeto con todos los totales calculados
  _subtotal.value = subtotalGeneral
  _valoriva.value = ivaGeneral
  _descuentos.value = descuentoGeneral
  _total.value = totalGeneral

  // recordComprasT.value.id = 1
  recordComprasT.value.subtotal = subtotalGeneral
  recordComprasT.value.retefuente = 0
  recordComprasT.value.reteiva = 0
  recordComprasT.value.reteica = 0
  recordComprasT.value.discount = descuentoGeneral
  recordComprasT.value.vatvalue = ivaGeneral
  recordComprasT.value.toal = totalGeneral

  return {
    subtotal: Math.round(subtotalGeneral),
    descuento: Math.round(descuentoGeneral),
    iva: Math.round(ivaGeneral),
    total: Math.round(totalGeneral),
  }
})

// 🔹 Encabezados de la tabla
export interface Document {
  id?: number
  nit?: string
  branch?: string
  name?: string
  number?: number
  concept_inv?: string
  concept_class?: string
  report_date?: string | Date
  purchase_invoice?: number
  prefix?: string
  documento_purchase?: string
  order_number?: string
  date_from?: string | Date
  date_to?: string | Date
  subtotal?: number
  vatvalue?: number
  reteiva?: number
  reteica?: number
  products_discount?: number
  additional_discounts?: number
  additional_value?: number
  freight?: number
  total_purchases?: number
  plate?: string
  type?: 'Compras' | 'Otras Entradas' | 'Otras Salidas' | 'Traslados' | 'Devolución' | 'Otras'
  type_of_purchase?: 'Contado' | 'Crédito'
  state?: 'Activo' | 'Eliminado' | 'Pendiente'
  state01?: string
  state02?: string
  state03?: string
  companies_id?: number
  proyect?: string
  sproyect?: string
  center?: string
  activity?: string
  observations: string
  created_at?: string | Date
  updated_at?: string | Date
  usercreate?: string
  userupdate?: string
}

// --- 🔹 Modal y formulario de creación ---
const showDialog = ref(false)
const editMode = ref(false) // 👈 false = crear, true = editar
const showComprasDialog = ref(false) // Formulario de Compras
const showCarguesDialog = ref(false)
const showSaveChargesDialog = ref(false)
const showCarguesEsDialog = ref(false)
const showCarguesDpDialog = ref(false)

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
  docs_purchases: [],
  docs_inputs_outputs: [],
  suppliers: [],
  cptpurchases: [],
  cptes: [],
  dctoscxp: [],
  products: [],
  totaldocument: 0,
  page: 1,
  per_page: 10,
  totaldctos: 0,
})

//  'data'              => $documents,
//             'docs_purchases'    => $compras,
//             'docs_inputs_outputs' => $entradas_salidas,
//             'suppliers'         => $proveedores,
//             'cpt_purchases'     => $cptos_compras,
//             'cpt_es'            => $cptos_es,
// 🔹 Diálogo de confirmación de eliminación
const documents = ref([])
const docs_purchases = ref([])
const docs_inputs_outputs = ref([])
const suppliers = ref([])
const cptpurchases = ref<Concept[]>([])
const cptes = ref([])
const dctoscxp = ref([])
const products = ref([])

const nameRecordToDelete = ref('')
const showConfirmDialog = ref(false)
const recordToDelete = ref<number | null>(null)

////////////////////////////////////////////////////////////////////////////////////////////////////////////
function agregarProducto(): void {
  if (!productoSeleccionado.value || newRecordCargue.value.quantity <= 0)
    return

  itemDetalle.value.push({
    id: newRecordCargue.value.id,
    code: newRecordCargue.value.code,
    name: newRecordCargue.value.name,
    store: '1',
    quantity: newRecordCargue.value.quantity,
    vat: newRecordCargue.value.vat,
    discount: newRecordCargue.value.discount,
    cost: newRecordCargue.value.cost,
    valueprevious: 0,
    valuediscount: 0,
    subtotal: 0,
    total: 0,
  })

  // Descontamos el saldo de inventario en el arreglo local (simulación)
  // const producto = products.value.find(p => p.id === productoSeleccionado.value!.id)
  // if (producto)
  //   producto.quantity = String(stockDisponible - cantidadAgregar.value)

  // costoDeVenta.value = costoDeVenta.value + cantidadAgregar.value * producto.cost
  // productoSeleccionado.value = null
  // cantidadAgregar.value = 1
  // descuentoItemAgregar.value = 0
  // precioUnitarioManual.value = 0
  autocompleteProductoKey.value++

  console.log('Soy Detalle de Items: ', itemDetalle.value)
  showCarguesDialog.value = false
  Limpiar_RegCargue()
}

function Limpiar_RegCargue(): void {
  newRecordCargue.value.id = 0
  newRecordCargue.value.code = ''
  newRecordCargue.value.name = ''
  newRecordCargue.value.store = '1'
  newRecordCargue.value.quantity = 0
  newRecordCargue.value.discount = 0
  newRecordCargue.value.valueprevious = 0
  newRecordCargue.value.valuediscount = 0
  newRecordCargue.value.cost = 0

  productoSeleccionado.value.code = null
  productoSeleccionado.value.name = null
}

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
    const response = await axios.get('/api/getdocuments', {
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

    documents.value = responseData.value.data

    // eslint-disable-next-line camelcase
    docs_purchases.value = responseData.value.docspurchases
    // eslint-disable-next-line camelcase
    docs_inputs_outputs.value = responseData.value.docsinputsoutputs
    suppliers.value = responseData.value.suppliers

    cptpurchases.value = responseData.value.cptpurchases
    products.value = responseData.value.products

    cptes.value = responseData.value.cptes
    dctoscxp.value = responseData.value.dctoscxp
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

const proveedorSeleccionado = ref<Supplier | null>(null)
const proveedorInfo = ref<Supplier | null>(null)

const conceptoSeleccionado = ref<Concept | null>(null)
const conceptoInfo = ref<Concept | null>(null)

const dctoscxpSeleccionado = ref<Dctoscxp | null>(null)
const dctoscxpInfo = ref<Dctoscxp | null>(null)

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

  // console.log('Soy Data 001 - 999 : ', JSON.parse(JSON.stringify(data)))

  return data.map(p => ({
    ...p,
    regimen: p.regimen,
    typedocument: p.typedocument,

  }))
})

const totalRecords = computed(() => responseData.value?.totaldocument ?? 0)
const perPage = computed(() => responseData.value.per_page ?? itemsPerPage.value)
const currentPage = computed(() => responseData.value.page ?? page.value)

// console.log('Información Regimen:', responseData.value.regimen)

const saveRecordCompras = async () => {
  newRecordCargue.value.id = 0

  const payload = {
    company_id: localStorage.getItem('company_id') || '',
    compras: recordComprasT.value,
    items: itemDetalle.value,
  }

  console.log('Información Estructura:', newRecordCargue.value)
  console.log('--- FormData enviado ---')

  try {
    const response = await axios.post('/api/purchases-details', {
      q: searchQuery.value,
      payload,
    },
    {
    // El tercer argumento es la configuración (Headers, etc.)
      headers: { Authorization: `Bearer ${token}` },
    })

    // responseData.value = response.data

    snackbarMessage.value = newRecordCargue.value.id
      ? 'Detalle de Compras Actualizada Correctamente'
      : 'Detalle de Compras Creada Correctamente'
    snackbarColor.value = 'success'

    certificateFileModel.value = null
    showDialog.value = false
  }
  catch (error: any) {
    console.error('Error:', error.response?.data)
    snackbarMessage.value = 'Error al guardar la Compra'
    snackbarColor.value = 'error'
  }
  finally {
    showSnackbar.value = true
  }
}

const saveRecord = async () => {
  const formData = new FormData()

  formData.append('company_id', localStorage.getItem('company_id') || '')

  Object.entries(newRecord.value).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '')
      formData.append(key, value as string)
  })

  // 🔍 DEBUG: confirmar qué se está enviando realmente

  console.log('Información Estructura:', newRecord.value)
  console.log('--- FormData enviado ---')
  for (const [key, value] of formData.entries())
    console.log(key, ':', value)

  try {
    const url = newRecord.value.id
      ? `/api/purchases/${newRecord.value.id}`
      : '/api/purchases'

    const { data } = await axios.post(url, formData)

    // 🔍 1. Confirmar qué llega del backend

    // 🔎 Buscamos los nombres correspondientes a los códigos seleccionados
    // const ciudadSeleccionada = municipalities.value.find(
    //    g => String(g.id) === String(newRecord.value.municipalities_id),
    //  )

    // console.log('3. Ciudad Seleccionada:', ciudadSeleccionada)

    // 🧩 Mergeamos el producto crudo del backend con los nombres frescos
    const registroActualizado = {
      ...data.purchase,

      // city_name: ciudadSeleccionada?.name ?? null,
    }

    if (newRecord.value.id) {
      const index = responseData.value.data.findIndex(
        (c: any) => c.id === newRecord.value.id,
      )

      if (index !== -1) {
        responseData.value.data = [
          ...responseData.value.data.slice(0, index),
          { ...registroActualizado }, // ✅ sin .value
          ...responseData.value.data.slice(index + 1),
        ]
      }
    }
    else {
      responseData.value.data = [...responseData.value.data, { ...registroActualizado }] // ✅ sin .value
    }

    snackbarMessage.value = newRecord.value.id
      ? 'Compra Actualizada Correctamente'
      : 'Compra Creada Correctamente'
    snackbarColor.value = 'success'

    certificateFileModel.value = null
    showDialog.value = false
  }
  catch (error: any) {
    console.error('Error:', error.response?.data)
    snackbarMessage.value = 'Error al guardar la Compra'
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
const openCreateDialog = () => {
  editMode.value = false
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
    companies_id: 0,
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

  // console.log('🆕 Abriendo modal para nuevo clientes :', newRecord.value.type_document_identification_id, ' TypeIdent:', typedocument.value)
  showDialog.value = true
}

const confirmSaveCharges = (id: number, Purchase_Invoice: string) => {
  console.log('🛑 Confirmar Cargue de Productos :', id, 'Purchase:', Purchase_Invoice)
  showSaveChargesDialog.value = true
}

const confirmPurchases = (id: number, Purchase_Invoice: string) => {
  const facturacompra = Purchase_Invoice ?? ''

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
  console.log('🛑 Confirmar eliminación del Proveedor ID:', id)
  recordToDelete.value = id
  nameRecordToDelete.value = infoData.value.find(c => c.id === id)?.name || ''
  showConfirmDialog.value = true
}

// 🔹 Eliminar empresa
const deleteRecord = async () => {
  if (!recordToDelete.value)
    return

  try {
    await $api(`/api/suppliers/${recordToDelete.value}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    })
    loadInfo()
    snackbarMessage.value = '✅ Proveedor eliminado correctamente'
    snackbarColor.value = 'success'
  }
  catch (error) {
    console.error('❌ Error al eliminar el Proveedor:', error)
    snackbarMessage.value = '❌ Error al eliminar el Proveedor'
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
}// Instancias la función para cada campo de tu formulario
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

function onProductoSeleccionado(producto: Producto | null): void {
  productoInfo.value = producto || null
  if (producto) {
    newRecordCargue.value.code = producto.code
    newRecordCargue.value.name = producto.name
  }
}
function onProveedorSeleccionado(proveedor: Supplier | null): void {
  proveedorInfo.value = proveedor || null
  if (proveedor) {
    newRecord.value.nit = proveedor.nit
    newRecord.value.branch = proveedor.branch ?? ''
    newRecord.value.dv = proveedor.dv ?? ''
    newRecord.value.name = proveedor.name
  }
}

function onConceptoSeleccionado(concepto: Concept | null): void {
  conceptoInfo.value = concepto || null
  if (concepto)
    newRecord.value.concept_inv = concepto.code
}

function onDctoscxpSeleccionado(_dctoscxp: Dctoscxp | null): void {
  dctoscxpInfo.value = _dctoscxp || null
  if (_dctoscxp)
    newRecord.value.documento_purchase = _dctoscxp.code
}

function Totalizar_Compra(): void {
  _SubTotal.value = 0
  _valoriva.value = 0
  _valorretenciones.value = 0
  _descuentos.value = 0
  _total.value = 0
}

// Mapeo de price_list_id (numérico, viene del controller) a la clave interna de lista de precios
// const mapaListas: Record<number, ClavePrecio> = { 1: 'minorista', 2: 'mayorista', 3: 'especial' }

// listaPrecioSeleccionada.value = mapaListas[cliente.price_list_id] ?? 'minorista'

// Si el documento es a crédito, usamos el plazo (deadline_days) propio del cliente
// if (docTipo.value === 'credito') {
//   const fecha = new Date(fechaFactura.value)

//   fecha.setDate(fecha.getDate() + (cliente.deadline_days || 30))
//   fechaVencimiento.value = fecha.toISOString().substring(0, 10)
// }
const productHeaders = [
  { title: 'ID', key: 'id', width: 60, sortable: false, align: 'start' },
  { title: 'Código', key: 'code', width: 100, sortable: false },
  { title: 'Descripción del Producto', key: 'name', sortable: false, width: '35%' },
  { title: 'Bd', key: 'store', width: 90, sortable: false },
  { title: 'Cant.', key: 'quantity', width: 80, align: 'end', sortable: false },
  { title: 'Desc (%)', key: 'discount', width: 100, align: 'end', sortable: false },
  { title: 'IVA', key: 'vat', width: 80, align: 'end', sortable: false },
  { title: 'Costo Unit.', key: 'cost', width: 120, align: 'end', sortable: false },
  { title: 'ValParcial', key: 'subtotal', width: 130, align: 'end', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, width: 130, aling: 'center' }, // Espacio optimizado para tus 3 IconBtn compactos
]

const headers = [
  { title: '#', key: 'id', width: 50 },
  { title: 'Fecha', key: 'report_date', sortable: true, width: 95 }, // Espacio justo para "AAAA-MM-DD"
  { title: 'Consecut.', key: 'number', sortable: true, width: 70, align: 'end' },
  { title: '#Factura', key: 'purchase_invoice', sortable: true, width: 70, align: 'end' },

  // A estas dos NO les pongas width para que absorban el espacio flexible y puedan hacer salto de línea
  { title: 'Descripción', key: 'concept_name', sortable: true },
  {
    title: 'Nit/Cédula',
    key: 'nit',
    sortable: true,
    width: 110,
    cellProps: { class: 'd-none d-lg-table-cell' },
    headerProps: { class: 'd-none d-lg-table-cell' },
  },
  { title: 'Nombre del Tercero', key: 'name', sortable: true },

  // Columnas numéricas con un ancho fijo prudente
  { title: 'SubTotal', key: 'subtotal', sortable: true, width: 80, aling: 'end' },
  { title: 'ValorIva', key: 'vatvalue', sortable: true, width: 70, aling: 'end' },
  { title: 'Desctos', key: 'descuentos', sortable: true, width: 70, aling: 'end' },
  { title: 'Retenc.', key: 'retenciones', sortable: true, width: 70, aling: 'end' },
  { title: 'Total', key: 'total_purchases', sortable: true, width: 110, aling: 'end' },

  { title: 'Estado', key: 'state', sortable: true, width: 90 },
  { title: 'Acciones', key: 'actions', sortable: false, width: 120, aling: 'center' }, // Espacio optimizado para tus 3 IconBtn compactos
]
</script>

<template>
  <!-- <VCardText class="d-flex justify-space-between align-center flex-wrap gap-4 toolbar-header">  -->
  <VCard class="mb-1 mt-1 py-3 px-4 justify-space-berween">
    <VRow class="align-center">
      <VCol
        cols="12"
        md="3"
        class="d-flex align-left flex-column"
      >
        <h4 class="text-primary mb-2">
          Movimientos de Inventarios
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
            style="inline-size: 20em;max-inline-size: 300px;"
          />
        </VCardText>
      </VCol>

      <VCol
        cols="12"
        md="3"
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
          width="200"
          @click="openCreateDialog"
        >
          <template #prepend>
            <VIcon
              icon="tabler-plus"
              size="20"
            />
          </template>
          Compras
        </VBtn>
      </VCol>
      <VCol
        cols="12"
        md="2"
        class="d-flex align-right justify-start mt-md-5 mt-2"
      >
        <VBtn
          rounded="pill"
          color="success"
          width="200"
          @click="openCreateDialog"
        >
          <template #prepend>
            <VIcon
              icon="tabler-plus"
              size="20"
            />
          </template>
          Entradas / Salidas
        </VBtn>
      </VCol>
      <VCol
        cols="12"
        md="2"
        class="align-right justify-start mt-md-5 mt-2"
      >
        <VBtn
          rounded="pill"
          color="error"
          width="200"
          @click="openCreateDialog"
        >
          <template #prepend>
            <VIcon
              icon="tabler-plus"
              size="20"
            />
          </template>
          Dev Proveedores
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
        dense
        @update:options="updateOptions"
      >
        <template #item.id="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.id }}
          </div>
        </template>

        <template #item.report_date="{ item }">
          <div class="cell-wrap text-no-wrap columna_name">
            {{ item.report_date }}
          </div>
        </template>

        <template #item.number="{ item }">
          <div class="cell-wrap text-no-wrap columna_name">
            {{ item.number }}
          </div>
        </template>

        <template #item.concept_name="{ item }">
          <div class="cell-wrap text-no-wrap columna_name">
            {{ item.concept_name }}
          </div>
        </template>

        <template #item.purchase_invoice="{ item }">
          <div class="cell-wrap text-no-wrap columna_name">
            {{ item.purchase_invoice }}
          </div>
        </template>

        <template #item.nit="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.nit }}
          </div>
        </template>

        <template #item.name="{ item }">
          <div class="cell-wrap columna_name2 text-no-wrap">
            {{ item.name }}
          </div>
        </template>

        <template #item.subtotal="{ item }">
          <div class="cell-wrap columna_name text-right">
            {{ item.subtotal }}
          </div>
        </template>

        <template #item.vatvalue="{ item }">
          <div class="cell-wrap columna_name text-right">
            {{ item.vatvalue }}
          </div>
        </template>

        <template #item.descuentos="{ item }">
          <div class="cell-wrap columna_name text-right">
            {{ item.descuentos }}
          </div>
        </template>

        <template #item.retenciones="{ item }">
          <div class="cell-wrap columna_name text-right">
            {{ item.retenciones }}
          </div>
        </template>

        <template #item.total_purchases="{ item }">
          <div class="cell-wrap columna_name text-right">
            {{ item.total_purchases }}
          </div>
        </template>

        <template #item.state="{ item }">
          <div class="cell-wrap columna_name">
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
            @click="confirmPurchases(item.id, item.purchase_invoice)"
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

  <VDialog
    v-model="showDialog"
    persistent
    max-width="1100px"
    attach="#app"
    :retain-focus="false"
  >
    <VCard>
      <!-- <VCardTitle class="text-h5 bg-primary text-white py-4 px-4">Agregar nueva empresa</VCardTitle> -->
      <VCardTitle class="modal-title d-flex align-center text-h6">
        <VIcon
          icon="tabler-businessplan"
          size="28"
          color="white"
          class="me-3"
        />
        {{ newRecord.id ? 'Actualizando una Compra' : 'Agregando una Compra' }}
        <span
          class="text-h6 font-weight-bold ml-2"
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
                md="6"
                class="mt-4"
              >
                <VAutocomplete
                  v-model="proveedorSeleccionado"
                  :items="suppliers"
                  item-title="name"
                  item-value="id"
                  label="Nombre del Proveedor"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  return-object
                  class="custom-autocomplete"
                  @update:model-value="onProveedorSeleccionado"
                />
              </VCol>
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
            </VRow>

            <VDivider
              color="dark"
              thickness="2"
            />
            <VRow
              dense
              align="center"
              class="g-2 mt-0"
            >
              <VCol
                cols="12"
                md="6"
                class="mt-3"
              >
                <VAutocomplete
                  v-model="conceptoSeleccionado"
                  :items="cptpurchases"
                  item-title="name"
                  label="Descripción del Concepto"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  return-object
                  class="custom-autocomplete"
                  @update:model-value="onConceptoSeleccionado"
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppSelect
                  v-model="newRecord.type_of_purchase"
                  :items="['Contado', 'Crédito']"
                  label="Tipo de Compra"
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
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.order_number"
                  label="Número de Orden"

                  class="mb-2 text_size aligned-field"
                  placeholder="Número de Orden"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppDateTimePicker
                  v-model="newRecord.report_date"
                  label="Fecha de Reporte :"
                  placeholder="Seleccionar Fecha"
                  class="text-center-input mb-2"
                  variant="outlined"
                  prepend-inner-icon="tabler-calendar"
                  :config="{ locale: Spanish, static: false, dateFormat: 'Y-m-d' }"
                />
              </VCol>
            </VRow>
            <VDivider
              color="dark"
              thickness="2"
            />

            <VRow
              dense
              align="center"
              class="g-2 mt-0"
            >
              <VCol
                cols="12"
                md="4"
                class="mt-3"
              >
                <VAutocomplete
                  v-model="dctoscxpSeleccionado"
                  :items="dctoscxp"
                  item-title="name"
                  label="Descripción del Tipo de Documento"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  return-object
                  class="custom-autocomplete"
                  @update:model-value="onDctoscxpSeleccionado"
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecord.purchase_invoice"
                  label="Número de Factura"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Número de Facura"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  type="number"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-file-text"
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
                  v-model="newRecord.prefix"
                  label="Prefijo"
                  class="mb-2 text_size aligned-field"
                  placeholder="Prefijo"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => newRecord.prefix = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-chart-bar-popular"
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
                <AppDateTimePicker
                  v-model="newRecord.date_from"
                  label="Fecha de Factura :"
                  placeholder="Seleccionar Fecha"
                  class="text-center-input mb-2"
                  variant="outlined"
                  prepend-inner-icon="tabler-calendar"
                  :config="{ locale: Spanish, static: false, dateFormat: 'Y-m-d' }"
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppDateTimePicker
                  v-model="newRecord.date_to"
                  label="Fecha de Vencimiento :"
                  placeholder="Seleccionar Fecha"
                  class="text-center-input mb-2"
                  variant="outlined"
                  prepend-inner-icon="tabler-calendar"
                  :config="{ locale: Spanish, static: false, dateFormat: 'Y-m-d' }"
                />
              </VCol>
            </VRow>
            <VDivider
              color="grey"
              thickness="2"
            />
            <VRow
              dense
              align="center"
              class="g-2 mt-0"
            >
              <VCol
                cols="12"
                md="12"
                class="py-0"
              >
                <AppTextarea
                  v-model="newRecord.observations"
                  label="Observaciones"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Ingrese Observaciones"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  auto-grow
                  rows="3"
                  @update:model-value="val => newRecord.address = val ? val.toUpperCase() : ''"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-writing"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </apptextarea>
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

  <VDialog
    v-model="showComprasDialog"
    persistent
    max-width="1200px"
    attach="#app"
    :retain-focus="false"
  >
    <VCard class="mt-1">
      <VCardTitle class="modal-title mt-1 d-flex align-center text-h6 bg-primary text-white py-3 px-4">
        <VIcon
          icon="tabler-settings-plus"
          size="28"
          color="white"
          class="me-3"
        />
        {{ newRecord.id ? 'Actualizando Registros de la Factura de Compra' : 'Ingresando Productos de la Factura de Compra' }}
        <span
          class="text-h6 font-weight-bold ml-2"
          style="color: #f7fb2d !important;"
        >
          Factura : {{ purchaseinvoice }}
        </span>
      </VCardTitle>

      <VCardText class="pa-0">
        <VDefaultsProvider
          :defaults="{
            AppTextField: {
              density: 'comfortable',
              variant: 'outlined',
              hideDetails: true,
              class: 'mb-3 text_size aligned-field',
            },
            AppSelect: {
              density: 'comfortable',
              variant: 'outlined',
              hideDetails: true,
              class: 'mb-3 text_size aligned-field',
            },
          }"
        >
          <VForm>
            <VRow
              dense
              align="center"
              class="g-2"
            >
              <VCol
                cols="12"
                class="my-2"
              >
                <!--
                  <VDivider
                  color="dark"
                  thickness="2"
                  />
                  </VCol>
                -->

                <VRow
                  dense
                  align="center"
                  class="ma-0 px-2 pt-2"
                >
                  <VCol
                    cols="12"
                    class="pa-0"
                  >
                    <VDataTable
                      :key="itemDetalle.length"
                      v-model:items-per-page="itemsPerPage"
                      :headers="productHeaders"
                      :items="itemDetalle"
                      item-value="id"
                      density="compact"
                      :items-per-page="-1"
                      hide-default-footer
                      class="products-gridc border rounded  mx-1 my-1"
                      height="370"
                      dense
                      striped="even"
                      fixed-header
                    >
                      <template #item.id="{ item }">
                        <div class="cell-wrap columna_name2">
                          {{ item.id }}
                        </div>
                      </template>

                      <template #item.code="{ item }">
                        <div class="cell-wrap columna_name2">
                          {{ item.code }}
                        </div>
                      </template>

                      <template #item.name="{ item }">
                        <div class="cell-wrap columna_name2">
                          {{ item.name }}
                        </div>
                      </template>

                      <template #item.store="{ item }">
                        <div class="cell-wrap columna_name2">
                          {{ item.store }}
                        </div>
                      </template>

                      <template #item.quantity="{ item }">
                        <div class="cell-wrap columna_name2">
                          {{ formatCurrency(item.quantity) }}
                        </div>
                      </template>

                      <template #item.discount="{ item }">
                        <div class="cell-wrap columna_name2">
                          {{ formatCurrency(item.discount, 0) }}
                        </div>
                      </template>

                      <template #item.vat="{ item }">
                        <div class="cell-wrap columna_name2">
                          {{ formatCurrency(item.vat, 0) }}
                        </div>
                      </template>

                      <template #item.cost="{ item }">
                        <div class="cell-wrap columna_name2">
                          {{ formatoMoneda(item.cost) }}
                        </div>
                      </template>

                      <template #item.subtotal="{ item }">
                        <span class="cell-wrap columna_name2">
                          {{ formatoMoneda(item.quantity * item.cost) }}
                        </span>
                      </template>

                      <template #item.actions="{ item }">
                        <IconBtn
                          density="compact"
                          @click="openEditDialog(item)"
                        >
                          <VIcon
                            icon="tabler-edit"
                            color="primary"
                          />
                        </IconBtn>

                        <IconBtn
                          density="compact"
                          @click="confirmDelete(item.id)"
                        >
                          <VIcon
                            icon="tabler-trash"
                            color="error"
                          />
                        </IconBtn>
                      </template>
                    </VDataTable>
                  </VCol>
                </VRow>
              </vcol>
            </VRow>
          </VForm>
        </VDefaultsProvider>
      </VCardText>
      <VRow class="justify-space-between">
        <Vcol
          cols="12"
          md="4"
        >
          <VCardActions class="mt-1 px-2 mb-3 mx-4">
            <div class="text-subtitle-2 font-weight-bold mb-2 text-secondary">
              <VTooltip
                text="Procesar Descuento"
                location="top"
              >
                <template #activator="{ props }">
                  <VBtn
                    v-bind="props"
                    color="white"
                    width="37"
                    height="37"
                    icon
                    class="mx-3"
                    style="background-color: #d41608 !important;"
                    @click="cargarproductosDialog"
                  >
                    <VIcon
                      icon="tabler-discount"
                      size="24"
                    />
                  </VBtn>
                </template>
              </VTooltip>
              <VTooltip
                text="Procesar Iva"
                location="top"
              >
                <template #activator="{ props }">
                  <VBtn
                    v-bind="props"
                    color="white"
                    width="37"
                    height="37"
                    icon
                    class="bg-error mx-3"
                    @click="cargarproductosDialog"
                  >
                    <VIcon
                      icon="tabler-receipt-tax"
                      size="24"
                    />
                  </VBtn>
                </template>
              </VTooltip>
              <VTooltip
                text="Guardar Compras"
                location="top"
              >
                <template #activator="{ props }">
                  <VBtn
                    v-bind="props"
                    color="white"
                    width="37"
                    height="37"
                    icon
                    class="bg-primary mx-3"
                    @click="confirmSaveCharges"
                  >
                    <VIcon
                      icon="tabler-database-edit"
                      size="24"
                    />
                  </VBtn>
                </template>
              </VTooltip>
              <VTooltip
                text="Ingresar Productos"
                location="top"
              >
                <template #activator="{ props }">
                  <VBtn
                    v-bind="props"
                    color="white"
                    width="37"
                    height="37"
                    icon
                    class="bg-success mx-3"
                    @click="cargarproductosDialog"
                  >
                    <VIcon
                      icon="tabler-plus"
                      size="24"
                    />
                  </VBtn>
                </template>
              </VTooltip>
              <VTooltip
                text="Salir de la Ventana"
                location="top"
              >
                <template #activator="{ props }">
                  <VBtn
                    v-bind="props"
                    color="white"
                    width="37"
                    height="37"
                    icon
                    class="mx-3"
                    style="background-color: #d41608 !important;"
                    @click="showComprasDialog = false"
                  >
                    <VIcon
                      icon="tabler-logout"
                      size="24"
                    />
                  </VBtn>
                </template>
              </VTooltip>
            </div>
          </VCardActions>
        </Vcol>

        <Vcol
          cols="12"
          md="8"
        >
          <VRow
            dense
            class="mx-10 my-3 justify-start mt-1"
          >
            <VCol
              cols="auto"
              class="me-7"
            >
              <h4 class="text-caption text-medium-emphasis align-center text-primary font-weight-bold mb-0 mt-0">
                SubTotal
              </h4>
              <span class="text-subtitle-2 mt-1">
                {{ formatCurrency(totalesFactura.subtotal, 0) }}
              </span>
            </VCol>

            <VCol
              cols="auto"
              class="me-7"
            >
              <h4 class="text-caption text-medium-emphasis align-center text-primary font-weight-bold">
                Descuentos
              </h4>
              <span class="text-subtitle-2">
                {{ formatCurrency(totalesFactura.descuento, 0) }}
              </span>
            </VCol>

            <VCol
              cols="auto"
              class="me-7"
            >
              <h4 class="text-caption text-medium-emphasis align-center text-primary font-weight-bold">
                Valor Iva
              </h4>
              <span class="text-subtitle-2">
                {{ formatCurrency(totalesFactura.iva, 0) }}
              </span>
            </VCol>

            <VCol
              cols="auto"
              class="me-7"
            >
              <h4 class="text-caption text-medium-emphasis align-center text-primary font-weight-bold">
                Retenciones
              </h4>
              <span class="text-subtitle-2">
                {{ 0 }}
              </span>
            </VCol>

            <VCol
              cols="auto"
              class="me-7"
            >
              <h4 class="text-caption text-medium-emphasis align-center text-primary font-weight-bold">
                Total
              </h4>
              <span class="text-subtitle-1 font-weight-bold align-right">
                {{ formatCurrency(totalesFactura.total, 0) }}
              </span>
            </VCol>
          </VRow>
        </Vcol>
      </Vrow>
    </VCard>
  </VDialog>

  <VDialog
    v-model="showCarguesDialog"
    persistent
    max-width="800px"
    attach="#app"
    :retain-focus="false"
    scrollable
    transition="dialog-top-transition"
    content-class="align-self-start mt-5"
  >
    <VCard>
      <VCardTitle class="modal-title d-flex align-center text-h6 bg-error text-white py-3 px-4 mt-0 mb-0">
        <VIcon
          icon="tabler-settings-plus"
          size="28"
          color="white"
          class="me-3"
        />
        Cargue de Productos
        <span
          class="text-h6 font-weight-bold ml-2"
          style="color: #f7fb2d !important;"
        >
          Factura : {{ purchaseinvoice }}
        </span>
      </VCardTitle>

      <VCardText class="pt-0 pb-0">
        <VDefaultsProvider
          :defaults="{
            AppTextField: {
              density: 'comfortable',
              variant: 'outlined',
              hideDetails: true,
              class: 'mb-3 text_size aligned-field',
            },
            AppSelect: {
              density: 'comfortable',
              variant: 'outlined',
              hideDetails: true,
              class: 'mb-3 text_size aligned-field',
            },
          }"
        >
          <VForm>
            <VRow
              dense
              align="center"
              class="g-2"
            >
              <VCol
                cols="12"
                class="my-2"
              >
                <VDivider
                  color="dark"
                  thickness="2"
                />
              </VCol>
              <VCol cols="12">
                <VAutocomplete
                  :key="autocompleteProductoKey"
                  v-model="productoSeleccionado"
                  :items="products"
                  item-title="name"
                  item-value="id"
                  label="Producto"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  prepend-inner-icon="mdi-archive-search-outline"
                  return-object
                  class="custom-autocomplete"
                  @update:model-value="onProductoSeleccionado"
                >
                  <template #item="{ props, item }">
                    <VListItem
                      v-bind="props"
                      :title="item.raw.name"
                      style="font-size: 10px !important;"
                    >
                      <template #subtitle>
                        <span class="text-success font-weight-medium">
                          <!-- Stock disponible: {{ aNumero(item.raw.quantity) }} {{ item.raw.measure_name }} -->
                        </span>
                        <span
                          class="text-primary font-weight-bold"
                          style="font-size: 12px;"
                        >
                          {{ formatoMoneda(item.raw.cost) }}
                        </span>
                      </template>
                    </VListItem>
                  </template>
                </VAutocomplete>
              </VCol>

              <VCol
                cols="12"
                md="1"
                class="py-0"
              >
                <AppTextField
                  v-model="newRecordCargue.store"
                  label="Bodega"
                  required
                  class="mb-2 text_size"
                  input-class="text-end"
                  :rules="[rules.required]"
                  placeholder="Ingresar Cantidad"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0 d-flex justify-end"
              >
                <AppTextField
                  v-model="newRecordCargue.quantity"
                  label="Ingresar Cantidad"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Ingresar Cantidad"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  type="number"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-writing"
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
                  v-model="newRecordCargue.discount"
                  label="(%) Dscto"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Descuento"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  type="number"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-percentage"
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
                  v-model="newRecordCargue.vat"
                  label="(%) Iva"
                  :rules="[rules.required]"
                  class="mb-2 text_size aligned-field"
                  placeholder="Iva"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  type="number"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-percentage"
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
                  v-model="newRecordCargue.cost"
                  label="Costo Unitario"
                  required
                  class="mb-2 text_size aligned-field"
                  :rules="[rules.required]"
                  placeholder="Descuento"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  type="number"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-basket-dollar"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextField>
              </VCol>
            </VRow>
          </VForm>
        </VDefaultsProvider>
        <VDivider
          color="dark"
          thickness="2"
        />
        <VRow
          cols="12"
          class="justify-end"
        >
          <VCardActions class="mt-1 px-2 mb-3 mx-4 mt-4">
            <VBtn
              color="error"
              variant="flat"
              class="text-white"
              @click="showCarguesDialog = false"
            >
              Cancelar
            </VBtn>
            <VBtn
              color="primary"
              variant="flat"
              class="text-white"
              @click="agregarProducto"
            >
              Agregar Producto
            </VBtn>
          </VCardActions>
        </VRow>
      </VCardText>
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

  <VDialog
    v-model="showSaveChargesDialog"
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
        Confirmar Actualización <br>
        Factura : {{ purchaseinvoice }}
      </VCardTitle>
      <VCardText class="text-center">
        ¿Está seguro Actualizar el Igreso de Productos, a la Compra ?<br>
        <strong>Esta acción no se puede deshacer.</strong>
      </VCardText>
      <VCardActions class="justify-center pb-4">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="showSaveChargesDialog = false"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="error"
          variant="flat"
          @click="saveRecordCompras"
        >
          Grabar
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
.products-grid :deep(thead th .v-table) {
  background-color: #1e1e1e !important; /* Negro mate elegante */
  block-size: 36px !important;              /* Altura compacta para el header */
  color: #fff !important;            /* Texto blanco */
  font-size: 0.75rem !important;
  font-weight: 600 !important;
  text-transform: uppercase;
}

/* Ajustes finos para las filas de datos de la grilla */
.products-gridc :deep(tbody td) {
  block-size: 32px !important;              /* Filas delgadas para optimizar espacio */
  font-size: 0.9rem !important;
}

/* 1. Forzamos la altura de la fila y de cada celda */
.products-gridc.v-table.v-data-table__tr {
  /* Estas variables controlan la altura base de las filas en Vuetify 3 */
  --v-table-row-height: 15px !important;
  --v-table-header-height: 30px !important;
}
</style>
