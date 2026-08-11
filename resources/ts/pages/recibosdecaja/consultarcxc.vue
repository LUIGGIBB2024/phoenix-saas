<script setup lang="ts">
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { computed, ref } from 'vue'
import * as XLSX from 'xlsx'

const isFocused = ref(false)
const hoy = new Date().toISOString().split('T')[0]
const token = localStorage.getItem('auth_token')
const yaBusco = ref(false)
const loading = ref(false)
const dialog = ref(false)
const customers = ref([])
const dctoscxc = ref([])

const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

const nameRecordToDelete = ref('')
const showConfirmDialog = ref(false)
const recordToDelete = ref<SalesInvoice | null>(null)

const tipodeusuario = ref(localStorage.getItem('tipo_de_usuario'))

const formatCurrency = (value: number | string, fractionDigits: number = 2) => {
  const num = Number(value) || 0

  return num.toLocaleString('en-US', {
    minimumFractionDigits: fractionDigits,
    maximumFractionDigits: fractionDigits,
  })
}

interface DocsPayment {
  id: number
  code: string
  name: string
}

const showDialog = ref(false)
const isPasswordVisible = ref(false)

const searchQuery = ref('')
const selectedRows = ref([])

const editedItem = ref<SalesInvoice>(getDefaultItem())
const clienteSeleccionado = ref<Customer | null>(null)
const clienteInfo = ref<Customer | null>(null)
const documentoSeleccionado = ref<DocsPayment | null>(null)
const documentoinfo = ref<DocsPayment | null>(null)

// const desdefecha = ref(hoy)
// const hastafecha = ref(hoy)

const ValidarCrearFacturas = computed(() => {
  const faltaCliente = !clienteSeleccionado.value
  const faltaDocumento = !documentoSeleccionado.value
  const faltaValorCxc = !editedItem.value?.valor_factura
  const faltanumeroFactura = !editedItem.value?.number

  // Validación para cualquier otro tipo de egreso
  return faltaCliente || faltaValorCxc || faltaDocumento || faltanumeroFactura
})

// 🔹 Variables del DataTable
const itemsPerPage = ref(13)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const datafechas = ref({
  desdefecha: hoy,
  hastafecha: hoy,
})

const invoiceData = ref({
  status: '',
  message: '',
  totaldocumentos: 0,
  TotalValor: 0,
  TotalIva: 0,
  TotalRentabilidad: 0,
  customers: [],
  dctoscxc: [],
  listbalances: [],
  page: 1,
  per_page: 13,
})

const invoiceData2 = ref({
  status: '',
  message: '',
  totaldocumentos: 0,
  TotalValor: 0,
  TotalIva: 0,
  TotalRentabilidad: 0,
  customers: [],
  dctoscxc: [],
  page: 1,
  per_page: 13,
})

function getDefaultItem(): SalesInvoice {
  return {
    id: null,
    fecha_factura: hoy,
    fecha_vencimiento: hoy,
    dias_vencimiento: 0,
    prefix: '',
    numero_factura: '',
    supplier: '',
    branch: '',
    proveedor: '',
    document_name: '',
    state: 'Activo',
    valor_factura: 0,
    abonos: 0,
    abonoactual: 0,
    saldo: 0,
  }
}

// 🔹 Abrir confirmación de eliminación
const confirmDelete = (item: any) => {
  console.log('🛑 Confirmar eliminación de la Factura ID:', item.id)
  recordToDelete.value = item
  console.log('Soy Registro Seleccionado: ', recordToDelete.value)
  nameRecordToDelete.value = `${invoiceData.value.listbalances.find(c => c.id === item.id)?.NombreCliente} NroFactura:${invoiceData.value.listbalances.find(c => c.id === item.id)?.numero_factura}`
 || ''
  showConfirmDialog.value = true
}

