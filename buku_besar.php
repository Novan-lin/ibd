<?php
// =====================================================================
//            HALAMAN BUKU BESAR (DENGAN PILIHAN)
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

// Ambil tipe buku besar dari URL ('kas_harian' atau 'bank')
$type = $_GET['type'] ?? null;
$transactions = null;
$page_title = 'Buku Besar';

if ($bruder_id > 0) {
    // Ambil Nama Bruder
    $stmt_nama = $conn->prepare("SELECT id_bruder, nama_bruder FROM bruder WHERE id_bruder = ?");
    $stmt_nama->bind_param("i", $bruder_id);
    $stmt_nama->execute();
    $result_bruder = $stmt_nama->get_result();
    if ($result_bruder->num_rows > 0) {
        $bruder = $result_bruder->fetch_assoc();
        $bruder_name = $bruder['nama_bruder'];
        $nomor_bruder = $bruder['id_bruder'];
    }
    $stmt_nama->close();

    // Jika tipe sudah dipilih, ambil data transaksinya
    if ($type) {
        $sumber_dana = ($type === 'kas_harian') ? 'Kas Harian' : 'Bank';
        $page_title = 'Buku Besar - ' . $sumber_dana;

        $stmt_trans = $conn->prepare(
            "SELECT tanggal_transaksi, keterangan, reff, nominal_penerimaan, nominal_pengeluaran
             FROM transaksi
             WHERE id_bruder = ? AND sumber_dana = ?
             ORDER BY tanggal_transaksi DESC, id_transaksi DESC"
        );
        $stmt_trans->bind_param("is", $bruder_id, $sumber_dana);
        $stmt_trans->execute();
        $transactions = $stmt_trans->get_result();
        $stmt_trans->close();
    }
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
    <title><?php echo htmlspecialchars($page_title); ?> - Aplikasi FIC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background-color: #F3F4F6; font-weight: 600; }
        th, td { padding: 0.75rem 1rem; border: 1px solid #E5E7EB; text-align: left; }
        th { background-color: #F3F4F6; }
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
                <a href="evaluasi.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Evaluasi</a>
                <a href="buku_besar.php?id=<?php echo $bruder_id; ?>" class="sidebar-link active flex items-center px-6 py-3 text-gray-800 transition">Buku Besar</a>
                <a href="kas_opname.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Opname</a>
            </nav>
            <div class="p-6 border-t">
                 <a href="laporan.php" class="w-full text-center mb-4 bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-300 transition block">
                    Kembali
                </a>
                <a href="buku_besar.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition block">
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 p-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 h-full flex flex-col">
                
                <?php if (!$type): ?>
                <!-- Tampilan Pilihan -->
                <div class="flex-grow flex flex-col items-center justify-center">
                    <div class="bg-[#003366] p-12 rounded-2xl shadow-xl text-center">
                        <h1 class="text-2xl font-bold text-white mb-8">Pilih Buku Besar yang ingin ditampilkan</h1>
                        <div class="flex space-x-6">
                            <a href="buku_besar.php?id=<?php echo $bruder_id; ?>&type=kas_harian" class="bg-gray-200 text-gray-800 font-bold py-4 px-12 rounded-lg hover:bg-gray-300 transition text-lg">
                                Kas Harian
                            </a>
                            <a href="buku_besar.php?id=<?php echo $bruder_id; ?>&type=bank" class="bg-gray-200 text-gray-800 font-bold py-4 px-12 rounded-lg hover:bg-gray-300 transition text-lg">
                                Bank
                            </a>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <!-- Tampilan Tabel Buku Besar -->
                <h1 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4 text-center"><?php echo htmlspecialchars(strtoupper($page_title)); ?></h1>
                <div class="flex-grow overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-white">
                            <tr>
                                <th class="w-32">Tanggal</th>
                                <th>Keterangan</th>
                                <th class="w-24">Ref</th>
                                <th class="w-40">Penerimaan</th>
                                <th class="w-40">Pengeluaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions && $transactions->num_rows > 0): ?>
                                <?php while($row = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_transaksi']))); ?></td>
                                    <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['reff']); ?></td>
                                    <td class="text-right"><?php echo "Rp " . number_format($row['nominal_penerimaan'], 2, ',', '.'); ?></td>
                                    <td class="text-right"><?php echo "Rp " . number_format($row['nominal_pengeluaran'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-gray-500">
                                        Tidak ada data <?php echo htmlspecialchars($sumber_dana); ?> untuk bruder ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>
