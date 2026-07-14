<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GeneralDocument;
use App\Models\InventoryBalance;
use App\Models\InventoryConcept;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\PurchasesInvoice;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Carbon;

class InventoryDocumentController extends Controller
{
    public function getDocuments(Request $request): JsonResponse
    {
        $companies_id       = $request->input('company_id');
        $process_year       = $request->input('process_year');
        $products           = Product::orderBy('name', 'ASC')->get();
        $proveedores        = Supplier::where('companies_id', $companies_id)->get();
        $compras            = GeneralDocument::where('typedocument2', 'Compras')->where('companies_id', $companies_id)->get();
        $entradas_salidas   = GeneralDocument::whereIn('typedocument2', ['Entradas', 'Salidas'])->where('companies_id', $companies_id)->get();
        $dctos_cxp          = GeneralDocument::where('typedocument3', 'Causaciones')->where('companies_id', $companies_id)->get();
        $cptos_compras      = InventoryConcept::where('documents', 'Compras')->where('companies_id', $companies_id)->get();
        $cptos_es           = InventoryConcept::whereIn('documents', ['Otras Entradas', 'Otras Salidas'])->where('companies_id', $companies_id)->get();
        $query = InventoryDocument::select(
            'inventory_documents.id',
            'nit',
            'branch',
            'inventory_documents.name',
            'number',
            'concept_inv',
            'concept_class',
            //'report_date',
            'purchase_invoice',
            'prefix',
            'documento_purchase',
            //'date_from',
            //'date_to',
            'subtotal',
            'vatvalue',
            'reteiva',
            'reteica',
            'products_discount',
            'additional_discounts',
            'additional_value',
            'freight',
            'total_purchases',
            'plate',
            'inventory_documents.type',
            'type_of_purchase',
            'inventory_documents.state',
            'inventory_documents.state01',
            'inventory_documents.state02',
            'inventory_documents.state03',
            'inventory_documents.companies_id',
            'proyect',
            'sproyect',
            'center',
            'activity'
        )
            // Usamos un alias 'm' para solucionar el problema del guion medio y escribir menos código
            // Forzamos el formato YYYY-MM-DD para las fechas
            ->selectRaw("DATE_FORMAT(inventory_documents.report_date, '%Y-%m-%d') as report_date")
            ->selectRaw("DATE_FORMAT(inventory_documents.date_from, '%Y-%m-%d') as date_from")
            ->selectRaw("DATE_FORMAT(inventory_documents.date_to, '%Y-%m-%d') as date_to")

            ->selectRaw('TRIM(m.name) as concept_name, (inventory_documents.retefuente + inventory_documents.reteiva + inventory_documents.reteica) as retenciones')
            ->selectRaw(' (inventory_documents.products_discount + inventory_documents.additional_discounts) as descuentos')
            ->leftJoin('inventory_concepts as m', function ($join) use ($companies_id) {
                $join->on('m.code', '=', 'inventory_documents.concept_inv')
                    ->where('m.companies_id', $companies_id);
            })
            ->where('inventory_documents.companies_id', $companies_id)
            ->orderBy('inventory_documents.created_at', 'DESC')
            ->get();

        $documents = $query;

        return response()->json([
            'data'              => $documents,
            'docspurchases'     => $compras,
            'docsinputsoutputs' => $entradas_salidas,
            'suppliers'         => $proveedores,
            'cptpurchases'      => $cptos_compras,
            'cptes'             => $cptos_es,
            'dctoscxp'          => $dctos_cxp,
            'products'          => $products,
            'totaldocument'     => $documents->count(),
        ]);
    }

