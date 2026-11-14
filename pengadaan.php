<?php
require 'koneksi.php';
require 'auth.php';
wajib_role('admin');


if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'];
    if ($aksi === 'tambah') {
        $id = (int) $_POST['idbarang'];
        $_SESSION['keranjang'][$id] = [
            'jumlah' => (int) $_POST['jumlah'],
            'harga'  => (int) $_POST['harga'], ];
    } elseif ($aksi === 'hapus') {
        unset($_SESSION['keranjang'][(int) $_POST['idbarang']]);
    } elseif ($aksi === 'simpan') {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO pengadaan (user_iduser, vendor_idvendor) VALUES (?, ?)');
            $stmt->execute([$_SESSION['user_id'], (int) $_POST['idvendor']]);
            $idpengadaan = $pdo->lastInsertId();
        
            $stmt = $pdo->prepare('INSERT INTO detail_pengadaan (harga_satuan, jumlah, sub_total, idbarang, idpengadaan) VALUES (?, ?, ?, ?, ?)');
            foreach ($_SESSION['keranjang'] as $id => $item) {
                $stmt->execute([$item['harga'], $item['jumlah'], $item['harga'] * $item['jumlah'], $id, $idpengadaan]);
        }
        $pdo->commit();
            $_SESSION['keranjang'] = [];
            $_SESSION['pesan'] = "Pengadaan #$idpengadaan berhasil disimpan.";
        } catch (Exception $error) {
            $pdo->rollBack();
            $_SESSION['pesan'] = 'Gagal menyimpan: ' . $error->getMessage();
        }
    }
    header('Location: pengadaan.php');
    exit;
}

$vendor  = $pdo->query('SELECT idvendor, nama_vendor FROM v_vendor_aktif')->fetchAll();
$barang  = $pdo->query("SELECT idbarang, nama_barang, harga FROM v_barang_lengkap WHERE status = 'Aktif' COLLATE utf8mb4_general_ci")->fetchAll();
$nama    = array_column($barang, 'nama_barang', 'idbarang');
$riwayat = $pdo->query('SELECT * FROM v_pengadaan_detail ORDER BY idpengadaan DESC LIMIT 30')->fetchAll();

$pesan = '';
if (isset($_SESSION['pesan'])) {
    $pesan = $_SESSION['pesan'];
    unset($_SESSION['pesan']);
}
?>
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pengadaan - Toko Hani</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
<h2>Pengadaan</h2>
<p class="nav"><a href="admin.php">&laquo; Dashboard</a> | <a href="logout.php">Logout</a></p>
<?php if ($pesan !== '') { ?>
    <p class="pesan"><?= htmlspecialchars($pesan) ?></p>
<?php } ?>

<h3>Tambah barang</h3>
<form method="post">
    <input type="hidden" name="aksi" value="tambah">
    <select name="idbarang" required>
        <option value="">-- Pilih barang --</option>
        <?php foreach ($barang as $b) { ?>
            <option value="<?= $b['idbarang'] ?>"><?= htmlspecialchars($b['nama_barang']) ?></option>
        <?php } ?>
    </select>
    Jumlah <input type="number" name="jumlah" min="1" value="1" required>
    Harga beli <input type="number" name="harga" min="1" required>
    <button>Tambah</button>
</form>

<h3>Daftar pesanan</h3>
<?php if (!$_SESSION['keranjang']) { ?>
    <p>Belum ada barang.</p>
<?php } else { ?>
    <table class="tabel">
        <tr><th>Barang</th><th>Harga beli</th><th>Jumlah</th><th>Subtotal</th><th></th></tr>
        <?php $total = 0; ?>
        <?php foreach ($_SESSION['keranjang'] as $id => $item) { ?>
            <?php $total += $item['harga'] * $item['jumlah']; ?>
            <tr>
                <td><?= htmlspecialchars($nama[$id]) ?></td>
                <td><?= $item['harga'] ?></td>
                <td><?= $item['jumlah'] ?></td>
                <td><?= $item['harga'] * $item['jumlah'] ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="idbarang" value="<?= $id ?>">
                        <button>Hapus</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        <tr><th colspan="3">Total (belum PPN)</th><th><?= $total ?></th><td></td></tr>
    </table>

    <form method="post">
        <input type="hidden" name="aksi" value="simpan">
        Vendor
        <select name="idvendor" required>
            <option value="">-- Pilih vendor --</option>
            <?php foreach ($vendor as $v) { ?>
                <option value="<?= $v['idvendor'] ?>"><?= htmlspecialchars($v['nama_vendor']) ?></option>
            <?php } ?>
        </select>
        <button>Simpan Pengadaan</button>
    </form>
<?php } ?>

<h3>Riwayat pengadaan</h3>
<table class="tabel">
    <tr><th>No</th><th>Vendor</th><th>Status</th><th>Barang</th><th>Jumlah</th><th>Harga</th><th>Total (+PPN)</th></tr>
    <?php foreach ($riwayat as $r) { ?>
        <tr>
            <td><?= $r['idpengadaan'] ?></td>
            <td><?= htmlspecialchars($r['nama_vendor']) ?></td>
            <td><?= $r['status_pengadaan'] ?></td>
            <td><?= htmlspecialchars($r['nama_barang']) ?></td>
            <td><?= $r['jumlah'] ?></td>
            <td><?= $r['harga_satuan'] ?></td>
            <td><?= $r['total_nilai'] ?></td>
        </tr>
    <?php } ?>
</table>
</div>
</body>
</html>
