<?php
// =====================================================================
//            HALAMAN KAS HARIAN (FINAL & FUNGSIONAL)
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
$transactions = null;
$pesan_sukses = '';
$pesan_error = '';

// --- LOGIKA PROSES FORM TAMBAH TRANSAKSI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_transaksi_kas'])) {
    if ($bruder_id > 0) {
        $id_perkiraan = (int)$_POST['id_perkiraan'];
        $tanggal = $_POST['tanggal'];
        $keterangan = $_POST['keterangan'];
        $tipe = $_POST['tipe_transaksi'];
        $nominal = (float)$_POST['nominal'];
        $reff = $_POST['reff'] ?? null;

        // Memanggil Stored Procedure `catat_transaksi_keuangan`
        // Ini adalah cara yang lebih aman dan terstruktur
        $stmt_proc = $conn->prepare("CALL catat_transaksi_keuangan(?, ?, ?, ?, 'Kas Harian', ?, ?)");
        $stmt_proc->bind_param("iisssd", $bruder_id, $id_perkiraan, $tanggal, $keterangan, $tipe, $nominal);
        
        if ($stmt_proc->execute()) {
            $pesan_sukses = "Transaksi kas harian berhasil dicatat!";
        } else {
            $pesan_error = "Gagal mencatat transaksi: " . $conn->error;
        }
        $stmt_proc->close();
    }
}


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

    // Ambil Data Transaksi Kas Harian untuk ditampilkan
    $stmt_trans = $conn->prepare(
        "SELECT t.tanggal_transaksi, kp.pos, kp.kode_perkiraan, kp.nama_akun, t.keterangan, t.reff, t.nominal_penerimaan, t.nominal_pengeluaran
         FROM transaksi t
         JOIN kode_perkiraan kp ON t.id_perkiraan = kp.id_perkiraan
         WHERE t.id_bruder = ? AND t.sumber_dana = 'Kas Harian'
         ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC"
    );
    $stmt_trans->bind_param("i", $bruder_id);
    $stmt_trans->execute();
    $transactions = $stmt_trans->get_result();
    $stmt_trans->close();

    // Ambil daftar kode perkiraan untuk dropdown form
    $result_perkiraan = $conn->query("SELECT id_perkiraan, kode_perkiraan, nama_akun FROM kode_perkiraan ORDER BY kode_perkiraan");
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
    <title>Kas Harian - <?php echo htmlspecialchars($bruder_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background-color: #F3F4F6; font-weight: 600; }
        th, td { padding: 0.75rem 1rem; border: 1px solid #E5E7EB; text-align: left; }
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
                <a href="kas_harian.php?id=<?php echo $bruder_id; ?>" class="sidebar-link active flex items-center px-6 py-3 text-gray-800 transition">Kas Harian</a>
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
                <a href="kas_harian.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition block">
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

                <!-- Notifikasi -->
                <?php if ($pesan_sukses): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert"><?php echo $pesan_sukses; ?></div>
                <?php endif; ?>
                <?php if ($pesan_error): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg" role="alert"><?php echo $pesan_error; ?></div>
                <?php endif; ?>

                <!-- Form Tambah Transaksi -->
                <div class="mb-8 p-6 border rounded-lg bg-gray-50">
                    <h2 class="text-xl font-bold text-gray-700 mb-4">Tambah Transaksi Kas Harian</h2>
                    <form method="POST" action="kas_harian.php?id=<?php echo $bruder_id; ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                        <div>
                            <label for="tanggal" class="block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="date" name="tanggal" required value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="id_perkiraan" class="block text-sm font-medium text-gray-700">Kode Perkiraan</label>
                            <select name="id_perkiraan" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Pilih Akun --</option>
                                <?php if (isset($result_perkiraan)) { while($kp = $result_perkiraan->fetch_assoc()): ?>
                                <option value="<?php echo $kp['id_perkiraan']; ?>"><?php echo htmlspecialchars($kp['kode_perkiraan'] . ' - ' . $kp['nama_akun']); ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                        <div>
                            <label for="tipe_transaksi" class="block text-sm font-medium text-gray-700">Tipe</label>
                            <select name="tipe_transaksi" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="Pengeluaran">Pengeluaran</option>
                                <option value="Penerimaan">Penerimaan</option>
                            </select>
                        </div>
                        <div class="md:col-span-2 lg:col-span-1">
                            <label for="keterangan" class="block text-sm font-medium text-gray-700">Keterangan</label>
                            <input type="text" name="keterangan" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="nominal" class="block text-sm font-medium text-gray-700">Nominal (Rp)</label>
                            <input type="number" step="0.01" name="nominal" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="lg:col-span-3 text-right">
                            <button type="submit" name="tambah_transaksi_kas" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-full text-white bg-[#003366] hover:bg-[#004488]">
                                Simpan Transaksi
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabel Riwayat Kas Harian -->
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
                            <?php if ($transactions && $transactions->num_rows > 0): ?>
                                <?php while($row = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_transaksi']))); ?></td>
                                    <td><?php echo htmlspecialchars($row['pos']); ?></td>
                                    <td><?php echo htmlspecialchars($row['kode_perkiraan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_akun']); ?></td>
                                    <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['reff']); ?></td>
                                    <td class="text-right"><?php echo "Rp " . number_format($row['nominal_penerimaan'], 0, ',', '.'); ?></td>
                                    <td class="text-right"><?php echo "Rp " . number_format($row['nominal_pengeluaran'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-10 text-gray-500">Belum ada transaksi kas harian untuk bruder ini.</td>
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