    public function store(Request $request)
    {
        $companyId  = $request->input('company_id');
        $tipodcto   = $request->input('documento_purchase');
        $concepto   = $request->input('concept_inv');
        $cpto   = InventoryConcept::where('code', $concepto)->where('companies_id', $companyId)->first();
        $conceptname = $cpto->name;

        $numerofactura = 0;

        $documento = GeneralDocument::where('companies_id', $companyId)
            ->where('type', 'Proveedores')
            ->where('typedocument2', 'Compras')
            ->where('typedocument3', 'Causaciones')
            ->where('code', $tipodcto)
            ->where('state', 'Activo')
            ->first();


        if ($documento) {

            // Incrementamos el campo 'consecutive' en 1 en la base de datos y en el objeto actual
            $documento->increment('consecutive');

            // Asignamos el valor actualizado a tu variable
            $numerofactura = $documento->consecutive;
        } else {
            // Manejo de error en caso de que no se encuentre el documento configurado
            throw new \Exception("No se encontró una configuración de documento activo para los parámetros internos.");
        }

        // Validamos los datos entrantes
        $data = $request->validate([
            'nit'                  => 'nullable|string|max:20',
            'branch'               => 'nullable|string|max:20',
            'name'                 => 'nullable|string|max:255',
            'number'               => 'nullable|integer',
            'concept_inv'          => 'nullable|string|max:20',
            'concept_class'        => 'nullable|string|max:20',
            'purchase_invoice'     => 'nullable|integer',
            'prefix'               => 'nullable|string|max:20',
            'documento_purchase'   => 'nullable|string|max:20',
            // Cambia esto en tu array de validación:
            'report_date'          => 'nullable|date_format:Y-m-d',
            'date_from'            => 'nullable|date_format:Y-m-d',
            'date_to'              => 'nullable|date_format:Y-m-d',

            // Valores decimales financieros (20,2)
            'subtotal'             => 'nullable|numeric|between:0,999999999999999999.99',
            'vatvalue'             => 'nullable|numeric|between:0,999999999999999999.99',
            'retefuente'           => 'nullable|numeric|between:0,999999999999999999.99',
            'reteiva'              => 'nullable|numeric|between:0,999999999999999999.99',
            'reteica'              => 'nullable|numeric|between:0,999999999999999999.99',
            'products_discount'    => 'nullable|numeric|between:0,999999999999999999.99',
            'additional_discounts' => 'nullable|numeric|between:0,999999999999999999.99',
            'additional_value'     => 'nullable|numeric|between:0,999999999999999999.99',
            'freight'              => 'nullable|numeric|between:0,999999999999999999.99',
            'total_purchases'      => 'nullable|numeric|between:0,999999999999999999.99',

            'plate'                => 'nullable|string|max:20',

            // Enums de la base de datos
            'type'                 => ['required', Rule::in(['Compras', 'Otras Entradas', 'Otras Salidas', 'Traslados', 'Devolución', 'Otras'])],
            'type_of_purchase'     => ['required', Rule::in(['Contado', 'Crédito'])],
            'state'                => ['nullable', Rule::in(['Activo', 'Eliminado', 'Pendiente'])],

            'state01'              => 'nullable|string|max:20',
            'state02'              => 'nullable|string|max:20',
            'state03'              => 'nullable|string|max:20',

            'proyect'              => 'nullable|string|max:20',
            'sproyect'             => 'nullable|string|max:20',
            'center'               => 'nullable|string|max:20',
            'activity'             => 'nullable|string|max:20',
            'observations'         => 'nullable',
        ]);

        $data['companies_id']   = $companyId;
        $data['number']         = $numerofactura;
        $data['state']          = 'Pendiente';

        $purchase = \App\Models\InventoryDocument::create($data);

        return response()->json([
            'message' => 'Compra Creada Exitosamente',
            'purchase' => $purchase,
            'concept_name' => $conceptname,
        ], 201);
    }

    public function storedetails(Request $request)
    {
        $tipodcto   = $request->input('documento_purchase');
        $numerofactura = 0;

        $items          = $request->payload['items'] ?? null;
        $factcompra     = $request->payload['compras'] ?? null;

        $idcompra       = $factcompra['id'];


        $compras        = InventoryDocument::find($idcompra);

        $companyId      = $compras->companies_id;

        $tipocompra     = $compras['type_of_purchase'];
        $number         = $compras['number'];

        $concepto   = $compras->concept_inv;

        $cpto   = InventoryConcept::where('code', $concepto)->where('companies_id', $companyId)->first();
        $conceptname = $cpto->name;

        // Actualizando Información de Compras (Totales)
        if ($compras) {
            // 2. Asignas los nuevos valores a cada campo           
            $compras->subtotal          = $factcompra['subtotal'];
            $compras->vatvalue          = $factcompra['vatvalue'];
            $compras->retefuente        = $factcompra['retefuente'];
            $compras->reteiva           = $factcompra['reteiva'];
            $compras->reteica           = $factcompra['reteica'];
            $compras->products_discount = $factcompra['discount'];
            $compras->total_purchases   = $factcompra['total'];
            $compras->state             = 'Activo';

            // 3. Guardas los cambios en la base de datos
            $compras->save();

            if ($tipocompra == 'Crédito') {
                // Procesar Factura de Cuentas por Pagar
                $this->SaveCxP($compras);
            }
        } else {
            return response()->json(['message' => 'No se encontró la compra'], 404);
        }

        // Procesa Detalle de la Compra
        $this->SaveDetalle($items, $compras);

        return response()->json([
            'message' => 'Compra Creada Exitosamente',
            'purchase' => $compras,
            'concept_name' => $conceptname,
        ], 201);
    }

