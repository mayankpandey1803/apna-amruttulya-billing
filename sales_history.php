<?php
$conn = new mysqli("localhost", "root", "", "apna_amruttulya");
// Yahan humne DESC lagaya hai taaki naya order upar dikhe
$orders = $conn->query("SELECT * FROM orders ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sales History</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <h1>Sales History</h1>
    <a href="dashboard.php">Back to Dashboard</a>
    <table border="1">
        <tr><th>ID</th><th>Items</th><th>Amount</th><th>Time</th></tr>
        <?php while($row = $orders->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['item_name']; ?></td>
                <td>₹<?php echo $row['price']; ?></td>
                <td><?php echo $row['order_time']; ?></td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
