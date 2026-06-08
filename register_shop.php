<?php
$conn = new mysqli("localhost", "root", "", "apna_amruttulya");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_name = $conn->real_escape_string($_POST['shop_name']);
    $owner_name = $conn->real_escape_string($_POST['owner_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']); // Real app me password_hash use karenge

    // 1. Shop create karo
    $sql_shop = "INSERT INTO shops (shop_name, owner_name, phone) VALUES ('$shop_name', '$owner_name', '$phone')";
    if ($conn->query($sql_shop)) {
        $shop_id = $conn->insert_id;

        // 2. Us shop ka Malik (Admin) account banao
        $sql_user = "INSERT INTO users (shop_id, username, password, role) VALUES ($shop_id, '$username', '$password', 'admin')";
        $conn->query($sql_user);

        // 3. Test ke liye default inventory item dal do
        $conn->query("INSERT INTO inventory (shop_id, item_name, stock_quantity) VALUES ($shop_id, 'GUD CHAI', 90)");

        echo "<script>alert('Dukan Kamyabi se Register ho gayi! Ab Login karein.'); window.location.href='login.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error: Phone number pehle se registered hai!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register Your Shop - Apna SaaS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #121212; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .reg-box { background: #1e1e1e; padding: 30px; border-radius: 12px; border: 1px solid #ff9800; width: 350px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); }
        h2 { color: #ff9800; margin-top: 0; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; background: #151515; color: white; border: 1px solid #2d2d2d; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #ff9800; border: none; color: black; font-weight: bold; border-radius: 6px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #e68a00; }
    </style>
</head>
<body>
<div class="reg-box">
    <h2>🚀 Start Your Shop App</h2>
    <form method="POST" action="">
        <input type="text" name="shop_name" placeholder="Dukan Ka Naam (e.g. Laxmi Amruttulya)" required>
        <input type="text" name="owner_name" placeholder="Malik Ka Naam" required>
        <input type="text" name="phone" placeholder="Mobile Number" required>
        <hr style="border-color: #2d2d2d; margin: 15px 0;">
        <input type="text" name="username" placeholder="Naya Admin Username" required>
        <input type="password" name="password" placeholder="Naya Admin Password" required>
        <button type="submit">CREATE SHOP & ACCOUNT</button>
    </form>
</div>
</body>
</html>
