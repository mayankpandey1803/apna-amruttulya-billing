<?php

// ---- 1. SERVER SESSION & ERROR SETTINGS FIX ----

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_name("AMRUTTULYA_SESS");

session_start();

date_default_timezone_set('Asia/Kolkata');

// SECURE CHECK: Agar role 'admin' nahi hai, toh bhaga do yahan se
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// 🎯 Live Server Database Connection
$conn = new mysqli("sql202.infinityfree.com", "if0_42114637", "Mayank1803", "if0_42114637_apna_db");

if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// =========================================================================
// 🔐 SETTINGS: USERNAME & PASSWORD UPDATE LOGIC
// =========================================================================

$update_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $new_user = trim($_POST['new_username']);
    $new_pass = trim($_POST['new_password']);
    
    if (!empty($new_user) && !empty($new_pass)) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE role = 'admin'");
        $stmt->bind_param("ss", $new_user, $new_pass);
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $new_user;
            $update_msg = "<script>alert('Malik! ID aur Password successfully badal gaya hai.');</script>";
        } else {
            $update_msg = "<script>alert('Gaddari karbe! Update nahi ho paya.');</script>";
        }
        $stmt->close();
    }
}

// =========================================================================
// 🎯 24-HOUR CUSTOM BUSINESS DAY LOGIC
// =========================================================================
$current_hour = (int)date('G'); 

if ($current_hour < 5) {
    $today = date('Y-m-d', strtotime('-1 day'));
} else {
    $today = date('Y-m-d');
}

// 1. Aaj ki Sale
$sales_res = $conn->query("SELECT SUM(price) as total_sales FROM orders WHERE DATE(order_time) = '$today'");
$sales_row = $sales_res->fetch_assoc();
$today_sales = (float)($sales_row['total_sales'] ?? 0);

// 2. Aaj ka Kharcha
$expense_res = $conn->query("SELECT SUM(amount) as total_expense FROM expenses WHERE DATE(expense_date) = '$today'");
$expense_row = $expense_res->fetch_assoc();
$today_expense = (float)($expense_row['total_expense'] ?? 0);

// 3. Net Profit
$profit_res = $conn->query("SELECT SUM(profit) as order_profit FROM orders WHERE DATE(order_time) = '$today'");
$profit_row = $profit_res->fetch_assoc();
$order_profit = (float)($profit_row['order_profit'] ?? 0);
$net_profit = $order_profit - $today_expense;

// 4. Total Stock
$stock_res = $conn->query("SELECT SUM(stock_quantity) as total_stock FROM inventory");
$stock_row = $stock_res->fetch_assoc();
$total_stock = $stock_row['total_stock'] ?? 0;

// 5. Aaj Ke Kharcho ki Detail
$expense_list_res = $conn->query("SELECT * FROM expenses WHERE DATE(expense_date) = '$today' ORDER BY expense_date DESC");

