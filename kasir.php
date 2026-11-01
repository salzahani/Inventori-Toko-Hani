<?php
require 'koneksi.php';
require 'auth.php';
wajib_role('kasir');

$msg = "";

if (isset($_POST['transaksi'])) {
    $idbarang = (int) $_POST['idbarang'];
    $jumlah = (int) $_POST['jumlah'];
    $iduser = $_SESSION['user_id'];

    try {
        if ($jumlah < 1) {
            throw new Exception("Jumlah minimal 1.");
        }
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("CALL sp_buat_penjualan(?, @idpenjualan)");
        $stmt->execute([$iduser]);
        $stmt->closeCursor();
        $idpenjualan = $pdo->query("SELECT @idpenjualan AS id")->fetchColumn();

        $stmt = $pdo->prepare("CALL sp_tambah_detail_penjualan(?, ?, ?)");
        $stmt->execute([$idpenjualan, $idbarang, $jumlah]);
        $stmt->closeCursor();

        $pdo->commit();
        $msg = "Transaksi Berhasil Ditambahkan!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $msg = "Gagal Transaksi: " . $e->getMessage();
    }
}

$barangList = $pdo->query("SELECT * FROM v_harga_jual")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Halaman Kasir - Toko Hani</title>
</head>
<body>
<h2>Kasir Panel - Toko Hani</h2>
<p>Selamat datang, <b><?= htmlspecialchars($_SESSION['username']) ?></b> |
<a href="logout.php">Logout</a></p>

<h3>Input Transaksi Penjualan</h3>
<?php if ($msg) { ?>
    <p><b><?= htmlspecialchars($msg) ?></b></p>
<?php } ?>

<form method="post">
    <p>Pilih Barang:<br>
    <select name="idbarang" required>
        <option value="">-- Pilih Barang --</option>
        <?php foreach ($barangList as $b) { ?>
            <option value="<?= (int) $b['idbarang'] ?>"><?= htmlspecialchars($b['nama']) ?> - Rp <?= number_format($b['harga_jual']) ?></option>
        <?php } ?>
    </select></p>

    <p>Jumlah Beli:<br>
    <input type="number" name="jumlah" min="1" required></p>

    <button type="submit" name="transaksi">Proses Transaksi</button>
</form>
</body>
</html>