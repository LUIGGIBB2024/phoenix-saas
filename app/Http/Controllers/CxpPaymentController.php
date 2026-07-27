<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ControlConsecutive;
use App\Models\CxpPayment;
use App\Models\DetailCxpOthersPayment;
use App\Models\DetailCxpPayment;
use App\Models\GeneralDocument;
use App\Models\MiscellaneousItem;
use App\Models\PaymentConcept;
use App\Models\SourcePayment;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CxpPaymentController extends Controller
{

    public function getPayments(Request $request): JsonResponse
    {

        $companies_id       = $request->input('company_id');
        $proveedores        = Supplier::where('companies_id', $companies_id)->get();

        $sources            = SourcePayment::where('companies_id', $companies_id)->get();
        $egresos            = GeneralDocument::where('typedocument3', 'Egresos')->where('typedocument4', 'Factura Crédito')->where('companies_id', $companies_id)->get();
        $egresos2           = GeneralDocument::where('typedocument3', 'Egresos')->where('typedocument4', 'No Aplica')->where('companies_id', $companies_id)->get();
        $tiposgastos        = MiscellaneousItem::where('miscellaneous_id', 32)->orderBy('name')->get();

        $payments = CxpPayment::select(
            'cxp_payments.id',
            'nit',
            'branch',
            'lapse',
            'report_date',
            'check_date',
            'delivery_date',
            'cxp_payments.consecutive',
            'document',
            'supplier_name',
            'value_cxp',
            'others_payments',
            'observations',
            'payment_method',
            'check_number',
            'payment_type',
            'cxp_payments.state',
            'state01',
            'state02',
            'state03',
            'proyect',
            'sproyect',
            'center',
            'activity',
            'suppliers_id',
            'cxp_payments.companies_id',
        )
            ->selectRaw("DATE_FORMAT(cxp_payments.report_date, '%Y-%m-%d') as report_date")
            ->selectRaw("m.name as document_name, n.name as origin_name")
            ->leftJoin('general_documents as m', function ($join) use ($companies_id) {
                $join->on('m.code', '=', 'cxp_payments.document')
                    ->where('typedocument3', 'Egresos')
                    ->where('m.companies_id', $companies_id);
            })
            ->leftJoin('source_payments as n', function ($join) use ($companies_id) {
                $join->on('n.code', '=', 'cxp_payments.payment_method')
                    ->where('n.companies_id', $companies_id);
            })
            ->orderBy('cxp_payments.created_at', 'DESC')
            ->where('cxp_payments.companies_id', $companies_id)
            ->get();

        return response()->json([
            'message' => 'Consulta Generada Exitosamente',
            'payments' => $payments,
            'suppliers' => $proveedores,
            'sources' => $sources,
            'otherexpenses' => $tiposgastos,
            'docspayments' => $egresos,
            'docspaymentsothers' => $egresos2,
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
        $origin             = $request->input('payment_method');
        $proveedores        = Supplier::where('nit', $nit)->where('branch', $suc)->where('companies_id', $companies_id)->first();
        $proveedorID        = $proveedores->id;
        $tipo_egreso        = $request->input('tipo');
        $items              = $request['detpagos'];
        $valor_pago_egreso = 0;
        ////////////////////////////////////////////////////
        if ($tipo_egreso == 'OTROSP') {
            $valor_pago_egreso = collect($items)->sum('payment_amount');
        }


        try {
            // 1. Validación de los datos según el Schema de la base de datos

            $numerodcto = 0;

            $sources_dcto          = SourcePayment::where('code', $origin)->where('companies_id', $companies_id)->first();

            $documento = GeneralDocument::where('companies_id',  $companies_id)
                ->where('type', 'Proveedores')
                ->where('typedocument2', 'Compras')
                ->where('typedocument3', 'Egresos')
                ->where('code', $tipodcto)
                ->where('state', 'Activo')
                ->first();

            $numerodcto = 0;
            if ($documento) {
                // Incrementamos el campo 'consecutive' en 1 en la base de datos y en el objeto actual
                if ($documento->controlconsecutive == 'Único') {
                    $docunico = ControlConsecutive::where('type', 'EGRESOS')->where('companies_id', $companies_id)->first();
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
                'company_id'       => 'required|exists:companies,id',
                'suppliers_id'     => 'nullable|exists:suppliers,id',
                'nit'              => 'nullable|string|max:20',
                'branch'           => 'nullable|string|max:20',
                'lapse'            => 'nullable|string|max:6',
                'report_date'      => 'nullable|date|date_format:Y-m-d', // Corregido a Y-m-d
                'check_date'       => 'nullable|date|date_format:Y-m-d',
                'delivery_date'    => 'nullable|date|date_format:Y-m-d',
                'consecutive'      => 'nullable|integer',
                'document'         => 'nullable|string|max:20',
                'supplier_name'    => 'nullable|string|max:255',
                'value_cxp'        => 'nullable|numeric|between:0,999999999999999999.99',
                'others_payments'  => 'nullable|numeric|between:0,999999999999999999.99',
                'observations'     => 'nullable|string|max:255',
                'payment_method'   => 'nullable|string|max:20',
                'check_number'     => 'nullable|integer',
                'payment_type'     => 'nullable|in:PagosFacturas,OtrosPagos',
                'state'            => 'nullable|in:Activo,Eliminado,Pendiente',
                'state01'          => 'nullable|string|max:20',
                'state02'          => 'nullable|string|max:20',
                'state03'          => 'nullable|string|max:20',
                'proyect'          => 'nullable|string|max:20',
                'sproyect'         => 'nullable|string|max:20',
                'center'           => 'nullable|string|max:20',
                'activity'         => 'nullable|string|max:20',
                'usercreate'       => 'nullable|string|max:20',
            ]);

            // 2. Mapear 'company_id' al campo real 'companies_id'
            $validated['companies_id'] = $validated['company_id'];
            unset($validated['company_id']);

            // Asignar el usuario creador
            $userCode = Auth::user()->code ?? 'System';
            $validated['usercreate'] = $request->input('usercreate', $userCode);
            $validated['userupdate'] = $validated['usercreate'];
            $validated['consecutive'] = $numerodcto;
            $validated['suppliers_id'] = $proveedorID;

            if ($tipo_egreso == 'OTROSP') {
                $validated['value_cxp'] = $valor_pago_egreso;
            }


            // 3. Crear el registro envuelto en una transacción por seguridad
            $cxpPayment = DB::transaction(function () use ($validated) {
                return CxpPayment::create($validated);
            });

            // 🔑 Aquí obtienes el ID:
            $nuevoId = $cxpPayment->id;

            if ($tipo_egreso == 'FACTURAS') {
                $this->storedetails($request, $nuevoId, $proveedorID, $cxpPayment);
            } else {
                $this->storedetails_others($request, $nuevoId, $proveedorID, $cxpPayment);
            }


            // Retorno exitoso HTTP 201 (Created)
            $cxpPayment->document_name  = $documento->name;
            $cxpPayment->origin_name    = $sources_dcto->name;
            return response()->json([
                'success' => true,
                'message' => 'Pago CXP creado con éxito.',
                'document_name' =>  $documento->name,
                'payments'    => $cxpPayment
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

    public function storedetails(Request $request, $nuevoid, $proveedorID, $cxpPayment)
    {
        $companies_id       = $request->input('company_id');
        $items              = $request['detpagos'];
        $fecha              = $request->input('report_date');
        $document           = $cxpPayment->document;
        $consecutive        = $cxpPayment->consecutive;
        $nit                = $cxpPayment->nit;
        $suc                = $cxpPayment->branch;
        //$table->index(['companies_id', 'report_date', 'consecutive', 'document', 'invoice', 'prefix', 'concept']

        if (is_array($items) && count($items) > 0) {
            $row = 0;

            foreach ($items as $item) {
                $factura_id = $item['factura_id'];
                $concept    = $item['concepto'];
                $valor      = $item['valor'];
                $tipo       = $item['tipo_calculo'];
                $invoice    = (int) $item['numero_factura'];
                $prefijo    = $item['prefijo'];
                $factdcto   = $item['dctofra'];
                if ($valor == 0) continue;

                try {
                    $reg_fact = DetailCxpPayment::updateOrCreate(
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
                            'cxp_payment_id'         => $nuevoid,
                            'purchases_invoice_id'   => $factura_id,
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
                            'suppliers_id'           => $proveedorID,
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

    public function storedetails_others(Request $request, $nuevoid, $proveedorID, $cxpPayment)
    {
        $companies_id       = $request->input('company_id');
        $items              = $request['detpagos'];
        $fecha              = $request->input('report_date');
        $document           = $cxpPayment->document;
        $consecutive        = $cxpPayment->consecutive;
        $nit                = $cxpPayment->nit;
        $suc                = $cxpPayment->branch;

        //['companies_id', 'report_date', 'consecutive', 'document', 'concept', 'nit', 'idregister']

        if (is_array($items) && count($items) > 0) {
            $row = 0;

            foreach ($items as $item) {
                $nit        = $item['nit'];
                $concept    = $item['concept'];
                $valor      = $item['payment_amount'];
                $idregister = $item['idlinea'];
                $internaldoc = $item['internaldoc'];
                $tipocalculo = $item['calculate'];
                if ($valor == 0) continue;

                try {
                    $reg_fact = DetailCxpOthersPayment::updateOrCreate(
                        [
                            // Campos únicos para localizar la fila exacta sin pisar otros productos
                            'companies_id'  => $companies_id,
                            'report_date'   => $fecha,
                            'consecutive'   => $consecutive,
                            'document'      => $document,
                            'concept'       => $concept,
                            'nit'           => $nit,
                            'idregister'    => $idregister,
                        ],
                        [
                            'cxp_payment_id'         => $nuevoid,
                            'nit'                    => $nit,
                            'branch'                 => $suc,
                            'internaldoc'            => ($internaldoc) ? $internaldoc : '',
                            'payment_amount'         => $valor,
                            'calculate'              => $tipocalculo,
                            'state'                  => 'Activo',
                            'state01'                => '',
                            'state02'                => '',
                            'state03'                => '',
                            'accounting_code'        => '',
                            'center'                 => '',
                            'scenter'                => '',
                            'proyect'                => '',
                            'sproyect'               => '',
                            'activity'               => '',
                            'suppliers_id'           =>  $item['suppliers_id'],
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

    public function ListBalancesCxp(Request $request): JsonResponse
    {
        $nit    = $request->input('nit');
        $suc    = $request->input('sucursal');
        $companies_id   = $request->input('company_id');
        $cptospagos     = PaymentConcept::where('type', 'Proveedores')->orderBy('code')->get();

        $paymentsSubquery = DB::table('detail_cxp_payments')
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
        $listado = DB::table('purchases_invoices as pi')
            ->leftJoinSub($paymentsSubquery, 'dp', function ($join) {
                $join->on('pi.companies_id', '=', 'dp.companies_id')
                    ->on('pi.number', '=', 'dp.invoice')
                    ->on('pi.supplier', '=', 'dp.nit')
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
                'pi.supplier',
                'pi.branch',
                'pi.supplier_name as proveedor',
                'pi.document_name',
                'pi.state',
                DB::raw("COALESCE(pi.total_purchase, 0) as valor_factura"),

                // Se obtiene directo del alias 'dp' sin SUM()
                DB::raw("COALESCE(dp.abonos, 0) as abonos"),
                DB::raw("COALESCE(0, 0) as abonoactual"),

                // Saldo = Valor factura - Abonos calculados
                DB::raw("(COALESCE(pi.total_purchase, 0) - COALESCE(dp.abonos, 0)) as saldo")
            )
            ->where('pi.companies_id', $companies_id)
            ->where('pi.supplier', $nit)
            ->where('pi.branch', $suc)
            ->where('pi.state', 'Activo')
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
        ], 201);
    }

    public function getPaymentsDetail(Request $request): JsonResponse
    {
        $companyId  = $request->input('company_id');
        $iddocument = $request['document']['id'];
        $conceptpay = PaymentConcept::where('type', 'Proveedores')->where('companies_id', $companyId)->get();
        $document = DetailCxpPayment::select(
            'detail_cxp_payments.id',
            'cxp_payment_id',
            'purchases_invoice_id',
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
            'suppliers_id',
            'detail_cxp_payments.companies_id',
        )
            ->selectRaw("m.name as concept_name")
            ->leftJoin('payment_concepts as m', function ($join) use ($companyId) {
                $join->on('m.code', '=', 'detail_cxp_payments.concept')
                    ->where('m.companies_id',  $companyId)
                    ->where('m.type', 'Proveedores');
            })
            ->where('detail_cxp_payments.companies_id', $companyId)
            ->where('cxp_payment_id', $iddocument)
            ->get();

        return response()->json([
            'message' => 'Consulta de Detalle del Documento Generada Exitosamente',
            'details' =>  $document,

        ], 201, [], JSON_UNESCAPED_UNICODE);
    }

    public function getPaymentsDetailothr(Request $request): JsonResponse
    {
        $companyId  = $request->input('company_id');
        $iddocument = $request['document']['id'];

        $document = DetailCxpOthersPayment::select(
            'detail_cxp_others_payments.cxp_payment_id',
            'consecutive',
            'document',
            'detail_cxp_others_payments.nit',
            'detail_cxp_others_payments.branch',
            'report_date',
            'internaldoc',
            'concept',
            'accounting_code',
            'center',
            'scenter',
            'proyect',
            'sproyect',
            'activity',
            'payment_amount',
            'idregister',
            'calculate',
            'detail_cxp_others_payments.state',
            'detail_cxp_others_payments.state01',
            'detail_cxp_others_payments.state02',
            'detail_cxp_others_payments.state03',
            'suppliers_id',
            'detail_cxp_others_payments.companies_id',
        )
            ->selectRaw("m.name as concept_name, n.name as suppliers_name")
            ->leftJoin('miscellaneous_items as m', function ($join) use ($companyId) {
                $join->on('m.code', '=', 'detail_cxp_others_payments.concept')
                    ->where('m.companies_id',  $companyId)
                    ->where('m.miscellaneous_id', 32);
            })
            ->leftJoin('suppliers as n', function ($join) use ($companyId) {
                $join->on('n.id', '=', 'detail_cxp_others_payments.suppliers_id')
                    ->where('n.companies_id',  $companyId);
            })
            ->where('detail_cxp_others_payments.companies_id', $companyId)
            ->where('cxp_payment_id', $iddocument)
            ->get();

        return response()->json([
            'message' => 'Consulta de Detalle del Documento Generada Exitosamente',
            'details' =>  $document,

        ], 201, [], JSON_UNESCAPED_UNICODE);
    }
}
