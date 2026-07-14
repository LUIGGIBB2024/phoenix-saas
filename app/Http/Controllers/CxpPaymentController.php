<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CxpPayment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class CxpPaymentController extends Controller
{

    public function getPayments(Request $request): JsonResponse
    {
        $payments = CxpPayment::select(
            'nit',
            'branch',
            'lapse',
            'report_date',
            'check_date',
            'delivery_date',
            'consecutive',
            'document',
            'supplier_name',
            'value_cxp',
            'others_payments',
            'observations',
            'payment_method',
            'check_number',
            'payment_type',
            'state',
            'state01',
            'state02',
            'state03',
            'proyect',
            'sproyect',
            'center',
            'activity',
            'suppliers_id',
            'companies_id',
            'usercreate',
            'userupdate',

        )->orderBy('report_date', 'DESC')->get();

        return response()->json([
            'message' => 'Consulta Generada Exitosamente',
            'payments' => $payments,
            'totaldocumentos' => $payments->count(),
        ], 201);
    }
}
