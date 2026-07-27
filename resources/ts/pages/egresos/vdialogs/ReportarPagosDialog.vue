<script lang="ts" setup>
import axios from 'axios'
import { onDeactivated, onUnmounted, ref } from 'vue'
import type { CxPPayment, PurchaseInvoiceReport } from '..'

const props = withDefaults(defineProps<Props>(), {
  tipoDeEgreso: '',
  payments: () => [],
  valoraplicacion: 0,
  paymentcpt: () => [],
})

// const props = defineProps<Props>()

const emit = defineEmits(['update:modelValue', 'pago-reportado', 'close', 'closeReg', 'cerrar-todo', 'egreso-guardado'])
const tokenhijo = localStorage.getItem('auth_token')
const dialogreg = ref(false)
const idfactura = ref(0)

const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

const formatCurrency = (value: number | string, fractionDigits: number = 2) => {
  const num = Number(value) || 0

  return num.toLocaleString('en-US', {
    minimumFractionDigits: fractionDigits,
    maximumFractionDigits: fractionDigits,
  })
}

const itemsPerPage = ref(10)

const valid = ref(true)

interface Props {
  dialogfact: boolean
  tipoDeEgreso: string
  isFocused: boolean
  tercero?: CxPPayment // 👈 agregar aquí también
  itemsdetallefact: PurchaseInvoiceReport[] // 👈 Usar el tipo real en vez de []
  totales: []
  valoregreso: number
  valoraplicacion: number
  detalledeapgo: []
  paymentcpt: []
}

// 3. Variables locales
const itemDetalleFact = ref<PurchaseInvoiceReport[]>([])
const pagosTemporal = ref<DetallePago[]>([])

// 4. Asignamos el valor usando props.itemsdetallefact (SIN .value)
onMounted(() => {
  if (props.itemsdetallefact) {
    // Usamos el operador spread [...] para crear una copia limpia y no modificar la prop original
    itemDetalleFact.value = [...props.itemsdetallefact]
  }
})

// 💡 RECOMENDACIÓN IMPORTANTE:
// Como es un Diálogo (Dialog), onMounted solo se ejecuta 1 vez cuando el componente se crea.
// Si el usuario abre/cierra el diálogo con diferentes facturas, usa un 'watch' para mantenerlo actualizado:
watch(
  () => props.itemsdetallefact,
  nuevosItems => {
    if (nuevosItems)
      itemDetalleFact.value = [...nuevosItems]
  },
  { immediate: true },
)

// Si es componente normal:
onUnmounted(() => {
  pagosTemporal.value = []
})

// Si usas keep-alive:
onDeactivated(() => {
  pagosTemporal.value = []
})

function AgregarPagosDirectos(factura: PurchaseInvoiceReport) {
  const cptopago = '999'
  const valorpago = ref(Number.parseFloat(factura.valor_factura)) // 👈 Valor directo, sin ref()

  if (totalCapturado.value < props.valoregreso) {
    const saldoactual = props.valoregreso - (totalCapturado.value)

    if (saldoactual < valorpago.value)
      valorpago.value = saldoactual

    console.log('Valor Pago: 202', valorpago.value, ' Saldo Actual:', saldoactual, ' valora Factura:', factura.valor_factura, 'Aplicación:', totalCapturado.value, 'Conceptos:', props.paymentcpt)
    console.log('Items:', props.paymentcpt.length, 'items_temp:', pagosTemporal.value)

    // Opción segura con encadenamiento opcional
    const conceptosSuma = ['Abonos', 'Descuentos', 'Retenciones']
    const tienePagosEstaFactura = pagosTemporal.value.some(pago => pago.factura_id === factura.id)

    // 2. Si no tiene registros previos para esta factura y existen conceptos
    if (!tienePagosEstaFactura && props.paymentcpt?.length) {
      const nuevosPagos = props.paymentcpt.map(item => ({
        id: crypto.randomUUID(),
        factura_id: factura.id,
        numero_factura: factura.numero_factura,
        prefijo: factura.prefix,
        dctofra: factura.document_name,
        concepto: item.code,
        concepto_nombre: item.name,
        valor: 0,
        tipo_calculo: conceptosSuma.includes(item.typeofcalculation) ? 'Suma' : 'Resta',
      }))

      // 3. Añadimos los nuevos registros manteniendo los de las demás facturas
      pagosTemporal.value.push(...nuevosPagos)
    }

    // ✅ 2. Preparamos el registro para la tabla (mergeamos datos si es necesario)
    const registroActualizado = {
      ...factura,
      abonoactual: valorpago.value,
    }

    // 2. Actualizamos el ref local "itemDetalleFact" (nombre exacto)
    itemDetalleFact.value = itemDetalleFact.value.map(item =>
      item.id === factura.id ? registroActualizado : item,
    )

    // 💡 3. Buscamos el registro que coincida con la factura y el concepto '999'
    const registroExistente = pagosTemporal.value.find(
      pago => pago.factura_id === factura.id && pago.concepto === cptopago,
    )

    if (registroExistente) {
      // Si ya existe en pagosTemporal, le asignamos el valor del pago
      registroExistente.valor = valorpago.value
    }

    // console.log('Soy PagosTemporal:', pagosTemporal.value)

    // pagosTemporal.value.push({
    //   id: crypto.randomUUID(),
    //   factura_id: factura.id,
    //   numero_factura: factura.numero_factura,
    //   prefijo: factura.prefix,
    //   dctofra: factura.document_name,
    //   concepto: cptopago,
    //   concepto_nombre: 'Abonos / Pago Total',
    //   valor: valorpago,
    //   tipo_calculo: 'Suma',
    // })
  }
  else {
    console.log(console.log('Valor Pago - 220:', valorpago.value, ' Saldo Actual:', saldoactual, ' valora Factura:', factura.valor_factura, 'Aplicación:', totalCapturado.value, 'Conceptos:', props.paymentcpt))
  }
}

