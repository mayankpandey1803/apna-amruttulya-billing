<?php
// ---- 1. SERVER SESSION SETTINGS (DASHBOARD WALI) ----
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_name("AMRUTTULYA_SESS");
// ⚠️ XAMPP wala session path hata diya gaya hai live server ke liye
session_start();

// Security Check: Ya toh role 'admin' ho, ya 'godown' ho, tabhi khulega
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'godown')) {
    header("Location: login.php?error=unauthorized");
    exit();
}

// 🎯 Live Server Database Connection
$conn = new mysqli("sql202.infinityfree.com", "if0_42114637", "Mayank1803", "if0_42114637_apna_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Stock Update karne ka logic
if(isset($_POST['update_stock'])) {
    $id = (int)$_POST['item_id'];
    $new_qty = (int)$_POST['new_qty'];
    $conn->query("UPDATE inventory SET stock_quantity = $new_qty WHERE id = $id");
    
    // Page wapas yahi refresh hoga update hone ke baad
    header("Location: godown.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Godown Inventory Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #1e1e1e; padding: 20px; color: white; margin: 0; }
        
        .header-bar { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ff9800; padding-bottom: 10px; margin-bottom: 20px; }
        
        /* Nav Links in Godown (Admin ke liye wapas jane ka raasta) */
        .nav-links { display: flex; align-items: center; gap: 15px; }
        .back-btn { background: #333; color: #ff9800; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; border: 1px solid #ff9800; }
        .back-btn:hover { background: #ff9800; color: #121212; }
        .logout-btn { background: #ff5252; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .logout-btn:hover { background: #e50914; }
        
        .box { background: #2d2d2d; padding: 20px; border-radius: 10px; border: 1px solid #444; width: 95%; max-width: 800px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #222; }
        th, td { border: 1px solid #444; padding: 12px; text-align: left; color: #fff; }
        th { background: #333; color: #ff9800; }
        input[type="number"] { background: #222; color: white; border: 1px solid #444; padding: 8px; border-radius: 5px; width: 80px; }
        button { background: #ff9800; color: black; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #e68a00; }
    </style>
</head>
<body>

    <div class="header-bar">
        <h1>📦 Manage Stock / Godown Inventory</h1>
        <div class="nav-links">
            <?php 
            // Agar Admin hai toh wapas jane ka button dikhao
            if($_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Wapas Dashboard</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <div class="box">
        <h3>Live Stock Status</h3>
        <p style="color: #aaa; font-size: 14px;">Yahan jo bhi stock badhaoge ya ghataoge, system mein turant live update ho jayega.</p>
        <table>
            <tr>
                <th>Item Name</th>
                <th>Current Quantity (In Stock)</th>
                <th>New Quantity Set Karo</th>
            </tr>
            <?php
            $res = $conn->query("SELECT id, item_name, stock_quantity FROM inventory");
            if ($res && $res->num_rows > 0) {
                while($row = $res->fetch_assoc()) {
                    echo "<tr>
                        <td><b>" . htmlspecialchars($row['item_name']) . "</b></td>
                        <td style='color: #ff9800; font-weight: bold; font-size: 16px;'>{$row['stock_quantity']} units</td>
                        <td>
                            <form method='POST' action='' style='display: flex; gap: 10px; align-items: center;'>
                                <input type='hidden' name='item_id' value='{$row['id']}'>
                                <input type='number' name='new_qty' placeholder='Qty' required min='0' value='{$row['stock_quantity']}'> 
                                <button type='submit' name='update_stock'><i class='fa-solid fa-check'></i> Update</button>
                            </form>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='3' style='text-align: center; color: #aaa;'>Inventory mein koi maal nahi mila, bhai.</td></tr>";
            }
            ?>
        </table>
    </div>

</body>
</html>
