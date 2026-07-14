<script setup lang="ts">
import axios from 'axios'
import { computed, reactive, ref, watch } from 'vue'

const snackbarMessage = ref('')
const snackbarColor = ref('success')
const snackbarVisible = ref(false)

const guardando = ref(false)

// 🔹 Filtros y variables de estado
const searchQuery = ref('')
const selectedRows = ref([])

// 🔹 Opciones del datatable
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const tableKey = ref(0)

const forceRefresh = () => {
  tableKey.value += 1
}

const hoy = new Date().toISOString().split('T')[0]

const token = localStorage.getItem('auth_token')

const responseData = ref({
  balances: [],
  customers: [],
  total: 0,
  page: 1,
  per_page: 10,
  totaldctos: 0,
})

const products = ref([])
const customers = ref([])
const paymentmethods = ref([])

// Los campos numéricos llegan como string desde el backend (price, quantity, etc.)
function aNumero(valor: string | number | null | undefined): number {
  if (valor === null || valor === undefined)
    return 0
  const numero = typeof valor === 'number' ? valor : Number.parseFloat(valor)

  return isNaN(numero) ? 0 : numero
}

// ===================== Tipos =====================
type TipoDocumento = 'contado' | 'credito'
type ClavePrecio = 'mayorista' | 'minorista' | 'especial'

interface Cliente {
  id: number
  nombre: string
  nit: string
  limiteCredito: number
  listaPrecio: 'Mayorista' | 'Minorista'
}

interface ListaPrecio {
  id: ClavePrecio
  nombre: string
}

interface Method {
  id: number
  code: string
  name: string
}

const PaymentsMethod = ref<Method>({
  id: 10,
  code: '10',
  name: '',
})

interface Producto {
  id: number
  products_id: number
  companies_id: number
  year: string
  code: string
  store: string
  batch: string
  group_name: string
  product_name: string
  quantity: string
  quantity1: string
  price: string
  cost: string
  lastcost: string
  measure_name: string
  previous_balance: number | null
  subtotal: string
}

interface ItemFactura {
  idLinea: number
  productoId: number
  codigo: string
  nombre: string
  store: string
  unidad: string
  cantidad: number
  precioUnitario: number
  descuentoPorcentaje: number
  cost: number
  percent: number
  measure_name: string
}

interface Factura {
  cliente: Cliente | null
  tipoDocumento: TipoDocumento
  fechaFactura: string
  fechaVencimiento: string
  listaPrecio: ClavePrecio
  items: ItemFactura[]
  subtotalBruto: number
  descuentoItemsTotal: number
  subtotal: number
  descuentoGlobal: number
  iva: number
  costoVenta: number
  total: number
}

// Estructura del arreglo 'balances' que trae la tarifa de IVA vigente (percent)
interface Balance {
  id: number
  name?: string
  percent: string | number
  [key: string]: any
}

// ===================== Cliente =====================
const clientes = ref<Cliente[]>([
  { id: 1, nombre: 'Comercializadora del Norte S.A.S.', nit: '900123456-1', limiteCredito: 5000000, listaPrecio: 'Mayorista' },
  { id: 2, nombre: 'Ferretería La Económica', nit: '901456789-2', limiteCredito: 2000000, listaPrecio: 'Minorista' },
  { id: 3, nombre: 'Distribuciones Andinas Ltda.', nit: '800987654-3', limiteCredito: 8000000, listaPrecio: 'Mayorista' },
  { id: 4, nombre: 'Tienda Don José', nit: '1065432109', limiteCredito: 500000, listaPrecio: 'Minorista' },
])

const clienteSeleccionado = ref<Cliente | null>(null)
const clienteInfo = ref<Cliente | null>(null)

const MetodoSeleccionado = ref<Method | null>(null)
const metodoInfo = ref<Cliente | null>(null)

// ===================== Documento =====================
const docTipo = ref<TipoDocumento>('contado')
const fechaFactura = ref<string>(new Date().toISOString().substring(0, 10))
const fechaVencimiento = ref<string>(new Date().toISOString().substring(0, 10))
const costoDeVenta = ref(0)

// Estado de los menús/selectores de fecha (formato visual siempre "YYYY-MM-DD")
const menuFechaFactura = ref<boolean>(false)
const menuFechaVencimiento = ref<boolean>(false)

// Convierte una fecha "YYYY-MM-DD" a Date (para alimentar el v-date-picker)
function aFecha(valor: string): Date | null {
  if (!valor)
    return null
  const [year, month, day] = valor.split('-').map(Number)

  return new Date(year, month - 1, day)
}

