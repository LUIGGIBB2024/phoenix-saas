<script setup lang="ts">
import axios from 'axios'
import { computed, nextTick, onMounted, ref, watch } from 'vue'

// Componentes de Vuetify corregidos
import { VBtn } from 'vuetify/components/VBtn'
import { VCard, VCardActions, VCardText, VCardTitle } from 'vuetify/components/VCard'
import { VDivider } from 'vuetify/components/VDivider' // <-- Corregido: Ruta específica para VDivider
import { VCol } from 'vuetify/components/VGrid'

// import type { Product } from './type'

const archivos = ref<File[]>([])

const isFocused = ref(false)

// const certificateNombre = ref('')
const certificateFile = ref<File | null>(null)
const certificateFileModel = ref<File | File[] | null>(null)

const inputRef = ref<HTMLInputElement | null>(null)

const tipodeusuario = localStorage.getItem('tipo_de_usuario')

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files)
    archivos.value = Array.from(target.files)
}

// 🔹 Filtros y variables de estado
const searchQuery = ref('')
const selectedRows = ref([])

// 🔹 Opciones del datatable
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const hoy = new Date().toISOString().split('T')[0]

// const token = localStorage.getItem('auth_token')

const accessToken = useCookie('accessToken', { path: '/' })

// accessToken.value = response.data.token // ← el que te devuelve Laravel

// 🔹 Actualizar opciones de orden
// const updateOptions = (options: any) => {
//   sortBy.value = options.sortBy[0]?.key
//   orderBy.value = options.sortBy[0]?.order
// }

const token = localStorage.getItem('auth_token')

