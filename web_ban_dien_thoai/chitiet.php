<?php
include 'db.php';
session_start();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM dienthoai WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $phone = $result->fetch_assoc();
    $stmt->close();

    if (!$phone) {
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}

// Xử lý đánh giá
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    $status = 'pending';

    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "Số sao phải từ 1 đến 5!";
        header("Location: chitiet.php?id=$id");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO danhgia (product_id, user_id, rating, comment, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $id, $user_id, $rating, $comment, $status);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success'] = "Đánh giá của bạn đã được gửi và đang chờ duyệt!";
    header("Location: chitiet.php?id=$id");
    exit();
}

// Lấy danh sách đánh giá đã được duyệt
$review_sql = "SELECT d.*, u.username 
               FROM danhgia d 
               JOIN users u ON d.user_id = u.id 
               WHERE d.product_id = ? AND d.status = 'approved' 
               ORDER BY d.created_at DESC";
$stmt = $conn->prepare($review_sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$reviews = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Chi tiết sản phẩm <?php echo htmlspecialchars($phone['ten']); ?> tại Shop Điện Thoại">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Sản Phẩm - <?php echo htmlspecialchars($phone['ten']); ?>"></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #1a1a1a 0%, #2c2d2c 100%);
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
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
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
        .cart-icon {
            position: relative;
            display: flex;
            align-items: center;
        }
        .cart-icon a {
            font-size: 24px;
            color: #FFD700;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .cart-icon a:hover {
            color: #DAA520;
        }
        .cart-icon #cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: 600;
        }
        .detail {
            margin: 30px 0;
        }
        .product-detail {
            display: flex;
            gap: 30px;
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            border: 1px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
        }
        .product-image img {
            max-width: 300px;
            width: 100%;
            border-radius: 15px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            transition: transform 0.3s ease;
        }
        .product-image img:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
        }
        .product-info {
            flex: 1;
            color: #ddd;
        }
        .product-info h2 {
            margin: 0 0 15px;
            color: #FFD700;
            font-size: 28px;
            font-weight: 600;
            text-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
        }
        .price-section {
            margin-bottom: 20px;
        }
        .price-section .price {
            font-size: 24px;
            font-weight: 600;
            color: #FFD700;
        }
        .price-section .promo {
            display: inline-block;
            margin-left: 10px;
            padding: 5px 10px;
            background: linear-gradient(90deg, #dc3545, #c82333);
            color: white;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        .specifications h3 {
            color: #FFD700;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            text-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
        }
        .specifications table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        .specifications th, .specifications td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            color: #ddd;
        }
        .specifications th {
            background: linear-gradient(90deg, #FFD700, #DAA520);
            color: #1a1a1a;
            font-weight: 600;
            text-align: left;
            width: 30%;
        }
        .specifications td {
            color: #ddd;
        }
        .description {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            border: 1px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
        }
        .description h3 {
            color: #FFD700;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            text-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
        }
        .description p {
            margin: 0;
            color: #ddd;
            line-height: 1.6;
        }
        .reviews {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            border: 1px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        .reviews h3 {
            color: #FFD700;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            text-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
        }
        .review-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }
        .review-form textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            background: rgba(255, 255, 255, 0.1);
            color: #ddd;
            font-size: 16px;
            resize: vertical;
            min-height: 100px;
            transition: border 0.3s ease;
        }
        .review-form textarea:focus {
            outline: none;
            border: 1px solid #FFD700;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        .review-form textarea::placeholder {
            color: #888;
        }
        .review-form select {
            width: 200px;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            background: rgba(255, 255, 255, 0.1);
            color: #ddd;
            font-size: 16px;
            transition: border 0.3s ease;
        }
        .review-form select:focus {
            outline: none;
            border: 1px solid #FFD700;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        .review-form select option {
            background: #1a1a1a;
            color: #ddd;
        }
        .review-item {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
        }
        .review-item:last-child {
            border-bottom: none;
        }
        .review-item p {
            margin: 5px 0;
            color: #ddd;
        }
        .review-item strong {
            color: #FFD700;
            font-weight: 600;
        }
        .review-item .rating {
            color: #FFD700;
            font-size: 16px;
        }
        .review-item small {
            color: #888;
            font-size: 12px;
        }
        .reviews a {
            color: #FFD700;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .reviews a:hover {
            color: #DAA520;
        }
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(90deg, #28a745, #218838);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .toast.show {
            opacity: 1;
            visibility: visible;
        }
        .shake {
            animation: shake 0.5s;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
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
        @media (max-width: 768px) {
            .product-detail {
                flex-direction: column;
                align-items: center;
            }
            .product-image img {
                max-width: 100%;
            }
            .product-info h2 {
                font-size: 24px;
            }
            .price-section .price {
                font-size: 20px;
            }
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            .action-buttons .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-flex">
            <h1>Chi Tiết Sản Phẩm</h1>
            <div class="header-actions">
                <a href="index.php" class="btn">Quay về</a>
                <div class="cart-icon">
                    <a href="cart.php">🛒</a>
                    <span id="cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container detail">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="toast show"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="toast" style="background: linear-gradient(90deg, #dc3545, #c82333);"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="product-detail">
            <div class="product-image">
                <img src="images/<?php echo htmlspecialchars($phone['hinhanh']); ?>" alt="<?php echo htmlspecialchars($phone['ten']); ?>">
            </div>

            <div class="product-info">
                <h2><?php echo htmlspecialchars($phone['ten']); ?></h2>
                <div class="price-section">
                    <span class="price"><?php echo number_format($phone['gia'], 0, ',', '.'); ?>₫</span>
                    <?php if (isset($phone['khuyen_mai']) && $phone['khuyen_mai']): ?>
                        <span class="promo"><?php echo htmlspecialchars($phone['khuyen_mai']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="action-buttons">
                    <a href="cart.php?action=add&id=<?php echo $phone['id']; ?>" class="btn btn-add-cart" onclick="addToCart(event, <?php echo $phone['id']; ?>)">Thêm vào giỏ hàng</a>
                    <a href="checkout.php" class="btn btn-buy">Mua Ngay</a>
                </div>

                <div class="specifications">
                    <h3>Thông số kỹ thuật</h3>
                    <table>
                        <tr>
                            <th>Màn hình</th>
                            <td><?php echo htmlspecialchars($phone['man_hinh'] ?? 'Không có thông tin'); ?></td>
                        </tr>
                        <tr>
                            <th>Camera</th>
                            <td><?php echo htmlspecialchars($phone['camera'] ?? 'Không có thông tin'); ?></td>
                        </tr>
                        <tr>
                            <th>RAM</th>
                            <td><?php echo htmlspecialchars($phone['ram'] ?? 'Không có thông tin'); ?></td>
                        </tr>
                        <tr>
                            <th>Bộ nhớ trong</th>
                            <td><?php echo htmlspecialchars($phone['rom'] ?? 'Không có thông tin'); ?></td>
                        </tr>
                        <tr>
                            <th>Pin</th>
                            <td><?php echo htmlspecialchars($phone['pin'] ?? 'Không có thông tin'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="description">
            <h3>Mô tả sản phẩm</h3>
            <p><?php echo htmlspecialchars($phone['mota'] ?? 'Không có mô tả.'); ?></p>
        </div>

        <!-- Phần đánh giá -->
        <div class="reviews">
            <h3>Đánh giá sản phẩm</h3>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form class="review-form" method="POST" action="">
                    <textarea name="comment" placeholder="Viết đánh giá của bạn..." required></textarea>
                    <select name="rating" required>
                        <option value="">Chọn số sao</option>
                        <option value="5">5 sao</option>
                        <option value="4">4 sao</option>
                        <option value="3">3 sao</option>
                        <option value="2">2 sao</option>
                        <option value="1">1 sao</option>
                    </select>
                    <button type="submit" class="btn">Gửi đánh giá</button>
                </form>
            <?php else: ?>
                <p>Vui lòng <a href="auth/login.php">đăng nhập</a> để viết đánh giá.</p>
            <?php endif; ?>

            <?php if ($reviews->num_rows > 0): ?>
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div class="review-item">
                        <p><strong><?php echo htmlspecialchars($review['username']); ?></strong> - 
                           <span class="rating"><?php echo str_repeat('★', $review['rating']); ?></span></p>
                        <p><?php echo htmlspecialchars($review['comment']); ?></p>
                        <p><small><?php echo $review['created_at']; ?></small></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Chưa có đánh giá nào cho sản phẩm này.</p>
            <?php endif; ?>
        </div>

        <div id="toast" class="toast">
            Đã thêm vào giỏ hàng!
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>© 2025 Shop Bán Điện Thoại. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function addToCart(event, productId) {
            event.preventDefault();
            const btn = event.target;
            btn.classList.add('shake');
            const toast = document.getElementById('toast');
            toast.classList.add('show');
            const cartCount = document.getElementById('cart-count');
            let currentCount = parseInt(cartCount.textContent) || 0;
            cartCount.textContent = currentCount + 1;
            setTimeout(() => {
                toast.classList.remove('show');
            }, 2000);
            setTimeout(() => {
                window.location.href = btn.href;
            }, 500);
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>