// 📊 6. NAYA FEATURE: Monthly Sales Report (Group By Date)
$monthly_sales_res = $conn->query("
    SELECT DATE(order_time) as sale_date, SUM(price) as daily_total, SUM(profit) as daily_profit 
    FROM orders 
    WHERE MONTH(order_time) = MONTH(CURRENT_DATE()) AND YEAR(order_time) = YEAR(CURRENT_DATE())
    GROUP BY DATE(order_time) 
    ORDER BY DATE(order_time) DESC
");

?>

<?php echo $update_msg; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Malik Dashboard - Apna Amruttulya</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #121212; color: #fff; font-family: Arial, sans-serif; margin: 20px; transition: background-color 0.3s ease, color 0.3s ease; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ff9800; padding-bottom: 10px; flex-wrap: wrap; gap: 10px;}
        .panel-title { color: #ff9800; font-size: 24px; font-weight: bold; }
        
        .nav-links { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .nav-link-item { color: #ff9800; text-decoration: none; font-weight: bold; font-size: 14px; cursor: pointer; }
        .nav-link-item:hover { color: #ffa726; }
        
        .logout-btn { background: #e50914; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; font-size: 14px;}
        .logout-btn:hover { background: #ff5252; }
        
        .mode-btn { background: #ff9800; color: #121212; padding: 8px 15px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 14px;}
        .mode-btn:hover { background: #ffa726; }

        .report-btn { background: #4caf50; color: white; padding: 8px 15px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 14px;}
        .report-btn:hover { background: #45a049; }
        
        .toggle-section-btn { background: #333; color: #ff9800; border: 1px solid #ff9800; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: bold; cursor: pointer; transition: all 0.2s; }
        .toggle-section-btn:hover { background: #ff9800; color: #121212; }

        .stats-container { display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap; }
        .card { background: #1e1e1e; padding: 20px; border-radius: 8px; min-width: 200px; flex: 1; border: 1px solid #333; transition: background-color 0.3s ease, border-color 0.3s ease; }
        .card h3 { margin: 0 0 10px 0; font-size: 14px; color: #aaa; text-transform: uppercase; display: flex; align-items: center; gap: 8px; }
        .card p { margin: 0; font-size: 28px; font-weight: bold; color: #fff; }
        .card.profit p { color: #4caf50; }
        .card.loss p { color: #f44336; }
        .card.expense p { color: #f44336; }
        
        #openExpenseModalBtn { cursor: pointer; position: relative; }
        #openExpenseModalBtn:hover { border-color: #f44336; background-color: #2a1a1a; }
        #openExpenseModalBtn::after { content: 'Click to view details'; position: absolute; bottom: 5px; right: 10px; font-size: 10px; color: #f44336; opacity: 0.7; }
        
        .section-title { color: #ff9800; margin-top: 40px; font-size: 20px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .title-text { display: flex; align-items: center; gap: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #1e1e1e; border-radius: 8px; overflow: hidden; border: 1px solid #333; transition: background-color 0.3s ease; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #333; }
        th { background-color: #2a2a2a; color: #ff9800; }
        tr:hover { background-color: #252525; }
        .profit-badge { background: #4caf50; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 14px; font-weight: bold; display: inline-block; }
        .profit-zero { background: #757575; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 14px; font-weight: bold; display: inline-block; }
        
        .stock-danger { color: #ff5252 !important; font-weight: bold; background: rgba(255, 82, 82, 0.1); padding: 4px 10px; border-radius: 4px; border: 1px solid rgba(255, 82, 82, 0.2); display: inline-block; animation: blinker 1.5s linear infinite; }
        .stock-warning { color: #ff9800 !important; font-weight: bold; background: rgba(255, 152, 0, 0.1); padding: 4px 10px; border-radius: 4px; border: 1px solid rgba(255, 152, 0, 0.2); display: inline-block; }
        .stock-safe { color: #4caf50 !important; font-weight: bold; background: rgba(76, 175, 80, 0.1); padding: 4px 10px; border-radius: 4px; display: inline-block; }
        @keyframes blinker { 50% { opacity: 0.5; } }

        body.light-mode { background-color: #f5f6fa; color: #222; }
        body.light-mode .card { background: #ffffff; border-color: #dcdde1; }
        body.light-mode .card p { color: #222; }
        body.light-mode .card h3 { color: #666; }
        body.light-mode table { background: #ffffff; border-color: #dcdde1; }
        body.light-mode th { background-color: #f1f2f6; color: #e67e22; }
        body.light-mode td { border-bottom-color: #f1f2f6; color: #333; }
        body.light-mode tr:hover { background-color: #f8f9fa; }
        body.light-mode td b { color: #222 !important; }
        body.light-mode .toggle-section-btn { background: #fff; color: #e67e22; border-color: #e67e22; }
        body.light-mode .toggle-section-btn:hover { background: #e67e22; color: #fff; }
        body.light-mode #openExpenseModalBtn:hover { background-color: #fce4e4; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); align-items: center; justify-content: center; }
        .modal-content { background-color: #1e1e1e; border: 2px solid #ff9800; border-radius: 8px; padding: 25px; width: 90%; max-width: 400px; color: #fff; position: relative; max-height: 80vh; overflow-y: auto; }
        .modal-content.wide { max-width: 600px; border-color: #f44336; } 
        .modal-content.green-border { border-color: #4caf50; max-width: 600px; } /* Monthly report modal ke liye */
        body.light-mode .modal-content { background-color: #fff; color: #222; }
        .close-modal { position: absolute; right: 15px; top: 10px; font-size: 24px; cursor: pointer; color: #aaa; }
        .close-modal:hover { color: #ff9800; }
        .modal h3 { margin-top: 0; color: #ff9800; }
        .modal-content.wide h3 { color: #f44336; }
        .modal-content.green-border h3 { color: #4caf50; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; box-sizing: border-box; border-radius: 5px; border: 1px solid #444; background: #2b2b2b; color: #fff; }
        body.light-mode .form-group input { background: #f1f2f6; color: #222; border-color: #ccc; }
        .save-settings-btn { background: #ff9800; color: #121212; width: 100%; padding: 10px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .save-settings-btn:hover { background: #ffa726; }
    </style>
</head>
<body>

    <div class="header">
        <div class="panel-title">👑 Malik Dashboard</div>
        <div class="nav-links">
            <button id="modeToggle" class="mode-btn"><i class="fa-solid fa-moon"></i> Light Mode</button>
            <button id="openMonthlyBtn" class="report-btn"><i class="fa-solid fa-calendar-days"></i> Monthly Report</button>
            <a href="godown.php" class="nav-link-item"><i class="fa-solid fa-warehouse"></i> Manage Stock</a>
            <span class="nav-link-item" id="openSettingsBtn"><i class="fa-solid fa-user-gear"></i> Settings</span>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <div class="stats-container">
        <div class="card">
            <h3><i class="fa-solid fa-chart-line"></i> AAJ KI SALE</h3>
            <p>₹<?php echo number_format($today_sales, 2); ?></p>
        </div>
        
        <div class="card expense" id="openExpenseModalBtn" title="Kharcha Details Dekhne Ke Liye Click Karein">
            <h3><i class="fa-solid fa-wallet"></i> KHARCHA (Tap for info)</h3>
            <p>₹<?php echo number_format($today_expense, 2); ?></p>
        </div>

        <div class="card <?php echo ($net_profit >= 0) ? 'profit' : 'loss'; ?>">
            <h3><i class="fa-solid fa-hand-holding-dollar"></i> NET PROFIT</h3>
            <p>₹<?php echo number_format($net_profit, 2); ?></p>
        </div>
        <div class="card">
            <h3><i class="fa-solid fa-boxes-stacked"></i> TOTAL STOCK</h3>
            <p><?php echo $total_stock; ?> Units</p>
        </div>
    </div>

    <div class="section-title">
        <div class="title-text"><i class="fa-solid fa-triangle-exclamation"></i> Live Stock Alert (Godown Status)</div>
        <button class="toggle-section-btn" onclick="toggleSection('stockTable', this)"><i class="fa-solid fa-eye-slash"></i> Hide</button>
    </div>
    
    <table id="stockTable">
        <thead>
            <tr>
                <th style="width: 40%;">Item Name</th>
                <th style="width: 60%;">Current Status / Remaining Quantity</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stock_alert_res = $conn->query("SELECT item_name, stock_quantity FROM inventory ORDER BY stock_quantity ASC");
            if ($stock_alert_res && $stock_alert_res->num_rows > 0) {
                while($alert_row = $stock_alert_res->fetch_assoc()) {
                    $qty = (int)$alert_row['stock_quantity'];
                    
                    if ($qty <= 5) {
                        $style_class = "class='stock-danger'";
                        $label = "🚨 CRITICAL LOW: " . $qty . " units left! Maal mangwao jaldi!";
                    } elseif ($qty <= 15) {
                        $style_class = "class='stock-warning'";
                        $label = "⚠️ WARNING: " . $qty . " units left. Stock kam ho raha hai.";
                    } else {
                        $style_class = "class='stock-safe'";
                        $label = "✅ SAFE: " . $qty . " units available.";
                    }
                    
                    echo "<tr>
                        <td><b style='font-size: 16px; color: #fff;'>" . htmlspecialchars($alert_row['item_name']) . "</b></td>
                        <td><span {$style_class}>{$label}</span></td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='2' style='text-align:center; color:#777;'>Inventory table khali hai bhau!</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="section-title">
        <div class="title-text"><i class="fa-solid fa-list-check"></i> Aaj Ki Sales Detail</div>
        <button class="toggle-section-btn" onclick="toggleSection('salesTable', this)"><i class="fa-solid fa-eye-slash"></i> Hide</button>
    </div>
    
    <table id="salesTable">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Customer Name</th>
                <th>Items Ordered</th>
                <th style="text-align: center;">Qty</th>
                <th>Amount Paid</th>
                <th>Order Profit</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $orders_res = $conn->query("SELECT * FROM orders WHERE DATE(order_time) = '$today' ORDER BY order_time DESC");
            if ($orders_res && $orders_res->num_rows > 0) {
                while ($row = $orders_res->fetch_assoc()) {
                    
                    $raw_items = $row['item_name']; 
                    $items_array = explode(',', $raw_items);
                    
                    $clean_names_list = [];
                    $qty_list = [];
                    
                    foreach ($items_array as $single_item) {
                        $single_item = trim($single_item);
                        if (!empty($single_item)) {
                            $parts = explode(' x', $single_item);
                            $clean_names_list[] = trim($parts[0]);
                            $qty_list[] = isset($parts[1]) ? trim($parts[1]) : '1';
                        }
                    }
                    
                    $display_items = implode(', ', $clean_names_list);
                    $display_qty = implode(', ', $qty_list);
                    $item_profit = (float)($row['profit'] ?? 0);

                    echo "<tr>";
                    echo "<td>" . date('d-M-Y h:i A', strtotime($row['order_time'])) . "</td>";
                    echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                    echo "<td style='color:#ff9800; font-weight: 500;'>" . htmlspecialchars($display_items) . "</td>";
                    echo "<td style='text-align: center; font-weight: bold; color: #ff9800;'>" . htmlspecialchars($display_qty) . "</td>";
                    echo "<td>" . "₹" . number_format($row['price'], 2) . "</td>";
                    
                    if ($item_profit > 0) {
                        echo "<td><span class='profit-badge'>▲ ₹" . number_format($item_profit, 2) . "</span></td>";
                    } else {
                        echo "<td><span class='profit-zero'>₹" . number_format($item_profit, 2) . "</span></td>";
                    }
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center; color:#777;'>Aaj abhi tak koi order nahi hua hai.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div id="settingsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeSettingsBtn">&times;</span>
            <h3><i class="fa-solid fa-user-shield"></i> Malik Settings</h3>
            <p style="font-size: 12px; color: #aaa; margin-bottom: 20px;">Yahan se admin ka login ID aur Password badle</p>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Naya Username (Login ID)</label>
                    <input type="text" name="new_username" placeholder="E.g. admin_apna" required>
                </div>
                <div class="form-group">
                    <label>Naya Password</label>
                    <input type="password" name="new_password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="update_settings" class="save-settings-btn">Save Changes</button>
            </form>
        </div>
    </div>

    <div id="expenseModal" class="modal">
        <div class="modal-content wide">
            <span class="close-modal" id="closeExpenseBtn">&times;</span>
            <h3><i class="fa-solid fa-receipt"></i> Aaj Ka Kharcha Details</h3>
            <p style="font-size: 12px; color: #aaa; margin-bottom: 10px;">Billing counter se dale gaye saare kharche ki list.</p>
            
            <table style="width: 100%; margin-top: 10px; font-size: 14px;">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Kiske Liye / Details</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($expense_list_res && $expense_list_res->num_rows > 0) {
                        while ($ex_row = $expense_list_res->fetch_assoc()) {
                            $reason = isset($ex_row['expense_name']) ? $ex_row['expense_name'] : 'Koi detail nahi';
                            echo "<tr>";
                            echo "<td>" . date('h:i A', strtotime($ex_row['expense_date'])) . "</td>";
                            echo "<td>" . htmlspecialchars($reason) . "</td>";
                            echo "<td style='color: #f44336; font-weight: bold;'>₹" . number_format($ex_row['amount'], 2) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center; color:#aaa; padding: 20px;'>Aaj dukan me koi kharcha nahi hua hai. ✅</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="monthlyModal" class="modal">
        <div class="modal-content green-border">
            <span class="close-modal" id="closeMonthlyBtn">&times;</span>
            <h3><i class="fa-solid fa-calendar-days"></i> Is Mahine Ki Sales Report</h3>
            <p style="font-size: 12px; color: #aaa; margin-bottom: 10px;">Yahan aapko har din ki total sale aur profit dikhega.</p>
            
            <table style="width: 100%; margin-top: 10px; font-size: 14px;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Sale</th>
                        <th>Total Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($monthly_sales_res && $monthly_sales_res->num_rows > 0) {
                        $total_month_sale = 0;
                        $total_month_profit = 0;
                        
                        while ($m_row = $monthly_sales_res->fetch_assoc()) {
                            $total_month_sale += $m_row['daily_total'];
                            $total_month_profit += $m_row['daily_profit'];
                            
                            echo "<tr>";
                            echo "<td>" . date('d-M-Y', strtotime($m_row['sale_date'])) . "</td>";
                            echo "<td style='color: #ff9800; font-weight: bold;'>₹" . number_format($m_row['daily_total'], 2) . "</td>";
                            echo "<td style='color: #4caf50; font-weight: bold;'>₹" . number_format($m_row['daily_profit'], 2) . "</td>";
                            echo "</tr>";
                        }
                        // Total ki line sabse niche
                        echo "<tr style='background-color: #333;'>";
                        echo "<td style='color: #fff;'><b>Poore Mahine Ka Total:</b></td>";
                        echo "<td style='color: #ff9800; font-weight: bold; font-size: 16px;'>₹" . number_format($total_month_sale, 2) . "</td>";
                        echo "<td style='color: #4caf50; font-weight: bold; font-size: 16px;'>₹" . number_format($total_month_profit, 2) . "</td>";
                        echo "</tr>";
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center; color:#aaa; padding: 20px;'>Is mahine abhi tak koi sale nahi hui hai.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // 1. 🌓 Dark / Light Mode Handle
    const modeToggle = document.getElementById('modeToggle');
    if (localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-mode');
        modeToggle.innerHTML = '<i class="fa-solid fa-sun"></i> Dark Mode';
    }
    modeToggle.addEventListener('click', () => {
        document.body.classList.toggle('light-mode');
        if (document.body.classList.contains('light-mode')) {
            localStorage.setItem('theme', 'light');
            modeToggle.innerHTML = '<i class="fa-solid fa-sun"></i> Dark Mode';
        } else {
            localStorage.setItem('theme', 'dark');
            modeToggle.innerHTML = '<i class="fa-solid fa-moon"></i> Light Mode';
        }
    });

    // 2. 👁️ Hide / Show Sections Handle
    function toggleSection(tableId, button) {
        const table = document.getElementById(tableId);
        const currentDisplay = window.getComputedStyle(table).display;
        if (currentDisplay === "none") {
            table.style.display = "table";
            button.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Hide';
        } else {
            table.style.display = "none";
            button.innerHTML = '<i class="fa-solid fa-eye"></i> Show';
        }
    }

    // 3. Modals ke Variables Setup
    const settingsModal = document.getElementById('settingsModal');
    const openSettingsBtn = document.getElementById('openSettingsBtn');
    const closeSettingsBtn = document.getElementById('closeSettingsBtn');

    const expenseModal = document.getElementById('expenseModal');
    const openExpenseBtn = document.getElementById('openExpenseModalBtn');
    const closeExpenseBtn = document.getElementById('closeExpenseBtn');

    const monthlyModal = document.getElementById('monthlyModal');
    const openMonthlyBtn = document.getElementById('openMonthlyBtn');
    const closeMonthlyBtn = document.getElementById('closeMonthlyBtn');

    // 4. Modals Open/Close Events
    openSettingsBtn.addEventListener('click', () => { settingsModal.style.display = 'flex'; });
    closeSettingsBtn.addEventListener('click', () => { settingsModal.style.display = 'none'; });

    openExpenseBtn.addEventListener('click', () => { expenseModal.style.display = 'flex'; });
    closeExpenseBtn.addEventListener('click', () => { expenseModal.style.display = 'none'; });

    openMonthlyBtn.addEventListener('click', () => { monthlyModal.style.display = 'flex'; });
    closeMonthlyBtn.addEventListener('click', () => { monthlyModal.style.display = 'none'; });

    // Bahar click karne par koi bhi khula modal band ho jaye
    window.addEventListener('click', (e) => {
        if (e.target === settingsModal) { settingsModal.style.display = 'none'; }
        if (e.target === expenseModal) { expenseModal.style.display = 'none'; }
        if (e.target === monthlyModal) { monthlyModal.style.display = 'none'; }
    });
    </script>
</body>
</html>
