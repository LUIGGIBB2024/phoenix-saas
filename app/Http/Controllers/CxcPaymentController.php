<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ControlConsecutive;
use App\Models\Customer;
use App\Models\CxcPayment;
use App\Models\CxpPayment;
use App\Models\DetailCxcPayment;
use App\Models\GeneralDocument;
use App\Models\MiscellaneousItem;
use App\Models\PaymentConcept;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CxcPaymentController extends Controller
{
    public function getCustomerPayments(Request $request): JsonResponse
    {

        $companies_id       = $request->input('company_id');
        $customers          = Customer::where('companies_id', $companies_id)->get();

        //$sources            = SourcePayment::where('companies_id', $companies_id)->get();
        $recibos            = GeneralDocument::where('typedocument1', 'Recibos')->where('typedocument4', 'Factura Crédito')->where('companies_id', $companies_id)->get();
        $recibos2           = GeneralDocument::where('typedocument1', 'Recibos')->where('typedocument4', 'No Aplica')->where('companies_id', $companies_id)->get();
        //$tiposgastos        = MiscellaneousItem::where('miscellaneous_id', 32)->orderBy('name')->get();

        $payments = CxcPayment::select(
            'cxc_payments.id',
            'cxc_payments.nit',
            'cxc_payments.branch',
            'lapse',
            'cxc_payments.report_date',
            'cxc_payments.consecutive',
            'cxc_payments.document',
            'customer_name',
            'value_cxc',
            'customer_balances',
            'observations',
            'check_number',
            'payment_type',
            'cxc_payments.state',
            'cxc_payments.state01',
            'cxc_payments.state02',
            'cxc_payments.state03',
            'cxc_payments.proyect',
            'cxc_payments.sproyect',
            'cxc_payments.center',
            'cxc_payments.activity',
            'cxc_payments.customers_id',
            'cxc_payments.companies_id',
        )
            ->selectRaw("DATE_FORMAT(cxc_payments.report_date, '%Y-%m-%d') as report_date")
            ->selectRaw("m.name as document_name, n.name as customers_name2")
            ->leftJoin('general_documents as m', function ($join) use ($companies_id) {
                $join->on('m.code', '=', 'cxc_payments.document')
                    ->where('typedocument1', 'Recibos')
                    ->where('m.companies_id', $companies_id);
            })
            ->leftJoin('customers as n', function ($join) use ($companies_id) {
                $join->on('n.nit', '=', 'cxc_payments.nit')
                    ->on('n.branch', '=', 'cxc_payments.branch')
                    ->where('n.companies_id', $companies_id);
            })
            ->orderBy('cxc_payments.created_at', 'DESC')
            ->where('cxc_payments.companies_id', $companies_id)
            ->get();

        return response()->json([
            'message' => 'Consulta de Recibos Generada Exitosamente',
            'payments' => $payments,
            'customers' => $customers,
            'docspayments' => $recibos,
            'docspaymentsothers' => $recibos2,
            'totaldocumentos' => $payments->count(),
        ], 201);
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $companies_id       = $request->input('company_id');
        $tipodcto           = $request->input('document');
        $nit                = $request->input('nit');
        $suc                = $request->input('branch');
        //$origin             = $request->input('payment_method');
        $clientes           = Customer::where('nit', $nit)->where('branch', $suc)->where('companies_id', $companies_id)->first();
        $clienteID          = $clientes->id;
        $tipo_recibo        = $request->input('tipo');
        $items              = $request['detpagos'];
        $valor_pago_recibo  = $request['value_cxc'];
        ////////////////////////////////////////////////////
        if ($tipo_recibo == 'OTROSP') {
            $valor_pago_recibo = collect($items)->sum('payment_amount');
        }


        try {
            // 1. Validación de los datos según el Schema de la base de datos

            $numerodcto = 0;

            //$sources_dcto          = SourcePayment::where('code', $origin)->where('companies_id', $companies_id)->first();

            $documento = GeneralDocument::where('companies_id',  $companies_id)
                ->where('type', 'Clientes')
                ->where('typedocument1', 'Recibos')
                ->where('typedocument2', 'Ventas')
                ->where('code', $tipodcto)
                ->where('state', 'Activo')
                ->first();

            $numerodcto = 0;
            if ($documento) {
                // Incrementamos el campo 'consecutive' en 1 en la base de datos y en el objeto actual
                if ($documento->controlconsecutive == 'Único') {
                    $docunico = ControlConsecutive::where('type', 'RCCAJA')->where('companies_id', $companies_id)->first();
                    $docunico->increment('consecutive');
                    $numerodcto = $docunico->consecutive;
                } else {
                    $documento->increment('consecutive');

                    // Asignamos el valor actualizado a tu variable
                    $numerodcto = $documento->consecutive;
                }
            } else {
                // Manejo de error en caso de que no se encuentre el documento configurado
                throw new \Exception("No se encontró una configuración de documento activo para los parámetros internos.");
            }

            $validated = $request->validate([
                'companies_id'      => 'nullable|exists:companies,id', // Cambia a 'required' si el pago siempre debe pertenecer a una empresa
                'customers_id'      => 'nullable|exists:customers,id',
                'nit'               => 'nullable|string|max:20',
                'branch'            => 'nullable|string|max:20',
                'lapse'             => 'nullable|string|max:6',
                'report_date'       => 'nullable|date|date_format:Y-m-d',
                'consecutive'        => 'nullable|integer',
                'document'          => 'nullable|string|max:20',
                'customer_name'     => 'nullable|string|max:255',
                'value_cxc'         => 'nullable|numeric|between:0,999999999999999999.99',
                'payments_others'   => 'nullable|numeric|between:0,999999999999999999.99',
                'customer_balances' => 'nullable|numeric|between:0,999999999999999999.99',
                'observations'      => 'nullable|string|max:255',
                'check_number'      => 'nullable|integer',
                'payment_type'      => 'nullable|string|max:20', // O si usas enum: 'nullable|in:PagosFacturas,OtrosPagos'
                'state'             => 'nullable|string|max:20', // O si usas enum: 'nullable|in:Activo,Eliminado,Pendiente'
                'state01'           => 'nullable|string|max:20',
                'state02'           => 'nullable|string|max:20',
                'state03'           => 'nullable|string|max:20',
                'proyect'           => 'nullable|string|max:20',
                'sproyect'          => 'nullable|string|max:20',
                'center'            => 'nullable|string|max:20',
                'activity'          => 'nullable|string|max:20',
                'usercreate'        => 'nullable|string|max:20',
                'userupdate'        => 'nullable|string|max:20',
            ]);

            // 2. Mapear 'company_id' al campo real 'companies_id'
            $validated['companies_id'] =  $companies_id;
            $validated['payments_others'] =  0;
            //unset($validated['company_id']);

            // Asignar el usuario creador
            $userCode = Auth::user()->code ?? 'System';
            $validated['usercreate'] = $request->input('usercreate', $userCode);
            $validated['userupdate'] = $validated['usercreate'];
            $validated['consecutive'] = $numerodcto;
            $validated['customers_id'] = $clienteID;
            $validated['value_cxc'] = $valor_pago_recibo;

            if ($tipo_recibo == 'OTROSP') {
                $validated['payments_others'] = $valor_pago_recibo;
            }


            // 3. Crear el registro envuelto en una transacción por seguridad
            $cxcPayment = DB::transaction(function () use ($validated) {
                return CxcPayment::create($validated);
            });

            // 🔑 Aquí obtienes el ID:
            $nuevoId = $cxcPayment->id;

            if ($tipo_recibo == 'FACTURAS') {
                $this->storedetails($request, $nuevoId, $clienteID, $cxcPayment);
            } else {
                $this->storedetails_others($request, $nuevoId, $clienteID, $cxcPayment);
            }

            // Retorno exitoso HTTP 201 (Created)
            $cxcPayment->document_name  = $documento->name;
            //$cxcPayment->origin_name    = $sources_dcto->name;
            return response()->json([
                'success'       => true,
                'message'       => 'Pago CXC creado con éxito.',
                'document_name' => $documento->name,
                'payments'      => $cxcPayment
            ], 201);
        } catch (Exception $e) {
            // Captura cualquier error (BD, asignación masiva, etc.) y lo expone para depuración
            return response()->json([
                'success' => false,
                'message' => 'Hubo un problema al procesar el pago.',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile()
            ], 500);
        }
    }

    public function storedetails(Request $request, $nuevoid, $clienteID, $cxcPayment)
    {
        $companies_id       = $request->input('company_id');
        $items              = $request['detpagos'];
        $fecha              = $request->input('report_date');
        $document           = $cxcPayment->document;
        $consecutive        = $cxcPayment->consecutive;
        $nit                = $cxcPayment->nit;
        $suc                = $cxcPayment->branch;

        //$table->index(['companies_id', 'report_date', 'consecutive', 'document', 'invoice', 'prefix', 'concept']

        //table->index(['companies_id', 'report_date', 'consecutive', 'document', 'invoice', 'prefix', 'concept']

        if (is_array($items) && count($items) > 0) {
            $row = 0;
            foreach ($items as $item) {
                //dd($item);
                $factura_id = $item['factura_id'];
                $concept    = $item['concepto'];
                $valor      = $item['valor'];
                $tipo       = $item['tipo_calculo'];
                $invoice    = (int) $item['numero_factura'];
                $prefijo    = $item['prefijo'];
                $factdcto   = $item['dctofra'];
                if ($valor == 0) continue;

                try {
                    $reg_fact = DetailCxcPayment::updateOrCreate(
                        [
                            // Campos únicos para localizar la fila exacta sin pisar otros productos
                            'companies_id'  => $companies_id,
                            'report_date'   => $fecha,
                            'consecutive'   => $consecutive,
                            'document'      => $document,
                            'invoice'       => $invoice,
                            'prefix'        => $prefijo,
                            'concept'       => $concept,
                        ],
                        [
                            'cxc_payment_id'         => $nuevoid,
                            'sales_invoice_id'       => $factura_id,
                            'nit'                    => $nit,
                            'branch'                 => $suc,
                            'invoicedcto'            => $factdcto,
                            'quota'                  => 1,
                            'payment_amount'         => $valor,
                            'calculate'              => $tipo,
                            'state'                  => 'Activo',
                            'state01'                => '',
                            'state02'                => '',
                            'state03'                => '',
                            'customers_id'           => $clienteID,
                        ]
                    );
                } catch (\Exception $ex) {
                    return response()->json(

                        [
                            'status'   => '404 OK',
                            'msg'      => 'Error en la actualización de la factura: ',
                            'error' => $ex,
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
        }
    }

    public function ListBalancesCxc(Request $request): JsonResponse
    {
        $nit    = $request->input('nit');
        $suc    = $request->input('sucursal');
        $companies_id   = $request->input('company_id');
        $cptospagos     = PaymentConcept::where('type', 'Clientes')->orderBy('code')->get();

        $paymentsSubquery = DB::table('detail_cxc_payments')
            ->select(
                'companies_id',
                'invoice',
                'nit',
                'prefix',
                DB::raw("SUM(
                CASE 
                    WHEN calculate = 'Suma' THEN payment_amount 
                    WHEN calculate = 'Resta' THEN -payment_amount 
                    ELSE 0 
                END
            ) as abonos")
            )
            ->where('companies_id', $companies_id)
            ->where('nit', $nit)
            ->where('state', 'Activo')
            ->groupBy('companies_id', 'invoice', 'nit', 'prefix');

        // 2. CONSULTA PRINCIPAL: Une la subconsulta con la tabla de facturas
        $listado = DB::table('sales_invoices as pi')
            ->leftJoinSub($paymentsSubquery, 'dp', function ($join) {
                $join->on('pi.companies_id', '=', 'dp.companies_id')
                    ->on('pi.number', '=', 'dp.invoice')
                    ->on('pi.customer', '=', 'dp.nit')
                    ->where(function ($query) {
                        $query->whereColumn('pi.prefix', '=', 'dp.prefix')
                            ->orWhere(function ($q) {
                                $q->whereNull('pi.prefix')->whereNull('dp.prefix');
                            });
                    });
            })
            ->select(
                'pi.id',
                'pi.date_issue as fecha_factura',
                'pi.expiration_date as fecha_vencimiento',
                DB::raw("DATEDIFF(CURRENT_DATE, pi.expiration_date) as dias_vencimiento"),
                'pi.prefix',
                'pi.number as numero_factura',
                'pi.customer',
                'pi.branch',
                'pi.client_name as cliente',
                'pi.document_name',
                'pi.state',
                DB::raw("COALESCE(pi.total_sale, 0) as valor_factura"),

                // Se obtiene directo del alias 'dp' sin SUM()
                DB::raw("COALESCE(dp.abonos, 0) as abonos"),
                DB::raw("COALESCE(0, 0) as abonoactual"),

                // Saldo = Valor factura - Abonos calculados
                DB::raw("(COALESCE(pi.total_sale, 0) - COALESCE(dp.abonos, 0)) as saldo")
            )
            ->where('pi.companies_id', $companies_id)
            ->where('pi.customer', $nit)
            ->where('pi.branch', $suc)
            ->where('pi.state', 'Activo')
            ->where('pi.type', 'Crédito')
            // Opcional: Filtrar solo las que tengan saldo pendiente
            // ->whereRaw('(COALESCE(pi.total_purchase, 0) - COALESCE(dp.abonos, 0)) > 0')
            ->get();

        $totales = [
            'valor_factura' => $listado->sum(fn($item) => (float) $item->valor_factura),
            'abonos'        => $listado->sum(fn($item) => (float) $item->abonos),
            'saldo'         => $listado->sum(fn($item) => (float) $item->saldo),
        ];


        return response()->json([
            'success' => true,
            'message' => 'Listado Generado Exitosamente',
            'listbalances'    => $listado,
            'totales'  => $totales,
            'paymentcpt' => $cptospagos,
        ], 201, [], JSON_NUMERIC_CHECK);
    }

    public function getPaymentsDetailCxc(Request $request): JsonResponse
    {
        $companyId  = $request->input('company_id');
        $iddocument = $request['document']['id'];
        $conceptpay = PaymentConcept::where('type', 'Proveedores')->where('companies_id', $companyId)->get();
        $document = DetailCxcPayment::select(
            'detail_cxc_payments.id',
            'cxc_payment_id',
            'sales_invoice_id',
            'consecutive',
            'document',
            'nit',
            'branch',
            'report_date',
            'concept',
            'invoice',
            'prefix',
            'invoicedcto',
            'quota',
            'payment_amount',
            'calculate',
            'state',
            'state01',
            'state02',
            'state03',
            'customers_id',
            'detail_cxc_payments.companies_id',
        )
            ->selectRaw("m.name as concept_name")
            ->leftJoin('payment_concepts as m', function ($join) use ($companyId) {
                $join->on('m.code', '=', 'detail_cxc_payments.concept')
                    ->where('m.companies_id',  $companyId)
                    ->where('m.type', 'Proveedores');
            })
            ->where('detail_cxc_payments.companies_id', $companyId)
            ->where('cxc_payment_id', $iddocument)
            ->get();

        return response()->json([
            'message' => 'Consulta de Detalle del Documento Generada Exitosamente',
            'details' =>  $document,

        ], 201, [], JSON_UNESCAPED_UNICODE);
    }

    public function ConsultBalancesCxc(Request $request): JsonResponse
    {
        $fechadesde     = $request->input('fechadesde');
        $fechahasta     = $request->input('fechahasta');
        $suc            = $request->input('sucursal');
        $companies_id   = $request->input('company_id');
        $cptospagos     = PaymentConcept::where('type', 'Clientes')->orderBy('code')->get();

        // 1. SUBCONSULTA: Suma los abonos agrupando únicamente por el ID de la factura
        $paymentsSubquery = DB::table('detail_cxc_payments')
            ->select(
                'sales_invoice_id',
                DB::raw("SUM(
                CASE 
                    WHEN calculate = 'Suma' THEN payment_amount 
                    WHEN calculate = 'Resta' THEN -payment_amount 
                    ELSE 0 
                END
            ) as abonos")
            )
            ->where('companies_id', $companies_id)
            ->where('state', 'Activo')
            ->whereNotNull('sales_invoice_id')
            ->groupBy('sales_invoice_id');

        // 2. CONSULTA PRINCIPAL: Une la subconsulta relacionando pi.id = dp.sales_invoice_id
        $listado = DB::table('sales_invoices as pi')
            ->leftJoinSub($paymentsSubquery, 'dp', function ($join) {
                $join->on('pi.id', '=', 'dp.sales_invoice_id');
            })
            ->select(
                'pi.id',
                'pi.date_issue as fecha_factura',
                'pi.expiration_date as fecha_vencimiento',
                DB::raw("DATEDIFF(CURRENT_DATE, pi.expiration_date) as dias_vencimiento"),
                'pi.prefix',
                'pi.number as numero_factura',
                'pi.customer',
                'pi.branch',
                'pi.client_name as NombreCliente',
                'pi.document_name',
                'pi.state',
                DB::raw("COALESCE(pi.total_sale, 0) as valor_factura"),

                // Se obtiene directo del alias 'dp'
                DB::raw("COALESCE(dp.abonos, 0) as abonos"),
                DB::raw("COALESCE(0, 0) as abonoactual"),

                // Saldo = Valor factura - Abonos calculados
                DB::raw("(COALESCE(pi.total_sale, 0) - COALESCE(dp.abonos, 0)) as saldo")
            )
            ->where('pi.companies_id', $companies_id)
            ->where('pi.type', 'Crédito')
            ->where('pi.state', 'Activo')
            ->whereBetween('pi.date_issue', [$fechadesde, $fechahasta])
            ->get();

        $totales = [
            'valor_factura' => $listado->sum(fn($item) => (float) $item->valor_factura),
            'abonos'        => $listado->sum(fn($item) => (float) $item->abonos),
            'saldo'         => $listado->sum(fn($item) => (float) $item->saldo),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Listado CXC Generado Exitosamente',
            'listbalances' => $listado,
            'totales'  => $totales,
            'totaldocumentos' => $listado->count(),
        ], 200);
    }

    public function GetCustomersInvoice(Request $request): JsonResponse
    {
        $companies_id   = $request->input('company_id');

        $clientes = Customer::where('companies_id', $companies_id)->get();
        $dctos_cxc          = GeneralDocument::where('typedocument1', 'Facturas')->where('typedocument4', 'Factura Crédito')->where('companies_id', $companies_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Facturas del Cliente obtenidas exitosamente.',
            'customers' => $clientes,
            'dctoscxc' => $dctos_cxc,
        ], 201, [], JSON_UNESCAPED_UNICODE);
    }

    public function SaveInvoiceCxc(Request $request): JsonResponse
    {
        //dd($request->all());

        $companies_id   = $request->input('company_id');

        $factura        = $request['facturas'];
        $numerofactura  = $factura['number'];
        $prefix         = $factura['prefix'];
        $customer       = $factura['nit'];
        try {
            SalesInvoice::updateOrCreate(
                [
                    'companies_id'  => $companies_id,
                    'number'        => $numerofactura,
                    'prefix'        => $prefix,
                    'customer'      => $customer,
                ],
                [
                    'type'                  => 'Crédito',
                    'date_issue'            => $factura['fecha_factura'],
                    'expiration_date'       => $factura['fecha_vencimiento'],
                    'document_name'         => $factura['document_name'],
                    'branch'                => $factura['branch'],
                    'client_name'           => $factura['customer_name'],
                    'total_sale'            => $factura['valor_factura'],
                    'subtotal'              => $factura['valor_factura'],
                    'discounts' => 0,
                    'products_discount' => 0,
                    'additional_discounts' => 0,
                    'additional_value' => 0,
                    'impoconsumo' => 0,
                    'vatvalue' => 0,
                    'retefuente' => 0,
                    'reteiva' => 0,
                    'reteica' => 0,
                    'exempt_sales' => 0,
                    'taxed_sales' => 0,
                    'cost_of_sale' => 0,
                    'payment_value' => 0,
                    'health_copays' => 0,
                    'health_advances' => 0,
                    'health_moderator_fee' => 0,
                    'hours' => 0,
                    'minutes' => 0,
                    'total_items' => 0,
                    'payment_methods_id' => 10,
                    'cufe' => '',
                    'proyect' => '',
                    'sproyect' => '',
                    'seller' => '001',
                    'route' => '001',
                    'zone' => '001',
                    'typecustomer' => '001',
                    'list' => '01',
                    'state' => 'Activo',
                ]
            );
        } catch (\Exception $ex) {
            return response()->json(
                [
                    'status'   => '404 OK',
                    'msg'      => 'Error en la actualización de la factura: ',
                    'error'    => $ex->getMessage(),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Facturas CXC guardadas exitosamente.',
        ], 201, [], JSON_UNESCAPED_UNICODE);
    }

    public function DeleteInvoiceCxc(Request $request, $id): JsonResponse
    {

        $companies_id   = (int) $request->input('company_id');
        $factura_id     = (int) $id;

        try {
            SalesInvoice::where('companies_id', $companies_id)
                ->where('id', $factura_id)
                ->delete();
        } catch (\Exception $ex) {
            return response()->json(
                [
                    'status'   => '404 OK',
                    'msg'      => 'Error al eliminar la factura: ',
                    'error'    => $ex->getMessage(),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Factura CXC eliminada exitosamente.',
        ], 201, [], JSON_UNESCAPED_UNICODE);
    }

    public function CashReconciliation(Request $request)
    {
        $companies_id   = $request->input('company_id');
        $fechadesde     = $request->input('fechadesde');
        $fechahasta     = $request->input('fechahasta');

        $company        = Company::where('id', $companies_id)->first();
        $saldoinicial   = (float) ($company->opening_balance ?? 0);



        // 1. Obtener Ventas (Ingresos)
        $ventas = SalesInvoice::select('date_issue as report_date', 'document_name', 'number', 'prefix', 'client_name as name')
            ->selectRaw("0.00 as saldoinicial, total_sale as ingresos, 0.00 as pagos, 0.00 as saldoactual, 
                    CASE WHEN payment_methods_id = 10 THEN 'SI' ELSE 'NO' END as calculo")
            ->withCasts([
                'saldoinicial' => 'float',
                'ingresos'     => 'float',
                'pagos'        => 'float',
                'saldoactual'  => 'float',
            ])
            ->where('date_issue', $fechahasta)
            ->where('type', 'Contado')
            ->where('state', 'Activo')
            ->orderBy('number')
            ->orderBy('prefix')
            ->get();

        // 2. Obtener Pagos/Egresos
        $pagos = CxpPayment::select('report_date', 'm.name as document_name', 'cxp_payments.consecutive as number')
            ->selectRaw("' ' as prefix, supplier_name as name")
            ->selectRaw("0.00 as saldoinicial, 0.00 as ingresos, (others_payments + value_cxp) as pagos, 0.00 as saldoactual, 
                    CASE WHEN n.type = 'Pagos en Efectivo' THEN 'SI' ELSE 'NO' END as calculo")
            ->withCasts([
                'saldoinicial' => 'float',
                'ingresos'     => 'float',
                'pagos'        => 'float',
                'saldoactual'  => 'float',
            ])
            ->leftJoin('general_documents as m', function ($join) use ($companies_id) {
                $join->on('m.code', '=', 'cxp_payments.document')
                    ->where('typedocument3', 'Egresos')
                    ->where('m.companies_id', $companies_id);
            })
            ->leftJoin('source_payments as n', function ($join) use ($companies_id) {
                $join->on('n.code', '=', 'cxp_payments.payment_method')
                    ->where('n.companies_id', $companies_id);
            })
            ->where('report_date', $fechahasta)
            ->where('cxp_payments.state', 'Activo')
            ->orderBy('number')
            ->orderBy('prefix')
            ->get();

        // 3. Unir ambas colecciones en una sola
        $movimientos = $ventas->concat($pagos);

        // (Opcional) Si deseas ordenarlos por número o fecha de forma consecutiva:
        // $movimientos = $movimientos->sortBy('number')->values();

        // 4. Recorrer la colección unificada y calcular saldos en cascada

        $saldoacumulado = $this->previous_balance($companies_id, $fechahasta);


        $saldo = $saldoinicial +  $saldoacumulado;

        foreach ($movimientos as $mov) {
            $mov->saldoinicial = $saldo;

            if ($mov->calculo === 'SI') {
                $saldo += $mov->ingresos; // Suma si es venta/ingreso
                $saldo -= $mov->pagos;    // Resta si es egreso/pago
            }

            $mov->saldoactual = $saldo;
        }



        // 5. Retornar la respuesta con la lista de movimientos unificada

        $acumulados = $this->accumulated_data($companies_id, $fechahasta);

        return response()->json([
            'openingbalance'    => ($saldoacumulado + $saldoinicial),
            'saldoactual'       => $saldo,
            'movements'         => $movimientos,
            'accumulated'       => $acumulados,
            'success'           => true,
            'message'           => 'Consulta Generada Exitosamente.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    public function  accumulated_data($companies_id, $fechahasta)
    {

        // Procesar Facturas de Contado
        $ventascdo = DB::table('sales_invoices')
            ->selectRaw('sum(total_sale) as acumulado')
            ->where('type', 'Contado')
            ->where('state', 'Activo')
            ->where('payment_methods_id', 10)
            ->where('date_issue', '=', $fechahasta)
            ->where('sales_invoices.companies_id', $companies_id)
            ->first();

        // Lo conviertes a float aquí de forma segura
        $acumuladovtacdo = $ventascdo ? (float) $ventascdo->acumulado : 0.0;

        // Procesar Facturas de Contado - Otros Métodos de Pagos
        $ventascdo_otros = DB::table('sales_invoices')
            ->selectRaw('sum(total_sale) as acumulado')
            ->where('type', 'Contado')
            ->where('state', 'Activo')
            ->where('payment_methods_id', '<>', 10)
            ->where('date_issue', '=', $fechahasta)
            ->where('sales_invoices.companies_id', $companies_id)
            ->first();

        // Lo conviertes a float aquí de forma segura
        $acumuladovtacdo_otros = $ventascdo_otros ? (float) $ventascdo_otros->acumulado : 0.0;

        // Procesar Facturas de Crédito
        $ventascre = DB::table('sales_invoices')
            ->selectRaw('sum(total_sale) as acumulado')
            ->where('type', 'Credito')
            ->where('state', 'Activo')
            ->where('date_issue', '=', $fechahasta)
            ->where('sales_invoices.companies_id', $companies_id)
            ->first();

        // Lo conviertes a float aquí de forma segura
        $acumuladovtacre = $ventascre ? (float) $ventascre->acumulado : 0.0;

        // Procesar Pagos en Efectivo
        $pagosefe = DB::table('cxp_payments')
            ->selectRaw('SUM(cxp_payments.value_cxp) as acumulado')
            ->join('source_payments as n', function ($join) use ($companies_id) {
                $join->on('n.code', '=', 'cxp_payments.payment_method')
                    ->where('n.companies_id', $companies_id);
            })
            ->where('cxp_payments.state', 'Activo')
            ->where('n.type', 'Pagos en Efectivo')
            ->where('cxp_payments.report_date', '=', $fechahasta)
            ->where('cxp_payments.companies_id', $companies_id)
            ->first();

        $acumuladopagosefe = $pagosefe ? (float) $pagosefe->acumulado : 0.0;

        // Procesar Pagos por Transferencias / Cheques / otros
        $pagosotros = DB::table('cxp_payments')
            ->selectRaw('SUM(cxp_payments.value_cxp) as acumulado')
            ->join('source_payments as n', function ($join) use ($companies_id) {
                $join->on('n.code', '=', 'cxp_payments.payment_method')
                    ->where('n.companies_id', $companies_id);
            })
            ->where('cxp_payments.state', 'Activo')
            ->where('n.type', '<>', 'Pagos en Efectivo')
            ->where('cxp_payments.report_date', '=', $fechahasta)
            ->where('cxp_payments.companies_id', $companies_id)
            ->first();

        $acumuladopagosotros = $pagosotros ? (float) $pagosotros->acumulado : 0.0;

        return [
            'ventascontado'       => $acumuladovtacdo,
            'ventascontado_otros' => $acumuladovtacdo_otros,
            'ventascredito'       => $acumuladovtacre,
            'recibosdecaja'       => 0,
            'egresosefectivo'     => $acumuladopagosefe,
            'egresos_otros'       => $acumuladopagosotros,
        ];
    }

    public function previous_balance($companies_id, $fechahasta)
    {
        // Procesar Facturas de Contado
        $ventas = DB::table('sales_invoices')
            ->selectRaw('sum(total_sale) as acumulado')
            ->where('type', 'Contado')
            ->where('state', 'Activo')
            ->where('payment_methods_id', 10)
            ->where('date_issue', '<', $fechahasta)
            ->where('sales_invoices.companies_id', $companies_id)
            ->first();

        // Lo conviertes a float aquí de forma segura
        $acumulado1 = $ventas ? (float) $ventas->acumulado : 0.0;

        // Procesar Pagos
        $pagos = DB::table('cxp_payments')
            ->selectRaw('SUM(cxp_payments.value_cxp) as acumulado')
            ->join('source_payments as n', function ($join) use ($companies_id) {
                $join->on('n.code', '=', 'cxp_payments.payment_method')
                    ->where('n.companies_id', $companies_id);
            })
            ->where('cxp_payments.state', 'Activo')
            ->where('n.type', 'Pagos en Efectivo')
            ->where('cxp_payments.report_date', '<', $fechahasta)
            ->where('cxp_payments.companies_id', $companies_id)
            ->first();

        // Conversión a float manual y segura:
        $acumulado2 = $pagos ? (float) $pagos->acumulado : 0.0;

        // Procesar Ingresos en Efectivo
        $recibos = DB::table('cxc_payments')
            ->selectRaw('SUM(cxc_payments.value_cxc) as acumulado')
            ->where('cxc_payments.state', 'Activo')
            ->where('cxc_payments.report_date', '<', $fechahasta)
            ->where('cxc_payments.companies_id', $companies_id)
            ->first();

        // Conversión a float manual y segura:
        $acumulado3 = $recibos ? (float) $recibos->acumulado : 0.0;

        return ($acumulado1 + $acumulado2 + $acumulado3);
    }


    public function ConsultPaymentsCxc(Request $request): JsonResponse
    {
        $companies_id       = $request->input('company_id');
        $customers          = Customer::where('companies_id', $companies_id)->get();
        $fechadesde         = $request->input('fechadesde');
        $fechahasta         = $request->input('fechahasta');

        //$sources            = SourcePayment::where('companies_id', $companies_id)->get();
        $recibos            = GeneralDocument::where('typedocument1', 'Recibos')->where('typedocument4', 'Factura Crédito')->where('companies_id', $companies_id)->get();
        $recibos2           = GeneralDocument::where('typedocument1', 'Recibos')->where('typedocument4', 'No Aplica')->where('companies_id', $companies_id)->get();
        //$tiposgastos        = MiscellaneousItem::where('miscellaneous_id', 32)->orderBy('name')->get();

        $payments = CxcPayment::select(
            'cxc_payments.id',
            'cxc_payments.nit',
            'cxc_payments.branch',
            'lapse',
            'cxc_payments.report_date',
            'cxc_payments.consecutive',
            'cxc_payments.document',
            'customer_name',
            'value_cxc',
            'customer_balances',
            'observations',
            'check_number',
            'payment_type',
            'cxc_payments.state',
            'cxc_payments.state01',
            'cxc_payments.state02',
            'cxc_payments.state03',
            'cxc_payments.proyect',
            'cxc_payments.sproyect',
            'cxc_payments.center',
            'cxc_payments.activity',
            'cxc_payments.customers_id',
            'cxc_payments.companies_id',
        )
            ->selectRaw("DATE_FORMAT(cxc_payments.report_date, '%Y-%m-%d') as report_date")
            ->selectRaw("m.name as document_name, n.name as customers_name2")
            ->leftJoin('general_documents as m', function ($join) use ($companies_id) {
                $join->on('m.code', '=', 'cxc_payments.document')
                    ->where('typedocument1', 'Recibos')
                    ->where('m.companies_id', $companies_id);
            })
            ->leftJoin('customers as n', function ($join) use ($companies_id) {
                $join->on('n.nit', '=', 'cxc_payments.nit')
                    ->on('n.branch', '=', 'cxc_payments.branch')
                    ->where('n.companies_id', $companies_id);
            })
            ->orderBy('cxc_payments.report_date')
            ->where('cxc_payments.companies_id', $companies_id)
            ->where('cxc_payments.report_date', '>=', $fechadesde)
            ->where('cxc_payments.report_date', '<=', $fechahasta)
            ->get();


        return response()->json([
            'message' => 'Consulta de Recibos Generada Exitosamente',
            'payments' => $payments,
            'customers' => $customers,
            'docspayments' => $recibos,
            'docspaymentsothers' => $recibos2,
            'totaldocumentos' => $payments->count(),
        ], 201);
    }
}
