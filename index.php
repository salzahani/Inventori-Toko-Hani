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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sistem Inventori - Toko Hani</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
<h2>Sistem Inventori - Toko Hani</h2>
<p class="nav">Login sebagai <b><?= htmlspecialchars($_SESSION['username']) ?></b> | <a href="admin.php">Dashboard Admin</a> | <a href="logout.php">Logout</a></p>

<h3>Data Master (CRUD)</h3>
<ul class="daftar">
<?php foreach ($master as $kunci => $m) { ?>
    <li><a href="crud.php?t=<?= $kunci ?>"><?= $m['judul'] ?></a></li>
<?php } ?>
</ul>

<h3>Daftar View</h3>
<ul class="daftar">
<?php foreach ($daftar_view as $nama => $judul) { ?>
    <li><a href="tampil.php?v=<?= $nama ?>"><?= $judul ?></a> (<?= $nama ?>)</li>
<?php } ?>
</ul>
</div>
</body>
</html>