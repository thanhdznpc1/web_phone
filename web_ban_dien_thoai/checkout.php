<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $shipping_address = trim($_POST['shipping_address']);
    $payment_method = trim($_POST['payment_method']);
    $total_amount = 0;

    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['gia'] * $item['quantity'];
    }

    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $user_id, $total_amount, $shipping_address, $payment_method);
    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['gia']);
            $stmt->execute();
        }
        unset($_SESSION['cart']);
        $message = "Đặt hàng thành công! Cảm ơn bạn đã mua sắm.";
    } else {
        $message = "Đặt hàng thất bại, vui lòng thử lại!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%);
            color: #fff;
        }
        .header {
            background: linear-gradient(90deg, #1a1a1a, #333);
            padding: 20px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            border-bottom: 2px solid #FFD700;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #FFD700;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        .btn {
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            color: #1a1a1a;
            background: linear-gradient(90deg, #FFD700, #DAA520);
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        .btn:hover {
            background: linear-gradient(90deg, #DAA520, #FFD700);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.8);
            transform: translateY(-2px);
        }
        main {
            margin: 30px 0;
        }
        .checkout-form {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            border: 1px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
            max-width: 600px;
            margin: 0 auto;
        }
        .checkout-form form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .checkout-form input,
        .checkout-form select {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            background: rgba(255, 255, 255, 0.1);
            color: #ddd;
            font-size: 16px;
            transition: border 0.3s ease;
        }
        .checkout-form input:focus,
        .checkout-form select:focus {
            outline: none;
            border: 1px solid #FFD700;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        .checkout-form input::placeholder {
            color: #888;
        }
        .checkout-form select option {
            background: #1a1a1a;
            color: #ddd;
        }
        .checkout-form button {
            padding: 12px;
            border-radius: 25px;
            border: none;
            background: linear-gradient(90deg, #FFD700, #DAA520);
            color: #1a1a1a;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        .checkout-form button:hover {
            background: linear-gradient(90deg, #DAA520, #FFD700);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.8);
            transform: translateY(-2px);
        }
        .no-products {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            color: #ddd;
            font-size: 18px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        .footer {
            background: linear-gradient(90deg, #1a1a1a, #333);
            color: #FFD700;
            text-align: center;
            padding: 20px 0;
            margin-top: 30px;
            border-top: 2px solid #FFD700;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-flex">
            <h1>Thanh Toán</h1>
            <a href="cart.php" class="btn">Quay về</a>
        </div>
    </header>

    <main class="container">
        <div class="checkout-form">
            <?php if ($message): ?>
                <p class="no-products"><?php echo $message; ?></p>
            <?php endif; ?>

            <?php if (!$message): ?>
                <form method="POST" action="">
                    <input type="text" name="shipping_address" placeholder="Địa chỉ giao hàng" required>
                    <select name="payment_method" required>
                        <option value="">Chọn phương thức thanh toán</option>
                        <option value="COD">Thanh toán khi nhận hàng (COD)</option>
                        <option value="Bank Transfer">Chuyển khoản ngân hàng</option>
                    </select>
                    <button type="submit" class="btn">Xác nhận đặt hàng</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© 2025 Shop Bán Điện Thoại. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>