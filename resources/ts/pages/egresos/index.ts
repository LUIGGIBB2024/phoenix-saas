export interface CxPPayment {
  id: number | null
  nit: string | null
  branch: string | null
  lapse: string | null
  report_date: string | null
  check_date: string | null
  delivery_date: string | null
  consecutive: number | null
  document: string | null
  supplier_name: string | null
  value_cxp: number | null
  others_payments: number | null
  observations: string | null
  payment_method: string | null
  check_number: number | null
  payment_type: 'PagosFacturas' | 'OtrosPagos' | null
  state: 'Activo' | 'Eliminado' | 'Pendiente' | null
  state01: string | null
  state02: string | null
  state03: string | null
  proyect: string | null
  sproyect: string | null
  center: string | null
  activity: string | null
  companies_id: number | null
  suppliers_id: number | null
  created_at: string | null
  updated_at: string | null
  usercreate: string | null
  userupdate: string | null
}

export interface PurchaseInvoiceReport {
  id: number
  fecha_factura: string // Formato "YYYY-MM-DD"
  fecha_vencimiento: string // Formato "YYYY-MM-DD"
  dias_vencimiento: number // Días transcurridos (positivo) o faltantes (negativo)
  prefix: string | null
  numero_factura: string
  supplier: string // NIT / Identificación
  branch: string // Sucursal
  proveedor: string // Nombre o razón social
  document_name: string
  valor_factura: string // Decimal serializado por MySQL/Laravel
  abonos: string // Decimal serializado por MySQL/Laravel
  saldo: string // Decimal serializado por MySQL/Laravel
}

export interface DetallePago {
  id: string // uuid temporal (para editar/eliminar antes de guardar)
  factura_id: number // id de la factura (pi.id)
  numero_factura: string // para mostrar en UI sin buscar de nuevo
  prefijo: string
  dctofra: string
  concepto: '001' | '002' | '999' // descuentos / retenciones / abonos
  concepto_nombre: string // "Descuentos" | "Retenciones" | "Abonos"
  valor: number
  tipo_calculo: 'Suma' | 'Resta | No Aplica'
}

export interface PaymentCpt {
  id: string
  code: string
  name: string
  calculate: string
}

export interface Supplier {
  id: number
  name: string
  nit: string
  branch: string
  dv: string
}

export interface Source {
  id: number
  code: string
  name: string
  type: string
}

export interface DocsPayments {
  id: number
  code: string
  name: string
}

export interface DocsPaymentsOthers {
  id: number
  code: string
  name: string
}

export interface recDetalle {
  code: string
  name: string
  stoe: string
  quantity: number
  vat: number
  discount: number
  cost: number
  valueprevious: number
  valuediscount: number
  subtotal: number
  total: number
}

export interface Cargue {
  id: number
  idregistro: number
  code: string
  name: string
  store: string
  quantity: number
  vat: number
  discount: number
  cost: number
  valueprevious: number
  valuediscount: number
  subtotal: number
  total: number
}

export interface OtherExpense {
  id: number
  code: string
  name: string
  factor: number
}

export interface OtherPayments {
  id: number
  consecutive: number
  document: string
  nit: string
  branch: string
  supplier_name: string
  report_date: string | Date
  internaldoc: string
  concept: string
  concept_name: string
  accounting_code: string
  center: string
  scenter: string
  proyect: string
  sproyect: string
  activity: string
  payment_amount: number
  idregister: number
  idlinea: number
  calculate: string
  suppliers_id: number
}
