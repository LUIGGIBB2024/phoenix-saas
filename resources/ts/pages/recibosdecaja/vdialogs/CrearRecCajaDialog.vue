<script lang="ts" setup>
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import { nextTick, onActivated, onMounted, ref } from 'vue'
import type { SalesInvoice } from '../index.js'
import ReportarOtrosPagosDialog from './ReportarOtrosPagosDialog.vue'
import ReportarPagosDialog from './ReportarPagosDialog.vue'

const props = withDefaults(defineProps<Props>(), {
  tipoDeRecibo: '',
  customers: () => [],
  inforecibo: () => [],
  otherexpenses: () => [],
  paymentcpt: () => [],

})

// 2. Declaramos las props con valores por defecto nativos de TS usando withDefaults

const emit = defineEmits(['save', 'close', 'list', 'tercero', 'recibo-guardado'])
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')
const ReciboActivo = ref('')

function onReciboGuardado(data: any) {
  // Cierra también este dialog (el padre)
  emit('update:dialogfact', false)

  // Reenvía el evento hacia index.vue
  emit('recibo-guardado', data)
}

interface Props {
  tipoDeRecibo: string
  dialogfact: boolean
  dialogotrop: boolean
  customers: Customer[]
  sources: Sources[]
  otherexpenses: OtherExpense[]
  docspayments: DocsPayments[]
  docspaymentsothers: DocsPaymentsOthers[]
  itemsdetallefact?: SalesInvoice[] // 👈 array, no Record
  paymentcpt: []
  totales: []
  detalledepago: []
  valorrecibo: number
  valoraplicacion: number
}
const tokenhijo = localStorage.getItem('auth_token')
const isFocused = ref(false)

const mostrarReportarPagos = ref(false)
const mostrarReportarOtrosPagos = ref(false)

const hoy = new Date().toISOString().split('T')[0]
const lapsedate = hoy.replace(/-/g, '').slice(0, 6) // "202607"

const rules = {
  required: (value: string) => !!value || 'Este campo es obligatorio',
  email: (value: string) =>
    !value || /^[^\s@]+@[^\s@][^\s.@]*\.[^\s@]+$/.test(value) || 'Correo inválido',
  phone: (value: string) =>
    !value || value.length >= 7 || 'Debe tener al menos 7 dígitos',
}

const dialog = ref(false)
const valid = ref(true)
const form = ref<HTMLFormElement | null>(null)

function getDefaultCxcPayment(): CxCPayment {
  return {
    id: 0, // O null si cambias la interfaz a: id: number | null
    nit: null,
    branch: '01',
    lapse: lapsedate,
    report_date: hoy,
    consecutive: null,
    document: null,
    customer_name: null,
    value_cxc: null,
    customer_balances: null,
    observations: null,
    check_number: null,
    payment_type: 'PagosFacturas',
    state: 'Activo', // O 'Pendiente' según el estado inicial de tu formulario
    state01: null,
    state02: null,
    state03: null,
    proyect: null,
    sproyect: null,
    center: null,
    activity: null,
    customers_id: null,
    companies_id: null,
    created_at: new Date().toISOString(), // O null si permites string | null en la interface
    updated_at: new Date().toISOString(), // O null si permites string | null en la interface
    usercreate: 'System',
    userupdate: 'System',
  }
}

const itemsdetallefact = ref<SalesInvoice[]>([])
const clienteSeleccionado = ref<Customer | null>(null)
const documentoSeleccionado = ref<DocsPayments | null>(null)
const origenSeleccionado = ref<Source | null>(null)
const clienteInfo = ref<Customer | null>(null)
const origenInfo = ref<Source | null>(null)
const documentoInfo = ref<DocsPayments | null>(null)
const totales = ref([])
const valorrecibo = ref(0)
const valoraplicacion = ref(0)
const paymentcptref = ref([])

onMounted(() => {
  clienteSeleccionado.value = null
  documentoSeleccionado.value = null
  origenSeleccionado.value = null
})

// console.log('Soy Tipo de Recibo :', props.tipoDeRecibo)

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

// 1. Definir ValidarCrearEgresos como una propiedad computada
const ValidarCrearRecibos = computed(() => {
  const faltaCliente = !clienteSeleccionado.value
  const faltaOrigen = !origenSeleccionado.value
  const faltaDocumento = !documentoSeleccionado.value

  // Validación específica para 'Pagos de Facturas'
  if (props.tipoDeRecibo === 'Pagos de Facturas') {
    const faltaValorCxc = !editedItem.value?.value_cxc

    return faltaCliente || faltaDocumento || faltaValorCxc
  }

  // Validación para cualquier otro tipo de egreso
  return faltaCliente || faltaOrigen || faltaDocumento
})

