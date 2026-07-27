<script lang="ts" setup>
import axios from 'axios'
import type { OtherExpense, OtherPayments } from '..'

const props = withDefaults(defineProps<Props>(), {
  dialogfact: false,
  tipoDeEgreso: '',
  isFocused: false,
  itemsdetallefact: () => [],
  totales: () => [],
  suppliers: () => [],
  otherexpenses: () => [],
  valoregreso: 0,
  totalcapturado: 0,
  valoraplicacion: 0,
  detalledeapgo: () => [],
  paymentcpt: () => [],
})

// Declaramos explícitamente los eventos que el componente va a emitir
const emit = defineEmits(['update:dialogotrop', 'pago-reportado', 'close', 'closeReg', 'cerrar-todo', 'egreso-guardado'])

// const emit = defineEmits<{
//   (e: 'update:dialogotrop', value: boolean): void
// }>()

const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

const form = ref<HTMLFormElement | null>(null)

// Asignamos valores por defecto para evitar errores si no se pasan
const tokenhijo = localStorage.getItem('auth_token')
const isFocused = ref(false)
const isEdited = ref(false)
const itemsPerPage = ref(10)

const hoy = new Date().toISOString().split('T')[0]

const editedItem = ref<OtherPayments>(getDefaultItem())
const pagosTemporal = ref<OtherPayments>(getDefaultItem())

interface Props {
  dialogfact: boolean
  dialogotrop: boolean
  tipoDeEgreso: string
  isFocused: boolean
  tercero?: CxPPayment // 👈 agregar aquí también
  itemsdetallefact: PurchaseInvoiceReport[] // 👈 Usar el tipo real en vez de []
  totales: []
  suppliers: Supplier[]
  otherexpenses: OtherExpense[]
  valoregreso: number
  totalcapturado: number
  valoraplicacion: number
  detalledeapgo: []
  paymentcpt: []
}

function getDefaultItem(): OtherPayments {
  return {
    id: 0,
    consecutive: 0,
    document: '',
    nit: '',
    branch: '',
    supplier_name: '',
    report_date: (hoy),
    internaldoc: '',
    concept: '',
    concept_name: '',
    accounting_code: '',
    center: '',
    scenter: '',
    proyect: '',
    sproyect: '',
    activity: '',
    payment_amount: 0,
    idregister: 0,
    idlinea: 0,
    calculate: '',
    suppliers_id: 0,
  }
}

const itemsOtrosPagos = ref<OtherPayments[]>([])
let contadorLinea = 0

const formatCurrency = (value: number | string, fractionDigits: number = 2) => {
  const num = Number(value) || 0

  return num.toLocaleString('en-US', {
    minimumFractionDigits: fractionDigits,
    maximumFractionDigits: fractionDigits,
  })
}

const proveedorSeleccionado = ref<Supplier | null>(null)
const proveedorInfo = ref<Supplier | null>(null)

const otrospagosSeleccionados = ref<OtherExpense | null>(null)
const otrospagosinfo = ref<OtherExpense | null>(null)

watch(() => props.dialogotrop, val => {
  if (val) {
    // Se ejecuta CADA VEZ que el modal se abre
    showSnackbar.value = false
    resetFormulario()
  }
})

const resetFormulario = () => {
  form.value?.resetValidation()
  pagosTemporal.value = []
  itemsOtrosPagos.value = []
  editedItem.value = getDefaultItem()
  proveedorSeleccionado.value = null
  otrospagosSeleccionados.value = null
  isEdited.value = false
  contadorLinea = 0
  totalCapturado.value = 0
}

const close_otrop = () => {
  resetFormulario()
  emit('update:dialogotrop', false)
  emit('close_otrop')
  props.dialogotrop.value = false
}

function onProveedorSeleccionado(proveedor: Supplier | null): void {
  console.log('Entre Aquí Seleccionando Proveedor :', proveedor)
  proveedorInfo.value = proveedor || null
  if (proveedor)
    console.log('Entre Aquí Seleccionando Proveedor :', `${proveedor.nit} ${proveedor.name}`)

  // ... modificas más propiedades de editedItem
  editedItem.value.nit = proveedor.nit
  editedItem.value.branch = proveedor.branch
  editedItem.value.supplier_name = proveedor.name
  editedItem.value.suppliers_id = proveedor.id
}