const updateOptions = async (options: any) => {
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// 🔹 Encabezados de la tabla
export interface Product {
  id?: number
  code: string
  name: string
  namegroupselected: string
  namesgroupselected: string
  namemeasureselected: string
  codereference: string
  unit_of_measure: string
  presentation: string
  percent: number
  sale_value: number
  cost: number
  location: string
  control_id: string
  typeofproduct: string
  require_scale: string
  billable: string
  group: string
  subgroup: string
  division: string
  category: string
  family: string
  namephoto: string
  routephoto: string
  observations: string
  cups: string
  alternate_code: string
  cie10_code: string
  invima_register: string
  units_per_packaging: number
  weight_volume: number
  conversion_factor: number
  date_last_purchase: string | Date
  minimum_stock: number
  maximum_stock: number
  profitability: number
  consumption_tax: number
  listvalue1: number
  listvalue2: number
  listvalue3: number
  companies_id: number
  state: string
}

const headers = [
  { title: '#', key: 'id' },
  { title: 'Codígo', key: 'code', sortable: true, width: '10%' },
  { title: 'Nombre', key: 'name', sortable: true, width: '35%' },
  { title: 'Referencia', key: 'codereference', sortable: true, width: '10%' },
  { title: 'Unidad', key: 'measure_name', sortable: true },
  { title: 'Presentación', key: 'presentation', sortable: true },
  { title: 'Iva', key: 'percent', sortable: true, align: 'end' },
  { title: 'Valor Venta', key: 'sale_value', sortable: true, width: '10%', align: 'end' },
  { title: 'Costo Promedio', key: 'cost', sortable: true, width: '20%', align: 'end' },
  { title: 'Estado', key: 'state', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false },
]

// --- 🔹 Modal y formulario de creación ---
const showDialog = ref(false)
const editMode = ref(false) // 👈 false = crear, true = editar

const newRecord = ref<Product>({
  id: 0,
  code: '',
  name: '',
  namegroupselected: '',
  namesgroupselected: '',
  namemeasureselected: '',
  codereference: '',
  unit_of_measure: '',
  presentation: '',
  percent: 0,
  sale_value: 0,
  cost: 0,
  location: '',
  control_id: 'Inventario',
  typeofproduct: '',
  require_scale: '',
  billable: '',
  group: '',
  subgroup: '',
  division: '',
  category: '',
  family: '',
  namephoto: '',
  routephoto: '',
  observations: '',
  cups: '',
  alternate_code: '',
  cie10_code: '',
  invima_register: '',
  units_per_packaging: 0,
  weight_volume: 0,
  conversion_factor: 0,
  date_last_purchase: (hoy),
  minimum_stock: 0,
  maximum_stock: 0,
  profitability: 0,
  consumption_tax: 0,
  listvalue1: 0,
  listvalue2: 0,
  listvalue3: 0,
  companies_id: 0,
  state: 'Activo',
})

// watch(archivos, files => {
//   if (files.length > 0)
//     newProduct.value.certificatename = files[0]
// })

// 🔹 Snackbar (toast)
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

const responseData = ref({
  data: [],
  grupos: [],
  sgrupos: [],
  unidades: [],
  total: 0,
  page: 1,
  per_page: 10,
  totaldctos: 0,
})

// 🔹 Diálogo de confirmación de eliminación
const grupos = ref([])
const sgrupos = ref([])
const unidades = ref([])
const nameRecordToDelete = ref('')
const showConfirmDialog = ref(false)
const recordToDelete = ref<number | null>(null)

// 🔹 Reglas de validación
const rules = {
  required: (value: string) => !!value || 'Este campo es obligatorio',
  email: (value: string) =>
    !value || /^[^\s@]+@[^\s@][^\s.@]*\.[^\s@]+$/.test(value) || 'Correo inválido',
  phone: (value: string) =>
    !value || value.length >= 7 || 'Debe tener al menos 7 dígitos',
}

// 🔹 Observa el estado del diálogo
watch(showDialog, isOpen => {
  if (isOpen && !editMode.value) {
    // Se abre el diálogo → limpiar los campos
    newRecord.value = {
      id: 0,
      code: '',
      name: '',
      namegroupselected: '',
      namesgroupselected: '',
      namemeasureselected: '',
      codereference: '',
      unit_of_measure: '',
      presentation: '',
      percent: 0,
      sale_value: 0,
      cost: 0,
      location: '',
      control_id: 'Inventario',
      typeofproduct: '',
      require_scale: '',
      billable: 'Si',
      group: '',
      subgroup: '',
      division: '',
      category: '',
      family: '',
      namephoto: '',
      routephoto: '',
      observations: '',
      cups: '',
      alternate_code: '',
      cie10_code: '',
      invima_register: '',
      units_per_packaging: 0,
      weight_volume: 0,
      conversion_factor: 0,
      date_last_purchase: (hoy),
      minimum_stock: 0,
      maximum_stock: 0,
      profitability: 0,
      consumption_tax: 0,
      listvalue1: 0,
      listvalue2: 0,
      listvalue3: 0,
      companies_id: 0,
      state: 'Activo',
    }
  }
})

const loadInfo = async () => {
  try {
    const response = await axios.get('/api/getproducts', {
      params: {
        q: searchQuery.value,
        itemsPerPage: itemsPerPage.value,
        page: page.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        company_id: localStorage.getItem('company_id'),
      },
      headers: { Authorization: `Bearer ${token}` },
    })

    responseData.value = response.data
    grupos.value = response.data.grupos
    sgrupos.value = response.data.sgrupos
    unidades.value = response.data.unidades

    console.log('Soy Datos:', responseData.value)
  }
  catch (error) {
    console.error('Error al intentar enviar correo :', error)
  }
}

// 🔹 Ejecutar al montar
onMounted(() => loadInfo())

// 🔍 Escucha cambios en la barra de búsqueda
// watch(searchQuery, () => {
//   console.log('🔍 Búsqueda cambiada:', searchQuery.value)
//   page.value = 1
//   loadInfo()
// })

// 🔹 Computed para acceder fácilmente a los datos
// const infoData = computed(() => responseData.value?.data ?? [])

const infoData = computed(() => {
  const data = responseData.value?.data ?? []

  return data.map(p => ({
    ...p,
    group: p.group?.trim(),
    subgroup: p.subgroup?.trim(),
    unit_of_measure: p.unit_of_measure?.trim(),
    group_name: p.group_name?.trim(),
    sgroup_name: p.sgroup_name?.trim(),
    measure_name: p.measure_name?.trim(),
  }))
})

const totalRecords = computed(() => responseData.value?.total ?? 0)
const perPage = computed(() => responseData.value.per_page ?? itemsPerPage.value)
const currentPage = computed(() => responseData.value.page ?? page.value)

console.log('Información sub grupos:', sgrupos)

const saveRecord = async () => {
  const formData = new FormData()

  formData.append('company_id', localStorage.getItem('company_id') || '')

  Object.entries(newRecord.value).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '')
      formData.append(key, value as string)
  })

  // 🔍 DEBUG: confirmar qué se está enviando realmente
  console.log('--- FormData enviado ---')
  for (const [key, value] of formData.entries())
    console.log(key, ':', value)

  try {
    const url = newRecord.value.id
      ? `/api/products/${newRecord.value.id}`
      : '/api/products'

    const { data } = await axios.post(url, formData)

    // 🔍 1. Confirmar qué llega del backend
    console.log('1. data.products desde backend:', data.products.unit_of_measure)

    // 🔎 Buscamos los nombres correspondientes a los códigos seleccionados
    const grupoSeleccionado = grupos.value.find(
      g => String(g.code).trim() === String(newRecord.value.namegroupselected).trim(),
    )

    const sgrupoSeleccionado = sgrupos.value.find(
      s => String(s.code).trim() === String(newRecord.value.namesgroupselected).trim(),
    )

    const unidadSeleccionada = unidades.value.find(
      u => String(u.code).trim() === String(newRecord.value.namemeasureselected).trim(),
    )

    // 🔍 2 y 3. Confirmar selección del VSelect de unidad
    console.log('2. newRecord.namemeasureselected:', newRecord.value.namemeasureselected)
    console.log('3. unidadSeleccionada encontrada:', unidadSeleccionada)

    // 🧩 Mergeamos el producto crudo del backend con los nombres frescos
    const productoActualizado = {
      ...data.products,
      group_name: grupoSeleccionado?.name ?? null,
      sgroup_name: sgrupoSeleccionado?.name ?? null,
      measure_name: unidadSeleccionada?.name ?? null,
    }

    // 🔍 4 y 5. Confirmar el objeto final antes de asignarlo
    console.log('4. productoActualizado.unit_of_measure:', productoActualizado.unit_of_measure)
    console.log('5. productoActualizado.measure_name:', productoActualizado.measure_name)

    if (newRecord.value.id) {
      const index = responseData.value.data.findIndex(
        (c: any) => c.id === newRecord.value.id,
      )

      console.log('index encontrado:', index)

      if (index !== -1) {
        // ✅ Reemplazamos el array completo (nueva referencia) para forzar reactividad
        responseData.value.data = [
          ...responseData.value.data.slice(0, index),
          productoActualizado,
          ...responseData.value.data.slice(index + 1),
        ]
      }
    }
    else {
      responseData.value.data = [...responseData.value.data, productoActualizado]
    }

    // 🔍 6. Confirmar el estado final en responseData
    console.log('6. responseData después de asignar:', responseData.value.data.find((c: any) => c.id === newRecord.value.id)?.measure_name)

    snackbarMessage.value = newRecord.value.id
      ? 'Producto actualizado correctamente'
      : 'Producto creado correctamente'
    snackbarColor.value = 'success'

    certificateFileModel.value = null
    showDialog.value = false
  }
  catch (error: any) {
    console.error('Error:', error.response?.data)
    snackbarMessage.value = 'Error al guardar el Producto'
    snackbarColor.value = 'error'
  }
  finally {
    showSnackbar.value = true
  }
}

