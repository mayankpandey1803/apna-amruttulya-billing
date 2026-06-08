
<?php
session_name("AMRUTTULYA_SESS");
session_start();

// Login check - agar login nahi hai toh login.php pe bhejo
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Business Day Logic (5 AM reset)
date_default_timezone_set('Asia/Kolkata');
$current_hour = (int)date('G');
$business_day = ($current_hour < 5) ? date('Y-m-d', strtotime('-1 day')) : date('Y-m-d');

// Actions Handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_stock'])) {
        $stmt = $conn->prepare("UPDATE inventory SET stock_quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $_POST['new_qty'], $_POST['item_id']);
        $stmt->execute();
        header("Location: dashboard.php"); exit();
    }
    if (isset($_POST['add_expense'])) {
        $stmt = $conn->prepare("INSERT INTO expenses (amount, expense_name, expense_date) VALUES (?, ?, ?)");
        $stmt->bind_param("dss", $_POST['amount'], $_POST['reason'], $business_day);
        $stmt->execute();
        header("Location: dashboard.php"); exit();
    }
}

// Data Calculations
$sales = $conn->query("SELECT SUM(price) as total FROM orders WHERE DATE(order_time) = '$business_day'")->fetch_assoc()['total'] ?? 0;
$expense = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE expense_date = '$business_day'")->fetch_assoc()['total'] ?? 0;
$stock = $conn->query("SELECT SUM(stock_quantity) as total FROM inventory")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Malik Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #121212; color: #fff; font-family: sans-serif; margin: 0; padding: 20px; }
        .nav { display: flex; justify-content: space-between; border-bottom: 2px solid #ff9800; padding-bottom: 10px; margin-bottom: 20px; }
        .nav a { color: #ff9800; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .card { background: #1e1e1e; padding: 15px; border-radius: 10px; border: 1px solid #333; text-align: center; }
        .card p { font-size: 22px; font-weight: bold; color: #ff9800; margin: 5px 0 0; }
        .box { background: #1e1e1e; padding: 20px; border-radius: 10px; border: 1px solid #333; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #333; }
        input { padding: 8px; border-radius: 5px; border: 1px solid #444; background: #222; color: white; width: 80px; }
        button { background: #ff9800; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; color: black; font-weight: bold; }
    </style>
</head>
<body>

<div class="nav">
    <h1><i class="fa-solid fa-crown"></i> Malik Dashboard</h1>
    <div>
        <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
        <a href="logout.php" style="color: #ff5252;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<div class="grid">
    <div class="card"><h3>Sale</h3><p>₹<?php echo number_format($sales, 2); ?></p></div>
    <div class="card"><h3>Kharcha</h3><p>₹<?php echo number_format($expense, 2); ?></p></div>
    <div class="card"><h3>Stock</h3><p><?php echo $stock; ?> Units</p></div>
    <div class="card"><h3>Status</h3><p>Online</p></div>
</div>

<div class="box">
    <h3>Live Stock Status</h3>
    <table>
        <tr><th>Item</th><th>Qty</th><th>Action</th></tr>
        <?php
        $res = $conn->query("SELECT * FROM inventory");
        while($row = $res->fetch_assoc()) {
            echo "<tr><td>{$row['item_name']}</td><td>{$row['stock_quantity']}</td>
            <td><form method='POST'><input type='hidden' name='item_id' value='{$row['id']}'><input type='number' name='new_qty' required placeholder='Qty'><button type='submit' name='update_stock'>Set</button></form></td></tr>";
        }
        ?>
    </table>
</div>

<div class="box">
    <h3>Add Kharcha</h3>
    <form method="POST">
        <input type="number" name="amount" placeholder="₹" required>
        <input type="text" name="reason" placeholder="Wajah?" required>
        <button type="submit" name="add_expense">Add</button>
    </form>
</div>

</body>
</html>
