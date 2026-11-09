<?php
function panggil_sp(PDO $pdo, string $sql, array $param = []): void{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($param);
    $stmt->closeCursor();
}
function ambil_out(PDO $pdo, string $variabel): int{
    return (int) $pdo->query("SELECT $variabel")->fetchColumn();
}
function pesan_error(Throwable $e): string{
    if (preg_match('/\b1644 (.+)$/s', $e->getMessage(), $m)) {
        return $m[1];
    } else{
        return $e->getMessage();
    }
}
function buat_penerimaan(PDO $pdo, int $idpengadaan, int $iduser): int{
    panggil_sp($pdo, 'CALL sp_buat_penerimaan(?, ?, @id_penerimaan)', [$idpengadaan, $iduser]);
    return ambil_out($pdo, '@id_penerimaan');
}

function tambah_detail_penerimaan(PDO $pdo, int $idpenerimaan, int $idbarang, int $jumlah): void{
    panggil_sp($pdo, 'CALL sp_tambah_detail_penerimaan(?, ?, ?)', [$idpenerimaan, $idbarang, $jumlah]);
}

function buat_penjualan(PDO $pdo, int $iduser): int{
    panggil_sp($pdo, 'CALL sp_buat_penjualan(?, @id_penjualan)', [$iduser]);
    return ambil_out($pdo, '@id_penjualan');
}

function tambah_detail_penjualan(PDO $pdo, int $idpenjualan, int $idbarang, int $jumlah): void{
    panggil_sp($pdo, 'CALL sp_tambah_detail_penjualan(?, ?, ?)', [$idpenjualan, $idbarang, $jumlah]);
}

