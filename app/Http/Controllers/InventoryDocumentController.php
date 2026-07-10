<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GeneralDocument;
use App\Models\InventoryConcept;
use App\Models\InventoryDocument;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;

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
            'report_date',
            'purchase_invoice',
            'prefix',
            'documento_purchase',
            'date_from',
            'date_to',
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
            ->selectRaw('TRIM(m.name) as concept_name, (inventory_documents.retefuente + inventory_documents.reteiva + inventory_documents.reteica) as retenciones')
            ->selectRaw(' (inventory_documents.products_discount + inventory_documents.additional_discounts) as descuentos')
            ->leftJoin('inventory_concepts as m', function ($join) use ($companies_id) {
                $join->on('m.code', '=', 'inventory_documents.concept_inv')
                    ->where('m.companies_id', $companies_id);
            })
            ->where('inventory_documents.companies_id', $companies_id)
            ->orderBy('inventory_documents.report_date', 'DESC')
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
            'totaldocument'     => $documents->sum('subtotal'),
        ]);
    }

    public function store(Request $request)
    {
        $companyId  = $request->input('company_id');
        $tipodcto   = $request->input('documento_purchase');
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
            'report_date' => 'nullable|date_format:Y-m-d',
            'date_from'   => 'nullable|date_format:Y-m-d',
            'date_to'     => 'nullable|date_format:Y-m-d|after_or_equal:date_from',

            // Valores decimales financieros (20,2)
            'subtotal'             => 'nullable|numeric|between:0,999999999999999999.99',
            'vatvalue'             => 'nullable|numeric|between:0,999999999999999999.99',
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
        ], 201);
    }

    public function storedetails(Request $request)
    {
        $companyId  = $request->input('company_id');
        $tipodcto   = $request->input('documento_purchase');
        $numerofactura = 0;

        $items          = $request->payload['items'] ?? null;
        $factcompra     = $request->payload['compras'] ?? null;

        return response()->json([
            'message' => 'Compra Creada Exitosamente',
            'details' => $items,
            'factcompra' => $factcompra,
        ], 201);

        // $documento = GeneralDocument::where('companies_id', $companyId)
        //     ->where('type', 'Proveedores')
        //     ->where('typedocument2', 'Compras')
        //     ->where('typedocument3', 'Causaciones')
        //     ->where('code', $tipodcto)
        //     ->where('state', 'Activo')
        //     ->first();

        // if ($documento) {

        //     // Incrementamos el campo 'consecutive' en 1 en la base de datos y en el objeto actual
        //     $documento->increment('consecutive');

        //     // Asignamos el valor actualizado a tu variable
        //     $numerofactura = $documento->consecutive;
        // } else {
        //     // Manejo de error en caso de que no se encuentre el documento configurado
        //     throw new \Exception("No se encontró una configuración de documento activo para los parámetros internos.");
        // }

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
            'report_date' => 'nullable|date_format:Y-m-d',
            'date_from'   => 'nullable|date_format:Y-m-d',
            'date_to'     => 'nullable|date_format:Y-m-d|after_or_equal:date_from',

            // Valores decimales financieros (20,2)
            'subtotal'             => 'nullable|numeric|between:0,999999999999999999.99',
            'vatvalue'             => 'nullable|numeric|between:0,999999999999999999.99',
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
        ], 201);
    }
}
