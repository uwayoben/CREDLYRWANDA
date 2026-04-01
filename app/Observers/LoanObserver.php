<?php

namespace App\Observers;

use App\Models\Loan;
use Illuminate\Support\Facades\Log;

class LoanObserver
{
    /**
     * Handle the Loan "updated" event.
     * Fires SMS when status changes to "disbursed".
     */
    public function updated(Loan $loan): void
    {
        // Only trigger when loan_status changes to "disbursed"
        if ($loan->isDirty('loan_status') && $loan->loan_status === 'disbursed') {
            $this->sendDisbursementSms($loan);
        }
    }

    /**
     * Also handle newly created loans that are immediately disbursed.
     */
    public function created(Loan $loan): void
    {
        if ($loan->loan_status === 'disbursed') {
            $this->sendDisbursementSms($loan);
        }
    }

    /**
     * Send disbursement SMS to the customer.
     */
    protected function sendDisbursementSms(Loan $loan): void
    {
        $customer = $loan->customer;
        $phone    = $customer?->phone;

        if (blank($phone)) {
            Log::warning("Loan #{$loan->loan_number}: customer has no phone number, SMS skipped.");
            return;
        }

        $companyName     = $loan->company?->name ?? config('app.name');
        $amount          = number_format($loan->principal_amount, 0) . ' RWF';
        $expectedDate    = $loan->last_payment_date?->format('d/m/Y') ?? 'N/A';
        $customerName    = $customer?->names ?? 'Customer';

        $message = "Dear {$customerName}, you have received a loan of {$amount} from {$companyName}. "
                 . "Please ensure full repayment before {$expectedDate}. "
                 . "For inquiries call us. Thank you.";

        $this->sendSms($phone, $message);
    }

    /**
     * Core SMS sending — IntouchSMS.
     */
    protected function sendSms(string $recipient, string $message): void
    {
        try {
            $data = [
                'sender'     => 'FASITA',
                'recipients' => $recipient,
                'message'    => $message,
                'dlrurl'     => 'http://www.dlrurl.rw/deliversms/',
            ];

            $url      = 'https://www.intouchsms.co.rw/api/sendsms/.json';
            $postData = http_build_query($data);
            $username = 'E-STORE';
            $password = 'fasita@123';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,            $url);
            curl_setopt($ch, CURLOPT_USERPWD,        $username . ':' . $password);
            curl_setopt($ch, CURLOPT_POST,           true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS,     $postData);

            $result   = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            Log::info("Loan disbursement SMS sent to {$recipient}", [
                'result'    => $result,
                'http_code' => $httpCode,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send disbursement SMS to {$recipient}: " . $e->getMessage());
        }
    }
}