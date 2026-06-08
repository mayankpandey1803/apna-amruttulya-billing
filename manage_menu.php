<?php
$conn = new mysqli("localhost", "root", "", "apna_amruttulya");

// Item Add karne ka logic
if(isset($_POST['add_item'])) {
    $name = $_POST['item_name'];
    $price = $_POST['price'];
    $conn->query("INSERT INTO menu (item_name, price) VALUES ('$name', '$price')");
    echo "<script>alert('Item Add Ho Gaya!');</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Menu</title>
    <style>
        body { font-family: sans-serif; background-color: #fdfaf6; padding: 20px; 
               background-image: url('logo.png'); background-repeat: no-repeat; background-position: center; background-attachment: fixed; background-size: 250px; }
        .box { background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 10px; max-width: 400px; margin: auto; }
        input, button { width: 100%; padding: 10px; margin: 5px 0; border-radius: 5px; border: 1px solid #ddd; }
        button { background-color: #8B4513; color: white; cursor: pointer; }
    </style>
</head>
<body>
<div class="box">
    <h2>Menu Manage Karein</h2>
    <form method="POST">
        <input type="text" name="item_name" placeholder="Item ka Naam" required>
        <input type="number" name="price" placeholder="Price" required>
        <button type="submit" name="add_item">Add Item</button>
    </form>
    <hr>
    <h3>Available Items:</h3>
    <?php
    $res = $conn->query("SELECT * FROM menu");
    while($row = $res->fetch_assoc()) {
        echo "<p>".$row['item_name']." - ₹".$row['price']."</p>";
    }
    ?>
    <br>
    <a href="dashboard.php">Dashboard par wapas jayein</a>
</div>
</body>
</html>
