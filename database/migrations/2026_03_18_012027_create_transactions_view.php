<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW transactions_view AS

            SELECT
                payments.id                         AS id,
                payments.created_at                 AS created_at,
                payments.payment_date               AS transaction_date,
                customers.names                     AS customer_name,
                customers.national_id               AS national_id,
                loans.loan_number                   AS loan_ref,
                loans.loan_status                   AS loan_status,
                loans.principal_amount              AS original_loan,
                payments.amount                     AS amount,
                payments.principal_paid             AS principal_paid,
                payments.interest_paid              AS interest_paid,
                payments.penalty_paid               AS penalty_paid,
                payments.payment_method             AS payment_method,
                payments.receipt_number             AS reference,
                payments.company_id                 AS company_id,
                'payment'                           AS transaction_type

            FROM payments
            INNER JOIN loans     ON payments.loan_id     = loans.id
            INNER JOIN customers ON payments.customer_id = customers.id

            UNION ALL

            SELECT
                (loans.id + 1000000)                AS id,
                loans.created_at                    AS created_at,
                loans.disbursement_date             AS transaction_date,
                customers.names                     AS customer_name,
                customers.national_id               AS national_id,
                loans.loan_number                   AS loan_ref,
                loans.loan_status                   AS loan_status,
                loans.principal_amount              AS original_loan,
                loans.principal_amount              AS amount,
                loans.principal_amount              AS principal_paid,
                0                                   AS interest_paid,
                0                                   AS penalty_paid,
                'disbursement'                      AS payment_method,
                loans.loan_number                   AS reference,
                loans.company_id                    AS company_id,
                'disbursement'                      AS transaction_type

            FROM loans
            INNER JOIN customers ON loans.customer_id = customers.id
            WHERE loans.disbursement_date IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS transactions_view");
    }
};
