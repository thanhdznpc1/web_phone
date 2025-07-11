<?php
include 'db.php';
session_start();

// Đảm bảo máy chủ gửi đúng mã hóa UTF-8
header('Content-Type: text/html; charset=UTF-8');

// Bật hiển thị lỗi PHP (chỉ dùng trong môi trường phát triển)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Xử lý tìm kiếm, lọc, và sắp xếp
$search_query = '';
$brand_filter = '';
$price_filter = '';
$ram_filter = '';
$sort = '';

$sql = "SELECT * FROM dienthoai WHERE 1=1";

// Tìm kiếm
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $sql .= " AND ten LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}

// Lọc theo thương hiệu
if (isset($_GET['brand']) && !empty($_GET['brand'])) {
    $brand_filter = $_GET['brand'];
    $sql .= " AND thuong_hieu = '" . $conn->real_escape_string($brand_filter) . "'";
}

// Lọc theo giá
if (isset($_GET['price_range']) && !empty($_GET['price_range'])) {
    $price_filter = $_GET['price_range'];
    if ($price_filter == 'under_10m') {
        $sql .= " AND gia < 10000000";
    } elseif ($price_filter == '10m_to_20m') {
        $sql .= " AND gia BETWEEN 10000000 AND 20000000";
    } elseif ($price_filter == 'above_20m') {
        $sql .= " AND gia > 20000000";
    }
}

// Lọc theo RAM
if (isset($_GET['ram']) && !empty($_GET['ram'])) {
    $ram_filter = $_GET['ram'];
    $sql .= " AND ram = '" . $conn->real_escape_string($ram_filter) . "'";
}

// Sắp xếp
if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    $sort = $_GET['sort'];
    if ($sort == 'price_asc') {
        $sql .= " ORDER BY gia ASC";
    } elseif ($sort == 'price_desc') {
        $sql .= " ORDER BY gia DESC";
    }
}

// Phân trang
$per_page = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $per_page;

// Đếm tổng số sản phẩm
$total_sql = "SELECT COUNT(*) as total FROM dienthoai WHERE 1=1";
if ($search_query) {
    $total_sql .= " AND ten LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}
if ($brand_filter) {
    $total_sql .= " AND thuong_hieu = '" . $conn->real_escape_string($brand_filter) . "'";
}
if ($price_filter == 'under_10m') {
    $total_sql .= " AND gia < 10000000";
} elseif ($price_filter == '10m_to_20m') {
    $total_sql .= " AND gia BETWEEN 10000000 AND 20000000";
} elseif ($price_filter == 'above_20m') {
    $total_sql .= " AND gia > 20000000";
}
if ($ram_filter) {
    $total_sql .= " AND ram = '" . $conn->real_escape_string($ram_filter) . "'";
}
$total_result = $conn->query($total_sql);
if (!$total_result) {
    die("Lỗi truy vấn tổng số sản phẩm: " . $conn->error);
}
$total_row = $total_result->fetch_assoc();
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $per_page);

