<?php

// ---- SESSION LOCK FIX ----

session_name("AMRUTTULYA_SESS");

// ⚠️ FIX: Live server pe 'C:/xampp/tmp' nahi hota, isliye ise hata diya gaya hai.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kolkata');

// 🎯 FIX: Nayi Database Connection Details (InfinityFree Live Server)
$conn = new mysqli("sql202.infinityfree.com", "if0_42114637", "Mayank1803", "if0_42114637_apna_db");

if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password']; // Note: Password hashing use karna future mein safe rahega

    $result = $conn->query("SELECT id, shop_id, username, role FROM users WHERE username='$username' AND password='$password'");

    if ($result && $result->num_rows > 0) {

        $user = $result->fetch_assoc();
        
        // --- SESSION FIX ---
        session_regenerate_id(true); // Purani session ID change ki
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['shop_id'] = $user['shop_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // 🔥 FIX: dashboard.php ko badal kar admin_dashboard.php kiya
        if ($user['role'] == 'admin' || $user['username'] == 'malik') {
            header("Location: admin_dashboard.php");
        } elseif ($user['role'] == 'godown' || $user['username'] == 'godown') {
            header("Location: godown.php");
        } else {
            header("Location: billing.php");
        }
        exit();

    } else {
        $error = "Galat Username ya Password, bhai!";
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">
    <title>Login - Apna Amruttulya SaaS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body { font-family: sans-serif; background: #1e1e1e; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #2d2d2d; padding: 40px 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); width: 300px; text-align: center; }
        h2 { margin-bottom: 5px; color: #ff9800; }
        p { color: #aaa; font-size: 14px; margin-top: 0; margin-bottom: 25px; }
        input { width: 90%; padding: 12px; margin: 10px 0; border: 1px solid #444; border-radius: 8px; background: #222; color: white; font-size: 14px; }
        button { width: 98%; padding: 12px; background: #ff9800; color: black; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .error { color: #ff5252; background: rgba(255,82,82,0.1); padding: 10px; border-radius: 5px; font-size: 14px; margin-bottom: 15px; border: 1px solid rgba(255,82,82,0.2); }
        
        /* Forgot Password Link Style */
        .forgot-link-wrapper { margin-top: 20px; text-align: center; }
        .forgot-link { color: #ff9800; text-decoration: none; font-size: 14px; transition: color 0.2s; }
        .forgot-link:hover { color: #e68a00; text-decoration: underline; }
    </style>

</head>

<body>

    <div class="login-box">

        <h2>Apna Amruttulya</h2>
        <p>Premium Multi-Shop Billing System</p>

        <?php if (!empty($error)): ?> <div class="error"><?php echo $error; ?></div> <?php endif; ?>
        
        <form method="POST" action="">

            <input type="text" name="username" placeholder="Username" required autocomplete="off">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">LOGIN</button>
            
            <div class="forgot-link-wrapper">
                <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            </div>

        </form>

    </div>

</body>

</html>
