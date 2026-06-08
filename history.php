<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "apna_amruttulya");

// Connection check
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales History</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            /* Background Logo Styling */
            background-image: url('logo.jpg');
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            background-size: 300px;
            background-color: rgba(244, 244, 244, 0.95);
            background-blend-mode: lighten;
        }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #8B4513; color: white; }
    </style>
</head>
<body>

<?php
echo "<h2>Sales History</h2>";

// Filter form
echo '<form method="GET" action="">
        Date se filter karein: <input type="date" name="filter_date">
        <button type="submit">Filter</button>
        <a href="history.php">Reset</a>
      </form><br>';

// SQL Query logic
$sql = "SELECT * FROM orders";
if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
    $selected_date = $_GET['filter_date'];
    $sql .= " WHERE DATE(order_time) = '$selected_date'";
}
$sql .= " ORDER BY order_time DESC";

$result = $conn->query($sql);

// Table display
echo "<table>
        <tr>
            <th>ID</th>
            <th>Item Name</th>
            <th>Price</th>
            <th>Order Time</th>
        </tr>";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>".$row['id']."</td>
                <td>".$row['item_name']."</td>
                <td>".$row['price']."</td>
                <td>".$row['order_time']."</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='4'>Koi data nahi mila</td></tr>";
}

echo "</table>";
echo "<br><a href='dashboard.php'>Back to Dashboard</a>";

$conn->close();
?>
</body>
</html>
