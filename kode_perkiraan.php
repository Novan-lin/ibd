<?php
// =====================================================================
//            HALAMAN KODE PERKIRAAN (VERSI DATA MANUAL)
// =====================================================================
// Versi ini tidak mengambil data dari database, melainkan langsung
// menampilkannya dari array PHP. Ini lebih cepat dan sederhana
// jika data kode perkiraan jarang sekali berubah.
// =====================================================================

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Logika yang benar untuk mengambil ID dari URL agar navigasi tetap berjalan
$bruder_id = (int)($_GET['id'] ?? 0);

// --- DATA KODE PERKIRAAN MANUAL ---
$data_perkiraan = [
    'PENERIMAAN' => [
        ['pos' => 'A', 'kode_perkiraan' => '110100', 'nama_akun' => 'Kas'],
        ['pos' => 'B', 'kode_perkiraan' => '110300', 'nama_akun' => 'Bank'],
        ['pos' => 'C', 'kode_perkiraan' => '410101', 'nama_akun' => 'Gaji/Pendapatan Bruder'],
        ['pos' => 'D', 'kode_perkiraan' => '410102', 'nama_akun' => 'Pensin Bruder'],
        ['pos' => 'E', 'kode_perkiraan' => '430101', 'nama_akun' => 'Hasil Kebun dan Piaraan'],
        ['pos' => 'F', 'kode_perkiraan' => '420101', 'nama_akun' => 'Bunga Tabungan'],
        ['pos' => 'G', 'kode_perkiraan' => '410202', 'nama_akun' => 'Sumbangan'],
        ['pos' => 'H', 'kode_perkiraan' => '430103', 'nama_akun' => 'Penerimaan Lainnya'],
        ['pos' => 'I', 'kode_perkiraan' => '610100', 'nama_akun' => 'Penerimaan dari DP'],
    ],
    'PENGELUARAN' => [
        ['pos' => '1', 'kode_perkiraan' => '510101', 'nama_akun' => 'Makanan'],
        ['pos' => '2', 'kode_perkiraan' => '510201', 'nama_akun' => 'Pakaian dan Perlengkapan Pribadi'],
        ['pos' => '3', 'kode_perkiraan' => '510301', 'nama_akun' => 'Pemeriksaan dan Pengobatan'],
        ['pos' => '4', 'kode_perkiraan' => '510303', 'nama_akun' => 'Hiburan / Rekreasi'],
        ['pos' => '5', 'kode_perkiraan' => '510501', 'nama_akun' => 'Transport Harian'],
        ['pos' => '6', 'kode_perkiraan' => '520401', 'nama_akun' => 'Sewa Pribadi'],
        ['pos' => '7', 'kode_perkiraan' => '510102', 'nama_akun' => 'Bahan Bakar Dapur'],
        ['pos' => '8', 'kode_perkiraan' => '510103', 'nama_akun' => 'Perlengkapan Cuci dan Kebersihan'],
        ['pos' => '9', 'kode_perkiraan' => '510104', 'nama_akun' => 'Perabot Rumah Tangga'],
        ['pos' => '10', 'kode_perkiraan' => '510105', 'nama_akun' => 'Iuran Hidup Bermasyarakat dan Menggereja'],
        ['pos' => '11', 'kode_perkiraan' => '510401', 'nama_akun' => 'Listrik'],
        ['pos' => '12', 'kode_perkiraan' => '510402', 'nama_akun' => 'Air'],
        ['pos' => '13', 'kode_perkiraan' => '510403', 'nama_akun' => 'Telepon dan Internet'],
        ['pos' => '14', 'kode_perkiraan' => '520201', 'nama_akun' => 'Keperluan Ibadah'],
        ['pos' => '15', 'kode_perkiraan' => '530302', 'nama_akun' => 'Sumbangan'],
        ['pos' => '16', 'kode_perkiraan' => '540101', 'nama_akun' => 'Insentif ART'],
        ['pos' => '17', 'kode_perkiraan' => '540201', 'nama_akun' => 'Pemeliharaan Rumah'],
        ['pos' => '18', 'kode_perkiraan' => '540202', 'nama_akun' => 'Pemeliharaan Kebun dan Piaraan'],
        ['pos' => '19', 'kode_perkiraan' => '540203', 'nama_akun' => 'Pemeliharaan Kendaraan'],
        ['pos' => '20', 'kode_perkiraan' => '540204', 'nama_akun' => 'Pemeliharaan Mesin dan Peralatan'],
        ['pos' => '21', 'kode_perkiraan' => '550101', 'nama_akun' => 'Administrasi Komunitas'],
        ['pos' => '22', 'kode_perkiraan' => '550102', 'nama_akun' => 'Legal dan Perijinan'],
        ['pos' => '23', 'kode_perkiraan' => '550106', 'nama_akun' => 'Buku, Majalah, Koran'],
        ['pos' => '24', 'kode_perkiraan' => '550107', 'nama_akun' => 'Administrasi Bank'],
        ['pos' => '25', 'kode_perkiraan' => '550201', 'nama_akun' => 'Pajak Bunga Bank'],
        ['pos' => '26', 'kode_perkiraan' => '550202', 'nama_akun' => 'Pajak Kendaraan dan PBB'],
        ['pos' => '27', 'kode_perkiraan' => '110202', 'nama_akun' => 'Kas Kecil DP'],
        ['pos' => '28', 'kode_perkiraan' => '110201', 'nama_akun' => 'Kas Kecil Komunitas'],
    ],
    'LANSIA' => [
        ['pos' => '29', 'kode_perkiraan' => '520501', 'nama_akun' => 'Penunjang Kesehatan Lansia'],
        ['pos' => '30', 'kode_perkiraan' => '520502', 'nama_akun' => 'Pemeliharaan Rohani Lansia'],
        ['pos' => '31', 'kode_perkiraan' => '520503', 'nama_akun' => 'Kegiatan Bruder Lansia'],
    ],
    'BUDGET KHUSUS' => [
        ['pos' => '32', 'kode_perkiraan' => '130400', 'nama_akun' => 'Mesin dan Peralatan'],
        ['pos' => '33', 'kode_perkiraan' => '510100', 'nama_akun' => 'Perabot Rumah Tangga'],
        ['pos' => '34', 'kode_perkiraan' => '510502', 'nama_akun' => 'Transport Pertemuan'],
        ['pos' => '35', 'kode_perkiraan' => '520300', 'nama_akun' => 'Perayaan Syukur'],
        ['pos' => '36', 'kode_perkiraan' => '520400', 'nama_akun' => 'Kegiatan Lainnya'],
        ['pos' => '37', 'kode_perkiraan' => '540200', 'nama_akun' => 'Pemeliharaan Rumah'],
        ['pos' => '38', 'kode_perkiraan' => '550100', 'nama_akun' => 'Budget Khusus Lainnya'],
    ],
    'BIAYA DP' => [
        ['pos' => '39', 'kode_perkiraan' => '510300', 'nama_akun' => 'Pemeriksaan dan Pengobatan'],
        ['pos' => '40', 'kode_perkiraan' => '550300', 'nama_akun' => 'Pertemuan DP'],
        ['pos' => '41', 'kode_perkiraan' => '530100', 'nama_akun' => 'Kegiatan Acc. DP'],
    ]
];

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Perkiraan - Aplikasi FIC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background-color: #F3F4F6; font-weight: 600; }
        tr:not(:last-child) { border-bottom: 1px solid #E5E7EB; }
        th, td { padding: 0.75rem 1rem; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#002244]">
    <div class="flex h-screen">
        <aside class="w-64 bg-white flex flex-col">
            <div class="p-6 text-center border-b">
                <img src="https://placehold.co/100x100/003366/FFFFFF?text=FIC" alt="Logo FIC" class="w-20 h-20 mx-auto mb-2 rounded-full">
            </div>
            <nav class="flex-grow pt-4">
                <a href="anggaran.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Data</a>
                <a href="kode_perkiraan.php?id=<?php echo $bruder_id; ?>" class="sidebar-link active flex items-center px-6 py-3 text-gray-800 transition">Kode Perkiraan</a>
                <a href="kas_harian.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Harian</a>
                <a href="bank.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Bank</a>
                <a href="bruder.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Bruder</a>
                <a href="lu_komunitas.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">LU Komunitas</a>
                <a href="evaluasi.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Evaluasi</a>
                <a href="buku_besar.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Buku Besar</a>
                <a href="kas_opname.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Opname</a>
            </nav>
            <div class="p-6 border-t">
                 <a href="laporan.php" class="w-full text-center mb-4 bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-300 transition block">
                    Kembali
                </a>
                <a href="kode_perkiraan.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition">
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 p-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 h-full overflow-y-auto">
                <h1 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4 text-center">KODE PERKIRAAN PEMBUKUAN<br>KOMUNITAS BRUDER FIC</h1>
                <table class="w-full text-left text-sm">
                    <thead class="border-b-2">
                        <tr>
                            <th class="font-semibold text-gray-600">POS</th>
                            <th class="font-semibold text-gray-600">KODE PERKIRAAN</th>
                            <th class="font-semibold text-gray-600">NAMA PERKIRAAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_perkiraan as $kategori => $items): ?>
                            <?php if (!empty($items)): ?>
                                <tr class="font-bold bg-gray-50"><td colspan="3"><?php echo $kategori; ?></td></tr>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['pos']); ?></td>
                                        <td><?php echo htmlspecialchars($item['kode_perkiraan']); ?></td>
                                        <td><?php echo htmlspecialchars($item['nama_akun']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>

