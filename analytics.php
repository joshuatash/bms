<?php 
require __DIR__ . '/config.php'; 
require_login(); 
if (!in_array(current_user()['role'], ['admin', 'manager'], true)) { header('Location: pos.php'); exit; }
$active = 'analytics'; 
$pdo = db();

// Date range filter (Default: Last 7 Days)
$range = $_GET['range'] ?? '7';
$days = ($range === '30') ? 30 : (($range === '90') ? 90 : 7);

// Helper to format large numbers cleanly for the Y-Axis (e.g., 50000 -> 50k)
function formatAxisNum($n) {
    if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
    if ($n >= 1000) return round($n / 1000, 1) . 'k';
    return round($n);
}

// --- FETCH ANALYTICS DATA ---
$chartData = [];
$totalSales = 0; $totalExp = 0; $totalRooms = 0; $totalGH = 0; $totalPA = 0;
$maxOverall = 0; // Track the highest value across ALL metrics for a unified Y-axis scale

for ($i = $days - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $displayDate = date(($days > 7 ? 'd M' : 'D'), strtotime($date));
    
    $s = $pdo->prepare("SELECT SUM(total_amount) FROM sales_record WHERE DATE(sale_date) = ?"); $s->execute([$date]);
    $sales = (float)($s->fetchColumn() ?? 0);
    
    $e = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE expense_date = ?"); $e->execute([$date]);
    $exp = (float)($e->fetchColumn() ?? 0);
    
    $r = $pdo->prepare("SELECT SUM(amount) FROM room_payments WHERE DATE(paid_at) = ?"); $r->execute([$date]);
    $rooms = (float)($r->fetchColumn() ?? 0);
    
    $g = $pdo->prepare("SELECT SUM(amount) FROM ground_hire WHERE DATE(created_at) = ?"); $g->execute([$date]);
    $gh = (float)($g->fetchColumn() ?? 0);
    
    $p = $pdo->prepare("SELECT SUM(amount) FROM pa_collections WHERE DATE(created_at) = ?"); $p->execute([$date]);
    $pa = (float)($p->fetchColumn() ?? 0);

    $totalSales += $sales;
    $totalExp += $exp;
    $totalRooms += $rooms;
    $totalGH += $gh;
    $totalPA += $pa;

    // Find the highest single value in this day to determine the chart scale
    $dayMax = max($sales, $exp, $rooms, $gh, $pa);
    if ($dayMax > $maxOverall) $maxOverall = $dayMax;

    $chartData[] = [
        'date' => $date,
        'label' => $displayDate,
        'sales' => $sales,
        'expenses' => $exp,
        'rooms' => $rooms,
        'gh' => $gh,
        'pa' => $pa
    ];
}

// Round the max overall up to a nice clean number for the Y-axis (e.g., 45,000 -> 50,000)
$scaleMax = max($maxOverall, 1000);
$power = pow(10, floor(log10($scaleMax)));
$scaleMax = ceil($scaleMax / $power) * $power;

