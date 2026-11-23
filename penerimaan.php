<?php
require 'koneksi.php';
require 'auth.php';
wajib_role('admin');

function ambil_barang($pdo, $idpengadaan){
    $stmt = $pdo->prepare('SELECT * FROM v_penerimaan_barang WHERE idpengadaan = ?');
    $stmt->execute([$idpengadaan]);
    return $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $idp = (int) $_POST['idpengadaan'];
    $barang = ambil_barang($pdo, $idp);
    $galat = '';
    $valid = [];
    if (!$barang) {
        $galat = 'Pengadaan tidak ditemukan';
    }
    foreach ($barang as $b) {
        $sisa = (int) $b['pesan'] - (int) $b['terima'];
        if (isset($_POST ['terima'][$b['idbarang']])) {
            $jumlah = (int) $_POST['terima'][$b['idbarang']];
        } else {
            $jumlah = 0;
        }
        if ($jumlah < 0) {
            $galat = 'Jumlah terima tidak boleh negatif: ' . $b['nama_barang'];
            break;
        }
        if ($jumlah > $sisa) {
            $galat = 'Jumlah terima melebihi sisa pesanan :' . $b['nama_barang'];
            break;
        }
        if ($jumlah > 0) {
            $valid[$b['idbarang']] = $jumlah;
        }
    }
    if ($galat === '' && !$valid) {
        $galat = 'Isi jumlah terima minimal pada satu barang';
    }
    if ($galat !== ''){
        $_SESSION['pesan'] = $galat;
    }else {
        try{
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('CALL sp_buat_penerimaan(?, ?, @idpenerimaan)');
            $stmt->execute([$idp, $_SESSION['user_id']]);
            $stmt->closeCursor();
            $idpenerimaan = $pdo->query('SELECT @idpenerimaan')->fetchColumn();
            $stmt = $pdo->prepare('CALL sp_tambah_detail_penerimaan(?, ?, ?)');
            foreach ($valid as $idbarang => $jumlah) {
                $stmt->execute([$idpenerimaan, $idbarang, $jumlah]);
                $stmt->closeCursor();
            }

            $pdo->commit();
            $_SESSION['pesan'] = "Penerimaan $idpenerimaan berhasil disimpan";
        } catch (Exception $ex) {
            $pdo->rollBack();
            $_SESSION['pesan'] = 'Gagal menyimpan:' . $ex->getMessage();
        }
    }
    header('Location: penerimaan.php?id=' . $idp);
    exit;
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
} else {
    $id = 0;
}

if ($id == 0) {
    $daftar = $pdo->query("SELECT * FROM v_pengadaan_ringkas WHERE status_kode <> 'D' ORDER BY idpengadaan DESC")->fetchAll();
    $selesai = $pdo->query("SELECT * FROM v_pengadaan_ringkas WHERE status_kode = 'D' ORDER BY idpengadaan DESC")->fetchAll();
} else {
    $barang = ambil_barang($pdo, $id);
}

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
<title>Penerimaan - Toko Hani</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
<h2>Penerimaan Barang</h2>
<p class="nav"><a href="admin.php">&laquo; Dashboard</a> | <a href="pengadaan.php">Pengadaan</a> | <a href="logout.php">Logout</a></p>

<?php if ($pesan !== '') { ?>
<p class="pesan"><?=  htmlspecialchars($pesan) ?></p>
<?php } ?>

<?php if ($id == 0) { ?>
    <h3>Penerimaan</h3>
    <table class="tabel">
        <tr><th>ID Pengadaan</th><th>Tanggal</th><th>Vendor</th><th>Dibuat oleh</th><th>Status</th><th>Aksi</th></tr>
        <?php foreach ($daftar as $p) { ?>
        <tr>
            <td><?= $p['idpengadaan'] ?></td>
                    <td><?= htmlspecialchars($p['tgl_pengadaan']) ?></td>
                    <td><?= htmlspecialchars($p['nama_vendor']) ?></td>
                    <td><?= htmlspecialchars($p['pemesan']) ?></td>
                    <td><?= htmlspecialchars($p['status_pengadaan']) ?></td>
                    <td><a href="penerimaan.php?id=<?= $p['idpengadaan'] ?>">Terima barang</a></td>
        </tr>
        <?php } ?>
            <?php if (!$daftar) { ?>
                <tr><td colspan="6">Tidak ada penerimaan</td></tr>
            <?php } ?>
    </table>

    <h3>Riwayat penerimaan</h3>
    <table class="tabel">
        <tr><th>ID Pengadaan</th><th>Tanggal</th><th>Vendor</th><th>Dibuat oleh</th><th>Status</th><th>Aksi</th></tr>
        <?php foreach ($selesai as $p) { ?>
        <tr>
            <td><?= $p['idpengadaan'] ?></td>
                <td><?= htmlspecialchars($p['tgl_pengadaan']) ?></td>
                <td><?= htmlspecialchars($p['nama_vendor']) ?></td>
                <td><?= htmlspecialchars($p['pemesan']) ?></td>
                <td><?= htmlspecialchars($p['status_pengadaan']) ?></td>
                <td><a href="penerimaan_detail.php?id=<?= $p['idpengadaan'] ?>">Detail</a></td>
        </tr>
        <?php } ?>
        <?php if (!$selesai) { ?>
            <tr><td colspan="6">Belum ada pengadaan yang selesai diterima.</td></tr>
        <?php } ?>
    </table>

<?php } elseif (!$barang) { ?>
    <p class="pesan">Pengadaan tidak ditemukan.</p>
    <p><a href="penerimaan.php">&laquo; Kembali ke daftar</a></p>

<?php } else { ?>
<p><a href="penerimaan.php">&laquo; Kembali ke daftar</a></p>
    <h3>Pengadaan <?= $id ?></h3>
    <form method="post" novalidate>
        <input type="hidden" name="idpengadaan" value="<?= $id ?>">
        <table class="tabel">
            <tr><th>Barang</th><th>Dipesan</th><th>Sudah diterima</th><th>Sisa</th><th>Terima sekarang</th></tr>
            <?php foreach ($barang as $b) { ?>
                <?php $sisa = (int) $b['pesan'] - (int) $b['terima']; ?>
                <tr>
                    <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                    <td><?= (int) $b['pesan'] ?></td>
                    <td><?= (int) $b['terima'] ?></td>
                    <td><?= $sisa ?></td>
                    <td><input type="number" name="terima[<?= $b['idbarang'] ?>]" min="0" max="<?= $sisa ?>" value="0"></td>
                </tr>
            <?php } ?>
        </table>
        <p>Isi 0 untuk barang yang belum datang</p>
        <button>Simpan Penerimaan</button>
    </form>

<?php } ?>
</div>
</body>
</html>