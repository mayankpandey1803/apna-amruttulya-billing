<?php
session_start();
$conn = new mysqli("localhost", "root", "", "apna_amruttulya");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$my_password = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $pin = $conn->real_escape_string($_POST['pin']);

    // Check kar rahe hain ki Username aur Security PIN sahi hai ya nahi
    $result = $conn->query("SELECT password FROM users WHERE username='$username' AND security_pin='$pin'");

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $my_password = "Tera Password Mil Gaya Bhai: 👉 " . $row['password'] . " 👈";
    } else {
        $error = "Galat Username ya Security PIN, dhyan se check karo!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Apna Amruttulya</title>
    <style>
        body { font-family: sans-serif; background: #1e1e1e; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: #2d2d2d; padding: 35px; border-radius: 12px; width: 300px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        h2 { color: #ff9800; margin-bottom: 20px; }
        input { width: 90%; padding: 12px; margin: 10px 0; border: 1px solid #444; border-radius: 8px; background: #222; color: white; }
        button { width: 98%; padding: 12px; background: #ff9800; color: black; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 15px; }
        .error { color: #ff5252; margin-bottom: 10px; font-size: 14px; }
        .success { color: #4caf50; font-size: 16px; font-weight: bold; margin-bottom: 15px; background: rgba(76,175,80,0.1); padding: 10px; border-radius: 6px; }
        a { color: #aaa; text-decoration: none; font-size: 14px; display: block; margin-top: 15px; }
        a:hover { color: #ff9800; }
    </style>
</head>
<body>

<div class="box">
    <h2>🔒 Recovery Panel</h2>
    
    <?php if(!empty($error)): ?> <div class="error"><?php echo $error; ?></div> <?php endif; ?>
    <?php if(!empty($my_password)): ?> <div class="success"><?php echo $my_password; ?></div> <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="username" placeholder="Apna Username" required>
        <input type="password" name="pin" placeholder="Enter Secret Security PIN" required>
        <button type="submit">RECOVER PASSWORD</button>
    </form>

    <a href="login.php">← Login Page Par Wapas Jao</a>
</div>

</body>
</html>
