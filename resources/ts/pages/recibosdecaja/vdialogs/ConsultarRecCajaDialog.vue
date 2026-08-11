<script lang="ts" setup>
import type { OtherExpense } from '..'

const props = withDefaults(defineProps<Props>(), {
  dialogfact: false,
  tipoDeRecibo: '',
  isFocused: false,
  dialogdetpayment: false,
  itemsdetallefact: () => [],
  totales: () => [],
  suppliers: () => [],
  otherexpenses: () => [],
  valoregreso: 0,
  totalcapturado: 0,
  valoraplicacion: 0,
  detalledeapgo: () => [],
  paymentcpt: () => [],
  details: () => [],
  titledetails: '',
})

const emit = defineEmits(['update:dialogdetpayment'])

interface Props {
  dialogfact: boolean
  dialogotrop: boolean
  tipoDeRecibo: string
  isFocused: boolean
  dialogdetpayment: boolean
  tercero?: CxPPayment // 👈 agrdialogdetpaymentegar aquí también
  itemsdetallefact: PurchaseInvoiceReport[] // 👈 Usar el tipo real en vez de []
  totales: []
  suppliers: Supplier[]
  otherexpenses: OtherExpense[]
  valoregreso: number
  totalcapturado: number
  valoraplicacion: number
  detalledeapgo: []
  paymentcpt: []
  details: []
  titledetails: string
}

const formatCurrency = (value: number | string, fractionDigits: number = 2) => {
  const num = Number(value) || 0

  return num.toLocaleString('en-US', {
    minimumFractionDigits: fractionDigits,
    maximumFractionDigits: fractionDigits,
  })
}

const closedet = () => {
  // resetFormulario()
  emit('update:dialogdetpayment', false)

  // props.dialogdetpayment.value = false

  // props.dialogotrop.value = false
}

const headers = [
  { title: 'Id', key: 'id', width: '5%' },
  { title: 'Fecha', key: 'report_date', width: 20 },
  { title: '# Factura', key: 'invoice', sortable: true, width: '10%' },
  { title: 'Prefijo', key: 'prefix', sortable: true, width: '5%' },
  { title: 'TipoDcto', key: 'invoicedcto', sortable: true, width: '5%' },
  { title: 'Concepto', key: 'concept', sortable: true, width: 30 },
  { title: 'Descripción', key: 'concept_name', sortable: true, width: '20%' },
  { title: 'Operación', key: 'calculate', sortable: true, width: '20%' },
  { title: 'Valor de Pago', key: 'payment_amount', sortable: true, width: '20%' },
]
</script>

<template>
  <VDialog
    max-width="1200px"
    :model-value="dialogdetpayment"
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
          icon="tabler-list-details"
          size="28"
          color="white"
          class="me-3"
        />
        Detalle del Documento -- {{ props.titledetails }}
        <span
          class="text-h6 font-weight-bold ml-2"
          style="color: #f7fb2d !important;"
        />
      </VCardTitle>
      <section>
        <VCard id="grid-list">
          <VDivider />
          <VDataTable
            :headers="headers"
            :items="details"
            item-value="id"
            density="compact"
            :items-per-page="-1"
            hide-default-footer
            class="products-gridc border rounded"
            :height="$vuetify.display.height < 800 ? 370 : 470"
            striped="even"
            fixed-header
          >
            <template #item.id="{ item }">
              <div class="cell-wrap text_column">
                {{ item.id }}
              </div>
            </template>

            <template #item.report_date="{ item }">
              <div class="cell-wrap text-no-wrap text_column">
                {{ item.report_date }}
              </div>
            </template>

            <template #item.invoice="{ item }">
              <div class="cell-wrap text_column text-right">
                {{ item.invoice }}
              </div>
            </template>

            <template #item.prefix="{ item }">
              <div class="cell-wrap text_column">
                {{ item.prefix }}
              </div>
            </template>

            <template #item.invoicedcto="{ item }">
              <div class="cell-wrap text_column">
                {{ item.invoicedcto }}
              </div>
            </template>

            <template #item.calculate="{ item }">
              <div class="cell-wrap text_column">
                {{ item.calculate }}
              </div>
            </template>

            <template #item.concept="{ item }">
              <div class="cell-wrap text_column">
                {{ item.concept }}
              </div>
            </template>

            <template #item.concept_name="{ item }">
              <div class="cell-wrap text_column">
                {{ item.concept_name }}
              </div>
            </template>

            <template #item.payment_amount="{ item }">
              <div class="cell-wrap text_column text-right">
                {{ formatCurrency(item.payment_amount, 2) }}
              </div>
            </template>
          </vdatatable>
        </VCard>
      </section>
      <VCardActions>
        <VSpacer />

        <VBtn
          width="100"
          min-width="0"
          color="error"
          variant="flat"
          class="mt-3"
          @click="closedet"
        >
          Cancelar
        </VBtn>
      </VCardActions>
    </vcard>
  </vdialog>
</template>

<style lang="scss">
.text_column {
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif, sans-serif;
  font-size: 0.8em;
  line-height: 1 !important;
  margin-block-start: 1 !important;
}

@media (max-width: 1400px) {
  :deep(.v-data-table) {
    font-size: 0.85em !important;
  }
}
</style>
