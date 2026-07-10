<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MiscellaneousItem;
use App\Models\Municipality;
use App\Models\Supplier;
use App\Models\TypeDocumentIdentification;
use App\Models\TypeLiability;
use App\Models\TypeRegime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpFoundation\JsonResponse;

class SupplierController extends Controller
{
    public function getSuppliers(Request $request): JsonResponse
    {

        $companies_id = $request->input('company_id');

        $query_typesupl    = MiscellaneousItem::select('id', 'code', 'name')->where('miscellaneous_id', 11)->where('companies_id', $companies_id)->orderBy('name')->get();
        $query_ciudades   = Municipality::select('id', 'code', 'name')->orderBy('name')->get();

        // $query_sgrupos   = MiscellaneousItem::select('code', 'name')->where('miscellaneous_id', 5)->where('companies_id', $companies_id)->orderBy('name')->get();        
        // $query_unidades  = UnitMeasur::select('code', 'name')->orderBy('name')->get();

        $query_regimen      = TypeRegime::select('id', 'name')->get();
        $query_typedocument = TypeDocumentIdentification::select('id', 'name', 'code', 'code_show')->get();
        $query_respfiscal   = TypeLiability::select('id', 'code', 'name')->get();

        try {
            $query = Supplier::select(
                'suppliers.id',
                'nit',
                'dv',
                'branch',
                'patient_id',
                'suppliers.code',
                'suppliers.name',
                'firstname',
                'lastname',
                'comercial_name',
                'address',
                'phone',
                'email',
                'contact_phone',
                'name_contact',
                'email_contact',
                'position_contact',
                'latitude',
                'longitude',
                'economic_activity',
                'zip_code',
                'business_registration',
                'bank_account_id',
                'branch_id',
                'type_id',
                'municipalities_id',
                'type_document_identification_id',
                'companies_id',
                'type_regime_id',
                'type_liability_id',
                'stype_of_taxpayer',
                'state',
                'retesource',
                'reteiva',
                'reteica',
            )
                // Usamos un alias 'm' para solucionar el problema del guion medio y escribir menos código
                ->selectRaw('m.name as city_name')
                ->leftJoin('municipalities as m', function ($join) {
                    $join->on('m.id', '=', 'suppliers.municipalities_id');
                })
                ->where('suppliers.companies_id', $companies_id)
                ->orderBy('suppliers.name')
                ->get();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el listado de proveedores.',
                'error'   => $e->getMessage() // Recuerda ocultar o manejar el detalle del error en producción
            ], 500);
        }

        $suppliers = $query;

        return response()->json([
            'data' =>       $suppliers,
            'regimen' =>    $query_regimen,
            'typedocument' => $query_typedocument,
            'typesupl'    => $query_typesupl,
            'municipalities'    => $query_ciudades,
            'liabilities'    => $query_respfiscal,
            'total' =>      $suppliers->count(),
        ]);
    }

    public function store(Request $request)
    {
        Log::info('ENTRANDO A STORE', ['method' => $request->method()]);

        $companyId  = $request->input('company_id');

        $data = $request->validate([
            'nit'                             => 'nullable|string|max:20',
            'branch'                          => 'nullable|string|max:20',
            'dv'                              => 'nullable|string|max:1',
            'patient_id'                      => 'nullable|string|max:20',
            'code'                            => 'nullable|string|max:20',
            'name'                            => 'nullable|string|max:255',
            'firstname'                       => 'nullable|string|max:255',
            'lastname'                        => 'nullable|string|max:255',
            'comercial_name'                  => 'nullable|string|max:255',
            'address'                         => 'nullable|string|max:255',
            'phone'                           => 'nullable|string|max:50',
            'email'                           => 'nullable|email|max:255',
            'contact_phone'                   => 'nullable|string|max:50',
            'name_contact'                    => 'nullable|string|max:255',
            'email_contact'                   => 'nullable|email|max:255',
            'position_contact'                => 'nullable|string|max:255',
            'latitude'                        => 'nullable|string|max:20',
            'longitude'                       => 'nullable|string|max:20',
            'economic_activity'               => 'nullable|string|max:20',
            'zip_code'                        => 'nullable|string|max:20',
            'business_registration'           => 'nullable|string|max:20',

            // Validaciones para llaves foráneas (puedes cambiar 'nullable' por 'required' si el flujo de tu SaaS lo exige)
            'bank_account_id'                 => 'nullable|integer|exists:bank_accounts,id',
            'branch_id'                       => 'nullable|integer|exists:miscellaneous_items,id',
            'type_id'                         => 'nullable|integer|exists:miscellaneous_items,id',
            'municipalities_id'               => 'nullable|integer|exists:municipalities,id',
            'type_document_identification_id' => 'nullable|integer|exists:type_document_identifications,id',
            'companies_id'                    => 'nullable|integer|exists:companies,id',
            'type_regime_id'                  => 'nullable|integer|exists:type_regimes,id',
            'type_liability_id'               => 'nullable|integer|exists:type_liabilities,id',

            // Campos con ENUM exactos de la base de datos
            'stype_of_taxpayer'               => 'nullable|in:Gran Contribuyente,Auto Retenedor,Régimen Común,Régimen Simplificado,Otro',
            'state'                           => 'nullable|in:Activo,Inactivo',
            'retesource'                      => 'nullable|in:Si,No',
            'reteiva'                         => 'nullable|in:Si,No',
            'reteica'                         => 'nullable|in:Si,No',
        ]);

        $data['companies_id'] = $companyId;

        $supplier = \App\Models\Supplier::create($data);

        return response()->json([
            'message' => 'Proveedor Creado Exitosamente',
            'suppliers' => $supplier,
        ], 201);
    }

    public function update(Request $request, $id)
    {

        Log::info('ENTRANDO A STORE', ['method' => $request->method()]);
        $companyId  = $request->input('company_id');

        $supplier = Supplier::findOrFail($id);

        $data = $request->validate([
            'nit'                             => 'nullable|string|max:20',
            'branch'                          => 'nullable|string|max:20',
            'dv'                              => 'nullable|string|max:1',
            'patient_id'                      => 'nullable|string|max:20',
            'code'                            => 'nullable|string|max:20',
            'name'                            => 'nullable|string|max:255',
            'firstname'                       => 'nullable|string|max:255',
            'lastname'                        => 'nullable|string|max:255',
            'comercial_name'                  => 'nullable|string|max:255',
            'address'                         => 'nullable|string|max:255',
            'phone'                           => 'nullable|string|max:50',
            'email'                           => 'nullable|email|max:255',
            'contact_phone'                   => 'nullable|string|max:50',
            'name_contact'                    => 'nullable|string|max:255',
            'email_contact'                   => 'nullable|email|max:255',
            'position_contact'                => 'nullable|string|max:255',
            'latitude'                        => 'nullable|string|max:20',
            'longitude'                       => 'nullable|string|max:20',
            'economic_activity'               => 'nullable|string|max:20',
            'zip_code'                        => 'nullable|string|max:20',
            'business_registration'           => 'nullable|string|max:20',

            // Validaciones para llaves foráneas (puedes cambiar 'nullable' por 'required' si el flujo de tu SaaS lo exige)
            'bank_account_id'                 => 'nullable|integer|exists:bank_accounts,id',
            'branch_id'                       => 'nullable|integer|exists:miscellaneous_items,id',
            'type_id'                         => 'nullable|integer|exists:miscellaneous_items,id',
            'municipalities_id'               => 'nullable|integer|exists:municipalities,id',
            'type_document_identification_id' => 'nullable|integer|exists:type_document_identifications,id',
            'companies_id'                    => 'nullable|integer|exists:companies,id',
            'type_regime_id'                  => 'nullable|integer|exists:type_regimes,id',
            'type_liability_id'               => 'nullable|integer|exists:type_liabilities,id',

            // Campos con ENUM exactos de la base de datos
            'stype_of_taxpayer'               => 'nullable|in:Gran Contribuyente,Auto Retenedor,Régimen Común,Régimen Simplificado,Otro',
            'state'                           => 'nullable|in:Activo,Inactivo',
            'retesource'                      => 'nullable|in:Si,No',
            'reteiva'                         => 'nullable|in:Si,No',
            'reteica'                         => 'nullable|in:Si,No',
        ]);

        $data['companies_id']       = $companyId;    

        try {
            $supplier->update($data);
            return response()->json([
                'message' => 'Proveedor Actualizado exitosamente',
                'suppliers' => $supplier,
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
        $company = Supplier::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Proveedor Eliminado Exitosamente']);
    }
}
