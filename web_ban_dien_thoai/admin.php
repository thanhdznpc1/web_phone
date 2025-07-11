<?php
// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kết nối database
include 'db.php';
if (!$conn) {
    die("Không thể kết nối đến database. Vui lòng kiểm tra file db.php.");
}

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: auth/login.php');
    exit();
}

// Xử lý tìm kiếm
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'products';

// Tìm kiếm sản phẩm
if ($search_type == 'products' && $search_query) {
    $stmt = $conn->prepare("SELECT d.*, c.name AS category_name FROM dienthoai d LEFT JOIN categories c ON d.category_id = c.id WHERE d.ten LIKE ? OR d.thuong_hieu LIKE ?");
    if (!$stmt) {
        die("Lỗi chuẩn bị truy vấn sản phẩm: " . $conn->error);
    }
    $search_term = "%$search_query%";
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $products = $stmt->get_result();
} else {
    $products = $conn->query("SELECT * FROM dienthoai ORDER BY id DESC");
    if (!$products) {
        die("Lỗi truy vấn sản phẩm: " . $conn->error);
    }
}

// Tìm kiếm loại sản phẩm
if ($search_type == 'categories' && $search_query) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE name LIKE ?");
    if (!$stmt) {
        die("Lỗi chuẩn bị truy vấn loại sản phẩm: " . $conn->error);
    }
    $search_term = "%$search_query%";
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $categories = $stmt->get_result();
} else {
    $categories = $conn->query("SELECT * FROM categories ORDER BY id DESC");
    if (!$categories) {
        die("Lỗi truy vấn loại sản phẩm: " . $conn->error);
    }
}

// Tìm kiếm tài khoản người dùng
if ($search_type == 'users' && $search_query) {
    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE username LIKE ?");
    if (!$stmt) {
        die("Lỗi chuẩn bị truy vấn tài khoản: " . $conn->error);
    }
    $search_term = "%$search_query%";
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query("SELECT id, username, role FROM users ORDER BY id DESC");
    if (!$users) {
        die("Lỗi truy vấn tài khoản: " . $conn->error);
    }
}

// Tìm kiếm đơn hàng
if ($search_type == 'orders' && $search_query) {
    $stmt = $conn->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id LIKE ? OR u.username LIKE ? ORDER BY o.ngay_dat DESC");
    if (!$stmt) {
        die("Lỗi chuẩn bị truy vấn đơn hàng: " . $conn->error);
    }
    $search_term = "%$search_query%";
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $orders = $conn->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.ngay_dat DESC");
    if (!$orders) {
        die("Lỗi truy vấn đơn hàng: " . $conn->error);
    }
}

// Xử lý thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $ten = trim($_POST['ten']);
    $gia = floatval($_POST['gia']);
    $category_id = intval($_POST['category_id']);
    $thuong_hieu = trim($_POST['thuong_hieu']);
    $man_hinh = trim($_POST['man_hinh']);
    $camera = trim($_POST['camera']);
    $ram = trim($_POST['ram']);
    $rom = trim($_POST['rom']);
    $pin = trim($_POST['pin']);
    $khuyen_mai = trim($_POST['khuyen_mai']);
    $mota = trim($_POST['mota']);
    $hinhanh = '';

    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
        $target_dir = "images/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $target_file = $target_dir . basename($_FILES["hinhanh"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["hinhanh"]["tmp_name"], $target_file)) {
                $hinhanh = basename($_FILES["hinhanh"]["name"]);
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Không thể upload ảnh.'];
                header('Location: admin.php');
                exit();
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận jpg, jpeg, png, gif.'];
            header('Location: admin.php');
            exit();
        }
    }

    if ($ten && $gia && $category_id && $hinhanh) {
        $stmt = $conn->prepare("INSERT INTO dienthoai (ten, gia, hinhanh, category_id, thuong_hieu, man_hinh, camera, ram, rom, pin, khuyen_mai, mota) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Lỗi chuẩn bị truy vấn thêm sản phẩm: " . $conn->error);
        }
        $stmt->bind_param("sdssssssssss", $ten, $gia, $hinhanh, $category_id, $thuong_hieu, $man_hinh, $camera, $ram, $rom, $pin, $khuyen_mai, $mota);
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Thêm sản phẩm thành công!'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Có lỗi khi thêm sản phẩm: ' . $conn->error];
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Thiếu thông tin hoặc ảnh không hợp lệ.'];
    }
    header('Location: admin.php');
    exit();
}

