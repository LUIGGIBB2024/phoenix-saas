<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GeneralDocument;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\InvoiceDetail;
use App\Models\MiscellaneousItem;
use App\Models\Municipality;
use App\Models\PaymentMethod;
use App\Models\PriceList;
use App\Models\SalesInvoice;
use App\Models\Seller;
use App\Models\TypeDocumentIdentification;
use App\Models\TypeLiability;
use App\Models\TypeRegime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SalesInvoiceController extends Controller
{
    public function getInfo(Request $request): JsonResponse
    {
        $process_year = $request->input('process_year');
        $companies_id = $request->input('company_id');
        $list_id = $request->input('list_id');

        $query = InventoryBalance::select(
            'inventory_balances.id',
            'year',
            'inventory_balances.code',
            'store',
            'batch',
            'inventory_balances.cost',
            'lastcost',
            'cost00',
            'cost01',
            'cost02',
            'cost03',
            'cost04',
            'cost05',
            'cost06',
            'cost07',
            'cost08',
            'cost09',
            'cost10',
            'cost11',
            'cost12',
            'inventory_balances.quantity',
            'quantity1',
            'previous_balance',
            'inventory_balances.companies_id',
            'inventory_balances.products_id',
        )
            // Usamos un alias 'm' para solucionar el problema del guion medio y escribir menos código
            ->selectRaw('TRIM(m.name) as product_name, TRIM(n.name) as group_name, p.price, q.name measure_name, round(inventory_balances.cost*inventory_balances.quantity,2) as subtotal, m.percent')
            ->leftJoin('products as m', function ($join) use ($companies_id) {
                $join->on('m.id', '=', 'inventory_balances.products_id')
                    ->where('m.companies_id', '=', $companies_id);
            })
            ->leftJoin('miscellaneous_items as n', function ($join) {
                $join->on('n.code', '=', 'm.group')
                    ->where('n.miscellaneous_id', '=', 4);
            })
            ->leftJoin('miscellaneous_items as o', function ($join) {
                $join->on('o.code', '=', 'm.unit_of_measure')
                    ->where('n.miscellaneous_id', '=', 13);
            })
            ->leftJoin('price_details as p', function ($join) use ($companies_id) {
                $join->on('p.products_id', '=', 'inventory_balances.products_id')
                    ->where('p.companies_id', '=', $companies_id);
            })
            ->leftJoin('unit_measures as q', function ($join) {
                $join->on('q.code', '=', 'm.unit_of_measure');
            })
            // ->leftJoin('miscellaneous_items as n', function ($join) {
            //     $join->on('n.code', '=', 'products.subgroup')
            //         ->where('n.miscellaneous_id', '=', 5);
            // })
            // ->leftJoin('unit_measures as o', function ($join) {
            //     $join->on('o.code', '=', 'products.unit_of_measure');
            // })
            // Aseguramos que el filtro del WHERE use el alias o especifique la tabla correcta
            ->where('inventory_balances.companies_id', $companies_id)
            ->where('inventory_balances.year', $process_year)
            ->orderBy('m.name')
            ->get();

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $query_methods_pay = PaymentMethod::whereIn('code', ['10', '20', '48', '49', '31'])->orderBy('name')->get();

        $query_zonas    = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 8)->where('companies_id', $companies_id)->orderBy('name')->get();
        $query_rutas    = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 9)->where('companies_id', $companies_id)->orderBy('name')->get();
        $query_typecust    = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 10)->where('companies_id', $companies_id)->orderBy('name')->get();
        $query_barrios    = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 18)->where('companies_id', $companies_id)->orderBy('name')->get();
        $query_ciudades   = Municipality::select('id', 'code', 'name')->orderBy('name')->get();

        // $query_sgrupos   = MiscellaneousItem::select('code', 'name')->where('miscellaneous_id', 5)->where('companies_id', $companies_id)->orderBy('name')->get();        
        // $query_unidades  = UnitMeasur::select('code', 'name')->orderBy('name')->get();

        $query_regimen      = TypeRegime::select('id', 'name')->get();
        $query_typedocument = TypeDocumentIdentification::select('id', 'name', 'code', 'code_show')->get();
        $query_list         = PriceList::select('id', 'code', 'name')->where('companies_id', $companies_id)->get();
        $query_sellers      = Seller::select('id', 'code', 'name')->where('companies_id', $companies_id)->get();
        $query_respfiscal   = TypeLiability::select('id', 'code', 'name')->get();

        $query_grupos = MiscellaneousItem::select(DB::raw('TRIM(code) as code'), DB::raw('TRIM(name) as name'))->where('miscellaneous_id', 4)->where('companies_id', $companies_id)
            ->orderBy('name')
            ->get();

        $query_sgrupos = MiscellaneousItem::select(DB::raw('TRIM(code) as code'), DB::raw('TRIM(name) as name'))->where('miscellaneous_id', 5)->where('companies_id', $companies_id)
            ->orderBy('name')
            ->get();

        $query_cli = Customer::select(
            'customers.id',
            'nit',
            'branch',
            'dv',
            'patient_id',
            'customers.code',
            'provider_code',
            'customers.name',
            'firstname',
            'lastname',
            'comercial_name',
            'address',
            'phone',
            'email',
            'latitude',
            'longitude',
            'zip_code',
            'nit_representative',
            'contact_phone',
            'name_contact',
            'email_contact',
            'health_contract_number',
            'health_policy_number',
            'credit_quota',
            'deadline_days',
            'point',
            'accumulated_points',
            'birthday',
            'last_purchase_date',
            'creation_date',
            'economic_activity',
            'business_registration',
            'sales_account',
            'center',
            'scenter',
            'health_service_coverage_id',
            'health_payment_method_id',
            'branch_id',
            'route_id',
            'zone_id',
            'type_id',
            'neighborhood_id',
            'price_list_id',
            'municipalities_id',
            'sellers_id',
            'type_document_identification_id',
            'companies_id',
            'type_regime_id',
            'type_liability_id',
            'sex',
            'state',
            'typeofcurrency',
            'retesource',
            'reteiva',
            'reteica',
            'declare_income',
            'control_points',
            'capture_signature'
        )
            // Usamos un alias 'm' para solucionar el problema del guion medio y escribir menos código
            ->selectRaw('m.name as city_name')
            ->leftJoin('municipalities as m', function ($join) {
                $join->on('m.id', '=', 'customers.municipalities_id');
            })
            // ->leftJoin('miscellaneous_items as n', function ($join) {
            //     $join->on('n.code', '=', 'products.subgroup')
            //         ->where('n.miscellaneous_id', '=', 5);
            // })
            // ->leftJoin('unit_measures as o', function ($join) {
            //     $join->on('o.code', '=', 'products.unit_of_measure');
            // })
            // Aseguramos que el filtro del WHERE use el alias o especifique la tabla correcta
            ->where('customers.companies_id', $companies_id)
            ->orderBy('customers.name')
            ->get();

        $balances = $query;
        $customers = $query_cli;

        return response()->json([
            'balances' =>       $balances,
            'customers' =>      $customers,
            'payment_methods' => $query_methods_pay,
        ]);
    }

    public function store(Request $request)
    {
        $companies_id   = $request->input('company_id');
        $customers      = $request->factura['cliente'] ?? null;
        $items          = $request->factura['items'] ?? null;
        $zoneid         = $request->factura['cliente']['zone_id'] ?? null;
        $routeid        = $request->factura['cliente']['route_id'] ?? null;
        $typecustomerid = $request->factura['cliente']['type_id'] ?? null;
        $branch         = $request->factura['cliente']['branch'] ?? null;
        $sellerid       = $request->factura['cliente']['sellers_id'] ?? null;

        $numeroDeRegistros = is_array($items) ? count($items) : 0;

        $query_zonas        = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 8)->where('companies_id', $companies_id)->where('id', $zoneid)->get();
        $query_rutas        = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 9)->where('companies_id', $companies_id)->where('id', $routeid)->get();
        $query_typecust     = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 10)->where('companies_id', $companies_id)->where('id', $typecustomerid)->get();
        $query_sellers      = Seller::select('id', 'code', 'name')->where('companies_id', $companies_id)->where('id', $sellerid)->get();

        $tipodcumento = $request->input('tipo_documento') ?? null;
        $paymentmethods = $request->input('payment_methods') ?? null;
        $tipodcto    = (trim($tipodcumento) == 'contado') ? 'Factura de Contado' : 'Factura Crédito';
        $numerofactura = 0;
        $nit = $request->factura['cliente']['nit'] ?? null;
        $price_list_id = $request->factura['cliente']['price_list_id'] ?? null;
        $list_code = PriceList::where('id', $price_list_id)->value('code') ?? null;

        $documento = GeneralDocument::all()
            ->where('companies_id', $companies_id)
            ->where('type', 'Clientes')
            ->where('typedocument1', 'Facturas')
            ->where('typedocument4', $tipodcto)
            ->where('state', 'Activo')
            ->first();
        $prefijo = "";
        if ($documento) {

            // Incrementamos el campo 'consecutive' en 1 en la base de datos y en el objeto actual
            $documento->increment('consecutive');

            // Asignamos el valor actualizado a tu variable
            $numerofactura = $documento->consecutive;
        } else {
            // Manejo de error en caso de que no se encuentre el documento configurado
            throw new \Exception("No se encontró una configuración de documento activo para los parámetros internos.");
        }

        $document_name = trim($documento->name) ?? null;
        try {
            //dd($item);
            $reg_fact       = SalesInvoice::updateOrCreate(
                ['number' => $numerofactura, 'prefix' => $prefijo, 'customer' => $nit, 'branch' => $branch, 'companies_id' => $companies_id],
                [
                    'date_issue' => $request->factura['fechaFactura'] ?? null,
                    'expiration_date' => $request->factura['fechaVencimiento'] ?? null,
                    'number' => $numerofactura,
                    'prefix' => $prefijo,
                    'document_name' => $document_name,
                    'customer' => $nit,
                    'client_name' => $request->factura['cliente']['name'] ?? null,
                    'subtotal' => $request->factura['subtotalBruto'] ?? 0,
                    'discounts' => $request->factura['descuentoGlobal'] ?? 0,
                    'total_sale' => $request->factura['total'] ?? 0,
                    'cost_of_sale' => $request->factura['costoVenta'] ?? 0,
                    'products_discount' => $request->factura['descuentoItemsTotal'] ?? 0,
                    'additional_discounts' => $request->factura['descuentoAdicional'] ?? 0,
                    'vatvalue' => $request->factura['iva'] ?? 0,
                    'retefuente' => 0,
                    'reteiva' =>  0,
                    'reteica' =>  0,
                    'impoconsumo' => 0,
                    'cufe' => "",
                    'list' => $list_code,
                    'zone' => $query_zonas->first()->code ?? null,
                    'route' => $query_rutas->first()->code ?? null,
                    'typecustomer' => $query_typecust->first()->code ?? null,
                    'seller' => $query_sellers->first()->code ?? null,
                    'companies_id' => $companies_id,
                    'observations' => $request->factura['observaciones'] ?? null,
                    'type' => trim($tipodcumento) == 'contado' ? 'Contado' : 'Crédito',
                    'total_items' => $numeroDeRegistros,
                    'payment_methods_id' => $paymentmethods,
                    'exempt_sales' => 0,
                    'taxed_sales' => 0,
                    'additional_value' => 0,
                    'payment_value' => 0,
                    'health_copays' => 0,
                    'health_advances' => 0,
                    'health_moderator_fee' => 0,
                    'proyect' => '',
                    'sproyect' => '',
                    'center' => '',
                    'activity' => '',
                    'state' => 'Activo',
                ]
            );
        } catch (\Exception $ex) {
            return response()->json(

                [
                    'status'   => '404 OK',
                    'msg'      => 'Error en la actualización de la factura: ' . $numerofactura,
                    'error' => $ex,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $sales_invoice_id = $reg_fact->id;

        $row = 0;

        foreach ($items as $item) {
            try {
                //dd($item);
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                //////////          Procesamdo el inventario, actualizando la cantidad en la tabla inventory_balances       ////////////
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                $code = $item['codigo'] ?? null;
                $store = $item['store'] ?? null;
                $year = date('Y', strtotime($request->factura['fechaFactura'] ?? null));
                $cantidad = $item['cantidad'] ?? 0;

                $actualizado = InventoryBalance::where('year', $year)
                    ->where('code', $code)
                    ->where('store', $store)
                    ->where('companies_id', $companies_id) // Quita esta línea si companies_id no debe ir en el WHERE
                    ->decrement('quantity', $cantidad);
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                $row++;
                $basequantity =  $item['cantidad'] * $item['precioUnitario'];

                $dscto1       =  $item['cantidad'] * $item['precioUnitario'] * ($item['descuentoPorcentaje'] / 100);


                $dscto2       =  (($item['cantidad'] * $item['precioUnitario']  - $dscto1) * $item['cantidad'] * 0 / 100);
                //dd($dscto2);
                $reg_fact_det   = InvoiceDetail::updateOrCreate(
                    ['number' => $numerofactura, 'prefix' => $prefijo, 'product' => $item['codigo'], 'store' => $item['store'], 'customer' => $nit, 'idregister' => $row, 'companies_id' => $companies_id],
                    [
                        'number' => $numerofactura,
                        'prefix' => $prefijo,
                        'document_name' => $documento->name ?? null,
                        'customer' => $nit,
                        'date_issue' => $request->factura['fechaFactura'] ?? null,
                        'product' => $item['codigo'],
                        'name' => $item['nombre'] ?? null,
                        'store' => $item['store'] ?? null,
                        'quantity' => $item['cantidad'] ?? null,
                        'vat_percentage' => $item['ivaPorcentaje'] ?? null,
                        'basequantity' => $basequantity,
                        'unit_value' => $item['precioUnitario'] ?? null,
                        'cost_value' => $item['cost'] ?? null,
                        'discount1' => $item['descuentoPorcentaje'] ?? null,
                        'discount2' => 0,
                        'valuediscount1' => $dscto1,
                        'valuediscount2' => $dscto2,
                        'proyect' => "",
                        'sproyect' => "",
                        'center' => "",
                        'activity' => "",
                        'idregister' => $row,
                        'state' => 'Activo',
                        'companies_id' => $companies_id,
                        'sales_invoices_id' => $sales_invoice_id,
                    ]
                );

                //`companies_id`, `report_date`, `number`, `code`, `store`, `idregister`
                $fecha = $request->factura['fechaFactura'] ?? null;
                $idregister = $row;

                $reg_movent_invent   = InventoryMovement::updateOrCreate(
                    ['companies_id' => $companies_id, 'report_date' => $fecha, 'number' => $numerofactura, 'code' => $item['codigo'], 'store' => $item['store'], 'idregister' => $idregister],
                    [
                        'companies_id' => $companies_id,
                        'report_date' => $fecha,
                        'number' => $numerofactura,
                        'code' => $item['codigo'],
                        'store' => $item['store'],
                        'idregister' => $idregister,
                        'name' => $item['nombre'] ?? null,
                        'batch' => '',
                        'prefix' => $prefijo,
                        'concept_inv' => '950',
                        'concept_class' => '001',
                        'nit' => $nit,
                        'nit2' => '',
                        'branch' => $branch,
                        'health_batch' => '',
                        'plate' => '',
                        'serial' => '',
                        'amount' => $item['cantidad'] ?? null,
                        'amount1' => 0,
                        'vat' => $item['ivaPorcentaje'] ?? null,
                        'discount1' => $item['descuentoPorcentaje'] ?? null,
                        'discount2' => 0,
                        'discount3' => 0,
                        'unit_cost' => $item['cost'] ?? null,
                        'sale_price' => $item['precioUnitario'] ?? null,
                        'parcial_value' => ($item['cantidad'] * $item['precioUnitario']) - ($dscto1 + $dscto2),
                        'type' => 'FACT',
                        'state' => 'Activo',
                    ]
                );
            } catch (\Exception $ex) {
                return response()->json([
                    'status' => 'Error', // El código HTTP maneja el estado; mejor usar un flag aquí
                    'msg'    => "Error en la actualización del detalle de la factura: {$numerofactura}",
                    'error'  => [
                        'message' => $ex->getMessage(), // Mensaje legible del error
                        'file'    => $ex->getFile(),    // Archivo donde ocurrió el problema
                        'line'    => $ex->getLine(),    // Línea exacta del error
                        'trace'   => $ex->getTrace(),   // Array completo con la ruta que siguió el código
                    ],
                ], Response::HTTP_BAD_REQUEST); // Retorna un código 400
            }
        }

        return response()->json([
            'message' => 'Factura de venta almacenada correctamente.',
            'data' => $request->all(),
            'companies_id' => $companies_id,
            'customers' => $customers,
            'items' => $items,
            'tipo_documento' => $tipodcumento,
            'documento' => $documento,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getSalesInvoices(Request $request): JsonResponse
    {
        $desdefecha = $request->input('fechadesde');
        $hastafecha = $request->input('fechahasta');

        $ventas = SalesInvoice::select(
            'id',
            //'date_issue',
            //'expiration_date',
            'number',
            'prefix',
            'document_name',
            'customer',
            'client_name',
            'subtotal',
            'discounts',
            'total_sale',
            'cost_of_sale',
            'vatvalue',
            'retefuente',
            'reteiva',
            'reteica',
            'impoconsumo',
            'cufe',
            'state'
        )
            // Forzamos el formato YYYY-MM-DD para las fechas
            ->selectRaw("DATE_FORMAT(sales_invoices.date_issue, '%Y-%m-%d') as date_issue")
            ->selectRaw("DATE_FORMAT(sales_invoices.expiration_date, '%Y-%m-%d') as expiration_date")
            ->selectRaw('total_sale - cost_of_sale as rentabilidad, ((total_sale - cost_of_sale) / total_sale) * 100 as margen')
            ->whereBetween('date_issue', [$desdefecha, $hastafecha])
            ->where('companies_id', $request->input('company_id'))
            ->orderBy('date_issue')
            ->get();

        return response()->json(
            [
                'status'           => '200',
                'message'          => 'Mostrando documentos enviados a la DIAN entre ' . $desdefecha . ' y ' . $hastafecha,
                'TotalDocumentos' => $ventas->count(),
                'TotalValor'      => $ventas->sum('total_sale'),
                'TotalIva'        => $ventas->sum('vatvalue'),
                'TotalRentabilidad' => $ventas->sum('rentabilidad'),
                'data'            => $ventas,
            ],
            Response::HTTP_ACCEPTED
        );
    }

    public function getDetSalesInvoices(Request $request, $salesInvoiceId): JsonResponse
    {
        $desdefecha = $request->input('fechadesde');
        $hastafecha = $request->input('fechahasta');

        $details = InvoiceDetail::select(
            'product',
            'name',
            'store',
            'quantity',
            'discount1',
            'unit_value',
            'cost_value',
        )
            ->selectRaw('(quantity * unit_value) as subtotal')
            ->where('sales_invoices_id', $salesInvoiceId)
            ->where('companies_id', $request->input('company_id'))
            ->orderBy('date_issue')
            ->get();

        return response()->json(
            [
                'status'            => '200',
                'message'           => 'Mostrando Detalle de la Factura ' . $salesInvoiceId,
                'TotalRegistros'    => $details->count(),
                'data'              => $details,
            ],
            Response::HTTP_ACCEPTED
        );
    }
}
