<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

// Security Check: Sirf malik hi ye page khol sake
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "apna_amruttulya");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$success = "";
$error = "";
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = $conn->real_escape_string($_POST['new_username']);
    $new_password = $conn->real_escape_string($_POST['new_password']);

    if (!empty($new_username) && !empty($new_password)) {
        // Database mein data update ho raha hai
        $sql = "UPDATE users SET username = '$new_username', password = '$new_password' WHERE id = $user_id";
        if ($conn->query($sql)) {
            $_SESSION['username'] = $new_username; // Session update kiya
            $success = "ID aur Password kamyabi se badal gaya, bhai!";
        } else {
            $error = "Kuch gadbad hui, database update nahi hua.";
        }
    } else {
        $error = "Dono fields bharna zaroori hai!";
    }
}

// Current data nikalne ke liye
$res = $conn->query("SELECT username, password FROM users WHERE id = $user_id");
$current_user = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Credentials - Malik Panel</title>
    <style>
        body { background: #121212; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: #1e1e1e; padding: 30px; border-radius: 12px; border: 1px solid #333; width: 350px; }
        h2 { color: #ff9800; margin-top: 0; text-align: center; }
        input { width: 93%; padding: 12px; margin: 12px 0; background: #2a2a2a; border: 1px solid #444; color: #fff; border-radius: 6px; }
        input:focus { border-color: #ff9800; outline: none; }
        button { width: 100%; padding: 12px; background: #ff9800; color: #000; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        button:hover { background: #e68a00; }
        .msg { padding: 10px; margin-bottom: 15px; border-radius: 6px; text-align: center; font-size: 14px; }
        .success { background: rgba(76,175,80,0.1); color: #4caf50; border: 1px solid #4caf50; }
        .error { background: rgba(244,67,54,0.1); color: #f44336; border: 1px solid #f44336; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #aaa; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: #ff9800; }
    </style>
</head>
<body>

<div class="box">
    <h2>🔑 Change ID & Password</h2>
    
    <?php if(!empty($success)): ?> <div class="msg success"><?php echo $success; ?></div> <?php endif; ?>
    <?php if(!empty($error)): ?> <div class="msg error"><?php echo $error; ?></div> <?php endif; ?>

    <form method="POST" action="">
        <label>Apna Naya Username:</label>
        <input type="text" name="new_username" value="<?php echo htmlspecialchars($current_user['username']); ?>" required>
        
        <label>Apna Naya Password:</label>
        <input type="text" name="new_password" value="<?php echo htmlspecialchars($current_user['password']); ?>" required>
        
        <button type="submit">UPDATE CREDENTIALS</button>
    </form>
    
    <a href="dashboard.php" class="back-link">← Wapas Dashboard Chalo</a>
</div>

</body>
</html>