const getsuppliersinvoice = async () => {
  // console.log('Id Company:', localStorage.getItem('company_id'))
  try {
    // onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
    const { data } = await axios.post('/api/get-customers-invoice', {
      url_token: localStorage.getItem('auth_token'),
      company_id: localStorage.getItem('company_id'),
      page: page.value,
      per_page: itemsPerPage.value,
    },
    {
      headers: { Authorization: `Bearer ${token}` },
    },
    )

    invoiceData2.value = data
    console.log('Respuesta API Customers Invoice:', invoiceData.value)
    customers.value = invoiceData2.value.customers ?? []
    dctoscxc.value = invoiceData2.value.dctoscxc ?? []

    // console.log('Respuesta InvoiceData:', invoiceData.value)
    // ackbar.value = true
  }
  catch (error) {
    console.error(error)
  }
  finally {
    loading.value = false
  }
}

// const facturas = computed(() => invoiceData.value.listbalances ?? [])

const openCreateDialog = () => {
  getsuppliersinvoice()
  dialog.value = true
  getDefaultItem()
}

const facturas = computed(() => {
  console.log('🔥 Evaluando "facturas"...')
  console.log('📦 Estado completo de invoiceData.value:', invoiceData.value)
  console.log('🔑 ¿Existe listbalances?:', invoiceData.value?.listbalances)

  return invoiceData.value?.listbalances ?? []
})

// console.log('Soy Facturas Recibidas:', facturas.value)

const facturasFiltradas = computed(() => {
  // console.log('Soy Facturas Filtradas:', facturas.value)
  if (!searchQuery.value || !facturas.value?.length)
    return facturas.value ?? []
  const q = searchQuery.value.toLowerCase()

  return facturas.value.filter(item =>
    Object.values(item).some(val =>
      String(val ?? '').toLowerCase().includes(q),
    ),
  )
})

// ✅ Totales que reaccionan al filtro
const totalInvoices = computed(() =>
  facturasFiltradas.value.reduce((acc, item) => acc + (Number(item.saldo) || 0), 0),
)

// ── Exportar a Excel ──────────────────────────────────────────
const exportarExcel = () => {
  // console.log('Información de Facturas para Excel:', facturas.value)

  const datos = facturas.value.map(item => ({
    'ID': item.id,
    'Fecha Factura': item.fecha_factura,
    'Fecha Vcmto': item.fecha_vencimiento,
    'Días': item.dias_vencimiento,
    'Nit/Cédula': item.customer,
    'Nombre del Cliente': item.NombreCliente,
    'Nro Factura:': item.numero_factura,
    'Prefijo': item.prefix,
    'Tipo Documento': item.document_name,
    'Valor Factura': Number(item.valor_factura),
    'Total Abonos': Number(item.abonos),
    'Saldo Factura': Number(item.saldo),
  }))

  // 1. Definir el encabezado/título en un arreglo de arreglos (AOA)
  const encabezado = [
    ['REPORTE DE FACTURAS DE CUENTAS POR COBRAR'], // Fila 1 (A1)
    [`Período: ${datafechas.value.desdefecha} al ${datafechas.value.hastafecha}`], // Fila 2 (A2)
    [], // Fila 3 vacía para dar espacio
  ]

  const hojaexcel = XLSX.utils.aoa_to_sheet(encabezado)

  // 2. Usas sheet_add_json para agregar los datos sobre la misma hoja
  XLSX.utils.sheet_add_json(hojaexcel, datos, { origin: 'A4' })

  const libro = XLSX.utils.book_new()

  XLSX.utils.book_append_sheet(libro, hojaexcel, 'Facturas')
  XLSX.writeFile(libro, `Facturas_CxC_${datafechas.value.desdefecha}_${datafechas.value.hastafecha}.xlsx`)
}

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

