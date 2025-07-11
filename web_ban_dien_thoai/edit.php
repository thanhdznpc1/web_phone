<?php
include 'db.php';
session_start();

// Đảm bảo không có output trước khi gọi header()
ob_start();

// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) { // role = 1 là admin
    header('Location: index.php');
    exit();
}

// Kiểm tra ID sản phẩm từ URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID sản phẩm không hợp lệ!";
    header("Location: admin.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Lấy thông tin sản phẩm để hiển thị trong form
$stmt = $conn->prepare("SELECT * FROM dienthoai WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Sản phẩm không tồn tại!";
    header("Location: admin.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Xử lý cập nhật sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $hinhanh = $product['hinhanh']; // Giữ ảnh cũ nếu không upload ảnh mới

    // Xử lý upload ảnh mới nếu có
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['hinhanh']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_name = uniqid() . '-' . basename($_FILES['hinhanh']['name']);
            $upload_path = 'images/' . $file_name;
            
            if (move_uploaded_file($_FILES['hinhanh']['tmp_name'], $upload_path)) {
                // Xóa ảnh cũ nếu tồn tại
                if (!empty($hinhanh) && file_exists("images/" . $hinhanh)) {
                    unlink("images/" . $hinhanh);
                }
                $hinhanh = $file_name;
            } else {
                $_SESSION['error'] = "Lỗi khi lưu file ảnh";
            }
        } else {
            $_SESSION['error'] = "Chỉ chấp nhận file ảnh JPG, PNG, JPEG";
        }
    }

    if (empty($_SESSION['error'])) {
        $stmt = $conn->prepare("UPDATE dienthoai SET ten = ?, hinhanh = ?, gia = ?, thuong_hieu = ?, man_hinh = ?, camera = ?, ram = ?, rom = ?, pin = ?, mota = ?, khuyen_mai = ? WHERE id = ?");
        $stmt->bind_param("ssissssssssi", 
            $_POST['ten'], 
            $hinhanh,
            $_POST['gia'],
            $_POST['thuong_hieu'],
            $_POST['man_hinh'],
            $_POST['camera'],
            $_POST['ram'],
            $_POST['rom'],
            $_POST['pin'],
            $_POST['mota'],
            $_POST['khuyen_mai'],
            $product_id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Cập nhật sản phẩm thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi cập nhật sản phẩm: " . $conn->error;
        }
        $stmt->close();
        header("Location: admin.php");
        exit();
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa sản phẩm</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #1a1a1a;
            color: #ffffff;
            transition: background-color 0.3s, color 0.3s;
        }
        body.light-mode {
            background-color: #f4f4f9;
            color: #000000;
        }
        .header {
            background-color: #333333;
            color: #ffffff;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        body.light-mode .header {
            background-color: #007bff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .container {
            max-width: 1200px;
            margin: 0;
            padding: 0 20px;
        }
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .auth-links span {
            margin-right: 15px;
        }
        .theme-toggle {
            background-color: #444444;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .theme-toggle:hover {
            background-color: #555555;
        }
        body.light-mode .theme-toggle {
            background-color: #666666;
        }
        body.light-mode .theme-toggle:hover {
            background-color: #555555;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: #ffffff;
            background-color: #444444;
            transition: background-color 0.3s;
            margin-right: 10px;
        }
        .btn:last-child {
            margin-right: 0;
        }
        .btn:hover {
            background-color: #555555;
        }
        body.light-mode .btn {
            background-color: #28a745;
        }
        body.light-mode .btn:hover {
            background-color: #218838;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .form-add {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            background-color: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
        }
        body.light-mode .form-add {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .form-add input,
        .form-add select,
        .form-add textarea {
            flex: 1 1 30%;
            min-width: 250px;
            padding: 10px;
            border: 1px solid #444444;
            border-radius: 4px;
            font-size: 14px;
            background-color: #333333;
            color: #ffffff;
        }
        body.light-mode .form-add input,
        body.light-mode .form-add select,
        body.light-mode .form-add textarea {
            border: 1px solid #ddd;
            background-color: white;
            color: #000000;
        }
        .form-add textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-add button {
            width: 100%;
            padding: 12px;
            background-color: #444444;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .form-add button:hover {
            background-color: #555555;
        }
        body.light-mode .form-add button {
            background-color: #007bff;
        }
        body.light-mode .form-add button:hover {
            background-color: #0056b3;
        }
        .success-message, .error-message {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success-message {
            background-color: #1a3c34;
            color: #a3cfbb;
        }
        body.light-mode .success-message {
            background-color: #d4edda;
            color: #155724;
        }
        .error-message {
            background-color: #5c1a1b;
            color: #f1aeb5;
        }
        body.light-mode .error-message {
            background-color: #f8d7da;
            color: #721c24;
        }
        .footer {
            background-color: #333333;
            color: #ffffff;
            text-align: center;
            padding: 15px 0;
            margin-top: 20px;
        }
        body.light-mode .footer {
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-flex">
                <h1>Chỉnh sửa sản phẩm</h1>
                <nav class="auth-links">
                    <span>Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)</span>
                    <button class="theme-toggle" onclick="toggleTheme()">Dark Mode</button>
                    <a href="admin.php" class="btn">Quay lại</a>
                    <a href="auth/logout.php" class="btn">Đăng xuất</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
        <!-- Form chỉnh sửa sản phẩm -->
        <form class="form-add" method="POST" enctype="multipart/form-data">
            <input type="text" name="ten" placeholder="Tên sản phẩm (ví dụ: iPhone 14)" value="<?php echo htmlspecialchars($product['ten']); ?>" required>
            <input type="file" name="hinhanh" accept="image/jpeg,image/png,image/jpg">
            <input type="number" name="gia" placeholder="Giá (VNĐ, ví dụ: 29990000)" value="<?php echo htmlspecialchars($product['gia']); ?>" required>
            <input type="text" name="thuong_hieu" placeholder="Thương hiệu (ví dụ: Apple)" value="<?php echo htmlspecialchars($product['thuong_hieu']); ?>" required>
            <input type="text" name="man_hinh" placeholder="Màn hình (ví dụ: 6.7 inch)" value="<?php echo htmlspecialchars($product['man_hinh']); ?>" required>
            <input type="text" name="camera" placeholder="Camera (ví dụ: 48MP + 12MP)" value="<?php echo htmlspecialchars($product['camera']); ?>" required>
            <select name="ram" required>
                <option value="" disabled>Chọn RAM</option>
                <option value="4GB" <?php if ($product['ram'] == '4GB') echo 'selected'; ?>>4GB</option>
                <option value="6GB" <?php if ($product['ram'] == '6GB') echo 'selected'; ?>>6GB</option>
                <option value="8GB" <?php if ($product['ram'] == '8GB') echo 'selected'; ?>>8GB</option>
                <option value="12GB" <?php if ($product['ram'] == '12GB') echo 'selected'; ?>>12GB</option>
            </select>
            <select name="rom" required>
                <option value="" disabled>Chọn ROM</option>
                <option value="64GB" <?php if ($product['rom'] == '64GB') echo 'selected'; ?>>64GB</option>
                <option value="128GB" <?php if ($product['rom'] == '128GB') echo 'selected'; ?>>128GB</option>
                <option value="256GB" <?php if ($product['rom'] == '256GB') echo 'selected'; ?>>256GB</option>
                <option value="512GB" <?php if ($product['rom'] == '512GB') echo 'selected'; ?>>512GB</option>
                <option value="1TB" <?php if ($product['rom'] == '1TB') echo 'selected'; ?>>1TB</option>
            </select>
            <input type="text" name="pin" placeholder="Pin (ví dụ: 4323mAh)" value="<?php echo htmlspecialchars($product['pin']); ?>" required>
            <textarea name="mota" placeholder="Mô tả sản phẩm (ví dụ: Thiết kế sang trọng, hiệu năng mạnh mẽ)"><?php echo htmlspecialchars($product['mota']); ?></textarea>
            <input type="text" name="khuyen_mai" placeholder="Khuyến mãi (ví dụ: Giảm giá 5%)" value="<?php echo htmlspecialchars($product['khuyen_mai']); ?>">
            <button type="submit" name="update_product">Cập nhật sản phẩm</button>
        </form>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© 2025 Shop Bán Điện Thoại</p>
        </div>
    </footer>

    <script>
        // Kiểm tra trạng thái theme từ localStorage
        if (localStorage.getItem('theme') === 'light') {
            document.body.classList.add('light-mode');
            document.querySelector('.theme-toggle').textContent = 'Dark Mode';
        }

        // Hàm chuyển đổi theme
        function toggleTheme() {
            const body = document.body;
            const isLightMode = body.classList.toggle('light-mode');
            const themeToggleBtn = document.querySelector('.theme-toggle');

            // Cập nhật text của nút
            themeToggleBtn.textContent = isLightMode ? 'Dark Mode' : 'Light Mode';

            // Lưu trạng thái theme vào localStorage
            localStorage.setItem('theme', isLightMode ? 'light' : 'dark');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>