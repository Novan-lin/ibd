<?php
// =====================================================================
//            HALAMAN KODE PERKIRAAN
// =====================================================================

// --- 1. Memulai Sesi & Keamanan ---
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Ambil ID Bruder dari URL untuk konsistensi link
$bruder_id = $_GET['id'] ?? 0;

// --- 2. Logika Logout ---
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
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-link.active {
            background-color: #004488;
            color: white;
        }
        tr:not(:last-child) {
            border-bottom: 1px solid #E5E7EB;
        }
        th, td {
            padding: 0.75rem 1rem;
        }
    </style>
    <!-- Load Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#002244]">
    <div class="flex h-screen">
        <!-- Sidebar Baru -->
        <aside class="w-64 bg-white flex flex-col">
            <div class="p-6 text-center border-b">
                <img src="https://placehold.co/100x100/003366/FFFFFF?text=FIC" alt="Logo FIC" class="w-20 h-20 mx-auto mb-2 rounded-full">
            </div>
            <nav class="flex-grow pt-4">
                <a href="anggaran.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Data</a>
                <a href="kode_perkiraan.php?id=<?php echo $bruder_id; ?>" class="sidebar-link active bg-gray-100 font-semibold flex items-center px-6 py-3 text-gray-800 transition">Kode Perkiraan</a>
                <a href="kas_harian.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Harian</a>
                <a href="bank.php" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Bank</a>
                <a href="#" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Bruder</a>
                <a href="#" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">LU Komunitas</a>
                <a href="#" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Evaluasi</a>
                <a href="#" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Buku Besar</a>
                <a href="#" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Opname</a>
            </nav>
            <div class="p-6 border-t">
                <a href="kode_perkiraan.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition">
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 p-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 h-full overflow-y-auto">
                <h1 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">KODE PERKIRAAN PEMBUKUAN KOMUNITAS BRUDER FIC</h1>
                <table class="w-full text-left text-sm">
                    <thead class="border-b-2">
                        <tr>
                            <th class="font-semibold text-gray-600">POS</th>
                            <th class="font-semibold text-gray-600">KODE PERKIRAAN</th>
                            <th class="font-semibold text-gray-600">NAMA PERKIRAAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="font-bold bg-gray-50"><td colspan="3">PENERIMAAN</td></tr>
                        <tr><td>A</td><td>110101</td><td>Kas</td></tr>
                        <tr><td>B</td><td>110301</td><td>Bank</td></tr>
                        <tr><td>C</td><td>410101</td><td>Gaji/Pendapatan Bruder</td></tr>
                        <tr><td>D</td><td>410102</td><td>Pakaian Bruder</td></tr>
                        <tr><td>E</td><td>430101</td><td>Hasil Kebun Dan Piaraan</td></tr>
                        <tr><td>F</td><td>420101</td><td>Bunga Tabungan</td></tr>
                        <tr><td>G</td><td>410202</td><td>Sumbangan</td></tr>
                        <tr><td>H</td><td>430103</td><td>Penerimaan Lainnya</td></tr>
                        <tr><td>I</td><td>610100</td><td>Penerimaan dari DP</td></tr>
                        
                        <tr class="font-bold bg-gray-50"><td colspan="3">PENGELUARAN</td></tr>
                        <?php
                        $pengeluaran = [
                            1 => ['510101', 'Makanan'], 2 => ['510201', 'Pakaian Dan Perlengkapan Pribadi'],
                            3 => ['510301', 'Pemeriksaan Dan Pengobatan'], 4 => ['510303', 'Hiburan / Rekreasi'],
                            5 => ['510501', 'Transport Harian'], 6 => ['520101', 'Sewa Pribadi'],
                            7 => ['510102', 'Bahan Bakar Dapur'], 8 => ['510103', 'Perlengkapan Cuci Dan Kebersihan'],
                            9 => ['510104', 'Perabot Rumah Tangga'], 10 => ['510105', 'Iuran Hidup Bermasyarakat Dan Menggereja'],
                            11 => ['510401', 'Listrik'], 12 => ['510402', 'Air'],
                            13 => ['510403', 'Telepon Dan Internet'], 14 => ['520201', 'Keperluan Ibadah'],
                            15 => ['530302', 'Sumbangan'], 16 => ['540101', 'Upah ART'],
                            17 => ['540201', 'Pemeliharaan Rumah'], 18 => ['540202', 'Pemeliharaan Kebun Dan Piaran'],
                            19 => ['540203', 'Pemeliharaan Kendaraan'], 20 => ['540204', 'Pemeliharaan Mesin Dan Peralatan'],
                            21 => ['550101', 'Administrasi Komunitas'], 22 => ['550102', 'Legal dan Perijinan'],
                            23 => ['550106', 'Pajak Jalan & STNK'], 24 => ['550107', 'Administrasi Bank'],
                            25 => ['550201', 'Pajak Bunga Bank'], 26 => ['550202', 'Pajak Kendaraan dan PBB'],
                            27 => ['110202', 'Kas Kecil DP'], 28 => ['110201', 'Kas Kecil Komunitas'],
                        ];
                        foreach ($pengeluaran as $no => $data) {
                            echo "<tr><td>$no</td><td>{$data[0]}</td><td>{$data[1]}</td></tr>";
                        }
                        ?>

                        <tr class="font-bold bg-gray-50"><td colspan="3">LANSIA</td></tr>
                        <tr><td>29</td><td>520501</td><td>Penunjang Kesehatan Lansia</td></tr>
                        <tr><td>30</td><td>520502</td><td>Pemeliharaan Rohani Lansia</td></tr>
                        <tr><td>31</td><td>520503</td><td>Kegiatan Bruder Lansia</td></tr>

                        <tr class="font-bold bg-gray-50"><td colspan="3">BUDGET KHUSUS</td></tr>
                        <tr><td>32</td><td>150201</td><td>Mesin dan Peralatan</td></tr>
                        <tr><td>33</td><td>510100</td><td>Perabot Rumah Tangga</td></tr>
                        <tr><td>34</td><td>510502</td><td>Transport Pertemuan</td></tr>
                        <tr><td>35</td><td>520300</td><td>Perayaan/Syukur</td></tr>
                        <tr><td>36</td><td>520400</td><td>Kegiatan Lainnya</td></tr>
                        <tr><td>37</td><td>540200</td><td>Pemeliharaan Rumah</td></tr>
                        <tr><td>38</td><td>550100</td><td>Budget Khusus Lainnya</td></tr>

                        <tr class="font-bold bg-gray-50"><td colspan="3">BIAYA DP</td></tr>
                        <tr><td>39</td><td>510302</td><td>Pemeriksaan Dan Pengobatan</td></tr>
                        <tr><td>40</td><td>550301</td><td>Pertemuan DP</td></tr>
                        <tr><td>41</td><td>550100</td><td>Kegiatan Adm. DP</td></tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
