<?php
// =====================================================================
//            HALAMAN BRUDER (FINAL - NAVIGASI DIPERBAIKI)
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
$perjalanan_data = null;

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

    // Ambil data perjalanan untuk bruder ini
    $stmt_perjalanan = $conn->prepare("SELECT * FROM perjalanan_bruder WHERE id_bruder = ? ORDER BY tanggal_berangkat DESC");
    $stmt_perjalanan->bind_param("i", $bruder_id);
    $stmt_perjalanan->execute();
    $perjalanan_data = $stmt_perjalanan->get_result();
    $stmt_perjalanan->close();
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
    <title>Bruder - <?php echo htmlspecialchars($bruder_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background-color: #F3F4F6; font-weight: 600; }
        th, td { padding: 0.5rem 1rem; border: 1px solid #E5E7EB; }
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
                <a href="bruder.php?id=<?php echo $bruder_id; ?>" class="sidebar-link active flex items-center px-6 py-3 text-gray-800 transition">Bruder</a>
                <a href="lu_komunitas.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">LU Komunitas</a>
                <a href="evaluasi.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Evaluasi</a>
                <a href="buku_besar.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Buku Besar</a>
                <a href="kas_opname.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Opname</a>
            </nav>
            <div class="p-6 border-t">
                 <a href="laporan.php" class="w-full text-center mb-4 bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-300 transition block">
                    Kembali
                </a>
                <a href="bruder.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition block">
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

                <!-- Tabel Bruder -->
                <div class="flex-grow overflow-y-auto">
                    <table class="w-full text-center text-sm">
                        <thead class="sticky top-0 bg-white">
                            <tr>
                                <th rowspan="2" class="align-bottom">No</th>
                                <th rowspan="2" class="align-bottom">Nama</th>
                                <th colspan="2">Tgl Penambahan</th>
                                <th colspan="2">Tgl Pengurangan</th>
                                <th rowspan="2" class="align-bottom">Jumlah Hari</th>
                                <th rowspan="2" class="align-bottom">Keterangan</th>
                            </tr>
                            <tr>
                                <th>Datang</th>
                                <th>Pergi</th>
                                <th>Pergi</th>
                                <th>Pulang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($perjalanan_data && $perjalanan_data->num_rows > 0): ?>
                                <?php $no = 1; ?>
                                <?php while($row = $perjalanan_data->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($bruder_name); ?></td>
                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_berangkat']))); ?></td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_kembali']))); ?></td>
                                    <td><?php echo htmlspecialchars($row['jumlah_hari']); ?></td>
                                    <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-10 text-gray-500">Belum ada data perjalanan untuk bruder ini.</td>
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

