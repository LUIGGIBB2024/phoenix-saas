<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MiscellaneousItem;
use App\Models\Municipality;
use App\Models\PriceList;
use App\Models\Seller;
use App\Models\TypeDocumentIdentification;
use App\Models\TypeLiability;
use App\Models\TypeRegime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpFoundation\JsonResponse;

class CustomerController extends Controller
{
    public function getCustomers(Request $request): JsonResponse
    {

        $companies_id = $request->input('company_id');

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

        $query = Customer::select(
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



        $customers = $query;

        return response()->json([
            'data' =>       $customers,
            'regimen' =>    $query_regimen,
            'typedocument' => $query_typedocument,
            'sellers' =>  $query_sellers,
            'lists'    => $query_list,
            'zones'    => $query_zonas,
            'routes'    => $query_rutas,
            'typecust'    => $query_typecust,
            'neighborhoods'    => $query_barrios,
            'municipalities'    => $query_ciudades,
            'liabilities'    => $query_respfiscal,
            // 'sgrupos' =>    $query_sgrupos,
            // 'unidades' =>   $query_unidades,
            'total' =>      $customers->count(),
        ]);
    }

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

            'nit'  => 'required|string|max:20',
            'branch' => 'required|string|max:20',
            'dv' => 'nullable|string',
            'patient_id' => 'nullable',
            'code' => 'nullable|string|max:20',
            'provider_code' => 'nullable|string|max:20',

            // Nombres
            'name' => 'required|string|max:255',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'comercial_name' => 'nullable|string|max:255',

            // Contacto y Ubicación
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:20',
            'longitude' => 'nullable|string|max:20',
            'zip_code' => 'nullable|string|max:20',

            // Representante y contacto secundario
            'nit_representative'  => 'nullable|string|max:20',
            'contact_phone'  => 'nullable|string|max:255',
            'name_contact'  => 'nullable|string|max:255',
            'email_contact'  => 'nullable|string|max:255',

            // Salud / Políticas
            'health_contract_number'  => 'nullable|string|max:20',
            'health_policy_number'  => 'nullable|string|max:20',

            // Financiero y Puntos
            'credit_quota'  => 'nullable',
            'deadline_days'  => 'nullable',
            'point'  => 'nullable',
            'accumulated_points'  => 'nullable',

            // Fechas
            'birthday'  => 'nullable',
            'last_purchase_date'  => 'nullable',
            'creation_date'  => 'nullable',

            // Información comercial / Contable
            'economic_activity'  => 'nullable|string|max:20',
            'business_registration'  => 'nullable|string|max:20',
            'sales_account'  => 'nullable',
            'center'  => 'nullable',
            'scenter'  => 'nullable',

            // Llaves Foráneas (Relaciones)
            'health_service_coverage_id' => 'nullable',
            'health_payment_method_id' => 'nullable',
            'branch_id' => 'nullable',
            'route_id' => 'nullable',
            'zone_id' => 'nullable',
            'type_id' => 'nullable',
            'neighborhood_id' => 'nullable',
            'price_list_id' => 'nullable',
            'municipalities_id'  => 'nullable',
            'sellers_id'  => 'nullable',
            'type_document_identification_id'  => 'nullable',
            'companies_id'  => 'nullable',
            'type_regime_id'  => 'nullable',
            'type_liability_id'  => 'nullable',

            // Estados y Enums
            'sex'  => 'nullable',
            'state'  => 'nullable',
            'typeofcurrency'  => 'nullable',
            'retesource'  => 'nullable',
            'reteiva'  => 'nullable',
            'reteica'  => 'nullable',
            'declare_income' => 'nullable',
            'control_points'  => 'nullable',
            'capture_signature'  => 'nullable',

        ]);

        $data['companies_id'] = $companyId;

        $customer = \App\Models\Customer::create($data);

        return response()->json([
            'message' => 'Cliente creado exitosamente',
            'customers' => $customer,
        ], 201);
    }

    public function update(Request $request, $id)
    {

        Log::info('ENTRANDO A STORE', ['method' => $request->method()]);
        $companyId  = $request->input('company_id');

        $customer = Customer::findOrFail($id);

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

            'nit'  => 'required|string|max:20',
            'branch' => 'required|string|max:20',
            'dv' => 'nullable|string',
            'patient_id' => 'nullable',
            'code' => 'nullable|string|max:20',
            'provider_code' => 'nullable|string|max:20',

            // Nombres
            'name' => 'required|string|max:255',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'comercial_name' => 'nullable|string|max:255',

            // Contacto y Ubicación
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:20',
            'longitude' => 'nullable|string|max:20',
            'zip_code' => 'nullable|string|max:20',

            // Representante y contacto secundario
            'nit_representative'  => 'nullable|string|max:20',
            'contact_phone'  => 'nullable|string|max:255',
            'name_contact'  => 'nullable|string|max:255',
            'email_contact'  => 'nullable|string|max:255',

            // Salud / Políticas
            'health_contract_number'  => 'nullable|string|max:20',
            'health_policy_number'  => 'nullable|string|max:20',

            // Financiero y Puntos
            'credit_quota'  => 'nullable',
            'deadline_days'  => 'nullable',
            'point'  => 'nullable',
            'accumulated_points'  => 'nullable',

            // Fechas
            'birthday'  => 'nullable',
            'last_purchase_date'  => 'nullable',
            'creation_date'  => 'nullable',

            // Información comercial / Contable
            'economic_activity'  => 'nullable|string|max:20',
            'business_registration'  => 'nullable|string|max:20',
            'sales_account'  => 'nullable',
            'center'  => 'nullable',
            'scenter'  => 'nullable',

            // Llaves Foráneas (Relaciones)
            'health_service_coverage_id' => 'nullable',
            'health_payment_method_id' => 'nullable',
            'branch_id' => 'nullable',
            'route_id' => 'nullable',
            'zone_id' => 'nullable',
            'type_id' => 'nullable',
            'neighborhood_id' => 'nullable',
            'price_list_id' => 'nullable',
            'municipalities_id'  => 'nullable',
            'sellers_id'  => 'nullable',
            'type_document_identification_id'  => 'nullable',
            'companies_id'  => 'nullable',
            'type_regime_id'  => 'nullable',
            'type_liability_id'  => 'nullable',

            // Estados y Enums
            'sex'  => 'nullable',
            'state'  => 'nullable',
            'typeofcurrency'  => 'nullable',
            'retesource'  => 'nullable',
            'reteiva'  => 'nullable',
            'reteica'  => 'nullable',
            'declare_income' => 'nullable',
            'control_points'  => 'nullable',
            'capture_signature'  => 'nullable',
        ]);

        $data['companies_id']       = $companyId;
        // $data['group']              = $request->input('namegroupselected');
        // $data['subgroup']           = $request->input('namesgroupselected');
        // $data['unit_of_measure']    = $request->input('namemeasureselected');

        // Mapeamos los campos que vienen con nombre distinto desde el frontend
        // $request->merge([
        //     'group'           => $request->input('namegroupselected'),
        //     'subgroup'        => $request->input('namesgroupselected'),
        //     'unit_of_measure' => $request->input('namemeasureselected'),
        // ]);

        try {
            $customer->update($data);
            return response()->json([
                'message' => 'Cliente Actualizado exitosamente',
                'customers' => $customer,
            ], 201);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'message' => 'El servidor tardó demasiado en responder.',
                'error' => $e->getMessage()
            ], 408);
        }
    }

    public function destroy($id)
    {
        $company = Customer::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Cliente Eliminado Exitosamente']);
    }
}
