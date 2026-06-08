<?php
// Naye online database connection file ko jod diya
include 'db.php';

// Frontend se data lene ke liye
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // SQL Injection se bachne ke liye real_escape_string lagaya hai
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $price = $conn->real_escape_string($_POST['price']);

    // 1. Pehle check karo ki stock kitna bacha hai
    $check_stock = $conn->query("SELECT stock_quantity FROM inventory WHERE item_name = '$item_name'");
    $stock_row = $check_stock->fetch_assoc();

    // 2. Sirf tabhi process karo agar stock 0 se zyada ho
    if ($stock_row && $stock_row['stock_quantity'] > 0) {
        
        // Order save karo
        $sql = "INSERT INTO orders (item_name, price) VALUES ('$item_name', '$price')";
        
        if ($conn->query($sql) === TRUE) {
            // Stock kam karo
            $update_stock = "UPDATE inventory SET stock_quantity = stock_quantity - 1 WHERE item_name = '$item_name'";
            $conn->query($update_stock);
            echo "Success";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } else {
        // Agar stock 0 hai toh user ko alert do
        echo "Out of Stock!";
    }
}

// Connection close karne ki zaroorat nahi hai, script khatam hote hi apne aap band ho jata hai
?>
