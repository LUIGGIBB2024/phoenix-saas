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

const showDetailsDialog = ref(false)

const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')
const details = ref([])
const detailsothr = ref([])
const DetailsDocument = ref([])

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

// const desdefecha = ref(hoy)

const itemsPerPage = ref(13)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const datafechas = ref({
  desdefecha: hoy,
  hastafecha: hoy,
})

const recordData = ref({
  status: '',
  message: '',
  totaldocumentos: 0,
  TotalValor: 0,
  TotalIva: 0,
  TotalRentabilidad: 0,
  saldoactual: 0,
  customers: [],
  dctoscxc: [],
  listbalances: [],
  movements: [],
  accumulated: [],
  documents: [],
  details: [],
  page: 1,
  per_page: 13,
})

const recordData2 = ref({
  status: '',
  message: '',
  totaldocumentos: 0,
  TotalValor: 0,
  TotalIva: 0,
  TotalRentabilidad: 0,
  saldoactual: 0,
  customers: [],
  dctoscxc: [],
  page: 1,
  per_page: 13,
})

const documentos = computed(() => {
  return recordData.value?.documents ?? []
})

// ✅ Computed de items filtrados
const documentosFiltrados = computed(() => {
  if (!searchQuery.value || !documentos.value?.length)
    return documentos.value ?? []
  const q = searchQuery.value.toLowerCase()

  return documentos.value.filter(item =>
    Object.values(item).some(val =>
      String(val ?? '').toLowerCase().includes(q),
    ),
  )
})

const totalDocuments = computed(() =>
  documentosFiltrados.value.reduce((acc, item) => acc + (Number(item.total_purchases) || 0), 0),
)