$money = fn($n) => number_format((float)$n);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analytics</title>
    <style>
        :root { --navy:#0f172a; --slate:#334155; --muted:#64748b; --line:#e2e8f0; --amber:#f59e0b; --green:#16a34a; --red:#dc2626; --blue:#2563eb; --purple:#8b5cf6; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); color: var(--navy); }
        main { max-width: 1100px; margin: 24px auto; padding: 0 18px; display: grid; gap: 20px; }
        
        .head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; }
        .head h2 { margin: 0; font-size: 1.4rem; } .head p { margin: 2px 0 0; color: var(--muted); }
        
        .actions { display: flex; gap: 8px; }
        .btn { display: inline-block; border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; font-size: .9rem; text-decoration: none; text-align: center; }
        .btn.tab { background: #fff; color: var(--slate); border: 1px solid var(--line); }
        .btn.tab.on { background: var(--navy); color: #fff; border-color: var(--navy); }
        .btn.primary { background: var(--amber); color: var(--navy); } 
        .btn.primary:hover { background: #d97706; color: #fff; }

        /* Summary Tiles */
        .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
        .tile { background: #fff; border-radius: 12px; padding: 16px 18px; border-left: 5px solid var(--slate); box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .tile small { color: var(--slate); font-weight: 600; } .tile b { display: block; font-size: 1.5rem; margin-top: 4px; }
        .tile.money { border-color: var(--green); }
        .tile.spend { border-color: var(--red); }
        .tile.room { border-color: var(--blue); }
        .tile.gh { border-color: var(--amber); }
        .tile.pa { border-color: var(--purple); }

        /* Charts */
        .chart-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; }
        .chart-box { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); width: 100%; }
        .chart-title { font-size: 0.8rem; font-weight: 700; color: var(--slate); margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* New Chart Layout with Axes */
        .chart-area { display: flex; height: 180px; gap: 8px; margin-bottom: 10px; }
        .y-axis { display: flex; flex-direction: column; justify-content: space-between; align-items: flex-end; font-size: 0.65rem; color: var(--muted); font-weight: 600; width: 35px; padding-bottom: 4px; }
        .chart-plot { flex: 1; display: flex; justify-content: space-between; align-items: flex-end; border-left: 1px solid var(--line); border-bottom: 1px solid var(--line); padding: 0 4px 4px 0; position: relative; background-image: linear-gradient(to bottom, #f1f5f9 1px, transparent 1px); background-size: 100% 25%; background-position: 0 0; background-repeat: repeat-y; }
        .bar-group { display: flex; flex-direction: column; align-items: center; flex: 1; height: 100%; justify-content: flex-end; position: relative; z-index: 2; }
        .bar { width: 100%; max-width: 24px; border-radius: 3px 3px 0 0; transition: height 0.3s ease; min-height: 2px; position: relative; }
        .bar:hover { opacity: 0.8; }
        .bar.sales { background: var(--green); }
        .bar.expenses { background: var(--red); }
        .bar.rooms { background: var(--blue); }
        .bar.gh { background: var(--amber); }
        .bar.pa { background: var(--purple); }
        
        /* X-Axis */
        .x-axis { display: flex; justify-content: space-between; padding-left: 43px; /* Align with plot area */ }
        .x-label { flex: 1; text-align: center; font-size: 0.6rem; color: var(--muted); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .legend { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 12px; font-size: 0.75rem; font-weight: 600; color: var(--slate); }
        .legend-dot { width: 12px; height: 12px; border-radius: 3px; }

        .printonly { display: none; }

        @media(max-width: 640px) {
            main { margin: 14px auto; padding: 0 12px; }
            .head { flex-direction: column; align-items: stretch; text-align: center; }
            .actions { flex-direction: column; }
            .btn { width: 100%; text-align: center; }
            .tiles { grid-template-columns: 1fr; }
            .chart-container { grid-template-columns: 1fr; }
            .x-label { font-size: 0.5rem; } /* Smaller text on mobile to prevent overlap */
        }

        /* --- PRINT STYLES --- */
        @media print {
            .sidebar, .mbar, .scrim, .noprint { display: none !important; }
            body { padding: 0 !important; background: #fff; }
            main { margin: 0; padding: 0; max-width: none; gap: 12px; }
            .card, .tile, .chart-box { box-shadow: none; border: 1px solid #cbd5e1; }
            .printonly { display: block; }
            .chart-container { grid-template-columns: 1fr 1fr; gap: 20px; }
            .bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <div class="head">
        <div>
            <h2>Business Analytics</h2>
            <p>Visual overview of your performance.</p>
            <p class="printonly" style="color:#64748b; font-size:0.85rem; margin-top:6px;">Generated: <?= date('d M Y, h:i A') ?> by <?= e(current_user()['name']) ?></p>
        </div>
        <div class="actions noprint">
            <a class="btn tab <?= $range === '7' ? 'on' : '' ?>" href="?range=7">Last 7 Days</a>
            <a class="btn tab <?= $range === '30' ? 'on' : '' ?>" href="?range=30">Last 30 Days</a>
            <a class="btn tab <?= $range === '90' ? 'on' : '' ?>" href="?range=90">Last 90 Days</a>
            <button class="btn primary" onclick="window.print()">Export to PDF</button>
        </div>
    </div>

    <!-- Summary Tiles -->
    <section class="tiles">
        <div class="tile money">
            <small>Total Sales (<?= $days ?> days)</small>
            <b><?= CURRENCY ?> <?= $money($totalSales) ?></b>
        </div>
        <div class="tile spend">
            <small>Total Expenses</small>
            <b><?= CURRENCY ?> <?= $money($totalExp) ?></b>
        </div>
        <div class="tile room">
            <small>Rooms Collection</small>
            <b><?= CURRENCY ?> <?= $money($totalRooms) ?></b>
        </div>
        <div class="tile gh">
            <small>Ground Hire</small>
            <b><?= CURRENCY ?> <?= $money($totalGH) ?></b>
        </div>
        <div class="tile pa">
            <small>PA System</small>
            <b><?= CURRENCY ?> <?= $money($totalPA) ?></b>
        </div>
    </section>

    <!-- Charts -->
    <section class="chart-container">
        
        <?php 
        // Define chart configurations to loop through and avoid repetitive code
        $charts = [
            ['key' => 'sales', 'title' => 'Sales Performance', 'color' => 'var(--green)', 'class' => 'sales'],
            ['key' => 'expenses', 'title' => 'Expenses', 'color' => 'var(--red)', 'class' => 'expenses'],
            ['key' => 'rooms', 'title' => 'Rooms Collection', 'color' => 'var(--blue)', 'class' => 'rooms'],
            ['key' => 'gh', 'title' => 'Ground Hire', 'color' => 'var(--amber)', 'class' => 'gh'],
            ['key' => 'pa', 'title' => 'PA System', 'color' => 'var(--purple)', 'class' => 'pa']
        ];
        
        foreach ($charts as $chart): 
        ?>
        <div class="chart-box">
            <div class="chart-title"><?= $chart['title'] ?></div>
            
            <div class="chart-area">
                <!-- Y-Axis Labels -->
                <div class="y-axis">
                    <span><?= formatAxisNum($scaleMax) ?></span>
                    <span><?= formatAxisNum($scaleMax * 0.75) ?></span>
                    <span><?= formatAxisNum($scaleMax * 0.5) ?></span>
                    <span><?= formatAxisNum($scaleMax * 0.25) ?></span>
                    <span>0</span>
                </div>
                
                <!-- Bars -->
                <div class="chart-plot">
                    <?php foreach ($chartData as $index => $d): 
                        $val = $d[$chart['key']]; 
                        $h = ($val / $scaleMax) * 100; 
                        // Show all labels for 7 days, skip some for 30/90 to prevent overlap
                        $showLabel = ($days <= 7) || ($index % ceil($days / 7) === 0);
                    ?>
                        <div class="bar-group" title="<?= date('D, d M', strtotime($d['date'])) ?>: <?= CURRENCY ?> <?= number_format($val) ?>">
                            <div class="bar <?= $chart['class'] ?>" style="height: <?= $h ?>%;"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- X-Axis Labels -->
            <div class="x-axis">
                <?php foreach ($chartData as $index => $d): 
                    $showLabel = ($days <= 7) || ($index % ceil($days / 7) === 0);
                ?>
                    <div class="x-label"><?= $showLabel ? $d['label'] : '' ?></div>
                <?php endforeach; ?>
            </div>
            
            <div class="legend"><div class="legend-dot" style="background:<?= $chart['color'] ?>"></div> <?= $chart['title'] ?></div>
        </div>
        <?php endforeach; ?>
        
    </section>
</main>
</body>
</html>