// Thêm LIMIT vào truy vấn
$sql .= " LIMIT $start, $per_page";
$result = $conn->query($sql);
if (!$result) {
    die("Lỗi truy vấn danh sách sản phẩm: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mua điện thoại giá rẻ, uy tín tại Shop Điện Thoại Siêu Rẻ. Đa dạng mẫu mã, thương hiệu, giá tốt nhất!">
    <meta name="keywords" content="điện thoại giá rẻ, mua điện thoại, điện thoại Apple, Samsung, Xiaomi, shop điện thoại">
    <meta name="author" content="Shop Điện Thoại Siêu Rẻ">
    <title>Shop Điện Thoại Siêu Rẻ - Mua Điện Thoại Giá Tốt</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&subset=vietnamese&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&subset=vietnamese&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #1a1a1a;
            --secondary-bg: #2c2c2c;
            --text-color: #ffffff;
            --card-text-color: #ffffff;
            --primary-color: #FFD700;
            --secondary-color: #DAA520;
            --header-bg: linear-gradient(90deg, #1a1a1a, #333);
            --border-color: rgba(255, 215, 0, 0.3);
            --shadow-color: rgba(255, 215, 0, 0.2);
            --card-bg: rgba(0, 0, 0, 0.3);
            --input-bg: rgba(255, 255, 255, 0.1);
        }

        .light-mode {
            --bg-color: #f5f5f5;
            --secondary-bg: #e0e0e0;
            --text-color: #333333;
            --card-text-color: #333333;
            --primary-color: #007bff;
            --secondary-color: #0056b3;
            --header-bg: linear-gradient(90deg, #007bff, #0056b3);
            --border-color: rgba(0, 123, 255, 0.3);
            --shadow-color: rgba(0, 123, 255, 0.2);
            --card-bg: rgba(255, 255, 255, 0.9);
            --input-bg: rgba(255, 255, 255, 0.9);
        }

        body {
            font-family: 'Poppins', 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: var(--bg-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        body, h1, h2, h3, h4, h5, h6, p, span, a, li, td, th, label {
            color: var(--text-color);
        }

        .header {
            background: var(--header-bg);
            padding: 20px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            border-bottom: 2px solid var(--primary-color);
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
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary-color);
            text-shadow: 0 0 10px var(--shadow-color);
            margin: 0;
        }

        .light-mode .logo {
            color: #ffffff;
            text-shadow: 0 0 10px var(--shadow-color);
        }

        .search-form {
            display: flex;
            flex-grow: 1;
            max-width: 500px;
            margin: 0 15px;
        }

        .search-form input {
            flex-grow: 1;
            padding: 10px 15px;
            border-radius: 25px 0 0 25px;
            border: 1px solid var(--border-color);
            background: var(--input-bg);
            color: var(--text-color);
            outline: none;
        }

        .btn-search {
            padding: 10px 15px;
            border-radius: 0 25px 25px 0;
            border: none;
            background: var(--primary-color);
            color: #1a1a1a;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .light-mode .btn-search {
            color: #ffffff;
        }

        .btn-search:hover {
            background: var(--secondary-color);
        }

        .auth-links {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome {
            color: var(--primary-color);
            font-weight: 500;
            margin-right: 10px;
        }

        .light-mode .welcome {
            color: var(--primary-color);
        }

        .btn {
            padding: 8px 15px;
            border-radius: 25px;
            text-decoration: none;
            color: #1a1a1a;
            background: var(--primary-color);
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 0 10px var(--shadow-color);
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .light-mode .btn {
            color: white;
        }

        .btn:hover {
            background: var(--secondary-color);
            box-shadow: 0 0 15px var(--shadow-color);
            transform: translateY(-2px);
        }

        .theme-toggle {
            background: var(--primary-color);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 0 10px var(--shadow-color);
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .theme-toggle:hover {
            transform: translateY(-2px) rotate(30deg);
        }

        .theme-toggle i {
            font-size: 20px;
            color: #1a1a1a;
        }

        .light-mode .theme-toggle i {
            color: white;
        }

        .banner {
            width: 100%;
            overflow: hidden;
            margin-bottom: 30px;
            position: relative;
        }

        .slideshow-container {
            width: 100%;
            height: 400px; /* Đảm bảo chiều cao cố định */
            position: relative;
            overflow: hidden;
        }

        .slide {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 1s ease;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            z-index: 1;
        }

        .slide.active {
            opacity: 1;
            z-index: 2;
        }

        .default-banner {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }

        .prev, .next {
            cursor: pointer;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: auto;
            padding: 10px;
            color: var(--text-color);
            font-weight: bold;
            font-size: 18px;
            transition: 0.3s ease;
            user-select: none;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 3px;
        }

        .next {
            right: 10px;
        }

        .prev {
            left: 10px;
        }

        .prev:hover, .next:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .dots-container {
            text-align: center;
            position: absolute;
            bottom: 10px;
            width: 100%;
        }

        .dot {
            cursor: pointer;
            height: 15px;
            width: 15px;
            margin: 0 5px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        .active-dot, .dot:hover {
            background-color: var(--primary-color);
        }

        .no-banner {
            text-align: center;
            padding: 50px;
            color: var(--text-color);
            background: var(--card-bg);
            border-radius: 10px;
            margin: 20px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            background: var(--card-bg);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 10px var(--shadow-color);
        }

        .filter-form select, .filter-form button {
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background: var(--input-bg);
            color: var(--text-color);
            outline: none;
        }

        .filter-form select option {
            background: var(--bg-color);
            color: var(--text-color);
        }

        .section-title {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
        }

        .light-mode .section-title {
            color: var(--primary-color);
        }

        .full-width-container {
            width: 100%;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .product-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .product-item {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s ease;
            box-shadow: 0 0 10px var(--shadow-color);
            border: 1px solid var(--border-color);
        }

        .product-item:hover {
            transform: translateY(-5px);
        }

        .product-item img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .no-image {
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-color);
            background: rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .product-name {
            font-size: 16px;
            margin: 10px 0;
            color: var(--card-text-color);
        }

        .product-details {
            font-size: 14px;
            color: var(--text-color);
            margin: 5px 0;
        }

        .product-price {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin: 10px 0;
        }

        .product-item .btn {
            margin: 5px;
            padding: 8px 12px;
            font-size: 13px;
        }

        .no-products {
            text-align: center;
            grid-column: 1 / -1;
            padding: 30px;
            color: var(--text-color);
            background: var(--card-bg);
            border-radius: 10px;
            margin: 20px 0;
            opacity: 0.9;
        }

        .no-products a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .no-products a:hover {
            text-decoration: underline;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 30px 0;
            flex-wrap: wrap;
            gap: 5px;
        }

        .pagination a {
            padding: 8px 15px;
            text-decoration: none;
            color: var(--text-color);
            background: var(--card-bg);
            border-radius: 5px;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .pagination a:hover, .pagination a.active {
            background: var(--primary-color);
            color: #1a1a1a;
            font-weight: 600;
        }

        .light-mode .pagination a:hover, 
        .light-mode .pagination a.active {
            color: white;
        }

        .pagination .prev-page, .pagination .next-page {
            font-size: 16px;
            font-weight: bold;
        }

        .pagination .ellipsis {
            padding: 8px 15px;
            color: var(--text-color);
        }

        .footer {
            background: var(--header-bg);
            color: var(--primary-color);
            text-align: center;
            padding: 20px 0;
            margin-top: 30px;
            border-top: 2px solid var(--primary-color);
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.5);
        }

        .footer p {
            color: var(--primary-color);
        }

        .light-mode .footer p {
            color: var(--primary-color);
        }

        /* Thông báo khi thêm vào giỏ hàng */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary-color);
            color: #1a1a1a;
            padding: 10px 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px var(--shadow-color);
            z-index: 1000;
            display: none;
            transition: opacity 0.3s ease;
        }

        .light-mode .notification {
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-flex {
                flex-direction: column;
                gap: 10px;
            }

            .search-form {
                max-width: 100%;
                margin: 0;
            }

            .auth-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .filter-form {
                flex-direction: column;
            }

            .filter-form select, .filter-form button {
                width: 100%;
            }

            .product-list {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .logo {
                font-size: 24px;
            }

            .btn {
                padding: 6px 10px;
                font-size: 12px;
            }

            .theme-toggle {
                width: 35px;
                height: 35px;
            }

            .theme-toggle i {
                font-size: 16px;
            }

            .product-item img, .no-image {
                height: 150px;
            }

            .product-name {
                font-size: 14px;
            }

            .product-price {
                font-size: 16px;
            }

            .product-item .btn {
                padding: 6px 10px;
                font-size: 12px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<!-- Thông báo khi thêm vào giỏ hàng -->
<div id="notification" class="notification"></div>

<header class="header">
    <div class="container header-flex">
        <h1 class="logo">📱 Điện Thoại Siêu Rẻ</h1>

        <form class="search-form" method="GET" action="">
            <input type="text" name="search" placeholder="Tìm kiếm điện thoại..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn-search">🔍</button>
        </form>

        <nav class="auth-links">
            <?php if (isset($_SESSION['username'])): ?>
                <span class="welcome">Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="profile.php" class="btn">Tài khoản</a>
                <a href="history.php" class="btn">Lịch sử</a>
                <a href="auth/logout.php" class="btn">Đăng xuất</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn">Đăng nhập</a>
                <a href="auth/register.php" class="btn">Đăng ký</a>
            <?php endif; ?>
            <a href="cart.php" class="btn">🛒</a>
            <button class="theme-toggle" id="themeToggle" title="Chuyển đổi chế độ sáng/tối">
                <i class="fas fa-moon"></i>
            </button>
        </nav>
    </div>
</header>

<div class="banner">
    <div class="slideshow-container">
        <?php
        $banners = [
            'banner1.jpg',
            'banner2.jpg',
            'banner3.jpg',
            'banner4.jpg',
            'banner5.jpg',
            'banner6.jpg',
            'banner7.jpg',
            'banner8.jpg',
            'banner9.jpg'
        ];

        $valid_banners = [];
        foreach ($banners as $banner) {
            // Chuẩn hóa tên file về lowercase để tránh lỗi phân biệt hoa thường
            $banner = strtolower($banner);
            $image_path = "images/" . $banner;
            if (file_exists($image_path)) {
                $valid_banners[] = $banner;
                echo '<img src="' . $image_path . '" class="slide' . (count($valid_banners) === 1 ? ' active' : '') . '" alt="Banner" loading="lazy">';
            } else {
                echo "<!-- Hình ảnh không tồn tại: $image_path. Kiểm tra thư mục images/ và tên file ($banner) -->";
            }
        }

        if (empty($valid_banners)) {
            echo '<img src="https://via.placeholder.com/1200x400?text=Banner+Điện+Thoại+Siêu+Rẻ" class="default-banner" alt="Default Banner" loading="lazy">';
        } else {
            echo '<a class="prev" onclick="plusSlides(-1)">❮</a>';
            echo '<a class="next" onclick="plusSlides(1)">❯</a>';
            echo '<div class="dots-container">';
            for ($i = 0; $i < count($valid_banners); $i++) {
                echo '<span class="dot' . ($i === 0 ? ' active-dot' : '') . '" onclick="currentSlide(' . ($i + 1) . ')"></span>';
            }
            echo '</div>';
        }
        ?>
    </div>
</div>

<main>
    <div class="container">
        <form class="filter-form" method="GET" action="">
            <select name="brand">
                <option value="">Tất cả thương hiệu</option>
                <option value="Apple" <?php echo $brand_filter == 'Apple' ? 'selected' : ''; ?>>Apple</option>
                <option value="Samsung" <?php echo $brand_filter == 'Samsung' ? 'selected' : ''; ?>>Samsung</option>
                <option value="Xiaomi" <?php echo $brand_filter == 'Xiaomi' ? 'selected' : ''; ?>>Xiaomi</option>
                <option value="Oppo" <?php echo $brand_filter == 'Oppo' ? 'selected' : ''; ?>>Oppo</option>
                <option value="Huawei" <?php echo $brand_filter == 'Huawei' ? 'selected' : ''; ?>>Huawei</option>
                <option value="Vivo" <?php echo $brand_filter == 'Vivo' ? 'selected' : ''; ?>>Vivo</option>
                <option value="HONOR" <?php echo $brand_filter == 'HONOR' ? 'selected' : ''; ?>>HONOR</option>
                <option value="Realme" <?php echo $brand_filter == 'Realme' ? 'selected' : ''; ?>>Realme</option>
                <option value="Sony" <?php echo $brand_filter == 'Sony' ? 'selected' : ''; ?>>Sony</option>
                <option value="Asus" <?php echo $brand_filter == 'Asus' ? 'selected' : ''; ?>>Asus</option>
                <option value="Lenovo" <?php echo $brand_filter == 'Lenovo' ? 'selected' : ''; ?>>Lenovo</option>
            </select>

            <select name="price_range">
                <option value="">Tất cả giá</option>
                <option value="under_10m" <?php echo $price_filter == 'under_10m' ? 'selected' : ''; ?>>Dưới 10 triệu</option>
                <option value="10m_to_20m" <?php echo $price_filter == '10m_to_20m' ? 'selected' : ''; ?>>10 - 20 triệu</option>
                <option value="above_20m" <?php echo $price_filter == 'above_20m' ? 'selected' : ''; ?>>Trên 20 triệu</option>
            </select>

            <select name="ram">
                <option value="">Tất cả RAM</option>
                <option value="4GB" <?php echo $ram_filter == '4GB' ? 'selected' : ''; ?>>4GB</option>
                <option value="6GB" <?php echo $ram_filter == '6GB' ? 'selected' : ''; ?>>6GB</option>
                <option value="8GB" <?php echo $ram_filter == '8GB' ? 'selected' : ''; ?>>8GB</option>
                <option value="12GB" <?php echo $ram_filter == '12GB' ? 'selected' : ''; ?>>12GB</option>
            </select>

            <select name="sort">
                <option value="">Sắp xếp</option>
                <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
                <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
            </select>

            <button type="submit" class="btn">Lọc</button>
        </form>

        <h2 class="section-title">
            <?php if ($search_query): ?>
                Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search_query); ?>"
            <?php else: ?>
                Sản Phẩm
            <?php endif; ?>
        </h2>
    </div>

    <div class="full-width-container">
        <div class="product-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="product-item">
                        <?php
                        $product_image_path = "images/" . $row['hinhanh'];
                        if (file_exists($product_image_path)) {
                            echo '<img src="' . $product_image_path . '" alt="' . htmlspecialchars($row['ten']) . '" loading="lazy">';
                        } else {
                            echo "<!-- Hình ảnh sản phẩm không tồn tại: $product_image_path -->";
                            echo '<p class="no-image">Hình ảnh không tồn tại: ' . htmlspecialchars($row['hinhanh']) . '</p>';
                        }
                        ?>
                        <h3 class="product-name"><?php echo htmlspecialchars($row['ten']); ?></h3>
                        <p class="product-details"><?php echo htmlspecialchars($row['thuong_hieu']); ?> | RAM: <?php echo htmlspecialchars($row['ram']); ?></p>
                        <p class="product-price"><?php echo number_format($row['gia'], 0, ',', '.'); ?> VNĐ</p>
                        <a href="chitiet.php?id=<?php echo $row['id']; ?>" class="btn">Xem chi tiết</a>
                        <a href="cart.php?action=add&id=<?php echo $row['id']; ?>" class="btn add-to-cart" data-name="<?php echo htmlspecialchars($row['ten']); ?>">Thêm vào giỏ</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-products">
                    <?php if ($search_query): ?>
                        Không tìm thấy sản phẩm nào với từ khóa "<?php echo htmlspecialchars($search_query); ?>"! 
                        <a href="?">Xem tất cả sản phẩm</a> hoặc thử tìm kiếm với từ khóa khác.
                    <?php else: ?>
                        Không có sản phẩm nào! Hãy kiểm tra lại cơ sở dữ liệu.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <div class="pagination">
            <?php if ($total_pages > 1): ?>
                <?php
                // Thêm nút "Trang trước"
                if ($page > 1) {
                    $prev_page = $page - 1;
                    echo '<a href="?page=' . $prev_page . '&search=' . urlencode($search_query) . '&brand=' . urlencode($brand_filter) . '&price_range=' . urlencode($price_filter) . '&ram=' . urlencode($ram_filter) . '&sort=' . urlencode($sort) . '" class="prev-page">« Trang trước</a>';
                }

                // Tính toán số trang hiển thị (giới hạn 5 số)
                $range = 2; // Hiển thị 2 số trước và sau trang hiện tại
                $start_page = max(1, $page - $range);
                $end_page = min($total_pages, $page + $range);

                // Thêm "..." nếu cần
                if ($start_page > 1) {
                    echo '<a href="?page=1&search=' . urlencode($search_query) . '&brand=' . urlencode($brand_filter) . '&price_range=' . urlencode($price_filter) . '&ram=' . urlencode($ram_filter) . '&sort=' . urlencode($sort) . '">1</a>';
                    if ($start_page > 2) {
                        echo '<span class="ellipsis">...</span>';
                    }
                }

                // Hiển thị các số trang
                for ($i = $start_page; $i <= $end_page; $i++) {
                    echo '<a href="?page=' . $i . '&search=' . urlencode($search_query) . '&brand=' . urlencode($brand_filter) . '&price_range=' . urlencode($price_filter) . '&ram=' . urlencode($ram_filter) . '&sort=' . urlencode($sort) . '" class="' . ($i == $page ? 'active' : '') . '">' . $i . '</a>';
                }

                // Thêm "..." nếu cần
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<span class="ellipsis">...</span>';
                    }
                    echo '<a href="?page=' . $total_pages . '&search=' . urlencode($search_query) . '&brand=' . urlencode($brand_filter) . '&price_range=' . urlencode($price_filter) . '&ram=' . urlencode($ram_filter) . '&sort=' . urlencode($sort) . '">' . $total_pages . '</a>';
                }

                // Thêm nút "Trang sau"
                if ($page < $total_pages) {
                    $next_page = $page + 1;
                    echo '<a href="?page=' . $next_page . '&search=' . urlencode($search_query) . '&brand=' . urlencode($brand_filter) . '&price_range=' . urlencode($price_filter) . '&ram=' . urlencode($ram_filter) . '&sort=' . urlencode($sort) . '" class="next-page">Trang sau »</a>';
                }
                ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <p>© 2025 Shop Điện Thoại Siêu Rẻ</p>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Áp dụng theme ngay khi load trang
    const currentTheme = localStorage.getItem('theme') || 'dark';
    const themeToggle = document.getElementById('themeToggle');
    const icon = themeToggle.querySelector('i');
    
    if (currentTheme === 'light') {
        document.body.classList.add('light-mode');
        icon.classList.replace('fa-moon', 'fa-sun');
    }
    
    // Xử lý chuyển đổi theme
    themeToggle.addEventListener('click', function() {
        document.body.classList.toggle('light-mode');
        
        if (document.body.classList.contains('light-mode')) {
            localStorage.setItem('theme', 'light');
            icon.classList.replace('fa-moon', 'fa-sun');
        } else {
            localStorage.setItem('theme', 'dark');
            icon.classList.replace('fa-sun', 'fa-moon');
        }
    });
    
    // Xử lý slideshow banner
    let slideIndex = 1;
    let slides = document.getElementsByClassName("slide");
    let dots = document.getElementsByClassName("dot");
    let autoSlideInterval;
    let imagesLoaded = 0;
    let totalImages = slides.length;

    // Kiểm tra trạng thái tải hình ảnh
    if (totalImages > 0) {
        for (let i = 0; i < slides.length; i++) {
            const img = slides[i];
            if (img.complete) {
                imagesLoaded++;
                console.log(`Hình ảnh ${img.src} đã tải thành công`);
                if (imagesLoaded === totalImages) {
                    startSlideshow();
                }
            } else {
                img.onload = () => {
                    imagesLoaded++;
                    console.log(`Hình ảnh ${img.src} đã tải thành công`);
                    if (imagesLoaded === totalImages) {
                        startSlideshow();
                    }
                };
                img.onerror = () => {
                    console.error(`Không tải được hình ảnh: ${img.src}`);
                    img.style.display = 'none'; // Ẩn slide nếu hình ảnh không load được
                    imagesLoaded++;
                    if (imagesLoaded === totalImages) {
                        startSlideshow();
                    }
                };
            }
        }
    } else {
        console.log('Không có slide nào để hiển thị');
    }

    function startSlideshow() {
        showSlides(slideIndex);
        autoSlideInterval = setInterval(() => plusSlides(1), 5000); // Chuyển slide mỗi 5 giây
    }

    function plusSlides(n) {
        clearInterval(autoSlideInterval); // Tạm dừng slideshow tự động khi người dùng tương tác
        showSlides(slideIndex += n);
        // Tự động chạy lại sau 10 giây không tương tác
        setTimeout(() => {
            autoSlideInterval = setInterval(() => plusSlides(1), 5000);
        }, 10000);
    }

    function currentSlide(n) {
        clearInterval(autoSlideInterval); // Tạm dừng slideshow tự động khi người dùng tương tác
        showSlides(slideIndex = n);
        // Tự động chạy lại sau 10 giây không tương tác
        setTimeout(() => {
            autoSlideInterval = setInterval(() => plusSlides(1), 5000);
        }, 10000);
    }

    function showSlides(n) {
        if (slides.length === 0) return;

        if (n > slides.length) slideIndex = 1;
        if (n < 1) slideIndex = slides.length;

        // Ẩn tất cả các slide và xóa lớp active
        for (let i = 0; i < slides.length; i++) {
            slides[i].classList.remove('active');
        }

        // Xóa lớp active-dot khỏi tất cả các dot
        for (let i = 0; i < dots.length; i++) {
            dots[i].className = dots[i].className.replace(' active-dot', '');
        }

        // Hiển thị slide hiện tại và thêm lớp active
        slides[slideIndex-1].classList.add('active');
        dots[slideIndex-1].className += ' active-dot';
    }

    window.plusSlides = plusSlides;
    window.currentSlide = currentSlide;

    // Xử lý thông báo khi thêm vào giỏ hàng
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productName = this.getAttribute('data-name');
            const notification = document.getElementById('notification');
            
            notification.textContent = `Đã thêm ${productName} vào giỏ hàng!`;
            notification.style.display = 'block';
            notification.style.opacity = '1';

            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 300);
            }, 2000);

            setTimeout(() => {
                window.location.href = this.href;
            }, 500);
        });
    });
});
</script>

</body>
</html>