// 🔧 Helper para normalizar IDs a número (o null si no aplica)
const toId = (val: any) => {
  if (val === null || val === undefined || val === '')
    return null

  return Number(val)
}

// 🔹 Abrir modal en modo edición
const openEditDialog = _infoData => {
  editMode.value = true
  certificateFileModel.value = null

  // console.log('Soy Grupo y SubGrupo :', _infoData.group_name, '-', _infoData.sgroup_name)
  newRecord.value = {
    id: _infoData.id,
    code: _infoData.code,
    name: _infoData.name,
    namegroupselected: _infoData.group,
    namesgroupselected: _infoData.subgroup,
    namemeasureselected: _infoData.unit_of_measure,
    codereference: _infoData.codereference,
    unit_of_measure: _infoData.unit_of_measure,
    presentation: _infoData.presentation,
    percent: _infoData.percent,
    sale_value: _infoData.sale_value,
    cost: _infoData.cost,
    location: _infoData.location,
    control_id: _infoData.control_id,
    typeofproduct: _infoData.typeofproduct,
    require_scale: _infoData.require_scale,
    billable: _infoData.billable,
    group: _infoData.group,
    subgroup: _infoData.subgroup,
    division: _infoData.division,
    category: _infoData.category,
    family: _infoData.family,
    namephoto: _infoData.namephoto,
    routephoto: _infoData.routephoto,
    observations: _infoData.observations,
    cups: _infoData.cups,
    alternate_code: _infoData.alternate_code,
    cie10_code: _infoData.cie10_code,
    invima_register: _infoData.invima_register,
    units_per_packaging: _infoData.units_per_packaging,
    weight_volume: _infoData.weight_volume,
    conversion_factor: _infoData.conversion_factor,
    date_last_purchase: _infoData.date_last_purchase,
    minimum_stock: _infoData.minimum_stock,
    maximum_stock: _infoData.maximum_stock,
    profitability: _infoData.profitability,
    consumption_tax: _infoData.consumption_tax,
    listvalue1: _infoData.listvalue1,
    listvalue2: _infoData.listvalue2,
    listvalue3: _infoData.listvalue3,
    companies_id: toId(_infoData.companies_id),
    state: _infoData.state,
  }

  // ✅ Verificar que el valor llega
  console.log('Soy Registr Edit:', newRecord.value)

  showDialog.value = true
}

