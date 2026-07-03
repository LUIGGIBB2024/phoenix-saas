// Interfaz que define la estructura del modelo Company
export interface Product {
  id?: number
  code: string
  name: string
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

// Interfaz para parámetros de búsqueda o paginación
export interface ProductParams {
  q?: string
  itemsPerPage?: number
  page?: number
  sortBy?: string
  orderBy?: 'asc' | 'desc'
}
