<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    header("Location: history.php");
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Kiểm tra đơn hàng có thuộc về người dùng không
$order_sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: history.php");
    exit();
}

// Lấy chi tiết đơn hàng
$details_sql = "SELECT od.*, d.ten, d.hinhanh FROM order_details od JOIN dienthoai d ON od.product_id = d.id WHERE od.order_id = ?";
$stmt = $conn->prepare($details_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$details = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Đơn Hàng #<?php echo $order_id; ?></title>
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
        .order-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            border: 1px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        .order-info h2, .order-details h2 {
            margin-top: 0;
            color: #FFD700;
            font-size: 24px;
            font-weight: 600;
            text-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
        }
        .order-info p {
            margin: 15px 0;
            font-size: 16px;
            color: #ddd;
        }
        .order-info p strong {
            color: #FFD700;
            font-weight: 600;
        }
        .order-details {
            margin-bottom: 30px;
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
        td img {
            max-width: 100px;
            height: auto;
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            transition: transform 0.3s ease;
        }
        td img:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
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
            <h1>Chi tiết Đơn Hàng #<?php echo $order_id; ?></h1>
            <a href="history.php" class="btn">Quay về</a>
        </div>
    </header>

    <main class="container">
        <div class="order-info">
            <h2>Thông tin Đơn Hàng</h2>
            <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount'], 0, ',', '.'); ?> VNĐ</p>
            <p><strong>Địa chỉ giao hàng:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
            <p><strong>Phương thức thanh toán:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
            <p><strong>Trạng thái:</strong> 
                <span class="status <?php
                    if ($order['status'] == 'Đã giao') echo 'status-delivered';
                    elseif ($order['status'] == 'Đang xử lý') echo 'status-processing';
                    else echo 'status-cancelled';
                ?>">
                    <?php echo htmlspecialchars($order['status']); ?>
                </span>
            </p>
            <p><strong>Ngày đặt:</strong> <?php echo $order['created_at']; ?></p>
        </div>

        <div class="order-details">
            <h2>Chi tiết Sản phẩm</h2>
            <table>
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Hình ảnh</th>
                        <th>Số lượng</th>
                        <th>Giá (VNĐ)</th>
                        <th>Tổng (VNĐ)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($detail = $details->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($detail['ten']); ?></td>
                            <td>
                                <img src="images/<?php echo htmlspecialchars($detail['hinhanh']); ?>" 
                                     alt="<?php echo htmlspecialchars($detail['ten']); ?>" 
                                     loading="lazy">
                            </td>
                            <td><?php echo $detail['quantity']; ?></td>
                            <td><?php echo number_format($detail['price'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format($detail['price'] * $detail['quantity'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© 2025 Shop Bán Điện Thoại. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>