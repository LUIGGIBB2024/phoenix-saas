<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CxcPayment;
use App\Models\GeneralDocument;
use App\Models\MiscellaneousItem;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

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
            'message' => 'Consulta Generada Exitosamente',
            'payments' => $payments,
            'customers' => $customers,
            'docspayments' => $recibos,
            'docspaymentsothers' => $recibos2,
            'totaldocumentos' => $payments->count(),
        ], 201);
    }
}

//getCustomerPayments
