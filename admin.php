<?php
require 'koneksi.php';
require 'auth.php';
wajib_role('admin');

$pesan = "";

if (isset($_POST['tambah_barang'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO barang (jenis, nama, idsatuan, harga) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['jenis'], trim($_POST['nama']), (int) $_POST['idsatuan'], $_POST['harga']]);
        $pesan = "Barang berhasil disimpan!";
    } catch (PDOException $e) {
        $pesan = "Gagal menyimpan: " . $e->getMessage();
    }
}

$stokList = $pdo->query("SELECT * FROM v_stok_terkini")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Halaman Admin - Toko Hani</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
<h2>Admin Control Panel - Toko Hani</h2>
<p class="nav">Selamat datang, <b><?= htmlspecialchars($_SESSION['username']) ?></b> |
<a href="pengadaan.php">Pengadaan</a> |
<a href="index.php">Data Master (CRUD)</a> |
<a href="logout.php">Logout</a></p>

<?php if ($pesan) { ?>
    <p class="pesan"><?= htmlspecialchars($pesan) ?></p>
<?php } ?>

<h3>Tambah Master Barang</h3>
<form method="post">
    <p>Nama Barang:<br>
    <input type="text" name="nama" required></p>

    <p>Jenis:<br>
    <select name="jenis" required>
        <option value="S">Sembako</option>
        <option value="M">Minuman</option>
        <option value="K">Kebersihan</option>
    </select></p>

    <p>ID Satuan:<br>
    <input type="number" name="idsatuan" required></p>

    <p>Harga Beli:<br>
    <input type="number" name="harga" min="0" required></p>

    <button type="submit" name="tambah_barang">Simpan Barang</button>
</form>

<h3>Stok Barang Terkini</h3>
<table class="tabel">
    <tr>
        <th>ID Barang</th>
        <th>Nama Barang</th>
        <th>Satuan</th>
        <th>Stok Available</th>
    </tr>
    <?php foreach ($stokList as $s) { ?>
        <tr>
            <td><?= (int) $s['idbarang'] ?></td>
            <td><?= htmlspecialchars($s['nama_barang']) ?></td>
            <td><?= htmlspecialchars($s['nama_satuan']) ?></td>
            <td><?= htmlspecialchars($s['stok']) ?></td>
        </tr>
    <?php } ?>
    <?php if (!$stokList) { ?>
        <tr><td colspan="4">Belum ada data.</td></tr>
    <?php } ?>
</table>
</div>
</body>
</html>