onActivated(() => {
  clienteSeleccionado.value = null
  documentoSeleccionado.value = null
  origenSeleccionado.value = null
})

// Cambia reactive por ref para el manejo del estado del formulario
const editedItem = ref<CxCPayment>(getDefaultCxcPayment())

const openreccaja = async (item: CxCPayment | null = null) => {
  await nextTick()

  if (item) {
    const rawItem = toRaw(item)

    editedItem.value = { ...getDefaultCxcPayment(), ...rawItem }
  }
  else {
    editedItem.value = getDefaultCxcPayment() // ✅ objeto nuevo, valores frescos, cero refs colados
  }

  console.log('Entre a Abrir Modal HIJO')

  form.value?.resetValidation()
  dialog.value = true
}

const list = async () => {
  // console.log('Soy Egreso:', egreso)
  const _nit = editedItem.value.nit
  const _suc = editedItem.value.branch
  const _fec = editedItem.value.report_date

  try {
    const response = await axios.post('/api/list-balances-cxc', {
      company_id: localStorage.getItem('company_id'),
      nit: _nit,
      sucursal: _suc,
      fecha: _fec,
      process_year: localStorage.getItem('process_year'),
    },
    {
      headers: { Authorization: `Bearer ${tokenhijo}` },
    })

    itemsdetallefact.value = response.data.listbalances
    paymentcptref.value = response.data.paymentcpt
    totales.value = response.data.totales
    valorrecibo.value = editedItem.value.value_cxc
    valoraplicacion.value = 0

    console.log('Soy Concepto:', paymentcptref)
  }
  catch (error) {
    console.error('Error al intentar Consultar:', error)
  }
}

const close = () => {
  dialog.value = false

  // Quitamos la limpieza inmediata de 'editedItem.value' de aquí.
  // Es mucho más seguro limpiar los datos en el método 'open' justo antes de que se muestre,
  // así evitamos que Vuetify intente validar campos inexistentes mientras el modal se destruye/oculta.
  clienteSeleccionado.value = null
  documentoSeleccionado.value = null
  origenSeleccionado.value = null
  form.value?.resetValidation()
  emit('close')
}

const save = async () => {
  const { valid } = await form.value!.validate()

  if (valid) {
    // 1. Tomamos el objeto de donde sea que esté guardado
    const origen = editedItem.value

    // 2. Apuntamos a los datos reales (desenvolvemos si hay doble Ref)
    const datos = (origen && origen._value) ? origen._value : origen

    // 3. Construimos un objeto nuevo con tipado dinámico para evitar errores de TS
    const payloadLimpio: Record<string, any> = {}

    // 4. Usamos Object.keys (más seguro que for...in) para recorrer las propiedades
    for (const key of Object.keys(datos)) {
      // MAGIA AQUÍ: Filtramos la estructura circular de Vue.
      if (key !== 'dep' && !key.startsWith('_'))
        payloadLimpio[key] = datos[key]
    }

    // console.log('Payload Purificado Listo para Enviar:', payloadLimpio)

    // 5. Enviamos el objeto completamente limpio al index.vue
    emit('save', payloadLimpio)
    close()
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
    editedItem.value.lapse = lapsedate
  }

  // ... modificas más propiedades de editedItem
}

function onDocumentoSeleccionado(documento: DocsPayments | null): void {
  documentoInfo.value = documento || null
  if (documento)
    editedItem.value.document = documento.code

  // ... modificas más propiedades de editedItem
}