function onOtrospagosSeleccionados(otrospagos: OtherExpense | null): void {
  console.log('Entre Aquí Seleccionando otros pagos :', otrospagos)
  otrospagosinfo.value = otrospagos || null
  if (otrospagos)
    console.log('Entre Aquí Seleccionando Otros Pagos :', `${otrospagos.code} ${otrospagos.name}`)

  // ... modificas más propiedades de editedItem
  editedItem.value.concept = otrospagos?.code
  editedItem.value.concept_name = otrospagos?.name
  editedItem.value.calculate = (Number.parseFloat(otrospagos.factor) === 1) ? 'Suma' : 'Resta'

  console.log('Entre Aquí Soy  editedItem :', editedItem.value)
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

const valueField = useNumericField(editedItem, 'payment_amount')

function agregarPagos(): void {
  // 1. Validaciones previas
  if (!proveedorSeleccionado.value || editedItem.value.payment_amount <= 0 || !otrospagosSeleccionados.value)
    return

  if (!isEdited.value) {
    // --- MODO CREACIÓN ---
    contadorLinea++ // Solo incrementa al crear un registro nuevo

    itemsOtrosPagos.value.push({
      id: contadorLinea,
      idlinea: contadorLinea,
      document: editedItem.value.document,
      nit: editedItem.value.nit,
      branch: editedItem.value.branch,
      supplier_name: editedItem.value.supplier_name,
      report_date: editedItem.value.report_date,
      concept: editedItem.value.concept,
      concept_name: editedItem.value.concept_name,
      payment_amount: editedItem.value.payment_amount,
      proyect: '',
      sproyect: '',
      activity: '',
      consecutive: 0,
      internaldoc: editedItem.value.internaldoc,
      accounting_code: '',
      center: '',
      scenter: '',
      idregister: 0,
      calculate: editedItem.value.calculate,
      suppliers_id: editedItem.value.suppliers_id,
    })
  }
  else {
    // --- MODO EDICIÓN ---
    const idBuscado = editedItem.value.idlinea
    const index = itemsOtrosPagos.value.findIndex(item => item.idlinea === idBuscado)

    if (index !== -1) {
      // Reemplazamos los datos actualizados manteniendo el idlinea e id original
      itemsOtrosPagos.value[index] = {
        ...itemsOtrosPagos.value[index], // Mantiene campos estáticos (center, proyect, etc.)
        document: editedItem.value.document,
        nit: editedItem.value.nit,
        branch: editedItem.value.branch,
        supplier_name: editedItem.value.supplier_name,
        report_date: editedItem.value.report_date,
        concept: editedItem.value.concept,
        concept_name: editedItem.value.concept_name,
        payment_amount: editedItem.value.payment_amount,
        internaldoc: editedItem.value.internaldoc,
        calculate: editedItem.value.calculate,
      }
    }
  }

  // 3. Reiniciar formulario y estado de edición
  proveedorSeleccionado.value = null
  otrospagosSeleccionados.value = null

  editedItem.value = getDefaultItem()
  isEdited.value = false
}

const totalCapturado = computed(() => {
  return itemsOtrosPagos.value.reduce((acc, pago) => {
    const signo = (pago.calculate === 'Suma') ? 1 : -1

    return acc + (pago.payment_amount * signo)
  }, 0)
})

function openEditDialog(item: any) {
  const idbuscado = item.id

  isEdited.value = true

  const index = itemsOtrosPagos.value.findIndex(regs => regs.id === idbuscado)

  if (index !== -1) {
  // 1. Para EDITAR el registro:
    editedItem.value.id = itemsOtrosPagos.value[index].idlinea
    editedItem.value.idlinea = itemsOtrosPagos.value[index].idlinea
    editedItem.value.nit = itemsOtrosPagos.value[index].nit
    editedItem.value.branch = itemsOtrosPagos.value[index].branch
    editedItem.value.supplier_name = itemsOtrosPagos.value[index].supplier_name
    editedItem.value.payment_amount = itemsOtrosPagos.value[index].payment_amount
    editedItem.value.report_date = itemsOtrosPagos.value[index].report_date
    editedItem.value.internaldoc = itemsOtrosPagos.value[index].internaldoc
    editedItem.value.calculate = itemsOtrosPagos.value[index].calculate
    editedItem.value.document = itemsOtrosPagos.value[index].document
    editedItem.value.concept = itemsOtrosPagos.value[index].concept
    editedItem.value.concept_name = itemsOtrosPagos.value[index].concept_name
    proveedorSeleccionado.value = itemsOtrosPagos.value[index].supplier_name
    otrospagosSeleccionados.value = itemsOtrosPagos.value[index].concept_name
  }
}

const handleSaveEgreso = async (egreso: any) => {
  console.log('Soy Egreso:', egreso, 'DetPagos:', pagosTemporal.value)
  try {
    const response = await axios.post('/api/supplierpayment', {
      ...egreso,
      tipo: 'OTROSP',
      detpagos: itemsOtrosPagos.value,
      company_id: localStorage.getItem('company_id'),
      process_year: localStorage.getItem('process_year'),
    },
    {
      headers: { Authorization: `Bearer ${tokenhijo}` },
    })

    console.log('Soy Response.data', response.data)

    // Cierra este dialog (hijo)
    emit('update:dialogotrop', false)

    // 👇 Avisa al padre que también debe cerrarse
    emit('cerrar-todo')

    emit('egreso-guardado', {
      esEdicion: !!egreso.id,
      registro: response.data.payments,
    })
    showSnackbar.value = true
    snackbarMessage.value = '<< Egreso Generado Exitosamente >>'
    snackbarColor.value = 'success'

    close()
  }
  catch (error) {
    console.error('Error al intentar guardar:', error)
  }
}

const confirmDelete = (idlinea: number) => {
  // 1. Buscamos el índice del elemento a eliminar
  const index = itemsOtrosPagos.value.findIndex(item => item.idlinea === idlinea)

  // 2. Si existe, lo eliminamos
  if (index !== -1) {
    itemsOtrosPagos.value.splice(index, 1)

    // 3. Reorganizamos consecutivamente 'id' e 'idlinea'
    itemsOtrosPagos.value.forEach((item, idx) => {
      item.id = idx + 1
      item.idlinea = idx + 1
    })

    // 4. (Opcional) Ajustamos el contador global para nuevos registros
    if (typeof contadorLinea !== 'undefined')
      contadorLinea = itemsOtrosPagos.value.length
  }
}

const headers = [
  { title: 'Id', key: 'idlinea', width: '5%' },
  { title: 'Nombre del Tercero', key: 'supplier_name', width: '30%' },
  { title: 'Nit/Cédula', key: 'nit', sortable: true, width: '5%' },
  { title: 'Suc', key: 'branch', sortable: true, width: 30 },
  { title: 'Descripción del Concepto', key: 'concept_name', sortable: true, width: '20%' },
  { title: 'Documento Interno', key: 'internaldoc', sortable: true, width: '10%', align: 'center' },
  { title: 'Valor del Pago', key: 'payment_amount', sortable: true, width: '10%', align: 'center' },
  { title: 'Acciones', key: 'actions', sortable: false, width: '10%', aling: 'center' },
]
</script>

<template>
  <VDialog
    max-width="1200px"
    :model-value="props.dialogotrop"
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
          icon="tabler-brand-cashapp"
          size="28"
          color="white"
          class="me-3"
        />
        Reporte de Otros Pagos
        <span
          class="text-h6 font-weight-bold ml-2"
          style="color: #f7fb2d !important;"
        />
      </VCardTitle>
      <VCard
        class="rounded-xl my-2 mx-2 border-accent"
        elevation="2"
        border="2"
      >
        <VCardText class="pa-3 pa-md-4">
          <VRow dense>
            <VCol
              cols="12"
              md="6"
            >
              <VAutocomplete
                v-model="proveedorSeleccionado"
                :items="suppliers"
                item-title="name"
                item-value="id"
                label="Seleccionar Proveedor"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="comfortable"
                rounded="lg"
                return-object
                autofocus
                required
                hide-details="auto"
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
              md="6"
            >
              <VAutocomplete
                v-model="otrospagosSeleccionados"
                :items="otherexpenses"
                item-title="name"
                item-value="id"
                label="Seleccionar Tipos de Gastos"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="comfortable"
                rounded="lg"
                return-object
                hide-details="auto"
                required
                class="custom-autocomplete mt-3"
                @update:model-value="onOtrospagosSeleccionados"
              >
                <template #prepend-inner>
                  <VIcon
                    icon="tabler-category-plus"
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
              md="3"
              class="py-0"
            >
              <AppTextField
                v-model="editedItem.internaldoc"
                label="Documento Interno"
                class="mb-2 text_size"
                placeholder="Documento Interno"
                density="comfortable"
                variant="outlined"
                hide-details="auto"
                @update:model-value="val => editedItem.internaldoc = val ? val.toUpperCase().replace(/[^A-Z0-9]/g, '') : ''"
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
                hide-details="auto"
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
              class="py-5"
            >
              <VBtn
                :color="formularioInvalido ? 'indigo' : 'success'"
                rounded="lg"
                height="40"
                width="160"
                :disabled="(!proveedorSeleccionado || !otrospagosSeleccionados || !editedItem.payment_amount) ? true : false"
                @click=" agregarPagos"
              >
                <VIcon
                  start
                  size="22"
                >
                  tabler-plus
                </VIcon>
                Agregar Reporte
              </VBtn>
            </VCol>
            <VCol
              cols="12"
              md="3"
              class="py-5 d-flex align-center justify-end"
            >
              <div class="d-flex text-h5 font-weight-bold text-primary">
                Total $: {{ formatCurrency(totalCapturado) }}
              </div>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
      <section>
        <VCard id="grid-list">
          <VDivider />
          <VDataTable
            :headers="headers"
            :items="itemsOtrosPagos"
            item-value="idLinea"
            density="compact"
            :items-per-page="-1"
            hide-default-footer
            class="products-gridc border rounded"
            :height="$vuetify.display.height < 800 ? 180 : 250"
            striped="even"
            fixed-header
          >
            <template #item.idlinea="{ item }">
              <div class="cell-wrap columna_name">
                {{ item.idlinea }}
              </div>
            </template>

            <template #item.supplier_name="{ item }">
              <div class="cell-wrap columna_name">
                {{ item.supplier_name }}
              </div>
            </template>

            <template #item.nit="{ item }">
              <div class="cell-wrap columna_name">
                {{ item.nit }}
              </div>
            </template>

            <template #item.branch="{ item }">
              <div class="cell-wrap columna_name">
                {{ item.branch }}
              </div>
            </template>

            <template #item.concept_name="{ item }">
              <div class="cell-wrap columna_name">
                {{ item.concept_name }}
              </div>
            </template>

            <template #item.internaldoc="{ item }">
              <div class="cell-wrap columna_name">
                {{ item.internaldoc }}
              </div>
            </template>
            <template #item.payment_amount="{ item }">
              <div class="cell-wrap columna_name">
                {{ formatCurrency(item.payment_amount, 2) }}
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
            </template>
          </VDataTable>
          <VSpacer />
          <VCardActions class="mt-3">
            <VSpacer />
            <VBtn
              width="100"
              min-width="0"
              color="error"
              variant="flat"
              @click="close_otrop"
            >
              Cancelar
            </VBtn>
            <VBtn
              width="100"
              min-width="0"
              color="success"
              variant="flat"
              :disabled="totalCapturado = 0"
              @click="handleSaveEgreso(props.tercero)"
            >
              Guardar
            </VBtn>
          </VCardActions>
        </vcard>
        <VSnackbar
          v-model="showSnackbar"
          :color="snackbarColor"
          location="center"
          timeout="5000"
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
    </vcard>
  </VDialog>
</template>

<style lang="css">
.columna_name {
  display: block;
  font-size: 0.75em !important;
  line-height: 1.3;         /* mejora legibilidad */
  overflow-wrap: break-word;
  white-space: normal; /* permite salto de línea */
  word-wrap: break-word;    /* divide palabras largas */
}

/* Pantallas laptop o medianas (1366x768 o menores) */
@media (max-width: 1400px) {
  .columna_name {
    font-size: 0.85rem !important; /* Equivalente a ~13.5px */
  }
}
</style>
