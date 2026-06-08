<?php
$conn = new mysqli("localhost", "root", "", "apna_amruttulya");

if(isset($_POST['add_expense'])) {
    $name = $_POST['expense_name'];
    $amount = $_POST['amount'];
    $date = date("Y-m-d");
    $conn->query("INSERT INTO expenses (expense_name, amount, expense_date) VALUES ('$name', '$amount', '$date')");
    echo "<script>alert('Kharcha Add Ho Gaya!');</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daily Expense</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #fdfaf6; }
        .box { background: white; padding: 20px; border-radius: 10px; max-width: 500px; margin: auto; }
        input, button { width: 100%; padding: 10px; margin: 5px 0; }
        button { background: #d9534f; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
<div class="box">
    <h2>Kharcha Add Karein</h2>
    <form method="POST">
        <input type="text" name="expense_name" placeholder="Kharche ka naam" required>
        <input type="number" name="amount" placeholder="Amount (₹)" required>
        <button type="submit" name="add_expense">Add Expense</button>
    </form>
    <br>
    <a href="dashboard.php">Dashboard par wapas jayein</a>
</div>
</body>
</html>
