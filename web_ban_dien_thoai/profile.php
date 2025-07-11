<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

$order_sql = "SELECT o.*, od.product_id, od.quantity, od.price, d.ten FROM orders o 
              JOIN order_details od ON o.id = od.order_id 
              JOIN dienthoai d ON od.product_id = d.id 
              WHERE o.user_id = $user_id ORDER BY o.created_at DESC";
$orders = $conn->query($order_sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài Khoản</title>
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
        .profile-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            border: 1px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        .profile-info h2 {
            margin-top: 0;
            color: #FFD700;
            font-size: 24px;
            font-weight: 600;
            text-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
        }
        .profile-info p {
            margin: 15px 0;
            font-size: 16px;
            color: #ddd;
        }
        .profile-info p strong {
            color: #FFD700;
            font-weight: 600;
        }
        .order-history {
            margin: 30px 0;
        }
        .order-history h2 {
            color: #FFD700;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            text-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        .cart-table th, .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            color: #ddd;
        }
        .cart-table th {
            background: linear-gradient(90deg, #FFD700, #DAA520);
            color: #1a1a1a;
            font-weight: 600;
            text-transform: uppercase;
        }
        .cart-table tr {
            transition: background 0.3s ease;
        }
        .cart-table tr:hover {
            background: rgba(255, 215, 0, 0.1);
        }
        .status {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            font-size: 14px;
        }
        .status-delivered {
            background: linear-gradient(90deg, #28a745, #218838);
            color: white;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
        }
        .status-processing {
            background: linear-gradient(90deg, #FFD700, #DAA520);
            color: #1a1a1a;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        .status-cancelled {
            background: linear-gradient(90deg, #dc3545, #c82333);
            color: white;
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
        }
        .no-products {
            text-align: center;
            padding: 30px;
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
            <h1>Tài Khoản</h1>
            <a href="index.php" class="btn">Quay về</a>
        </div>
    </header>

    <main class="container">
        <div class="profile-info">
            <h2>Thông Tin Tài Khoản</h2>
            <p><strong>Tên đăng nhập:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Vai trò:</strong> <?php echo $user['role'] == 1 ? 'Admin' : 'Khách hàng'; ?></p>
        </div>

        <div class="order-history">
            <h2>Lịch Sử Đơn Hàng</h2>
            <?php if ($orders->num_rows > 0): ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Mã đơn hàng</th>
                            <th>Sản phẩm</th>
                            <th>Số lượng</th>
                            <th>Giá</th>
                            <th>Tổng</th>
                            <th>Ngày đặt</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['ten']); ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td><?php echo number_format($order['price'], 0, ',', '.'); ?>₫</td>
                                <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>₫</td>
                                <td><?php echo $order['created_at']; ?></td>
                                <td>
                                    <span class="status <?php
                                        if ($order['status'] == 'Đã giao') echo 'status-delivered';
                                        elseif ($order['status'] == 'Đang xử lý') echo 'status-processing';
                                        else echo 'status-cancelled';
                                    ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-products">Bạn chưa có đơn hàng nào!</p>
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