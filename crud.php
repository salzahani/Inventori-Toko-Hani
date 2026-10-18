<?php
require 'koneksi.php';
require 'master.php';

function opsi_field(PDO $pdo, array $f): array
{
    if (isset($f['pilihan'])) {
        return $f['pilihan'];
    }
    [$tabel, $kunci, $teks] = $f['sumber'];
    $hasil = [];
    $stmt = $pdo->query("SELECT `$kunci`, `$teks` FROM `$tabel` ORDER BY `$teks`");
    foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $r) {
        $hasil[(string) $r[0]] = $r[1];
    }
    return $hasil;
}

$t = $_GET['t'] ?? '';
if (!is_string($t) || !isset($master[$t])) {
    die('Data master tidak ditemukan. <a href="index.php">Kembali</a>');
}

$m = $master[$t];
$tabel = "`{$m['tabel']}`";
$pk = $m['pk'];
$kolom_aktif = $m['satu_aktif'] ?? null;
$aksi = $_GET['aksi'] ?? 'daftar';
if (!in_array($aksi, ['daftar', 'tambah', 'ubah', 'hapus'], true)) {
    $aksi = 'daftar';
}
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$kirim = $_SERVER['REQUEST_METHOD'] === 'POST';
$daftar_pesan = ['simpan' => 'Data berhasil disimpan!', 'ubah' => 'Data berhasil diubah!', 'hapus' => 'Data berhasil dihapus!'];
$galat = [];
$nilai_form = [];
$lama = null;

if ($aksi === 'ubah') {
    $stmt = $pdo->prepare("SELECT * FROM $tabel WHERE `$pk` = ?");
    $stmt->execute([$id]);
    $lama = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lama) {
        die('Data tidak ditemukan. <a href="crud.php?t=' . urlencode($t) . '">Kembali</a>');
    }
}

if ($aksi === 'hapus' && $kirim) {
    try {
        if ($kolom_aktif) {
            $stmt = $pdo->prepare("SELECT `$kolom_aktif` FROM $tabel WHERE `$pk` = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() == 1) {
                $galat[] = 'Margin yang sedang aktif tidak bisa dihapus. Aktifkan margin lain terlebih dahulu.';
            }
        }
        if (!$galat) {
            $pdo->prepare("DELETE FROM $tabel WHERE `$pk` = ?")->execute([$id]);
            header('Location: crud.php?t=' . urlencode($t) . '&pesan=hapus');
            exit;
        }
    } catch (PDOException $e) {
        $galat[] = $e->getCode() === '23000'
            ? 'Data tidak bisa dihapus karena masih dipakai di tabel lain.'
            : 'Gagal menghapus: ' . $e->getMessage();
    }
    $aksi = 'daftar';
}

if (($aksi === 'tambah' || $aksi === 'ubah') && $kirim) {
    $data = [];
    foreach ($m['field'] as $nama => $f) {
        $nilai = is_string($_POST[$nama] ?? null) ? trim($_POST[$nama]) : '';
        $nilai_form[$nama] = $nilai;
        if ($nilai === '') {
            if (!($f['tipe'] === 'sandi' && $aksi === 'ubah')) {
                $galat[] = $f['label'] . ' wajib diisi.';
            }
            continue;
        }
        if ($f['tipe'] === 'teks' && mb_strlen($nilai) > $f['maks']) {
            $galat[] = $f['label'] . ' maksimal ' . $f['maks'] . ' karakter.';
        } elseif ($f['tipe'] === 'angka' && (!is_numeric($nilai) || $nilai < 0)) {
            $galat[] = $f['label'] . ' harus berupa angka yang tidak negatif.';
        } elseif ($f['tipe'] === 'pilihan' && !isset(opsi_field($pdo, $f)[$nilai])) {
            $galat[] = $f['label'] . ' tidak valid.';
        }
        $data[$nama] = $f['tipe'] === 'sandi' ? password_hash($nilai, PASSWORD_DEFAULT) : $nilai;
    }

    if (!$galat && $aksi === 'ubah' && $kolom_aktif) {
        if ($lama[$kolom_aktif] == 1 && $data[$kolom_aktif] !== '1') {
            $galat[] = 'Harus ada satu margin aktif. Untuk menonaktifkan, aktifkan margin lain.';
        }
    }

    if (!$galat) {
        try {
            $pdo->beginTransaction();
            if ($aksi === 'tambah') {
                $semua = $data + ($m['tetap'] ?? []);
                $nama_kolom = implode(', ', array_map(fn($k) => "`$k`", array_keys($semua)));
                $tanda = implode(', ', array_fill(0, count($semua), '?'));
                $pdo->prepare("INSERT INTO $tabel ($nama_kolom) VALUES ($tanda)")->execute(array_values($semua));
                $id = (int) $pdo->lastInsertId();
            } else {
                $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
                $pdo->prepare("UPDATE $tabel SET $set WHERE `$pk` = ?")->execute([...array_values($data), $id]);
            }
            if ($kolom_aktif && $data[$kolom_aktif] === '1') {
                $pdo->prepare("UPDATE $tabel SET `$kolom_aktif` = 0 WHERE `$pk` <> ?")->execute([$id]);
            }
            $pdo->commit();
            header('Location: crud.php?t=' . urlencode($t) . '&pesan=' . ($aksi === 'tambah' ? 'simpan' : 'ubah'));
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $galat[] = $e->getCode() === '23000'
                ? 'Data sudah ada atau bentrok dengan data lain.'
                : 'Gagal menyimpan: ' . $e->getMessage();
        }
    }
}

