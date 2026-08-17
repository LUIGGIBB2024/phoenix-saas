<script setup lang="ts">
import axios from 'axios'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { computed, ref } from 'vue'
import Apexchart from 'vue3-apexcharts'
import * as XLSX from 'xlsx'

/// ///////////// Procesar Información de Gráfica
import { onMounted } from 'vue'

const isFocused = ref(false)
const hoy = new Date().toISOString().split('T')[0]
const token = localStorage.getItem('auth_token')
const yaBusco = ref(false)
const loading = ref(false)
const dialog = ref(false)

const showDetailsPayment = ref(false)
const showDetailsOtherPayment = ref(false)

const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')
const details = ref([])
const detailsothr = ref([])

const model = defineModel<number>({ default: new Date().getFullYear() })
const currentYear = new Date().getFullYear()
const years = Array.from({ length: 40 }, (_, i) => (currentYear + 2) - i)

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
  data: [],
  page: 1,
  per_page: 13,
})

const documentos = computed(() => {
  return recordData.value?.data ?? []
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
  documentosFiltrados.value.reduce((acc, item) => acc + (Number(item.value_cxc) || 0), 0),
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

const perPage = computed(() => responseData.value.per_page ?? itemsPerPage.value)
const currentPage = computed(() => responseData.value.page ?? page.value)
const totalregistros = computed(() => responseData.value?.totaldocumentos ?? 0)

const proccessYear = computed(() => model.value)

const headers = [
  { title: '#Id', key: 'month_number', width: '10%' },
  { title: 'Mes de Proceso', key: 'month_name', sortable: true, width: '10%' }, // Espacio justo para "AAAA-MM-DD"
  { title: 'Ventas', key: 'total', sortable: true, width: '10%', align: 'end' },
  { title: 'Costo de Venta', key: 'cost_of_sale', sortable: true, width: '10%', align: 'center' },
  { title: 'Diferencia Absoluta', key: 'absolute_diference', sortable: true, width: '10%', align: 'center' },
  { title: 'Porcentaje', key: 'percentage', sortable: true, width: '10%', align: 'end' },
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

const chartKey = ref(0)
const series = ref([])

const chartOptions = ref({
  chart: {
    type: 'line',
    stacked: false,
    toolbar: { show: true },
  },
  stroke: {
    width: [0, 0, 3], // 0 para Ventas (barra), 0 para Costos (barra), 3 para Margen (línea)
    curve: 'smooth',
  },
  plotOptions: {
    bar: { columnWidth: '50%' },
  },
  colors: ['#2e7d32', '#c62828', '#0288d1'],
  xaxis: {
    categories: [],
  },
  yaxis: [
    {
      // Eje Izquierdo: Monto en $ (compartido por Ventas y Costos)
      title: { text: 'Monto ($)' },
      labels: {
        formatter: val => (val !== undefined && val !== null ? `$${Number(val).toLocaleString()}` : '$0'),
      },
    },
    {
      // Eje Derecho: Porcentaje % (para Margen)
      opposite: true,
      title: { text: 'Margen (%)' },
      min: 0,
      max: 100,
      labels: {
        formatter: val => (val !== undefined && val !== null ? `${Number(val).toFixed(0)}%` : '0%'),
      },
    },
  ],
  tooltip: {
    shared: true,
    intersect: false,
    y: {
      formatter: (val, { seriesIndex }) => {
        if (val === null || val === undefined)
          return ''

        return seriesIndex === 2
          ? `${Number(val).toFixed(2)}%`
          : `$${Number(val).toLocaleString()}`
      },
    },
  },
})

const fetchData = async () => {
  loading.value = true
  try {
    // DIAGNÓSTICO: Revisa qué estructura está llegando en la consola
    console.log('recordData actual:', recordData.value)

    const rows = recordData.value?.data || recordData.value || []

    if (!Array.isArray(rows) || rows.length === 0) {
      console.warn('No hay registros en rows')
      series.value = []

      return
    }

    const months = rows.map(item => item.month_name || `Mes ${item.month || ''}`)
    const totals = rows.map(item => Number(item.total) || 0)
    const costs = rows.map(item => Number(item.cost_of_sale) || 0)
    const margins = rows.map(item => Number(item.percentage) || 0)

    console.log('Datos Mapeados:', { months, totals, costs, margins })

    // Actualizar datos
    series.value = [
      { name: 'Ventas Totales', type: 'column', data: totals },
      { name: 'Costo de Ventas', type: 'column', data: costs },
      { name: 'Margen %', type: 'line', data: margins },
    ]

    chartOptions.value = {
      ...chartOptions.value,
      xaxis: {
        categories: months,
      },
    }

    chartKey.value++
  }
  catch (error) {
    console.error('Error al procesar gráfica:', error)
  }
  finally {
    loading.value = false
  }
}

onMounted(fetchData)

const generarConsulta = async () => {
  console.log('Id Company:', localStorage.getItem('company_id'))

  // datafechas.value.desdefecha = '1900-01-01'
  loading.value = true
  try {
    // onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
    const { data } = await axios.post('/api/monthly-statistics', {
      url_token: localStorage.getItem('auth_token'),
      company_id: localStorage.getItem('company_id'),
      process_year: model.value,
      page: page.value,
      per_page: itemsPerPage.value,
    },
    {
      headers: { Authorization: `Bearer ${token}` },
    },
    )

    recordData.value = data
    responseData.value = data

    // console.log('Respuesta API Consolidado :', recordData.value.data)

    fetchData()

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
        <h3 class="text-primary mb-2">
          Estadísticas Mensuales
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
            style="inline-size: 20em; max-inline-size: 300px;"
          />
        </VCardText>
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <VSelect
          v-model="model"
          label="Año de Proceso :"
          :items="years"
          class="mt-7"
          variant="outlined"
          @update:model-value="fetchData"
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
    </VRow>
  </VCard>

  <!-- Contenedor Principal en 2 Columnas -->
  <VRow v-if="documentos && documentos.length">
    <!-- Columna Izquierda: VDataTable (Ocupa 9 columnas en pantallas md/desktop) -->
    <VCol
      cols="12"
      md="5"
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
            <template #item.total="{ item }">
              <div class="td-right text-column text-end">
                {{ formatCurrency(Number(item.total)) }}
              </div>
            </template>
            <template #item.cost_of_sale="{ item }">
              <div class="td-right text-column text-end">
                {{ formatCurrency(Number(item.cost_of_sale)) }}
              </div>
            </template>
            <template #item.absolute_diference="{ item }">
              <div class="td-right text-column text-end">
                {{ formatCurrency(Number(item.absolute_diference)) }}
              </div>
            </template>
            <template #item.percentage="{ item }">
              <div class="td-right text-column text-end">
                {{ formatCurrency(Number(item.percentage, 2)) }} %
              </div>
            </template>
          </VDataTable>
        </div>
      </VCard>
    </VCol>

    <VCol
      cols="12"
      md="7"
    >
      <VCard class="pa-4">
        <VCardTitle class="d-flex justify-space-between align-center">
          <span>Consolidado de Ventas vs. Costos (Año {{ proccessYear }})</span>
          <VBtn
            color="primary"
            icon="mdi-refresh"
            :loading="loading"
            @click="fetchData"
          />
        </VCardTitle>

        <VCardText>
          <!-- Estado: Cargando -->
          <div
            v-if="loading"
            class="d-flex justify-center align-center py-12"
          >
            <VProgressCircular
              indeterminate
              color="primary"
            />
          </div>

          <!-- Estado: Sin Datos -->
          <div
            v-else-if="!series.length || !series[0].data.length"
            class="text-center py-12 text-medium-emphasis"
          >
            No se encontraron datos para el año {{ processYear }}.
          </div>

          <!-- Gráfica (Solo se renderiza con datos listos) -->
          <Apexchart
            v-else
            :key="chartKey"
            v-model="processYear"
            type="line"
            height="400"
            :options="chartOptions"
            :series="series"
          />
        </VCardText>
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
