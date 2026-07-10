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
const codegroup = ref('')

const inputRef = ref<HTMLInputElement | null>(null)

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files)
    archivos.value = Array.from(target.files)
}
const tipodeusuario = localStorage.getItem('tipo_de_usuario')
const process_year = ref(localStorage.getItem('process_year'))

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
export interface InventoryBalance {
  id?: number
  year: string
  code: string
  name: string
  store: string
  batch: string
  group: string
  cost: number
  cost00: number
  cost01: number
  cost02: number
  cost03: number
  cost04: number
  cost05: number
  cost06: number
  cost07: number
  cost08: number
  cost09: number
  cost10: number
  cost11: number
  cost12: number
  lastcost: number
  quantity: number
  quantity1: number
  companies_id: number
  products_id: number
}

const headers = [
  { title: '#', key: 'id' },
  { title: 'Codígo', key: 'code', sortable: true, width: '10%' },
  { title: 'Descripcion del Producto ', key: 'product_name', sortable: true, width: '40%' },
  { title: 'Bodega', key: 'store', sortable: true, width: '10%' },
  { title: 'lote', key: 'batch', sortable: true },
  { title: 'Grupo', key: 'group_name', sortable: true, width: '20%' },
  { title: 'Existencia', key: 'quantity', sortable: true, align: 'end' },
  { title: 'Costo', key: 'cost', sortable: true, align: 'end' },
  { title: 'Último Costo', key: 'lastcost', sortable: true, width: '10%', align: 'end' },
  { title: 'Inventario Valorizado', key: 'subtotal', sortable: true, width: '20%', align: 'end' },
  { title: 'Acciones', key: 'actions', sortable: false },
]

// --- 🔹 Modal y formulario de creación ---
const showDialog = ref(false)
const editMode = ref(false) // 👈 false = crear, true = editar

const newRecord = ref<InventoryBalance>({
  id: 0,
  year: '',
  code: '',
  name: '',
  store: '',
  batch: '',
  group: '',
  cost: 0,
  cost00: 0,
  cost01: 0,
  cost02: 0,
  cost03: 0,
  cost04: 0,
  cost05: 0,
  cost06: 0,
  cost07: 0,
  cost08: 0,
  cost09: 0,
  cost10: 0,
  cost11: 0,
  cost12: 0,
  lastcost: 0,
  quantity: 0,
  quantity1: 0,
  companies_id: 0,
  products_id: 0,
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
    // Se abre el diálogo en modo creación → limpiar los campos de saldos de inventario
    newRecord.value = {
      id: 0,
      year: '',
      code: '',
      name: '',
      store: '',
      batch: '',
      group: '',
      cost: 0,
      cost00: 0,
      cost01: 0,
      cost02: 0,
      cost03: 0,
      cost04: 0,
      cost05: 0,
      cost06: 0,
      cost07: 0,
      cost08: 0,
      cost09: 0,
      cost10: 0,
      cost11: 0,
      cost12: 0,
      lastcost: 0,
      quantity: 0,
      quantity1: 0,
      companies_id: 0,
      products_id: 0,
    }
  }
})

