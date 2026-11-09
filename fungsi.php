<?php
function harga_jual(PDO $pdo, int $idbarang): int {
    $stmt = $pdo->prepare('SELECT fn_harga_jual(?)');
    $stmt->execute([$idbarang]);
    return (int) $stmt->fetchColumn();
}

function stok_barang(PDO $pdo, int $idbarang): int {
    $stmt = $pdo->prepare('SELECT fn_stok_barang(?)');
    $stmt->execute([$idbarang]);
    return (int) $stmt->fetchColumn();
}

function hitung_ppn(PDO $pdo, int $subtotal): int {
    $stmt = $pdo->prepare('SELECT fn_hitung_ppn(?)');
    $stmt->execute([$subtotal]);
    return (int) $stmt->fetchColumn();
}