    public function SaveDetalle($items, $compras)
    {
        //$table->index(['companies_id', 'report_date', 'number', 'code', 'store', 'idregister'], 'idx_companies_report_code');
        $companyId = $compras['companies_id'];
        $number    = $compras['number'];
        $fecha     = $compras['report_date'];
        $year      = Carbon::parse($fecha)->year;
        $month     = Carbon::parse($fecha)->month;
        $nit       = $compras['nit'];
        $branch    = $compras['branch'];
        $idcompra  = $compras['id'];
        $concept   = $compras['concept_inv'];
        $cptclass  = $compras['concept_class'];
        if (is_array($items) && count($items) > 0) {
            $row = 0;

            // 2. Lo recorremos con un foreach
            foreach ($items as $item) {
                // 1. Limpieza de strings con espacios en blanco fijos del frontend
                $code = isset($item['code']) ? trim($item['code']) : null;
                $name = isset($item['name']) ? trim($item['name']) : null;

                // 2. Casteo seguro de valores numéricos
                $quantity = floatval($item['quantity'] ?? 0);
                $vat      = floatval($item['vat'] ?? 0);
                $discount = floatval($item['discount'] ?? 0);
                $cost     = floatval($item['cost'] ?? 0);

                $row++; // Incremento de tu fila/consecutivo
                $store = $item['store'] ?? '1';

                // 3. Cálculos matemáticos corregidos
                $valuedsc = round($quantity * $cost * (1 + ($vat / 100)), 0);
                $subtotal = round($quantity * $cost, 0) - $valuedsc;

                // Calcular Costos Promedios
                $saldos     = InventoryBalance::where('year', $year)->where('code', $code)->where('store', $store)->where('companies_id', $companyId)->first();
                $producto   = Product::where('code', $code)->where('companies_id', $companyId)->first();
                $nombreCampo = 'cost' . Carbon::parse($fecha)->format('m');


                $CostoActual    = $saldos->quantity * $saldos->cost;
                $CostoDcto      = $quantity *  $cost;
                $CostoPromedio  = ($CostoActual +  $CostoDcto) / ($saldos->quantity + $quantity);
                $saldos->lastcost = $saldos->cost;
                $saldos->quantity += $quantity;
                $saldos->cost   = $CostoPromedio;

                // Actualizar Costo en el Mes de Proceso
                if (in_array($nombreCampo, ['cost00', 'cost01', 'cost02', 'cost03', 'cost04', 'cost05', 'cost06', 'cost07', 'cost08', 'cost09', 'cost10', 'cost11', 'cost12'])) {
                    $saldos->$nombreCampo = $CostoPromedio;
                }

                $saldos->save();

                // Actualizar Información de Costos en la Tabla de Productos
                $producto->cost = $CostoPromedio;
                $producto->save();

                try {
                    $reg_fact = InventoryMovement::updateOrCreate(
                        [
                            // Campos únicos para localizar la fila exacta sin pisar otros productos
                            'number'       => $number,
                            'report_date'  => $fecha,
                            'code'         => $code,
                            'companies_id' => $companyId,
                            'idregister'   => $row // 💡 Crucial para diferenciar si el mismo código entra 2 veces
                        ],
                        [
                            'code'                   => $code,
                            'name'                   => $name,
                            'store'                  => $store,
                            'batch'                  => '',
                            'report_date'            => $fecha,
                            'number'                 => $number,
                            'prefix'                 => '',
                            'concept_inv'            => $concept,
                            'concept_class'          => $cptclass,
                            'nit'                    => $nit,
                            'nit2'                   => '',
                            'branch'                 => $branch,
                            'health_batch'           => '',
                            'serial'                 => '',
                            'amount'                 => $quantity, // Se guarda de forma segura el float
                            'amount1'                => 0,
                            'vat'                    => $vat,
                            'discount1'              => $discount,
                            'discount2'              => 0,
                            'discount3'              => 0,
                            'unit_cost'              => $cost,
                            'sale_price'             => 0,
                            'parcial_value'          => $subtotal,
                            'type'                   => 'COMP',
                            'idregister'             => $row,
                            'state'                  => 'Activo',
                            'state01'                => '',
                            'state02'                => '',
                            'state03'                => '',
                            'companies_id'           => $companyId,
                            'inventory_documents_id' => $idcompra,
                            'proyect'                => '',
                            'sproyect'               => '',
                            'center'                 => '',
                            'activity'               => '',
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

                // Ejemplo: Tu lógica para guardar o procesar cada ítem
            }
        } else {
            // Opcional: Manejar el caso de que no vengan ítems
        }
    }

    public function SaveCxP($compras)
    {
        $numerofactura = intval($compras['purchase_invoice']);
        $prefijo       = $compras['prefix'] ?? '';
        $nit           = $compras['nit'] ?? '';
        $subtotal      = $compras['subtotal'];
        $total         = $compras['total_purchases'];
        $company_id    = $compras['companies_id'];

        try {
            //dd($item);
            $reg_fact       = PurchasesInvoice::updateOrCreate(
                ['number' => $numerofactura, 'prefix' => $prefijo, 'supplier' => $nit, 'companies_id' => $company_id],
                [
                    'branch'               => $compras['branch'],
                    'date_issue'           => $compras['date_from'],
                    'expiration_date'      => $compras['date_to'],
                    'document_name'        => $compras['documento_purchase'],
                    'supplier_name'        => $compras['name'],
                    'subtotal'             => $subtotal,
                    'discounts'            => $compras['products_discount'] + $compras['additional_discounts'],
                    'vatvalue'             => $compras['vatvalue'],
                    'retefuente'           => $compras['retefuente'],
                    'reteiva'              => $compras['reteiva'],
                    'reteica'              => $compras['reteica'],
                    'impoconsumo'          => 0,
                    'total_purchase'       => $total,
                    'cufe'                 => '',
                    'state'                => 'Activo',
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
        return response()->json([
            'message' => 'Compra Creada Exitosamente',
            'compras' => $compras,

        ], 201, [], JSON_UNESCAPED_UNICODE);
    }
}
