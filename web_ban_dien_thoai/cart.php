<?php
include 'db.php';
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Xử lý thêm vào giỏ hàng
if (isset($_GET['action']) && $_GET['action'] == 'add' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM dienthoai WHERE id = $id";
    $result = $conn->query($sql);
    $product = $result->fetch_assoc();

    if ($product) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity']++;
        } else {
            $_SESSION['cart'][$id] = [
                'id' => $product['id'],
                'ten' => $product['ten'],
                'hinhanh' => $product['hinhanh'],
                'gia' => $product['gia'],
                'quantity' => 1
            ];
        }
    }
    header("Location: cart.php");
    exit();
}

// Xử lý xóa sản phẩm khỏi giỏ hàng
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
    header("Location: cart.php");
    exit();
}

// Xử lý cập nhật số lượng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    foreach ($_POST['quantity'] as $id => $quantity) {
        $quantity = intval($quantity);
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id]['quantity'] = $quantity;
        }
    }
    header("Location: cart.php");
    exit();
}

$total = 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng</title>
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
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: linear-gradient(90deg, #DAA520, #FFD700);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.8);
            transform: translateY(-2px);
        }
        .btn-buy {
            background: linear-gradient(90deg, #28a745, #218838);
            color: white;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
        }
        .btn-buy:hover {
            background: linear-gradient(90deg, #218838, #28a745);
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.8);
        }
        .btn-danger {
            background: linear-gradient(90deg, #dc3545, #c82333);
            color: white;
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
        }
        .btn-danger:hover {
            background: linear-gradient(90deg, #c82333, #dc3545);
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.8);
        }
        main {
            margin: 30px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            border: 1px solid rgba(255, 215, 0, 0.3);
            margin-bottom: 20px;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            color: #ddd;
        }
        th {
            background: linear-gradient(90deg, #FFD700, #DAA520);
            color: #1a1a1a;
            font-weight: 600;
            text-transform: uppercase;
        }
        tr {
            transition: background 0.3s ease;
        }
        tr:hover {
            background: rgba(255, 215, 0, 0.1);
        }
        td[colspan="6"] {
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #ddd;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            box-shadow: inset 0 0 10px rgba(255, 215, 0, 0.2);
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        input[type="number"] {
            width: 60px;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid rgba(255, 215, 0, 0.5);
            background: rgba(0, 0, 0, 0.3);
            color: white;
            text-align: center;
        }
        .cart-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-top: 20px;
        }
        .total-price {
            font-size: 20px;
            font-weight: 600;
            color: #FFD700;
            margin-right: auto;
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
            <h1>Giỏ Hàng</h1>
            <a href="index.php" class="btn">Quay về</a>
        </div>
    </header>

    <main class="container">
        <form method="POST" action="">
            <table>
                <thead>
                    <tr>
                        <th>Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Tổng</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <?php
                            $subtotal = $item['gia'] * $item['quantity'];
                            $total += $subtotal;
                            ?>
                            <tr>
                                <td><img src="images/<?php echo htmlspecialchars($item['hinhanh']); ?>" alt="<?php echo htmlspecialchars($item['ten']); ?>" class="product-image"></td>
                                <td><?php echo htmlspecialchars($item['ten']); ?></td>
                                <td><?php echo number_format($item['gia'], 0, ',', '.'); ?>₫</td>
                                <td>
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1">
                                </td>
                                <td><?php echo number_format($subtotal, 0, ',', '.'); ?>₫</td>
                                <td>
                                    <a href="cart.php?action=remove&id=<?php echo $item['id']; ?>" class="btn btn-danger">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Giỏ hàng trống!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($_SESSION['cart'])): ?>
                <div class="cart-actions">
                    <span class="total-price">Tổng cộng: <?php echo number_format($total, 0, ',', '.'); ?>₫</span>
                    <button type="submit" name="update" class="btn">Cập nhật giỏ hàng</button>
                    <a href="checkout.php" class="btn btn-buy">Thanh toán</a>
                </div>
            <?php endif; ?>
        </form>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© 2025 Shop Bán Điện Thoại. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>