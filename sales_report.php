<?php
require __DIR__ . '/config.php';
require_login();
if (!in_array(current_user()['role'], ['admin', 'manager'], true)) { header('Location: pos.php'); exit; }
$active = 'dashboard';
$pdo = db();
$period = ($_GET['period'] ?? 'today') === 'month' ? 'month' : 'today';   // whitelisted, never used raw
$todayStr = $pdo->query("SELECT CURDATE()")->fetchColumn();
log_activity('view_sales_report', $period === 'today' ? 'Sales today' : 'Sales this month');
if ($period === 'today') {
    $where  = "s.sale_date >= CURDATE() AND s.sale_date < CURDATE() + INTERVAL 1 DAY";
    $title  = 'Sales Report & Profit from Sales Only (Today)';
    $label  = date('l, d M Y', strtotime($todayStr));
    $timeFmt = 'h:i A'; // 12-hour format with AM/PM
} else {
    $where  = "s.sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND s.sale_date < DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 1 MONTH";
    $title  = 'Sales Report & Profit from Sales Only (This Month)';
    $label  = date('F Y', strtotime($todayStr));
    $timeFmt = 'd M, h:i A'; // 12-hour format with AM/PM
}

// Fetch sales with profit computed safely using the products table buying price
$rows = $pdo->query("SELECT s.sale_date, s.receipt_no, i.product_name, i.qty_bottles, i.unit_price, i.line_total, 
                     COALESCE(p.buying_price, 0) AS buying_price, 
                     COALESCE(i.line_total - (COALESCE(p.buying_price, 0) * i.qty_bottles), 0) AS line_profit, 
                     u.full_name
                     FROM sales_record_items i
                     JOIN sales_record s ON s.sale_id = i.sale_id
                     JOIN users u ON u.user_id = s.user_id
                     LEFT JOIN products p ON p.product_id = i.product_id
                     WHERE $where
                     ORDER BY s.sale_date DESC, i.item_id")->fetchAll();

$total = 0.0; $totalProfit = 0.0; $bottles = 0; $receipts = []; $byItem = [];
foreach ($rows as $r) {
    $lineTotal = (float)$r['line_total'];
    $lineProfit = (float)$r['line_profit'];
    $total   += $lineTotal;
    $totalProfit += $lineProfit;
    $bottles += (int)$r['qty_bottles'];
    $receipts[$r['receipt_no']] = true;
    $k = $r['product_name'];
    $byItem[$k] ??= ['qty' => 0, 'amount' => 0.0, 'profit' => 0.0];
    $byItem[$k]['qty']     += (int)$r['qty_bottles'];
    $byItem[$k]['amount']  += $lineTotal;
    $byItem[$k]['profit']  += $lineProfit;
}
uasort($byItem, fn($a, $b) => $b['amount'] <=> $a['amount']);
$n = fn($x) => number_format((float)$x);
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?></title>
<style>
:root{--navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--amber:#f59e0b;--green:#16a34a}
*{box-sizing:border-box}body{margin:0;font-family:'Segoe UI',system-ui,sans-serif;background:linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);color:var(--navy)}
main{max-width:1100px;margin:24px auto;padding:0 18px;display:grid;gap:18px}
/* HEADER */
.head{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between}
.head h2{margin:0;font-size:1.4rem}.head p{margin:2px 0 0;color:var(--muted)}
.actions{display:flex;flex-wrap:wrap;gap:8px}
.btn{display:inline-block;border:0;border-radius:8px;padding:10px 16px;font-weight:700;cursor:pointer;font-size:.9rem;text-decoration:none}
.btn.ghost{background:#e2e8f0;color:var(--slate)}.btn.ghost:hover{background:#cbd5e1}
.btn.tab{background:#fff;color:var(--slate);border:1px solid var(--line)}.btn.tab.on{background:var(--navy);color:#fff;border-color:var(--navy)}
.btn.primary{background:var(--amber);color:var(--navy)}.btn.primary:hover{background:#d97706;color:#fff}
/* TILES */
.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}
.tile{background:#fff;border-radius:12px;padding:16px 18px;border-left:5px solid var(--slate);box-shadow:0 1px 3px rgba(0,0,0,.06)}
.tile.money{border-color:var(--green)}.tile.profit{border-color:#2563eb}.tile small{color:var(--muted)}.tile b{display:block;font-size:1.6rem}
/* CARDS & TABLES */
.card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.card h3{margin:0 0 12px;font-size:1.02rem}
.tablewrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:.92rem}
th{text-align:left;background:#f8fafc;color:var(--slate);padding:10px 12px;font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;border-bottom:2px solid var(--line)}
td{padding:10px 12px;border-bottom:1px solid var(--line)}.num{text-align:right}
tfoot td{font-weight:800;border-top:2px solid var(--navy);border-bottom:0;background:#f8fafc}
.bar{height:6px;border-radius:4px;background:#e2e8f0;margin-top:5px;overflow:hidden}.bar i{display:block;height:100%;background:var(--amber)}
.empty{text-align:center;color:var(--muted);padding:34px}
.printonly{display:none}.note{font-size:.8rem;color:var(--muted)}
/* ---- SMALL PHONE RESPONSIVENESS ---- */
@media(max-width: 640px) {
    main { padding: 0 12px; margin: 14px auto; gap: 14px; }
    .head { flex-direction: column; align-items: stretch; text-align: center; }
    .actions { flex-direction: column; }
    .btn { width: 100%; text-align: center; margin-bottom: 4px; }
    .tiles { grid-template-columns: 1fr; }
    .rt thead { display: none; }
    .rt, .rt tbody, .rt tr, .rt td { display: block; width: 100%; }
    .rt tr { border: 1px solid var(--line); border-radius: 10px; margin-bottom: 12px; padding: 8px 12px; background: #f8fafc; }
    .rt td { display: flex; justify-content: space-between; align-items: center; gap: 12px; border-bottom: 1px solid #f1f5f9; padding: 8px 2px; text-align: right; }
    .rt td:last-child { border-bottom: 0; padding-bottom: 0; }
    .rt td::before { content: attr(data-label); font-weight: 700; font-size: .68rem; text-transform: uppercase; color: var(--muted); text-align: left; flex-shrink: 0; }
    .rt td[data-label="Item"] { flex-direction: column; align-items: flex-start; gap: 6px; }
    .rt td[data-label="Item"]::before { margin-bottom: 2px; }
    .bar { width: 100%; }
    .rt tfoot { display: block; width: 100%; }
    .rt tfoot tr { display: flex; justify-content: space-between; align-items: center; background: #fff; border: 2px solid var(--navy); padding: 14px; border-radius: 10px; margin-top: 10px; }
    .rt tfoot td { border: 0; padding: 0; font-weight: 800; display: block; text-align: right; width: auto; }
    .rt tfoot td::before { content: none; }
    .rt tfoot td:first-child { text-align: left; font-size: 0.9rem; }
    .rt tfoot td:empty { display: none; }
}
/* ---- printing / Save as PDF ---- */
@media print {
    .sidebar, .mbar, .scrim, .noprint { display: none !important; }
    body { padding: 0 !important; background: #fff; } main { margin: 0; padding: 0; max-width: none; }
    .card, .tile { box-shadow: none; border: 1px solid #cbd5e1; }
    .printonly { display: block; }
    th { background: #0f172a !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    tr { break-inside: avoid; }
    @page { size: A4; margin: 12mm; }
}
</style></head><body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
  <div class="head">
    <div>
      <h2><?= e($title) ?></h2>
      <p><?= e($label) ?> &bull; <small style="color:var(--slate); font-weight:600;">(Profits from Sales Only)</small></p>
      <p class="printonly note">Generated <?= date('d M Y, h:i A') ?> by <?= e(current_user()['name']) ?></p>
    </div>
    <div class="actions noprint">
      <a class="btn ghost" href="dashboard.php">&larr; Back to Dashboard</a>
      <a class="btn tab <?= $period === 'today' ? 'on' : '' ?>" href="sales_report.php?period=today">Today</a>
      <a class="btn tab <?= $period === 'month' ? 'on' : '' ?>" href="sales_report.php?period=month">This Month</a>
      <button class="btn primary" onclick="window.print()">Export to PDF</button>
    </div>
  </div>
  <section class="tiles">
    <div class="tile money"><small>Total Sales (<?= CURRENCY ?>)</small><b><?= $n($total) ?></b></div>
    <div class="tile profit"><small>Gross Profit from Sales (<?= CURRENCY ?>)</small><b style="color:#2563eb;"><?= $n($totalProfit) ?></b></div>
    <div class="tile"><small>Receipts</small><b><?= count($receipts) ?></b></div>
    <div class="tile"><small>Bottles Sold</small><b><?= $n($bottles) ?></b></div>
  </section>
  <?php if (!$rows): ?>
    <section class="card"><div class="empty">No sales recorded for this period yet.</div></section>
  <?php else: ?>
  <section class="card">
    <h3>Where the money came from & profit breakdown from sales (by item)</h3>
    <div class="tablewrap"><table class="rt">
      <thead><tr><th>Item</th><th class="num">Bottles</th><th class="num">Amount (<?= CURRENCY ?>)</th><th class="num">Profit (<?= CURRENCY ?>)</th><th class="num">Share</th></tr></thead>
      <tbody>
      <?php foreach ($byItem as $name => $v): $pct = $total > 0 ? $v['amount'] / $total * 100 : 0; ?>
        <tr><td data-label="Item"><b><?= e($name) ?></b><div class="bar noprint"><i style="width:<?= round($pct) ?>%"></i></div></td>
            <td class="num" data-label="Bottles"><?= $n($v['qty']) ?></td>
            <td class="num" data-label="Amount"><?= $n($v['amount']) ?></td>
            <td class="num" data-label="Profit" style="color:#2563eb; font-weight:700;"><?= $n($v['profit']) ?></td>
            <td class="num" data-label="Share"><?= number_format($pct, 1) ?>%</td></tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td data-label="Total">Total</td><td class="num" data-label="Bottles"><?= $n($bottles) ?></td><td class="num" data-label="Amount"><?= $n($total) ?></td><td class="num" data-label="Profit" style="color:#2563eb;"><?= $n($totalProfit) ?></td><td class="num" data-label="Share">100%</td></tr></tfoot>
    </table></div>
  </section>
  <section class="card">
    <h3>All items sold (<?= $period === 'today' ? 'time' : 'date &amp; time' ?>, receipt, amount, profit)</h3>
    <div class="tablewrap"><table class="rt">
      <thead><tr><th><?= $period === 'today' ? 'Time' : 'Date &amp; Time' ?></th><th>Receipt</th><th>Item</th><th class="num">Bottles</th><th class="num">Price</th><th class="num">Amount (<?= CURRENCY ?>)</th><th class="num">Profit (<?= CURRENCY ?>)</th><th>Sold By</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr><td data-label="Time"><?= e(date($timeFmt, strtotime($r['sale_date']))) ?></td>
            <td data-label="Receipt"><?= e($r['receipt_no']) ?></td>
            <td data-label="Item"><b><?= e($r['product_name']) ?></b></td>
            <td class="num" data-label="Bottles"><?= (int)$r['qty_bottles'] ?></td>
            <td class="num" data-label="Price"><?= $n($r['unit_price']) ?></td>
            <td class="num" data-label="Amount"><?= $n($r['line_total']) ?></td>
            <td class="num" data-label="Profit" style="color:#2563eb;"><?= $n($r['line_profit']) ?></td>
            <td class="num" data-label="Sold By"><?= e($r['full_name']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="3">Total</td><td class="num"><?= $n($bottles) ?></td><td></td><td class="num"><?= $n($total) ?></td><td class="num" style="color:#2563eb;"><?= $n($totalProfit) ?></td><td></td></tr></tfoot>
    </table></div>
  </section>
  <p class="note noprint">Click Export to PDF, then choose "Save as PDF" as the destination in the print window.</p>
  <?php endif; ?>
</main>
</body></html>