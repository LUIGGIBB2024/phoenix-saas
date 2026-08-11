export type PaymentType = 'PagosFacturas' | 'OtrosPagos'
export type PaymentState = 'Activo' | 'Eliminado' | 'Pendiente'

export interface CxCPayment {
  id: number
  nit: string | null
  branch: string | null
  lapse: string | null
  report_date: string | null // Usar Date | null si manejas objetos Date nativos
  consecutive: number | null
  document: string | null
  customer_name: string | null
  value_cxc: number | null
  customer_balances: number | null
  observations: string | null
  check_number: number | null
  payment_type: PaymentType | null
  state: PaymentState | null
  state01: string | null
  state02: string | null
  state03: string | null
  proyect: string | null
  sproyect: string | null
  center: string | null
  activity: string | null
  customers_id: number | null
  companies_id: number | null
  created_at: string // Usar Date si el ORM parsea el timestamp a Date
  updated_at: string // Usar Date si el ORM parsea el timestamp a Date
  usercreate: string | null
  userupdate: string | null
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

export type CustomerSex = 'Masculino' | 'Femenino' | 'Otro'
export type CustomerState = 'Activo' | 'Inactivo'
export type CustomerCurrency = 'Pesos' | 'Dólares'
export type YesNoFlag = 'Si' | 'No'

export interface Customer {
  id: number
  nit: string | null
  branch: string | null
  dv: string | null
  patient_id: string | null
  code: string | null
  name: string | null
  firstname: string | null
  lastname: string | null
  comercial_name: string | null
  address: string | null
  phone: string | null
  email: string | null
  nit_representative: string | null
  contact_phone: string | null
  name_contact: string | null
  email_contact: string | null
  health_contract_number: string | null
  health_policy_number: string | null
  credit_quota: number | null
  deadline_days: number | null
  point: number | null
  accumulated_points: number | null
  birthday: string | null // Cambiar a Date | null si tu ORM maneja objetos Date
  last_purchase_date: string | null
  creation_date: string | null
  provider_code: string | null
  latitude: string | null
  longitude: string | null
  economic_activity: string | null
  zip_code: string | null
  business_registration: string | null
  sales_account: string | null
  center: string | null
  scenter: string | null
  health_service_coverage_id: number | null
  health_payment_method_id: number | null
  branch_id: number | null
  route_id: number | null
  zone_id: number | null
  type_id: number | null
  neighborhood_id: number | null
  price_list_id: number | null
  municipalities_id: number | null
  sellers_id: number | null
  type_document_identification_id: number | null
  companies_id: number | null
  type_regime_id: number | null
  type_liability_id: number | null
  sex: CustomerSex | null
  state: CustomerState | null
  typeofcurrency: CustomerCurrency | null
  retesource: YesNoFlag | null
  reteiva: YesNoFlag | null
  reteica: YesNoFlag | null
  declare_income: YesNoFlag | null
  control_points: YesNoFlag | null
  capture_signature: YesNoFlag | null
  created_at: string // Cambiar a Date si parseas el timestamp
  updated_at: string // Cambiar a Date si parseas el timestamp
  usercreate: string | null
  userupdate: string | null
}

export type SalesInvoiceState = 'Activo' | 'Eliminado' | 'Pendiente'
export type SalesInvoiceType = 'Contado' | 'Crédito'

export interface SalesInvoice {
  id: number
  date_issue: string | null
  expiration_date: string | null
  entry_date: string | null
  departure_date: string | null
  number: number | null
  prefix: string | null
  document_name: string | null
  customer: string | null
  branch: string | null
  patient_id: string | null
  client_name: string | null
  subtotal: number | null
  discounts: number | null
  vatvalue: number | null
  retefuente: number | null
  reteiva: number | null
  reteica: number | null
  impoconsumo: number | null
  products_discount: number | null
  additional_discounts: number | null
  exempt_sales: number | null
  taxed_sales: number | null
  additional_value: number | null
  cost_of_sale: number | null
  payment_value: number | null
  health_copays: number | null
  health_advances: number | null
  health_moderator_fee: number | null
  tip: number | null
  hours: number | null
  minutes: number | null
  total_items: number | null
  total_sale: number | null
  cufe: string | null
  observations: string | null
  plate: string | null
  room: string | null
  purchase_orders: string | null
  document_number: string | null
  property: string | null
  authorization: string | null
  type_operation: string | null
  scenery: string | null
  conveyor: string | null
  table: string | null
  order: number | null
  seller: string | null
  route: string | null
  zone: string | null
  typecustomer: string | null
  box: string | null
  atm: string | null
  list: string | null
  proyect: string | null
  sproyect: string | null
  center: string | null
  activity: string | null
  state: SalesInvoiceState | null
  type: SalesInvoiceType | null
  companies_id: number | null
  payment_methods_id: number | null
  payment_forms_id: number | null
  created_at: string
  updated_at: string
  usercreate: string | null
  userupdate: string | null
}
