<?php 
require __DIR__ . '/config.php'; 
require_login(); 
if (!in_array(current_user()['role'], ['admin', 'manager'], true)) { header('Location: pos.php'); exit; }
$active = 'analytics'; 
$pdo = db();

// View filter: 'week' (7 days) or 'month' (30 days)
$view = $_GET['view'] ?? 'week';
$rangeDays = ($view === 'month') ? 30 : 7;
$startDate = date('Y-m-d', strtotime("-$rangeDays days"));

// --- FETCH SUMMARY METRICS ---
$totalSales = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales_record WHERE sale_date >= '$startDate'")->fetchColumn();
$totalExp = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= '$startDate'")->fetchColumn();
$totalRooms = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM room_payments WHERE paid_at >= '$startDate'")->fetchColumn();
$totalGH = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM ground_hire WHERE created_at >= '$startDate'")->fetchColumn();
$totalPA = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM pa_collections WHERE created_at >= '$startDate'")->fetchColumn();

// Profit vs Loss breakdown on sales
$pQuery = $pdo->prepare("SELECT 
    SUM(CASE WHEN (i.line_total - (COALESCE(p.buying_price, 0) * i.qty_bottles)) > 0 THEN (i.line_total - (COALESCE(p.buying_price, 0) * i.qty_bottles)) ELSE 0 END) AS profit,
    SUM(CASE WHEN (i.line_total - (COALESCE(p.buying_price, 0) * i.qty_bottles)) < 0 THEN ABS(i.line_total - (COALESCE(p.buying_price, 0) * i.qty_bottles)) ELSE 0 END) AS loss
    FROM sales_record_items i 
    JOIN sales_record sr ON sr.sale_id = i.sale_id 
    LEFT JOIN products p ON p.product_id = i.product_id 
    WHERE sr.sale_date >= ?");
$pQuery->execute([$startDate]);
$pRes = $pQuery->fetch();
$totalProfit = (float)($pRes['profit'] ?? 0);
$totalLoss = (float)($pRes['loss'] ?? 0);

// Calculate Net Profit (Gross Sales Profit minus Expenses)
$netProfit = $totalProfit - $totalExp;

// Top Selling Products breakdown
$topProducts = $pdo->prepare("SELECT i.product_name, SUM(i.qty_bottles) as qty, SUM(i.line_total) as revenue 
    FROM sales_record_items i 
    JOIN sales_record sr ON sr.sale_id = i.sale_id 
    WHERE sr.sale_date >= ? 
    GROUP BY i.product_id, i.product_name 
    ORDER BY revenue DESC LIMIT 5");
$topProducts->execute([$startDate]);
$topProductsList = $topProducts->fetchAll();

$money = fn($n) => number_format((float)$n);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business Analytics - Reports</title>
    <style>
        :root{
            --navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--amber:#f59e0b;--green:#16a34a;--red:#dc2626;--blue:#2563eb;--purple:#8b5cf6;
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
        main { max-width: 1100px; margin: 24px auto; padding: 0 18px; display: grid; gap: 20px; }
        
        .head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; }
        .head h2 { margin: 0; font-size: 1.4rem; color: var(--text); }
        .head p { margin: 2px 0 0; color: var(--text-dim); }
        
        .actions { display: flex; gap: 8px; }
        .btn { display: inline-block; border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; font-size: .9rem; text-decoration: none; text-align: center; transition: .15s; }
        .btn.tab { background: var(--surface-2); color: var(--text-dim); border: 1px solid var(--border); }
        .btn.tab:hover { background: var(--surface-3); color: var(--text); }
        .btn.tab.on { background: var(--amber); color: #0b1220; border-color: var(--amber); }
        .btn.primary { background: var(--amber); color: #0b1220; box-shadow: 0 4px 6px -1px rgba(245,158,11,.3); }
        .btn.primary:hover { background: #d97706; color: #fff; }

        /* Summary Tiles */
        .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
        .tile { background: var(--surface); border-radius: 12px; padding: 16px 18px; border-left: 5px solid var(--text-dim); border-top: 1px solid var(--border); border-right: 1px solid var(--border); border-bottom: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,.3); }
        .tile small { color: var(--text-dim); font-weight: 600; text-transform: uppercase; font-size: 0.72rem; }
        .tile b { display: block; font-size: 1.4rem; margin-top: 4px; color: var(--text); }
        .tile.money { border-left-color: var(--green); }
        .tile.profit { border-left-color: var(--blue); }
        .tile.net { border-left-color: var(--green); }
        .tile.spend { border-left-color: var(--red); }

        /* Chart Grid Layout */
        .chart-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
        .chart-box { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.3); display: flex; flex-direction: column; align-items: center; }
        .chart-title { font-size: 0.95rem; font-weight: 700; color: var(--text); text-transform: uppercase; letter-spacing: 0.5px; align-self: flex-start; margin-bottom: 20px; }
        
        /* Pie Chart Styling */
        .pie-chart { width: 160px; height: 160px; border-radius: 50%; position: relative; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,.4); }
        .pie-hole { position: absolute; inset: 35px; background: var(--surface); border-radius: 50%; display: grid; place-items: center; font-size: 0.75rem; font-weight: 700; color: var(--text-dim); text-align: center; line-height: 1.3; }

        /* Legend & Keys */
        .legend { width: 100%; display: flex; flex-direction: column; gap: 8px; font-size: 0.82rem; font-weight: 600; color: var(--text-dim); margin-top: 10px; }
        .legend-row { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 6px; }
        .legend-row span { color: var(--text); }
        .legend-item { display: flex; align-items: center; gap: 8px; }
        .legend-dot { width: 12px; height: 12px; border-radius: 3px; flex-shrink: 0; }

        /* Interpretation box */
        .interpretation { background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; margin-top: 15px; font-size: 0.82rem; color: var(--text-dim); line-height: 1.4; width: 100%; }
        .interpretation b { color: var(--text); }

        .printonly { display: none; }

        @media(max-width: 640px) {
            main { margin: 14px auto; padding: 0 12px; }
            .head { flex-direction: column; align-items: stretch; text-align: center; }
            .actions { flex-direction: column; }
            .btn { width: 100%; text-align: center; }
            .tiles { grid-template-columns: 1fr; }
            .chart-container { grid-template-columns: 1fr; }
        }

        /* --- PRINT STYLES --- */
        @media print {
            .sidebar, .mbar, .scrim, .noprint { display: none !important; }
            body { padding: 0 !important; background: #fff; color: #0f172a; }
            main { margin: 0; padding: 0; max-width: none; gap: 12px; }
            .tile, .chart-box { box-shadow: none; border: 1px solid #cbd5e1; background: #fff; }
            .head h2, .chart-title, .tile b { color: #0f172a; }
            .head p, .tile small, .legend, .interpretation { color: #334155; }
            .pie-hole { background: #fff; color: #64748b; }
            .interpretation { background: #f8fafc; }
            .printonly { display: block; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <div class="head">
        <div>
            <h2>Business Analytics &mdash; Performance Reports</h2>
            <p>Detailed breakdown viewable per week or per month using individual charts.</p>
            <p class="printonly" style="color:#64748b; font-size:0.85rem; margin-top:6px;">Generated: <?= date('d M Y, h:i A') ?> by <?= e(current_user()['name']) ?></p>
        </div>
        <div class="actions noprint">
            <a class="btn tab <?= $view === 'week' ? 'on' : '' ?>" href="?view=week">Per Week (7 Days)</a>
            <a class="btn tab <?= $view === 'month' ? 'on' : '' ?>" href="?view=month">Per Month (30 Days)</a>
            <button class="btn primary" onclick="window.print()">Export to PDF</button>
        </div>
    </div>

    <!-- Summary Tiles -->
    <section class="tiles">
        <div class="tile money">
            <small>Total Sales Revenue</small>
            <b><?= CURRENCY ?> <?= $money($totalSales) ?></b>
        </div>
        <div class="tile profit">
            <small>Gross Profit on Sales</small>
            <b style="color:#60a5fa;"><?= CURRENCY ?> <?= $money($totalProfit) ?></b>
        </div>
        <div class="tile spend">
            <small>Operating Expenses</small>
            <b style="color:#f87171;"><?= CURRENCY ?> <?= $money($totalExp) ?></b>
        </div>
        <div class="tile net">
            <small>Net Profit (After Expenses)</small>
            <b style="color:#4ade80;"><?= CURRENCY ?> <?= $money($netProfit) ?></b>
        </div>
    </section>

    <!-- Individual Charts Grid -->
    <section class="chart-container">
        
        <!-- CHART 1: Gross Profit vs Expenses Impact -->
        <?php 
        // Compare Gross Profit against Expenses to evaluate net bottom-line health
        $baseScale = max($totalProfit, $totalExp, 1);
        $profitBarPct = ($totalProfit / $baseScale) * 100;
        $expBarPct = ($totalExp / $baseScale) * 100;

        // For Conic ratio calculation (Gross Profit vs Expenses)
        $plTotal = max($totalProfit + $totalExp, 1);
        $profitShare = ($totalProfit / $plTotal) * 100;
        $expShare = ($totalExp / $plTotal) * 100;
        $conicPL = "conic-gradient(#2563eb 0% {$profitShare}%, #dc2626 {$profitShare}% 100%)";
        ?>
        <div class="chart-box">
            <div class="chart-title">1. Gross Profit vs. Expenses Impact</div>
            <div class="pie-chart" style="background: <?= $conicPL ?>;">
                <div class="pie-hole"><b>Net</b><br><?= $money($netProfit) ?></div>
            </div>
            <div class="legend">
                <div class="legend-row">
                    <div class="legend-item"><div class="legend-dot" style="background:#2563eb;"></div> Gross Sales Profit</div>
                    <span><?= CURRENCY ?> <?= $money($totalProfit) ?></span>
                </div>
                <div class="legend-row">
                    <div class="legend-item"><div class="legend-dot" style="background:#dc2626;"></div> Operating Expenses</div>
                    <span><?= CURRENCY ?> <?= $money($totalExp) ?></span>
                </div>
            </div>
            <div class="interpretation">
                <b>Interpretation:</b> Evaluates how operating expenses weigh against your inventory sales earnings. Your net profit after expenses stands at <b><?= CURRENCY ?> <?= $money($netProfit) ?></b>.
            </div>
        </div>

        <!-- CHART 2: Top Selling Products Share -->
        <?php 
        $topRevTotal = array_sum(array_column($topProductsList, 'revenue'));
        $prodColors = ['#16a34a', '#2563eb', '#f59e0b', '#8b5cf6', '#dc2626'];
        $currentDeg = 0;
        $conicParts = [];
        foreach ($topProductsList as $idx => $prod) {
            $share = $topRevTotal > 0 ? ($prod['revenue'] / $topRevTotal) * 100 : 0;
            $nextDeg = $currentDeg + $share;
            $color = $prodColors[$idx % count($prodColors)];
            $conicParts[] = "{$color} {$currentDeg}% {$nextDeg}%";
            $currentDeg = $nextDeg;
        }
        $conicProducts = count($conicParts) > 0 ? "conic-gradient(" . implode(', ', $conicParts) . ")" : "var(--surface-2)";
        ?>
        <div class="chart-box">
            <div class="chart-title">2. Top Selling Products (by Revenue)</div>
            <div class="pie-chart" style="background: <?= $conicProducts ?>;">
                <div class="pie-hole"><b>Top 5</b><br>Share</div>
            </div>
            <div class="legend">
                <?php if (empty($topProductsList)): ?>
                    <div style="text-align:center; color:var(--text-mute); padding:10px;">No product sales recorded for this period.</div>
                <?php else: foreach ($topProductsList as $idx => $prod): 
                    $share = $topRevTotal > 0 ? ($prod['revenue'] / $topRevTotal) * 100 : 0;
                    $color = $prodColors[$idx % count($prodColors)];
                ?>
                    <div class="legend-row">
                        <div class="legend-item"><div class="legend-dot" style="background:<?= $color ?>;"></div> <?= e($prod['product_name']) ?> (<?= (int)$prod['qty'] ?> bot)</div>
                        <span><?= CURRENCY ?> <?= $money($prod['revenue']) ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="interpretation">
                <b>Interpretation:</b> Highlights your highest-earning inventory items, allowing you to prioritize restocking for top performers.
            </div>
        </div>

        <!-- CHART 3: Revenue Streams Breakdown -->
        <?php 
        $totalStreams = $totalSales + $totalRooms + $totalGH + $totalPA;
        $salesShare = $totalStreams > 0 ? ($totalSales / $totalStreams) * 100 : 0;
        $roomsShare = $totalStreams > 0 ? ($totalRooms / $totalStreams) * 100 : 0;
        $ghShare = $totalStreams > 0 ? ($totalGH / $totalStreams) * 100 : 0;
        $paShare = $totalStreams > 0 ? ($totalPA / $totalStreams) * 100 : 0;

        $d1 = $salesShare;
        $d2 = $d1 + $roomsShare;
        $d3 = $d2 + $ghShare;
        $conicStreams = "conic-gradient(
            #16a34a 0% {$d1}%, 
            #2563eb {$d1}% {$d2}%, 
            #f59e0b {$d2}% {$d3}%, 
            #8b5cf6 {$d3}% 100%
        )";
        ?>
        <div class="chart-box">
            <div class="chart-title">3. Revenue Streams Breakdown</div>
            <div class="pie-chart" style="background: <?= $conicStreams ?>;">
                <div class="pie-hole"><b>Streams</b><br>Total</div>
            </div>
            <div class="legend">
                <div class="legend-row">
                    <div class="legend-item"><div class="legend-dot" style="background:#16a34a;"></div> Bar Sales</div>
                    <span><?= CURRENCY ?> <?= $money($totalSales) ?> (<?= round($salesShare, 1) ?>%)</span>
                </div>
                <div class="legend-row">
                    <div class="legend-item"><div class="legend-dot" style="background:#2563eb;"></div> Rooms</div>
                    <span><?= CURRENCY ?> <?= $money($totalRooms) ?> (<?= round($roomsShare, 1) ?>%)</span>
                </div>
                <div class="legend-row">
                    <div class="legend-item"><div class="legend-dot" style="background:#f59e0b;"></div> Ground Hire</div>
                    <span><?= CURRENCY ?> <?= $money($totalGH) ?> (<?= round($ghShare, 1) ?>%)</span>
                </div>
                <div class="legend-row">
                    <div class="legend-item"><div class="legend-dot" style="background:#8b5cf6;"></div> PA System</div>
                    <span><?= CURRENCY ?> <?= $money($totalPA) ?> (<?= round($paShare, 1) ?>%)</span>
                </div>
            </div>
            <div class="interpretation">
                <b>Interpretation:</b> Compares contributions from bar inventory vs. lodge/facility collections over the selected <?= $rangeDays ?>-day period.
            </div>
        </div>

    </section>
</main>
</body>
</html>