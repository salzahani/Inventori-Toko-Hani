<?php
require 'koneksi.php';
require 'auth.php';
wajib_role('kasir');

function rp($angka){
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
} else {
    $id = 0;
}

$stmt = $pdo->prepare('SELECT * FROM v_detail_penerimaan WHERE idpengadaan = ? ORDER BY idpenerimaan, nama_barang');
$stmt->execute([$id]);
$baris = $stmt->fetchAll();
$total = 0;
foreach ($baris as $b) {
    $total = $total + $b['sub_total_terima'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Detail Penerimaan - Toko Hani</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
<h2>Penerimaan Barang</h2>
<p class="nav"><a href="admin.php">&laquo; Dashboard</a> | <a href="pengadaan.php">Pengadaan</a> | <a href="logout.php">Logout</a></p>
<p><a href="penerimaan.php">&laquo; Kembali ke daftar</a></p>

<h3>Detail penerimaan - Pengadaan <?= $id ?></h3>
<?php if (!$baris) { ?>
    <p class="pesan">Belum ada penerimaan</p>
<?php } else { ?>
    <table class="tabel">
        <tr><th>ID penerimaan</th><th>Tanggal terima</th><th>Barang</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th></tr>
        <?php foreach ($baris as $b) { ?>
        <tr>
            <td><?= $b['idpenerimaan'] ?></td>
                <td><?= htmlspecialchars($b['tgl_terima']) ?></td>
                <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                <td><?= (int) $b['jumlah_terima'] ?></td>
                <td><?= rp($b['harga_satuan_terima']) ?></td>
                <td><?= rp($b['sub_total_terima']) ?></td>
        </tr>
        <?php } ?>
        <tr><th colspan="5">Total barang diterima</th><th><?= rp($total) ?></th></tr>
    </table>
<?php } ?>
</div>
</body>
</html>