// 🔹 Abrir modal en modo creación
const openCreateDialog = () => {
  editMode.value = false
  newRecord.value = {
    id: null,
    code: '',
    name: '',
    namegroupselected: '',
    namesgroupselected: '',
    namemeasureselected: '',
    codereference: '',
    unit_of_measure: '',
    presentation: '',
    percent: 0,
    sale_value: 0,
    cost: 0,
    location: '',
    control_id: '',
    typeofproduct: '',
    require_scale: '',
    billable: '',
    group: '',
    subgroup: '',
    division: '',
    category: '',
    family: '',
    namephoto: '',
    routephoto: '',
    observations: '',
    cups: '',
    alternate_code: '',
    cie10_code: '',
    invima_register: '',
    units_per_packaging: 0,
    weight_volume: 0,
    conversion_factor: 0,
    date_last_purchase: '',
    minimum_stock: 0,
    maximum_stock: 0,
    profitability: 0,
    consumption_tax: 0,
    listvalue1: 0,
    listvalue2: 0,
    listvalue3: 0,
    companies_id: 0,
    state: '',
  }

  // console.log('🆕 Abriendo modal para nueva empresa')
  showDialog.value = true
}

// 🔹 Abrir confirmación de eliminación
const confirmDelete = (id: number) => {
  console.log('🛑 Confirmar eliminación del Producto ID:', id)
  recordToDelete.value = id
  nameRecordToDelete.value = infoData.value.find(c => c.id === id)?.name || ''
  showConfirmDialog.value = true
}