// Xử lý cập nhật sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $id = intval($_POST['id']);
    $ten = trim($_POST['ten']);
    $gia = floatval($_POST['gia']);
    $category_id = intval($_POST['category_id']);
    $thuong_hieu = trim($_POST['thuong_hieu']);
    $man_hinh = trim($_POST['man_hinh']);
    $camera = trim($_POST['camera']);
    $ram = trim($_POST['ram']);
    $rom = trim($_POST['rom']);
    $pin = trim($_POST['pin']);
    $khuyen_mai = trim($_POST['khuyen_mai']);
    $mota = trim($_POST['mota']);
    $hinhanh = trim($_POST['current_image']);

    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
        $target_dir = "images/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $target_file = $target_dir . basename($_FILES["hinhanh"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["hinhanh"]["tmp_name"], $target_file)) {
                // Xóa ảnh cũ nếu tồn tại
                if ($hinhanh && file_exists("images/" . $hinhanh)) {
                    unlink("images/" . $hinhanh);
                }
                $hinhanh = basename($_FILES["hinhanh"]["name"]);
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Không thể upload ảnh mới.'];
                header('Location: admin.php');
                exit();
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận jpg, jpeg, png, gif.'];
            header('Location: admin.php');
            exit();
        }
    }

    if ($ten && $gia && $category_id) {
        $stmt = $conn->prepare("UPDATE dienthoai SET ten = ?, gia = ?, hinhanh = ?, category_id = ?, thuong_hieu = ?, man_hinh = ?, camera = ?, ram = ?, rom = ?, pin = ?, khuyen_mai = ?, mota = ? WHERE id = ?");
        if (!$stmt) {
            die("Lỗi chuẩn bị truy vấn cập nhật sản phẩm: " . $conn->error);
        }
        $stmt->bind_param("sdssssssssssi", $ten, $gia, $hinhanh, $category_id, $thuong_hieu, $man_hinh, $camera, $ram, $rom, $pin, $khuyen_mai, $mota, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Cập nhật sản phẩm thành công!'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Có lỗi khi cập nhật sản phẩm: ' . $conn->error];
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Thiếu thông tin cần thiết.'];
    }
    header('Location: admin.php');
    exit();
}

// Xử lý xóa sản phẩm
if (isset($_GET['delete_product_id'])) {
    $id = intval($_GET['delete_product_id']);
    // Kiểm tra sản phẩm có trong đơn hàng không
    $check = $conn->prepare("SELECT COUNT(*) as count FROM order_details WHERE product_id = ?");
    if (!$check) {
        die("Lỗi chuẩn bị truy vấn kiểm tra sản phẩm: " . $conn->error);
    }
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    if ($result->fetch_assoc()['count'] == 0) {
        $stmt = $conn->prepare("SELECT hinhanh FROM dienthoai WHERE id = ?");
        if (!$stmt) {
            die("Lỗi chuẩn bị truy vấn lấy ảnh sản phẩm: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $image_path = "images/" . $row['hinhanh'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            $stmt = $conn->prepare("DELETE FROM dienthoai WHERE id = ?");
            if (!$stmt) {
                die("Lỗi chuẩn bị truy vấn xóa sản phẩm: " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Xóa sản phẩm thành công!'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Có lỗi khi xóa sản phẩm.'];
            }
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Không thể xóa sản phẩm đã có trong đơn hàng!'];
    }
    header('Location: admin.php');
    exit();
}

// Xử lý thêm loại sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if ($name) {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        if (!$stmt) {
            die("Lỗi chuẩn bị truy vấn thêm loại sản phẩm: " . $conn->error);
        }
        $stmt->bind_param("ss", $name, $description);
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Thêm loại sản phẩm thành công!'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Có lỗi khi thêm loại sản phẩm.'];
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Thiếu tên loại sản phẩm.'];
    }
    header('Location: admin.php');
    exit();
}

// Xử lý cập nhật loại sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if ($name) {
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        if (!$stmt) {
            die("Lỗi chuẩn bị truy vấn cập nhật loại sản phẩm: " . $conn->error);
        }
        $stmt->bind_param("ssi", $name, $description, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Cập nhật loại sản phẩm thành công!'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Có lỗi khi cập nhật loại sản phẩm.'];
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Thiếu tên loại sản phẩm.'];
    }
    header('Location: admin.php');
    exit();
}

