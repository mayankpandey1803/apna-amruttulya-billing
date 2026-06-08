<?php

// ---- 1. SERVER SESSION & ERROR SETTINGS ----

error_reporting(E_ALL);

ini_set('display_errors', 1);

session_name("AMRUTTULYA_SESS");

// session_save_path('C:/xampp/tmp'); // BHAU YE ONLINE SERVER PE NAHI CHALTA ISLIYE HATA DIYA

session_start();

// Security Check - Agar staff ya admin logged in nahi hai toh login page par bhejo
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata');

// --- TERE NAYE ONLINE SERVER KI DETAILS YAHAN DAAL DI HAIN ---
$conn = new mysqli("sql202.infinityfree.com", "if0_42114637", "Mayank1803", "if0_42114637_apna_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// =========================================================================
// 🎯 24-HOUR CUSTOM BUSINESS DAY LOGIC (Subah 5:00 AM ko Naya Din Shuru Hoga)
// =========================================================================

$current_hour = (int)date('G'); // 24-hour format

if ($current_hour < 5) {
    $business_day = date('Y-m-d', strtotime('-1 day'));
} else {
    $business_day = date('Y-m-d');
}

define('TEST_ZERO_MODE', false); 

// ---- 🔑 STAFF CREDENTIALS CHANGE LOGIC ----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_staff_credentials'])) {
    $new_user = $conn->real_escape_string($_POST['new_username']);
    $new_pwd = $conn->real_escape_string($_POST['new_pwd']);
    $user_id = $_SESSION['user_id'] ?? null; 
    
    if ($user_id && !empty($new_user) && !empty($new_pwd)) {
        $sql = "UPDATE users SET username = '$new_user', password = '$new_pwd' WHERE id = $user_id";
        if($conn->query($sql)) {
            $_SESSION['username'] = $new_user;
            echo "<script>alert('Staff Username aur Password kamyabi se badal gaya bhau!'); window.location.href='billing.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error: " . $conn->error . "');</script>";
        }
    }
}

// ---- KHARCHA ADD LOGIC (FIXED TIME) ----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
    $amount = (float)$_POST['amount'];
    $reason = $conn->real_escape_string($_POST['reason']);
    $exact_time = $business_day . ' ' . date('H:i:s'); // 🎯 Date ke sath time jod diya
    $conn->query("INSERT INTO expenses (amount, expense_name, expense_date) VALUES ($amount, '$reason', '$exact_time')");
    header("Location: billing.php?expense=" . time());
    exit();
}

// ---- FETCH SALES, ORDERS LIST & EXPENSES ----
$sales = 0;
$expenses_list = [];
$today_orders = [];

if (TEST_ZERO_MODE) {
    $sales = 0;
} else {
    $sales_query = $conn->query("SELECT SUM(price) AS total_sale FROM orders WHERE DATE(order_time) = '$business_day'");
    if ($sales_query) {
        $sales_row = $sales_query->fetch_assoc();
        $sales = $sales_row['total_sale'] ?: 0;
    }
    
    $orders_query = $conn->query("SELECT DATE_FORMAT(order_time, '%d-%b %h:%i:%s %p') AS order_time_formatted, customer_name, item_name, price FROM orders WHERE DATE(order_time) = '$business_day' ORDER BY id DESC");
    if ($orders_query) {
        $today_orders = $orders_query->fetch_all(MYSQLI_ASSOC);
    }
    
    $res_exp = $conn->query("SELECT expense_name, amount FROM expenses WHERE DATE(expense_date) = '$business_day'");
    if($res_exp && $res_exp->num_rows > 0) {
        while($row_exp = $res_exp->fetch_assoc()) {
            $expenses_list[] = $row_exp;
        }
    }
}

