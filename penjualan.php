<?php
require 'koneksi.php';
require 'auth.php';
wajib_role('kasir');

function rp($angka){
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

if (!isset($_SESSION['keranjang_jual'])) {
    $_SESSION['keranjang_jual'] = [];
}

function ambil_barang($pdo, $idbarang){
    $stmt = $pdo->prepare('SELECT * FROM v_barang_penjualan WHERE idbarang = ?');
    $stmt->execute([$idbarang]);
    return $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah') {
        $id = (int) $_POST['idbarang'];
        $jumlah = (int) $_POST['jumlah'];
        $b = ambil_barang($pdo, $id);

        if ($id < 1 || $jumlah < 1) {
            $_SESSION['pesan'] = 'Pilih barang dan isi jumlah minimal 1';
        } elseif (!$b) {
            $_SESSION['pesan'] = 'Barang tidak tersedia';
        } else {
            if (isset($_SESSION['keranjang_jual'][$id])) {
                $total = $_SESSION['keranjang_jual'][$id] + $jumlah;
            } else {
                $total = $jumlah;
            }

            if ($total > (int) $b['stok']) {
                $_SESSION['pesan'] = 'Stok tidak cukup: ' . $b['nama_barang'] . ' (stok ' . $b['stok'] . ')';
            } else {
                $_SESSION['keranjang_jual'][$id] = $total;
            }
        }
    } elseif ($aksi === 'hapus') {
        unset($_SESSION['keranjang_jual'][(int) $_POST['idbarang']]);
    } elseif ($aksi === 'simpan') {
        $galat = '';
        if (!$_SESSION['keranjang_jual']) {
            $galat = 'Keranjang masih kosong';
        } else {
            foreach ($_SESSION['keranjang_jual'] as $id => $jumlah) {
                $b = ambil_barang($pdo, $id);
                if (!$b) {
                    $galat = 'Barang tidak tersedia';
                    break;
                }
                if ($jumlah > (int) $b['stok']) {
                    $galat = 'Stok tidak mencukupi: ' . $b['nama_barang'] . ' (stok ' . $b['stok'] . ')';
                    break;
                }
            }
        }
        if ($galat !== '') {
            $_SESSION['pesan'] = $galat;
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare('CALL sp_buat_penjualan(?, @idpenjualan)');
                $stmt->execute([$_SESSION['user_id']]);
                $stmt->closeCursor();
                $idpenjualan = $pdo->query('SELECT @idpenjualan')->fetchColumn();

                $stmt = $pdo->prepare('CALL sp_tambah_detail_penjualan(?, ?, ?)');
                foreach ($_SESSION['keranjang_jual'] as $id => $jumlah) {
                    $stmt->execute([$idpenjualan, $id, $jumlah]);
                    $stmt->closeCursor();
                }
                $pdo->commit();
                $_SESSION['keranjang_jual'] = [];
                $_SESSION['pesan'] = "Penjualan $idpenjualan berhasil disimpan.";
            } catch (Exception $ex) {
                $pdo->rollBack();
                $_SESSION['pesan'] = 'Gagal menyimpan' . $ex->getMessage();
            }
        }
    }    
    header('Location: penjualan.php');
    exit;
}

$barang = $pdo->query('SELECT * FROM v_barang_penjualan ORDER BY nama_barang')->fetchAll();
$nama_barang = array_column($barang, 'nama_barang', 'idbarang');
$harga_barang = array_column($barang, 'harga_jual', 'idbarang');
$riwayat = $pdo->query('SELECT * FROM v_penjualan_ringkas ORDER BY idpenjualan DESC LIMIT 15')->fetchAll();

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
<title>Penjualan - Toko Hani</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
<h2>Penjualan</h2>
<p class="nav">Selamat datang, <b><?= htmlspecialchars($_SESSION['username']) ?></b> | <a href="logout.php">Logout</a></p>

<?php if ($pesan !== '') { ?>
    <p class="pesan"><?= htmlspecialchars($pesan) ?></p>
<?php } ?>

<h3>Buat penjualan baru</h3>
<form method="post">
    <input type="hidden" name="aksi" value="tambah">
    <select name="idbarang" required>
        <option value="">-- Pilih barang --</option>
        <?php foreach ($barang as $b) { ?>
            <option value="<?= $b['idbarang'] ?>"><?= htmlspecialchars($b['nama_barang']) ?> (<?= rp($b['harga_jual']) ?>/<?= htmlspecialchars($b['nama_satuan']) ?>) - stok <?= (int) $b['stok'] ?></option>
        <?php } ?>
    </select>
    Jumlah <input type="number" name="jumlah" min="1" value="1" required>
    <button>+ Tambah</button>
</form>

<h3>Keranjang</h3>
<?php if (!$_SESSION['keranjang_jual']) { ?>
    <p>Keranjang masih kosong.</p>
<?php } else { ?>
    <table class="tabel">
        <tr><th>Barang</th><th>Harga jual</th><th>Jumlah</th><th>Subtotal</th><th></th></tr>
        <?php $subtotal = 0; ?>
        <?php foreach ($_SESSION['keranjang_jual'] as $id => $jumlah) { ?>
            <?php

            if (isset($harga_barang[$id])) {
                $harga = $harga_barang[$id];
                $nama = $nama_barang[$id];
            } else {
                $harga = 0;
                $nama = "Barang $id (tidak tersedia)";
            }
            $subtotal = $subtotal + $harga * $jumlah;
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
        <?php
        $stmt = $pdo->prepare('SELECT fn_hitung_ppn(?)');
        $stmt->execute([(int) $subtotal]);
        $ppn = (int) $stmt->fetchColumn();
        ?>
        <tr><th colspan="3">Subtotal</th><th><?= rp($subtotal) ?></th><td></td></tr>
        <tr><th colspan="3">PPN</th><th><?= rp($ppn) ?></th><td></td></tr>
        <tr><th colspan="3">Total bayar</th><th><?= rp($subtotal + $ppn) ?></th><td></td></tr>
    </table>
    <form method="post">
        <input type="hidden" name="aksi" value="simpan">
        <button>Simpan Penjualan</button>
    </form>
<?php } ?>

<h3>Riwayat penjualan</h3>
<table class="tabel">
    <tr><th>ID Penjualan</th><th>Tanggal</th><th>Kasir</th><th>Jumlah barang</th><th>Subtotal</th><th>PPN</th><th>Total</th><th>Aksi</th></tr>
    <?php foreach ($riwayat as $r) { ?>
        <tr>
           <td>#<?= $r['idpenjualan'] ?></td>
            <td><?= htmlspecialchars($r['tgl_jual']) ?></td>
            <td><?= htmlspecialchars($r['kasir']) ?></td>
            <td><?= (int) $r['jumlah_barang'] ?></td>
            <td><?= rp($r['subtotal']) ?></td>
            <td><?= rp($r['ppn']) ?></td>
            <td><b><?= rp($r['total']) ?></b></td>
            <td><a href="penjualan_detail.php?id=<?= $r['idpenjualan'] ?>">Detail</a></td> 
        </tr>
    <?php } ?>
    <?php if (!$riwayat) { ?>
        <tr><td colspan="8">Belum ada penjualan</td></tr>
    <?php } ?>
</table>
</div>
</body>
</html>