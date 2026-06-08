<?php
$conn = new mysqli("localhost", "root", "", "apna_amruttulya");

// Agar update button dabaya gaya
if(isset($_POST['update'])) {
    $item_name = $_POST['item_name'];
    $new_qty = $_POST['quantity'];
    $conn->query("UPDATE inventory SET stock_quantity = stock_quantity + $new_qty WHERE item_name = '$item_name'");
    echo "<script>alert('Stock Updated Successfully!'); window.location='update_stock.php';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Stock</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #fdfaf6; padding: 20px; 
               background-image: url('logo.png'); background-repeat: no-repeat; background-position: center; background-attachment: fixed; background-size: 250px; }
        .box { background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 10px; max-width: 400px; margin: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        select, input, button { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ddd; }
        button { background-color: #8B4513; color: white; cursor: pointer; }
    </style>
</head>
<body>

<div class="box">
    <h2>Update Stock</h2>
    <form method="POST">
        <label>Item Chunein:</label>
        <select name="item_name">
            <?php
            $items = $conn->query("SELECT item_name FROM inventory");
            while($row = $items->fetch_assoc()) {
                echo "<option value='".$row['item_name']."'>".$row['item_name']."</option>";
            }
            ?>
        </select>
        <label>Kitna Stock Add Krna Hai:</label>
        <input type="number" name="quantity" required placeholder="Enter Quantity">
        <button type="submit" name="update">Update Stock</button>
    </form>
    <br>
    <a href="dashboard.php">Dashboard par wapas jayein</a>
</div>

</body>
</html>
