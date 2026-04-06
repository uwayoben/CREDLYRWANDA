<?php

namespace App\Http\Controllers;

use App\Models\Loan;

class LoanPrintController extends Controller
{
    public function show(Loan $loan)
    {
        $loan->load(['customer', 'company', 'createdBy']);

        $payments = $loan->payments()
            ->orderBy('payment_date')
            ->get();

        return view('loans.print', compact('loan', 'payments'));
    }
}
