<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=db_inventori;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$daftar_view = [
    'v_vendor_aktif' => 'Vendor Aktif',
    'v_margin_aktif' => 'Margin Aktif',
    'v_user' => 'Pengguna',
    'v_barang_lengkap' => 'Barang Lengkap',
    'v_harga_jual' => 'Harga Jual',
    'v_pengadaan_detail' => 'Detail Pengadaan',
    'v_sisa_pengadaan' => 'Sisa Pengadaan',
    'v_rekap_pengadaan_vendor' => 'Rekap Pengadaan per Vendor',
    'v_penerimaan_detail' => 'Detail Penerimaan',
    'v_retur_detail' => 'Detail Retur',
    'v_penjualan_detail' => 'Detail Penjualan',
    'v_penjualan_harian' => 'Penjualan Harian',
    'v_stok_terkini' => 'Stok Terkini',
];