const loadInfo = async () => {
  try {
    const response = await axios.get('/api/getbalances', {
      params: {
        q: searchQuery.value,
        itemsPerPage: itemsPerPage.value,
        page: page.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        company_id: localStorage.getItem('company_id'),
        process_year: localStorage.getItem('process_year'),
      },
      headers: { Authorization: `Bearer ${token}` },
    })

    responseData.value = response.data

    totalinventory.value = response.data.totalinventory

    grupos.value = response.data.grupos

    // sgrupos.value = response.data.sgrupos
    // unidades.value = response.data.unidades

    // console.log('Soy Grupos :', grupos.value)
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
      ? `/api/balances/${newRecord.value.id}`
      : '/api/balances'

    const { data } = await axios.post(url, formData)

    // 🔍 1. Confirmar qué llega del backend
    console.log('1. data.products desde backend:', data.products.unit_of_measure)

    // 🔎 Buscamos los nombres correspondientes a los códigos seleccionados
    const grupoSeleccionado = grupos.value.find(
      g => String(g.code).trim() === String(newRecord.value.group).trim(),
    )

    // 🧩 Mergeamos el producto crudo del backend con los nombres frescos
    const productoActualizado = {
      ...data.products,
      group_name: grupoSeleccionado?.name ?? null,
      product_name: newRecord.value.name ?? null,
      subtotal: newRecord.value.quantity * newRecord.value.cost,
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
  certificateFileModel.value = null // Lo mantengo por si manejas adjuntos en el componente

  newRecord.value = {
    id: _infoData.id,
    year: _infoData.year,
    code: _infoData.code,
    name: _infoData.product_name, // 👈 Asegúrate de que el backend envíe este campo
    store: _infoData.store,
    batch: _infoData.batch,
    group: _infoData.group,
    cost: _infoData.cost,
    cost00: _infoData.cost00,
    cost01: _infoData.cost01,
    cost02: _infoData.cost02,
    cost03: _infoData.cost03,
    cost04: _infoData.cost04,
    cost05: _infoData.cost05,
    cost06: _infoData.cost06,
    cost07: _infoData.cost07,
    cost08: _infoData.cost08,
    cost09: _infoData.cost09,
    cost10: _infoData.cost10,
    cost11: _infoData.cost11,
    cost12: _infoData.cost12,
    lastcost: _infoData.lastcost,
    quantity: _infoData.quantity,
    quantity1: _infoData.quantity1,
    companies_id: _infoData.companies_id,
    products_id: _infoData.products_id,
  }
  codegroup.value = _infoData.codegroup
  showDialog.value = true
}

// ✅ Verificar que el valor llega
// console.log('certificatename:', newProduct.value.certificatename)

// 🔹 Abrir modal en modo creación
const openCreateDialog = () => {
  editMode.value = false
  newRecord.value = {
    id: null,
    year: '',
    code: '',
    name: '', // 👈 Asegúrate de inicializar el nombre también
    store: '',
    batch: '',
    group: '',
    cost: 0,
    cost00: 0,
    cost01: 0,
    cost02: 0,
    cost03: 0,
    cost04: 0,
    cost05: 0,
    cost06: 0,
    cost07: 0,
    cost08: 0,
    cost09: 0,
    cost10: 0,
    cost11: 0,
    cost12: 0,
    lastcost: 0,
    quantity: 0,
    quantity1: 0,
    companies_id: 0,
    products_id: 0,
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

const registrosFiltrados = computed(() => {
  if (!searchQuery.value || !infoData.value?.length)
    return infoData.value ?? []
  const q = searchQuery.value.toLowerCase()

  return infoData.value.filter(item =>
    Object.values(item).some(val =>
      String(val ?? '').toLowerCase().includes(q),
    ),
  )
})

// 2. El cálculo del Total corregido
// NOTA: Asegúrate que "totalinventory" se escriba exactamente así en tus datos.
const totalinventory = computed(() => {
  return registrosFiltrados.value.reduce((acc, item) => {
    // Si tus datos usan camelCase o snake_case, cámbialo aquí (ej: item.total_inventory)
    const valor = Number(item.subtotal) || 0

    return acc + valor
  }, 0)
})

// console.log('Soy Total Inventarios: ', totalinventory.value)

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
// const totalField = useNumericField(newRecord, 'total_amount')
const costField = useNumericField(newRecord, 'cost')
const quantityField = useNumericField(newRecord, 'quantity')
const cost00Field = useNumericField(newRecord, 'cost00')
const cost01Field = useNumericField(newRecord, 'cost01')
const cost02Field = useNumericField(newRecord, 'cost02')
const cost03Field = useNumericField(newRecord, 'cost03')
const cost04Field = useNumericField(newRecord, 'cost04')
const cost05Field = useNumericField(newRecord, 'cost05')
const cost06Field = useNumericField(newRecord, 'cost06')
const cost07Field = useNumericField(newRecord, 'cost07')
const cost08Field = useNumericField(newRecord, 'cost08')
const cost09Field = useNumericField(newRecord, 'cost09')
const cost10Field = useNumericField(newRecord, 'cost10')
const cost11Field = useNumericField(newRecord, 'cost11')
const cost12Field = useNumericField(newRecord, 'cost12')
const previousField = useNumericField(newRecord, 'previous_balance')
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
        <h4 class="text-primary mb-2">
          Saldos de Inventarios
          <span>
            :(<strong class="text-success">{{ process_year }}</strong>)
          </span>
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
        <!--
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
          Ingresar Saldos
          </VBtn>
        -->
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
        :items="registrosFiltrados"
        item-value="id"
        class="text-no-wrap text-body-2 grid-table"
        :cell-props="cellProps"
        :header-props="headerProps"
      >
        <template #header.subtotal="{ column }">
          <div class="th-center text-white">
            Inventario<br>Valorizado
          </div>
        </template>
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

        <template #item.product_name="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.product_name }}
          </div>
        </template>

        <template #item.store="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.store }}
          </div>
        </template>

        <template #item.group_name="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.group_name }}
          </div>
        </template>

        <template #item.percent="{ item }">
          <div class="cell-wrap columna_name">
            {{ item.percent }}
          </div>
        </template>

        <template #item.quantity="{ item }">
          <div class="cell-wrap columna_name">
            {{ formatCurrency(item.quantity) }}
          </div>
        </template>

        <template #item.cost="{ item }">
          <div class="cell-wrap columna_name">
            {{ formatCurrency(item.cost) }}
          </div>
        </template>

        <template #item.lastcost="{ item }">
          <div class="cell-wrap columna_name">
            {{ formatCurrency(item.lastcost) }}
          </div>
        </template>

        <template #item.subtotal="{ item }">
          <div class="cell-wrap columna_name">
            {{ formatCurrency(item.subtotal) }}
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
            <VCol
              cols="12"
              md="4"
            >
              <div class="text-caption text-medium-emphasis ps-4 text-end">
                Total Inventarios $:
                <strong class="text-primary">{{ formatCurrency(totalinventory) }}</strong>
                <!--
                  <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                  <strong>{{ Math.min(currentPage * perPage, totalInvoices) }}</strong>
                  de <strong>{{ totalInvoices }}</strong> registros
                -->
              </div>
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
      <VCardTitle class="modal-title d-flex align-center text-h6">
        <VIcon
          icon="tabler-building"
          size="28"
          color="white"
          class="me-3"
        />
        {{ newRecord.id ? 'Actualizando Saldos' : 'Agregando Saldo' }}
        <span
          class="text-h6 font-weight-bold ml-2"
          style="color: #f7fb2d !important;"
        >
          ID: {{ String(newRecord.id || 0).padStart(8, '0') }} / {{ newRecord.name }}
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
                readonly
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
                readonly
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
                v-model="quantityField.formattedValue.value"
                label="Cantidad Actual"
                class="mb-3 text_size"
                :rules="[rules.required]"
                placeholder="Ingrese Cantidad Actual"
                :disabled="tipodeusuario === 'Operador'"
                @keypress="quantityField.onlyNumbersAndDot"
                @focus="quantityField.isFocused.value = true"
                @blur="quantityField.isFocused.value = false"
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
                v-model="costField.formattedValue.value"
                label="Costo Promedio"
                class="mb-3 text_size"
                placeholder="Ingrese Costo Promedio"
                :disabled="tipodeusuario === 'Operador'"
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
            <VCol
              cols="12"
              md="3"
              class="py-0"
            >
              <AppTextField
                v-model="previousField.formattedValue.value"
                label="Cantidad Inicial"
                class="mb-3 text_size"
                placeholder="Ingrese Cantidad Inicial"
                :disabled="tipodeusuario === 'Operador'"
                @keypress="previousField.onlyNumbersAndDot"
                @focus="previousField.isFocused.value = true"
                @blur="previousField.isFocused.value = false"
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
                v-model="cost00Field.formattedValue.value"
                label="Costo Inicial"
                class="mb-3 text_size"
                placeholder="Ingrese Costo Inicial"
                :disabled="tipodeusuario === 'Operador'"
                @keypress="cost00Field.onlyNumbersAndDot"
                @focus="cost00Field.isFocused.value = true"
                @blur="cost00Field.isFocused.value = false"
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
              v-model="cost01Field.formattedValue.value"
              label="Costo de Enero"
              class="mb-3 text_size"
              placeholder="Ingrese Costo de Enero"
              @keypress="cost01Field.onlyNumbersAndDot"
              @focus="cost01Field.isFocused.value = true"
              @blur="cost01Field.isFocused.value = false"
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
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="cost02Field.formattedValue.value"
              label="Costo de Febrero"
              class="mb-3 text_size"
              placeholder="Ingrese Costo Febrero"
              @keypress="cost02Field.onlyNumbersAndDot"
              @focus="cost02Field.isFocused.value = true"
              @blur="cost02Field.isFocused.value = false"
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
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="cost03Field.formattedValue.value"
              label="Costo de Marzo"
              class="mb-3 text_size"
              placeholder="Ingrese Costo de Marzo"
              @keypress="cost03Field.onlyNumbersAndDot"
              @focus="cost03Field.isFocused.value = true"
              @blur="cost03Field.isFocused.value = false"
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
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="cost04Field.formattedValue.value"
              label="Costo de Abril"
              class="mb-3 text_size"
              placeholder="Ingrese Costo de Abril"
              @keypress="cost04Field.onlyNumbersAndDot"
              @focus="cost04Field.isFocused.value = true"
              @blur="cost04Field.isFocused.value = false"
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
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="cost05Field.formattedValue.value"
              label="Costo Mayo"
              class="mb-3 text_size"
              placeholder="Ingrese Costo Mayo"
              @keypress="cost05Field.onlyNumbersAndDot"
              @focus="cost05Field.isFocused.value = true"
              @blur="cost05Field.isFocused.value = false"
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
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="cost06Field.formattedValue.value"
              label="Costo de Junio"
              class="mb-3 text_size"
              placeholder="Ingrese Costo Junio"
              @keypress="cost06Field.onlyNumbersAndDot"
              @focus="cost06Field.isFocused.value = true"
              @blur="cost06Field.isFocused.value = false"
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
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="cost07Field.formattedValue.value"
              label="Costo de Julio"
              class="mb-3 text_size"
              placeholder="Ingrese Costo Julio"
              @keypress="cost07Field.onlyNumbersAndDot"
              @focus="cost07Field.isFocused.value = true"
              @blur="cost07Field.isFocused.value = false"
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
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="cost08Field.formattedValue.value"
              label="Costo de Agosto"
              class="mb-3 text_size"
              placeholder="Ingrese Costo Agosto"
              @keypress="cost08Field.onlyNumbersAndDot"
              @focus="cost08Field.isFocused.value = true"
              @blur="cost08Field.isFocused.value = false"
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
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="cost09Field.formattedValue.value"
              label="Costo de Septiembre"
              class="mb-3 text_size"
              placeholder="Ingrese Costo Septiembre"
              @keypress="cost09Field.onlyNumbersAndDot"
              @focus="cost09Field.isFocused.value = true"
              @blur="cost09Field.isFocused.value = false"
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
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="cost10Field.formattedValue.value"
              label="Costo de Octubre"
              class="mb-3 text_size"
              placeholder="Ingrese Costo Octubre"
              @keypress="cost10Field.onlyNumbersAndDot"
              @focus="cost10Field.isFocused.value = true"
              @blur="cost10Field.isFocused.value = false"
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
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="cost11Field.formattedValue.value"
              label="Costo de Noviembre"
              class="mb-3 text_size"
              placeholder="Ingrese Costo Noviembre"
              @keypress="cost11Field.onlyNumbersAndDot"
              @focus="cost11Field.isFocused.value = true"
              @blur="cost11Field.isFocused.value = false"
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
          <VCol
            cols="12"
            md="3"
            class="py-0"
          >
            <AppTextField
              v-model="cost12Field.formattedValue.value"
              label="Costo de Diciembre"
              class="mb-3 text_size"
              placeholder="Ingrese Costo Diciembre"
              @keypress="cost12Field.onlyNumbersAndDot"
              @focus="cost12Field.isFocused.value = true"
              @blur="cost12Field.isFocused.value = false"
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
