<?php
require __DIR__ . '/../config.php';
$user = current_user();
if (!$user) json_out(['error' => 'Please log in again.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'POST only.'], 405);
if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) json_out(['error' => 'Invalid session token.'], 403);
$in    = json_decode(file_get_contents('php://input'), true) ?: [];
$items = $in['items'] ?? [];
if (!is_array($items) || !$items) json_out(['error' => 'The cart is empty.'], 422);

// Merge duplicates and validate quantities. Prices are NEVER taken from the browser.
$want = [];
foreach ($items as $it) {
    $pid = (int)($it['product_id'] ?? 0);
    $qty = (int)($it['qty'] ?? 0);
    if ($pid < 1 || $qty < 1 || $qty > 100000) json_out(['error' => 'Invalid item in the cart.'], 422);
    $want[$pid] = ($want[$pid] ?? 0) + $qty;
}
ksort($want);   // same lock order every time avoids deadlocks between two cashiers

$pdo = db();
try {
    $pdo->beginTransaction();
    
    // Check if an identical sale has already happened TODAY
    // We build a dynamic query to check exactly these items and quantities
    $signatureSql = "SELECT s.sale_id, s.receipt_no, u.full_name, s.sale_date 
                     FROM sales_record s
                     JOIN users u ON u.user_id = s.user_id
                     WHERE DATE(s.sale_date) = CURDATE() 
                     AND (SELECT COUNT(*) FROM sales_record_items i WHERE i.sale_id = s.sale_id) = ?";
    
    $signatureParams = [count($want)];
    
    foreach ($want as $pid => $qty) {
        $signatureSql .= " AND EXISTS (SELECT 1 FROM sales_record_items i WHERE i.sale_id = s.sale_id AND i.product_id = ? AND i.qty_bottles = ?)";
        $signatureParams[] = $pid;
        $signatureParams[] = $qty;
    }
    
    $checkStmt = $pdo->prepare($signatureSql . " LIMIT 1");
    $checkStmt->execute($signatureParams);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        $time = date('g:i A', strtotime($existing['sale_date']));
        $pdo->rollBack();
        json_out([
            'error' => "Possible double entry! An identical cart was already recorded today by {$existing['full_name']} at $time (Receipt: {$existing['receipt_no']}). If this is a separate order, clear the cart and ring it up with a different combination, or proceed knowing the sale was already logged."
        ], 409);
    }

    $sel = $pdo->prepare("SELECT product_id, product_name, price_per_bottle, COALESCE(buying_price, 0) AS buying_price, bottles_available
                          FROM products WHERE product_id = ? AND is_active = 1 FOR UPDATE");
    $lines = []; $total = 0.0; $totalProfit = 0.0;
    
    foreach ($want as $pid => $qty) {
        $sel->execute([$pid]);
        $p = $sel->fetch();
        if (!$p) { $pdo->rollBack(); json_out(['error' => 'A product in the cart is no longer available. Refresh and try again.'], 409); }
        if ((int)$p['bottles_available'] < $qty) {
            $pdo->rollBack();
            json_out(['error' => "Only {$p['bottles_available']} bottle(s) of {$p['product_name']} left."], 409);
        }
        $price = (float)$p['price_per_bottle'];
        $buyingPrice = (float)$p['buying_price'];
        
        $line       = round($price * $qty, 2);
        $lineCost   = round($buyingPrice * $qty, 2);
        $lineProfit = round($line - $lineCost, 2);
        
        $total += $line;
        $totalProfit += $lineProfit;
        
        $lines[] = [
            'id' => (int)$p['product_id'], 
            'name' => $p['product_name'], 
            'qty' => $qty, 
            'price' => $price, 
            'buying_price' => $buyingPrice,
            'line' => $line,
            'profit' => $lineProfit
        ];
    }
    $total = round($total, 2);
    $totalProfit = round($totalProfit, 2);

    // Save the sale
    $pdo->prepare("INSERT INTO sales_record (receipt_no, user_id, total_amount) VALUES (?,?,?)")
        ->execute(['TMP' . bin2hex(random_bytes(6)), $user['user_id'], $total]);
        
    $saleId  = (int)$pdo->lastInsertId();
    $receipt = 'R' . str_pad((string)$saleId, 6, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE sales_record SET receipt_no = ? WHERE sale_id = ?")->execute([$receipt, $saleId]);
    
    // Check if sales_record_items has profit/buying_price columns, insert gracefully
    $ins = $pdo->prepare("INSERT INTO sales_record_items (sale_id, product_id, product_name, qty_bottles, unit_price, line_total, buying_price, line_profit) VALUES (?,?,?,?,?,?,?,?)");
    $upd = $pdo->prepare("UPDATE products SET bottles_available = bottles_available - ? WHERE product_id = ? AND bottles_available >= ?");
    
    foreach ($lines as $l) {
        $ins->execute([$saleId, $l['id'], $l['name'], $l['qty'], $l['price'], $l['line'], $l['buying_price'], $l['profit']]);
        $upd->execute([$l['qty'], $l['id'], $l['qty']]);
        if ($upd->rowCount() !== 1) throw new RuntimeException('Stock changed during payment.');
    }
    
    $pdo->commit();
    $sum = implode(', ', array_map(fn($l) => $l['qty'] . ' x ' . $l['name'], $lines));
    log_activity('sale', "$receipt, total " . number_format($total) . " (Profit: " . number_format($totalProfit) . "): $sum");
    json_out(['ok' => true, 'sale_id' => $saleId, 'receipt_no' => $receipt, 'total' => $total, 'profit' => $totalProfit]);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_out(['error' => 'Could not save the sale. Nothing was charged. Error: ' . $ex->getMessage()], 500);
}