<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use Illuminate\Http\Request;
use App\Models\MiscellaneousItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpFoundation\JsonResponse;

class InventoryBalanceController extends Controller
{
    public function getBalances(Request $request): JsonResponse
    {

        $companies_id = $request->input('company_id');
        $process_year = $request->input('process_year');

        // $query_zonas    = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 8)->where('companies_id', $companies_id)->orderBy('name')->get();
        // $query_rutas    = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 9)->where('companies_id', $companies_id)->orderBy('name')->get();
        // $query_typecust    = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 10)->where('companies_id', $companies_id)->orderBy('name')->get();
        // $query_barrios    = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 18)->where('companies_id', $companies_id)->orderBy('name')->get();
        // $query_ciudades   = Municipality::select('id', 'code', 'name')->orderBy('name')->get();

        $query_grupos   = MiscellaneousItem::select('code', 'name')->where('miscellaneous_id', 4)->where('companies_id', $companies_id)->orderBy('name')->get();
        // $query_unidades  = UnitMeasur::select('code', 'name')->orderBy('name')->get();

        // $query_regimen      = TypeRegime::select('id', 'name')->get();
        // $query_typedocument = TypeDocumentIdentification::select('id', 'name', 'code', 'code_show')->get();
        // $query_list         = PriceList::select('id', 'code', 'name')->where('companies_id', $companies_id)->get();
        // $query_sellers      = Seller::select('id', 'code', 'name')->where('companies_id', $companies_id)->get();
        // $query_respfiscal   = TypeLiability::select('id', 'code', 'name')->get();

        // $query_grupos = MiscellaneousItem::select(DB::raw('TRIM(code) as code'), DB::raw('TRIM(name) as name'))->where('miscellaneous_id', 4)->where('companies_id', $companies_id)
        //     ->orderBy('name')
        //     ->get();

        // $query_sgrupos = MiscellaneousItem::select(DB::raw('TRIM(code) as code'), DB::raw('TRIM(name) as name'))->where('miscellaneous_id', 5)->where('companies_id', $companies_id)
        //     ->orderBy('name')
        //     ->get();

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
            ->selectRaw('TRIM(m.name) as product_name, TRIM(n.name) as group_name, round(inventory_balances.cost*inventory_balances.quantity,2) as subtotal, m.percent, m.group')
            ->leftJoin('products as m', function ($join) {
                $join->on('m.id', '=', 'inventory_balances.products_id');
            })
            ->leftJoin('miscellaneous_items as n', function ($join) {
                $join->on('n.code', '=', 'm.group')
                    ->where('n.miscellaneous_id', '=', 4);
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



        $balances = $query;

        return response()->json([
            'data'          => $balances,
            'grupos'        => $query_grupos,
            'total'         => $balances->count(),
            'totalinventory' =>  $balances->sum('subtotal'),
        ]);
    }

    public function update(Request $request, $id)
    {
        // return response()->json([
        //     'Dato Request: ' => $request->all(),
        // ], 201);

        Log::info('ENTRANDO A UPDATE', ['method' => $request->method()]);
        $companyId  = $request->input('company_id');

        $balance = InventoryBalance::findOrFail($id);

        $data = $request->validate([
            'quantity'          => 'nullable',
            'previous_balance'  => 'nullable',
            'cost'              => 'nullable',
            'cost00'            => 'nullable',
            'cost01'            => 'nullable',
            'cost02'            => 'nullable',
            'cost03'            => 'nullable',
            'cost04'            => 'nullable',
            'cost05'            => 'nullable',
            'cost06'            => 'nullable',
            'cost07'            => 'nullable',
            'cost08'            => 'nullable',
            'cost09'            => 'nullable',
            'cost10'            => 'nullable',
            'cost11'            => 'nullable',
            'cost12'            => 'nullable',
        ]);

        //$data['companies_id']       = $companyId;


        // Mapeamos los campos que vienen con nombre distinto desde el frontend
        // $request->merge([
        //     'group'           => $request->input('namegroupselected'),
        //     'subgroup'        => $request->input('namesgroupselected'),
        //     'unit_of_measure' => $request->input('namemeasureselected'),
        // ]);

        try {
            $balance->update($data);

            return response()->json([
                'message' => 'Registro Actualizado exitosamente',
                'products' => $balance,
            ], 201);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'message' => 'El servidor tardó demasiado en responder.',
                'error' => $e->getMessage()
            ], 408);
        }
    }
}
