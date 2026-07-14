<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;

use App\Http\Controllers\Controller;
use App\Models\MiscellaneousItem;
use App\Models\PriceDetail;
use App\Models\Product;
use App\Models\UnitMeasur;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        Log::info('ENTRANDO A STORE', ['method' => $request->method()]);

        $companyId  = $request->input('company_id');

        $data = $request->validate([
            'code'              => 'required|string|max:20',
            'name'              => 'required|string|max:255',
            'codereference'     => 'nullable|string|max:20',
            'unit_of_measure'   => 'nullable|string|max:20',
            'presentation'      => 'nullable|string|max:20',
            'percent'           => 'nullable',
            'sale_value'        => 'nullable',
            'cost'              => 'nullable',
            'location'          => 'nullable|string|max:20',
            'control_id'        => 'nullable',
            'typeofproduct'     => 'nullable',
            'require_scale'     => 'nullable',
            'billable'          => 'nullable',
            'group'             => 'nullable|string|max:20',
            'subgroup'          => 'nullable|string|max:20',
            'division'          => 'nullable|string|max:20',
            'category'          => 'nullable|string|max:20',
            'family'            => 'nullable|string|max:20',
            'namephoto'         => 'nullable|string|max:50',
            'routephoto'        => 'nullable|string|max:255',
            'observations'      => 'nullable|string|max:255',
            'cups'              => 'nullable|string|max:20',
            'alternate_code'    => 'nullable|string|max:20',
            'cie10_code'        => 'nullable|string|max:20',
            'invima_register'   => 'nullable|string|max:20',
            'units_per_packaging'  => 'nullable|string|max:20',
            'weight_volume'        => 'nullable',
            'conversion_factor'    => 'nullable',
            'date_last_purchase'   => 'nullable',
            'minimum_stock'        => 'nullable',
            'maximum_stock'        => 'nullable',
            'profitability'        => 'nullable',
            'consumption_tax'      => 'nullable',
            'listvalue1'           => 'nullable',
            'listvalue2'           => 'nullable',
            'listvalue3'           => 'nullable',
            'companies_id'         => 'required',
            'state'                => 'string',
        ]);

        $data['companies_id'] = $companyId;

        $product = \App\Models\Product::create($data);

        $this->updatelist($request);

        return response()->json([
            'message' => 'Empresa creada exitosamente',
            'products' => $product,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        // return response()->json([
        //     'Dato Request: ' => $request->all(),
        // ], 201);

        Log::info('ENTRANDO A STORE', ['method' => $request->method()]);
        $companyId  = $request->input('company_id');

        $product = Product::findOrFail($id);

        $data = $request->validate([
            'code'              => 'required|string|max:20',
            'name'              => 'required|string|max:255',
            'codereference'     => 'nullable|string|max:20',
            'unit_of_measure'   => 'nullable|string|max:20',
            'presentation'      => 'nullable|string|max:20',
            'percent'           => 'nullable',
            'sale_value'        => 'nullable',
            'cost'              => 'nullable',
            'location'          => 'nullable|string|max:20',
            'control_id'        => 'nullable',
            'typeofproduct'     => 'nullable',
            'require_scale'     => 'nullable',
            'billable'          => 'nullable',
            'group'             => 'nullable|string|max:20',
            'subgroup'          => 'nullable|string|max:20',
            'division'          => 'nullable|string|max:20',
            'category'          => 'nullable|string|max:20',
            'family'            => 'nullable|string|max:20',
            'namephoto'         => 'nullable|string|max:50',
            'routephoto'        => 'nullable|string|max:255',
            'observations'      => 'nullable|string|max:255',
            'cups'              => 'nullable|string|max:20',
            'alternate_code'    => 'nullable|string|max:20',
            'cie10_code'        => 'nullable|string|max:20',
            'invima_register'   => 'nullable|string|max:20',
            'units_per_packaging'  => 'nullable|string|max:20',
            'weight_volume'        => 'nullable',
            'conversion_factor'    => 'nullable',
            'date_last_purchase'   => 'nullable',
            'minimum_stock'        => 'nullable',
            'maximum_stock'        => 'nullable',
            'profitability'        => 'nullable',
            'consumption_tax'      => 'nullable',
            'listvalue1'           => 'nullable',
            'listvalue2'           => 'nullable',
            'listvalue3'           => 'nullable',
            'companies_id'         => 'required',
            'state'                => 'string',
        ]);

        $data['companies_id']       = $companyId;
        $data['group']              = $request->input('namegroupselected');
        $data['subgroup']           = $request->input('namesgroupselected');
        $data['unit_of_measure']    = $request->input('namemeasureselected');

        // Mapeamos los campos que vienen con nombre distinto desde el frontend
        // $request->merge([
        //     'group'           => $request->input('namegroupselected'),
        //     'subgroup'        => $request->input('namesgroupselected'),
        //     'unit_of_measure' => $request->input('namemeasureselected'),
        // ]);

        try {
            $product->update($data);

            $this->updatelist($request);

            return response()->json([
                'message' => 'Producto Actualizado exitosamente (200)',
                'products' => $product,
            ], 201);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'message' => 'El servidor de la DIAN tardó demasiado en responder.',
                'error' => $e->getMessage()
            ], 408);
        }
    }

    public function updatelist(Request $request)
    {
        $companyId      = $request->input('companies_id');
        $product        = $request->input('code');
        $codlista       = "01";
        $percent        = $request->input('percent');
        $valor          = $request->input('sale_value');
        $producto       = Product::where('code', $product)->where('companies_id', $companyId)->first();
        $idproduct      = $producto->id;
   
        try {
            $reg_prod = PriceDetail::updateOrCreate(
                [
                    // Campos únicos para localizar la fila exacta sin pisar otros productos
                    'code'          => $codlista,
                    'product'       => $product,
                    'companies_id'  => $companyId,
                ],
                [
                    'vat'                    => $percent,
                    'price'                  => $valor,
                    'priceunit'              => $valor,
                    'beforevat'              => $valor,
                    'products_id'            => $idproduct,
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

        return response()->json(['message' => 'Precios Actualizado Exitosamente']);
    }

    public function getProducts(Request $request): JsonResponse
    {

        $companies_id = $request->input('company_id');

        // $query_grupos    = MiscellaneousItem::select('code', 'name')->where('miscellaneous_id', 4)->where('companies_id', $companies_id)->orderBy('name')->get();
        // $query_sgrupos   = MiscellaneousItem::select('code', 'name')->where('miscellaneous_id', 5)->where('companies_id', $companies_id)->orderBy('name')->get();
        // $query_unidades  = UnitMeasur::select('code', 'name')->orderBy('name')->get();

        $query_grupos = MiscellaneousItem::select(DB::raw('TRIM(code) as code'), DB::raw('TRIM(name) as name'))->where('miscellaneous_id', 4)->where('companies_id', $companies_id)
            ->orderBy('name')
            ->get();

        $query_sgrupos = MiscellaneousItem::select(DB::raw('TRIM(code) as code'), DB::raw('TRIM(name) as name'))->where('miscellaneous_id', 5)->where('companies_id', $companies_id)
            ->orderBy('name')
            ->get();

        $query_unidades = UnitMeasur::select(DB::raw('TRIM(code) as code'), DB::raw('TRIM(name) as name'))->orderBy('name')->get();

        $query = Product::select(
            'products.id', // Es mejor anteponer 'products.' para evitar ambigüedades
            'products.code',
            'products.name',
            'codereference',
            'unit_of_measure',
            'presentation',
            'products.percent',
            'sale_value',
            'cost',
            'location',
            'control_id',
            'typeofproduct',
            'require_scale',
            'billable',
            'products.group',
            'subgroup',
            'division',
            'category',
            'family',
            'namephoto',
            'routephoto',
            'observations',
            'cups',
            'alternate_code',
            'cie10_code',
            'invima_register',
            'units_per_packaging',
            'weight_volume',
            'conversion_factor',
            'date_last_purchase',
            'minimum_stock',
            'maximum_stock',
            'profitability',
            'consumption_tax',
            'listvalue1',
            'listvalue2',
            'listvalue3',
            'products.companies_id',
            'state'
        )
            // Usamos un alias 'm' para solucionar el problema del guion medio y escribir menos código
            ->selectRaw('m.name as group_name, n.name sgroup_name, o.name as measure_name')
            ->leftJoin('miscellaneous_items as m', function ($join) use ($companies_id) {
                $join->on('m.code', '=', 'products.group')
                    ->where('m.miscellaneous_id', '=', 4)
                    ->where('m.companies_id', '=', $companies_id);
            })
            ->leftJoin('miscellaneous_items as n', function ($join) use ($companies_id) {
                $join->on('n.code', '=', 'products.subgroup')
                    ->where('n.companies_id', '=', $companies_id)
                    ->where('n.miscellaneous_id', '=', 5);
            })
            ->leftJoin('unit_measures as o', function ($join) {
                $join->on('o.code', '=', 'products.unit_of_measure');
            })
            // Aseguramos que el filtro del WHERE use el alias o especifique la tabla correcta
            ->where('products.companies_id', $companies_id)
            ->orderBy('products.name')
            ->get();

        $products = $query;

        return response()->json([
            'data' =>       $products,
            'grupos' =>     $query_grupos,
            'sgrupos' =>    $query_sgrupos,
            'unidades' =>   $query_unidades,
            'total' =>      $products->count(),
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Producto Eliminado Exitosamente']);
    }
}