// ── Exportar a PDF ────────────────────────────────────────────
const exportarPDF = () => {
  const doc = new jsPDF({ orientation: 'landscape' })

  doc.setFontSize(14)
  doc.text('Cuentas por Cobrar:', 14, 15)
  doc.setFontSize(9)
  doc.text(`Período: ${datafechas.value.desdefecha}  al  ${datafechas.value.hastafecha}`, 14, 22)

  const totalValorFactura = computed(() => {
    return facturas.value.reduce((acc, item) => acc + (Number(item.valor_factura) || 0), 0)
  })

  // 2. Total Abonos
  const totalAbonos = computed(() => {
    return facturas.value.reduce((acc, item) => acc + (Number(item.abonos) || 0), 0)
  })

  // 3. Total Saldo
  const totalSaldo = computed(() => {
    return facturas.value.reduce((acc, item) => acc + (Number(item.saldo) || 0), 0)
  })

  autoTable(doc, {
    startY: 28,
    head: [[
      'ID',
      'Fecha Factura',
      'Fecha Vcmto',
      'Días',
      'Nit/Cédula',
      'Nombre del Cliente',
      'Número',
      'Prefijo',
      'Tdo',
      'Vlr Factura',
      'Abonos',
      'Saldo',
    ]],
    body: facturas.value.map(item => [
      item.id,
      item.fecha_factura,
      item.fecha_vencimiento,
      item.dias_vencimiento,
      item.customer,
      item.NombreCliente,
      item.numero_factura,
      item.prefix,
      item.document_name,
      formatCurrency(Number(item.valor_factura), 0),
      formatCurrency(Number(item.abonos), 0),
      formatCurrency(Number(item.saldo), 0),
    ]),

    // Definir la alineación por índice de columna
    columnStyles: {
      9: { halign: 'right' }, // Valor Factura a la derecha
      10: { halign: 'right' }, // Abonos a la derecha
      11: { halign: 'right' }, // Saldo a la derecha
    },
    styles: { fontSize: 7, cellPadding: 2 },
    headStyles: { fillColor: [25, 118, 210], textColor: 255, fontStyle: 'bold' },
    alternateRowStyles: { fillColor: [240, 248, 255] },
    foot: [[
      '',
      '',
      '',
      '',
      '',
      '',
      '',
      '',
      'TOTALES',

      formatCurrency(totalValorFactura.value), // Total Factira
      formatCurrency(totalAbonos.value),
      formatCurrency(totalSaldo.value),
    ]],
    footStyles: { fillColor: [200, 230, 255], fontStyle: 'bold' },
  })

  doc.save(`Facturas_CxC_${datafechas.value.desdefecha}_${datafechas.value.hastafecha}.pdf`)
}

const generarConsulta = async () => {
  console.log('Id Company:', localStorage.getItem('company_id'))
  datafechas.value.desdefecha = '1900-01-01'
  loading.value = true
  try {
    // onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
    const { data } = await axios.post('/api/consult-balances-cxc', {
      url_token: localStorage.getItem('auth_token'),
      company_id: localStorage.getItem('company_id'),
      fechadesde: datafechas.value.desdefecha,
      fechahasta: datafechas.value.hastafecha,
      page: page.value,
      per_page: itemsPerPage.value,
    },
    {
      headers: { Authorization: `Bearer ${token}` },
    },
    )

    invoiceData.value = data
    responseData.value = data
    console.log('Respuesta API Listbalances:', invoiceData.value.listbalances)

    // console.log('Respuesta InvoiceData:', invoiceData.value)
    yaBusco.value = true // Marcar que ya se realizó una búsqueda
    // console.log('Soy Registro..:', invoiceData.value.data.length)
    // if (invoiceData.value.TotalDocumentos === 0 && yaBusco.value)
    //   snackbar.value = true
  }
  catch (error) {
    console.error(error)
  }
  finally {
    loading.value = false
  }
}

const perPage = computed(() => responseData.value.per_page ?? itemsPerPage.value)
const currentPage = computed(() => responseData.value.page ?? page.value)
const totalregistros = computed(() => responseData.value?.totaldocumentos ?? 0)

