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
  customers: [],
  dctoscxc: [],
  listbalances: [],
  movements: [],
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
  customers: [],
  dctoscxc: [],
  page: 1,
  per_page: 13,
})

const documentos = computed(() => {
  return recordData.value?.movements ?? []
})

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
  datafechas.value.desdefecha = '1900-01-01'
  loading.value = true
  try {
    // onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
    const { data } = await axios.post('/api/cash-reconciliation', {
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
    console.log('Respuesta API SaldoInicial:', recordData.value)

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

const headers = [
  { title: 'Fecha Reporte', key: 'report_date', sortable: true, width: '5%' },
  { title: 'Tipo Documento', key: 'document_name', sortable: true, width: '15%' },
  { title: 'Consecutivo', key: 'number', sortable: true, width: '5px' },
  { title: 'Prefijo', key: 'prefix', sortable: true },
  { title: 'Nombre del Tercero', key: 'name', sortable: true, width: '35%' },
  { title: 'Cálculo', key: 'calculo', sortable: true, align: 'center' },
  { title: 'Saldo Inicial', key: 'saldoinicial', sortable: true, align: 'center' },
  { title: 'Ingresos', key: 'ingresos', sortable: true, align: 'center' },
  { title: 'Pagos', key: 'pagos', sortable: true, align: 'center' },
  { title: 'Saldo Actual', key: 'saldoactual', sortable: true, align: 'end' },
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
          Cuadre de Caja
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

  <section v-if="documentos && documentos.length">
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

          <template #item.document_name="{ item }">
            <div class="td-left text-column">
              {{ item.document_name }}
            </div>
          </template>

          <template #item.number="{ item }">
            <div class="td-left text-column">
              {{ item.number }}
            </div>
          </template>

          <template #item.prefix="{ item }">
            <div class="td-left text-column">
              {{ item.prefix }}
            </div>
          </template>

          <template #item.name="{ item }">
            <div class="td-left text-column">
              {{ item.name }}
            </div>
          </template>

          <template #item.calculo="{ item }">
            <div class="td-left text-column">
              {{ item.calculo }}
            </div>
          </template>

          <template #item.saldoinicial="{ item }">
            <div class="td-right text-column text-end">
              {{ formatCurrency(Number(item.saldoinicial)) }}
            </div>
          </template>

          <template #item.ingresos="{ item }">
            <div class="td-right text-column text-end">
              {{ formatCurrency(Number(item.ingresos)) }}
            </div>
          </template>

          <template #item.pagos="{ item }">
            <div class="td-right text-column text-end">
              {{ formatCurrency(Number(item.pagos)) }}
            </div>
          </template>

          <template #item.saldoactual="{ item }">
            <div class="td-right text-column text-end">
              {{ formatCurrency(Number(item.saldoactual)) }}
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
                  Saldo Actual $:
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

  <section v-else-if="(!documentos || !documentos.length)">
    <VCard>
      <VCardTitle class="pa-4">
        No se encontraron registros para el periodo seleccionado
      </VCardTitle>
    </VCard>
  </section>

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
