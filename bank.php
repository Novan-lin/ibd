<?php
// =====================================================================
//            HALAMAN BANK (VERSI PERBAIKAN)
// =====================================================================

// --- 1. Memulai Sesi & Keamanan ---
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// --- 2. Koneksi Database & Ambil Data ---
$conn = new mysqli('localhost', 'root', '', 'db_fic_bruderan');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Logika baru untuk mengambil ID Bruder yang lebih kuat
$bruder_id = 0;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $bruder_id = (int)$_GET['id'];
    $_SESSION['bruder_id_terpilih'] = $bruder_id;
} elseif (isset($_SESSION['bruder_id_terpilih'])) {
    $bruder_id = (int)$_SESSION['bruder_id_terpilih'];
}

$bruder_name = 'Pilih Bruder Dahulu';
$nomor_bruder = '-';

if ($bruder_id > 0) {
    // Ambil Nama Bruder
    $stmt = $conn->prepare("SELECT id_bruder, nama_bruder FROM bruder WHERE id_bruder = ?");
    $stmt->bind_param("i", $bruder_id);
    $stmt->execute();
    $result_bruder_info = $stmt->get_result();
    if ($result_bruder_info->num_rows > 0) {
        $bruder = $result_bruder_info->fetch_assoc();
        $bruder_name = $bruder['nama_bruder'];
        $nomor_bruder = $bruder['id_bruder'];
    }
    $stmt->close();

    // Ambil Data Transaksi Bank
    $stmt_trans = $conn->prepare(
        "SELECT t.tanggal_transaksi, kp.pos, kp.kode_perkiraan, kp.nama_akun, t.keterangan, t.reff, t.nominal_penerimaan, t.nominal_pengeluaran
         FROM transaksi t
         JOIN kode_perkiraan kp ON t.id_perkiraan = kp.id_perkiraan
         WHERE t.id_bruder = ? AND t.sumber_dana = 'Bank'
         ORDER BY t.tanggal_transaksi DESC"
    );
    $stmt_trans->bind_param("i", $bruder_id);
    $stmt_trans->execute();
    $transactions = $stmt_trans->get_result();
    $stmt_trans->close();
}

// --- 3. Logika Logout ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['bruder_id_terpilih']);
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
    <title>Bank - <?php echo htmlspecialchars($bruder_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background-color: #F3F4F6; font-weight: 600; }
        th, td { padding: 0.75rem 1rem; border: 1px solid #E5E7EB; text-align: left;}
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
                <a href="bank.php?id=<?php echo $bruder_id; ?>" class="sidebar-link active flex items-center px-6 py-3 text-gray-800 transition">Bank</a>
                <a href="bruder.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Bruder</a>
                <a href="lu_komunitas.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">LU Komunitas</a>
                <a href="#" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Evaluasi</a>
                <a href="#" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Buku Besar</a>
                <a href="#" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Opname</a>
            </nav>
            <div class="p-6 border-t">
                 <a href="laporan.php" class="w-full text-center mb-4 bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-300 transition block">
                    Kembali
                </a>
                <a href="bank.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition block">
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 p-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 h-full flex flex-col">
                <!-- Header -->
                <div class="flex items-center space-x-4 border-b pb-4 mb-6">
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

                <!-- Tabel Input Bank -->
                <div class="flex-grow overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-white">
                            <tr>
                                <th>Tgl</th>
                                <th>Pos</th>
                                <th>Kode Perkiraan</th>
                                <th>Akun</th>
                                <th>Keterangan</th>
                                <th>Reff</th>
                                <th>Penerimaan</th>
                                <th>Pengeluaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($transactions) && $transactions->num_rows > 0): ?>
                                <?php while($row = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_transaksi']))); ?></td>
                                    <td><?php echo htmlspecialchars($row['pos']); ?></td>
                                    <td><?php echo htmlspecialchars($row['kode_perkiraan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_akun']); ?></td>
                                    <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['reff']); ?></td>
                                    <td class="text-right"><?php echo "Rp " . number_format($row['nominal_penerimaan'], 2, ',', '.'); ?></td>
                                    <td class="text-right"><?php echo "Rp " . number_format($row['nominal_pengeluaran'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-10 text-gray-500">Belum ada transaksi bank untuk bruder ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Tombol Simpan -->
                <div class="pt-6 text-right border-t mt-auto">
                    <button class="bg-[#003366] text-white font-bold py-2 px-8 rounded-full hover:bg-[#004488] transition">
                        Tambah Transaksi
                    </button>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

