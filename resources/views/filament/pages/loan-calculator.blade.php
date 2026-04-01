<x-filament::page>
<style>
    .calc-wrap {
        font-family: 'Georgia', serif;
        --green: #16a34a;
        --green-light: #dcfce7;
        --green-dark: #14532d;
        --slate: #1e293b;
        --slate-mid: #334155;
        --slate-light: #64748b;
        --border: #e2e8f0;
        --bg: #f8fafc;
        --white: #ffffff;
        --red: #ef4444;
        --gold: #f59e0b;
    }

    .calc-wrap * { box-sizing: border-box; }

    /* ── Page Header ── */
    .calc-header {
        text-align: center;
        padding: 2rem 1rem 1.5rem;
        position: relative;
    }
    .calc-header::before {
        content: '';
        position: absolute;
        top: 0; left: 50%; transform: translateX(-50%);
        width: 60px; height: 3px;
        background: var(--green);
        border-radius: 2px;
    }
    .calc-header h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--slate);
        margin: 1rem 0 0.4rem;
        letter-spacing: -0.03em;
    }
    .calc-header p {
        color: var(--slate-light);
        font-size: 0.9rem;
        font-style: italic;
        margin: 0;
    }

    /* ── Main Layout ── */
    .calc-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 768px) {
        .calc-grid { grid-template-columns: 1fr; }
    }

    /* ── Form Panel ── */
    .calc-form-panel {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
    }
    .panel-title {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--green);
        margin: 0 0 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .panel-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    /* ── Form Fields ── */
    .field-group { margin-bottom: 1.1rem; }
    .field-label {
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--slate-mid);
        margin-bottom: 0.4rem;
        letter-spacing: 0.01em;
    }
    .field-hint {
        font-size: 0.7rem;
        color: var(--slate-light);
        font-weight: 400;
        font-style: italic;
    }
    .calc-input, .calc-select {
        width: 100%;
        padding: 0.6rem 0.85rem;
        border: 1.5px solid var(--border);
        border-radius: 8px;
        font-size: 0.9rem;
        color: var(--slate);
        background: var(--bg);
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        outline: none;
        font-family: 'Georgia', serif;
    }
    .calc-input:focus, .calc-select:focus {
        border-color: var(--green);
        background: var(--white);
        box-shadow: 0 0 0 3px rgba(22,163,74,0.1);
    }
    .calc-input::placeholder { color: #cbd5e1; }

    .field-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }

    /* ── Interest Type Toggle ── */
    .type-toggle {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border: 1.5px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
        background: var(--bg);
    }
    .type-btn {
        padding: 0.55rem;
        font-size: 0.8rem;
        font-weight: 600;
        font-family: 'Georgia', serif;
        border: none;
        background: transparent;
        color: var(--slate-light);
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    .type-btn.active {
        background: var(--green);
        color: white;
    }
    .type-btn:not(.active):hover { background: var(--green-light); color: var(--green-dark); }

    /* ── Error ── */
    .calc-error {
        display: none;
        font-size: 0.8rem;
        color: var(--red);
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        padding: 0.6rem 0.85rem;
        margin-bottom: 1rem;
    }

    /* ── Calculate Button ── */
    .calc-submit {
        width: 100%;
        padding: 0.75rem;
        background: var(--green);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 700;
        font-family: 'Georgia', serif;
        letter-spacing: 0.02em;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
        box-shadow: 0 2px 8px rgba(22,163,74,0.3);
        margin-top: 0.5rem;
    }
    .calc-submit:hover {
        background: #15803d;
        box-shadow: 0 4px 16px rgba(22,163,74,0.35);
        transform: translateY(-1px);
    }
    .calc-submit:active { transform: translateY(0); }

    /* ── Results Panel ── */
    .calc-results-panel {
        background: linear-gradient(145deg, #1a3a2a 0%, #14532d 40%, #166534 100%);
        border-radius: 16px;
        padding: 1.75rem;
        color: white;
        display: flex;
        flex-direction: column;
        gap: 0;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(22,163,74,0.25);
    }
    .calc-results-panel::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 160px; height: 160px;
        background: rgba(255,255,255,0.04);
        border-radius: 50%;
    }
    .calc-results-panel::after {
        content: '';
        position: absolute;
        bottom: -60px; left: -30px;
        width: 200px; height: 200px;
        background: rgba(255,255,255,0.03);
        border-radius: 50%;
    }

    .results-title {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: rgba(255,255,255,0.5);
        margin: 0 0 1.25rem;
    }

    .result-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.85rem 0;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        position: relative;
        z-index: 1;
    }
    .result-row:last-of-type { border-bottom: none; }

    .result-label {
        font-size: 0.8rem;
        color: rgba(255,255,255,0.65);
    }
    .result-sublabel {
        font-size: 0.68rem;
        color: rgba(255,255,255,0.35);
        display: block;
        margin-top: 1px;
    }
    .result-value {
        font-size: 1rem;
        font-weight: 700;
        color: white;
        letter-spacing: -0.01em;
    }
    .result-value.highlight {
        font-size: 1.3rem;
        color: #86efac;
    }

    /* ── Grand Total Box ── */
    .grand-total-box {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 10px;
        padding: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        position: relative;
        z-index: 1;
    }
    .grand-total-box .label {
        font-size: 0.8rem;
        color: rgba(255,255,255,0.7);
        font-weight: 600;
    }
    .grand-total-box .value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #86efac;
        letter-spacing: -0.02em;
    }

    /* ── Action Buttons ── */
    .result-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1.25rem;
        position: relative;
        z-index: 1;
    }
    .btn-outline-white {
        padding: 0.6rem;
        border: 1.5px solid rgba(255,255,255,0.25);
        background: rgba(255,255,255,0.07);
        color: white;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        font-family: 'Georgia', serif;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    .btn-outline-white:hover {
        background: rgba(255,255,255,0.15);
        border-color: rgba(255,255,255,0.4);
    }
    .btn-reset-white {
        padding: 0.5rem;
        border: none;
        background: transparent;
        color: rgba(255,255,255,0.4);
        border-radius: 8px;
        font-size: 0.78rem;
        font-family: 'Georgia', serif;
        cursor: pointer;
        transition: color 0.2s;
        text-align: center;
        text-decoration: underline;
    }
    .btn-reset-white:hover { color: rgba(255,255,255,0.8); }

    /* ── Amortization Section ── */
    .amort-section {
        display: none;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
    }
    .amort-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border);
        background: var(--bg);
    }
    .amort-header h3 {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -0.01em;
    }
    .amort-summary {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0;
        border-bottom: 1px solid var(--border);
    }
    @media (max-width: 600px) {
        .amort-summary { grid-template-columns: repeat(2, 1fr); }
    }
    .amort-summary-item {
        padding: 0.85rem 1rem;
        border-right: 1px solid var(--border);
        text-align: center;
    }
    .amort-summary-item:last-child { border-right: none; }
    .amort-summary-item .s-label {
        font-size: 0.68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--slate-light);
        display: block;
        margin-bottom: 0.2rem;
    }
    .amort-summary-item .s-value {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--slate);
    }

    .amort-table-wrap { overflow-x: auto; }
    .amort-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.83rem;
    }
    .amort-table thead tr {
        background: #f1f5f9;
        border-bottom: 2px solid var(--border);
    }
    .amort-table th {
        padding: 0.7rem 1rem;
        text-align: right;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--slate-light);
        white-space: nowrap;
    }
    .amort-table th:first-child { text-align: left; }
    .amort-table td {
        padding: 0.65rem 1rem;
        text-align: right;
        color: var(--slate);
        border-bottom: 1px solid #f1f5f9;
    }
    .amort-table td:first-child { text-align: left; color: var(--slate-light); font-weight: 600; }
    .amort-table tbody tr:hover { background: #f8fafc; }
    .amort-table .td-principal { color: #2563eb; }
    .amort-table .td-interest  { color: #dc2626; }
    .amort-table tfoot tr {
        background: #f1f5f9;
        border-top: 2px solid var(--border);
        font-weight: 700;
    }
    .amort-table tfoot td { padding: 0.75rem 1rem; color: var(--slate); }

    .btn-print {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        background: var(--green);
        color: white;
        border: none;
        border-radius: 7px;
        font-size: 0.78rem;
        font-weight: 700;
        font-family: 'Georgia', serif;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-print:hover { background: #15803d; }

    @media print {
        .calc-form-panel, .calc-results-panel, .amort-header .btn-print,
        .result-actions, .calc-header p { display: none !important; }
        .amort-section { display: block !important; box-shadow: none; border: none; }
    }
</style>

<div class="calc-wrap">

    {{-- Header --}}
    <div class="calc-header">
        <h1>Loan Payment Calculator</h1>
        <p>Estimate installments using flat rate or declining balance (EMI) method</p>
    </div>

    {{-- Main Grid --}}
    <div class="calc-grid">

        {{-- ── Left: Form ── --}}
        <div class="calc-form-panel">
            <p class="panel-title">Loan Parameters</p>

            <div class="field-group">
                <label class="field-label" for="c-principal">
                    Principal Amount
                    <span class="field-hint">— in RWF</span>
                </label>
                <input type="number" id="c-principal" class="calc-input" placeholder="e.g. 500,000" min="1000" step="1000">
            </div>

            <div class="field-row">
                <div class="field-group">
                    <label class="field-label" for="c-rate">
                        Interest Rate
                        <span class="field-hint">— % per month</span>
                    </label>
                    <input type="number" id="c-rate" class="calc-input" placeholder="e.g. 5" min="0" max="50" step="0.1">
                </div>
                <div class="field-group">
                    <label class="field-label" for="c-installments">
                        Installments
                        <span class="field-hint">— months</span>
                    </label>
                    <input type="number" id="c-installments" class="calc-input" placeholder="e.g. 12" min="1" max="120">
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Interest Method</label>
                <div class="type-toggle">
                    <button type="button" class="type-btn active" data-type="declining">Declining Balance</button>
                    <button type="button" class="type-btn" data-type="flat">Flat Rate</button>
                </div>
                <input type="hidden" id="c-type" value="declining">
            </div>

            <div class="field-row">
                <div class="field-group">
                    <label class="field-label" for="c-proc-fee">
                        Processing Fee
                        <span class="field-hint">— RWF</span>
                    </label>
                    <input type="number" id="c-proc-fee" class="calc-input" placeholder="0" min="0" step="100" value="0">
                </div>
                <div class="field-group">
                    <label class="field-label" for="c-app-fee">
                        Application Fee
                        <span class="field-hint">— RWF</span>
                    </label>
                    <input type="number" id="c-app-fee" class="calc-input" placeholder="0" min="0" step="100" value="0">
                </div>
            </div>

            <div id="c-error" class="calc-error"></div>

            <button type="button" id="c-calc-btn" class="calc-submit">
                Calculate Loan
            </button>
        </div>

        {{-- ── Right: Results ── --}}
        <div class="calc-results-panel">
            <p class="results-title">Loan Summary</p>

            <div class="result-row">
                <div>
                    <span class="result-label">Monthly Installment</span>
                    <span class="result-sublabel">Fixed payment per month</span>
                </div>
                <span class="result-value highlight" id="r-installment">RWF 0</span>
            </div>

            <div class="result-row">
                <div>
                    <span class="result-label">Total Interest</span>
                    <span class="result-sublabel">Over full loan term</span>
                </div>
                <span class="result-value" id="r-interest">RWF 0</span>
            </div>

            <div class="result-row">
                <div>
                    <span class="result-label">Total Repayment</span>
                    <span class="result-sublabel">Principal + Interest</span>
                </div>
                <span class="result-value" id="r-total">RWF 0</span>
            </div>

            <div class="result-row">
                <div>
                    <span class="result-label">Fees</span>
                    <span class="result-sublabel">Processing + Application</span>
                </div>
                <span class="result-value" id="r-fees">RWF 0</span>
            </div>

            <div class="grand-total-box">
                <span class="label">Total Cost of Loan</span>
                <span class="value" id="r-grand">RWF 0</span>
            </div>

            <div class="result-actions">
                <button type="button" id="c-toggle-amort" class="btn-outline-white">
                    Show Amortization Schedule
                </button>
                <button type="button" id="c-reset" class="btn-reset-white">
                    Reset calculator
                </button>
            </div>
        </div>
    </div>

    {{-- Amortization Schedule --}}
    <div id="amort-section" class="amort-section">
        <div class="amort-header">
            <h3>Amortization Schedule</h3>
            <button type="button" class="btn-print" id="c-print">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print
            </button>
        </div>

        <div class="amort-summary" id="amort-summary"></div>

        <div class="amort-table-wrap">
            <table class="amort-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Installment</th>
                        <th>Principal</th>
                        <th>Interest</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody id="amort-body"></tbody>
                <tfoot id="amort-foot"></tfoot>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const $ = id => document.getElementById(id);
    const fmt = n => 'RWF ' + Math.round(Math.max(0, n)).toLocaleString();

    // ── Interest type toggle ──────────────────────────────────────
    document.querySelectorAll('.type-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            $('c-type').value = this.dataset.type;
        });
    });

    // ── EMI (Declining Balance) ───────────────────────────────────
    function calcDeclining(P, r, n) {
        if (r === 0) {
            const emi = P / n;
            return {
                emi,
                totalInterest: 0,
                totalPayment: P,
                schedule: Array.from({ length: n }, (_, i) => ({
                    month: i + 1, payment: emi, principal: emi,
                    interest: 0, balance: P - emi * (i + 1)
                }))
            };
        }
        const emi = (P * r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1);
        let balance = P;
        const schedule = [];
        for (let m = 1; m <= n; m++) {
            const interest  = balance * r;
            const principal = emi - interest;
            balance        -= principal;
            schedule.push({ month: m, payment: emi, principal, interest, balance: Math.max(0, balance) });
        }
        return { emi, totalInterest: emi * n - P, totalPayment: emi * n, schedule };
    }

    // ── Flat Rate ─────────────────────────────────────────────────
    function calcFlat(P, r, n) {
        const totalInterest = P * r * n;
        const totalPayment  = P + totalInterest;
        const emi           = totalPayment / n;
        const pp            = P / n;
        const ip            = totalInterest / n;
        return {
            emi, totalInterest, totalPayment,
            schedule: Array.from({ length: n }, (_, i) => ({
                month: i + 1, payment: emi, principal: pp,
                interest: ip, balance: Math.max(0, P - pp * (i + 1))
            }))
        };
    }

    // ── Render amortization table ─────────────────────────────────
    function renderSchedule(schedule, totalInterest, totalPayment) {
        const body = $('amort-body');
        const foot = $('amort-foot');
        body.innerHTML = '';
        foot.innerHTML = '';

        schedule.forEach((row, i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.month}</td>
                <td>${fmt(row.payment)}</td>
                <td class="td-principal">${fmt(row.principal)}</td>
                <td class="td-interest">${fmt(row.interest)}</td>
                <td>${fmt(row.balance)}</td>
            `;
            body.appendChild(tr);
        });

        foot.innerHTML = `
            <tr>
                <td>Total</td>
                <td>${fmt(totalPayment)}</td>
                <td class="td-principal">${fmt(totalPayment - totalInterest)}</td>
                <td class="td-interest">${fmt(totalInterest)}</td>
                <td>—</td>
            </tr>
        `;
    }

    // ── Calculate ─────────────────────────────────────────────────
    $('c-calc-btn').addEventListener('click', function () {
        const errEl = $('c-error');
        errEl.style.display = 'none';

        const P    = parseFloat($('c-principal').value);
        const rPct = parseFloat($('c-rate').value);
        const n    = parseInt($('c-installments').value);
        const type = $('c-type').value;
        const pFee = parseFloat($('c-proc-fee').value) || 0;
        const aFee = parseFloat($('c-app-fee').value)  || 0;

        if (!P || P <= 0)          { errEl.textContent = 'Enter a valid principal amount.';    errEl.style.display = 'block'; return; }
        if (isNaN(rPct) || rPct < 0) { errEl.textContent = 'Enter a valid interest rate.';      errEl.style.display = 'block'; return; }
        if (!n || n < 1)            { errEl.textContent = 'Enter a valid number of installments.'; errEl.style.display = 'block'; return; }

        const r      = rPct / 100;
        const result = type === 'flat' ? calcFlat(P, r, n) : calcDeclining(P, r, n);
        const fees   = pFee + aFee;

        $('r-installment').textContent = fmt(result.emi);
        $('r-interest').textContent    = fmt(result.totalInterest);
        $('r-total').textContent       = fmt(result.totalPayment);
        $('r-fees').textContent        = fmt(fees);
        $('r-grand').textContent       = fmt(result.totalPayment + fees);

        renderSchedule(result.schedule, result.totalInterest, result.totalPayment);

        $('amort-summary').innerHTML = `
            <div class="amort-summary-item">
                <span class="s-label">Principal</span>
                <span class="s-value">${fmt(P)}</span>
            </div>
            <div class="amort-summary-item">
                <span class="s-label">Rate / Month</span>
                <span class="s-value">${rPct}%</span>
            </div>
            <div class="amort-summary-item">
                <span class="s-label">Term</span>
                <span class="s-value">${n} months</span>
            </div>
            <div class="amort-summary-item">
                <span class="s-label">Method</span>
                <span class="s-value">${type === 'flat' ? 'Flat Rate' : 'Declining'}</span>
            </div>
        `;

        $('amort-section').style.display = 'none';
        $('c-toggle-amort').textContent = 'Show Amortization Schedule';
    });

    // ── Toggle amortization ───────────────────────────────────────
    $('c-toggle-amort').addEventListener('click', function () {
        const sec = $('amort-section');
        const visible = sec.style.display === 'block';
        sec.style.display = visible ? 'none' : 'block';
        this.textContent  = visible ? 'Show Amortization Schedule' : 'Hide Amortization Schedule';
        if (!visible) sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // ── Reset ─────────────────────────────────────────────────────
    $('c-reset').addEventListener('click', function () {
        ['c-principal','c-rate','c-installments'].forEach(id => $(id).value = '');
        $('c-proc-fee').value = '0';
        $('c-app-fee').value  = '0';
        $('c-type').value = 'declining';
        document.querySelectorAll('.type-btn').forEach((b, i) => b.classList.toggle('active', i === 0));
        ['r-installment','r-interest','r-total','r-fees','r-grand'].forEach(id => $(id).textContent = 'RWF 0');
        $('amort-section').style.display = 'none';
        $('amort-body').innerHTML = '';
        $('amort-summary').innerHTML = '';
        $('c-error').style.display = 'none';
    });

    // ── Print ─────────────────────────────────────────────────────
    $('c-print').addEventListener('click', () => window.print());

    // ── Enter key ─────────────────────────────────────────────────
    document.addEventListener('keypress', e => {
        if (e.key === 'Enter') $('c-calc-btn').click();
    });

});
</script>
@endpush
</x-filament::page>