// Xử lý xóa loại sản phẩm
if (isset($_GET['delete_category_id'])) {
    $id = intval($_GET['delete_category_id']);
    // Kiểm tra loại sản phẩm có sản phẩm nào không
    $check = $conn->prepare("SELECT COUNT(*) as count FROM dienthoai WHERE category_id = ?");
    if (!$check) {
        die("Lỗi chuẩn bị truy vấn kiểm tra loại sản phẩm: " . $conn->error);
    }
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    if ($result->fetch_assoc()['count'] == 0) {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        if (!$stmt) {
            die("Lỗi chuẩn bị truy vấn xóa loại sản phẩm: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Xóa loại sản phẩm thành công!'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Có lỗi khi xóa loại sản phẩm.'];
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Không thể xóa loại sản phẩm có sản phẩm!'];
    }
    header('Location: admin.php');
    exit();
}

// Xử lý cập nhật số lượng trong đơn hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quantity'])) {
    $order_id = intval($_POST['order_id']);
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    if ($quantity >= 0) {
        $stmt = $conn->prepare("UPDATE order_details SET quantity = ? WHERE order_id = ? AND product_id = ?");
        if (!$stmt) {
            die("Lỗi chuẩn bị truy vấn cập nhật số lượng: " . $conn->error);
        }
        $stmt->bind_param("iii", $quantity, $order_id, $product_id);
        if ($stmt->execute()) {
            // Cập nhật tổng tiền đơn hàng
            $total = 0;
            $stmt_details = $conn->prepare("SELECT quantity, price FROM order_details WHERE order_id = ?");
            if (!$stmt_details) {
                die("Lỗi chuẩn bị truy vấn lấy chi tiết đơn hàng: " . $conn->error);
            }
            $stmt_details->bind_param("i", $order_id);
            $stmt_details->execute();
            $details = $stmt_details->get_result();
            while ($detail = $details->fetch_assoc()) {
                $total += $detail['quantity'] * $detail['price'];
            }
            $stmt_details->close();

            $update_total = $conn->prepare("UPDATE orders SET total = ? WHERE id = ?");
            if (!$update_total) {
                die("Lỗi chuẩn bị truy vấn cập nhật tổng tiền: " . $conn->error);
            }
            $update_total->bind_param("di", $total, $order_id);
            $update_total->execute();
            $update_total->close();

            $_SESSION['message'] = ['type' => 'success', 'text' => 'Cập nhật số lượng thành công!'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Có lỗi khi cập nhật số lượng.'];
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Số lượng phải lớn hơn hoặc bằng 0.'];
    }
    header('Location: admin.php?tab=orders');
    exit();
}

// Xử lý xóa tài khoản
if (isset($_GET['delete_user_id'])) {
    $id = intval($_GET['delete_user_id']);
    // Kiểm tra tài khoản có đơn hàng không
    $check = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    if (!$check) {
        die("Lỗi chuẩn bị truy vấn kiểm tra tài khoản: " . $conn->error);
    }
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    if ($result->fetch_assoc()['count'] == 0) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if (!$stmt) {
            die("Lỗi chuẩn bị truy vấn xóa tài khoản: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Xóa tài khoản thành công!'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Có lỗi khi xóa tài khoản.'];
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Không thể xóa tài khoản đã đặt hàng!'];
    }
    header('Location: admin.php?tab=users');
    exit();
}

// Xử lý cập nhật tài khoản người dùng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $role = intval($_POST['role']);
    if ($username) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        if (!$stmt) {
            die("Lỗi chuẩn bị truy vấn cập nhật tài khoản: " . $conn->error);
        }
        $stmt->bind_param("sii", $username, $role, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Cập nhật tài khoản thành công!'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Có lỗi khi cập nhật tài khoản: ' . $conn->error];
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Thiếu tên đăng nhập.'];
    }
    header('Location: admin.php?tab=users');
    exit();
}

// Xử lý duyệt/từ chối đánh giá
if (isset($_GET['review_id']) && isset($_GET['status'])) {
    $review_id = intval($_GET['review_id']);
    $status = $_GET['status'] === 'approve' ? 'approved' : 'rejected';
    $stmt = $conn->prepare("UPDATE danhgia SET status = ? WHERE id = ?");
    if (!$stmt) {
        die("Lỗi chuẩn bị truy vấn cập nhật đánh giá: " . $conn->error);
    }
    $stmt->bind_param("si", $status, $review_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = ['type' => 'success', 'text' => ($status === 'approved' ? 'Đã duyệt đánh giá!' : 'Đã từ chối đánh giá!')];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Có lỗi khi cập nhật đánh giá.'];
    }
    $stmt->close();
    header('Location: admin.php?tab=reviews');
    exit();
}

// Lấy danh sách đánh giá
$reviews = $conn->query("SELECT d.id, d.product_id, d.user_id, d.rating, d.comment, d.status, d.created_at, dt.ten AS product_name, u.username FROM danhgia d JOIN dienthoai dt ON d.product_id = dt.id JOIN users u ON d.user_id = u.id ORDER BY d.created_at DESC");
if (!$reviews) {
    die("Lỗi truy vấn đánh giá: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Admin - Shop Điện Thoại</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <!-- Header -->
    <header class="bg-gray-800 shadow-lg fixed w-full z-10">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-yellow-400">Shop Điện Thoại</a>
            <nav class="flex space-x-4 items-center">
                <a href="index.php" class="hover:text-yellow-400 transition">Trang chủ</a>
                <div class="flex items-center space-x-2">
                    <span><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'N/A'; ?></span>
                    <a href="auth/logout.php" class="hover:text-yellow-400 transition">Đăng xuất</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pt-20 container mx-auto px-4 py-8">
        <!-- Notification Modal -->
        <?php if (isset($_SESSION['message'])): ?>
            <div id="notification" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 fade-in" style="display: block;">
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg max-w-sm w-full">
                    <p class="text-center <?php echo $_SESSION['message']['type'] == 'success' ? 'text-green-400' : 'text-red-600'; ?>">
                        <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
                    </p>
                    <button onclick="document.getElementById('notification').style.display='none'" class="mt-4 w-full bg-yellow-400 text-black py-2 rounded hover:bg-yellow-500 transition">Đóng</button>
                </div>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Search -->
        <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
            <h2 class="text-2xl font-semibold text-yellow-400 mb-4">Tìm kiếm</h2>
            <form method="GET" class="flex space-x-4">
                <select name="search_type" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                    <option value="products" <?php echo $search_type == 'products' ? 'selected' : ''; ?>>Sản phẩm</option>
                    <option value="categories" <?php echo $search_type == 'categories' ? 'selected' : ''; ?>>Loại sản phẩm</option>
                    <option value="users" <?php echo $search_type == 'users' ? 'selected' : ''; ?>>Tài khoản</option>
                    <option value="orders" <?php echo $search_type == 'orders' ? 'selected' : ''; ?>>Đơn hàng</option>
                </select>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Nhập từ khóa..." class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white flex-grow">
                <button type="submit" class="bg-yellow-400 text-black px-4 py-2 rounded hover:bg-yellow-500 transition">Tìm</button>
            </form>
        </div>

        <!-- Tabs -->
        <div class="mb-4">
            <button onclick="openTab('products')" class="bg-gray-700 px-4 py-2 rounded-l hover:bg-gray-600">Sản phẩm</button>
            <button onclick="openTab('categories')" class="bg-gray-700 px-4 py-2 hover:bg-gray-600">Loại sản phẩm</button>
            <button onclick="openTab('users')" class="bg-gray-700 px-4 py-2 hover:bg-gray-600">Tài khoản</button>
            <button onclick="openTab('orders')" class="bg-gray-700 px-4 py-2 rounded-r hover:bg-gray-600">Đơn hàng</button>
            <button onclick="openTab('reviews')" class="bg-gray-700 px-4 py-2 hover:bg-gray-600">Đánh giá</button>
        </div>

        <!-- Product Management -->
        <div id="products" class="tab-content active">
            <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
                <h2 class="text-2xl font-semibold text-yellow-400 mb-4">Quản lý sản phẩm</h2>
                <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <input type="hidden" name="add_product" value="1">
                    <input type="text" name="ten" placeholder="Tên sản phẩm" required class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                    <input type="number" step="0.01" name="gia" placeholder="Giá (VND)" required class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                    <select name="category_id" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                        <?php
                        $cats = $conn->query("SELECT * FROM categories");
                        if ($cats) {
                            while ($cat = $cats->fetch_assoc()) {
                                echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                            }
                        } else {
                            echo "<option value='1'>Không có loại sản phẩm</option>";
                        }
                        ?>
                    </select>
                    <input type="text" name="thuong_hieu" placeholder="Thương hiệu" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                    <input type="text" name="man_hinh" placeholder="Màn hình" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                    <input type="text" name="camera" placeholder="Camera" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                    <select name="ram" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                        <option value="" disabled selected>Chọn RAM</option>
                        <option value="4GB">4GB</option>
                        <option value="6GB">6GB</option>
                        <option value="8GB">8GB</option>
                        <option value="12GB">12GB</option>
                    </select>
                    <select name="rom" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                        <option value="" disabled selected>Chọn ROM</option>
                        <option value="64GB">64GB</option>
                        <option value="128GB">128GB</option>
                        <option value="256GB">256GB</option>
                        <option value="512GB">512GB</option>
                        <option value="1TB">1TB</option>
                    </select>
                    <input type="text" name="pin" placeholder="Pin" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                    <input type="text" name="khuyen_mai" placeholder="Khuyến mãi" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                    <textarea name="mota" placeholder="Mô tả" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white col-span-2"></textarea>
                    <input type="file" name="hinhanh" accept="image/*" required class="p-2 bg-gray-700 rounded-lg col-span-2 text-white">
                    <button type="submit" class="bg-yellow-400 text-black px-6 py-2 rounded hover:bg-yellow-500 transition col-span-2">Thêm sản phẩm</button>
                </form>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead><tr class="bg-gray-700"><th class="p-2">ID</th><th class="p-2">Hình ảnh</th><th class="p-2">Tên</th><th class="p-2">Giá</th><th class="p-2">Loại</th><th class="p-2">Hành động</th></tr></thead>
                        <tbody>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <tr class="border-b border-gray-700">
                                    <td class="p-2"><?php echo $product['id']; ?></td>
                                    <td class="p-2"><img src="images/<?php echo htmlspecialchars($product['hinhanh']); ?>" alt="<?php echo htmlspecialchars($product['ten']); ?>" class="w-16 rounded"></td>
                                    <td class="p-2"><?php echo htmlspecialchars($product['ten']); ?></td>
                                    <td class="p-2"><?php echo number_format($product['gia'], 0, ',', '.'); ?>₫</td>
                                    <td class="p-2"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                    <td class="p-2 space-x-2">
                                        <a href="admin.php?view_product_id=<?php echo $product['id']; ?>&tab=products" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 transition">Xem</a>
                                        <a href="admin.php?edit_product_id=<?php echo $product['id']; ?>&tab=products" class="bg-green-600 text-white px-4 py-1 rounded hover:bg-green-700 transition">Sửa</a>
                                        <a href="admin.php?delete_product_id=<?php echo $product['id']; ?>" onclick="return confirm('Xác nhận xóa?')" class="bg-red-600 text-white px-4 py-1 rounded hover:bg-red-700 transition">Xóa</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Xem chi tiết sản phẩm -->
            <?php if (isset($_GET['view_product_id'])) {
                $product_id = intval($_GET['view_product_id']);
                $stmt = $conn->prepare("SELECT d.*, c.name AS category_name FROM dienthoai d LEFT JOIN categories c ON d.category_id = c.id WHERE d.id = ?");
                if (!$stmt) {
                    die("Lỗi chuẩn bị truy vấn chi tiết sản phẩm: " . $conn->error);
                }
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                if ($product): ?>
                    <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
                        <h3 class="text-xl font-semibold text-yellow-400 mb-4">Chi tiết sản phẩm #<?php echo $product_id; ?></h3>
                        <p><strong>Tên:</strong> <?php echo htmlspecialchars($product['ten'] ?? 'N/A'); ?></p>
                        <p><strong>Giá:</strong> <?php echo number_format($product['gia'] ?? 0, 0, ',', '.'); ?>₫</p>
                        <p><strong>Loại:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></p>
                        <p><strong>Thương hiệu:</strong> <?php echo htmlspecialchars($product['thuong_hieu'] ?? 'N/A'); ?></p>
                        <p><strong>Màn hình:</strong> <?php echo htmlspecialchars($product['man_hinh'] ?? 'N/A'); ?></p>
                        <p><strong>Camera:</strong> <?php echo htmlspecialchars($product['camera'] ?? 'N/A'); ?></p>
                        <p><strong>RAM:</strong> <?php echo htmlspecialchars($product['ram'] ?? 'N/A'); ?></p>
                        <p><strong>ROM:</strong> <?php echo htmlspecialchars($product['rom'] ?? 'N/A'); ?></p>
                        <p><strong>Pin:</strong> <?php echo htmlspecialchars($product['pin'] ?? 'N/A'); ?></p>
                        <p><strong>Khuyến mãi:</strong> <?php echo htmlspecialchars($product['khuyen_mai'] ?? 'Không có'); ?></p>
                        <p><strong>Mô tả:</strong> <?php echo htmlspecialchars($product['mota'] ?? 'Không có'); ?></p>
                        <img src="images/<?php echo htmlspecialchars($product['hinhanh']); ?>" alt="<?php echo htmlspecialchars($product['ten']); ?>" class="w-32 rounded mt-4">
                    </div>
                <?php endif; ?>
            <?php } ?>

            <!-- Chỉnh sửa sản phẩm -->
            <?php if (isset($_GET['edit_product_id'])) {
                $product_id = intval($_GET['edit_product_id']);
                $stmt = $conn->prepare("SELECT * FROM dienthoai WHERE id = ?");
                if (!$stmt) {
                    die("Lỗi chuẩn bị truy vấn chỉnh sửa sản phẩm: " . $conn->error);
                }
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                if ($product): ?>
                    <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
                        <h3 class="text-xl font-semibold text-yellow-400 mb-4">Chỉnh sửa sản phẩm #<?php echo $product_id; ?></h3>
                        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <input type="hidden" name="update_product" value="1">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['hinhanh']); ?>">
                            <input type="text" name="ten" value="<?php echo htmlspecialchars($product['ten']); ?>" required class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                            <input type="number" step="0.01" name="gia" value="<?php echo $product['gia']; ?>" required class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                            <select name="category_id" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                                <?php
                                $cats = $conn->query("SELECT * FROM categories");
                                if ($cats) {
                                    while ($cat = $cats->fetch_assoc()) {
                                        $selected = $cat['id'] == $product['category_id'] ? 'selected' : '';
                                        echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
                                    }
                                } else {
                                    echo "<option value='1'>Không có loại sản phẩm</option>";
                                }
                                ?>
                            </select>
                            <input type="text" name="thuong_hieu" value="<?php echo htmlspecialchars($product['thuong_hieu']); ?>" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                            <input type="text" name="man_hinh" value="<?php echo htmlspecialchars($product['man_hinh']); ?>" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                            <input type="text" name="camera" value="<?php echo htmlspecialchars($product['camera']); ?>" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                            <select name="ram" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                                <option value="" <?php echo !$product['ram'] ? 'selected' : ''; ?>>Chọn RAM</option>
                                <option value="4GB" <?php echo $product['ram'] == '4GB' ? 'selected' : ''; ?>>4GB</option>
                                <option value="6GB" <?php echo $product['ram'] == '6GB' ? 'selected' : ''; ?>>6GB</option>
                                <option value="8GB" <?php echo $product['ram'] == '8GB' ? 'selected' : ''; ?>>8GB</option>
                                <option value="12GB" <?php echo $product['ram'] == '12GB' ? 'selected' : ''; ?>>12GB</option>
                            </select>
                            <select name="rom" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                                <option value="" <?php echo !$product['rom'] ? 'selected' : ''; ?>>Chọn ROM</option>
                                <option value="64GB" <?php echo $product['rom'] == '64GB' ? 'selected' : ''; ?>>64GB</option>
                                <option value="128GB" <?php echo $product['rom'] == '128GB' ? 'selected' : ''; ?>>128GB</option>
                                <option value="256GB" <?php echo $product['rom'] == '256GB' ? 'selected' : ''; ?>>256GB</option>
                                <option value="512GB" <?php echo $product['rom'] == '512GB' ? 'selected' : ''; ?>>512GB</option>
                                <option value="1TB" <?php echo $product['rom'] == '1TB' ? 'selected' : ''; ?>>1TB</option>
                            </select>
                            <input type="text" name="pin" value="<?php echo htmlspecialchars($product['pin']); ?>" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                            <input type="text" name="khuyen_mai" value="<?php echo htmlspecialchars($product['khuyen_mai']); ?>" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                            <textarea name="mota" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white col-span-2"><?php echo htmlspecialchars($product['mota']); ?></textarea>
                            <input type="file" name="hinhanh" accept="image/*" class="p-2 bg-gray-700 rounded-lg col-span-2 text-white">
                            <button type="submit" class="bg-yellow-400 text-black px-6 py-2 rounded hover:bg-yellow-500 transition col-span-2">Cập nhật sản phẩm</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php } ?>
        </div>

        <!-- Category Management -->
        <div id="categories" class="tab-content">
            <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
                <h2 class="text-2xl font-semibold text-yellow-400 mb-4">Quản lý loại sản phẩm</h2>
                <form method="POST" class="grid grid-cols-1 gap-4 mb-6">
                    <input type="hidden" name="add_category" value="1">
                    <input type="text" name="name" placeholder="Tên loại sản phẩm" required class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                    <textarea name="description" placeholder="Mô tả" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white"></textarea>
                    <button type="submit" class="bg-yellow-400 text-black px-6 py-2 rounded hover:bg-yellow-500 transition">Thêm loại</button>
                </form>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead><tr class="bg-gray-700"><th class="p-2">ID</th><th class="p-2">Tên</th><th class="p-2">Mô tả</th><th class="p-2">Hành động</th></tr></thead>
                        <tbody>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <tr class="border-b border-gray-700">
                                    <td class="p-2"><?php echo $category['id']; ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($category['description'] ?? 'N/A'); ?></td>
                                    <td class="p-2 space-x-2">
                                        <a href="admin.php?view_category_id=<?php echo $category['id']; ?>&tab=categories" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 transition">Xem</a>
                                        <a href="admin.php?edit_category_id=<?php echo $category['id']; ?>&tab=categories" class="bg-green-600 text-white px-4 py-1 rounded hover:bg-green-700 transition">Sửa</a>
                                        <a href="admin.php?delete_category_id=<?php echo $category['id']; ?>" onclick="return confirm('Xác nhận xóa?')" class="bg-red-600 text-white px-4 py-1 rounded hover:bg-red-700 transition">Xóa</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Xem chi tiết loại sản phẩm -->
            <?php if (isset($_GET['view_category_id'])) {
                $category_id = intval($_GET['view_category_id']);
                $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
                if (!$stmt) {
                    die("Lỗi chuẩn bị truy vấn chi tiết loại sản phẩm: " . $conn->error);
                }
                $stmt->bind_param("i", $category_id);
                $stmt->execute();
                $category = $stmt->get_result()->fetch_assoc();
                if ($category): ?>
                    <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
                        <h3 class="text-xl font-semibold text-yellow-400 mb-4">Chi tiết loại sản phẩm #<?php echo $category_id; ?></h3>
                        <p><strong>Tên:</strong> <?php echo htmlspecialchars($category['name'] ?? 'N/A'); ?></p>
                        <p><strong>Mô tả:</strong> <?php echo htmlspecialchars($category['description'] ?? 'Không có'); ?></p>
                    </div>
                <?php endif; ?>
            <?php } ?>

            <!-- Chỉnh sửa loại sản phẩm -->
            <?php if (isset($_GET['edit_category_id'])) {
                $category_id = intval($_GET['edit_category_id']);
                $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
                if (!$stmt) {
                    die("Lỗi chuẩn bị truy vấn chỉnh sửa loại sản phẩm: " . $conn->error);
                }
                $stmt->bind_param("i", $category_id);
                $stmt->execute();
                $category = $stmt->get_result()->fetch_assoc();
                if ($category): ?>
                    <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
                        <h3 class="text-xl font-semibold text-yellow-400 mb-4">Chỉnh sửa loại sản phẩm #<?php echo $category_id; ?></h3>
                        <form method="POST" class="grid grid-cols-1 gap-4 mb-6">
                            <input type="hidden" name="update_category" value="1">
                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                            <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                            <textarea name="description" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                            <button type="submit" class="bg-yellow-400 text-black px-6 py-2 rounded hover:bg-yellow-500 transition">Cập nhật loại sản phẩm</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php } ?>
        </div>

        <!-- User Management -->
        <div id="users" class="tab-content">
            <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
                <h2 class="text-2xl font-semibold text-yellow-400 mb-4">Quản lý tài khoản</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead><tr class="bg-gray-700"><th class="p-2">ID</th><th class="p-2">Tên đăng nhập</th><th class="p-2">Quyền</th><th class="p-2">Hành động</th></tr></thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr class="border-b border-gray-700">
                                    <td class="p-2"><?php echo $user['id']; ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="p-2"><?php echo $user['role'] == 1 ? 'Admin' : 'User'; ?></td>
                                    <td class="p-2 space-x-2">
                                        <a href="admin.php?view_user_id=<?php echo $user['id']; ?>&tab=users" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 transition">Xem</a>
                                        <a href="admin.php?edit_user_id=<?php echo $user['id']; ?>&tab=users" class="bg-green-600 text-white px-4 py-1 rounded hover:bg-green-700 transition">Sửa</a>
                                        <a href="admin.php?delete_user_id=<?php echo $user['id']; ?>" onclick="return confirm('Xác nhận xóa?')" class="bg-red-600 text-white px-4 py-1 rounded hover:bg-red-700 transition">Xóa</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Xem chi tiết tài khoản -->
            <?php if (isset($_GET['view_user_id'])) {
                $user_id = intval($_GET['view_user_id']);
                $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
                if (!$stmt) {
                    die("Lỗi chuẩn bị truy vấn chi tiết tài khoản: " . $conn->error);
                }
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                if ($user): ?>
                    <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
                        <h3 class="text-xl font-semibold text-yellow-400 mb-4">Chi tiết tài khoản #<?php echo $user_id; ?></h3>
                        <p><strong>ID:</strong> <?php echo htmlspecialchars($user['id'] ?? 'N/A'); ?></p>
                        <p><strong>Tên đăng nhập:</strong> <?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></p>
                        <p><strong>Quyền:</strong> <?php echo $user['role'] == 1 ? 'Admin' : 'User'; ?></p>
                    </div>
                <?php endif; ?>
            <?php } ?>

            <!-- Chỉnh sửa tài khoản -->
            <?php if (isset($_GET['edit_user_id'])) {
                $user_id = intval($_GET['edit_user_id']);
                $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
                if (!$stmt) {
                    die("Lỗi chuẩn bị truy vấn chỉnh sửa tài khoản: " . $conn->error);
                }
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                if ($user): ?>
                    <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
                        <h3 class="text-xl font-semibold text-yellow-400 mb-4">Chỉnh sửa tài khoản #<?php echo $user_id; ?></h3>
                        <form method="POST" class="grid grid-cols-1 gap-4 mb-6">
                            <input type="hidden" name="update_user" value="1">
                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                            <select name="role" class="p-2 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 text-white">
                                <option value="1" <?php echo $user['role'] == 1 ? 'selected' : ''; ?>>Admin</option>
                                <option value="0" <?php echo $user['role'] == 0 ? 'selected' : ''; ?>>User</option>
                            </select>
                            <button type="submit" class="bg-yellow-400 text-black px-6 py-2 rounded hover:bg-yellow-500 transition">Cập nhật tài khoản</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php } ?>
        </div>

        <!-- Order Management -->
        <div id="orders" class="tab-content">
            <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
                <h2 class="text-2xl font-semibold text-yellow-400 mb-4">Quản lý đơn hàng</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead><tr class="bg-gray-700"><th class="p-2">ID</th><th class="p-2">Người dùng</th><th class="p-2">Tổng</th><th class="p-2">Ngày đặt</th><th class="p-2">Trạng thái</th><th class="p-2">Hành động</th></tr></thead>
                        <tbody>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <tr class="border-b border-gray-700">
                                    <td class="p-2"><?php echo $order['id']; ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td class="p-2"><?php echo number_format($order['total'], 0, ',', '.'); ?>₫</td>
                                    <td class="p-2"><?php echo $order['ngay_dat']; ?></td>
                                    <td class="p-2"><?php echo $order['payment_status']; ?></td>
                                    <td class="p-2"><a href="admin.php?view_order_id=<?php echo $order['id']; ?>&tab=orders" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 transition">Xem chi tiết</a></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if (isset($_GET['view_order_id'])) {
                $order_id = intval($_GET['view_order_id']);
                $order_details = $conn->prepare("SELECT od.*, d.ten FROM order_details od JOIN dienthoai d ON od.product_id = d.id WHERE od.order_id = ?");
                if (!$order_details) {
                    die("Lỗi chuẩn bị truy vấn chi tiết đơn hàng: " . $conn->error);
                }
                $order_details->bind_param("i", $order_id);
                $order_details->execute();
                $order_details_result = $order_details->get_result();

                $order = $conn->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
                if (!$order) {
                    die("Lỗi chuẩn bị truy vấn đơn hàng: " . $conn->error);
                }
                $order->bind_param("i", $order_id);
                $order->execute();
                $order_result = $order->get_result()->fetch_assoc();
                if ($order_result): ?>
                    <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 fade-in">
                        <h3 class="text-xl font-semibold text-yellow-400 mb-4">Chi tiết đơn hàng #<?php echo $order_id; ?></h3>
                        <p><strong>Người dùng:</strong> <?php echo htmlspecialchars($order_result['username'] ?? 'N/A'); ?></p>
                        <p><strong>Tên khách hàng:</strong> <?php echo htmlspecialchars($order_result['customer_name'] ?? 'N/A'); ?></p>
                        <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order_result['shipping_address'] ?? 'N/A'); ?></p>
                        <p><strong>SĐT:</strong> <?php echo htmlspecialchars($order_result['phone'] ?? 'N/A'); ?></p>
                        <p><strong>SĐT khách hàng:</strong> <?php echo htmlspecialchars($order_result['customer_phone'] ?? 'N/A'); ?></p>
                        <p><strong>Phương thức:</strong> <?php echo htmlspecialchars($order_result['payment_method'] ?? 'N/A'); ?></p>
                        <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order_result['payment_status'] ?? 'N/A'); ?></p>
                        <p><strong>Tổng:</strong> <?php echo number_format($order_result['total'] ?? 0, 0, ',', '.'); ?>₫</p>
                        <p><strong>Ngày đặt:</strong> <?php echo htmlspecialchars($order_result['ngay_dat'] ?? 'N/A'); ?></p>
                        <table class="w-full text-left mt-4">
                            <thead><tr class="bg-gray-700"><th class="p-2">Sản phẩm</th><th class="p-2">Số lượng</th><th class="p-2">Giá</th><th class="p-2">Tổng</th><th class="p-2">Hành động</th></tr></thead>
                            <tbody>
                                <?php while ($detail = $order_details_result->fetch_assoc()): ?>
                                    <tr class="border-b border-gray-700">
                                        <td class="p-2"><?php echo htmlspecialchars($detail['ten'] ?? 'N/A'); ?></td>
                                        <td class="p-2">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                                <input type="hidden" name="product_id" value="<?php echo $detail['product_id']; ?>">
                                                <input type="number" name="quantity" value="<?php echo $detail['quantity'] ?? 0; ?>" min="0" class="p-1 bg-gray-700 rounded-lg w-16">
                                                <button type="submit" name="update_quantity" class="bg-yellow-400 text-black px-2 py-1 rounded hover:bg-yellow-500 transition">Cập nhật</button>
                                            </form>
                                        </td>
                                        <td class="p-2"><?php echo number_format($detail['price'] ?? 0, 0, ',', '.'); ?>₫</td>
                                        <td class="p-2"><?php echo number_format(($detail['price'] ?? 0) * ($detail['quantity'] ?? 0), 0, ',', '.'); ?>₫</td>
                                        <td class="p-2"></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php } ?>
        </div>

        <!-- Review Management -->
        <div id="reviews" class="tab-content">
            <div class="bg-gray-800 rounded-lg shadow-lg p-6 fade-in">
                <h2 class="text-2xl font-semibold text-yellow-400 mb-4">Quản lý đánh giá</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead><tr class="bg-gray-700"><th class="p-2">ID</th><th class="p-2">Điện thoại</th><th class="p-2">Người dùng</th><th class="p-2">Sao</th><th class="p-2">Bình luận</th><th class="p-2">Trạng thái</th><th class="p-2">Ngày tạo</th><th class="p-2">Hành động</th></tr></thead>
                        <tbody>
                            <?php while ($review = $reviews->fetch_assoc()): ?>
                                <tr class="border-b border-gray-700">
                                    <td class="p-2"><?php echo $review['id']; ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($review['product_name'] ?? 'N/A'); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($review['username'] ?? 'N/A'); ?></td>
                                    <td class="p-2"><?php echo $review['rating'] ?? 'N/A'; ?> sao</td>
                                    <td class="p-2"><?php echo htmlspecialchars($review['comment'] ?? 'N/A'); ?></td>
                                    <td class="p-2"><?php echo $review['status'] == 'pending' ? 'Chờ duyệt' : ($review['status'] == 'approved' ? 'Đã duyệt' : 'Bị từ chối'); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($review['created_at'] ?? 'N/A'); ?></td>
                                    <td class="p-2">
                                        <?php if ($review['status'] == 'pending'): ?>
                                            <a href="admin.php?review_id=<?php echo $review['id']; ?>&status=approve&tab=reviews" class="bg-green-600 text-white px-4 py-1 rounded hover:bg-green-700 transition mr-2">Duyệt</a>
                                            <a href="admin.php?review_id=<?php echo $review['id']; ?>&status=reject&tab=reviews" class="bg-red-600 text-white px-4 py-1 rounded hover:bg-red-700 transition">Từ chối</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-center py-4">
        <p class="text-yellow-400">© 2025 Shop Bán Điện Thoại</p>
    </footer>

    <script>
        function openTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
        }
        // Mở tab mặc định
        window.onload = function() {
            openTab('<?php echo isset($_GET['tab']) ? $_GET['tab'] : 'products'; ?>');
        };
    </script>
</body>
</html>
<?php $conn->close(); ?>