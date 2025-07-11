<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn hàng
$order_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đơn Hàng</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            border: 1px solid rgba(255, 215, 0, 0.3);
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
        td[colspan="7"] {
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #ddd;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            box-shadow: inset 0 0 10px rgba(255, 215, 0, 0.2);
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
            <h1>Lịch Sử Đơn Hàng</h1>
            <a href="index.php" class="btn">Quay về</a>
        </div>
    </header>

    <main class="container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tổng tiền (VNĐ)</th>
                    <th>Địa chỉ giao hàng</th>
                    <th>Phương thức thanh toán</th>
                    <th>Trạng thái</th>
                    <th>Ngày đặt</th>
                    <th>Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders->num_rows > 0): ?>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($order['shipping_address']); ?></td>
                            <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                            <td>
                                <span class="status <?php
                                    if ($order['status'] == 'Đã giao') echo 'status-delivered';
                                    elseif ($order['status'] == 'Đang xử lý') echo 'status-processing';
                                    else echo 'status-cancelled';
                                ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $order['created_at']; ?></td>
                            <td><a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="btn">Xem chi tiết</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Bạn chưa có đơn hàng nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© 2025 Shop Bán Điện Thoại. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>