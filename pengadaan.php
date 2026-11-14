<?php
require 'koneksi.php';
require 'auth.php';
wajib_role('admin');

function rp($angka){
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'];
    if ($aksi === 'tambah') {
        $id = (int) $_POST['idbarang'];
        $jumlah = (int) $_POST['jumlah'];
        if ($id < 1 || $jumlah < 1) {
            $_SESSION['pesan'] = 'Pilih barang dan isi jumlah minimal 1.';
        } else {
            if(isset( $_SESSION['keranjang'][$id])) {
                $_SESSION['keranjang'][$id] = $_SESSION['keranjang'][$id] + $jumlah;
            } else {
                $_SESSION['keranjang'][$id] = $jumlah;
            }
        }
        
    } elseif ($aksi === 'hapus') {
        unset($_SESSION['keranjang'][(int) $_POST['idbarang']]);
    } elseif ($aksi === 'simpan') {
        $idvendor = (int) $_POST['idvendor'];
        if (!$_SESSION['keranjang']) {
            $_SESSION['pesan'] = 'Daftar barang masih kosong.';
        } elseif ($idvendor < 1) {
            $_SESSION['pesan'] = 'Pilih vendor terlebih dahulu.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare('SELECT 1 FROM v_vendor_aktif WHERE idvendor = ?');
                $stmt->execute([$idvendor]);
                if (!$stmt->fetchColumn()){
                    throw new Exception('Vendor tidak ditemukan atau tidak aktif');
                }

                $stmt = $pdo->prepare('INSERT INTO pengadaan (user_iduser, vendor_idvendor) VALUES (?, ?)');
                $stmt->execute([$_SESSION['user_id'], $idvendor]);
                $idpengadaan = $pdo->lastInsertId();
                
                $cari_harga = $pdo->prepare('SELECT harga FROM barang WHERE idbarang = ? AND status_barang = 1');
                $simpan = $pdo->prepare('INSERT INTO detail_pengadaan (harga_satuan, jumlah, sub_total, idbarang, idpengadaan) VALUES (?, ?, ?, ?, ?)');
                foreach ($_SESSION['keranjang'] as $id => $jumlah) {
                    $cari_harga->execute([$id]);
                    $harga = $cari_harga->fetchColumn();
                    if ($harga === false || $harga < 1) {
                        throw new Exception('Ada barang yang tidak ditemukan atau tidak aktif.');
                    }
                    $simpan->execute([$harga, $jumlah, $harga * $jumlah, $id, $idpengadaan]);
            }
            $pdo->commit();
                $_SESSION['keranjang'] = [];
                $_SESSION['pesan'] = "Pengadaan #$idpengadaan berhasil disimpan.";
            } catch (Exception $error) {
                $pdo->rollBack();
                $_SESSION['pesan'] = 'Gagal menyimpan: ' . $error->getMessage();
            }
        }
    }
    header('Location: pengadaan.php');
    exit;
}

$vendor = $pdo->query('SELECT idvendor, nama_vendor FROM v_vendor_aktif')->fetchAll();
$barang = $pdo->query("SELECT idbarang, nama_barang, nama_satuan, harga FROM v_barang_lengkap WHERE status = 'Aktif' COLLATE utf8mb4_general_ci")->fetchAll();
$nama_barang = array_column($barang, 'nama_barang', 'idbarang');
$harga_barang = array_column($barang, 'harga', 'idbarang');
$riwayat = $pdo->query(
    'SELECT idpengadaan, tgl_pengadaan, nama_vendor, pemesan, status_pengadaan, SUM(sub_total) AS subtotal, MAX(total_nilai) AS total
    from v_pengadaan_detail
    group by idpengadaan, tgl_pengadaan, nama_vendor, pemesan, status_pengadaan
    order by idpengadaan desc limit 30')->fetchAll();

$pesan = '';
if (isset($_SESSION['pesan'])) {
    $pesan = $_SESSION['pesan'];
    unset($_SESSION['pesan']);
}
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

<h3>Buat pengadaan baru</h3>
<form method="post">
    <input type="hidden" name="aksi" value="tambah">
    <select name="idbarang" required>
        <option value="">-- Pilih barang --</option>
        <?php foreach ($barang as $b) { ?>
            <option value="<?= $b['idbarang'] ?>"><?= htmlspecialchars($b['nama_barang']) ?> (<?= rp($b['harga']) ?>/<?= htmlspecialchars($b['nama_satuan']) ?>)</option>
        <?php } ?>
    </select>
    Jumlah <input type="number" name="jumlah" min="1" value="1" required>
    <button>Tambah</button>
</form>

<h3>Daftar pesanan</h3>
<?php if (!$_SESSION['keranjang']) { ?>
    <p>Belum ada barang.</p>
<?php } else { ?>
    <table class="tabel">
        <tr><th>Barang</th><th>Harga beli</th><th>Jumlah</th><th>Subtotal</th><th></th></tr>
        <?php $total = 0; ?>
        <?php foreach ($_SESSION['keranjang'] as $id => $jumlah) { ?>
            <?php 
            if (isset($harga_barang[$id])) {
                $harga = $harga_barang[$id];
                $nama = $nama_barang[$id];
            } else {
                $harga = 0;
                $nama = "Barang #$id (tidak aktif)";
            }
            $subtotal_item = $harga * $jumlah;
            $total = $total + $subtotal_item;
            ?>
            <tr>
                <td><?= htmlspecialchars($nama) ?></td>
                <td><?= rp($harga) ?></td>
                <td><?= $jumlah ?></td>
                <td><?= rp($harga * $jumlah) ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="idbarang" value="<?= $id ?>">
                        <button>Hapus</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        <tr><th colspan="3">Total (belum PPN)</th><th><?= rp($total) ?></th><td></td></tr>
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
    <tr><th>ID</th><th>Tanggal</th><th>Vendor</th><th>Dibuat oleh</th><th>Subtotal</th><th>PPN</th><th>Total</th><th>Status</th><th>Aksi</th></tr>
    <?php foreach ($riwayat as $r) { ?>
        <tr>
            <td><?= $r['idpengadaan'] ?></td>
            <td><?= htmlspecialchars($r['tgl_pengadaan']) ?></td>
            <td><?= htmlspecialchars($r['nama_vendor']) ?></td>
            <td><?= htmlspecialchars($r['pemesan']) ?></td>
            <td><?= rp($r['subtotal']) ?></td>
            <td><?= rp($r['total'] - $r['subtotal']) ?></td>
            <td><b><?= rp($r['total']) ?></b></td>
            <td><?= htmlspecialchars($r['status_pengadaan']) ?></td>
            <td><a href="pengadaan_detail.php?id=<?= $r['idpengadaan'] ?>">Detail</a></td>
        </tr>
    <?php } ?>
    <?php if (!$riwayat) { ?>
        <tr><td colspan="9">Belum ada pengadaan.</td></tr>
    <?php } ?>
</table>
</div>
</body>
</html>