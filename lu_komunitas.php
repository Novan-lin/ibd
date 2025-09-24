<?php
// =====================================================================
//            HALAMAN LU KOMUNITAS (FINAL - NAVIGASI DIPERBAIKI)
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

$laporan_data = [];
$total_penerimaan = 0;
$total_pengeluaran = 0;

if ($bruder_id > 0) {
    // Ambil Nama Bruder
    $stmt = $conn->prepare("SELECT id_bruder, nama_bruder FROM bruder WHERE id_bruder = ?");
    $stmt->bind_param("i", $bruder_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $bruder = $result->fetch_assoc();
        $bruder_name = $bruder['nama_bruder'];
        $nomor_bruder = $bruder['id_bruder'];
    }
    $stmt->close();

    // Ambil Data Laporan Umum (LU)
    $stmt_lu = $conn->prepare(
       "SELECT 
            kp.kode_perkiraan,
            kp.nama_akun,
            SUM(t.nominal_penerimaan) as total_penerimaan,
            SUM(t.nominal_pengeluaran) as total_pengeluaran
        FROM transaksi t
        JOIN kode_perkiraan kp ON t.id_perkiraan = kp.id_perkiraan
        WHERE t.id_bruder = ?
        GROUP BY kp.id_perkiraan
        ORDER BY kp.kode_perkiraan ASC"
    );
    $stmt_lu->bind_param("i", $bruder_id);
    $stmt_lu->execute();
    $laporan_result = $stmt_lu->get_result();
    
    while ($row = $laporan_result->fetch_assoc()) {
        $laporan_data[] = $row;
        $total_penerimaan += $row['total_penerimaan'];
        $total_pengeluaran += $row['total_pengeluaran'];
    }
    $stmt_lu->close();
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
    <title>LU Komunitas - <?php echo htmlspecialchars($bruder_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background-color: #F3F4F6; font-weight: 600; }
        th, td { padding: 0.5rem; border: 1px solid #E5E7EB; text-align: left; }
        th { background-color: #F9FAFB; }
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
                <a href="lu_komunitas.php?id=<?php echo $bruder_id; ?>" class="sidebar-link active flex items-center px-6 py-3 text-gray-800 transition">LU Komunitas</a>
                <a href="evaluasi.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Evaluasi</a>
                <a href="buku_besar.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Buku Besar</a>
                <a href="kas_opname.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Opname</a>
            </nav>
            <div class="p-6 border-t">
                 <a href="laporan.php" class="w-full text-center mb-4 bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-300 transition block">
                    Kembali
                </a>
                <a href="lu_komunitas.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition block">
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 p-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 h-full flex flex-col">
                 <h1 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Laporan Umum - <?php echo htmlspecialchars($bruder_name); ?></h1>
                <!-- Tabel LU Komunitas -->
                <div class="flex-grow overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-white">
                            <tr>
                                <th>No</th>
                                <th>Kode Perkiraan</th>
                                <th>Nama Perkiraan</th>
                                <th>Penerimaan</th>
                                <th>Pengeluaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($laporan_data)): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($laporan_data as $row): ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['kode_perkiraan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_akun']); ?></td>
                                    <td class="text-right"><?php echo "Rp " . number_format($row['total_penerimaan'], 2, ',', '.'); ?></td>
                                    <td class="text-right"><?php echo "Rp " . number_format($row['total_pengeluaran'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="font-bold bg-gray-50">
                                    <td colspan="3" class="text-right">Jumlah</td>
                                    <td class="text-right"><?php echo "Rp " . number_format($total_penerimaan, 2, ',', '.'); ?></td>
                                    <td class="text-right"><?php echo "Rp " . number_format($total_pengeluaran, 2, ',', '.'); ?></td>
                                </tr>
                                <tr class="font-bold bg-gray-50">
                                    <td colspan="3" class="text-right">Saldo Kas dan Bank</td>
                                    <td class="text-right text-green-600"><?php echo "Rp " . number_format($total_penerimaan - $total_pengeluaran, 2, ',', '.'); ?></td>
                                    <td></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-gray-500">Tidak ada data untuk ditampilkan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

