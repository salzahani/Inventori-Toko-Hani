<?php
$status01 = ['1' => 'Aktif', '0' => 'Non-Aktif'];

$master = [
    'role' => [
        'judul' => 'Role',
        'tabel' => 'role',
        'view' => 'v_role',
        'pk' => 'idrole',
        'field' => [
            'nama_role' => ['label' => 'Nama Role', 'tipe' => 'teks', 'maks' => 45],
        ],
    ],
    'user' => [
        'judul' => 'Pengguna',
        'tabel' => 'user',
        'view' => 'v_user',
        'pk' => 'iduser',
        'field' => [
            'username' => ['label' => 'Username', 'tipe' => 'teks', 'maks' => 45],
            'password_user' => ['label' => 'Password', 'tipe' => 'sandi'],
            'idrole' => ['label' => 'Role', 'tipe' => 'pilihan', 'sumber' => ['role', 'idrole', 'nama_role']],
        ],
    ],
    'satuan' => [
        'judul' => 'Satuan Barang',
        'tabel' => 'satuan',
        'view' => 'v_satuan',
        'pk' => 'idsatuan',
        'field' => [
            'nama_satuan' => ['label' => 'Nama Satuan', 'tipe' => 'teks', 'maks' => 45],
            'status_satuan' => ['label' => 'Status', 'tipe' => 'pilihan', 'pilihan' => $status01],
        ],
    ],
    'vendor' => [
        'judul' => 'Vendor',
        'tabel' => 'vendor',
        'view' => 'v_vendor',
        'pk' => 'idvendor',
        'field' => [
            'nama_vendor' => ['label' => 'Nama Vendor', 'tipe' => 'teks', 'maks' => 100],
            'badan_hukum' => ['label' => 'Badan Hukum', 'tipe' => 'pilihan', 'pilihan' => ['P' => 'PT', 'C' => 'CV', 'U' => 'UD']],
            'status_vendor' => ['label' => 'Status', 'tipe' => 'pilihan', 'pilihan' => ['A' => 'Aktif', 'N' => 'Non-Aktif']],
        ],
    ],
    'barang' => [
        'judul' => 'Barang',
        'tabel' => 'barang',
        'view' => 'v_barang_lengkap',
        'pk' => 'idbarang',
        'field' => [
            'nama' => ['label' => 'Nama Barang', 'tipe' => 'teks', 'maks' => 45],
            'jenis' => ['label' => 'Jenis', 'tipe' => 'pilihan', 'pilihan' => ['S' => 'Sembako', 'M' => 'Minuman', 'K' => 'Kebersihan']],
            'idsatuan' => ['label' => 'Satuan', 'tipe' => 'pilihan', 'sumber' => ['satuan', 'idsatuan', 'nama_satuan']],
            'harga' => ['label' => 'Harga Beli', 'tipe' => 'angka', 'langkah' => '1'],
            'status_barang' => ['label' => 'Status', 'tipe' => 'pilihan', 'pilihan' => $status01],
        ],
    ],
    'margin' => [
        'judul' => 'Margin Penjualan',
        'tabel' => 'margin_penjualan',
        'view' => 'v_margin',
        'pk' => 'idmargin_penjualan',
        'field' => [
            'persen' => ['label' => 'Persen Margin (%)', 'tipe' => 'angka', 'langkah' => '0.01'],
            'status_margin_penjualan' => ['label' => 'Status', 'tipe' => 'pilihan', 'pilihan' => $status01],
        ],
        'tetap' => ['iduser' => 1],
        'satu_aktif' => 'status_margin_penjualan',
    ],
];