const totalCapturado = computed(() => {
  return pagosTemporal.value.reduce((acc, pago) => {
    const signo = pago.tipo_calculo === 'Suma' ? 1 : -1

    return acc + (pago.valor * signo)
  }, 0)
})

function cerrar() {
  emit('update:modelValue', false)
}

function guardarPago() {
  // tu lógica...
  emit('pago-reportado', { /* datos */ })
  cerrar()
}

// const valid = ref(true)
const form = ref<HTMLFormElement | null>(null)

// 🔹 Abrir modal en modo creación
const openReportDialog = () => {
  dialogfact.value = true
}

// const itemDetalleFact = ref<PurchaseInvoiceReport []>([])

const close = () => {
  // dialogfact.value = false
  emit('update:dialogfact', false)
  console.log('Enter Aqui 200')
  form.value?.resetValidation()
  pagosTemporal.value = []

  emit('close')
  emit('cerrar-todo')
}

const closeReg = () => {
  // dialogfact.value = false

  dialogreg.value = false

  const totalFacturaActual = computed(() => {
    return pagosTemporal.value.reduce((acc, pago) => {
    // ✅ Aquí SÍ va .value porque usas la variable ref directa
      if (pago.factura_id === idfactura.value) {
        const signo = pago.tipo_calculo === 'Suma' ? 1 : -1

        return acc + (pago.valor * signo)
      }

      return acc
    }, 0)
  })

  console.log('Enter Aqui 200 - :', pagosTemporal.value, 'IdFactura:', idfactura.value, 'Soy Detalle:', itemDetalleFact.value, 'TotalFactura:', totalFacturaActual.value)

  // ✅ 2. Preparamos el registro para la tabla (mergeamos datos si es necesario)
  const registroActualizado = {
    ...itemDetalleFact.value,
    abonoactual: totalFacturaActual.value,
  }

  // ✅ Actualizamos el ref desglosando el item individual
  itemDetalleFact.value = itemDetalleFact.value.map(item => {
    if (item.id === idfactura.value) {
      return {
        ...item, // 💡 Copiamos las propiedades del registro individual
        abonoactual: totalFacturaActual.value,
      }
    }

    return item
  })

  emit('closeReg')
  emit('update:dialogreg', false)
}

