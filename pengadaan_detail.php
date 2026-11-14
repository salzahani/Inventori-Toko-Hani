<?php
require 'koneksi.php';
require 'auth.php';
wajib_role('admin');

function rp($angka){
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
} else {
    $id = 0;
}

$stmt = $pdo->prepare('SELECT * FROM v_pengadaan_detail WHERE idpengadaan = ?');
$stmt->execute([$id]);
$baris = $stmt->fetchAll();
$ada = count($baris) > 0;
if($ada) {
    $info = $baris[0];
    $subtotal = 0;
    foreach ($baris as $b) {
        $subtotal = $subtotal + $b['sub_total'];
    }
    $total = $info['total_nilai'];
}
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Detail Pengadaan</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="wrap">
        <?php if (!$ada) { ?>
            <h2>Detail Pengadaan</h2>
            <p class="pesan">Pengadaan tidak ditemukan.</p>
            <p><a href="pengadaan.php">&laquo; Kembali ke daftar</a></p>
        <?php } else { ?>
            <h2>Detail Pengadaan #<?= $info['idpengadaan'] ?></h2>
            <p class="nav"><a href="pengadaan.php">&laquo; Kembali ke daftar</a> | <a href="logout.php">Logout</a></p>
            <p>
                Tanggal: <b><?= htmlspecialchars($info['tgl_pengadaan']) ?></b><br>
                Vendor: <b><?= htmlspecialchars($info['nama_vendor']) ?></b><br>
                Dibuat oleh: <b><?= htmlspecialchars($info['pemesan']) ?></b><br>
                Status: <b><?= htmlspecialchars($info['status_pengadaan']) ?></b>
            </p>
            <table class="tabel">
                <tr><th>No</th><th>Nama barang</th><th>Satuan</th><th>Harga satuan</th><th>Jumlah</th><th>Subtotal</th></tr>
                <?php $no = 1; ?>
                <?php foreach ($baris as $b) { ?>
                    <tr>
                        <td><?= $no ?></td>
                        <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                        <td><?= htmlspecialchars($b['nama_satuan']) ?></td>
                        <td><?= rp($b['harga_satuan']) ?></td>
                        <td><?= $b['jumlah'] ?></td>
                        <td><?= rp($b['sub_total']) ?></td>
                    </tr>
                    <?php $no = $no + 1; ?>   
                <?php } ?>
                <tr><th colspan="5">Subtotal</th><th><?= rp($subtotal) ?></th></tr>
                <tr><th colspan="5">PPN</th><th><?= rp($total - $subtotal) ?></th></tr>
                <tr><th colspan="5">Total nilai akhir</th><th><?= rp($total) ?></th></tr>
             </table>
        <?php } ?>
        </div>
    </body>
</html>