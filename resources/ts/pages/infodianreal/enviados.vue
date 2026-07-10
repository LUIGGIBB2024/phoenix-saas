<script setup lang="ts">
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import fileDownload from 'js-file-download'
import { T } from 'node_modules/unplugin-vue-router/dist/options-ChnxZdan.mjs'
import { computed, ref } from 'vue'
import { VRow } from 'vuetify/components'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

const snackbar = ref(false)
const yaBusco = ref(false) // Nueva variable de control

const formatCurrency = (value: number | string) => {
  const num = Number(value) || 0

  return num.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const hoy = new Date().toISOString().split('T')[0]
const loading = ref(false)
const isFormValid = ref(false)

const datafechas = ref({
  desdefecha: hoy,
  hastafecha: hoy,
})

const capturarEmail = ref({
  email: '',
})

// 🔹 Reglas
const rules = {
  required: (v: string) => !!v || 'Este campo es obligatorio',
  email: (v: string) => !v || /^[^\s@]+@[^\s@][^\s.@]*\.[^\s@]+$/.test(v) || 'Correo inválido',
  password: (v: string) => v.length >= 6 || 'La contraseña debe tener al menos 6 caracteres',
}

const datadocument = ref({
  numberdocument: '',
  prefix: '',
  email: '',
})

const correoelectronico = ref('')

const selectedInvoice = ref<any>(null) // 👈 aquí guardaremos el item de la fila

const showDialog = ref(false)
const isPasswordVisible = ref(false)

const searchQuery = ref('')
const selectedRows = ref([])

const invoiceData = ref({
  status: '',
  message: '',
  TotalDocumentos: 0,
  TotalValor: 0,
  TotalIva: 0,
  data: [],
  page: 1,
  per_page: 13,
})

//
const showDialogEmail = ref(false)
const editMode = ref(false)

// 🔹 Variables del DataTable
const itemsPerPage = ref(13)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const token = localStorage.getItem('auth_token')

const updateOptions = async (options: any) => {
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  await generarConsulta()
}

const descargarXml = async (item: any) => {
  try {
    const response = await axios.post('/api/downdocument/xml', {
      numberdocument: item.number,
      prefix: item.prefix,
    }, {
      responseType: 'blob', // 👈 recibe el archivo como binario
    })

    // Descargar con nombre dinámico
    fileDownload(response.data, `factura_${item.prefix}_${item.number}.xml`)
  }
  catch (error) {
    console.error('Error al descargar XML:', error)
  }
}

const descargarPdf = async (item: any) => {
  try {
    const response = await axios.post('/api/downdocument/pdf', {
      numberdocument: item.number,
      prefix: item.prefix,
    }, {
      responseType: 'blob',
    })

    fileDownload(response.data, `factura_${item.prefix}_${item.number}.pdf`)
  }
  catch (error) {
    console.error('Error al descargar PDF:', error)
  }
}

const generarConsulta = async () => {
  console.log('Id Company:', localStorage.getItem('company_id'))
  loading.value = true
  try {
    // onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
    const { data } = await axios.post('/api/dian/documentos-enviados', {
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
    console.log('Respuesta API:', data)
    console.log('Respuesta InvoiceData:', invoiceData.value)
    yaBusco.value = true // Marcar que ya se realizó una búsqueda
    if (invoiceData.value.TotalDocumentos === 0 && yaBusco.value)
      snackbar.value = true
  }
  catch (error) {
    console.error(error)
  }
  finally {
    loading.value = false
  }
}

// onMounted(() => generarConsulta())

const facturas = computed(() => invoiceData.value.data ?? [])
const currentPage = computed(() => invoiceData.value.page ?? page.value)
const perPage = computed(() => invoiceData.value.per_page ?? itemsPerPage.value)

// const totalInvoices   = computed(() => invoiceData.value.TotalValor ?? 0)
// const totalIva        = computed(() => invoiceData.value.TotalIva ?? 0)
const totalregistros = computed(() => invoiceData.value.TotalDocumentos ?? 0)

console.log('Soy Registro..:', invoiceData.value.TotalDocumentos)

// ── Exportar a Excel ──────────────────────────────────────────
const exportarExcel = () => {
  const datos = facturas.value.map(item => ({
    'ID': item.id,
    'Fecha': item.date_issue,
    'Prefijo': item.prefix,
    'Número': item.number,
    'Tipo Documento': item.document_name,
    'Nit/Cédula': item.customer,
    'Cliente': item.client_name,
    'Subtotal': item.subtotal,
    'IVA': item.vatvalue,
    'Total': item.total_sale,
    'CUFE': item.cufe,
  }))

  const hoja = XLSX.utils.json_to_sheet(datos)
  const libro = XLSX.utils.book_new()

  XLSX.utils.book_append_sheet(libro, hoja, 'Facturas')
  XLSX.writeFile(libro, `Facturas_Ventas_${datafechas.value.desdefecha}_${datafechas.value.hastafecha}.xlsx`)
}

// ── Exportar a PDF ────────────────────────────────────────────
const exportarPDF = () => {
  const doc = new jsPDF({ orientation: 'landscape' })

  doc.setFontSize(14)
  doc.text('Documentos Enviados (Facturas/Notas)', 14, 15)
  doc.setFontSize(9)
  doc.text(`Período: ${datafechas.value.desdefecha}  al  ${datafechas.value.hastafecha}`, 14, 22)

  autoTable(doc, {
    startY: 28,
    head: [[
      'ID',
      'Fecha',
      'Prefijo',
      'Número',
      'Tipo Doc.',
      'Nit/Cédula',
      'Cliente',
      'Subtotal',
      'IVA',
      'Total',
    ]],
    body: facturas.value.map(item => [
      item.id,
      item.date_issue,
      item.prefix,
      item.number,
      item.document_name,
      item.customer,
      item.client_name,
      formatCurrency(item.subtotal),
      formatCurrency(item.vatvalue),
      formatCurrency(item.total_sale),
    ]),
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
      'TOTALES',
      formatCurrency(totalInvoices.value - totalIva.value), // opcional si lo tienes calculado
      formatCurrency(totalIva.value),
      formatCurrency(totalInvoices.value),
    ]],
    footStyles: { fillColor: [200, 230, 255], fontStyle: 'bold' },
  })

  doc.save(`Facturas_Ventas_${datafechas.value.desdefecha}_${datafechas.value.hastafecha}.pdf`)
}

// Items filtrados según searchQuery
// ✅ Computed de items filtrados
const facturasFiltradas = computed(() => {
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
  facturasFiltradas.value.reduce((acc, item) => acc + (Number(item.total_sale) || 0), 0),
)

const totalIva = computed(() =>
  facturasFiltradas.value.reduce((acc, item) => acc + (Number(item.vatvalue) || 0), 0),
)

const headers = [
  { title: 'id', key: 'id', sortable: true, width: '4px' },
  { title: 'Fecha Documento', key: 'date_issue', sortable: true },
  { title: 'Número de Factura', key: 'number', sortable: true, width: '5px' },
  { title: 'Prefijo', key: 'prefix', sortable: true },
  { title: 'Tipo Documento', key: 'document_name', sortable: true },
  { title: 'Nit/Cédula', key: 'customer', sortable: true, width: '12%' },
  { title: 'Nombre del Cliente', key: 'client_name', sortable: true, width: '50%' },
  { title: 'Valor Documento', key: 'subtotal', sortable: true },
  { title: 'Valor Impuestos', key: 'vatvalue', sortable: true },
  { title: 'Total Documento', key: 'total_sale', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false, width: '15%' },
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
        <h3 class="text-primary mb-2">
          Documentos Enviados
        </h3>
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
          v-model="datafechas.desdefecha"
          label="Desde Fecha :"
          placeholder="Seleccionar Fecha"
          class="text-center-input"
          variant="outlined"
          prepend-inner-icon="tabler-calendar"
          :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppDateTimePicker
          v-model="datafechas.hastafecha"
          label="Hasta Fecha :"
          placeholder="Seleccionar Fecha"
          class="text-center-input"
          prepend-inner-icon="tabler-calendar"
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

      <VCol
        cols="12"
        md="2"
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
          />
          PDF
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
          class="text-body-2 tabla-facturas"
          fixed-header
          density="compact"
        >
          <template #header.id>
            <div class="th-center">
              #Id
            </div>
          </template>
          <template #item.id="{ item }">
            <div class="td-right">
              {{ item.id }}
            </div>
          </template>

          <template #header.date_issue>
            <div class="th-center">
              Fecha<br>Documento
            </div>
          </template>
          <template #item.date_issue="{ item }">
            <div class="td-center">
              {{ item.date_issue }}
            </div>
          </template>

          <template #header.number>
            <div class="th-center">
              Número<br>Factura
            </div>
          </template>
          <template #item.number="{ item }">
            <div class="td-right">
              {{ item.number }}
            </div>
          </template>

          <template #header.prefix>
            <div class="th-center">
              Prefijo
            </div>
          </template>
          <template #item.prefix="{ item }">
            <div class="td-left">
              {{ item.prefix }}
            </div>
          </template>

          <template #header.document_name>
            <div class="th-center">
              Tipo<br>Documento
            </div>
          </template>
          <template #item.document_name="{ item }">
            <div class="td-left">
              {{ item.document_name }}
            </div>
          </template>

          <template #header.customer>
            <div class="th-center">
              Nit/Cédula<br>Cliente
            </div>
          </template>
          <template #item.customer="{ item }">
            <div class="td-left">
              {{ item.customer }}
            </div>
          </template>

          <template #header.client_name>
            <div class="th-center">
              Nombre del Cliente
            </div>
          </template>
          <template #item.client_name="{ item }">
            <div class="td-left">
              {{ item.client_name }}
            </div>
          </template>

          <template #header.subtotal>
            <div class="th-center">
              Valor<br>Parcial
            </div>
          </template>
          <template #item.subtotal="{ item }">
            <div class="td-right">
              {{ formatCurrency(item.subtotal) }}
            </div>
          </template>

          <template #header.vatvalue>
            <div class="th-center">
              Valor<br>Impuestos
            </div>
          </template>
          <template #item.vatvalue="{ item }">
            <div class="td-right">
              {{ formatCurrency(item.vatvalue) }}
            </div>
          </template>

          <template #header.total_sale>
            <div class="th-center">
              Valor<br>Total
            </div>
          </template>
          <template #item.total_sale="{ item }">
            <div class="td-right">
              {{ formatCurrency(item.total_sale) }}
            </div>
          </template>

          <template #header.actions>
            <div class="td-center">
              Acciones
            </div>
          </template>
          <template #item.actions="{ item }">
            <div class="td-center">
              <!-- <div class="td-actions" style="display: flex; align-items: center"> -->
              <IconBtn>
                <VIcon
                  icon="tabler-file-type-xml"
                  color="primary"
                  @click=""
                />
              </IconBtn>
            </div>
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
                  <strong class="text-primary">{{ formatCurrency(totalInvoices) }}</strong>
                </div>
                <div class="text-caption text-medium-emphasis ps-4 text-end">
                  Total Iva $:
                  <strong class="text-error">{{ formatCurrency(totalIva) }}</strong>
                </div>
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

  <VOverlay
    :model-value="loading"
    persistent
    class="align-center justify-center"
  >
    <VProgressCircular
      indeterminate
      size="64"
      color="primary"
    />
  </VOverlay>

  <VSnackbar
    v-model="snackbar"
    color="error"
    timeout="4000"
    location="center"
    style="font-size: 5em !important;"
  >
    <VIcon
      icon="tabler-alert-circle"
      class="me-2"
    />
    <span style="font-size: 1.2rem; font-weight: 500;">
      No se encontraron registros, intente nuevamente por favor
    </span>
    <template #actions>
      <VBtn
        variant="text"
        @click="snackbar = false"
      >
        Cerrar
      </VBtn>
    </template>
  </VSnackbar>
</template>

 <style lang="css">
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

.pagination-wrapper :deep(.v-pagination__list) {
  justify-content: center;
}

/* Si no usas scoped, puedes hacerlo así: */
.text-center-input input {
  cursor: pointer;
  text-align: center !important;
}

/* .v-data-table :deep(.v-data-table__thead th) {
    background-color: rgb(243, 16, 175) !important;
    color: white !important;
    text-transform: none !important;
  } */

.v-data-table__thead th {
  background-color: rgb(247, 58, 206) !important;
  color: white !important;
}

thead th {
  background-color: rgb(247, 58, 206) !important;
  color: white !important;
}

.v-data-table thead th {
  text-transform: capitalize !important;
}

._fila {
  color: black;
  font-size: 11px !important;
  inline-size: 6em;
  line-height: 1.2;
  white-space: normal;
  word-wrap: break-word;
}

.modal-title {
  background-color: rgb(var(--v-theme-primary));
  border-start-end-radius: 6px;
  border-start-start-radius: 6px;
  color: white;
  font-size: 1.2rem;
  font-weight: 600;
  padding-block: 16px;
  padding-inline: 24px;
}

.custom-card {
  block-size: 250px !important; /* Ajusta a tu necesidad */
}

.dian-iframe {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px;
  block-size: 700px;
  inline-size: 100%;
}

.loader-overlay {
  position: absolute;
  z-index: 2;
  background: rgba(var(--v-theme-surface), 0.7);
  block-size: 100%;
  inline-size: 100%;
  inset-block-start: 0;
  inset-inline-start: 0;
}

.dian-iframe-full {
  border: none;
  background-color: #fff;
  block-size: 100%;
  inline-size: 100%;
}

.loader-overlay {
  position: absolute;
  z-index: 10;
  background: rgba(var(--v-theme-surface), 0.9);
  block-size: 100%;
  inline-size: 100%;
  inset-block-start: 0;
  inset-inline-start: 0;
}

.gap-2 {
  gap: 8px;
}

/* .header-columna .v-table{
       color: white !important;
    }

     .header-columna {
       color: white !important;
    } */

.v-data-table thead th .v-table {
  color: white !important;
}

.v-data-table thead th {
  color: white !important;
}

.header-columna {
  font-size: 0.9em !important;
}

.boton-export {
  inline-size: 10em !important;
}

/* Contenedor con scroll horizontal en pantallas pequeñas */
.table-responsive {
  inline-size: 100%;
  overflow-x: auto;
}

.th-center {
  font-weight: 600;
  line-height: 1.3;
  text-align: center;
  white-space: normal;
}

.td-left,
.td-right,
.td-center {
  line-height: 1.3;
  white-space: nowrap; /* ← clave: evita que los números se partan */
}

.td-right { text-align: end; }
.td-center { text-align: center; }
.td-left { text-align: start; }

.td-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Columnas numéricas — nunca se parten */
.col-numero {
  min-inline-size: 90px;
  white-space: nowrap;
}

/* Columna cliente — única que puede wrappear */
.col-cliente {
  max-inline-size: 95%;
  min-inline-size: 180px;
  white-space: normal;
  word-break: break-word;
}

.col-tipo {
  min-inline-size: 120px;
  white-space: nowrap;
}

@media (max-width: 1366px) {
  .tabla-facturas {
    font-size: 0.75rem;
  }
}

/* ─── TABLA FACTURAS ──────────────────────────────────────────────── */

/* Filas de datos */
.tabla-facturas :deep(tbody tr td) {
  color: #0a0a0a !important;
  font-family: Roboto, sans-serif !important;
  font-size: 0.9rem !important;
  font-weight: 300 !important;
  white-space: nowrap;
}

/* Headers */
.tabla-facturas :deep(thead tr th) {
  color: #fff !important;
  font-family: Roboto, sans-serif !important;
  font-size: 0.73rem !important;
  font-weight: 600 !important;
}

/* Columna cliente — única que puede wrappear */
.tabla-facturas :deep(tbody tr td) .col-cliente {
  max-inline-size: 260px;
  min-inline-size: 180px;
  white-space: normal;
  word-break: break-word;
}

.tabla-facturas :deep(tbody tr td) .col-tipo {
  min-inline-size: 140px;
  white-space: nowrap;
}

/* Responsive 1366px */
@media (max-width: 1366px) {
  .tabla-facturas :deep(tbody tr td) {
    font-size: 0.4rem !important;
  }
}

/* Apunta al div interno que Vuetify genera dentro de cada td */
.tabla-facturas :deep(.v-data-table__td-inner) {
  color: #0a0a0a !important;
  font-family: Roboto, sans-serif !important;
  font-size: 0.65rem !important;
}

.tabla-facturas :deep(.v-data-table__th-inner) {
  color: #fff !important;
  font-family: Roboto, sans-serif !important;
  font-size: 0.73rem !important;
  font-weight: 600 !important;
}
</style>