// Convierte un Date (lo que entrega el v-date-picker) a string "YYYY-MM-DD"
function aFechaTexto(fecha: Date): string {
  const year = fecha.getFullYear()
  const month = String(fecha.getMonth() + 1).padStart(2, '0')
  const day = String(fecha.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

const fechaFacturaDate = computed<Date | null>(() => aFecha(fechaFactura.value))
const fechaVencimientoDate = computed<Date | null>(() => aFecha(fechaVencimiento.value))

function onSeleccionarFechaFactura(fecha: unknown): void {
  if (fecha instanceof Date)
    fechaFactura.value = aFechaTexto(fecha)

  menuFechaFactura.value = false
}

function onSeleccionarFechaVencimiento(fecha: unknown): void {
  if (fecha instanceof Date)
    fechaVencimiento.value = aFechaTexto(fecha)

  menuFechaVencimiento.value = false
}

// ===================== Listas de precios =====================
const listasPrecios = ref<ListaPrecio[]>([
  { id: 'mayorista', nombre: 'Mayorista' },
  { id: 'minorista', nombre: 'Minorista' },
  { id: 'especial', nombre: 'Especial / Promoción' },
])

const listaPrecioSeleccionada = ref<ClavePrecio>('minorista')
const descuentoGlobal = ref<number>(0)

// ===================== Productos / Inventario =====================
const productos = reactive<Producto[]>([
  { id: 1, nombre: 'Cemento Gris 50kg', unidad: 'Bulto', stock: 120, precios: { mayorista: 28000, minorista: 32000, especial: 26000 } },
  { id: 2, nombre: 'Varilla de Acero 1/2" x 6m', unidad: 'Unidad', stock: 80, precios: { mayorista: 22000, minorista: 25500, especial: 21000 } },
  { id: 3, nombre: 'Pintura Acrílica Blanca 1Gal', unidad: 'Galón', stock: 45, precios: { mayorista: 65000, minorista: 78000, especial: 60000 } },
  { id: 4, nombre: 'Teja de Zinc 3m', unidad: 'Unidad', stock: 60, precios: { mayorista: 34000, minorista: 39000, especial: 32000 } },
  { id: 5, nombre: 'Tubo PVC 4" x 6m', unidad: 'Unidad', stock: 30, precios: { mayorista: 41000, minorista: 47500, especial: 39000 } },
  { id: 6, nombre: 'Adhesivo para Cerámica 25kg', unidad: 'Bulto', stock: 15, precios: { mayorista: 24000, minorista: 28000, especial: 23000 } },
])

const productoSeleccionado = ref<Producto | null>(null)

// Se incrementa cada vez que se agrega un producto, para forzar el remontaje del
// VAutocomplete y así limpiar el texto de búsqueda interno (no se borra solo con v-model = null)
const autocompleteProductoKey = ref<number>(0)

const cantidadAgregar = ref<number>(1)
const descuentoItemAgregar = ref<number>(0)
const precioUnitarioManual = ref<number>(0)

// ===================== Detalle de la factura =====================
const itemsFactura = ref<ItemFactura[]>([])
let contadorLinea = 1

// ===================== Edición de línea =====================
const dialogEdicion = ref<boolean>(false)
const indiceEnEdicion = ref<number | null>(null)
const itemEnEdicion = ref<ItemFactura | null>(null)

const formEdicion = reactive<{ cantidad: number; descuentoPorcentaje: number }>({
  cantidad: 1,
  descuentoPorcentaje: 0,
})

// ===================== Eliminación de línea =====================
const dialogEliminacion = ref<boolean>(false)
const indiceAEliminar = ref<number | null>(null)
const itemAEliminar = ref<ItemFactura | null>(null)

// ===================== Mensajes =====================
const mostrarMensaje = ref<boolean>(false)
const textoMensaje = ref<string>('')
const colorMensaje = ref<string>('success')

// ===================== Computados =====================
// const productosDisponibles = computed<Producto[]>(() =>
//   productos.filter(p => p.stock > 0),
// )

// Indica si el usuario modificó manualmente el precio sugerido por el inventario
const precioFueModificado = computed<boolean>(() => {
  if (!productoSeleccionado.value)
    return false

  return precioUnitarioManual.value !== aNumero(productoSeleccionado.value.price)
})

const precioUnitarioPreview = computed<string>(() => {
  if (!productoSeleccionado.value)
    return ''

  return formatoMoneda(aNumero(productoSeleccionado.value.price))
})

// Subtotal de una línea ya aplicando su descuento por ítem (%)
function subtotalItem(item: ItemFactura): number {
  const bruto = item.cantidad * item.precioUnitario
  const descuento = bruto * (item.descuentoPorcentaje || 0) / 100

  return Math.round(bruto - descuento)
}

const subtotalBruto = computed<number>(() =>
  itemsFactura.value.reduce((acc, item) => acc + item.cantidad * item.precioUnitario, 0),
)

const descuentoItemsTotal = computed<number>(() =>
  itemsFactura.value.reduce((acc, item) => {
    const bruto = item.cantidad * item.precioUnitario

    return acc + Math.round(bruto * (item.descuentoPorcentaje || 0) / 100)
  }, 0),
)

// Subtotal neto: suma de cada línea ya con su descuento por ítem aplicado
const subtotal = computed<number>(() => subtotalBruto.value - descuentoItemsTotal.value)

// El descuento adicional sobre la factura es un valor absoluto ingresado directamente
const baseGravable = computed<number>(() => Math.max(subtotal.value - (descuentoGlobal.value || 0), 0))

// const iva = computed<number>(() => Math.round(baseGravable.value * 0.19))
const iva = computed<number>(() => Math.round(baseGravable.value * 0.0))

const total = computed<number>(() => baseGravable.value + iva.value)

const puedeGuardar = computed<boolean>(() =>
  !!clienteSeleccionado.value && itemsFactura.value.length > 0,
)

// Stock disponible al editar: el saldo actual del producto + la cantidad que esa
// misma línea ya tenía reservada (porque aún no se ha devuelto al inventario)
const stockDisponibleParaEdicion = computed<number>(() => {
  if (!itemEnEdicion.value)
    return 0
  const producto = productos.find(p => p.id === itemEnEdicion.value!.productoId)

  return producto ? producto.stock + itemEnEdicion.value.cantidad : 0
})

// ===================== Watchers =====================
watch(docTipo, valor => {
  if (valor === 'contado') {
    fechaVencimiento.value = fechaFactura.value
  }
  else if (!fechaVencimiento.value) {
    const fecha = new Date(fechaFactura.value)

    fecha.setDate(fecha.getDate() + 30)
    fechaVencimiento.value = fecha.toISOString().substring(0, 10)
  }
})

// ===================== Watchers =====================
// Al seleccionar un producto, se sugiere su precio de inventario, pero queda editable
watch(productoSeleccionado, producto => {
  precioUnitarioManual.value = producto ? aNumero(producto.price) : 0
})

// ===================== Métodos =====================
// ===================== Métodos =====================
function onClienteSeleccionado(cliente: Cliente | null): void {
  clienteInfo.value = cliente || null
  if (cliente) {
    // Mapeo de price_list_id (numérico, viene del controller) a la clave interna de lista de precios
    const mapaListas: Record<number, ClavePrecio> = { 1: 'minorista', 2: 'mayorista', 3: 'especial' }

    listaPrecioSeleccionada.value = mapaListas[cliente.price_list_id] ?? 'minorista'

    // Si el documento es a crédito, usamos el plazo (deadline_days) propio del cliente
    if (docTipo.value === 'credito') {
      const fecha = new Date(fechaFactura.value)

      fecha.setDate(fecha.getDate() + (cliente.deadline_days || 30))
      fechaVencimiento.value = fecha.toISOString().substring(0, 10)
    }
  }
}

function recalcularPrecios(): void {
  // Recalcula los precios de los items ya agregados según la lista seleccionada
  itemsFactura.value = itemsFactura.value.map(item => {
    const producto = productos.find(p => p.id === item.productoId)

    return {
      ...item,
      precioUnitario: producto ? producto.precios[listaPrecioSeleccionada.value] : item.precioUnitario,
    }
  })
}

function agregarProducto(): void {
  if (!productoSeleccionado.value || cantidadAgregar.value <= 0)
    return

  const stockDisponible = aNumero(productoSeleccionado.value.quantity)
  if (cantidadAgregar.value > stockDisponible) {
    mostrarSnack('La cantidad supera el saldo de inventario disponible', 'warning')

    return
  }

  if (precioUnitarioManual.value < 0) {
    mostrarSnack('El precio unitario no puede ser negativo', 'warning')

    return
  }

  const precioUnitario = precioUnitarioManual.value
  const descuento = Math.min(Math.max(descuentoItemAgregar.value || 0, 0), 100)

  itemsFactura.value.push({
    idLinea: contadorLinea++,
    productoId: productoSeleccionado.value.id,
    nombre: productoSeleccionado.value.product_name,
    codigo: productoSeleccionado.value.code,
    unidad: productoSeleccionado.value.measure_name?.trim() || productoSeleccionado.value.group_name,
    store: productoSeleccionado.value.store,
    cantidad: cantidadAgregar.value,
    precioUnitario,
    descuentoPorcentaje: descuento,
    ivaPorcentaje: aNumero(productoSeleccionado.value.percent),
    cost: aNumero(productoSeleccionado.value.cost),
  })

  // Descontamos el saldo de inventario en el arreglo local (simulación)
  const producto = products.value.find(p => p.id === productoSeleccionado.value!.id)
  if (producto)
    producto.quantity = String(stockDisponible - cantidadAgregar.value)

  costoDeVenta.value = costoDeVenta.value + cantidadAgregar.value * producto.cost
  productoSeleccionado.value = null
  cantidadAgregar.value = 1
  descuentoItemAgregar.value = 0
  precioUnitarioManual.value = 0
  autocompleteProductoKey.value++

  // console.log('Soy Detalle de Items: ', itemsFactura)
}

// ===================== Eliminación con confirmación =====================
function confirmarEliminacion(index: number): void {
  indiceAEliminar.value = index
  itemAEliminar.value = itemsFactura.value[index]
  dialogEliminacion.value = true
}

function eliminarItemConfirmado(): void {
  if (indiceAEliminar.value === null)
    return
  const item = itemsFactura.value[indiceAEliminar.value]
  const producto = productos.find(p => p.id === item.productoId)
  if (producto)
    producto.stock += item.cantidad
  itemsFactura.value.splice(indiceAEliminar.value, 1)

  dialogEliminacion.value = false
  indiceAEliminar.value = null
  itemAEliminar.value = null
  mostrarSnack('Producto eliminado de la factura', 'success')
}

// ===================== Edición de línea =====================
function abrirEdicion(index: number): void {
  const item = itemsFactura.value[index]

  indiceEnEdicion.value = index
  itemEnEdicion.value = item
  formEdicion.cantidad = item.cantidad
  formEdicion.descuentoPorcentaje = item.descuentoPorcentaje
  dialogEdicion.value = true
}

function guardarEdicion(): void {
  if (indiceEnEdicion.value === null || !itemEnEdicion.value)
    return

  if (formEdicion.cantidad <= 0 || formEdicion.cantidad > stockDisponibleParaEdicion.value) {
    mostrarSnack('La cantidad ingresada no es válida para el saldo disponible', 'warning')

    return
  }

  const producto = productos.find(p => p.id === itemEnEdicion.value!.productoId)
  const cantidadAnterior = itemEnEdicion.value.cantidad

  // Ajustamos el saldo de inventario según la diferencia entre la cantidad anterior y la nueva
  if (producto)
    producto.stock += cantidadAnterior - formEdicion.cantidad

  const item = itemsFactura.value[indiceEnEdicion.value]

  item.cantidad = formEdicion.cantidad
  item.descuentoPorcentaje = Math.min(Math.max(formEdicion.descuentoPorcentaje || 0, 0), 100)

  cancelarEdicion()
  mostrarSnack('Producto actualizado correctamente', 'success')
}

function cancelarEdicion(): void {
  dialogEdicion.value = false
  indiceEnEdicion.value = null
  itemEnEdicion.value = null
}

function limpiarFactura(): void {
  // Devolvemos el stock de todos los items antes de limpiar
  itemsFactura.value.forEach(item => {
    const producto = productos.find(p => p.id === item.productoId)
    if (producto)
      producto.stock += item.cantidad
  })
  itemsFactura.value = []
  clienteSeleccionado.value = null
  clienteInfo.value = null
  productoSeleccionado.value = null
  cantidadAgregar.value = 1
  descuentoItemAgregar.value = 0
  descuentoGlobal.value = 0
  PaymentsMethod.value.id = 10

  seleccionarClientePorDefecto()
}

const facturarInfo = async (factura: Factura) => {
  try {
    const response = await axios.post('/api/facturas', {

      // El segundo argumento es directamente el Body de la petición
      q: searchQuery.value,
      factura,
      company_id: localStorage.getItem('company_id'),
      process_year: localStorage.getItem('process_year'),
      tipo_documento: docTipo.value,
      payment_methods: PaymentsMethod.value.id,
    },
    {
    // El tercer argumento es la configuración (Headers, etc.)
      headers: { Authorization: `Bearer ${token}` },
    })

    responseData.value = response.data
  }
  catch (error) {
    console.error('Error al intentar enviar correo :', error)
  }
}

function guardarFactura(): void {
  const factura: Factura = {
    cliente: clienteInfo.value,
    tipoDocumento: docTipo.value,
    fechaFactura: fechaFactura.value,
    fechaVencimiento: fechaVencimiento.value,
    listaPrecio: listaPrecioSeleccionada.value,
    items: itemsFactura.value,
    subtotalBruto: subtotalBruto.value,
    descuentoItemsTotal: descuentoItemsTotal.value,
    subtotal: subtotal.value,
    descuentoGlobal: descuentoGlobal.value,
    iva: iva.value,
    costoVenta: costoDeVenta.value,
    total: total.value,
  }

  // Aquí se realizaría la petición al backend Laravel, por ejemplo:
  // await axios.post('/api/facturas', factura)
  guardando.value = true

  console.log('Factura a guardar:', factura)

  facturarInfo(factura)

  // mostrarSnack('Factura guardada correctamente', 'success')

  snackbarMessage.value = '✅ Producto eliminado correctamente'
  snackbarColor.value = 'success'

  limpiarFactura()
}

function imprimirFactura(): void {
  if (!puedeGuardar.value) {
    mostrarSnack('Agrega un cliente y al menos un producto antes de imprimir', 'warning')

    return
  }

  // Se usa la impresión nativa del navegador (el usuario elige "Guardar como PDF")
  // para mantener la solución simple y sin dependencias externas.
  window.print()
}

function mostrarSnack(texto: string, color: string): void {
  textoMensaje.value = texto
  colorMensaje.value = color
  mostrarMensaje.value = true
}

function formatoMoneda(valor: number): string {
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(valor || 0)
}

// Esta variable dirá exactamente si el formulario es válido para agregar
const formularioInvalido = computed(() => {
  return !productoSeleccionado.value || cantidadAgregar.value <= 0
})

const loadInfo = async () => {
  try {
    const response = await axios.get('/api/getinfo', {
      params: {
        q: searchQuery.value,
        itemsPerPage: itemsPerPage.value,
        page: page.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        company_id: localStorage.getItem('company_id'),
        process_year: localStorage.getItem('process_year'),
      },
      headers: { Authorization: `Bearer ${token}` },
    })

    responseData.value = response.data

    products.value = response.data.balances
    customers.value = response.data.customers
    paymentmethods.value = response.data.payment_methods

    seleccionarClientePorDefecto()

    // grupos.value = response.data.grupos
    // sgrupos.value = response.data.sgrupos
    // unidades.value = response.data.unidades

    console.log('Soy Método de Pago:', paymentmethods.value)
  }
  catch (error) {
    console.error('Error al intentar enviar correo :', error)
  }
}

onMounted(async () => {
  // 2. Ejecutas la función cuando la vista esté montada
  await loadInfo()
})

// ===================== Carga inicial =====================
const NIT_CLIENTE_DEFECTO = '222222222222'

function seleccionarClientePorDefecto(): void {
  console.log('Entre Aqui cliente seleccionado (2002)')

  const clientePorDefecto = customers.value.find(
    c => String(c.nit).trim() === NIT_CLIENTE_DEFECTO,
  )

  if (clientePorDefecto) {
    clienteSeleccionado.value = clientePorDefecto
    onClienteSeleccionado(clientePorDefecto)
  }
  else {
    // Ayuda a depurar si el NIT no coincide (revisa la consola del navegador)
    console.warn(
      `No se encontró ningún cliente con NIT "${NIT_CLIENTE_DEFECTO}". NITs disponibles:`,
      customers.value.map(c => c.nit),
    )
  }
}

// const productosDisponibles = computed<Producto[]>(() =>
//   productos.filter(p => aNumero(p.quantity) > 0),
// )

const productosDisponibles = computed(() =>
  products.value.filter(p => aNumero(p.quantity) > 0),
)

// Descontamos el saldo de inventario en el arreglo local (simulación)
const producto = products.value.find(p => p.id === productoSeleccionado.value!.id)
if (producto)
  products.value.quantity -= cantidadAgregar.value

// Se toma la tarifa vigente desde 'balances' (primer registro, o el que aplique según tu regla de negocio)
// const porcentajeIva = computed<number>(() => aNumero(balances.value[0]?.percent))
</script>

<template>
  <VApp>
    <VMain class="bg-grey elevation-9">
      <VContainer
        fluid
        class="pa-4 pa-md-3"
      >
        <div class="contenido-pantalla">
          <!-- ===== Encabezado ===== -->
          <!--
            <div class="d-flex flex-column flex-md-row justify-space-between align-md-center mb-6 gap-4">
            <div>
            <h1 class="text-h4 font-weight-bold header-title">
            Nueva factura de venta
            </h1>
            <p class="text-medium-emphasis mb-0">
            Completa los datos del cliente y agrega los productos a facturar
            </p>
            </div>
            <VChip
            class="px-4"
            size="large"
            :color="docTipo === 'contado' ? 'teal' : 'indigo'"
            variant="flat"
            label
            >
            <VIcon
            start
            :icon="docTipo === 'contado' ? 'mdi-cash-fast' : 'mdi-credit-card-clock-outline'"
            />
            {{ docTipo === 'contado' ? 'Documento de Contado' : 'Documento de Crédito' }}
            </VChip>
            </div>
          -->

          <VRow>
            <!-- ===================== COLUMNA DERECHA: Productos ===================== -->
            <VCol
              cols="12"
              md="8"
            >
              <VCard
                class="rounded-xl mb-2 border-accent"
                elevation="3"
                border="2"
              >
                <VCardTitle class="text-primary text-h6 font-weight-bold d-flex align-center text-h5">
                  <VIcon>
                    mdi-package-variant-closed
                  </VIcon>
                  Agregar producto
                </VCardTitle>
                <VCardText>
                  <VRow dense>
                    <VCol
                      cols="12"
                      md="6"
                    >
                      <VAutocomplete
                        :key="autocompleteProductoKey"
                        v-model="productoSeleccionado"
                        :items="productosDisponibles"
                        item-title="product_name"
                        item-value="id"
                        label="Producto"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        prepend-inner-icon="mdi-archive-search-outline"
                        return-object
                        class="custom-autocomplete"
                      >
                        <template #item="{ props, item }">
                          <VListItem
                            v-bind="props"
                            :title="item.raw.product_name"
                          >
                            <template #subtitle>
                              <span class="text-success font-weight-medium">
                                Stock disponible: {{ aNumero(item.raw.quantity) }} {{ item.raw.measure_name }}
                              </span>
                              <span class="text-primary font-weight-bold">
                                {{ formatoMoneda(item.raw.price) }}
                              </span>
                            </template>
                          </VListItem>
                        </template>
                      </VAutocomplete>
                    </VCol>
                    <VCol
                      cols="6"
                      md="1"
                    >
                      <VTextField
                        v-model.number="cantidadAgregar"
                        type="number"
                        min="1"
                        :max="productoSeleccionado ? productoSeleccionado.stock : 9999"
                        label="Cantidad"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                      />
                    </VCol>
                    <VCol
                      cols="6"
                      md="2"
                    >
                      <VTextField
                        v-model.number="precioUnitarioManual"
                        type="number"
                        min="0"
                        step="100"
                        label="Precio unit."
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                        prepend-inner-icon="mdi-currency-usd"
                        :hint="precioFueModificado ? 'Precio modificado manualmente' : ''"
                        persistent-hint
                      />
                    </VCol>
                    <VCol
                      cols="6"
                      md="1"
                    >
                      <VTextField
                        v-model.number="descuentoItemAgregar"
                        type="number"
                        min="0"
                        max="100"
                        step="0.5"
                        label="% Dsct"
                        variant="outlined"
                        density="comfortable"
                        rounded="lg"
                      />
                    </VCol>
                    <VCol
                      cols="12"
                      md="2"
                      class="d-flex align-center"
                    >
                      <VBtn
                        :color="formularioInvalido ? 'indigo' : 'success'"
                        :disabled="formularioInvalido"
                        rounded="lg"
                        height="40"
                        width="120"
                        @click="agregarProducto"
                      >
                        <VIcon
                          start
                          size="22"
                        >
                          tabler-plus
                        </VIcon>
                        Agregar
                      </VBtn>
                    </VCol>
                  </VRow>
                  <VAlert
                    v-if="productoSeleccionado && cantidadAgregar > productoSeleccionado.stock"
                    type="warning"
                    variant="tonal"
                    density="comfortable"
                    rounded="lg"
                    class="mt-2"
                  >
                    La cantidad supera el saldo de inventario disponible ({{ productoSeleccionado.stock }} {{ productoSeleccionado.unidad }}).
                  </VAlert>
                </VCardText>
              </VCard>

              <!-- ===== Tabla de items de la factura ===== -->
              <VCard
                class="rounded-xl mb-2 border-accent"
                elevation="3"
                border="2"
              >
                <VCardTitle class="text-primary text-h6 font-weight-bold d-flex align-center justify-space-between">
                  <div class="d-flex align-center">
                    <VIcon
                      class="mr-2"
                      color="indigo"
                    >
                      mdi-format-list-bulleted
                    </VIcon>
                    Detalle de la factura
                  </div>
                  <VChip
                    size="small"
                    variant="tonal"
                    color="indigo"
                  >
                    {{ itemsFactura.length }} ítem(s)
                  </VChip>
                </VCardTitle>
                <VCardText class="pt-0">
                  <VTable
                    v-if="itemsFactura.length"
                    class="rounded-lg overflow-hidden custom-table"
                    dense
                    density="compact"
                    striped="even"
                  >
                    <thead>
                      <tr>
                        <th
                          class="text-subtitle-2 encabezado-tabla"
                          style="background-color: #3f51b5 !important; color: #fff !important;"
                        >
                          Descripción del Producto
                        </th>
                        <th
                          class="text-subtitle-2 text-right"
                          style="background-color: #3f51b5 !important; color: #fff !important;"
                        >
                          Bd
                        </th>
                        <th
                          class="text-subtitle-2 text-right"
                          style="background-color: #3f51b5 !important; color: #fff !important;"
                        >
                          Cant.
                        </th>
                        <th
                          class="text-subtitle-2 text-right"
                          style="background-color: #3f51b5 !important; color: #fff !important;"
                        >
                          Precio unit.
                        </th>
                        <th
                          class="text-subtitle-2 text-right"
                          style="background-color: #3f51b5 !important; color: #fff !important;"
                        >
                          Desc. (%)
                        </th>
                        <th
                          class="text-subtitle-2 text-right"
                          style="background-color: #3f51b5 !important; color: #fff !important;"
                        >
                          Subtotal
                        </th>
                        <th
                          class="text-subtitle-2 text-center"
                          style="background-color: #3f51b5 !important; color: #fff !important;"
                        >
                          Acción
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(item, index) in itemsFactura"
                        :key="item.idLinea"
                        py-0
                      >
                        <td width="40%">
                          <div class="text-body-2">
                            {{ item.nombre }}
                          </div>
                          <div class="text-body-2 text-primary">
                            {{ item.measure_name }}
                          </div>
                        </td>
                        <td class="text-right text-body-2">
                          {{ item.store }}
                        </td>
                        <td class="text-right text-body-2">
                          {{ item.cantidad }}
                        </td>
                        <td class="text-right text-body-2">
                          {{ formatoMoneda(item.precioUnitario) }}
                        </td>
                        <td class="text-right text-body-2">
                          <span
                            v-if="item.descuentoPorcentaje > 0"
                            class="text-warning text-body-2"
                          >{{ item.descuentoPorcentaje }}%</span>
                          <span
                            v-else
                            class="text-medium-emphasis"
                          >—</span>
                        </td>
                        <td class="text-right text-body-2">
                          {{ formatoMoneda(subtotalItem(item)) }}
                        </td>
                        <td class="text-center text-no-wrap">
                          <VBtn
                            icon
                            size="small"
                            variant="text"
                            color="success"
                            @click="abrirEdicion(index)"
                          >
                            <VIcon size="20">
                              tabler-edit
                            </VIcon>
                          </VBtn>
                          <VBtn
                            icon
                            size="small"
                            variant="text"
                            color="warning"
                            @click="confirmarEliminacion(index)"
                          >
                            <VIcon size="20">
                              tabler-trash
                            </VIcon>
                          </VBtn>
                        </td>
                      </tr>
                    </tbody>
                  </VTable>

                  <VEmptyState
                    v-else
                    icon="mdi-package-variant-closed-remove"
                    title="Sin productos agregados"
                    text="Selecciona un producto y agrégalo para construir el detalle de la factura."
                  />

                  <VDivider class="my-4" />

                  <!-- ===== Totales ===== -->
                  <div class="d-flex justify-end">
                    <div style="min-inline-size: 280px;">
                      <div class="d-flex justify-space-between text-body-2 mb-1">
                        <span class="text-medium-emphasis">Subtotal</span>
                        <span>{{ formatoMoneda(subtotalBruto) }}</span>
                      </div>
                      <div
                        v-if="descuentoItemsTotal > 0"
                        class="d-flex justify-space-between text-body-2 mb-1 text-red-darken-2"
                      >
                        <span>Descuento por productos</span>
                        <span>- {{ formatoMoneda(descuentoItemsTotal) }}</span>
                      </div>
                      <div
                        v-if="descuentoGlobal > 0"
                        class="d-flex justify-space-between text-body-2 mb-1 text-red-darken-2"
                      >
                        <span>Descuento adicional</span>
                        <span>- {{ formatoMoneda(descuentoGlobal) }}</span>
                      </div>
                      <div class="d-flex justify-space-between text-body-2 mb-1">
                        <span class="text-medium-emphasis">IVA (19%)</span>
                        <span>{{ formatoMoneda(iva) }}</span>
                      </div>
                      <VDivider class="my-2" />
                      <div class="d-flex justify-space-between text-h6 font-weight-bold">
                        <span>Total</span>
                        <span class="total-text">{{ formatoMoneda(total) }}</span>
                      </div>
                    </div>
                  </div>
                </VCardText>
                <!--
                  <VCardActions class="px-4 pb-4">
                  <VSpacer />
                  <VBtn
                  variant="text"
                  rounded="lg"
                  @click="limpiarFactura"
                  >
                  Cancelar
                  </VBtn>
                  <VBtn
                  variant="outlined"
                  color="indigo"
                  size="large"
                  rounded="lg"
                  :disabled="!puedeGuardar"
                  @click="imprimirFactura"
                  >
                  <VIcon start>
                  mdi-file-pdf-box
                  </VIcon>
                  Imprimir / PDF
                  </VBtn>
                  <VBtn
                  color="indigo"
                  size="large"
                  rounded="lg"
                  :disabled="!puedeGuardar"
                  @click="guardarFactura"
                  >
                  <VIcon start>
                  mdi-content-save-check-outline
                  </VIcon>
                  Guardar factura
                  </VBtn>
                  </VCardActions>
                -->
              </VCard>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <VCard
                class="rounded-xl mb-2 border-accent"
                elevation="3"
                border="2"
              >
                <VCardTitle class="text-primary text-h6 font-weight-bold d-flex align-center">
                  <VIcon
                    class="mr-2"
                    color="indigo"
                  >
                    mdi-account-circle-outline
                  </VIcon>
                  Datos del cliente
                </VCardTitle>
                <VCardText>
                  <VAutocomplete
                    v-model="clienteSeleccionado"
                    :items="customers"
                    item-title="name"
                    item-value="id"
                    label="Cliente"
                    prepend-inner-icon="mdi-magnify"
                    variant="outlined"
                    density="comfortable"
                    rounded="lg"
                    return-object
                    class="custom-autocomplete"
                    @update:model-value="onClienteSeleccionado"
                  />

                  <VFadeTransition>
                    <div
                      v-if="clienteInfo"
                      class="info-cliente mt-2 pa-3 rounded-lg"
                    >
                      <div class="d-flex justify-space-between text-body-2">
                        <span class="text-medium-emphasis">NIT / Doc.</span>
                        <span class="font-weight-medium">{{ clienteInfo.nit }}</span>
                      </div>
                      <div class="d-flex justify-space-between text-body-2">
                        <span class="text-medium-emphasis">Límite de crédito</span>
                        <span class="font-weight-medium">{{ formatoMoneda(clienteInfo.limiteCredito) }}</span>
                      </div>
                      <div class="d-flex justify-space-between text-body-2">
                        <span class="text-medium-emphasis">Lista de precios</span>
                        <VChip
                          size="x-small"
                          color="teal"
                          variant="flat"
                        >
                          {{ clienteInfo.listaPrecio }}
                        </VChip>
                      </div>
                    </div>
                  </VFadeTransition>
                </VCardText>
              </VCard>

              <VCard
                class="rounded-xl mb-2 border-accent"
                elevation="3"
                border="2"
              >
                <VCardTitle class="text-primary text-h6 font-weight-bold d-flex align-center">
                  <VIcon
                    class="mr-2"
                    color="indigo"
                  >
                    mdi-file-document-outline
                  </VIcon>
                  Datos del documento
                </VCardTitle>
                <VCardText>
                  <VBtnToggle
                    v-model="docTipo"
                    mandatory
                    rounded="lg"
                    color="indigo"
                    class="mb-4 w-100"
                    density="comfortable"
                  >
                    <VBtn
                      value="contado"
                      class="flex-1-1"
                      prepend-icon="mdi-cash-fast"
                    >
                      Contado
                    </VBtn>
                    <VBtn
                      value="credito"
                      class="flex-1-1"
                      prepend-icon="mdi-credit-card-clock-outline"
                    >
                      Crédito
                    </VBtn>
                  </VBtnToggle>

                  <VRow
                    justify="space-between"
                    mb-1
                  >
                    <VCol
                      cols="12"
                      md="5"
                    >
                      <VMenu
                        v-model="menuFechaFactura"
                        :close-on-content-click="false"
                        location="bottom"
                      >
                        <template #activator="{ props: menuProps }">
                          <VTextField
                            v-bind="menuProps"
                            :model-value="fechaFactura"
                            label="Fecha de factura"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            class="campo-fecha-centrada"
                            prepend-inner-icon="tabler-calendar-stats"
                          />
                        </template>
                        <VDatePicker
                          :model-value="fechaFacturaDate"
                          @update:model-value="onSeleccionarFechaFactura"
                        />
                      </VMenu>
                    </VCol>

                    <VCol
                      cols="12"
                      md="5"
                    >
                      <VMenu
                        v-model="menuFechaVencimiento"
                        :close-on-content-click="false"
                        location="bottom"
                      >
                        <template #activator="{ props: menuProps }">
                          <VTextField
                            v-bind="menuProps"
                            :model-value="fechaVencimiento"
                            label="Fecha de vencimiento"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            class="campo-fecha-centrada"
                            :disabled="docTipo === 'contado'"
                            prepend-inner-icon="tabler-calendar-stats"
                          />
                        </template>
                        <VDatePicker
                          :model-value="fechaVencimientoDate"
                          @update:model-value="onSeleccionarFechaVencimiento"
                        />
                      </VMenu>
                    </VCol>
                  </VRow>

                  <VSelect
                    v-model="PaymentsMethod.id"
                    :items="paymentmethods"
                    item-title="name"
                    item-value="id"
                    label="Método de Pago"
                    variant="outlined"
                    density="comfortable"
                    rounded="lg"
                    prepend-inner-icon="mdi-tag-multiple-outline"
                    class="py-3"
                  />

                  <VTextField
                    v-model.number="descuentoGlobal"
                    label="Descuento adicional sobre la factura"
                    type="number"
                    min="0"
                    step="1000"
                    variant="outlined"
                    density="comfortable"
                    rounded="lg"
                    prepend-inner-icon="mdi-sale-outline"
                    prefix="$"
                    persistent-hint
                    class="py-2"
                  />
                  <VDivider class="my-0" />
                  <div class="d-flex justify-space-between text-h6 font-weight-bold">
                    <span>Total</span>
                    <span class="total-text">{{ formatoMoneda(total) }}</span>
                  </div>
                </VCardText>
                <VCardActions class="px-4 pb-4">
                  <VSpacer />

                  <!-- Botón Cancelar -->
                  <VBtn
                    variant="text"
                    rounded="medium"
                    height="30"
                    :disabled="guardando"
                    @click="limpiarFactura"
                  >
                    Cancelar
                  </VBtn>

                  <!-- Botón Imprimir / PDF -->
                  <VBtn
                    variant="outlined"
                    color="indigo"
                    size="small"
                    rounded="lg"
                    height="30"
                    :disabled="!puedeGuardar || guardando"
                    @click="imprimirFactura"
                  >
                    <VIcon start>
                      tabler-printer
                    </VIcon>
                    Imprimir / PDF
                  </VBtn>

                  <!-- Botón Guardar factura (con estado de carga) -->
                  <VBtn
                    color="indigo"
                    size="small"
                    rounded="lg"
                    height="30"
                    :disabled="!puedeGuardar || guardando"
                    :loading="guardando"
                    @click="guardarFactura"
                  >
                    <template #loader>
                      <span class="custom-loader">
                        <VIcon icon="mdi-cached" />
                      </span>
                    </template>
                    <VIcon start>
                      tabler-brand-databricks
                    </VIcon>
                    {{ guardando ? 'Guardando...' : 'Guardar factura' }}
                  </VBtn>
                </VCardActions>
              </vcard>
            </VCol>

            <VSnackbar
              v-model="mostrarMensaje"
              :color="colorMensaje"
              timeout="2500"
              rounded="lg"
            >
              {{ textoMensaje }}
            </VSnackbar>

            <!-- ===================== DIÁLOGO: Editar línea de la factura ===================== -->
            <VDialog
              v-model="dialogEdicion"
              max-width="480"
              persistent
            >
              <VCard
                v-if="itemEnEdicion"
                class="rounded-xl pa-2"
                elevation="0"
              >
                <VCardTitle class="font-weight-bold d-flex align-center">
                  <VIcon
                    class="mr-2"
                    color="indigo"
                  >
                    mdi-pencil-outline
                  </VIcon>
                  Editar producto
                </VCardTitle>
                <VCardSubtitle>{{ itemEnEdicion.nombre }}</VCardSubtitle>
                <VCardText>
                  <VTextField
                    v-model.number="formEdicion.cantidad"
                    type="number"
                    min="1"
                    :max="stockDisponibleParaEdicion"
                    label="Cantidad"
                    variant="outlined"
                    density="comfortable"
                    rounded="lg"
                    prepend-inner-icon="mdi-counter"
                  />
                  <VTextField
                    v-model.number="formEdicion.descuentoPorcentaje"
                    type="number"
                    min="0"
                    max="100"
                    step="0.5"
                    label="Descuento ítem (%)"
                    variant="outlined"
                    density="comfortable"
                    rounded="lg"
                    suffix="%"
                    prepend-inner-icon="mdi-sale-outline"
                  />

                  <VAlert
                    v-if="formEdicion.cantidad > stockDisponibleParaEdicion"
                    type="warning"
                    variant="tonal"
                    density="comfortable"
                    rounded="lg"
                    class="mt-2"
                  >
                    La cantidad supera el saldo de inventario disponible ({{ stockDisponibleParaEdicion }} {{ itemEnEdicion.unidad }}).
                  </VAlert>
                </VCardText>
                <VCardActions class="px-4 pb-4">
                  <VSpacer />
                  <VBtn
                    variant="text"
                    rounded="lg"
                    @click="cancelarEdicion"
                  >
                    Cancelar
                  </VBtn>
                  <VBtn
                    color="indigo"
                    rounded="lg"
                    :disabled="formEdicion.cantidad <= 0 || formEdicion.cantidad > stockDisponibleParaEdicion"
                    @click="guardarEdicion"
                  >
                    <VIcon start>
                      mdi-check
                    </VIcon>
                    Guardar cambios
                  </VBtn>
                </VCardActions>
              </VCard>
            </VDialog>

            <!-- ===================== DIÁLOGO: Confirmar eliminación ===================== -->
            <VDialog
              v-model="dialogEliminacion"
              max-width="420"
            >
              <VCard
                class="rounded-xl pa-2"
                elevation="0"
              >
                <VCardTitle class="font-weight-bold d-flex align-center">
                  <VIcon
                    class="mr-2"
                    color="red-darken-1"
                  >
                    mdi-alert-circle-outline
                  </VIcon>
                  Eliminar producto
                </VCardTitle>
                <VCardText>
                  ¿Seguro que deseas eliminar <strong>{{ itemAEliminar?.nombre }}</strong> del detalle de la factura? El saldo de inventario será restituido.
                </VCardText>
                <VCardActions class="px-4 pb-4">
                  <VSpacer />
                  <VBtn
                    variant="text"
                    rounded="lg"
                    @click="dialogEliminacion = false"
                  >
                    Cancelar
                  </VBtn>
                  <VBtn
                    color="red-darken-1"
                    rounded="lg"
                    @click="eliminarItemConfirmado"
                  >
                    <VIcon start>
                      mdi-trash-can-outline
                    </VIcon>
                    Eliminar
                  </VBtn>
                </VCardActions>
              </VCard>
            </VDialog>
          </vrow>
        </div>

        <!-- ===================== ÁREA IMPRIMIBLE (solo visible al imprimir / generar PDF) ===================== -->
        <div class="area-imprimible">
          <div class="factura-pdf">
            <div class="d-flex justify-space-between align-start mb-6">
              <div>
                <h2 class="text-h5 font-weight-bold mb-1">
                  Factura de venta
                </h2>
                <p class="text-body-2 mb-0">
                  Fecha de factura: {{ fechaFactura }}
                </p>
                <p class="text-body-2 mb-0">
                  Fecha de vencimiento: {{ fechaVencimiento || 'N/A' }}
                </p>
              </div>
              <VChip
                size="small"
                variant="flat"
                :color="docTipo === 'contado' ? 'teal' : 'indigo'"
                label
              >
                {{ docTipo === 'contado' ? 'Contado' : 'Crédito' }}
              </VChip>
            </div>

            <div class="mb-6">
              <h3 class="text-subtitle-1 font-weight-bold mb-1">
                Cliente
              </h3>
              <p class="mb-0">
                {{ clienteInfo?.nombre || 'N/A' }}
              </p>
              <p class="mb-0">
                NIT / Doc.: {{ clienteInfo?.nit || 'N/A' }}
              </p>
              <p class="mb-0">
                Lista de precios: {{ listaPrecioSeleccionada }}
              </p>
            </div>

            <table class="tabla-pdf">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th class="text-right">
                    Cant.
                  </th>
                  <th class="text-right">
                    Precio unit.
                  </th>
                  <th class="text-right">
                    Desc. (%)
                  </th>
                  <th class="text-right">
                    Subtotal
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in itemsFactura"
                  :key="item.idLinea"
                >
                  <td>{{ item.nombre }}</td>
                  <td class="text-right">
                    {{ item.cantidad }}
                  </td>
                  <td class="text-right">
                    {{ formatoMoneda(item.precioUnitario) }}
                  </td>
                  <td class="text-right">
                    {{ item.descuentoPorcentaje > 0 ? `${item.descuentoPorcentaje}%` : '—' }}
                  </td>
                  <td class="text-right">
                    {{ formatoMoneda(subtotalItem(item)) }}
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="d-flex justify-end mt-6">
              <div style="min-inline-size: 260px;">
                <div class="d-flex justify-space-between text-body-2 mb-1">
                  <span>Subtotal</span>
                  <span>{{ formatoMoneda(subtotalBruto) }}</span>
                </div>
                <div
                  v-if="descuentoItemsTotal > 0"
                  class="d-flex justify-space-between text-body-2 mb-1"
                >
                  <span>Descuento por productos</span>
                  <span>- {{ formatoMoneda(descuentoItemsTotal) }}</span>
                </div>
                <div
                  v-if="descuentoGlobal > 0"
                  class="d-flex justify-space-between text-body-2 mb-1"
                >
                  <span>Descuento adicional</span>
                  <span>- {{ formatoMoneda(descuentoGlobal) }}</span>
                </div>
                <div class="d-flex justify-space-between text-body-2 mb-1">
                  <span>IVA (19%)</span>
                  <span>{{ formatoMoneda(iva) }}</span>
                </div>
                <div class="d-flex justify-space-between text-h6 font-weight-bold mt-2 pt-2 linea-total">
                  <span>Total</span>
                  <span>{{ formatoMoneda(total) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </VContainer>
    </VMain>
  </VApp>
</template>

<style scoped>
.bg-base {
  background: linear-gradient(160deg, #f4f6fb 0%, #eef1f8 100%);
  min-block-size: 100vh;
}

.header-title {
  background: linear-gradient(90deg, #3949ab, #00897b);
  background-clip: text;
  color: transparent;
}

.card-soft {
  border: 1px solid rgba(57, 73, 171, 6%);
  background: #fff;
  box-shadow: 0 4px 20px rgba(57, 73, 171, 8%);
}

.info-cliente {
  background: linear-gradient(135deg, rgba(57, 73, 171, 6%), rgba(0, 137, 123, 6%));
}

.total-text {
  color: #3949ab;
}

.gap-4 {
  gap: 16px;
}

/* ===== Área imprimible: oculta en pantalla, visible solo al imprimir / generar PDF ===== */
.area-imprimible {
  display: none;
}

.factura-pdf {
  padding: 32px;
  color: #1a1a2e;
  font-family: Arial, Helvetica, sans-serif;
}

.tabla-pdf {
  border-collapse: collapse;
  inline-size: 100%;
}

.tabla-pdf th,
.tabla-pdf td {
  border-block-end: 1px solid #ddd;
  font-size: 13px;
  padding-block: 8px;
  padding-inline: 6px;
  text-align: start;
}

.tabla-pdf th {
  border-block-end: 2px solid #3949ab;
  color: #555;
  font-size: 11px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.linea-total {
  border-block-start: 2px solid #3949ab;
}

/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.campo-fecha-centrada :deep(input),
.campo-fecha-centrada :deep(.v-field__input input) {
  text-align: center !important;
}

/* Aplica color gris a las filas pares */
.custom-table tbody tr:nth-child(even) {
  background-color: #f5f5f5 !important; /* Un gris muy claro */
}

/* Opcional: Cambiar el color al pasar el mouse (hover) */
.custom-table tbody tr:hover {
  background-color: #eee !important;
}

/* Cambia el tamaño del texto del input y el label */
/* stylelint-disable-next-line selector-pseudo-class-no-unknown */

/* 1. Controla el texto que ya fue seleccionado (cuando no tiene el foco) */
/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.custom-autocomplete :deep(.v-field__selection),
.custom-autocomplete :deep(.v-field__input) {
  font-size: 13px !important;
}

/* 2. Controla el Label (el título "Producto" o "Cliente") */
/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.custom-autocomplete :deep(.v-label) {
  font-size: 13px !important;
}

/* 3. Controla el Label cuando el campo está lleno pero sin foco (Label flotante) */
/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.custom-autocomplete :deep(.v-label--floating) {
  --v-field-label-scale: 0.9em; /* Ajusta la escala del label cuando flota */

  font-size: 13px !important;
}

/* Aplica color gris a las filas pares */
/* stylelint-disable-next-line no-duplicate-selectors */
.custom-table tbody tr:nth-child(even) {
  background-color: #f5f5f5 !important; /* Un gris muy claro */
}

/* Opcional: Cambiar el color al pasar el mouse (hover) */
/* stylelint-disable-next-line no-duplicate-selectors */
.custom-table tbody tr:hover {
  background-color: #eee !important;
}
</style>

<style>
/* ===== Reglas de impresión (no scoped, para afectar también <body>) ===== */
@media print {
  body * {
    visibility: hidden;
  }

  .area-imprimible,
  .area-imprimible * {
    visibility: visible;
  }

  .area-imprimible {
    position: absolute;
    display: block !important;
    inline-size: 100%;
    inset-block-start: 0;
    inset-inline-start: 0;
  }

  .contenido-pantalla {
    display: none !important;
  }

  /* stylelint-disable-next-line selector-pseudo-class-no-unknown */

  /* stylelint-disable-next-line selector-pseudo-class-no-unknown */
  :deep(.v-table__wrapper table thead tr th) {
    background-color: #3f51b5 !important;
    color: #fff !important;
  }

  /* stylelint-disable-next-line selector-pseudo-class-no-unknown */
  .campo-fecha-centrada :deep(input),
  .campo-fecha-centrada :deep(.v-field__input input) {
    text-align: center !important;
  }
}
</style>
