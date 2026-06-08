<?php

// ---- SESSION LOCK FIX ----

session_name("AMRUTTULYA_SESS");

if (session_status() === PHP_SESSION_NONE) {
    // ⚠️ FIX: Live server pe 'C:/xampp/tmp' nahi hota, isliye ise hata diya. Server apna default path use karega.
    session_start();
}

// 🔑 Security Check
if (!isset($_SESSION['role'])) {
    die("Session Expired! Please login again.");
}

date_default_timezone_set('Asia/Kolkata');

// 🎯 FIX: Nayi Database Connection Details (InfinityFree Live Server)
$conn = new mysqli("sql202.infinityfree.com", "if0_42114637", "Mayank1803", "if0_42114637_apna_db");

if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

$shop_id = $_SESSION['shop_id'] ?? 1; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $conn->real_escape_string($_POST['customer_name']);
    
    // Total amount ko sahi format me nikala
    $total_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0.00;
    
    // Counter se aane wali raw items string
    $raw_items = $_POST['item_names']; 
    $clean_items = str_replace(array("\r", "\n", "\r\n"), '', $raw_items);
    
    $items_array = explode(',', $clean_items);
    $total_profit = 0;

    foreach ($items_array as $item_str) {
        $item_str = trim($item_str);
        if (empty($item_str)) continue;

        $name = $item_str;
        $qty = 1; // Default Qty

        // Splitting Logic: Agar string me 'x' ya ' x' ho
        if (stripos($item_str, ' x') !== false) {
            $parts = explode(' x', $item_str);
            $name = trim($parts[0]);
            $qty = (int)trim($parts[1]);
        } elseif (stripos($item_str, 'x') !== false) {
            $parts = explode('x', $item_str);
            $name = trim($parts[0]);
            $qty = isset($parts[1]) ? (int)trim($parts[1]) : 1;
        }

        $name = trim($name, ", "); 
        if (empty($name)) continue;
        if ($qty <= 0) $qty = 1;

        // Menu se price aur cost_price nikalna
        $stmt = $conn->prepare("SELECT price, cost_price FROM menu WHERE BINARY TRIM(UPPER(item_name)) = BINARY TRIM(UPPER(?))");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $item_price = (float)$row['price'];
            $item_cost = (float)$row['cost_price'];
            
            // Profit calculation
            $total_profit += ($item_price - $item_cost) * $qty;
        }
        $stmt->close();

        // Stock minus logic
        $stmt_s = $conn->prepare("UPDATE inventory SET stock_quantity = stock_quantity - ? WHERE BINARY TRIM(UPPER(item_name)) = BINARY TRIM(UPPER(?))");
        $stmt_s->bind_param("is", $qty, $name);
        $stmt_s->execute();
        $stmt_s->close();
    }

    // Custom 24-Hour Business day logic
    $current_hour = (int)date('G'); 
    $order_time = ($current_hour < 5) ? date('Y-m-d H:i:s', strtotime('-1 day')) : date('Y-m-d H:i:s');

    // Orders table me data insertion (Price aur Profit dono perfectly jayenge)
    $stmt_insert = $conn->prepare("INSERT INTO orders (shop_id, customer_name, item_name, price, profit, order_time) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("issdds", $shop_id, $customer_name, $clean_items, $total_amount, $total_profit, $order_time);
    
    if ($stmt_insert->execute()) {
        $stmt_insert->close();
        
        // AJAX aur Redirect dono handling ke liye
        header("Location: billing.php?success=1");
        echo "Success";
        exit();
    } else {
        echo "Database Error: " . $conn->error;
    }
}
?>

