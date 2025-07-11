<?php
session_start();
require_once '../db.php';
ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Lưu session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = (int) $user['role']; // ép role thành số nguyên

                    $stmt->close();
                    $conn->close();
                    ob_end_clean(); // Clear buffer

                    // Chuyển hướng dựa trên role
                    if ($_SESSION['role'] === 1) {
                        header("Location: ../admin.php");
                    } else {
                        header("Location: ../index.php");
                    }
                    exit();
                }
            }
            $stmt->close();
        }
        // Sai tài khoản hoặc mật khẩu
        $_SESSION['error'] = "Tên đăng nhập hoặc mật khẩu không đúng.";
    } else {
        $_SESSION['error'] = "Vui lòng nhập đầy đủ thông tin.";
    }
}

$conn->close();
ob_end_flush();
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-flex">
                <h1>Đăng Nhập</h1>
                <div class="header-actions">
                    <a href="../index.php" class="btn">Quay về</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Tên đăng nhập" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            <input type="password" name="password" placeholder="Mật khẩu" required>
            <button type="submit">Đăng Nhập</button>

            <?php 
            if (isset($_SESSION['error'])) {
                echo '<p class="error">'.htmlspecialchars($_SESSION['error']).'</p>';
                unset($_SESSION['error']);
            }
            ?>

            <p>Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
        </form>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© 2025 Shop Bán Điện Thoại. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