const handleSaveEgreso = async (egreso: any) => {
  try {
    const response = await axios.post('/api/supplierpayment', {
      ...egreso, // Ahora sí, este 'egreso' es un objeto JavaScript 100% normal
      tipo: 'FACTURAS',
      detpagos: pagosTemporal.value,
      company_id: localStorage.getItem('company_id'),
      process_year: localStorage.getItem('process_year'),
    },
    {
      headers: { Authorization: `Bearer ${tokenhijo}` },
    })

    // console.log('Soy Response.date', response.data)

    // Cierra este dialog (hijo)
    emit('update:dialogfact', false)

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

const itemSeleccionado = ref<any>(null)

// 2. El computed reacciona automáticamente al itemSeleccionado
const pagosFiltrados = computed(() => {
  if (!itemSeleccionado.value)
    return []

  return pagosTemporal.value.filter(p => p.factura_id === itemSeleccionado.value.id)
})

function OpenListaPagos(item: any) {
  itemSeleccionado.value = item
  dialogreg.value = true
  idfactura.value = item.id
}

const headers = [
  { title: 'Id', key: 'id', width: 40 },
  { title: 'Número Factura', key: 'numero_factura', width: 40 },
  { title: 'Prefijo', key: 'prefix', sortable: true, width: 95 }, // Espacio justo para "AAAA-MM-DD"
  { title: 'Tipo Docto', key: 'document_name', sortable: true, width: 40 },
  { title: 'Fecha Factura', key: 'fecha_factura', sortable: true, width: 120, align: 'center' },
  { title: 'Fecha Vcmto', key: 'fecha_vencimiento', sortable: true, width: 120, align: 'center' },
  { title: 'Días', key: 'dias_vencimiento', sortable: true, width: 50, align: 'center' },

  // {
  //   title: 'Nit/Cédula',
  //   key: 'nit',
  //   sortable: true,
  //   width: 110,
  //   cellProps: { class: 'd-none d-lg-table-cell' },
  //   headerProps: { class: 'd-none d-lg-table-cell' },
  // },
  { title: 'Saldo Factura', key: 'saldo', sortable: true, align: 'end' },
  { title: 'Valor Abonos', key: 'abonoactual', sortable: true, align: 'end' },

  // { title: 'Saldo Factura', key: 'saldo', sortable: true, align: 'end' },
  { title: 'Nit/Cédula', key: 'supplier', sortable: true, width: 90 },
  { title: 'Acciones', key: 'actions', sortable: false, width: 120, aling: 'center' }, // Espacio optimizado para tus 3 IconBtn compactos
]
</script>

<template>
  <VDialog
    max-width="1250px"
    :model-value="dialogfact"
    persistent
    @update:model-value="emit('update:dialogfact', $event)"
  >
    <VCard>
      <VCardTitle class="modal-title d-flex align-center text-h6 bg-success mb-0 mt-0">
        <VRow class="justify-space-between">
          <VCol
            cols="12"
            md="4"
            class="pa-0 d-flex flex-column"
          >
            <div class="d-flex align-center">
              <VIcon
                icon="tabler-file-text"
                size="20"
                color="white"
                class="me-2"
              />
              <span
                class="font-weight-bold"
                style="color: #510dee !important; font-size: 0.85rem;"
              >
                Facturas Pendientes:
              </span>
            </div>

            <div class="ms-6 mt-1">
              <span
                class="font-weight-bold"
                style="color: #f31f10 !important; font-size: 0.85rem;"
              >
                {{ tercero?.supplier_name }}
              </span>
            </div>
          </VCol>
          <Vcol
            cols="12"
            md="2"
            class="d-flex justify-end align-center"
          >
            <div class="cell-wrap align-end">
              <span style="font-size: 0.78rem;">Vlr Recibo: </span>
              <VChip
                size="x-small"
                variant="flat"
                class="rounded-pill"
                style="background-color: rgba(39, 245, 218, 80%) !important; font-size: 11px !important;"
              >
                {{ formatCurrency(valoregreso, 2) }}
              </VChip>
            </div>
          </Vcol>
          <Vcol
            cols="12"
            md="2"
            class="d-flex justify-end align-center"
          >
            <div class="cell-wrap align-end">
              <span style="font-size: 0.78rem;">Tot Facturas: </span>
              <VChip
                size="x-small"
                variant="flat"
                class="rounded-pill"
                style="background-color: rgba(39, 245, 218, 80%) !important; font-size: 11px !important;"
              >
                {{ formatCurrency(totales.valor_factura, 2) }}
              </VChip>
            </div>
          </Vcol>
          <Vcol
            cols="12"
            md="2"
            class="d-flex justify-end align-center"
          >
            <div class="cell-wrap align-end">
              <span style="font-size: 0.78rem;">Tot Abonos: </span>
              <VChip
                size="x-small"
                variant="flat"
                class="rounded-pill"
                style="background-color: rgba(39, 245, 218, 80%) !important; font-size: 11px !important;"
              >
                {{ formatCurrency(totales.abonos, 2) }}
              </VChip>
            </div>
          </Vcol>
          <Vcol
            cols="12"
            md="2"
            class="d-flex justify-end align-center"
          >
            <div class="cell-wrap align-end">
              <span style="font-size: 0.78rem;">Tot Saldos: </span>
              <VChip
                size="x-small"
                variant="flat"
                class="rounded-pill"
                style="background-color: rgba(39, 245, 218, 80%) !important; font-size: 11px !important;"
              >
                {{ formatCurrency(totales.saldo, 2) }}
              </VChip>
            </div>
          </Vcol>
        </VRow>
      </VCardTitle>
      <VForm class="mt-0 py-0 my-0 mx-0">
        <VRow
          dense
          align="center"
          class="g-2"
        >
          <VCol
            cols="12"
            class="my-2"
          >
            <VRow
              dense
              align="center"
              class="ma-0 px-2 pt-2"
            >
              <VCol
                cols="12"
                class="pa-0"
              >
                <section>
                  <VCard id="grid-list">
                    <VDivider />
                    <VDataTable
                      :key="itemDetalleFact.length"
                      :headers="headers"
                      :items="itemDetalleFact"
                      item-value="id"
                      density="compact"
                      :items-per-page="-1"
                      hide-default-footer
                      class="products-gridc border rounded  mx-1 my-1"
                      height="370"
                      dense
                      striped="even"
                      fixed-header
                    >
                      <template #item.id="{ item }">
                        <div class="cell-wrap columna_name_reppag">
                          {{ item.id }}
                        </div>
                      </template>

                      <template #item.numero_factura="{ item }">
                        <div class="cell-wrap columna_name_reppag">
                          {{ item.numero_factura }}
                        </div>
                      </template>

                      <template #item.prefix="{ item }">
                        <div class="cell-wrap columna_name_reppag">
                          {{ item.prefix }}
                        </div>
                      </template>

                      <template #item.document_name="{ item }">
                        <div class="cell-wrap columna_name_reppag">
                          {{ item.document_name }}
                        </div>
                      </template>

                      <template #item.fecha_factura="{ item }">
                        <div class="cell-wrap columna_name_reppag">
                          {{ item.fecha_factura }}
                        </div>
                      </template>

                      <template #item.fecha_vencimiento="{ item }">
                        <div class="cell-wrap columna_name_reppag">
                          {{ item.fecha_vencimiento }}
                        </div>
                      </template>

                      <template #item.supplier="{ item }">
                        <div class="cell-wrap columna_name_reppag">
                          {{ item.supplier }}
                        </div>
                      </template>

                      <template #item.dias_vencimiento="{ item }">
                        <div class="cell-wrap columna_name_reppag">
                          {{ formatCurrency(parseFloat(item.dias_vencimiento, 0)) }}
                        </div>
                      </template>

                      <template #item.valor_factura="{ item }">
                        <div class="cell-wrap columna_name_reppag">
                          <VChip
                            size="x-small"
                            variant="flat"
                            class="rounded-pill"
                            style="background-color: rgba(39, 245, 238, 20%) !important; font-size: 11px !important;"
                          >
                            {{ formatCurrency(parseFloat(item.valor_factura), 2) }}
                          </VChip>
                        </div>
                      </template>

                      <template #item.abonoactual="{ item }">
                        <div class="cell-wrap columna_name_reppag">
                          <VChip
                            size="x-small"
                            variant="flat"
                            class="rounded-pill"
                            style="background-color: rgba(42, 245, 39, 20%) !important; font-size: 11px !important;"
                          >
                            {{ formatCurrency(parseFloat(item.abonoactual), 2) }}
                          </VChip>
                        </div>
                      </template>

                      <template #item.saldo="{ item }">
                        <div class="cell-wrap columna_name_reppag">
                          <VChip
                            size="x-small"
                            variant="flat"
                            class="rounded-pill"
                            style="background-color: rgba(245, 125, 39, 20%) !important; font-size: 11px !important;"
                          >
                            {{ formatCurrency(parseFloat(item.saldo), 2) }}
                          </VChip>
                        </div>
                      </template>

                      <template #item.actions="{ item }">
                        <IconBtn
                          density="compact"
                          class="ma-0"
                          @click="AgregarPagosDirectos(item)"
                        >
                          <VIcon
                            icon="tabler-arrows-move"
                            color="primary"
                          />
                        </IconBtn>

                        <IconBtn
                          density="compact"
                          class="ma-0"
                          @click="OpenListaPagos(item)"
                        >
                          <VIcon
                            icon="tabler-list-check"
                            :color="item.state !== 'Activo' ? 'error' : 'success'"
                          />
                        </IconBtn>
                      </template>
                    </VDataTable>
                  </vcard>
                </section>
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
              </VCol>
            </VRow>
          </VCol>
        </VRow>
      </VForm>
      <VCardActions>
        <VSpacer />
        <div class="cell-wrap align-end">
          <span style="font-size: 0.78rem;">Valor del Egreso: </span>
          <VChip
            size="x-small"
            variant="flat"
            class="rounded-pill"
            style="background-color: rgba(39, 245, 218, 80%) !important; font-size: 11px !important;"
          >
            {{ formatCurrency(valoregreso, 2) }}
          </VChip>
        </div>
        <div class="cell-wrap align-end">
          <span style="font-size: 0.78rem;">Valor Aplicación: </span>
          <VChip
            size="x-small"
            variant="flat"
            class="rounded-pill"
            style="background-color: rgba(39, 245, 218, 80%) !important; font-size: 11px !important;"
          >
            {{ formatCurrency(totalCapturado, 2) }}
          </VChip>
        </div>
        <!--
          <VBtn
          color="blue-darken-1"
          variant="text"
          @click="abrirReportarPagos"
          >
          Listar
          </VBtn>
        -->
        <VBtn
          width="100"
          min-width="0"
          color="error"
          variant="flat"
          @click="close"
        >
          Cancelar
        </VBtn>
        <VBtn
          width="100"
          min-width="0"
          color="success"
          variant="flat"
          :disabled="props.valoregreso !== totalCapturado"
          @click="handleSaveEgreso(props.tercero)"
        >
          Guardar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Ventana para Cargar los Items de una Factura, para modificar manualmente -->

  <VDialog
    v-model="dialogreg"
    max-width="600"
    scrollable
  >
    <VCard
      class="rounded-xl mb-2 border-accent"
      elevation="3"
      border="2"
    >
      <VCardTitle class="d-flex align-center justify-space-between bg-warning text-subtitle-1">
        <span>Detalle de Pagos</span>
        <VChip
          size="small"
          color="primary"
          variant="tonal"
        >
          <!-- {{ 1000 }} -->
        </VChip>
      </VCardTitle>

      <VCardText style="max-block-size: 420px;">
        <VList
          v-if="pagosFiltrados.length"
          lines="two"
        >
          <VListItem
            v-for="pago in pagosFiltrados"
            :key="pago.id"
          >
            <VListItemTitle class="text-subtitle-2 text-primary">
              {{ pago.concepto }} - {{ pago.concepto_nombre }}
            </VListItemTitle>
            <VListItemSubtitle>
              {{ pago.numero_factura }} · {{ pago.tipo_calculo }}
            </VListItemSubtitle>

            <template #append>
              <VTextField
                v-model.number="pago.valor"
                type="number"
                density="compact"
                variant="outlined"
                hide-details
                prefix="$"
                style="max-inline-size: 160px;"
                @update:model-value="emitirCambio(pago)"
              />
              <!--
                <VBtn
                icon="tabler-trash"
                variant="text"
                color="error"
                size="small"
                class="ml-2"
                @click="eliminarPago(pago)"
                />
              -->
            </template>
            <VDivider />
          </VListItem>
        </VList>

        <VAlert
          v-else
          type="info"
          variant="tonal"
          class="mt-2"
        >
          No hay pagos registrados para esta factura.
        </VAlert>
      </VCardText>

      <VDivider />

      <VCardText class="d-flex justify-space-between align-center">
        <span class="text-subtitle-2">Total</span>
        <span class="text-h6">{{ formatCurrency(totalPagos) }}</span>
      </VCardText>

      <VCardActions>
        <VSpacer />
        <VBtn
          width="80"
          min-width="0"
          variant="flat"
          @click="closeReg"
        >
          Aplicar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style lang="css">
.columna_name_reppag {
  font-size: 0.72em !important;
  line-height: 0.85;         /* mejora legibilidad */
  overflow-wrap: break-word;
  white-space: normal; /* permite salto de línea */
  word-wrap: break-word;    /* divide palabras largas */
}

:deep(.badge-numeric) {
  display: inline-block !important;
  border: 1px solid rgba(0, 0, 0, 8%) !important;
  border-radius: 12px !important;
  background-color: rgba(0, 0, 0, 6%) !important;
  font-size: 0.85rem;
  font-weight: 600;
  padding-block: 2px !important;
  padding-inline: 10px !important;
}
</style>
