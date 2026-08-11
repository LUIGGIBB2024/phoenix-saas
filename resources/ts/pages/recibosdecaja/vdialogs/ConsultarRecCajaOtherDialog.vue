<script lang="ts" setup>
import { VIcon } from 'vuetify/components'
import { VCard } from 'vuetify/components/VCard'
import { VCol } from 'vuetify/components/VGrid'

const props = withDefaults(defineProps<Props>(), {
  tipoDeEgreso: '',
  payments: () => [],
  valoraplicacion: 0,
  paymentcpt: () => [],
})

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
    </VCard>
  </vdialog>
</template>