$judul = $m['judul'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($judul) ?> - Toko Hani</title>
</head>
<body>
<?php if ($aksi === 'tambah' || $aksi === 'ubah') { ?>
    <h2><?= $aksi === 'tambah' ? 'Tambah ' . htmlspecialchars($judul) . ' Baru' : 'Edit Data ' . htmlspecialchars($judul) ?></h2>
    <?php foreach ($galat as $g) { ?>
        <p><b><?= htmlspecialchars($g) ?></b></p>
    <?php } ?>
    <form method="post" action="crud.php?t=<?= urlencode($t) ?>&aksi=<?= $aksi ?><?= $aksi === 'ubah' ? '&id=' . $id : '' ?>">
        <?php if ($aksi === 'ubah') { ?>
            <p>ID:<br><input type="text" value="<?= $id ?>" disabled></p>
        <?php } ?>
        <?php foreach ($m['field'] as $nama => $f) {
            $nilai = $nilai_form[$nama] ?? ($lama[$nama] ?? '');
            ?>
            <p><?= htmlspecialchars($f['label']) ?>:<br>
            <?php if ($f['tipe'] === 'teks') { ?>
                <input type="text" name="<?= $nama ?>" maxlength="<?= $f['maks'] ?>" value="<?= htmlspecialchars($nilai) ?>">
            <?php } elseif ($f['tipe'] === 'angka') { ?>
                <input type="number" name="<?= $nama ?>" min="0" step="<?= $f['langkah'] ?>" value="<?= htmlspecialchars($nilai) ?>">
            <?php } elseif ($f['tipe'] === 'sandi') { ?>
                <input type="password" name="<?= $nama ?>">
                <?= $aksi === 'ubah' ? '<br><small>Kosongkan jika tidak ingin mengganti password.</small>' : '' ?>
            <?php } else { ?>
                <select name="<?= $nama ?>">
                    <?php foreach (opsi_field($pdo, $f) as $nilai_opsi => $teks_opsi) { ?>
                        <option value="<?= htmlspecialchars($nilai_opsi) ?>" <?= (string) $nilai === (string) $nilai_opsi ? 'selected' : '' ?>><?= htmlspecialchars($teks_opsi) ?></option>
                    <?php } ?>
                </select>
            <?php } ?>
            </p>
        <?php } ?>
        <button type="submit"><?= $aksi === 'tambah' ? 'Simpan Data' : 'Simpan Perubahan' ?></button>
        <a href="crud.php?t=<?= urlencode($t) ?>">Batal</a>
    </form>
<?php } else {
    $stmt = $pdo->query("SELECT * FROM `{$m['view']}` ORDER BY 1");
    $kolom = [];
    for ($i = 0; $i < $stmt->columnCount(); $i++) {
        $kolom[] = $stmt->getColumnMeta($i)['name'];
    }
    $baris = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $kode_pesan = is_string($_GET['pesan'] ?? null) ? $_GET['pesan'] : '';
    ?>
    <h2>Daftar <?= htmlspecialchars($judul) ?> - Toko Hani</h2>
    <a href="index.php">&laquo; Menu</a> |
    <a href="crud.php?t=<?= urlencode($t) ?>&aksi=tambah">+ Tambah Data Baru</a>
    <br><br>
    <?php if (isset($daftar_pesan[$kode_pesan])) { ?>
        <p><b><?= $daftar_pesan[$kode_pesan] ?></b></p>
    <?php } ?>
    <?php foreach ($galat as $g) { ?>
        <p><b><?= htmlspecialchars($g) ?></b></p>
    <?php } ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <?php foreach ($kolom as $k) { ?>
                <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $k))) ?></th>
            <?php } ?>
            <th>Aksi</th>
        </tr>
        <?php foreach ($baris as $b) { ?>
            <tr>
                <?php foreach ($b as $nilai) { ?>
                    <td><?= $nilai === null ? '-' : htmlspecialchars($nilai) ?></td>
                <?php } ?>
                <td>
                    <a href="crud.php?t=<?= urlencode($t) ?>&aksi=ubah&id=<?= (int) $b[$pk] ?>">Edit</a> |
                    <form method="post" action="crud.php?t=<?= urlencode($t) ?>&aksi=hapus" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                        <input type="hidden" name="id" value="<?= (int) $b[$pk] ?>">
                        <button type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        <?php if (!$baris) { ?>
            <tr><td colspan="<?= count($kolom) + 1 ?>">Belum ada data.</td></tr>
        <?php } ?>
    </table>
<?php } ?>
</body>
</html>
