<script lang="ts" setup>
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import { nextTick, onActivated, onMounted, ref } from 'vue'
import type { CxPPayment } from '../index.js'
import ReportarOtrosPagosDialog from './ReportarOtrosPagosDialog.vue'
import ReportarPagosDialog from './ReportarPagosDialog.vue'

const props = withDefaults(defineProps<Props>(), {
  tipoDeEgreso: '',
  suppliers: () => [],
  infoegreso: () => [],
  otherexpenses: () => [],
})

// 2. Declaramos las props con valores por defecto nativos de TS usando withDefaults

const emit = defineEmits(['save', 'close', 'list', 'tercero', 'egreso-guardado'])
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')
const EgresoActivo = ref('')

function onEgresoGuardado(data: any) {
  // Cierra también este dialog (el padre)
  emit('update:dialogfact', false)

  // Reenvía el evento hacia index.vue
  emit('egreso-guardado', data)
}

interface Props {
  tipoDeEgreso: string
  dialogfact: boolean
  dialogotrop: bolena
  suppliers: Supplier[]
  sources: Sources[]
  otherexpenses: OtherExpense[]
  docspayments: DocsPayments[]
  docspaymentsothers: DocsPaymentsOthers[]
  itemsdetallefact?: PurchaseInvoiceReport[] // 👈 array, no Record
  paymentcpt: []
  totales: []
  detalledepago: []
  valoregreso: number
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

function getDefaultItem(): CxPPayment {
  return {
    id: null,
    nit: null,
    branch: '01',
    lapse: lapsedate, // valor fresco cada vez
    report_date: hoy, // fecha fresca cada vez (o tu lógica de "hoy")
    check_date: null,
    delivery_date: null,
    consecutive: null,
    document: null,
    supplier_name: null,
    value_cxp: null,
    others_payments: null,
    observations: null,
    payment_method: null,
    check_number: null,
    payment_type: 'PagosFacturas',
    state: 'Pendiente',
    state01: null,
    state02: null,
    state03: null,
    proyect: null,
    sproyect: null,
    center: null,
    activity: null,
    companies_id: null,
    suppliers_id: null,
    created_at: null,
    updated_at: null,
    usercreate: 'System',
    userupdate: 'System',
  }
}

const itemsdetallefact = ref<PurchaseInvoiceReport[]>([])
const proveedorSeleccionado = ref<Supplier | null>(null)
const documentoSeleccionado = ref<DocsPayments | null>(null)
const origenSeleccionado = ref<Source | null>(null)
const proveedorInfo = ref<Supplier | null>(null)
const origenInfo = ref<Source | null>(null)
const documentoInfo = ref<DocsPayments | null>(null)
const totales = ref([])
const valoregreso = ref(0)
const valoraplicacion = ref(0)
const paymentcptref = ref([])

onMounted(() => {
  proveedorSeleccionado.value = null
  documentoSeleccionado.value = null
  origenSeleccionado.value = null
  //console.log('Soy Tipo de Egreso :', props.tipoDeEgreso)

  if (props.tipoDeEgreso === 'Pagos de Facturas')
    ValidarCrearEgresos.value = '!proveedorSeleccionado || !origenSeleccionado || !editedItem.value_cxp || !documentoSeleccionado ? true : false'

  else
    ValidarCrearEgresos.value = '!proveedorSeleccionado || !origenSeleccionado || !documentoSeleccionado ? true : false'
})

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
const ValidarCrearEgresos = computed(() => {
  const faltaProveedor = !proveedorSeleccionado.value
  const faltaOrigen = !origenSeleccionado.value
  const faltaDocumento = !documentoSeleccionado.value

  // Validación específica para 'Pagos de Facturas'
  if (props.tipoDeEgreso === 'Pagos de Facturas') {
    const faltaValorCxp = !editedItem.value?.value_cxp

    return faltaProveedor || faltaOrigen || faltaDocumento || faltaValorCxp
  }

  // Validación para cualquier otro tipo de egreso
  return faltaProveedor || faltaOrigen || faltaDocumento
})

onActivated(() => {
  proveedorSeleccionado.value = null
  documentoSeleccionado.value = null
  origenSeleccionado.value = null
  console.log('Soy Tipo de Egreso :', props.tipoDeEgreso)
})

// Cambia reactive por ref para el manejo del estado del formulario
const editedItem = ref<CxPPayment>(getDefaultItem())

const open = async (item: CxPPayment | null = null) => {
  await nextTick()

  if (item) {
    const rawItem = toRaw(item)

    editedItem.value = { ...getDefaultItem(), ...rawItem }
  }
  else {
    editedItem.value = getDefaultItem() // ✅ objeto nuevo, valores frescos, cero refs colados
  }

  form.value?.resetValidation()
  dialog.value = true
}

const list = async () => {
  // console.log('Soy Egreso:', egreso)
  const _nit = editedItem.value.nit
  const _suc = editedItem.value.branch
  const _fec = editedItem.value.report_date

  try {
    const response = await axios.post('/api/list-balances-cxp', {
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
    valoregreso.value = editedItem.value.value_cxp
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
  proveedorSeleccionado.value = null
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

// Exponer la función open para que el componente padre pueda llamarla
defineExpose({
  open,
})

function onProveedorSeleccionado(proveedor: Supplier | null): void {
  console.log('Entre Aquí Seleccionando Proveedor :', proveedor)
  proveedorInfo.value = proveedor || null
  if (proveedor) {
    console.log('Entre Aquí Seleccionando Proveedor :', `${proveedor.nit} ${proveedor.name}`)
    editedItem.value.nit = proveedor.nit
    editedItem.value.branch = proveedor?.branch
    editedItem.value.supplier_name = proveedor?.name
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

const valueField = useNumericField(editedItem, 'value_cxp')

const egresoSeleccionado = ref(0)

async function abrirReportarPagos(tipodeegreso: string) {
  if (tipodeegreso === 'Pagos de Facturas') {
    console.log('Entre a Pagos Facturas')
    await list()
    egresoSeleccionado.value = 1
    mostrarReportarPagos.value = true
    mostrarReportarOtrosPagos.value = false
  }
  else {
    console.log('Entre a Otros Pagos')
    egresoSeleccionado.value = 1
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
</script>

<template>
  <VDialog
    v-if="dialog"
    v-model="dialog"
    max-width="1100px"
    persistent
  >
    <VCard class="mt-0">
      <VCardTitle class="modal-title d-flex align-center text-h6">
        <VIcon
          icon="tabler-brand-cashapp"
          size="28"
          color="white"
          class="me-3"
        />
        {{ editedItem.id ? 'Actualizando el Egreso' : 'Generando Egreso' }}
        <span
          class="text-h6 font-weight-bold ml-2"
          style="color: #f7fb2d !important;"
        >
          ({{ tipoDeEgreso }})
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
                  autofocus
                  required
                  class="custom-autocomplete mt-3"
                  @update:model-value="onProveedorSeleccionado"
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
                  class="text-center-input mb-2"
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
                  :items="(tipoDeEgreso === 'Pagos de Facturas') ? docspayments : docspaymentsothers"
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
                md="6"
                class="py-0"
              >
                <VAutocomplete
                  v-model="origenSeleccionado"
                  :items="sources"
                  item-title="name"
                  item-value="code"
                  label="Orígen del Pago"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="comfortable"
                  rounded="lg"
                  return-object
                  required
                  class="custom-autocomplete mt-2"
                  @update:model-value="onOrigenSeleccionado"
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
            </VRow>
            <VRow>
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
                <AppDateTimePicker
                  v-model="editedItem.check_date"
                  label="Fecha del Cheque :"
                  placeholder="Seleccionar Fecha"
                  class="text-center-input mb-2"
                  variant="outlined"
                  prepend-inner-icon="tabler-calendar"
                  :config="{ locale: Spanish, static: false, dateFormat: 'Y-m-d' }"
                  readonly
                />
              </VCol>
              <VCol
                cols="12"
                md="2"
                class="py-0"
              >
                <AppDateTimePicker
                  v-model="editedItem.check_number"
                  label="Número de Cheque:"
                  placeholder="Seleccionar Fecha"
                  class="text-center-input mb-2"
                  variant="outlined"
                  prepend-inner-icon="tabler-calendar"
                  readonly
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
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
                  :disabled="tipoDeEgreso === 'Otros Pagos'"
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
                  class="mb-2 aligned-field custom-font-size"
                  readonly
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
                  @update:model-value="val => editedItem.observactions = val ? val.toUpperCase() : ''"
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
          :disabled="ValidarCrearEgresos"
          @click="abrirReportarPagos(tipoDeEgreso)"
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
    :egreso-id="egresoSeleccionado?.id"
    :tercero="editedItem"
    :itemsdetallefact="itemsdetallefact"
    :totales="totales"
    :valoregreso="valoregreso"
    :paymentcpt="paymentcptref"
    @pago-reportado="onPagoReportado"
    @cerrar-todo="cerrarAmbosDialogs"
    @egreso-guardado="onEgresoGuardado"
  />

  <ReportarOtrosPagosDialog
    v-model:dialogotrop="mostrarReportarOtrosPagos"
    :egreso-id="egresoSeleccionado?.id"
    :tercero="editedItem"
    :suppliers="suppliers"
    :otherexpenses="otherexpenses"
    @pago-reportado="onPagoReportado"
    @cerrar-todo="cerrarAmbosDialogs"
    @egreso-guardado="onEgresoGuardado"
  />
</template>

<style scoped>
/* Estilos específicos del componente si son necesarios */
/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
.custom-font-size :deep(.v-field__input) {
  font-size: 14px !important; /* Ajusta los píxeles a tu gusto */
}
</style>
