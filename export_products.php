<?php
require __DIR__ . '/config.php';
require_admin();
log_activity('export_products', 'Opened the products report');
$status = (string)($_GET['status'] ?? '');
$rows = fetch_products(db(), trim($_GET['q'] ?? ''), (int)($_GET['category'] ?? 0), $status);
$statusLabel = ['out' => 'Out of Stock', 'low' => 'Low Stock', 'available' => 'Available'][$status] ?? '';
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Products Report</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;margin:30px;color:#0f172a}
h1{margin:0}p{color:#64748b;margin:4px 0 18px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{background:#0f172a;color:#fff;text-align:left;padding:9px}td{padding:8px 9px;border-bottom:1px solid #e2e8f0}
tr:nth-child(even) td{background:#f8fafc}.r{text-align:right}
.note{margin-top:16px;font-size:12px;color:#64748b}

.bar{display:flex;gap:10px;margin-bottom:16px}
.bar .back,.bar button{padding:9px 18px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;border:0}
.bar .back{background:#e2e8f0;color:#334155}.bar .back:hover{background:#cbd5e1}
.bar button{background:#f59e0b;color:#0f172a}.bar button:hover{background:#d97706;color:#fff}
@media print{.noprint{display:none}body{margin:12mm}}
@page{size:A4}
</style></head><body>
<div class="noprint bar">
  <a class="back" href="products.php">&larr; Back to Products</a>
  <button onclick="window.print()">Print / Save as PDF</button>
</div>
<h1>Current Products</h1>
<p>Generated <?= date('d M Y, H:i') ?> by <?= e(current_user()['name']) ?> | <?= count($rows) ?> products<?= $statusLabel ? ' | Showing: ' . e($statusLabel) . ' only' : '' ?></p>
<table>
<tr><th>#</th><th>Name</th><th>Category</th><th class="r">Bottles</th><th class="r">Price (<?= CURRENCY ?>)</th><th>Status</th></tr>
<?php foreach ($rows as $i => $p): ?>
<tr><td><?= $i + 1 ?></td><td><?= e($p['product_name']) ?></td><td><?= e($p['category']) ?></td>
<td class="r"><?= number_format((int)$p['bottles_available']) ?></td><td class="r"><?= number_format((float)$p['price']) ?></td><td><?= e($p['status']) ?></td></tr>
<?php endforeach; ?>
</table>
<p class="note">In the print dialog, choose "Save as PDF" as the destination.</p>
<script>window.onload = () => setTimeout(() => window.print(), 400);</script>
</body></html>
