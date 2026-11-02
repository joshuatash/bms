<?php 
require __DIR__ . '/config.php'; 
require_login(); 
$active = 'expenses'; 
$user = current_user();
$isAdmin = $user['role'] === 'admin';
$isManager = $user['role'] === 'manager';
$canViewList = $isAdmin || $isManager;
$canRecord = !$isAdmin; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expenses & Department Tracking</title>
    <style>
        :root{
            --navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--amber:#f59e0b;--green:#16a34a;--red:#dc2626;--blue:#2563eb;
            /* Dark theme (matches POS) */
            --surface:#111c2e;
            --surface-2:#16243a;
            --surface-3:#1e2d47;
            --text:#e2e8f0;
            --text-dim:#94a3b8;
            --text-mute:#64748b;
            --border:#243349;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: #0b1220; color: var(--text); }
        main { max-width: 1000px; margin: 24px auto; padding: 0 18px; display: grid; gap: 20px; }
        .head h2 { color: var(--text); }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.3); }
        .card h2 { margin: 0 0 14px; font-size: 1.05rem; color: var(--text); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: end; }
        label { display: block; font-size: .75rem; font-weight: 700; color: var(--text-dim); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .4px; }
        input, select { width: 100%; padding: 10px 11px; border: 1px solid var(--border); border-radius: 8px; font-size: .95rem; background: var(--surface-2); color: var(--text); }
        input::placeholder { color: var(--text-mute); }
        input:focus, select:focus { outline: 2px solid var(--amber); border-color: var(--amber); background: var(--surface-3); }
        .btn { border: 0; border-radius: 8px; padding: 10px 18px; font-weight: 700; cursor: pointer; font-size: .9rem; text-decoration: none; display: inline-block; text-align: center; transition: .15s; }
        .btn.primary { background: var(--amber); color: #0b1220; box-shadow: 0 4px 6px -1px rgba(245,158,11,.3); }
        .btn.primary:hover { background: #d97706; color: #fff; }
        .btn.dark { background: var(--text-dim); color: var(--text); }
        .btn.dark:hover { background: var(--text); color: #0b1220; }
        
        /* Filter Tabs */
        .tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
        .tab { padding: 8px 16px; border-radius: 8px; background: var(--surface-2); color: var(--text-dim); border: 1px solid var(--border); font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: 0.2s; }
        .tab:hover { background: var(--surface-3); color: var(--text); }
        .tab.active { background: var(--amber); color: #0b1220; border-color: var(--amber); }

        .hint { font-size: .85rem; margin-top: 10px; min-height: 1.2em; color: var(--text-dim); }
        .hint.good { color: #4ade80; }
        .hint.bad { color: #f87171; font-weight: 600; }
        .tablewrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; color: var(--text); }
        th { text-align: left; background: var(--surface-2); color: var(--text-dim); padding: 11px 12px; font-size: .75rem; text-transform: uppercase; border-bottom: 2px solid var(--border); }
        td { padding: 11px 12px; border-bottom: 1px solid var(--border); }
        .num { text-align: right; }
        .printonly { display: none; }

        /* ---------- RESPONSIVE MOBILE VIEW ---------- */
        @media(max-width: 640px) {
            main { padding: 0 12px; margin: 14px auto; gap: 14px; }
            .head { flex-direction: column; align-items: stretch !important; gap: 10px; }
            .head h2 { font-size: 1.1rem; }
            .head .btn { width: 100%; padding: 12px; }
            .grid { grid-template-columns: 1fr; gap: 12px; }
            .grid > div { grid-column: auto !important; }
            
            .tabs { justify-content: space-between; }
            .tab { flex: 1; text-align: center; padding: 10px 4px; font-size: 0.8rem; }

            /* Hide table headers */
            .rt thead { display: none; }
            .rt, .rt tbody, .rt tr, .rt td { display: block; width: 100%; }

            /* Card style for each expense */
            .rt tbody tr { 
                border: 1px solid var(--border); 
                border-radius: 12px; 
                margin-bottom: 12px; 
                padding: 10px 14px; 
                background: var(--surface-2); 
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }

            /* Row alignment */
            .rt td { 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                border: 0; 
                border-bottom: 1px dashed var(--border); 
                padding: 10px 0; 
                text-align: right; 
                font-size: 0.9rem;
            }
            .rt tbody td:last-child { 
                border-bottom: 0; 
                padding-bottom: 4px;
            }

            /* Labels on the left */
            .rt td::before { 
                content: attr(data-label); 
                font-weight: 700; 
                font-size: .7rem; 
                text-transform: uppercase; 
                color: var(--text-dim); 
                text-align: left; 
                flex-shrink: 0;
                margin-right: 16px;
            }

            /* Reset loading state */
            .rt td[colspan]::before { content: none; } 
            .rt td[colspan] { display: block; text-align: center; border-bottom: 0; padding: 24px 0; }

            /* Footer (Total Row) */
            .rt tfoot { display: block; width: 100%; margin-top: 4px; }
            .rt tfoot tr { 
                display: flex; 
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                border: 2px solid var(--border); 
                border-radius: 12px; 
                background: var(--surface-3); 
                padding: 16px 18px; 
                width: 100%;
            }
            .rt tfoot td { border: 0; padding: 0; display: block; }
            .rt tfoot td[colspan] { 
                display: block; 
                text-align: left; 
                font-weight: 700; 
                font-size: 0.85rem; 
                color: var(--text-dim); 
            }
            .rt tfoot td.num { 
                text-align: right; 
                font-size: 1.4rem; 
                font-weight: 800; 
                color: #f87171; 
            }
            .rt tfoot td.num::before { content: none; }
        }

        /* ---------- PRINT VIEW ---------- */
        @media print {
            .sidebar, .mbar, .scrim, .noprint, .tabs { display: none !important; }
            body { padding: 0 !important; background: #fff; color: #0f172a; } 
            main { margin: 0; padding: 0; max-width: none; }
            .card { box-shadow: none; border: 1px solid #cbd5e1; background: #fff; }
            .printonly { display: block; }
            th { background: #0f172a !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr { break-inside: avoid; } @page { size: A4 portrait; margin: 10mm; }
            .rt thead { display: table-header-group; } .rt tr { display: table-row; }
            .rt td { display: table-cell; text-align: left; border-bottom: 1px solid #cbd5e1; padding: 8px; color: #0f172a; }
            .rt td::before { content: none; } .rt td.num { text-align: right; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <?php if ($canRecord): ?>
    <div class="head noprint">
        <h2>Record an Expense by Department</h2>
    </div>
    <section class="card noprint">
        <div class="grid">
            <div><label>Expense Title</label><input id="eTitle" placeholder="e.g. Electricity Bill" autocomplete="off"></div>
            <div>
                <label>Department</label>
                <select id="eDept">
                    <option value="Administration">Administration</option>
                    <option value="Bar & Beverage">Bar & Beverage</option>
                    <option value="Kitchen">Kitchen</option>
                    <option value="Lodge / Rooms">Lodge / Rooms</option>
                    <option value="Ground Hire">Ground Hire</option>
                    <option value="PA System">PA System</option>
                    <option value="Maintenance">Maintenance</option>
                </select>
            </div>
            <div>
                <label>Category</label>
                <select id="eCat">
                    <option value="Utilities">Utilities</option>
                    <option value="Supplies">Supplies</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Transport">Transport</option>
                    <option value="Salary">Salary</option>
                    <option value="General">General</option>
                </select>
            </div>
            <div><label>Amount (<?= CURRENCY ?>)</label><input id="eAmt" type="number" min="1" step="500"></div>
            <div style="grid-column: 1 / -1"><label>Description (Optional)</label><input id="eDesc" maxlength="255" placeholder="Any extra details..."></div>
            <div><button class="btn primary" id="eSave" style="width:100%">Save Expense</button></div>
        </div>
        <div class="hint" id="eHint"></div>
    </section>
    <?php endif; ?>

    <?php if ($canViewList): ?>
        <div class="head noprint" style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
            <h2 id="tableTitle">Expenses & Net Impact</h2>
            <button class="btn dark" onclick="window.print()">Export to PDF</button>
        </div>

        <div class="tabs noprint">
            <button class="tab active" onclick="changePeriod('today')">Today</button>
            <button class="tab" onclick="changePeriod('week')">This Week</button>
            <button class="tab" onclick="changePeriod('month')">This Month</button>
            <button class="tab" onclick="changePeriod('all')">All Time</button>
        </div>
        
        <p class="printonly" style="color:#64748b; font-size:0.85rem; margin-bottom:10px;">Expenses Report Generated: <?= date('d M Y, h:i A') ?> by <?= e($user['name'] ?? $user['username'] ?? 'Staff') ?></p>
        <section class="card">
            <div class="tablewrap">
                <table class="rt">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Department / Title</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Recorded By</th>
                            <th class="num">Amount (<?= CURRENCY ?>)</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:var(--text-mute);">Loading...</td></tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align:right; font-weight:bold; color:var(--text);" id="tLabel">Total Expenses Today:</td>
                            <td class="num" style="font-weight:bold; font-size:1.1rem; color:#f87171;" id="tTotal">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>
<script>
const CSRF = <?= json_encode(csrf_token()) ?>;
const CAN_VIEW_LIST = <?= $canViewList ? 'true' : 'false' ?>;
const CUR = <?= json_encode(CURRENCY) ?>;
const $ = id => document.getElementById(id);
const fmt = n => Number(n).toLocaleString();
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const apiUrl = './api/expenses.php';

let currentPeriod = 'today';

const localTime = (dateStr, period) => {
    const d = new Date(dateStr.replace(' ', 'T'));
    if (period === 'today') {
        return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: true });
    }
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ', ' + 
           d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: true });
};

const loadExpenses = async () => {
    if (!CAN_VIEW_LIST) return;
    
    if (currentPeriod === 'today') {
        $('tableTitle').textContent = "Today's Expenses & Net Impact (<?= date('d M Y') ?>)";
        $('tLabel').textContent = "Total Expenses Today:";
    } else if (currentPeriod === 'week') {
        $('tableTitle').textContent = "This Week's Expenses & Net Impact";
        $('tLabel').textContent = "Total Expenses This Week:";
    } else if (currentPeriod === 'month') {
        $('tableTitle').textContent = "This Month's Expenses & Net Impact";
        $('tLabel').textContent = "Total Expenses This Month:";
    } else {
        $('tableTitle').textContent = "All-Time Expenses & Net Impact";
        $('tLabel').textContent = "All-Time Total Expenses:";
    }

    $('tbody').innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:var(--text-mute);">Loading...</td></tr>';

    try {
        const res = await fetch(apiUrl + '?action=list&period=' + currentPeriod);
        const r = await res.json();
        if (r.error) {
            $('tbody').innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:#f87171;">${r.error}</td></tr>`;
            return;
        }
        
        $('tTotal').textContent = CUR + ' ' + fmt(r.total);
        
        if (!r.rows.length) {
            $('tbody').innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:var(--text-mute);">No expenses recorded for this period.</td></tr>';
            return;
        }
        
        $('tbody').innerHTML = r.rows.map(x => `
            <tr>
                <td data-label="Time">${localTime(x.created_at, currentPeriod)}</td>
                <td data-label="Department / Title"><span style="font-size:0.75rem; font-weight:700; color:#60a5fa; text-transform:uppercase;">${esc(x.department)}</span><br><b>${esc(x.title)}</b></td>
                <td data-label="Category">${esc(x.category)}</td>
                <td data-label="Description">${esc(x.description || '—')}</td>
                <td data-label="Recorded By">${esc(x.recorded_by_name)}</td>
                <td class="num" data-label="Amount"><b style="color:#f87171;">${CUR} ${fmt(x.amount)}</b></td>
            </tr>
        `).join('');
    } catch(e) {
        console.error(e);
        $('tbody').innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:#f87171;">Network error loading expenses.</td></tr>';
    }
};

function changePeriod(period) {
    currentPeriod = period;
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
    loadExpenses();
}

if (document.getElementById('eSave')) {
    const hint = (t, k = '') => { $('eHint').textContent = t; $('eHint').className = 'hint ' + k; };
    $('eSave').onclick = async () => {
        const body = {
            title: $('eTitle').value, 
            department: $('eDept').value, 
            category: $('eCat').value, 
            amount: $('eAmt').value, 
            description: $('eDesc').value
        };
        $('eSave').disabled = true; $('eSave').textContent = 'Saving...';
        hint(''); 
        
        try {
            const res = await fetch(apiUrl + '?action=add', {
                method: 'POST', 
                headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF }, 
                body: JSON.stringify(body)
            });
            
            const text = await res.text();
            let r;
            try { r = JSON.parse(text); } catch (err) { throw new Error("Server Error: " + text.substring(0, 150)); }
            
            if (r.error) { hint(r.error, 'bad'); }
            else {
                hint('', ''); 
                showToast('Expense recorded successfully!');
                $('eTitle').value = ''; $('eAmt').value = ''; $('eDesc').value = '';
                if (CAN_VIEW_LIST) loadExpenses();
            }
        } catch (e) { 
            hint(e.message || 'Network error.', 'bad'); 
        }
        
        $('eSave').disabled = false; $('eSave').textContent = 'Save Expense';
    };
}

if (CAN_VIEW_LIST) {
    loadExpenses();
}
</script>
</body>
</html>