const deleteRecord = async (item: any) => {
  if (!recordToDelete.value)
    return

  // console.log('🗑️ Eliminando Factura - Item :', item)
  // console.log('🗑️ Eliminando Factura:', recordToDelete.value)

  const company_id = localStorage.getItem('company_id')

  try {
    await $api(`/api/delete-invoice-cxc/${item.id}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
      params: { company_id }, // Enviado como query param (?company_id=...)
    })
    generarConsulta() // Refrescar la lista después de eliminar
    snackbarMessage.value = '✅ Factura eliminada correctamente'
    snackbarColor.value = 'success'
  }
  catch (error) {
    console.error('❌ Error al eliminar la Factura:', error)
    snackbarMessage.value = '❌ Error al eliminar la Factura'
    snackbarColor.value = 'error'
  }
  finally {
    showConfirmDialog.value = false
    recordToDelete.value = null

    showSnackbar.value = false
    nextTick(() => (showSnackbar.value = true))
  }
}

const saveFacturas = async (tipo: number) => {
  console.log('Id Company:', localStorage.getItem('company_id'))
  datafechas.value.desdefecha = '1900-01-01'
  loading.value = true
  try {
    // onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
    const { data } = await axios.post('/api/save-invoice-cxc', {
      url_token: localStorage.getItem('auth_token'),
      company_id: localStorage.getItem('company_id'),
      fechadesde: datafechas.value.desdefecha,
      fechahasta: datafechas.value.hastafecha,
      page: page.value,
      per_page: itemsPerPage.value,
      facturas: editedItem.value,
    },
    {
      headers: { Authorization: `Bearer ${token}` },
    },
    )

    // invoiceData.value = data
    // console.log('Respuesta API Listbalances:', invoiceData.value.listbalances)

    // console.log('Respuesta InvoiceData:', invoiceData.value)
    yaBusco.value = true // Marcar que ya se realizó una búsqueda
    // console.log('Soy Registro..:', invoiceData.value.data.length)
    // if (invoiceData.value.TotalDocumentos === 0 && yaBusco.value)
    snackbarMessage.value = 'Factura Reportada Exitosamente'
    snackbarColor.value = 'success'
    dialog.value = false
  }
  catch (error) {
    console.error(error)
  }
  finally {
    loading.value = false
    showSnackbar.value = true
  }
}

function onClienteSeleccionado(cliente: Customer | null): void {
  console.log('Entre Aquí Seleccionando Cliente :', cliente)
  clienteInfo.value = cliente || null
  if (cliente) {
    console.log('Entre Aquí Seleccionando Cliente :', `${cliente.nit} ${cliente.name}`)
    editedItem.value.nit = cliente.nit
    editedItem.value.branch = cliente?.branch
    editedItem.value.customer_name = cliente?.name
  }

  // ... modificas más propiedades de editedItem
}

function onDocumentoSeleccionado(documento: DocsPayments | null): void {
  documentoinfo.value = documento || null
  if (documento)
    editedItem.value.document_name = documento.name
}

const headers = [
  { title: 'id', key: 'id', sortable: true, width: '10%' },
  { title: 'Nit/Cédula', key: 'customer', sortable: true, width: '12%' },
  { title: 'Suc', key: 'branch', sortable: true, width: '4%' },
  { title: 'Nombre del Cliente', key: 'NombreCliente', sortable: true, width: '35%' },
  { title: 'Fecha Factura', key: 'fecha_factura', sortable: true, width: '5%' },
  { title: 'Fecha Vcmto', key: 'fecha_vencimiento', sortable: true, width: '5%' },
  { title: 'Días', key: 'dias_vencimiento', sortable: true, align: 'center' },
  { title: 'Número de Factura', key: 'numero_factura', sortable: true, width: '5px' },
  { title: 'Prefijo', key: 'prefix', sortable: true },
  { title: 'Tipo Documento', key: 'document_name', sortable: true, width: '10%' },
  { title: 'Valor Factura', key: 'valor_factura', sortable: true, align: 'center' },
  { title: 'Abonos', key: 'abonos', sortable: true, align: 'end' },
  { title: 'Saldo', key: 'saldo', sortable: true, align: 'end' },
  { title: 'Acción', key: 'actions', sortable: true, align: 'center' },
]

const cellProps = () => ({
  style: {
    fontSize: '0.78rem',
    color: '#0a0a0a',
    fontFamily: 'Roboto, sans-serif',
    fontWeight: '400',
  },
})

const headerProps = () => ({
  style: {
    fontSize: '0.78rem',
    color: '#ffffff',
    fontFamily: 'Roboto, sans-serif',
    fontWeight: '600',
  },
})

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
const valueField = useNumericField(editedItem, 'valor_factura')
</script>

<template>
  <!-- <VCard class="mb-2" style="height: 13vh !important;"">  -->
  <VCard class="mb-2 py-3 px-4">
    <VRow class="align-center">
      <VCol
        cols="12"
        md="3"
        class="d-flex align-center flex-column"
      >
        <h4 class="text-primary mb-2">
          Consultar Cuentas por Cobrar
        </h4>
        <!-- Campo de búsqueda -->
        <!-- <VCardText class="d-flex align-center flex-wrap gap-4 pb-0"></VCardText> -->
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
        md="2"
      >
        <AppDateTimePicker
          v-model="datafechas.hastafecha"
          label="Fecha de Corte :"
          placeholder="Seleccionar Fecha"
          class="text-center-input"
          prepend-inner-icon="tabler-calendar"
          :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
        />
      </VCol>
      <VCol
        cols="12"
        md="2"
      />

      <VCol
        cols="12"
        md="2"
        class="d-flex align-center justify-start mt-md-5 mt-2"
      >
        <VBtn
          rounded="pill"
          color="primary"
          variant="flat"
          block
          @click="generarConsulta"
        >
          Generar Consulta
        </VBtn>
      </VCol>

      <VCol
        cols="12"
        md="3"
        class="d-flex align-center justify-start mt-md-5 mt-2 gap-2"
      >
        <VBtn
          class="boton-export"
          rounded="pill"
          color="success"
          variant="flat"
          :disabled="!facturas?.length"
          @click="exportarExcel"
        >
          <VIcon
            start
            icon="tabler-file-spreadsheet"
            witdh="100"
          />
          Excel
        </VBtn>

        <VBtn
          class="boton-export"
          rounded="pill"
          color="error"
          variant="flat"
          :disabled="!facturas?.length"
          @click="exportarPDF"
        >
          <VIcon
            start
            icon="tabler-file-type-pdf"
            witdh="100"
          />
          PDF
        </VBtn>

        <VBtn
          class="boton-export"
          rounded="pill"
          color="secondary"
          variant="flat"
          @click="openCreateDialog"
        >
          <VIcon
            start
            icon="tabler-copy-plus"
            witdh="100"
          />
          FACT
        </VBtn>
      </VCol>
    </VRow>
  </VCard>

  <section v-if="yaBusco && facturas && facturas.length">
    <VCard>
      <div class="table-responsive">
        <VDataTable
          v-model:model-value="selectedRows"
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :headers="headers"
          :items="facturasFiltradas"
          item-value="id"
          :search="searchQuery"
          :cell-props="cellProps"
          :header-props="headerProps"
          class="text-body-2 tabla-facturas custom-table"
          fixed-header
          density="compact"
          striped="even"
        >
          <template #header.id>
            <div class="th-center">
              #Id
            </div>
          </template>
          <template #item.id="{ item }">
            <div class="td-right text-column">
              {{ item.id }}
            </div>
          </template>

          <template #item.supplier="{ item }">
            <div class="td-left text-column">
              {{ item.customer }}
            </div>
          </template>

          <template #item.branch="{ item }">
            <div class="td-left text-column">
              {{ item.branch }}
            </div>
          </template>

          <template #item.proveedor="{ item }">
            <div class="td-left text-column">
              {{ item.proveedor }}
            </div>
          </template>

          <template #header.fecha_factura>
            <div class="th-center text-center">
              Fecha<br>Factura
            </div>
          </template>
          <template #item.fecha_factura="{ item }">
            <div class="td-center text-column text-no-wrap text-center">
              {{ item.fecha_factura }}
            </div>
          </template>

          <template #header.fecha_vencimiento>
            <div class="th-center">
              Fecha<br>Vcmto
            </div>
          </template>
          <template #item.fecha_vencimiento="{ item }">
            <div class="td-center text-column text-no-wrap text-center">
              {{ item.fecha_vencimiento }}
            </div>
          </template>

          <template #item.dias_vencimiento="{ item }">
            <div class="td-center text-column text-no-wrap text-center">
              {{ item.dias_vencimiento }}
            </div>
          </template>

          <template #header.numero_factura>
            <div class="th-center">
              Número<br>Factura
            </div>
          </template>
          <template #item.numero_factura="{ item }">
            <div class="td-end text-column text-end">
              {{ item.numero_factura }}
            </div>
          </template>

          <template #header.prefix>
            <div class="th-center">
              Prefijo
            </div>
          </template>
          <template #item.prefix="{ item }">
            <div class="td-left text-column">
              {{ item.prefix }}
            </div>
          </template>

          <template #header.document_name>
            <div class="td-center">
              Tdo
            </div>
          </template>
          <template #item.document_name="{ item }">
            <div class="td-center text-column text-no-wrap">
              {{ item.document_name }}
            </div>
          </template>

          <template #header.valor_factura>
            <div class="td-center">
              Valor<br>Factura
            </div>
          </template>
          <template #item.valor_factura="{ item }">
            <div class="td-right text-column text-end">
              {{ formatCurrency(Number(item.valor_factura)) }}
            </div>
          </template>

          <template #header.abonos>
            <div class="td-center">
              Abonos
            </div>
          </template>
          <template #item.abonos="{ item }">
            <div class="td-right text-column">
              {{ formatCurrency(Number(item.abonos)) }}
            </div>
          </template>

          <template #header.saldo>
            <div class="td-center">
              Saldo
            </div>
          </template>
          <template #item.saldo="{ item }">
            <div class="td-right text-column">
              {{ formatCurrency(Number(item.saldo)) }}
            </div>
          </template>

          <template #item.actions="{ item }">
            <IconBtn
              :disabled="tipodeusuario === 'Operador'"
              @click="confirmDelete(item)"
            >
              <VIcon
                icon="tabler-trash"
                color="primary"
                size="18"
              />
            </IconBtn>
          </template>

          <!-- Slot Bottom -->
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
                  <strong>{{ Math.min(currentPage * perPage, totalregistros) }}</strong>
                  de <strong>{{ totalregistros }}</strong> registros
                </div>
              </VCol>
              <VCol
                cols="12"
                md="4"
                class="d-flex justify-center pagination-wrapper"
              >
                <VPagination
                  v-model="page"
                  :length="Math.ceil(totalregistros / perPage)"
                  rounded="circle"
                  size="large"
                  :total-visible="5"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <div class="text-caption text-medium-emphasis ps-4 text-end">
                  Total CxC $:
                  <strong class="text-primary">{{ formatCurrency(totalInvoices) }}</strong>
                </div>
                <!--
                  <div class="text-caption text-medium-emphasis ps-4 text-end">
                  Total Iva $:
                  <strong class="text-error">{{ formatCurrency(totalIva) }}</strong>
                  </div>
                  <div class="text-caption text-medium-emphasis ps-4 text-end">
                  Total Rentabilidad $:
                  <strong class="text-success">{{ formatCurrency(totalRentabilidad) }}</strong>
                  </div>
                -->
              </VCol>
            </VRow>
          </template>
        </VDataTable>
      </div>
    </VCard>
  </section>

  <section v-else-if="yaBusco && (!facturas || !facturas.length)">
    <VCard>
      <VCardTitle class="pa-4">
        No se encontraron registros para el periodo seleccionado
      </VCardTitle>
    </VCard>
  </section>

  <VDialog
    v-if="dialog"
    v-model="dialog"
    max-width="1100px"
    persistent
  >
    <VCard class="mt-0">
      <VCardTitle class="modal-title d-flex align-center text-h6 text-white bg-primary pa-4">
        <VIcon
          icon="tabler-brand-cashapp"
          size="28"
          color="white"
          class="me-3"
        />
        Reporte de Factura de Cuentas por Cobrar
        <!--
          <span
          class="text-h6 font-weight-bold ml-2"
          style="color: #f7fb2d !important;"
          >
          Texto de Prueba
          </span>
        -->
      </VCardTitle>

      <VCardText class="py-0">
        <VContainer class="mt-0">
          <VForm
            ref="form"
            v-model="valid"
            lazy-validation
          >
            <VRow
              dense
              align="center"
              class="g-2"
            >
              <!-- Nit/Cédula -->
              <VCol
                cols="12"
                md="6"
                class="py-0"
              >
                <VAutocomplete
                  v-model="clienteSeleccionado"
                  :items="customers"
                  item-title="name"
                  item-value="id"
                  label="Nombre del Cliente"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  return-object
                  required
                  class="custom-autocomplete mt-3 text_size bg-yellow-light"
                  :menu-props="{ contentClass: 'custom-autocomplete-menu' }"
                  @update:model-value="onClienteSeleccionado"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-user-circle"
                      color="primary"
                      size="18"
                      class="me-1"
                    />
                  </template>
                </VAutocomplete>
              </VCol>
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppTextField
                  v-model="editedItem.nit"
                  label="Nit/Cédula"
                  class="mb-2 text_size"
                  placeholder="Nit/Cédula"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  readonly
                  @update:model-value="val => editedItem.nit = val.replace(/\D/g, '')"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-id"
                      color="primary"
                      size="18"
                      class="me-1"
                    />
                  </template>
                </AppTextField>
              </VCol>

              <!-- DV -->
              <!-- Sucursal (VSelect) -->
              <VCol
                cols="12"
                md="1"
                class="py-0"
              >
                <AppSelect
                  v-model="editedItem.branch"
                  :items="['01', '02', '03']"
                  label="Sucursal"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  readonly
                  class="mb-2 text_size"
                />
              </VCol>
              <!--
                <VCol
                cols="12"
                md="2"
                class="py-0"
                >
                <AppDateTimePicker
                v-model="editedItem.fecha_factura"
                label="Fecha de Factura :"
                placeholder="Seleccionar Fecha"
                class="text-center-input mb-2"
                variant="outlined"
                prepend-inner-icon="tabler-calendar"
                :config="{ locale: Spanish, static: false, dateFormat: 'Y-m-d' }"
                />
                </VCol>
              -->
            </VRow>
            <VRow
              dense
              align="center"
              class="g-2"
            >
              <!-- Nit/Cédula -->
              <VCol
                cols="12"
                md="6"
                class="py-0"
              >
                <VAutocomplete
                  v-model="documentoSeleccionado"
                  :items="dctoscxc"
                  item-title="name"
                  item-value="document_name"
                  label="Tipo de Documento"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  return-object
                  required
                  class="custom-autocomplete mt-5 text_size bg-yellow-light"
                  :menu-props="{ contentClass: 'custom-autocomplete-menu' }"
                  @update:model-value="onDocumentoSeleccionado"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-checklist"
                      color="primary"
                      size="18"
                      class="me-1"
                    />
                  </template>
                </VAutocomplete>
              </VCol>
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppDateTimePicker
                  v-model="editedItem.fecha_factura"
                  label="Fecha de Factura :"
                  placeholder="Seleccionar Fecha"
                  class="text-center-input mb-2 text_size"
                  variant="outlined"
                  prepend-inner-icon="tabler-calendar"
                  :config="{ locale: Spanish, static: false, dateFormat: 'Y-m-d' }"
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppDateTimePicker
                  v-model="editedItem.fecha_vencimiento"
                  label="Fecha de Vencimiento :"
                  placeholder="Seleccionar Fecha"
                  class="text-center mb-2 text_size"
                  variant="outlined"
                  prepend-inner-icon="tabler-calendar"
                  :config="{ locale: Spanish, static: false, dateFormat: 'Y-m-d' }"
                />
              </VCol>
            </VRow>

            <VDivider class="mt-4" />
            <VRow class="mt-2 g-2">
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="editedItem.number"
                  label="Número de Factura"
                  required
                  class="mb-2 text_size aligned-field"
                  placeholder="Número de Factura"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => editedItem.number = val.replace(/\D/g, '')"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-file-text"
                      color="primary"
                      size="16"
                      class="me-0"
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
                  v-model="editedItem.prefix"
                  label="Prefijo"
                  class="mb-2 text_size aligned-field"
                  placeholder="Prefijo"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @update:model-value="val => editedItem.prefix = val.toUpperCase()"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-file-text"
                      color="primary"
                      size="16"
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
                  v-model="valueField.formattedValue.value"
                  label="Valor de la Factura:"
                  class="mb-2 text_size"
                  placeholder="Valor de la Factura"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  @keypress="valueField.onlyNumbersAndDot"
                  @focus="valueField.isFocused.value = true"
                  @blur="valueField.isFocused.value = false"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-report-money"
                      class="py-0"
                      size="16"
                    />
                  </template>
                </apptextfield>
              </VCol>
              <VCol
                cols="12"
                md="3"
                class="py-0"
              >
                <AppSelect
                  v-model="editedItem.state"
                  :items="['Activo', 'Eliminado', 'Pendiente']"
                  label="Estado"
                  item-title="name"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  color="primary"
                  hide-details
                  class="mb-2 aligned-field custom-font-size text_size"
                  readonly
                  v-bind="$attrs"
                />
              </VCol>
            </VRow>
          </VForm>
        </VContainer>
      </VCardText>
      <VDivider class="py-2 w-100" />
      <VCardActions>
        <!--
          <VBtn
          color="blue-darken-1"
          variant="text"
          @click="list"
          >
          Listar
          </VBtn>
        -->
        <VBtn
          width="120"
          min-width="0"
          color="error"
          variant="flat"
          @click="dialog = false"
        >
          Cancelar
        </VBtn>
        <VBtn
          width="120"
          min-width="0"
          color="success"
          variant="flat"
          :disabled="ValidarCrearFacturas"
          @click="saveFacturas(0)"
        >
          Guardar
        </VBtn>

        <!-- @click="tipoDeEgreso === 'Pagos de Facturas' ? abrirReportarPagos() : abrirReportarOtrosPagos()" -->

        <!--
          <VBtn
          color="blue-darken-1"
          variant="text"
          @click="save"
          >
          Guardar
          </VBtn>
        -->
      </VCardActions>
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
        ¿Está seguro que desea eliminar esta Factura ?<br>
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
          :disabled="tipodeusuario === 'Operador'"
          @click="deleteRecord(recordToDelete)"
        >
          Eliminar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

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
</template>

<style lang="scss">
.pagination-wrapper {
  .v-pagination__first,
  .v-pagination__item,
  .v-pagination__next,
  .v-pagination__prev,
  .v-pagination__last {
    .v-btn {
      background-color: rgb(253, 134, 227) !important;

      .v-icon {
        color: rgb(250, 253, 245) !important;
      }
    }
  }
}

.text-column {
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  font-size: 0.85em;
  line-height: 1 !important;
  margin-block-start: 1 !important;
}

@media (max-width: 1400px) {
  :deep(.v-data-table) {
    font-size: 0.85em !important;
  }
}

/* Cambiar el fondo gris de las filas pares (intercaladas) */
.tabla-facturas tbody tr:nth-child(even) {
  background-color: #f0f0f0 !important; /* Ajusta el tono de gris */
}

/* O si quieres que TODAS las filas tengan fondo gris */
.tabla-facturas tbody tr {
  background-color: #fff !important;
}

.v-data-table__thead th {
  background-color: rgb(247, 58, 206) !important;
  color: white !important;
}

thead th {
  background-color: rgb(247, 58, 206) !important;
  color: white !important;
}

$bg-header: #1e293b;
$text-header: #fff;

.v-table th {
  color: #fff !important;

  /* 1. Primera letra de cada palabra en mayúscula */
  text-transform: capitalize !important;

  /* 2. Centrar el texto junto con los íconos de ordenamiento de Vuetify */
  :deep(.v-data-table-header__content) {
    justify-content: center !important;
  }
}

// .tabla-facturas {
//   :deep(.v-data-table-rows) {
//     tr td {
//       block-size: 25px !important; /* Altura personalizada de cada fila */
//     }
//   }
// }

.custom-autocomplete-menu {
  .v-list-item-title {
    font-size: 0.78rem !important; /* Tamaño del texto de cada opción */
  }

  .v-list-item {
    background-color: #e1fce1 !important; /* Cambia este color por el que gustes */
    color: #333 !important;           /* Color del texto */

    /* 2. Fondo al pasar el cursor por encima (Hover) */
    &:hover {
      background-color: #e9ecef !important;
    }
  }

  /* 3. Fondo de la opción que está actualmente seleccionada */
  .v-list-item--active {
    background-color: #e2e8f0 !important;
    font-weight: bold;
  }
}

.custom-autocomplete .v-field__input,
.custom-autocomplete .v-field input,
.custom-autocomplete .v-select__selection,
.custom-autocomplete .v-select__selection-text {
  font-size: 0.78rem !important;
}

.text_size {
  .v-field__input,
  input,
  input::placeholder,
  .v-label {
    font-size: 0.78rem !important;
  }
}

.aligned-field {
  .v-field__prepend-inner {
    margin-inline-end: 0 !important;
    padding-inline-end: 2px !important;
  }

  .v-field__input {
    padding-inline-start: 4px !important;
  }
}
</style>