// ── Exportar a Excel ──────────────────────────────────────────
const exportarExcel = () => {
  // console.log('Información de Facturas para Excel:', facturas.value)

  const datos = documentos.value.map(item => ({
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

  // datafechas.value.desdefecha = '1900-01-01'
  loading.value = true
  try {
    // onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
    const { data } = await axios.post('/api/consult-documents', {
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

    recordData.value = data
    responseData.value = data

    console.log('Respuesta API Mvtos:', recordData.value.documents)

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

const Headers_Details = [
  { title: 'ID', key: 'id', width: 60, sortable: false, align: 'start' },
  { title: 'Código', key: 'code', width: 100, sortable: false },
  { title: 'Descripción del Producto', key: 'name', sortable: false, width: '35%' },
  { title: 'Bd', key: 'store', width: 90, sortable: false },
  { title: 'cpto', key: 'concept_inv', width: 50, sortable: false },
  { title: 'Cant.', key: 'amount', width: 80, align: 'end', sortable: false },
  { title: 'Desc (%)', key: 'discount1', width: 100, align: 'end', sortable: false },
  { title: 'IVA', key: 'vat', width: 80, align: 'end', sortable: false },
  { title: 'Costo Unit.', key: 'unit_cost', width: 120, align: 'end', sortable: false },
  { title: 'ValParcial', key: 'subtotal', width: 130, align: 'end', sortable: false },
]

const headers = [
  { title: '#', key: 'id', width: 40 },
  { title: 'Fecha', key: 'report_date', sortable: true, width: 80 }, // Necesita ~85px para "YYYY-MM-DD"
  { title: 'Consecut.', key: 'number', sortable: true, width: 60, align: 'end' },
  { title: '#Factura', key: 'purchase_invoice', sortable: true, width: 60, align: 'end' },

  // Sin width para que absorban el espacio dinámico disponible
  { title: 'Descripción', key: 'concept_name', sortable: true },
  {
    title: 'Nit/Cédula',
    key: 'nit',
    sortable: true,
    width: 85,
    cellProps: { class: 'd-none d-lg-table-cell' },
    headerProps: { class: 'd-none d-lg-table-cell' },
  },
  { title: 'Nombre del Tercero', key: 'name', sortable: true, width: 180 },

  // Corregido 'aling' -> 'align'
  { title: 'SubTotal', key: 'subtotal', sortable: true, width: 65, align: 'end' },
  { title: 'ValorIva', key: 'vatvalue', sortable: true, width: 50, align: 'end' },
  { title: 'Desctos', key: 'descuentos', sortable: true, width: 50, align: 'end' },
  { title: 'Retenc.', key: 'retenciones', sortable: true, width: 50, align: 'end' },
  { title: 'Total', key: 'total_purchases', sortable: true, width: 65, align: 'end' },

  { title: 'Estado', key: 'state', sortable: true, width: 40 },
  {
    title: 'Acciones',
    key: 'actions',
    sortable: false,
    width: 150,
    align: 'center',
    cellProps: {
      style: 'padding: 0 0px !important;',
    },
  },
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
const selectedDocument = ref('')
const NombreDelProveedor = ref('')

const openreccaja = (item: CxCPayment | null = null) => {
  if (item)
    Object.assign(editedItem, item)
  else
    Object.assign(editedItem, defaultItem)

  dialog.value = true
}

const showDetailDocumentDialog = async (item: any) => {
  selectedDocument.value = item
  NombreDelProveedor.value = `${item.name} - Nro:${item.number} - ${item.concept_name}`
  console.log('Id Company:', localStorage.getItem('company_id'))
  showDetailsDialog.value = true
  try {
    // onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
    const { data } = await axios.post('/api/documents-details', {
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

    // invoiceDetData.value = data
    console.log('Respuesta DetDocument :', data)
    DetailsDocument.value = data.details

    // console.log('Soy ID Envío:', selectedInvoice.value.id)
    // console.log('Respuesta InvoiceDetData:', invoiceDetData.value)
    // yaBusco.value = true // Marcar que ya se realizó una búsqueda
    // console.log('Soy Registro..(200):', invoiceDetData.value.data.length)
    // if (invoiceDetData.value.TotalRegistros === 0 && yaBusco.value)
    //   snackbar.value = true
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

const closedet = () => {
  showDetailsDialog.value = false
  form.value?.resetValidation()
}
</script>

<template>
  <!-- Card de Filtros (Encabezado) -->
  <VCard class="mb-2 py-3 px-4">
    <VRow class="align-center">
      <VCol
        cols="12"
        md="3"
        class="d-flex align-center flex-column"
      >
        <h4 class="text-primary mb-2">
          Movimientos de Inventarios
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
        md="2"
      >
        <AppDateTimePicker
          v-model="datafechas.desdefecha"
          label="Desde Fecha :"
          placeholder="Seleccionar Fecha"
          class="date-picker-centered"
          prepend-inner-icon="tabler-calendar"
          input-class="text-center"
          :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppDateTimePicker
          v-model="datafechas.hastafecha"
          label="Hasta Fecha:"
          placeholder="Seleccionar Fecha"
          class="date-picker-centered"
          prepend-inner-icon="tabler-calendar"
          input-class="text-center"
          :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
        />
      </VCol>

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

      <!--
        <VCol
        cols="12"
        md="3"
        class="d-flex align-center justify-start mt-md-5 mt-2 gap-2"
        >
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
        width="100"
        />
        PDF
        </VBtn>
        </VCol>
      -->
    </VRow>
  </VCard>

  <!-- Contenedor Principal en 2 Columnas -->
  <VRow v-if="documentos && documentos.length">
    <!-- Columna Izquierda: VDataTable (Ocupa 9 columnas en pantallas md/desktop) -->
    <VCol
      cols="12"
      md="12"
    >
      <VCard>
        <div class="table-responsive">
          <VDataTable
            v-model:model-value="selectedRows"
            v-model:items-per-page="itemsPerPage"
            v-model:page="page"
            :headers="headers"
            :items="documentos"
            item-value="id"
            :search="searchQuery"
            :cell-props="cellProps"
            :header-props="headerProps"
            class="text-body-2 tabla-facturas custom-table"
            fixed-header
            density="compact"
            striped="even"
          >
            <template #item.report_date="{ item }">
              <div class="td-left text-column">
                {{ item.report_date }}
              </div>
            </template>

            <template #item.concept_name="{ item }">
              <div class="td-left text-column">
                {{ item.concept_name }}
              </div>
            </template>

            <template #item.number="{ item }">
              <div class="td-left text-column">
                {{ item.number }}
              </div>
            </template>

            <template #item.purchase_invoice="{ item }">
              <div class="td-left text-column">
                {{ item.purchase_invoice }}
              </div>
            </template>

            <template #item.nit="{ item }">
              <div class="td-left text-column">
                {{ item.nit }}
              </div>
            </template>

            <template #item.name="{ item }">
              <div class="td-left text-column">
                {{ item.name }}
              </div>
            </template>

            <template #item.subtotal="{ item }">
              <div class="td-left text-column">
                {{ formatCurrency(item.subtotal) }}
              </div>
            </template>

            <template #item.vatvalue="{ item }">
              <div class="td-left text-column">
                {{ formatCurrency(item.vatvalue) }}
              </div>
            </template>

            <template #item.descuentos="{ item }">
              <div class="td-left text-column">
                {{ formatCurrency(item.descuentos) }}
              </div>
            </template>

            <template #item.retenciones="{ item }">
              <div class="td-left text-column">
                {{ formatCurrency(item.retenciones) }}
              </div>
            </template>

            <template #item.state="{ item }">
              <div class="td-left text-column">
                {{ item.state }}
              </div>
            </template>

            <template #item.total_purchases="{ item }">
              <div class="td-right text-column text-end">
                {{ formatCurrency(Number(item.total_purchases)) }}
              </div>
            </template>

            <template #item.actions="{ item }">
              <IconBtn
                density="compact"
                class="ma-0"
                @click="showDetailDocumentDialog(item)"
              >
                <VIcon
                  icon="tabler-list-check"
                  :color="item.state !== 'Activo' ? 'error' : 'success'"
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
                    Total Documentos $:
                    <strong class="text-primary">{{ formatCurrency(totalDocuments) }}</strong>
                  </div>
                </VCol>
              </VRow>
            </template>
          </VDataTable>
        </div>
      </VCard>
    </VCol>
  </VRow>

  <!-- Mensaje si no hay registros -->
  <VRow v-else>
    <VCol cols="12">
      <VCard>
        <VCardTitle class="pa-4">
          No se encontraron registros para el periodo seleccionado
        </VCardTitle>
      </VCard>
    </VCol>
  </VRow>

  <!-- Notification Snackbar -->
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

  <VDialog
    max-width="1200px"
    :model-value="showDetailsDialog"
    persistent
    @update:model-value="emit('update:dialogotrop', $event)"
  >
    <VCard
      class="rounded-xs mb-1 border-accent d-flex flex-column"
      elevation="3"
      border="2"
    >
      <VCardTitle class="modal-title d-flex align-center text-h6 bg-success pa-4">
        <VIcon
          icon="tabler-list-details"
          size="28"
          color="white"
          class="me-3"
        />
        Detalle del Documento -- {{ NombreDelProveedor }}
        <span
          class="text-h6 font-weight-bold ml-2"
          style="color: #f7fb2d !important;"
        />
      </VCardTitle>
      <section>
        <VCard id="grid-list">
          <VDivider />
          <VDataTable
            :headers="Headers_Details"
            :items=" DetailsDocument"
            item-value="idLinea"
            density="compact"
            :items-per-page="-1"
            hide-default-footer
            class="products-gridc border rounded"
            :height="$vuetify.display.height < 800 ? 370 : 470"
            striped="even"
            fixed-header
          >
            <template #item.id="{ item }">
              <div class="cell-wrap text-column">
                {{ item.id }}
              </div>
            </template>

            <template #item.code="{ item }">
              <div class="cell-wrap text-column">
                {{ item.code }}
              </div>
            </template>

            <template #item.name="{ item }">
              <div class="cell-wrap text-column">
                {{ item.name }}
              </div>
            </template>

            <template #item.store="{ item }">
              <div class="cell-wrap text-column">
                {{ item.store }}
              </div>
            </template>

            <template #item.concept_inv="{ item }">
              <div class="cell-wrap text-column">
                {{ item.concept_inv }}
              </div>
            </template>

            <template #item.amount="{ item }">
              <div class="cell-wrap text-column">
                {{ formatCurrency(item.amount, 2) }}
              </div>
            </template>

            <template #item.discount1="{ item }">
              <div class="cell-wrap text-column">
                {{ formatCurrency(item.discount1, 2) }}
              </div>
            </template>

            <template #item.vat="{ item }">
              <div class="cell-wrap text-column">
                {{ formatCurrency(item.vat, 0) }}
              </div>
            </template>

            <template #item.unit_cost="{ item }">
              <div class="cell-wrap text-column">
                {{ formatCurrency(item.unit_cost, 0) }}
              </div>
            </template>

            <template #item.subtotal="{ item }">
              <div class="cell-wrap text-column">
                {{ formatCurrency(item.subtotal, 0) }}
              </div>
            </template>
          </VDataTable>
        </VCard>
      </section>
      <VCardActions>
        <VSpacer />

        <VBtn
          width="100"
          min-width="0"
          color="error"
          variant="flat"
          class="mt-3"
          @click="closedet"
        >
          Cancelar
        </VBtn>
      </VCardActions>
    </vcard>
  </vdialog>

  <ConsultarRecCajaDialog
    ref="consultarRecibosDialog"
    v-model:dialogdetpayment="showDetailsPayment"
    :details="details"
    :titledetails="NombreDelCliente"
  />

  <ConsultarRecCajaOtrosDialog
    ref="consultarRecibosDialog"
    v-model:dialogotherpayment="showDetailsOtherPayment"
    :detailsothr="detailsothr"
    :titledetails="NombreDelCliente"
  />
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

.text-micro {
  font-size: 0.65rem !important; /* Aproximadamente 10.4px */
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
