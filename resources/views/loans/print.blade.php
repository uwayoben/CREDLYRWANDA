<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Statement — {{ $loan->loan_number }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 13px;
            color: #1e293b;
            background: #f1f5f9;
            line-height: 1.5;
        }

        /* ── Print bar ──────────────────────────────────────────────── */
        .print-bar {
            background: #0f172a;
            color: #fff;
            padding: 12px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,.3);
        }
        .print-bar-left h1 { font-size: 15px; font-weight: 600; }
        .print-bar-left p  { font-size: 12px; opacity: .55; margin-top: 1px; }
        .btn-group { display: flex; gap: 8px; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 18px; border-radius: 8px; font-size: 13px;
            font-weight: 600; cursor: pointer; border: none;
            text-decoration: none; transition: opacity .15s;
        }
        .btn:hover { opacity: .85; }
        .btn-print { background: #0ea5e9; color: #fff; }
        .btn-back  { background: #334155; color: #fff; }

        /* ── Page ─────────────────────────────────────────────────── */
        .page {
            max-width: 900px;
            margin: 28px auto 48px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
        }

        /* ── Header ───────────────────────────────────────────────── */
        .header {
            background: linear-gradient(135deg, #003D22 0%, #006633 100%);
            color: #fff;
            padding: 32px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 32px;
        }
        .header-left .label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px;
            opacity: .55; margin-bottom: 6px;
        }
        .header-left .loan-num {
            font-size: 26px; font-weight: 800;
            font-family: 'Courier New', monospace; letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .header-left .borrower { font-size: 14px; opacity: .85; margin-bottom: 2px; }
        .header-left .nid      { font-size: 12px; opacity: .55; font-family: monospace; }

        .status-pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 14px; border-radius: 20px;
            font-size: 12px; font-weight: 700; margin-top: 12px;
        }
        .pill-active    { background: rgba(74,222,128,.25); color: #4ade80; }
        .pill-completed { background: rgba(96,165,250,.25); color: #60a5fa; }
        .pill-defaulted { background: rgba(248,113,113,.25);color: #f87171; }
        .pill-pending   { background: rgba(148,163,184,.2); color: #94a3b8; }
        .pill-disbursed { background: rgba(251,191,36,.25); color: #fbbf24; }

        .header-right { text-align: right; flex-shrink: 0; }
        .header-right .company { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .header-right .meta    { font-size: 11px; opacity: .55; line-height: 1.7; }
        .header-right .meta strong { color: rgba(255,255,255,.85); }

        /* ── Summary strip ────────────────────────────────────────── */
        .strip {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            border-bottom: 1px solid #e2e8f0;
        }
        .strip-item {
            padding: 16px 18px; text-align: center;
            border-right: 1px solid #e2e8f0;
        }
        .strip-item:last-child { border-right: none; }
        .strip-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #94a3b8; margin-bottom: 4px; }
        .strip-value { font-size: 16px; font-weight: 800; color: #1e293b; }
        .strip-sub   { font-size: 10px; color: #94a3b8; margin-top: 2px; }
        .c-green { color: #16a34a; }
        .c-red   { color: #dc2626; }
        .c-blue  { color: #2563eb; }

        /* ── Progress ─────────────────────────────────────────────── */
        .progress-wrap { height: 8px; background: #e2e8f0; border-radius: 8px; overflow: hidden; margin-top: 12px; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #003D22, #22c55e); border-radius: 8px; }
        .progress-label { font-size: 11px; color: #64748b; text-align: right; margin-top: 4px; }

        /* ── Body ─────────────────────────────────────────────────── */
        .body { padding: 32px 40px; }

        /* ── Section ──────────────────────────────────────────────── */
        .section { margin-bottom: 28px; }
        .section-title {
            font-size: 10.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; color: #64748b;
            padding-bottom: 8px; border-bottom: 2px solid #e2e8f0;
            margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
        }
        .section-title::before {
            content: ''; display: inline-block;
            width: 3px; height: 13px; background: #003D22; border-radius: 2px;
        }

        /* ── Grid ─────────────────────────────────────────────────── */
        .g2 { display: grid; grid-template-columns: 1fr 1fr;         gap: 10px 24px; }
        .g3 { display: grid; grid-template-columns: 1fr 1fr 1fr;      gap: 10px 24px; }
        .g4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr;  gap: 10px 20px; }

        /* ── Field ────────────────────────────────────────────────── */
        .f-label {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .6px; color: #94a3b8; margin-bottom: 3px;
        }
        .f-val {
            font-size: 13px; font-weight: 500; color: #1e293b;
            padding: 7px 10px; background: #f8fafc;
            border: 1px solid #e2e8f0; border-radius: 6px; min-height: 34px;
        }
        .f-val.mono  { font-family: 'Courier New', monospace; font-weight: 700; }
        .f-val.green { color: #16a34a; font-weight: 700; }
        .f-val.red   { color: #dc2626; font-weight: 700; }
        .f-val.empty { color: #cbd5e1; font-style: italic; }

        /* ── Payments table ───────────────────────────────────────── */
        table.ptable {
            width: 100%; border-collapse: collapse; font-size: 12px;
        }
        .ptable thead tr { background: #0f172a; color: #fff; }
        .ptable thead th {
            padding: 9px 12px; text-align: left;
            font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .6px;
        }
        .ptable thead th:not(:first-child) { text-align: right; }
        .ptable tbody tr:nth-child(even) { background: #f8fafc; }
        .ptable tbody td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
        .ptable tbody td:not(:first-child):not(:last-child):not(.td-method):not(.td-status) { text-align: right; }
        .ptable tfoot td {
            padding: 10px 12px; font-weight: 700;
            background: #f1f5f9; border-top: 2px solid #003D22;
            text-align: right;
        }
        .ptable tfoot td:first-child { text-align: left; }

        /* ── Schedule status pills ────────────────────────────────── */
        .sched-pill {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 2px 10px; border-radius: 20px;
            font-size: 10.5px; font-weight: 700;
        }
        .sched-paid     { background: rgba(74,222,128,.18); color: #16a34a; }
        .sched-overdue  { background: rgba(248,113,113,.18); color: #dc2626; }
        .sched-due      { background: rgba(251,191,36,.18);  color: #b45309; }
        .sched-upcoming { background: #f1f5f9; color: #64748b; }

        .row-paid { opacity: .72; }
        .row-overdue { background: rgba(254,226,226,.4) !important; }

        /* ── Footer ───────────────────────────────────────────────── */
        .footer {
            background: #f8fafc; border-top: 1px solid #e2e8f0;
            padding: 14px 40px;
            display: flex; justify-content: space-between;
            font-size: 11px; color: #94a3b8;
        }

        /* ── Print ────────────────────────────────────────────────── */
        @media print {
            body { background: #fff; font-size: 11px; }
            .print-bar { display: none !important; }
            .page { margin: 0; border-radius: 0; box-shadow: none; max-width: 100%; }
            .header { padding: 20px 28px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .strip  { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .body { padding: 18px 28px; }
            .ptable thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .footer { padding: 10px 28px; }
            .sched-paid, .sched-overdue, .sched-due, .sched-upcoming {
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            .row-overdue { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

{{-- Print bar --}}
<div class="print-bar">
    <div class="print-bar-left">
        <h1>Loan Statement</h1>
        <p>{{ $loan->loan_number }} · {{ $loan->customer?->names }}</p>
    </div>
    <div class="btn-group">
        <a href="javascript:history.back()" class="btn btn-back">← Back</a>
        <button onclick="window.print()" class="btn btn-print">🖨 Print / Save PDF</button>
    </div>
</div>

<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="label">Loan Statement</div>
            <div class="loan-num">{{ $loan->loan_number }}</div>
            <div class="borrower">{{ $loan->customer?->names ?? '—' }}</div>
            <div class="nid">ID: {{ $loan->customer?->national_id ?? '—' }}</div>
            @php
                $pillMap = [
                    'active'    => 'pill-active',
                    'completed' => 'pill-completed',
                    'defaulted' => 'pill-defaulted',
                    'disbursed' => 'pill-disbursed',
                ];
                $pillClass = $pillMap[$loan->loan_status] ?? 'pill-pending';
            @endphp
            <div class="status-pill {{ $pillClass }}">
                ● {{ ucwords(str_replace('_', ' ', $loan->loan_status)) }}
            </div>
        </div>
        <div class="header-right">
            <div class="company">{{ $loan->company?->name ?? config('app.name') }}</div>
            <div class="meta">
                Generated: <strong>{{ now()->format('d M Y, H:i') }}</strong><br>
                Loan Class: <strong>{{ ucfirst($loan->loan_class ?? 'Normal') }}</strong><br>
                Officer: <strong>{{ $loan->createdBy?->name ?? '—' }}</strong><br>
                Phone: <strong>{{ $loan->customer?->phone ?? '—' }}</strong>
            </div>
        </div>
    </div>

    {{-- Summary strip --}}
    @php
        $principal  = (float) $loan->principal_amount;
        $totalAmt   = (float) $loan->total_amount;
        $paid       = (float) $loan->amount_paid;
        $balance    = $loan->interest_type === 'declining'
            ? (float) $loan->remaining_balance + ((float) $loan->total_interest - (float) $loan->interest_paid)
            : (float) $loan->remaining_balance;
        $progress   = $totalAmt > 0 ? min(100, round($paid / $totalAmt * 100)) : 0;
    @endphp
    <div class="strip">
        <div class="strip-item">
            <div class="strip-label">Principal</div>
            <div class="strip-value">{{ number_format($principal, 0) }}</div>
            <div class="strip-sub">RWF</div>
        </div>
        <div class="strip-item">
            <div class="strip-label">Total Repayment</div>
            <div class="strip-value c-blue">{{ number_format($totalAmt, 0) }}</div>
            <div class="strip-sub">Principal + Interest</div>
        </div>
        <div class="strip-item">
            <div class="strip-label">Amount Paid</div>
            <div class="strip-value c-green">{{ number_format($paid, 0) }}</div>
            <div class="strip-sub">RWF</div>
        </div>
        <div class="strip-item">
            <div class="strip-label">Balance Due</div>
            <div class="strip-value {{ $balance > 0 ? 'c-red' : 'c-green' }}">{{ number_format($balance, 0) }}</div>
            <div class="strip-sub">RWF</div>
        </div>
        <div class="strip-item">
            <div class="strip-label">Progress</div>
            <div class="strip-value {{ $progress >= 100 ? 'c-green' : 'c-blue' }}">{{ $progress }}%</div>
            <div class="strip-sub">Repaid</div>
        </div>
    </div>

    <div class="body">

        {{-- Loan Terms --}}
        <div class="section">
            <div class="section-title">Loan Terms</div>
            <div class="g4">
                <div><div class="f-label">Principal</div><div class="f-val">RWF {{ number_format($loan->principal_amount, 0) }}</div></div>
                <div><div class="f-label">Interest Rate</div><div class="f-val">{{ $loan->interest_rate }}% / month</div></div>
                <div><div class="f-label">Interest Type</div><div class="f-val">{{ ucfirst($loan->interest_type ?? '—') }}</div></div>
                <div><div class="f-label">Total Interest</div><div class="f-val">RWF {{ number_format($loan->total_interest, 0) }}</div></div>
                <div><div class="f-label">Installments</div><div class="f-val">{{ $loan->number_of_installments }}</div></div>
                <div><div class="f-label">Frequency</div><div class="f-val">{{ ucwords(str_replace('_',' ',$loan->installment_frequency ?? '—')) }}</div></div>
                <div><div class="f-label">EMI Amount</div><div class="f-val">RWF {{ number_format($loan->emi_amount, 0) }}</div></div>
                <div><div class="f-label">Penalty Rate</div><div class="f-val">{{ $loan->penalty_rate ?? 0 }}%</div></div>
            </div>
            <div class="progress-wrap" style="margin-top:16px"><div class="progress-fill" style="width:{{ $progress }}%"></div></div>
            <div class="progress-label">{{ $progress }}% repaid · RWF {{ number_format($paid, 0) }} of RWF {{ number_format($totalAmt, 0) }}</div>
        </div>

        {{-- Payment Breakdown --}}
        <div class="section">
            <div class="section-title">Payment Breakdown</div>
            <div class="g4">
                <div><div class="f-label">Amount Paid</div><div class="f-val green">RWF {{ number_format($loan->amount_paid, 0) }}</div></div>
                <div><div class="f-label">Principal Paid</div><div class="f-val green">RWF {{ number_format($loan->principal_paid, 0) }}</div></div>
                <div><div class="f-label">Interest Paid</div><div class="f-val">RWF {{ number_format($loan->interest_paid, 0) }}</div></div>
                <div><div class="f-label">Penalty Paid</div><div class="f-val {{ ($loan->penalty_paid ?? 0) > 0 ? 'red' : '' }}">RWF {{ number_format($loan->penalty_paid ?? 0, 0) }}</div></div>
                <div><div class="f-label">Remaining Balance</div><div class="f-val {{ $balance > 0 ? 'red' : 'green' }}">RWF {{ number_format($balance, 0) }}</div></div>
                <div><div class="f-label">Outstanding Interest</div><div class="f-val">RWF {{ number_format(max(0, (float)$loan->total_interest - (float)$loan->interest_paid), 0) }}</div></div>
                <div><div class="f-label">Payments Made</div><div class="f-val">{{ $payments->count() }} / {{ $loan->number_of_installments }}</div></div>
                <div><div class="f-label">Arrears</div><div class="f-val {{ ($loan->arrears_amount ?? 0) > 0 ? 'red' : '' }}">RWF {{ number_format($loan->arrears_amount ?? 0, 0) }}</div></div>
            </div>
        </div>

        {{-- Key Dates --}}
        <div class="section">
            <div class="section-title">Key Dates</div>
            <div class="g4">
                <div><div class="f-label">Disbursement</div><div class="f-val">{{ $loan->disbursement_date?->format('d M Y') ?? '—' }}</div></div>
                <div><div class="f-label">First Payment</div><div class="f-val">{{ $loan->first_payment_date?->format('d M Y') ?? '—' }}</div></div>
                <div>
                    <div class="f-label">Expected Completion</div>
                    <div class="f-val {{ $loan->expected_completion_date?->isPast() && !in_array($loan->loan_status,['completed','written_off']) ? 'red' : '' }}">
                        {{ $loan->expected_completion_date?->format('d M Y') ?? '—' }}
                        @if($loan->expected_completion_date?->isPast() && !in_array($loan->loan_status,['completed','written_off']))
                            ⚠️
                        @endif
                    </div>
                </div>
                <div><div class="f-label">Last Payment</div><div class="f-val">{{ $loan->last_payment_date?->format('d M Y') ?? '—' }}</div></div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             PAYMENT PLAN / INSTALLMENT SCHEDULE
        ═══════════════════════════════════════════════════════════ --}}
        @php
            $schedPrincipal = (float) $loan->principal_amount;
            $schedRate      = (float) $loan->interest_rate / 100;
            $schedN         = (int)   $loan->number_of_installments;
            $schedType      = $loan->interest_type ?? 'declining';
            $schedEmi       = (float) $loan->emi_amount;
            $schedFreq      = $loan->installment_frequency ?? 'monthly';
            $schedBalance   = $schedPrincipal;

            $schedStart = $loan->first_payment_date
                ? \Carbon\Carbon::parse($loan->first_payment_date)
                : \Carbon\Carbon::now()->addMonth();

            $schedRows          = [];
            $schedTotalEmi      = 0;
            $schedTotalPrincipal = 0;
            $schedTotalInterest  = 0;

            // Index actual payments by position (sorted by payment_date)
            $sortedPayments = $payments->sortBy('payment_date')->values();

            for ($si = 1; $si <= $schedN; $si++) {

                $dueDate = match($schedFreq) {
                    'daily'     => $schedStart->copy()->addDays($si - 1),
                    'weekly'    => $schedStart->copy()->addWeeks($si - 1),
                    'bi_weekly' => $schedStart->copy()->addWeeks(($si - 1) * 2),
                    'quarterly' => $schedStart->copy()->addMonths(($si - 1) * 3),
                    default     => $schedStart->copy()->addMonths($si - 1),
                };

                if ($schedType === 'flat') {
                    $schedInterest  = $schedPrincipal * $schedRate;
                    $schedPrincipalPmt = $schedPrincipal / $schedN;
                    $rowEmi         = $schedInterest + $schedPrincipalPmt;
                } else {
                    // Declining balance
                    $schedInterest     = $schedBalance * $schedRate;
                    $schedPrincipalPmt = $schedEmi - $schedInterest;
                    $rowEmi            = $schedEmi;
                }

                $openingBal  = $schedBalance;
                $schedBalance = max(0, $schedBalance - $schedPrincipalPmt);

                $schedTotalEmi       += $rowEmi;
                $schedTotalPrincipal += $schedPrincipalPmt;
                $schedTotalInterest  += $schedInterest;

                // Match actual payment for this installment slot
                $matchedPayment = $sortedPayments->get($si - 1);
                $isPaid    = $matchedPayment !== null;
                $isOverdue = !$isPaid && $dueDate->isPast() && !in_array($loan->loan_status, ['completed', 'written_off']);
                $isDue     = !$isPaid && !$isOverdue && $dueDate->isCurrentMonth();

                $schedStatus = match(true) {
                    $loan->loan_status === 'completed' => 'paid',
                    $isPaid    => 'paid',
                    $isOverdue => 'overdue',
                    $isDue     => 'due',
                    default    => 'upcoming',
                };

                $schedRows[] = [
                    'index'          => $si,
                    'due_date'       => $dueDate,
                    'emi'            => $rowEmi,
                    'principal'      => $schedPrincipalPmt,
                    'interest'       => $schedInterest,
                    'opening_bal'    => $openingBal,
                    'closing_bal'    => $schedBalance,
                    'status'         => $schedStatus,
                    'paid_amount'    => $matchedPayment?->amount,
                    'paid_date'      => $matchedPayment?->payment_date
                                        ? \Carbon\Carbon::parse($matchedPayment->payment_date)->format('d M Y')
                                        : null,
                ];
            }
        @endphp

        <div class="section">
            <div class="section-title">
                Payment Plan / Installment Schedule
                &nbsp;·&nbsp;
                {{ $schedN }} {{ ucwords(str_replace('_', ' ', $schedFreq)) }} installments
                &nbsp;·&nbsp;
                EMI: RWF {{ number_format($schedEmi, 0) }}
            </div>

            <table class="ptable">
                <thead>
                    <tr>
                        <th style="text-align:center">#</th>
                        <th>Due Date</th>
                        <th>EMI (RWF)</th>
                        <th>Principal (RWF)</th>
                        <th>Interest (RWF)</th>
                        <th>Opening Bal (RWF)</th>
                        <th>Closing Bal (RWF)</th>
                        <th class="td-status" style="text-align:left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedRows as $row)
                    @php
                        $rowClass = match($row['status']) {
                            'paid'    => 'row-paid',
                            'overdue' => 'row-overdue',
                            default   => '',
                        };
                        $pillClass = match($row['status']) {
                            'paid'     => 'sched-paid',
                            'overdue'  => 'sched-overdue',
                            'due'      => 'sched-due',
                            default    => 'sched-upcoming',
                        };
                        $pillLabel = match($row['status']) {
                            'paid'     => '✓ Paid',
                            'overdue'  => '⚠ Overdue',
                            'due'      => '● Due Now',
                            default    => 'Upcoming',
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td style="text-align:center; color:#94a3b8; font-size:11px">{{ $row['index'] }}</td>
                        <td>
                            {{ $row['due_date']->format('d M Y') }}
                            @if($row['paid_date'] && $row['status'] === 'paid')
                                <br><span style="font-size:10px; color:#16a34a">Paid {{ $row['paid_date'] }}</span>
                            @endif
                        </td>
                        <td style="font-weight:700; color:#2563eb">{{ number_format($row['emi'], 0) }}</td>
                        <td>{{ number_format($row['principal'], 0) }}</td>
                        <td style="color:#64748b">{{ number_format($row['interest'], 0) }}</td>
                        <td style="color:#64748b">{{ number_format($row['opening_bal'], 0) }}</td>
                        <td style="font-weight:600; color:{{ $row['closing_bal'] > 0 ? '#dc2626' : '#16a34a' }}">
                            {{ number_format($row['closing_bal'], 0) }}
                        </td>
                        <td class="td-status" style="text-align:left">
                            <span class="sched-pill {{ $pillClass }}">{{ $pillLabel }}</span>
                            @if($row['status'] === 'paid' && $row['paid_amount'])
                                <br><span style="font-size:10px; color:#94a3b8">RWF {{ number_format($row['paid_amount'], 0) }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align:left">TOTALS</td>
                        <td>{{ number_format($schedTotalEmi, 0) }}</td>
                        <td>{{ number_format($schedTotalPrincipal, 0) }}</td>
                        <td>{{ number_format($schedTotalInterest, 0) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>

            {{-- Legend --}}
            <div style="display:flex; gap:16px; margin-top:10px; flex-wrap:wrap;">
                <span class="sched-pill sched-paid">✓ Paid</span>
                <span class="sched-pill sched-due">● Due Now</span>
                <span class="sched-pill sched-overdue">⚠ Overdue</span>
                <span class="sched-pill sched-upcoming">Upcoming</span>
                <span style="font-size:10px; color:#94a3b8; margin-left:4px; align-self:center;">
                    * Declining balance: interest recalculates on reducing principal each period.
                </span>
            </div>
        </div>
        {{-- ═══════════════════════════════════════════════════════════ --}}

        {{-- Borrower --}}
        @if($loan->customer)
        <div class="section">
            <div class="section-title">Borrower Information</div>
            <div class="g3">
                <div><div class="f-label">Full Name</div><div class="f-val">{{ $loan->customer->names }}</div></div>
                <div><div class="f-label">National ID</div><div class="f-val mono">{{ $loan->customer->national_id ?? '—' }}</div></div>
                <div><div class="f-label">Phone</div><div class="f-val">{{ $loan->customer->phone ?? '—' }}</div></div>
                <div><div class="f-label">Gender</div><div class="f-val">{{ ucfirst($loan->customer->gender ?? '—') }}</div></div>
                <div><div class="f-label">Marital Status</div><div class="f-val">{{ ucfirst($loan->customer->marital_status ?? '—') }}</div></div>
                <div><div class="f-label">Employment</div><div class="f-val">{{ ucwords(str_replace('_',' ',$loan->customer->employment_status ?? '—')) }}</div></div>
            </div>
        </div>
        @endif

        {{-- Collateral --}}
        @if($loan->guarantee_collateral || $loan->collateral_value)
        <div class="section">
            <div class="section-title">Collateral & Security</div>
            <div class="g3">
                <div><div class="f-label">Collateral Type</div><div class="f-val">{{ ucwords(str_replace('_',' ',$loan->guarantee_collateral ?? '—')) }}</div></div>
                <div><div class="f-label">Collateral Value</div><div class="f-val">RWF {{ number_format($loan->collateral_value ?? 0, 0) }}</div></div>
                <div><div class="f-label">Purpose</div><div class="f-val {{ $loan->purpose ? '' : 'empty' }}">{{ $loan->purpose ?? 'Not specified' }}</div></div>
            </div>
            @if($loan->collateral_details)
            <div style="margin-top:10px"><div class="f-label">Details</div><div class="f-val">{{ $loan->collateral_details }}</div></div>
            @endif
        </div>
        @endif

        {{-- Payment History --}}
        <div class="section">
            <div class="section-title">Payment History ({{ $payments->count() }} payment{{ $payments->count() !== 1 ? 's' : '' }})</div>
            @if($payments->count() > 0)
            <table class="ptable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Receipt</th>
                        <th>Amount (RWF)</th>
                        <th>Principal (RWF)</th>
                        <th>Interest (RWF)</th>
                        <th class="td-method">Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $i => $payment)
                    <tr>
                        <td style="color:#94a3b8">{{ $i + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                        <td style="font-family:monospace; color:#0ea5e9; font-size:11px">{{ $payment->receipt_number ?? '—' }}</td>
                        <td style="font-weight:700; color:#16a34a">{{ number_format($payment->amount, 0) }}</td>
                        <td>{{ number_format($payment->principal_paid ?? 0, 0) }}</td>
                        <td>{{ number_format($payment->interest_paid ?? 0, 0) }}</td>
                        <td class="td-method" style="text-align:left">{{ ucwords(str_replace('_',' ',$payment->payment_method ?? '—')) }}</td>
                        <td style="color:#64748b; font-size:11px">{{ $payment->transaction_reference ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:left">TOTAL</td>
                        <td>{{ number_format($payments->sum('amount'), 0) }}</td>
                        <td>{{ number_format($payments->sum('principal_paid'), 0) }}</td>
                        <td>{{ number_format($payments->sum('interest_paid'), 0) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
            @else
            <p style="color:#94a3b8; font-style:italic; padding:10px 0">No payments recorded yet.</p>
            @endif
        </div>

    </div>{{-- end body --}}

    <div class="footer">
        <span>{{ $loan->company?->name ?? config('app.name') }}</span>
        <span>{{ $loan->loan_number }}</span>
        <span>Confidential · Generated {{ now()->format('d M Y, H:i') }}</span>
    </div>
</div>

<script>
    if (new URLSearchParams(window.location.search).get('print') === '1') {
        window.addEventListener('load', () => setTimeout(() => window.print(), 300));
    }
</script>
</body>
</html>