<?php
require 'koneksi.php';
require 'master.php';
require 'auth.php';
wajib_role('admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Sistem Inventori - Toko Hani</title>
</head>
<body>
<h2>Sistem Inventori - Toko Hani</h2>
<p>Login sebagai <b><?= htmlspecialchars($_SESSION['username']) ?></b> | <a href="admin.php">Dashboard Admin</a> | <a href="logout.php">Logout</a></p>

<h3>Data Master (CRUD)</h3>
<ul>
<?php foreach ($master as $kunci => $m) { ?>
    <li><a href="crud.php?t=<?= $kunci ?>"><?= $m['judul'] ?></a></li>
<?php } ?>
</ul>

<h3>Daftar View</h3>
<ul>
<?php foreach ($daftar_view as $nama => $judul) { ?>
    <li><a href="tampil.php?v=<?= $nama ?>"><?= $judul ?></a> (<?= $nama ?>)</li>
<?php } ?>
</ul>
</body>
</html>