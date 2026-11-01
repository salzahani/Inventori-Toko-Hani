<?php
require 'koneksi.php';
session_start();

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT u.*, r.nama_role FROM user u JOIN role r ON u.idrole = r.idrole WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    $cocok = false;
    if ($user) {
        $cocok = password_get_info($user['password_user'])['algo']
            ? password_verify($password, $user['password_user'])
            : hash_equals($user['password_user'], $password);
    }

    if ($cocok && in_array($user['nama_role'], ['admin', 'kasir'], true)) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['iduser'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['nama_role'];
        header("Location: " . ($user['nama_role'] === 'admin' ? 'admin.php' : 'kasir.php'));
        exit;
    } elseif ($cocok) {
        $error = "Akses role belum didukung!";
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Login - Toko Hani</title>
</head>
<body>
<h2>Login Sistem Inventori - Toko Hani</h2>

<?php if ($error) { ?>
    <p><b><?= htmlspecialchars($error) ?></b></p>
<?php } ?>

<form method="post">
    <p>Username:<br>
    <input type="text" name="username" required></p>

    <p>Password:<br>
    <input type="password" name="password" required></p>

    <button type="submit" name="login">Login</button>
</form>
</body>
</html>