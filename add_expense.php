<?php
// Naye online database connection file ko jod diya
include 'db.php';

// Agar user login nahi hai toh security ke liye login page par bhejo
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if (isset($_POST['submit'])) {
    $amount = (float)$_POST['amount'];
    // SQL Injection se bachne ke liye safe string kiya hai
    $reason = $conn->real_escape_string($_POST['reason']);
    
    // Custom business day logic ke hisab se current date nikalna (Agar billing.php se match karna ho)
    $current_hour = (int)date('G');
    if ($current_hour < 5) {
        $business_day = date('Y-m-d', strtotime('-1 day'));
    } else {
        $business_day = date('Y-m-d');
    }

    // Database mein entry insert kar rahe hain (Table structure ke hisab se 'expense_name' aur 'expense_date' use kiya hai jo billing mein tha)
    $sql = "INSERT INTO expenses (amount, expense_name, expense_date) VALUES ($amount, '$reason', '$business_day')";
    
    if ($conn->query($sql) === TRUE) {
        $message = "<p style='color: #4caf50; font-weight: bold;'>Kharcha kamyabi se add ho gaya, bhau!</p>";
    } else {
        $message = "<p style='color: #ff5252;'>Error: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Kharcha - Apna Amruttulya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; background: #1e1e1e; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .expense-box { background: #2d2d2d; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); width: 320px; text-align: center; border: 1px solid #ff9800; }
        h3 { color: #ff9800; margin-top: 0; margin-bottom: 20px; }
        input { width: 90%; padding: 12px; margin: 10px 0; border: 1px solid #444; border-radius: 8px; background: #222; color: white; font-size: 14px; }
        button { width: 98%; padding: 12px; background: #ff5252; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; margin-top: 10px; transition: 0.2s; }
        button:hover { background: #e04040; }
        .back-link { display: inline-block; margin-top: 15px; color: #ff9800; text-decoration: none; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="expense-box">
    <h3><i class="fa-solid fa-hand-holding-dollar"></i> Daily Kharcha Entry</h3>
    
    <?php if (!empty($message)) { echo $message; } ?>

    <form method="POST" action="">
        <input type="number" name="amount" placeholder="Kharcha Amount (₹)" step="0.01" required autocomplete="off">
        <input type="text" name="reason" placeholder="Wajah? (e.g. Doodh, Patti)" required autocomplete="off">
        <button type="submit" name="submit">Add Kharcha</button>
    </form>
    
    <a href="billing.php" class="back-link">← Back to Billing</a>
</div>

</body>
</html>