// 🔹 Eliminar empresa
const deleteRecord = async () => {
  if (!recordToDelete.value)
    return

  try {
    await $api(`/api/products/${recordToDelete.value}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    })
    loadInfo()
    snackbarMessage.value = '✅ Producto eliminado correctamente'
    snackbarColor.value = 'success'
  }
  catch (error) {
    console.error('❌ Error al eliminar Producto:', error)
    snackbarMessage.value = '❌ Error al eliminar Producto'
    snackbarColor.value = 'error'
  }
  finally {
    showConfirmDialog.value = false
    recordToDelete.value = null

    showSnackbar.value = false
    nextTick(() => (showSnackbar.value = true))
  }
}

const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (file)
    certificateFile.value = file
}

const validateFileSize = (v: File | File[] | null) => {
  if (!v)
    return true // Sin archivo, válido
  const file = Array.isArray(v) ? v[0] : v // Maneja array o archivo directo
  if (!file)
    return true

  return file.size < 2048000 || 'El archivo no debe superar 2MB'
}

// ✅ Computed de items filtrados
const registrosFiltrados = computed(() => {
  // 1. Si no hay búsqueda o no hay datos, devolvemos el array original (o vacío si es null/undefined)
  if (!searchQuery.value || !infoData.value?.length)
    return infoData.value ?? []

  // 2. Si pasa el filtro anterior, normalizamos la búsqueda en minúsculas
  const q = searchQuery.value.toLowerCase()

  // 3. Filtramos los registros de forma segura
  return infoData.value.filter(item =>
    Object.values(item).some(val =>
      String(val ?? '').toLowerCase().includes(q)),
  )
})

//   const file = Array.isArray(certificateFileModel.value)
//     ? certificateFileModel.value[0]
//     : certificateFileModel.value as File

//   return file?.name ?? newCompany.value.certificatename
// })

const formatCurrency = (value: number | string) => {
  const num = Number(value) || 0

  return num.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
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
const taxField = useNumericField(newRecord, 'consumption_tax')
const renField = useNumericField(newRecord, 'profitability')
const stckminField = useNumericField(newRecord, 'minimum_stocky')
const stckmaxField = useNumericField(newRecord, 'maximum_stock')
const ivaField = useNumericField(newRecord, 'percent')

// const totalField = useNumericField(newRecord, 'total_amount')
const saleslField = useNumericField(newRecord, 'sale_value')
const costField = useNumericField(newRecord, 'cost')
const factField = useNumericField(newRecord, 'conversion_factor')
const weightField = useNumericField(newRecord, 'weight_volume')
const unitxpackField = useNumericField(newRecord, 'units_per_packaging')
</script>

<template>
  <!-- <VCardText class="d-flex justify-space-between align-center flex-wrap gap-4 toolbar-header">  -->
  <VCard class="mb-1 mt-1 py-3 px-4 justify-space-berween">
    <VRow class="align-center">
      <VCol
        cols="12"
        md="2"
        class="d-flex align-left flex-column"
      >
        <h3 class="text-primary mb-2">
          Mantenimiento de Productos
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
            style="inline-size: 20em;max-inline-size: 300px;"
          />
        </VCardText>
      </VCol>

      <VCol
        cols="12"
        md="8"
        class="d-flex align-center flex-column"
      />

      <VCol
        cols="12"
        md="2"
        class="d-flex align-right justify-start mt-md-5 mt-2"
      >
        <VBtn
          rounded="pill"
          color="primary"
          variant="flat"
          block
          @click="openCreateDialog"
        >
          <template #prepend>
            <VIcon
              icon="tabler-plus"
              size="20"
            />
          </template>
          Nuevo Producto
        </VBtn>
      </VCol>
    </VRow>
  </VCard>
  <!-- <section v-if="companies && companies.length"></section> -->
  <section>
    <VCard id="grid-list">
      <VDivider />

      <VDataTable
        v-model:model-value="selectedRows"
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        density="compact"
        border-cells="true"
        show-select
        :striped-rows="true"
        :headers="headers"
        :items="infoData"
        :search="searchQuery"
        item-value="id"
        class="text-no-wrap text-body-2 grid-table"
        @update:options="updateOptions"
      >
        <template #item.id="{ item }">
          <div class="cell-wrap columna_size">
            {{ item.id }}
          </div>
        </template>

        <template #item.code="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.code }}
          </div>
        </template>

        <template #item.name="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.name }}
          </div>
        </template>

        <template #item.codereference="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.codereference }}
          </div>
        </template>

        <template #item.group_name="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.group_name }}
          </div>
        </template>

        <template #item.presentation="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.presentation }}
          </div>
        </template>

        <template #item.percent="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.percent }}
          </div>
        </template>

        <template #item.sale_value="{ item }">
          <div class="cell-wrap columna_name">
            {{ formatCurrency(item.sale_value) }}
          </div>
        </template>

        <template #item.cost="{ item }">
          <div class="cell-wrap columna_name">
            {{ formatCurrency(item.cost) }}
          </div>
        </template>

        <template #item.state="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.state }}
          </div>
        </template>

        <template #item.actions="{ item }">
          <IconBtn @click="openEditDialog(item)">
            <VIcon
              icon="tabler-edit"
              color="primary"
            />
          </IconBtn>

          <IconBtn
            :disabled="tipodeusuario === 'Operador'"
            @click="confirmDelete(item.id)"
          >
            <VIcon
              icon="tabler-trash"
              :color="tipodeusuario === 'Operador' ? 'grey' : 'error'"
            />
          </IconBtn>
        </template>

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
                <strong>{{ Math.min(currentPage * perPage, totalRecords) }}</strong>
                de <strong>{{ totalRecords }}</strong> registros
              </div>
            </VCol>
            <VCol
              cols="12"
              md="4"
              class="d-flex justify-center pagination-wrapper"
            >
              <VPagination
                v-model="page"
                :length="Math.ceil(totalRecords / perPage)"
                rounded="circle"
                size="large"
                :total-visible="5"
              />
            </VCol>
          </VRow>
        </template>
      </VDataTable>
    </VCard>

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

    <!-- 🌟 Popup Modal para nueva empresa -->
  </section>

  <VDialog
    v-model="showDialog"
    persistent
    max-width="1100px"
    attach="#app"
  >
    <VCard>
      <!-- <VCardTitle class="text-h5 bg-primary text-white py-4 px-4">Agregar nueva empresa</VCardTitle> -->
      <VCardTitle class="modal-title d-flex align-center text-h5">
        <VIcon
          icon="tabler-building"
          size="28"
          color="white"
          class="me-3"
        />
        {{ newRecord.id ? 'Actualizando un Producto' : 'Agregando un Producto' }}
        <span
          class="text-h5 font-weight-bold ml-2"
          style="color: #f7fb2d !important;"
        >
          ID: {{ String(newRecord.id || 0).padStart(8, '0') }}
        </span>
      </VCardTitle>

      <VCardText
        mb="4"
        class="pt-4 pb-2"
      >
        <VForm @submit.prevent="saveRecord">
          <VRow
            dense
            align="center"
            class="g-2"
          >
            <VCol
              cols="12"
              md="3"
              class="py-0"
            >
              <AppTextField
                v-model="newRecord.code"
                label="Código del Producto"
                autofocus
                required
                class="mb-3 text_size mt-0"
                :rules="[rules.required]"
                placeholder="Ingrese Código del Producto"
                @update:model-value="val => newRecord.code = val.toUpperCase()"
              >
                <template #prepend-inner>
                  <VIcon
                    icon="tabler-qrcode"
                    color="primary"
                    size="22"
                    class="me-2"
                  />
                </template>
              </AppTextField>
            </VCol>
            <VCol
              cols="12"
              md="9"
              class="py-0"
            >
              <AppTextField
                v-model="newRecord.name"
                label="Descripcón del Producto"
                required
                class="mb-3 text_size"
                :rules="[rules.required]"
                placeholder="Ingrese Descripción del Producto"
                @update:model-value="val => newRecord.name = val.toUpperCase()"
              >
                <template #prepend-inner>
                  <VIcon
                    icon="tabler-file-description"
                    color="primary"
                    size="22"
                    class="me-2"
                  />
                </template>
              </AppTextField>
            </VCol>
          </VRow>
          <VRow
            dense
            align="center"
            class="g-2"
          >
            <VCol
              cols="12"
              md="3"
              class="py-0"
            >
              <AppTextField
                v-model="newRecord.codereference"
                label="Código de Referencia"
                class="mb-3 text_size"
                :rules="[rules.required]"
                placeholder="Ingrese Código de Referencia"
                @update:model-value="val => newRecord.codereference = val.toUpperCase()"
              >
                <template #prepend-inner>
                  <VIcon
                    icon="tabler-qrcode"
                    color="primary"
                    size="22"
                    class="me-2"
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
                v-model="newRecord.presentation"
                label="Presentación"
                class="mb-3 text_size"
                :rules="[rules.required]"
                placeholder="Ingrese Presentación"
                @update:model-value="val => newRecord.presentation = val.toUpperCase()"
              >
                <template #prepend-inner>
                  <VIcon
                    icon="tabler-brand-codepen"
                    color="primary"
                    size="22"
                    class="me-2"
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
                v-model="ivaField.formattedValue.value"
                label="Iva (%)"
                class="mb-3 text_size"
                placeholder="Ingrese Porcentaje de Iva"
                @keypress="ivaField.onlyNumbersAndDot"
                @focus="ivaField.isFocused.value = true"
                @blur="ivaField.isFocused.value = false"
              >
                <template #prepend-inner>
                  <VIcon
                    icon="tabler-receipt-tax"
                    color="primary"
                    size="22"
                    class="me-2"
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
                v-model="unitxpackField.formattedValue.value"
                label="Unidades por Empaque"
                class="mb-3 text_size"
                placeholder="Ingrese Unidades por Empaque"
                @keypress="unitxpackField.onlyNumbersAndDot"
                @focus="unitxpackField.isFocused.value = true"
                @blur="unitxpackField.isFocused.value = false"
              >
                <template #prepend-inner>
                  <VIcon
                    icon="tabler-packages"
                    color="primary"
                    size="22"
                    class="me-2"
                  />
                </template>
              </AppTextField>
            </VCol>
          </VRow>
        </VForm>
        <VRow
          dense
          align="center"
          class="g-2"
        >
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="weightField.formattedValue.value"
              label="Peso / Volumen"
              class="mb-3 text_size"
              placeholder="Ingrese Peso / Volumen"
              @keypress="weightField.onlyNumbersAndDot"
              @focus="weightField.isFocused.value = true"
              @blur="weightField.isFocused.value = false"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-vector-bezier"
                  color="primary"
                  size="22"
                  class="me-2"
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
              v-model="factField.formattedValue.value"
              label="Factor de Conversión"
              class="mb-3 text_size"
              placeholder="Ingrese Factor de Conversión"
              @keypress="factField.onlyNumbersAndDot"
              @focus="factField.isFocused.value = true"
              @blur="factField.isFocused.value = false"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-transform"
                  color="primary"
                  size="22"
                  class="me-2"
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
              v-model="stckminField.formattedValue.value"
              label="Stock Mínimo"
              class="mb-3 text_size"
              placeholder="Ingrese Stock Máximo"
              @keypress="stckminField.onlyNumbersAndDot"
              @focus="stckminField.isFocused.value = true"
              @blur="stckminField.isFocused.value = false"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-calculator"
                  color="primary"
                  size="22"
                  class="me-2"
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
              v-model="stckmaxField.formattedValue.value"
              label="Stock Máximo"
              class="mb-3 text_size"
              placeholder="Ingrese Stock Máximo"
              @keypress="stckmaxField.onlyNumbersAndDot"
              @focus="stckmaxField.isFocused.value = true"
              @blur="stckmaxField.isFocused.value = false"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-calculator"
                  color="primary"
                  size="22"
                  class="me-2"
                />
              </template>
            </AppTextField>
          </VCol>
        </VRow>
        <VRow
          dense
          align="center"
          class="g-2"
        >
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="renField.formattedValue.value"
              label="(%) Rentabilidad"
              class="mb-3 text_size"
              placeholder="Ingrese Factor Rentabilidad"
              @keypress="renField.onlyNumbersAndDot"
              @focus="renField.isFocused.value = true"
              @blur="renField.isFocused.value = false"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-free-rights"
                  color="primary"
                  size="22"
                  class="me-2"
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
              v-model="taxField.formattedValue.value"
              label="Impoconsumo"
              class="mb-3 text_size"
              placeholder="Ingrese Impoconsumo"
              @keypress="taxField.onlyNumbersAndDot"
              @focus="taxField.isFocused.value = true"
              @blur="taxField.isFocused.value = false"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-license"
                  color="primary"
                  size="22"
                  class="me-2"
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
              v-model="saleslField.formattedValue.value"
              label="($) Valor de Venta"
              class="mb-3 text_size"
              placeholder="Ingrese Valor de Venta"
              @keypress="saleslField.onlyNumbersAndDot"
              @focus="saleslField.isFocused.value = true"
              @blur="saleslField.isFocused.value = false"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-free-rights"
                  color="primary"
                  size="22"
                  class="me-2"
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
              v-model="costField.formattedValue.value"
              label="Costo Promedio"
              class="mb-3 text_size"
              placeholder="Ingrese Costo Promedio"
              @keypress="costField.onlyNumbersAndDot"
              @focus="costField.isFocused.value = true"
              @blur="costField.isFocused.value = false"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-basket-dollar"
                  color="primary"
                  size="22"
                  class="me-2"
                />
              </template>
            </AppTextField>
          </VCol>
        </VRow>
        <VRow
          dense
          align="center"
          class="g-2"
        >
          <VCol
            cols="12"
            md="6"
            class="py-0"
          >
            <VSelect
              v-model="newRecord.namegroupselected"
              :items="grupos"
              item-title="name"
              item-value="code"
              label="Grupo de Producto:"
              :rules="[rules.required]"
              class="mb-3"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-chart-cohort"
                  color="primary"
                  size="22"
                  class="me-3"
                />
              </template>
            </VSelect>
          </VCol>
          <VCol
            cols="12"
            md="6"
            class="py-0"
          >
            <VSelect
              v-model="newRecord.namesgroupselected"
              :items="sgrupos"
              item-title="name"
              item-value="code"
              label="Sub Grupo de Producto:"
              :rules="[rules.required]"
              class="mb-3"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-chart-cohort"
                  color="primary"
                  size="22"
                  class="me-3"
                />
              </template>
            </VSelect>
          </VCol>
        </VRow>
        <VRow
          dense
          align="center"
          class="g-2"
        >
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <VSelect
              v-model="newRecord.namemeasureselected"
              :items="unidades"
              item-title="name"
              item-value="code"
              label="Unidad de Medida:"
              class="mb-3"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-ruler-measure"
                  color="primary"
                  size="22"
                  class="me-3"
                />
              </template>
            </VSelect>
          </VCol>
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <VSelect
              v-model="newRecord.control_id"
              :items="[
                { id: 'inventario', name: 'Inventario' },
                { id: 'servicio', name: 'Servicio' }]"
              item-title="name"
              item-value="id"
              label="Tipo de Control:"
              :rules="[rules.required]"
              class="mb-3"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-settings-search"
                  color="primary"
                  size="22"
                  class="me-3"
                />
              </template>
            </VSelect>
          </VCol>
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <VSelect
              v-model="newRecord.billable"
              :items="[
                { id: 'si', name: 'Si' },
                { id: 'no', name: 'No' }]"
              item-title="name"
              item-value="id"
              label="Producto Facturable:"
              class="mb-3"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-ruler-measure"
                  color="primary"
                  size="22"
                  class="me-3"
                />
              </template>
            </VSelect>
          </VCol>
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <VSelect
              v-model="newRecord.state"
              :items="[
                { id: 'activo', name: 'Activo' },
                { id: 'inactivo', name: 'Inactivo' }]"
              item-title="name"
              item-value="id"
              label="Estado:"
              :rules="[rules.required]"
              class="mb-3"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-settings-search"
                  color="primary"
                  size="22"
                  class="me-3"
                />
              </template>
            </VSelect>
          </VCol>
        </VRow>
        <VRow
          dense
          align="center"
          class="g-2"
        >
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="newRecord.location"
              label="Ubicación (Estantes)"
              class="mb-3 text_size"
              required
              :rules="[rules.required]"
              placeholder="Ingrese Ubicación del Producto"
              @update:model-value="val => newRecord.location = val.toUpperCase()"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-map-pin"
                  color="primary"
                  size="22"
                  class="me-2"
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
              v-model="newRecord.namephoto"
              label="Nombre de la Foto (Imagen)"
              class="mb-3 text_size"
              required
              :rules="[rules.required]"
              placeholder="Ingrese Nombre Imagen"
              @update:model-value="val => newRecord.namephoto = val.toUpperCase()"
            >
              <template #prepend-inner>
                <VIcon
                  icon="tabler-photo-bitcoin"
                  color="primary"
                  size="22"
                  class="me-2"
                />
              </template>
            </AppTextField>
          </VCol>
        </VRow>
      </VCardText>

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
        ¿Está seguro que desea eliminar este Producto ?<br>
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
          @click="deleteRecord"
        >
          Eliminar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
#company-list {
  .company-list-filter {
    inline-size: 12rem;
  }
}

/* Paginación circular */

/* 1. Estilos para el componente VPagination (el que tienes en el slot #bottom) */
.pagination-wrapper {
  .v-pagination__first,
  .v-pagination__item,
  .v-pagination__next,
  .v-pagination__prev,
  .v-pagination__last {
    .v-btn {
      background-color: rgb(247, 58, 206) !important;

      /* Cambia el color de los iconos de flecha y números */
      // color: #0EE920 !important;

      .v-icon {
        color: rgb(250, 253, 245) !important;
      }
    }
  }
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

.columna_name {
  display: block;
  font-size: 0.85em;
  line-height: 1.3;         /* mejora legibilidad */
  overflow-wrap: break-word;

  // max-width: 600px;         /* ancho fijo */
  white-space: normal !important; /* permite salto de línea */
  word-wrap: break-word;    /* divide palabras largas */
}

/* Evita que el resto de columnas se vean afectadas */
// .company-table :deep(td),
// .company-table :deep(th) {
//   white-space: nowrap;
// }

/* 🌟 Bordes verticales para VDataTableServer */
thead th {
  background-color: rgb(247, 58, 206) !important;
  color: white !important;
}

.v-data-table__thead th {
  color: white !important;
}

/* Quita el borde derecho en la última columna */
.grid-table :deep(.v-data-table__td:last-child),
.grid-table :deep(.v-data-table__th:last-child) {
  border-inline-end: none;
}

/* Opcional: bordes suaves inferiores */
.grid-table :deep(.v-data-table__td) {
  border-block-end: 1px solid rgba(0, 0, 0, 8%) !important;
}

.grid-table :deep(.v-data-table__wrapper) {
  overflow: visible !important;
}

.grid-table :deep(.v-data-table__td),
.grid-table :deep(.v-data-table__th) {
  border-inline-end: 1px solid rgba(var(--v-theme-on-surface), 0.15) !important;
}

/* Botón mejor alineado */
.toolbar-header .v-btn {
  block-size: 40px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 8%);
  font-weight: 500;
}

.v-overlay {
  position: fixed !important;
  z-index: 9999 !important;
}

.textarea {
  font-size: 12px !important;
  line-height: 1.4;
}

textarea {
  block-size: 80px !important;
  font-size: 0.85em !important;
}

.v-field__input {
  font-size: 0.84em !important;
}

.columna_size {
  display: block;
  font-size: 0.9em;
  line-height: 1.3;         /* mejora legibilidad */
  overflow-wrap: break-word;
  white-space: normal !important; /* permite salto de línea */
  word-wrap: break-word;    /* divide palabras largas */
}

.column_date_size {
  font-size: 0.9em;
  line-height: 1.3;         /* mejora legibilidad */

  // min-height: 56px!important;
  margin-block-start: 0 !important;
  padding-block-start: 0 !important;

  // width: 20em !important;
  white-space: normal !important; /* permite salto de línea */
}

.text-center-input input {
  cursor: pointer;
  text-align: center !important;
}

/* Forzar que el calendario de Flatpickr esté sobre el VDialog */
.flatpickr-calendar {
  z-index: 10000 !important;
}

.v-data-table thead th {
  text-transform: capitalize !important;
}

.v-data-table thead th .v-table {
  color: white !important;
}

.v-data-table-header__content {
  color: white !important;
}

.row-uniform-margin > .v-col > * {
  margin-block-start: 12px !important; /* o el valor que quieras */
}

//   .v-col {
//   display: flex;
//   align-items: center;
// }

:deep(.v-file-upload) {
  min-block-size: 120px !important;
  padding-block: 8px !important;
  padding-inline: 16px !important;
}

:deep(.v-file-upload-divider) {
  margin-block: 4px !important;
  margin-inline: 0 !important;
}
</style>
