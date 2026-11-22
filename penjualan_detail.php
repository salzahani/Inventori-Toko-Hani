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

$stmt = $pdo->prepare('SELECT * FROM v_penjualan_detail WHERE idpenjualan = ?');
$stmt->execute([$id]);
$baris = $stmt->fetchAll();
$ada = count($baris) > 0;
if($ada) {
    $info = $baris[0];
    $subtotal = 0;
    foreach ($baris as $b) {
        $subtotal = $subtotal + $b['subtotal'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Detail Penjualan - Toko Hani</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
<h2>Penjualan</h2>
<p class="nav">Selamat datang, <b><?= htmlspecialchars($_SESSION['username']) ?></b> | <a href="logout.php">Logout</a></p>
<p><a href="penjualan.php">&laquo; Kembali ke daftar</a></p>

<?php if (!$ada) { ?>
    <p class="pesan">Penjualan tidak ditemukan</p>
<?php } else { ?>
    <h3>Detail penjualan <?= $info['idpenjualan'] ?></h3>
    <p>
        Tanggal: <b><?= htmlspecialchars($info['tgl_jual']) ?></b><br>
        Kasir: <b><?= htmlspecialchars($info['kasir']) ?></b><br>
        Margin penjualan: <b><?= htmlspecialchars($info['margin_persen']) ?>%</b>
    </p>

    <table class="tabel">
        <tr><th>No</th><th>Nama barang</th><th>Harga satuan</th><th>Jumlah</th><th>Subtotal</th></tr>
        <?php $no = 1; ?>
        <?php foreach ($baris as $b) { ?>
            <tr>
                <td><?= $no ?></td>
                <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                <td><?= rp($b['harga_satuan']) ?></td>
                <td><?= (int) $b['jumlah'] ?></td>
                <td><?= rp($b['subtotal']) ?></td>
            </tr>
            <?php $no = $no + 1; ?>
        <?php } ?>
        <tr><th colspan="4">Subtotal</th><th><?= rp($subtotal) ?></th></tr>
        <tr><th colspan="4">PPN</th><th><?= rp($info['ppn']) ?></th></tr>
        <tr><th colspan="4">Total nilai akhir</th><th><?= rp($info['total_nilai']) ?></th></tr>
    </table>
<?php } ?>
</div>
</body>
</html>