$menu = $conn->query("SELECT * FROM menu");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Staff Multi-Panel - Apna Amruttulya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --primary: #ff9800;
            --danger: #ff5252;
            --success: #4caf50;
            --info: #2196f3;
            --text: #ffffff;
            --text-muted: #aaaaaa;
            --border: #2d2d2d;
            --table-header: #222222;
            --input-bg: #151515;
            --item-card-bg: #222222;
        }

        .light-mode {
            --bg-color: #f5f5f5;
            --card-bg: #ffffff;
            --primary: #e68a00;
            --danger: #d32f2f;
            --success: #388e3c;
            --info: #1976d2;
            --text: #121212;
            --text-muted: #666666;
            --border: #dddddd;
            --table-header: #eeeeee;
            --input-bg: #f9f9f9;
            --item-card-bg: #eaeaea;
        }

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 15px; background-color: var(--bg-color); color: var(--text); margin: 0; transition: background 0.3s, color 0.3s; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--primary); padding-bottom: 10px; margin-bottom: 25px; }
        .header-bar h1 { margin: 0; font-size: 24px; font-weight: bold; color: var(--text); display: flex; align-items: center; gap: 10px; }
        .header-bar h1 span { color: var(--primary); }
        .header-actions { display: flex; align-items: center; gap: 12px; }
        .btn-action { background: var(--card-bg); color: var(--text); border: 1px solid var(--border); padding: 8px 14px; border-radius: 8px; font-weight: bold; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 6px; text-decoration: none; transition: 0.2s; }
        .btn-action:hover { border-color: var(--primary); color: var(--primary); }
        .logout-btn { background: rgba(255, 82, 82, 0.1); color: var(--danger); border: 1px solid var(--danger); }
        .logout-btn:hover { background: var(--danger); color: white; }
        .box { background: var(--card-bg); padding: 20px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        h3 { color: var(--primary); margin-top: 0; font-size: 18px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .flex-container { display: flex; gap: 20px; flex-wrap: wrap; }
        .half-box { flex: 1; min-width: 300px; background: rgba(125,125,125,0.03); padding: 15px; border-radius: 10px; border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: rgba(0,0,0,0.02); border-radius: 8px; overflow: hidden; }
        th, td { border-bottom: 1px solid var(--border); padding: 12px; text-align: left; font-size: 14px; }
        th { background: var(--table-header); color: var(--primary); font-weight: bold; }
        input[type="number"], input[type="text"], input[type="password"] { background: var(--input-bg); color: var(--text); border: 1px solid var(--border); padding: 8px 12px; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        input:focus { border-color: var(--primary); outline: none; }
        .billing-grid { display: flex; gap: 25px; flex-wrap: wrap; margin-top: 15px; }
        .menu-side { flex: 1; min-width: 320px; }
        .bill-side { width: 400px; min-width: 320px; }
        .item-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px; }
        .item-card { padding: 15px 10px; border: 1px solid var(--border); cursor: pointer; border-radius: 10px; background: var(--item-card-bg); text-align: center; font-weight: bold; transition: 0.2s; }
        .item-card:hover { border-color: var(--primary); transform: scale(1.03); }
        .item-card h4 { margin: 0 0 5px 0; font-size: 14px; }
        .item-card p { margin: 0; color: var(--primary); }
        .bill-box { background: var(--card-bg); padding: 20px; border-radius: 12px; border: 1px solid var(--border); }
        .basket-table th, .basket-table td { padding: 8px 6px; font-size: 13px; text-align: center; }
        .basket-table td:first-child, .basket-table th:first-child { text-align: left; }
        .qty-btn { background: var(--border); color: var(--text); border: none; padding: 2px 8px; font-weight: bold; cursor: pointer; border-radius: 4px; font-size: 12px; }
        .qty-btn:hover { background: var(--primary); color: black; }
        .customer-input { width: 100%; padding: 10px; background: var(--input-bg); color: var(--text); border: 1px solid var(--border); border-radius: 6px; margin-bottom: 10px; box-sizing: border-box; }
        .action-buttons { display: flex; gap: 10px; }
        .btn-print { padding: 12px 20px; background: var(--success); color: white; border: none; font-weight: bold; border-radius: 8px; cursor: pointer; flex: 1; }
        .btn-save { padding: 12px 20px; background: var(--info); color: white; border: none; font-weight: bold; border-radius: 8px; cursor: pointer; flex: 1; }
        .sale-badge { background: rgba(76, 175, 80, 0.15); padding: 6px 14px; border-radius: 8px; font-weight: bold; color: var(--success); border: 1px solid var(--success); font-size: 16px; }
        .remove-btn { color: var(--danger); cursor: pointer; font-size: 14px; background: none; border: none; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 9999; }
        .modal-box { background: var(--card-bg); border: 1px solid var(--primary); padding: 25px; border-radius: 12px; width: 340px; box-shadow: 0 5px 25px rgba(0,0,0,0.5); position: relative; }
        .close-modal { position: absolute; top: 10px; right: 15px; color: var(--text-muted); cursor: pointer; font-size: 20px; }
        .close-modal:hover { color: var(--danger); }
        .test-banner { background: #5d4037; color: #ffb74d; padding: 10px; text-align: center; font-weight: bold; border-radius: 8px; margin-bottom: 15px; border: 1px solid #795548; font-size: 14px; }
        .toggle-sales-btn { background: var(--input-bg); color: var(--primary); border: 1px solid var(--primary); padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 6px; transition: 0.2s; }
        .toggle-sales-btn:hover { background: var(--primary); color: black; }
        .sales-detail-panel { display: none; margin-top: 15px; animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        
        .stock-danger { color: #ff5252 !important; font-weight: bold; background: rgba(255, 82, 82, 0.1); padding: 4px 8px; border-radius: 4px; border: 1px solid rgba(255, 82, 82, 0.2); display: inline-block; animation: blinker 1.5s linear infinite; }
        .stock-warning { color: #ff9800 !important; font-weight: bold; background: rgba(255, 152, 0, 0.1); padding: 4px 8px; border-radius: 4px; border: 1px solid rgba(255, 152, 0, 0.2); display: inline-block; }
        @keyframes blinker { 50% { opacity: 0.5; } }

        /* ========================================== */
        /* 🔥 NEW PRINT RECEIPT STYLING BY GEMINI     */
        /* ========================================== */
        #print-receipt-template {
            display: none; /* Normal Screen par hidden rahega */
        }

        @media print {
            /* 1. Poore screen/dashboard data ko chupao */
            body * {
                display: none !important;
            }
            
            /* 2. Sirf print template aur uske table parts ko active karo */
            #print-receipt-template, #print-receipt-template * {
                display: block !important;
            }
            #print-receipt-template table {
                display: table !important;
                width: 100% !important;
            }
            #print-receipt-template thead { display: table-header-group !important; }
            #print-receipt-template tbody { display: table-row-group !important; }
            #print-receipt-template tr { display: table-row !important; }
            #print-receipt-template th, #print-receipt-template td { 
                display: table-cell !important; 
                color: #000 !important;
                padding: 5px 0 !important;
            }

            /* 3. Receipt Box formatting (Thermal printer support - 80mm size) */
            #print-receipt-template {
                width: 76mm;
                max-width: 80mm;
                margin: 0 auto;
                padding: 5px;
                font-family: 'Courier New', Courier, monospace;
                color: #000 !important;
                background: #fff !important;
                font-size: 13px;
            }
            .p-text-center { text-align: center; }
            .p-text-right { text-align: right; }
            .p-bold { font-weight: bold; }
            .receipt-dashed { border-top: 1px dashed #000 !important; margin: 8px 0; border-bottom:none; border-left:none; border-right:none; }
            .receipt-row { display: flex; justify-content: space-between; margin: 3px 0; }
        }
    </style>
</head>
<body>

<div class="header-bar">
    <h1><i class="fa-solid fa-calculator"></i> Counter & Stock Panel <span>(Staff)</span></h1>
    <div class="header-actions">
        <button class="btn-action" onclick="toggleTheme()" id="theme-btn">
            <i class="fa-solid fa-sun"></i> Light Mode
        </button>
        <button class="btn-action" onclick="openSettings()">
            <i class="fa-solid fa-gear" style="color: var(--primary);"></i> Settings
        </button>
        <a href="logout.php" class="btn-action logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
</div>

<?php if (TEST_ZERO_MODE) { ?>
    <div class="test-banner">
        <i class="fa-solid fa-vial"></i> TESTING MODE ALIVE: Aaj ki Sale temporary ZERO (0) dikhaye dega test karne ke liye!
    </div>
<?php } ?>

<div class="modal-overlay" id="settingsModal">
    <div class="modal-box">
        <span class="close-modal" onclick="closeSettings()">&times;</span>
        <h3 style="margin-bottom: 15px;"><i class="fa-solid fa-user-gear"></i> Change Staff Credentials</h3>
        <form method="POST" action="billing.php" style="display: flex; flex-direction: column; gap: 12px;">
            <div>
                <label style="font-size: 13px; color: var(--text-muted); display:block; margin-bottom: 5px;">Apna Naya Username:</label>
                <input type="text" name="new_username" placeholder="Type New Username" required autocomplete="off" style="width: 100%; padding: 10px;">
            </div>
            <div>
                <label style="font-size: 13px; color: var(--text-muted); display:block; margin-bottom: 5px;">Apna Naya Password:</label>
                <input type="password" name="new_pwd" placeholder="Type New Password" required autocomplete="off" style="width: 100%; padding: 10px;">
            </div>
            <button type="submit" name="change_staff_credentials" style="background: var(--primary); color: black; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; font-size: 14px;">
                <i class="fa-solid fa-user-check"></i> UPDATE CREDENTIALS
            </button>
        </form>
    </div>
</div>

<div class="box">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <h3><i class="fa-solid fa-chart-simple"></i> Aaj ki Total Counter Sale:</h3>
            <button class="toggle-sales-btn" onclick="toggleSalesDetail()" id="sales-toggle-btn">
                <i class="fa-solid fa-eye"></i> Show Details
            </button>
        </div>
        <span class="sale-badge">₹<?php echo number_format($sales, 2); ?></span>
    </div>

    <div class="sales-detail-panel" id="salesDetailPanel">
        <h4 style="color: var(--primary); margin: 10px 0 5px 0; font-size: 14px;"><i class="fa-solid fa-list-check"></i> Recent Orders List (Today)</h4>
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Customer Name</th>
                    <th>Items</th>
                    <th style="text-align: center;">Qty</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($today_orders)) { 
                    foreach($today_orders as $order) { 
                        $raw_items = explode(',', $order['item_name']);
                        $clean_item_names = [];
                        $qty_list = [];

                        foreach($raw_items as $raw_it) {
                            $raw_it = trim($raw_it);
                            if(!empty($raw_it)) {
                                $parts = explode(' x', $raw_it);
                                $clean_item_names[] = trim($parts[0]);
                                $qty_list[] = isset($parts[1]) ? trim($parts[1]) : '1';
                            }
                        }
                        $display_items = implode(', ', $clean_item_names);
                        $display_qtys = implode(', ', $qty_list);
                ?>
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 500;"><?php echo $order['order_time_formatted']; ?></td>
                            <td><b><?php echo htmlspecialchars($order['customer_name']); ?></b></td>
                            <td style="color: var(--primary); font-size: 13px;"><?php echo htmlspecialchars($display_items); ?></td>
                            <td style="text-align: center; font-weight: bold; color: #ff9800;"><?php echo $display_qtys; ?></td>
                            <td style="font-weight: bold;">₹<?php echo number_format($order['price'], 2); ?></td>
                        </tr>
                    <?php } 
                } else { ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); font-size: 13px;">Abhi tak koi order nahi kata hai.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">
    
    <div class="flex-container">
        <div class="half-box">
            <h3><i class="fa-solid fa-warehouse"></i> Live Stock Status (Godown View)</h3>
            <table>
                <tr>
                    <th>Item Name</th>
                    <th>Current Qty</th>
                </tr>
                <?php
                $res = $conn->query("SELECT id, item_name, stock_quantity FROM inventory");
                while($row = $res->fetch_assoc()) {
                    $qty = (int)$row['stock_quantity'];
                    
                    if ($qty <= 5) {
                        $stock_style = "class='stock-danger'"; 
                    } elseif ($qty <= 15) {
                        $stock_style = "class='stock-warning'"; 
                    } else {
                        $stock_style = "style='color: var(--success); font-weight: bold;'"; 
                    }

                    echo "<tr>
                        <td><b>" . htmlspecialchars($row['item_name']) . "</b></td>
                        <td><span {$stock_style}>" . $qty . " units</span></td>
                    </tr>";
                }
                ?>
            </table>
        </div>

        <div class="half-box">
            <h3><i class="fa-solid fa-hand-holding-dollar"></i> Aaj Ka Kharcha Entry</h3>
            <form action="billing.php" method="POST" style="display: flex; gap: 8px; margin-bottom: 15px;">
                <input type="number" name="amount" placeholder="Amount (₹)" step="0.01" required style="width: 100px;">
                <input type="text" name="reason" placeholder="Wajah?" required style="flex: 1;">
                <button type="submit" name="add_expense" style="background:var(--danger); color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer; font-weight:bold;">Add</button>
            </form>

            <h4 style="color: var(--primary); margin-bottom: 8px; font-size:14px;">Aaj ke Kharche ki List:</h4>
            <table>
                <tr><th>Reason</th><th>Amount</th></tr>
                <?php
                if(!empty($expenses_list)) {
                    foreach($expenses_list as $row_exp) {
                        echo "<tr><td>" . htmlspecialchars($row_exp['expense_name']) . "</td><td style='color:var(--danger); font-weight:bold;'>₹{$row_exp['amount']}</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='2' style='color:var(--text-muted); text-align:center; font-size:12px;'>Koi kharcha nahi hua.</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>
</div>

<div class="box">
    <h3><i class="fa-solid fa-mug-hot"></i> Counter Billing & Order System</h3>
    <div class="billing-grid">
        <div class="menu-side">
            <div class="item-container">
                <?php while($row = $menu->fetch_assoc()) { ?>
                    <div class="item-card" data-name="<?php echo htmlspecialchars($row['item_name']); ?>" data-price="<?php echo $row['price']; ?>">
                        <h4><?php echo htmlspecialchars($row['item_name']); ?></h4>
                        <p>₹<?php echo $row['price']; ?></p>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="bill-side">
            <div class="bill-box">
                <h4 style="color:var(--primary); margin-top:0; font-size:16px;"><i class="fa-solid fa-basket-shopping"></i> Current Bill Basket</h4>
                
                <table class="basket-table" style="margin-bottom: 15px;">
                    <thead>
                        <tr>
                            <th style="width:40%;">Item</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th><i class="fa-solid fa-trash"></i></th>
                        </tr>
                    </thead>
                    <tbody id="bill-tbody">
                    </tbody>
                </table>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-top: 1px dashed var(--border); padding-top: 10px;">
                    <span>Total Amount:</span>
                    <span style="color:var(--primary); font-size:20px; font-weight:bold;">₹<span id="total-amount">0</span></span>
                </div>
                
                <form id="billingForm" method="POST" action="save_order.php">
                    <input type="text" name="customer_name" id="customer_name" class="customer-input" placeholder="Customer ka Naam" required autocomplete="off">
                    <input type="number" name="customer_phone" id="customer_phone" class="customer-input" placeholder="WhatsApp Number (Optional)" autocomplete="off">
                    <input type="hidden" name="total_amount" id="hidden_total" value="0">
                    <input type="hidden" name="item_names" id="hidden_items" value="">
                    
                    <div class="action-buttons">
                        <button type="button" class="btn-print" onclick="prepareAndPrint()"><i class="fa-solid fa-print"></i> Print</button>
                        <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Save Bill</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="print-receipt-template">
    <div class="p-text-center">
        <h2 style="margin: 0 0 5px 0; font-size: 18px; font-weight: bold;">APNA AMRUTTULYA</h2>
        <p style="margin: 2px 0; font-size: 11px;">Counter & Stock Management</p>
        <div class="receipt-dashed"></div>
        <p class="p-bold" style="margin: 3px 0; font-size: 13px; letter-spacing: 1px;">GST INVOICE</p>
    </div>
    
    <div style="margin-top: 10px; font-size: 12px;">
        <div class="receipt-row">
            <span><strong>Date:</strong> <span id="p-date"></span></span>
        </div>
        <div class="receipt-row">
            <span><strong>Customer:</strong> <span id="p-cust-name"></span></span>
        </div>
        <div class="receipt-row">
            <span><strong>WhatsApp:</strong> <span id="p-cust-phone"></span></span>
        </div>
    </div>
    
    <div class="receipt-dashed"></div>
    
    <table>
        <thead>
            <tr>
                <th style="text-align: left; width: 50%;">Product</th>
                <th style="text-align: center; width: 15%;">Qty</th>
                <th style="text-align: right; width: 35%;">Amount</th>
            </tr>
        </thead>
        <tbody id="p-items-tbody">
            </tbody>
    </table>
    
    <div class="receipt-dashed"></div>
    
    <div class="receipt-row p-bold" style="font-size: 14px; margin-top: 5px;">
        <span>GRAND TOTAL:</span>
        <span>₹<span id="p-grand-total">0.00</span></span>
    </div>
    
    <div class="receipt-dashed"></div>
    
    <div class="p-text-center" style="margin-top: 15px; font-size: 11px;">
        <p style="margin: 0;">Thank You! Visit Again 🙏</p>
    </div>
</div>

<script>
    function toggleSalesDetail() {
        let panel = document.getElementById('salesDetailPanel');
        let btn = document.getElementById('sales-toggle-btn');
        if (panel.style.display === 'none' || panel.style.display === '') {
            panel.style.display = 'block';
            btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Hide Details';
        } else {
            panel.style.display = 'none';
            btn.innerHTML = '<i class="fa-solid fa-eye"></i> Show Details';
        }
    }

    if (localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-mode');
        document.getElementById('theme-btn').innerHTML = '<i class="fa-solid fa-moon"></i> Dark Mode';
    }

    function toggleTheme() {
        document.body.classList.toggle('light-mode');
        let btn = document.getElementById('theme-btn');
        if (document.body.classList.contains('light-mode')) {
            localStorage.setItem('theme', 'light');
            btn.innerHTML = '<i class="fa-solid fa-moon"></i> Dark Mode';
        } else {
            localStorage.setItem('theme', 'dark');
            btn.innerHTML = '<i class="fa-solid fa-sun"></i> Light Mode';
        }
    }

    function openSettings() {
        document.getElementById('settingsModal').style.display = 'flex';
    }
    function closeSettings() {
        document.getElementById('settingsModal').style.display = 'none';
    }

    window.onclick = function(event) {
        let modal = document.getElementById('settingsModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    let cart = {}; 

    document.querySelectorAll('.item-card').forEach(card => {
        card.addEventListener('click', function() {
            let name = this.getAttribute('data-name').trim();
            let price = parseInt(this.getAttribute('data-price'));
            
            if (cart[name]) {
                cart[name].qty += 1;
            } else {
                cart[name] = { price: price, qty: 1 };
            }
            renderCart();
        });
    });

    function changeQty(name, amount) {
        if (cart[name]) {
            cart[name].qty += amount;
            if (cart[name].qty <= 0) {
                delete cart[name];
            }
            renderCart();
        }
    }

    function removeItem(name) {
        if (cart[name]) {
            delete cart[name];
            renderCart();
        }
    }

    function renderCart() {
        let tbody = document.getElementById('bill-tbody');
        tbody.innerHTML = "";
        let total = 0;
        let itemsStringArray = [];

        for (let name in cart) {
            let item = cart[name];
            let itemTotal = item.price * item.qty;
            total += itemTotal;
            
            itemsStringArray.push(`${name} x${item.qty}`);

            let tr = document.createElement('tr');
            
            let tdName = document.createElement('td');
            tdName.style.textAlign = "left";
            let bName = document.createElement('b');
            bName.innerText = name;
            tdName.appendChild(bName);
            tr.appendChild(tdName);

            let tdPrice = document.createElement('td');
            tdPrice.innerText = `₹${item.price}`;
            tr.appendChild(tdPrice);

            let tdQty = document.createElement('td');
            
            let btnMinus = document.createElement('button');
            btnMinus.type = "button";
            btnMinus.className = "qty-btn";
            btnMinus.innerText = "-";
            btnMinus.onclick = (function(n) { return function() { changeQty(n, -1); }; })(name);
            
            let qtySpan = document.createElement('span');
            qtySpan.style.margin = "0 5px";
            qtySpan.style.fontWeight = "bold";
            qtySpan.innerText = item.qty;

            let btnPlus = document.createElement('button');
            btnPlus.type = "button";
            btnPlus.className = "qty-btn";
            btnPlus.innerText = "+";
            btnPlus.onclick = (function(n) { return function() { changeQty(n, 1); }; })(name);

            tdQty.appendChild(btnMinus);
            tdQty.appendChild(qtySpan);
            tdQty.appendChild(btnPlus);
            tr.appendChild(tdQty);

            let tdTotal = document.createElement('td');
            tdTotal.style.fontWeight = "bold";
            tdTotal.style.color = "var(--primary)";
            tdTotal.innerText = `₹${itemTotal}`;
            tr.appendChild(tdTotal);

            let tdRemove = document.createElement('td');
            let btnRemove = document.createElement('button');
            btnRemove.type = "button";
            btnRemove.className = "remove-btn";
            btnRemove.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
            btnRemove.onclick = (function(n) { return function() { removeItem(n); }; })(name);
            tdRemove.appendChild(btnRemove);
            tr.appendChild(tdRemove);

            tbody.appendChild(tr);
        }

        document.getElementById('total-amount').innerText = total;
        document.getElementById('hidden_total').value = total;
        document.getElementById('hidden_items').value = itemsStringArray.join(', ');
    }

    // 🔥 NEW FUNCTION: PRINT BUTTON DATING & RENDER LOGIC BY GEMINI
    function prepareAndPrint() {
        let customerName = document.getElementById('customer_name').value.trim() || "Walk-in Customer";
        let whatsappNum = document.getElementById('customer_phone').value.trim() || "-";
        let totalAmt = document.getElementById('hidden_total').value;

        if (parseInt(totalAmt) === 0) {
            alert("Basket khaali hai bhau, pehle item select karo!");
            return;
        }

        // 1. Text details data fill karo hidden template me
        document.getElementById('p-cust-name').innerText = customerName;
        document.getElementById('p-cust-phone').innerText = whatsappNum;
        document.getElementById('p-grand-total').innerText = parseFloat(totalAmt).toFixed(2);
        
        // 2. Real-time dynamic Date & Time format set karo
        let now = new Date();
        let formattedDate = now.toLocaleDateString('en-IN') + ' ' + now.toLocaleTimeString('en-IN', {hour: '2-digit', minute:'2-digit'});
        document.getElementById('p-date').innerText = formattedDate;

        // 3. Print wale items ki table body ko populate karo loop se
        let pTbody = document.getElementById('p-items-tbody');
        pTbody.innerHTML = ""; // Pehle purana clear karo

        for (let name in cart) {
            let item = cart[name];
            let itemTotal = item.price * item.qty;

            let tr = document.createElement('tr');
            
            let tdName = document.createElement('td');
            tdName.innerText = name.toUpperCase(); // Capitalize item name like proper bills
            
            let tdQty = document.createElement('td');
            tdQty.style.textAlign = "center";
            tdQty.innerText = item.qty;
            
            let tdTotal = document.createElement('td');
            tdTotal.style.textAlign = "right";
            tdTotal.innerText = `${parseFloat(itemTotal).toFixed(2)}`;

            tr.appendChild(tdName);
            tr.appendChild(tdQty);
            tr.appendChild(tdTotal);
            pTbody.appendChild(tr);
        }

        // 4. Trigger Native Print Setup Window
        window.print();
    }

    // 🔥 WHATSAPP & AJAX SAVE LOGIC
    document.getElementById('billingForm').addEventListener('submit', function(e) {
        e.preventDefault(); 
        
        let form = this;
        let customerName = document.getElementById('customer_name').value;
        let whatsappNum = document.getElementById('customer_phone').value.trim();
        let totalAmt = document.getElementById('hidden_total').value;
        let itemDetails = document.getElementById('hidden_items').value;

        if (totalAmt == 0 || itemDetails === "") {
            alert("Basket khaali hai bhau, pehle item select karo!");
            return;
        }

        let formData = new FormData(form);

        fetch('save_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (whatsappNum !== "") {
                let msgText = `*Apna Amruttulya* ☕\n\n` +
                              `Namaste *${customerName}* ji,\n` +
                              `Apka bill kamyabi se save ho gaya hai.\n\n` +
                              `📝 *Order Details:* ${itemDetails}\n` +
                              `💰 *Total Amount:* ₹${totalAmt}\n\n` +
                              `Thank you! Visit Again. 🙏`;
                              
                let encodedMsg = encodeURIComponent(msgText);
                let whatsappUrl = `https://api.whatsapp.com/send?phone=91${whatsappNum}&text=${encodedMsg}`;
                
                window.open(whatsappUrl, '_blank');
            } else {
                alert("Order Saved Successfully!");
            }
            
            cart = {};
            form.reset();
            renderCart();
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Kuch gaddbad hui bill save karne mein!");
        });
    });
</script>

</body>
</html>
