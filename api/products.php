<?php 
require __DIR__ . '/../config.php';
$user = current_user();
if (!$user) json_out(['error' => 'Please log in again.'], 401);

$pdo = db();
$action = $_GET['action'] ?? '';
$isPost = $_SERVER['REQUEST_METHOD'] == 'POST';
$in = $isPost ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];

if ($isPost) {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) json_out(['error' => 'Invalid session token.'], 403);
    if (!in_array($user['role'], ['admin', 'manager'], true)) json_out(['error' => 'Only admin and managers can change products.'], 403);
}

function sentence_case(string $s): string {
    $s = preg_replace('/\s+/u', ' ', trim($s));
    return $s === '' ? '' : mb_strtoupper(mb_substr($s, 0, 1)) . mb_strtolower(mb_substr($s, 1));
}

try{ 
    switch($action){  
        case 'list':  
            $rows = fetch_products($pdo, trim($_GET['q'] ?? ''), (int)($_GET['category'] ?? 0), (string)($_GET['status'] ?? ''));  
            $stats = $pdo->query("SELECT COUNT(*) total, SUM(bottles_available > 0 AND bottles_available <= low_stock_threshold) low, SUM(bottles_available = 0) out_of_stock FROM products WHERE is_active = 1")->fetch();  
            json_out(['rows' => $rows, 'stats' => $stats]);  
            break;

        case 'suggest':  
            $st = $pdo->prepare("SELECT product_name, category_id, price_per_bottle AS price, buying_price, low_stock_threshold FROM products WHERE is_active = 1 AND product_name LIKE ? ORDER BY product_name ASC LIMIT 8");  
            $st->execute(["%" . trim($_GET['q'] ?? '') . "%"]);  
            json_out($st->fetchAll());  
            break;

        case 'add':  
            $name = sentence_case((string)($in['product_name'] ?? '')); 
            $cat = (int)($in['category_id'] ?? 0);  
            $price = (float)($in['price'] ?? 0);            
            $buying_price = (float)($in['buying_price'] ?? 0); 
            $bottles = (int)($in['bottles'] ?? 0);  
            $threshold = isset($in['low_stock_threshold']) && $in['low_stock_threshold'] !== '' ? (int)$in['low_stock_threshold'] : 20;
            
            if ($name == '' || $cat < 1) json_out(['error' => 'Enter the product name and choose a category.'], 422);
            if ($buying_price < 0)      json_out(['error' => 'Buying price cannot be negative.'], 422);
            if ($price <= 0)             json_out(['error' => 'Selling price must be above 0.'], 422);
            if ($bottles < 1)            json_out(['error' => 'Enter the number of bottles (1 or more).'], 422);
            if ($threshold < 0)          json_out(['error' => 'Low stock threshold cannot be negative.'], 422);

            $pdo->beginTransaction();  

            $mq = $pdo->prepare("SELECT mode_id FROM packaging_modes WHERE name = 'Bottle' LIMIT 1");
            $mq->execute();
            $modeId = (int)$mq->fetchColumn();
            if (!$modeId) {
                $pdo->prepare("INSERT INTO packaging_modes (name, bottles_per_unit) VALUES ('Bottle', 1)")->execute();
                $modeId = (int)$pdo->lastInsertId();
            }

            $f = $pdo->prepare("SELECT product_id, is_active FROM products WHERE product_name = ? FOR UPDATE");  
            $f->execute([$name]);  
            $p = $f->fetch();  

            if ($p && $p['is_active']) {       
                $pid = (int)$p['product_id'];  
                $pdo->prepare("UPDATE products SET bottles_available = bottles_available + ?, price_per_bottle = ?, buying_price = ?, low_stock_threshold = ? WHERE product_id = ?")  
                    ->execute([$bottles, $price, $buying_price, $threshold, $pid]);  
                $msg = "Restocked: +$bottles bottles."; $act = 'product_restock';  
            } elseif ($p) {         
                $pid = (int)$p['product_id'];  
                $pdo->prepare("UPDATE products SET is_active = 1, category_id = ?, price_per_bottle = ?, buying_price = ?, bottles_available = ?, low_stock_threshold = ? WHERE product_id = ?")  
                    ->execute([$cat, $price, $buying_price, $bottles, $threshold, $pid]);  
                $msg = "Product restored with $bottles bottles."; $act = 'product_restore';  
            } else {            
                $pdo->prepare("INSERT INTO products (product_name, category_id, price_per_bottle, buying_price, bottles_available, low_stock_threshold) VALUES (?,?,?,?,?,?)")  
                    ->execute([$name, $cat, $price, $buying_price, $bottles, $threshold]);  
                $pid = (int)$pdo->lastInsertId();  
                $msg = "New product added with $bottles bottles."; $act = 'product_add';  
            }  

            $pdo->prepare("INSERT INTO stock_entries (product_id, mode_id, units_added, bottles_added, added_by) VALUES (?,?,?,?,?)")  
                ->execute([$pid, $modeId, $bottles, $bottles, $user['user_id'] ?? $user['id']]);  
            $pdo->commit();  
            log_activity($act, "$name: +$bottles bottles, threshold set to $threshold");  
            json_out(['ok' => true, 'message' => $msg]);  
            break;

        case 'update':  
            $id = (int)($in['product_id'] ?? 0);  
            $name = sentence_case((string)($in['product_name'] ?? ''));  
            $cat = (int)($in['category_id'] ?? 0);  
            $price = (float)($in['price'] ?? 0);  
            $buying_price = (float)($in['buying_price'] ?? 0); 
            $stock = (int)($in['bottles_available'] ?? -1);  
            $threshold = isset($in['low_stock_threshold']) ? (int)$in['low_stock_threshold'] : 20;
            
            if ($id < 1 || $name === '' || $cat < 1 || $price <= 0 || $stock < 0 || $threshold < 0)  
                json_out(['error' => 'Check values. Price must be > 0 and stock/threshold cannot be negative.'], 422);  
            if ($buying_price < 0) json_out(['error' => 'Buying price cannot be negative.'], 422);
                
            $o = $pdo->prepare("SELECT product_name, category_id, price_per_bottle, buying_price, bottles_available, low_stock_threshold FROM products WHERE product_id = ? AND is_active = 1");  
            $o->execute([$id]);  
            $old = $o->fetch();  
            if (!$old) json_out(['error' => 'Product not found.'], 404);  
            
            $pdo->prepare("UPDATE products SET product_name=?, category_id=?, price_per_bottle=?, buying_price=?, bottles_available=?, low_stock_threshold=? WHERE product_id=? AND is_active=1")  
                ->execute([$name, $cat, $price, $buying_price, $stock, $threshold, $id]);  
                
            $ch = [];  
            if ($old['product_name'] !== $name) $ch[] = "name ({$old['product_name']} -> $name)";  
            if ((int)$old['low_stock_threshold'] !== $threshold) $ch[] = "threshold ({$old['low_stock_threshold']} -> $threshold)";

            log_activity('product_update', $old['product_name'] . ': ' . ($ch ? implode('; ', $ch) : 'updated'));  
            json_out(['ok' => true, 'message' => 'Product updated.']);  
            break;

        case 'delete': 
            $pid = (int)($in['product_id'] ?? 0); 
            $n = $pdo->prepare("SELECT product_name, bottles_available FROM products WHERE product_id = ? AND is_active = 1"); 
            $n->execute([$pid]); 
            $row = $n->fetch(); 
            
            $pdo->prepare("UPDATE products SET is_active = 0 WHERE product_id = ?")->execute([$pid]); 
            if($row) log_activity('product_delete', "{$row['product_name']}"); 
            json_out(['ok' => true, 'message' => 'Product deleted.']); 
            break;

        default: 
            json_out(['error' => 'Unknown action.'], 400); 
    } 
} catch (PDOException $ex) { 
    if ($pdo->inTransaction()) $pdo->rollBack(); 
    if ($ex->getCode() == '23000') json_out(['error' => 'A product with that name already exists. Note: Names are unique across items.'], 409); 
    json_out(['error' => 'Database error.'], 500); 
}