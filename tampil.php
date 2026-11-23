<?php
require 'koneksi.php';
require 'auth.php';
wajib_role('admin');
$v = $_GET['v'] ?? '';
if (!is_string($v) || !isset($daftar_view[$v])) {
    die('View tidak ditemukan. <a href="index.php">Kembali</a>');
}

try {
    $stmt = $pdo->query("SELECT * FROM $v");
    $baris = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('View belum bisa dibaca: ' . htmlspecialchars($e->getMessage()) . ' <a href="index.php">Kembali</a>');
}

$kolom = [];
for ($i = 0; $i < $stmt->columnCount(); $i++) {
    $kolom[] = $stmt->getColumnMeta($i)['name'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $daftar_view[$v] ?> - Toko Hani</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
<h2><?= $daftar_view[$v] ?> - Toko Hani</h2>
<p class="nav"><a href="index.php">&laquo; Kembali ke daftar view</a></p>
<div class="gulir">
<table  class="tabel">
    <tr>
        <?php foreach ($kolom as $k) { ?>
            <th><?= htmlspecialchars($k) ?></th>
        <?php } ?>
    </tr>
    <?php foreach ($baris as $b) { ?>
        <tr>
            <?php foreach ($b as $nilai) { ?>
                <td><?= $nilai === null ? '-' : htmlspecialchars($nilai) ?></td>
            <?php } ?>
        </tr>
    <?php } ?>
    <?php if (!$baris) { ?>
        <tr><td colspan="<?= count($kolom) ?>">Belum ada data</td></tr>
    <?php } ?>
</table>
</div>
</div>
</body>
</html>
