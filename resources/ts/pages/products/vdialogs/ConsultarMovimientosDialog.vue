<script lang="ts" setup>
const props = withDefaults(defineProps<Props>(), {
  dialogmovement: false,
  detailsmov: () => [],
  titledetails: '',
})

const emit = defineEmits(['update:dialogmovement'])

interface Props {
  dialogmovement: boolean
  detailsmov: []
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
  emit('update:dialogmovement', false)

  // props.dialogdetpayment.value = false

  // props.dialogotrop.value = false
}

const headers = [
  { title: 'Fecha', key: 'report_date', width: '8%' },
  { title: 'Nombre del Tercero', key: 'supplier_name', sortable: true, width: '25%' },
  { title: 'Nit', key: 'nit', sortable: true, width: '10%' },
  { title: 'NroDcto', key: 'number', sortable: true, width: '3%' },
  { title: 'Prefijo', key: 'prefix', sortable: true, width: '3%' },
  { title: 'Descripción', key: 'concept_name', sortable: true, width: 30 },
  { title: 'Bd', key: 'store', sortable: true, width: '5%' },
  { title: 'Dsct1', key: 'discount1', sortable: true, width: '8%', align: 'end' },
  { title: 'Iva', key: 'vat', sortable: true, width: '8%', align: 'end' },
  { title: 'Vlr.Venta', key: 'sale_price', sortable: true, width: '8%', align: 'end' },
  { title: 'Costo', key: 'unit_cost', sortable: true, width: '8%', align: 'end' },
  { title: 'Cantidad', key: 'amount', sortable: true, width: '8%', align: 'end' },
]
</script>

<template>
  <VDialog
    max-width="1300px"
    :model-value="dialogmovement"
    persistent
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
        Detalle del Producto -- {{ props.titledetails }}
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
            :items="detailsmov"
            item-value="id"
            density="compact"
            :items-per-page="-1"
            hide-default-footer
            class="products-gridc border rounded"
            :height="$vuetify.display.height < 800 ? 370 : 470"
            striped="even"
            fixed-header
          >
            <template #item.report_date="{ item }">
              <div class="cell-wrap text_column text-no-wrap">
                {{ item.report_date }}
              </div>
            </template>

            <template #item.number="{ item }">
              <div class="cell-wrap text_column text-no-wrap">
                {{ item.number }}
              </div>
            </template>

            <template #item.supplier_name="{ item }">
              <div class="text-no-wrap text_column">
                {{ item.supplier_name }}
              </div>
            </template>
            <template #item.nit="{ item }">
              <div class="cell-wrap text_column text-no-wrap">
                {{ item.nit }}
              </div>
            </template>

            <template #item.concept="{ item }">
              <div class="cell-wrap text_column text-no-wrap">
                {{ item.concept }}
              </div>
            </template>

            <template #item.concept_name="{ item }">
              <div class="cell-wrap text_column">
                {{ item.concept_name }}
              </div>
            </template>

            <template #item.store="{ item }">
              <div class="text_column">
                {{ item.store }}
              </div>
            </template>

            <template #item.amount="{ item }">
              <div class="cell-wrap text_column text-no-wrap">
                {{ formatCurrency(item.amount, 2) }}
              </div>
            </template>
            <template #item.discount1="{ item }">
              <div class="cell-wrap text_column text-no-wrap">
                {{ formatCurrency(item.discount1, 2) }}
              </div>
            </template>

            <template #item.vat="{ item }">
              <div class="cell-wrap text_column text-no-wrap">
                {{ formatCurrency(item.vat, 0) }}
              </div>
            </template>

            <template #item.sale_price="{ item }">
              <div class="cell-wrap text_column text-no-wrap">
                {{ formatCurrency(item.sale_price, 0) }}
              </div>
            </template>

            <template #item.unit_cost="{ item }">
              <div class="cell-wrap text_column text-no-wrap">
                {{ formatCurrency(item.unit_cost, 0) }}
              </div>
            </template>

            <!--
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
            -->
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
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
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
