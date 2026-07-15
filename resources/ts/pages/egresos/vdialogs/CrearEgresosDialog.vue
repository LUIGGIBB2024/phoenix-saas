<script setup lang="ts">

// Si usas flatpickr para las fechas, necesitas el idioma español

// 1. DEFINIR PROPS (Lo que el padre le envía a este diálogo)
interface Props {
  modelValue: boolean // Controla si se muestra o no el diálogo
  recordToEdit?: any // Datos si se va a editar (opcional)
  suppliers: any[] // Listado de proveedores
  cptpurchases: any[] // Listado de conceptos de compra
  dctoscxp: any[] // Listado de tipos de documentos
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false,
  recordToEdit: () => ({}),
  payments: () => [],

})

// 2. DEFINIR EMITS (Eventos para avisarle al padre)
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'save', record: any): void
}>()

// 3. CONTROL DE VISIBILIDAD (Sincronizado con el padre)
const showDialog = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})

// 4. ESTADO LOCAL DEL FORMULARIO
// Inicializamos el registro vacío o con los datos a editar
const newRecord = ref<any>({
  id: null,
  nit: '',
  dv: '',
  branch: '01',
  type_of_purchase: 'Contado',
  order_number: '',
  report_date: '',
  purchase_invoice: '',
  prefix: '',
  date_from: '',
  date_to: '',
  observations: '',
  address: '',
})

const proveedorSeleccionado = ref<any>(null)
const conceptoSeleccionado = ref<any>(null)
const dctoscxpSeleccionado = ref<any>(null)

// Reglas de validación
const rules = {
  required: (value: any) => !!value || 'Este campo es obligatorio.',
}

// 5. OBSERVAR CAMBIOS (Si cambia el registro a editar, actualizamos el formulario)
watch(() => props.recordToEdit, newVal => {
  if (newVal && Object.keys(newVal).length > 0)
    newRecord.value = { ...newVal }

  // Aquí puedes pre-cargar los autocompletes si ya vienen asignados
  else
    resetForm()
}, { immediate: true })

// 6. FUNCIONES / MANEJADORES DE EVENTOS
const onProveedorSeleccionado = (proveedor: any) => {
  if (proveedor) {
    newRecord.value.nit = proveedor.nit || ''
    newRecord.value.dv = proveedor.dv || ''
  }
}

const onConceptoSeleccionado = (concepto: any) => {
  // Tu lógica cuando seleccionan un concepto
}

const onDctoscxpSeleccionado = (documento: any) => {
  // Tu lógica cuando seleccionan tipo de documento
}

const resetForm = () => {
  newRecord.value = {
    id: null,
    nit: '',
    dv: '',
    branch: '01',
    type_of_purchase: 'Contado',
    order_number: '',
    report_date: '',
    purchase_invoice: '',
    prefix: '',
    date_from: '',
    date_to: '',
    observations: '',
    address: '',
  }
  proveedorSeleccionado.value = null
  conceptoSeleccionado.value = null
  dctoscxpSeleccionado.value = null
}

const saveRecord = () => {
  // Emitimos el registro al componente padre para que haga el llamado API (Laravel)
  emit('save', { ...newRecord.value })
  showDialog.value = false // Cerramos el diálogo
}
</script>

<template>
  <VDialog
    v-model="showDialog"
    persistent
    max-width="1100px"
    attach="#app"
    :retain-focus="false"
  >
    <VCard>
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
                  class="mb-2 text_size"
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
                  class="mb-2 text_size"
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
                  class="mb-3 text_size"
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
                  class="mb-2 text_size"
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
                  placeholder="Número de Factura"
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
                  class="mb-2 text_size"
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
                  @update:model-value="val => newRecord.observations = val ? val.toUpperCase() : ''"
                >
                  <template #prepend-inner>
                    <VIcon
                      icon="tabler-writing"
                      color="primary"
                      size="22"
                      class="me-2"
                    />
                  </template>
                </AppTextarea>
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
          </VForm>
        </VDefaultsProvider>
      </VCardText>
    </VCard>
  </VDialog>
</template>
