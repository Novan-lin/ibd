<?php
// =====================================================================
//            HALAMAN EVALUASI (DENGAN PERHITUNGAN SALDO YANG BENAR)
// =====================================================================

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'db_fic_bruderan');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Logika yang benar untuk mengambil ID dari URL
$bruder_id = (int)($_GET['id'] ?? 0);
$bruder_name = 'Pilih Bruder Dahulu';
$nomor_bruder = '-';

// --- LOGIKA FILTER BARU ---
// Ambil bulan & tahun dari form, jika tidak ada, gunakan bulan & tahun saat ini
$bulan_laporan = (int)($_POST['bulan'] ?? date('n'));
$tahun_laporan = (int)($_POST['tahun'] ?? date('Y'));


$data_evaluasi = [
    'PENERIMAAN' => [],
    'PENGELUARAN' => []
];
$total_realisasi_penerimaan = 0;
$total_realisasi_pengeluaran = 0;
$total_anggaran_penerimaan = 0;
$total_anggaran_pengeluaran = 0;

if ($bruder_id > 0) {
    // Ambil Nama Bruder
    $stmt = $conn->prepare("SELECT id_bruder, nama_bruder FROM bruder WHERE id_bruder = ?");
    $stmt->bind_param("i", $bruder_id);
    $stmt->execute();
    $result_bruder = $stmt->get_result();
    if ($result_bruder->num_rows > 0) {
        $bruder = $result_bruder->fetch_assoc();
        $bruder_name = $bruder['nama_bruder'];
        $nomor_bruder = $bruder['id_bruder'];
    }
    $stmt->close();

    // Query utama untuk menggabungkan kode perkiraan, realisasi, dan anggaran
    $sql_evaluasi = "
        SELECT
            kp.pos,
            kp.nama_akun,
            kp.tipe_akun,
            COALESCE(t_sum.realisasi, 0) AS realisasi_bulan_ini,
            COALESCE(ra.jumlah_anggaran, 0) AS anggaran
        FROM kode_perkiraan kp
        LEFT JOIN (
            SELECT id_perkiraan, SUM(nominal_penerimaan + nominal_pengeluaran) as realisasi
            FROM transaksi
            WHERE id_bruder = ? AND MONTH(tanggal_transaksi) = ? AND YEAR(tanggal_transaksi) = ?
            GROUP BY id_perkiraan
        ) t_sum ON kp.id_perkiraan = t_sum.id_perkiraan
        LEFT JOIN rencana_anggaran ra ON kp.id_perkiraan = ra.id_perkiraan
            AND ra.id_bruder = ?
            AND ra.bulan = ?
            AND ra.tahun = ?
        GROUP BY kp.id_perkiraan, kp.pos, kp.nama_akun, kp.tipe_akun, ra.jumlah_anggaran
        ORDER BY kp.tipe_akun DESC, kp.kode_perkiraan ASC
    ";
    
    $stmt_evaluasi = $conn->prepare($sql_evaluasi);
    // Gunakan bulan dan tahun dari filter
    $stmt_evaluasi->bind_param("iiiiii", $bruder_id, $bulan_laporan, $tahun_laporan, $bruder_id, $bulan_laporan, $tahun_laporan);
    $stmt_evaluasi->execute();
    $result_evaluasi = $stmt_evaluasi->get_result();

    while($row = $result_evaluasi->fetch_assoc()) {
        // --- PERBAIKAN LOGIKA PERHITUNGAN SALDO ---
        if ($row['tipe_akun'] == 'Penerimaan') {
            // Untuk Penerimaan: Saldo = Realisasi - Anggaran
            $row['saldo'] = $row['realisasi_bulan_ini'] - $row['anggaran'];
        } else {
            // Untuk Pengeluaran: Saldo = Anggaran - Realisasi
            $row['saldo'] = $row['anggaran'] - $row['realisasi_bulan_ini'];
        }
        
        $row['persen'] = ($row['anggaran'] > 0) ? ($row['realisasi_bulan_ini'] / $row['anggaran']) * 100 : 0;
        
        if ($row['tipe_akun'] == 'Penerimaan') {
            $data_evaluasi['PENERIMAAN'][] = $row;
            $total_realisasi_penerimaan += $row['realisasi_bulan_ini'];
            $total_anggaran_penerimaan += $row['anggaran'];
        } else {
             $data_evaluasi['PENGELUARAN'][] = $row;
             $total_realisasi_pengeluaran += $row['realisasi_bulan_ini'];
             $total_anggaran_pengeluaran += $row['anggaran'];
        }
    }
    $stmt_evaluasi->close();
}

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
    <title>Evaluasi - <?php echo htmlspecialchars($bruder_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background-color: #F3F4F6; font-weight: 600; }
        th, td { padding: 0.5rem; border: 1px solid #E5E7EB; text-align: left; }
        th { background-color: #F9FAFB; text-align: center; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#002244]">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white flex flex-col">
            <div class="p-6 text-center border-b">
                <img src="https://placehold.co/100x100/003366/FFFFFF?text=FIC" alt="Logo FIC" class="w-20 h-20 mx-auto mb-2 rounded-full">
            </div>
            <nav class="flex-grow pt-4">
                <a href="anggaran.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Data</a>
                <a href="kode_perkiraan.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kode Perkiraan</a>
                <a href="kas_harian.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Harian</a>
                <a href="bank.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Bank</a>
                <a href="bruder.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Bruder</a>
                <a href="lu_komunitas.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">LU Komunitas</a>
                <a href="evaluasi.php?id=<?php echo $bruder_id; ?>" class="sidebar-link active flex items-center px-6 py-3 text-gray-800 transition">Evaluasi</a>
                <a href="buku_besar.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Buku Besar</a>
                <a href="kas_opname.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Opname</a>
            </nav>
            <div class="p-6 border-t">
                 <a href="laporan.php" class="w-full text-center mb-4 bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-300 transition block">
                    Kembali
                </a>
                <a href="evaluasi.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition block">
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 p-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 h-full flex flex-col">
                <!-- Header -->
                <div class="flex items-start justify-between border-b pb-4 mb-6">
                    <div class="flex items-center space-x-4">
                        <div>
                            <p class="text-lg font-semibold text-gray-500">No.</p>
                            <p class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($nomor_bruder); ?></p>
                        </div>
                        <div class="border-l h-12"></div>
                        <div>
                            <p class="text-lg font-semibold text-gray-500">Nama</p>
                            <p class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($bruder_name); ?></p>
                        </div>
                    </div>
                    <!-- FORM FILTER BARU -->
                    <form method="POST" action="evaluasi.php?id=<?php echo $bruder_id; ?>" class="flex items-end space-x-2">
                        <div>
                            <label for="bulan" class="block text-sm font-medium text-gray-700">Bulan</label>
                            <select name="bulan" id="bulan" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php if ($i == $bulan_laporan) echo 'selected'; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 10)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label for="tahun" class="block text-sm font-medium text-gray-700">Tahun</label>
                            <input type="number" name="tahun" id="tahun" value="<?php echo $tahun_laporan; ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[#003366] hover:bg-[#004488]">
                            Tampilkan
                        </button>
                    </form>
                </div>

                <!-- Tabel Evaluasi -->
                <div class="flex-grow overflow-y-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr>
                                <th rowspan="2">Pos</th>
                                <th rowspan="2">Nama Perkiraan</th>
                                <th colspan="2">Realisasi</th>
                                <th rowspan="2">Anggaran</th>
                                <th rowspan="2">Saldo</th>
                                <th rowspan="2">%</th>
                            </tr>
                            <tr>
                                <th>Bulan Ini</th>
                                <th>Semua</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data Penerimaan -->
                            <tr class="font-bold bg-gray-100"><td colspan="7">JUMLAH PENERIMAAN</td></tr>
                            <?php foreach ($data_evaluasi['PENERIMAAN'] as $item): ?>
                            <tr>
                                <td class="text-center"><?php echo htmlspecialchars($item['pos']); ?></td>
                                <td><?php echo htmlspecialchars($item['nama_akun']); ?></td>
                                <td class="text-right"><?php echo number_format($item['realisasi_bulan_ini'], 0, ',', '.'); ?></td>
                                <td class="text-right">-</td> <!-- Kolom "Semua" belum diimplementasikan -->
                                <td class="text-right"><?php echo number_format($item['anggaran'], 0, ',', '.'); ?></td>
                                <td class="text-right"><?php echo number_format($item['saldo'], 0, ',', '.'); ?></td>
                                <td class="text-center"><?php echo round($item['persen']); ?>%</td>
                            </tr>
                            <?php endforeach; ?>

                             <!-- Data Pengeluaran -->
                            <tr class="font-bold bg-gray-100"><td colspan="7">JUMLAH PENGELUARAN</td></tr>
                            <?php foreach ($data_evaluasi['PENGELUARAN'] as $item): ?>
                            <tr>
                                <td class="text-center"><?php echo htmlspecialchars($item['pos']); ?></td>
                                <td><?php echo htmlspecialchars($item['nama_akun']); ?></td>
                                <td class="text-right"><?php echo number_format($item['realisasi_bulan_ini'], 0, ',', '.'); ?></td>
                                <td class="text-right">-</td>
                                <td class="text-right"><?php echo number_format($item['anggaran'], 0, ',', '.'); ?></td>
                                <td class="text-right"><?php echo number_format($item['saldo'], 0, ',', '.'); ?></td>
                                <td class="text-center"><?php echo round($item['persen']); ?>%</td>
                            </tr>
                            <?php endforeach; ?>

                            <!-- Total -->
                            <tr class="font-bold bg-gray-200">
                                <td colspan="2" class="text-right">JUMLAH BRUDER</td>
                                <td class="text-right"><?php echo number_format($total_realisasi_pengeluaran, 0, ',', '.'); ?></td>
                                <td>-</td>
                                <td class="text-right"><?php echo number_format($total_anggaran_pengeluaran, 0, ',', '.'); ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pt-6 text-right border-t mt-auto">
                    <button class="bg-[#003366] text-white font-bold py-2 px-8 rounded-full hover:bg-[#004488] transition">
                        Simpan
                    </button>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