function onOrigenSeleccionado(origen: Source | null): void {
  origenInfo.value = origen || null
  if (origen)
    editedItem.value.payment_method = origen.code

  // ... modificas más propiedades de editedItem
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

const valueField = useNumericField(editedItem, 'value_cxc')

const reciboSeleccionado = ref(0)

async function abrirReportarPagos(tipoderecibo: string) {
  if (tipoderecibo === 'Pagos de Facturas') {
    console.log('Entre a Pagos Facturas')
    await list()
    reciboSeleccionado.value = 1
    mostrarReportarPagos.value = true
    mostrarReportarOtrosPagos.value = false
  }
  else {
    console.log('Entre a Otros Pagos')
    reciboSeleccionado.value = 1
    mostrarReportarOtrosPagos.value = true
    mostrarReportarPagos.value = false
  }
}

function onPagoReportado(datos) {
  console.log('Pago reportado:', datos)

  // refrescar lista, mostrar snackbar, etc.
}

function cerrarAmbosDialogs() {
  console.log('Entre Aqui a Cerrar')

  // Cierra el dialog padre (CrearEgresosDialog)
  emit('update:dialog', false)

  // Si necesitas limpiar el formulario del padre también
  form.value?.resetValidation()
  close()
}

// Exponer la función open para que el componente padre pueda llamarla
defineExpose({
  openreccaja,
})
</script>

<template>
  <VDialog
    v-if="dialog"
    v-model="dialog"
    max-width="1100px"
    persistent
  >
    <VCard class="mt-0">
      <VCardTitle class="modal-title d-flex align-center text-h6 bg-primary">
        <VIcon
          icon="tabler-brand-cashapp"
          size="28"
          color="white"
          class="me-3"
        />
        Reportando Recibos de Caja
        <span
          class="text-h6 font-weight-bold ml-2"
          style="color: #f7fb2d !important;"
        >
          ({{ tipoDeRecibo }})
        </span>
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
                  autofocus
                  required
                  class="custom-autocomplete mt-3"
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
                  :rules="[rules.required]"
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
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppDateTimePicker
                  v-model="editedItem.report_date"
                  label="Fecha de Reporte :"
                  placeholder="Seleccionar Fecha"
                  class="text-center-input mb-2 text_size"
                  variant="outlined"
                  prepend-inner-icon="tabler-calendar"
                  :config="{ locale: Spanish, static: false, dateFormat: 'Y-m-d' }"
                />
              </VCol>
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
                  :items="(tipoDeRecibo === 'Pagos de Facturas') ? docspayments : docspaymentsothers"
                  item-title="name"
                  item-value="code"
                  label="Tipo de Documento"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  return-object
                  required
                  class="custom-autocomplete mt-2"
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
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="editedItem.lapse"
                  label="Lapso"
                  class="mb-2 text_size"
                  placeholder="Lapso"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  readonly
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-layers-intersect"
                      class="py-0"
                    />
                  </template>
                </apptextfield>
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppTextField
                  v-model="valueField.formattedValue.value"
                  label="Valor del Pago:"
                  class="mb-2 text_size"
                  placeholder="Valor del Pago"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  :disabled="tipoDeRecibo === 'Otros Pagos'"
                  @keypress="valueField.onlyNumbersAndDot"
                  @focus="valueField.isFocused.value = true"
                  @blur="valueField.isFocused.value = false"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-report-money"
                      class="py-0"
                    />
                  </template>
                </apptextfield>
              </VCol>
              <VCol
                cols="12"
                md="2"
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
            <VDivider class="mt-3 color='error'" />
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
                  v-model="editedItem.observations"
                  label="Observaciones"
                  required
                  class="mb-2 text_size aligned-field"
                  placeholder="Ingrese Observaciones"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                  auto-grow
                  rows="3"
                  @update:model-value="val => editedItem.observations = val ? val.toUpperCase() : ''"
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
          </VForm>
        </VContainer>
      </VCardText>
      <VCardActions>
        <VSpacer />
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
          @click="close"
        >
          Cancelar
        </VBtn>
        <VBtn
          width="120"
          min-width="0"
          color="success"
          variant="flat"
          :disabled="ValidarCrearRecibos"
          @click="abrirReportarPagos(tipoDeRecibo)"
        >
          Aplicar Pagos
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

  <!-- Aquí lo instancias, fuera o dentro del v-dialog padre -->
  <ReportarPagosDialog
    v-model:dialogfact="mostrarReportarPagos"
    :egreso-id="reciboSeleccionado?.id"
    :tercero="editedItem"
    :itemsdetallefact="itemsdetallefact"
    :totales="totales"
    :valorrecibo="valorrecibo"
    :paymentcpt="paymentcptref"
    @pago-reportado="onPagoReportado"
    @cerrar-todo="cerrarAmbosDialogs"
    @recibo-guardado="onReciboGuardado"
  />

  <ReportarOtrosPagosDialog
    v-model:dialogotrop="mostrarReportarOtrosPagos"
    :egreso-id="reciboSeleccionado?.id"
    :tercero="editedItem"
    :suppliers="customers"
    :otherexpenses="otherexpenses"
    @pago-reportado="onPagoReportado"
    @cerrar-todo="cerrarAmbosDialogs"
    @recibo-guardado="onReciboGuardado"
  />
</template>

<style lang="scss">
/* Estilos específicos del componente si son necesarios */
/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.custom-font-size :deep(.v-field__input) {
  font-size: 14px !important; /* Ajusta los píxeles a tu gusto */
}

.custom-autocomplete-menu {
  .v-list-item-title {
    font-size: 0.8rem !important; /* Tamaño del texto de cada opción */
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
  font-size: 0.8rem !important;
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
</style>
