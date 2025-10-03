<?php
// =====================================================================
//            HALAMAN KAS PENGELUARAN BRUDER - DENGAN UPLOAD FOTO
// =====================================================================

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'bruder') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'db_fic_bruderan');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Data bruder dari session
$id_bruder = $_SESSION['id_bruder'];
$nama_bruder = $_SESSION['nama_bruder'];
$transactions = null;
$pesan_sukses = '';
$pesan_error = '';

// --- LOGIKA PROSES FORM TAMBAH TRANSAKSI (DIPINDAHKAN KE AJAX_HANDLER.PHP) ---
// Logika ini sekarang ditangani oleh ajax_handler.php untuk alur persetujuan.
// Pesan sukses/error akan di-handle oleh JavaScript.

// Debug: Cek session dan data bruder
echo "<!-- Debug Info:
ID Bruder: $id_bruder
Nama Bruder: $nama_bruder
Session ID Cabang: " . ($_SESSION['id_cabang'] ?? 'NULL') . "
-->";

// Ambil Data Pengajuan Pengeluaran untuk ditampilkan
$stmt_trans = $conn->prepare(
    "SELECT p.id_pengajuan, p.tanggal_pengajuan, kp.kode_perkiraan, kp.nama_akun, p.keterangan, p.nominal, p.status, p.catatan_bendahara
     FROM pengajuan_pengeluaran p
     JOIN kode_perkiraan kp ON p.id_perkiraan = kp.id_perkiraan
     WHERE p.id_bruder = ?
     ORDER BY p.tanggal_pengajuan DESC"
);
$stmt_trans->bind_param("i", $id_bruder);
$stmt_trans->execute();
$transactions = $stmt_trans->get_result();

// Debug: Cek hasil query
$debug_transaksi = $transactions ? $transactions->num_rows : 0;
echo "<!-- Debug: Ditemukan $debug_transaksi pengajuan pengeluaran -->";

$stmt_trans->close();

// Ambil daftar kode perkiraan untuk dropdown form (hanya pengeluaran dari cabang bruder)
$id_cabang = $_SESSION['id_cabang'] ?? 1;
$result_perkiraan = $conn->query("SELECT id_perkiraan, kode_perkiraan, nama_akun FROM kode_perkiraan WHERE tipe_akun = 'Pengeluaran' AND id_cabang = " . (int)$id_cabang . " ORDER BY kode_perkiraan");

// Jika tidak ada kode perkiraan pengeluaran di cabang ini, gunakan dari cabang utama (ID 1)
if (!$result_perkiraan || $result_perkiraan->num_rows === 0) {
    $result_perkiraan = $conn->query("SELECT id_perkiraan, kode_perkiraan, nama_akun FROM kode_perkiraan WHERE tipe_akun = 'Pengeluaran' AND id_cabang = 1 ORDER BY kode_perkiraan");
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
    <title>Kas Pengeluaran - <?php echo htmlspecialchars($nama_bruder); ?></title>
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
        <aside class="w-56 bg-white flex flex-col shadow-lg">
            <div class="p-4 text-center border-b border-gray-200">
                <img src="https://placehold.co/80x80/003366/FFFFFF?text=FIC" alt="Logo FIC" class="w-16 h-16 mx-auto mb-2 rounded-lg">
            </div>
            <nav class="flex-grow pt-2">
                <a href="dashboard_bruder.php" class="sidebar-link flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-all duration-200 rounded-lg mx-2 mb-1">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="kas_penerimaan_bruder.php" class="sidebar-link flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-all duration-200 rounded-lg mx-2 mb-1">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    Kas Penerimaan
                </a>
                <a href="kas_pengeluaran_bruder.php" class="sidebar-link active flex items-center px-4 py-3 text-blue-700 bg-blue-50 font-semibold rounded-lg mx-2 mb-1">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    Kas Pengeluaran
                </a>
            </nav>
            <div class="p-4 border-t border-gray-200">
                 <a href="dashboard_bruder.php" class="w-full text-center mb-3 bg-gray-100 text-gray-700 font-medium py-2.5 px-4 rounded-lg hover:bg-gray-200 transition-all duration-200 block">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Dashboard
                </a>
                <a href="kas_pengeluaran_bruder.php?action=logout" class="w-full text-center bg-red-500 text-white font-medium py-2.5 px-4 rounded-lg hover:bg-red-600 transition-all duration-200 block">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 p-3 bg-gray-50 overflow-hidden">
            <div class="bg-white rounded-lg shadow-sm p-4 h-full flex flex-col" style="height: calc(100vh - 2rem); max-height: none;">
                <!-- Header -->
                <div class="border-b border-gray-200 pb-3 mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <h1 class="text-xl font-bold text-gray-800">Kas Pengeluaran</h1>
                        <div class="text-right">
                            <p class="text-xs font-medium text-gray-500">Cabang</p>
                            <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['kode_cabang'] . ' - ' . $_SESSION['nama_cabang']); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 text-sm">
                        <div>
                            <span class="text-gray-500">ID:</span>
                            <span class="font-semibold text-gray-800 ml-1"><?php echo htmlspecialchars($id_bruder); ?></span>
                        </div>
                        <div class="w-px h-4 bg-gray-300"></div>
                        <div>
                            <span class="text-gray-500">Nama:</span>
                            <span class="font-semibold text-gray-800 ml-1"><?php echo htmlspecialchars($nama_bruder); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Notifikasi -->
                <?php if ($pesan_sukses): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert"><?php echo $pesan_sukses; ?></div>
                <?php endif; ?>
                <?php if ($pesan_error): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg" role="alert"><?php echo $pesan_error; ?></div>
                <?php endif; ?>

                <!-- Form Pengeluaran -->
                <div class="mb-4 p-4 border border-gray-200 rounded-lg bg-gradient-to-br from-gray-50 to-gray-100">
                    <div class="flex items-center mb-3">
                        <div class="bg-blue-100 p-1.5 rounded-md mr-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold text-gray-800">Catat Pengeluaran</h2>
                    </div>
                    <form id="pengeluaranForm" class="space-y-4" enctype="multipart/form-data">
                        <!-- Row 1: Tanggal dan Kode Perkiraan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="md:col-span-1">
                                <label for="tanggal" class="block text-xs font-medium text-gray-700 mb-1">
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Tanggal
                                    </span>
                                </label>
                                <input type="date" name="tanggal" id="tanggal" required value="<?php echo date('Y-m-d'); ?>" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm">
                            </div>
                            <div class="md:col-span-1">
                                <label for="id_perkiraan" class="block text-xs font-medium text-gray-700 mb-1">
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        Kode Perkiraan
                                    </span>
                                </label>
                                <select name="id_perkiraan" id="id_perkiraan" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm">
                                    <option value="">-- Pilih Akun Pengeluaran --</option>
                                    <?php if (isset($result_perkiraan)) { while($kp = $result_perkiraan->fetch_assoc()): ?>
                                    <option value="<?php echo $kp['id_perkiraan']; ?>"><?php echo htmlspecialchars($kp['kode_perkiraan'] . ' - ' . $kp['nama_akun']); ?></option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                        </div>

                        <!-- Row 2: Keterangan dan Nominal -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="md:col-span-1">
                                <label for="keterangan" class="block text-xs font-medium text-gray-700 mb-1">
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                        </svg>
                                        Keterangan
                                    </span>
                                </label>
                                <input type="text" name="keterangan" id="keterangan" required placeholder="Contoh: Beli bahan makanan" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm">
                            </div>
                            <div class="md:col-span-1">
                                <label for="nominal" class="block text-xs font-medium text-gray-700 mb-1">
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                        Nominal (Rp)
                                    </span>
                                </label>
                                <input type="text" name="nominal" id="nominal" required placeholder="0" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm">
                            </div>
                        </div>

                        <!-- Row 3: File Upload -->
                        <div>
                            <label for="foto_bukti" class="block text-xs font-medium text-gray-700 mb-1">
                                <span class="flex items-center">
                                    <svg class="w-3 h-3 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Foto Bukti/Kwitansi
                                </span>
                            </label>
                            <div class="mt-1 flex justify-center px-4 pt-3 pb-4 border-2 border-gray-300 border-dashed rounded-md hover:border-blue-400 transition-colors duration-200">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-8 w-8 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-xs text-gray-600">
                                        <label for="foto_bukti" class="relative cursor-pointer bg-white rounded font-medium text-blue-600 hover:text-blue-500">
                                            <span>Upload file</span>
                                            <input type="file" name="foto_bukti" id="foto_bukti" accept="image/*" class="sr-only">
                                        </label>
                                        <p class="pl-1">atau drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">JPG, PNG, GIF max 5MB</p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-2 border-t border-gray-200">
                            <button type="button" id="submitPengeluaranBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Simpan Pengeluaran
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabel Riwayat Pengeluaran -->
                <div class="flex-grow bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="bg-green-100 p-2 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800">Riwayat Pengeluaran</h3>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo $transactions ? $transactions->num_rows : 0; ?> Total
                                </span>
                                <a href="kas_pengeluaran_bruder_full.php" target="_blank"
                                   class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 transition-colors duration-200">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    Lihat Lengkap
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto overflow-y-auto flex-1" style="height: calc(100vh - 16rem); min-height: 300px;">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-white shadow-sm z-10">
                                <tr class="bg-gray-50">
                                    <th class="px-3 py-3 text-left font-bold text-gray-900 border-b border-gray-200 w-20">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Tanggal
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-left font-bold text-gray-900 border-b border-gray-200 w-24">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            Kode
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-left font-bold text-gray-900 border-b border-gray-200 min-w-48">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                            </svg>
                                            Keterangan
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-right font-bold text-gray-900 border-b border-gray-200 w-32">
                                        <div class="flex items-center justify-end">
                                            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                            Nominal
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-center font-bold text-gray-900 border-b border-gray-200 w-28">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if ($transactions && $transactions->num_rows > 0): ?>
                                    <?php while($row = $transactions->fetch_assoc()):
                                        $status_bg = 'bg-yellow-100 text-yellow-800';
                                        if ($row['status'] == 'disetujui') {
                                            $status_bg = 'bg-green-100 text-green-800';
                                        } elseif ($row['status'] == 'ditolak') {
                                            $status_bg = 'bg-red-100 text-red-800';
                                        }
                                    ?>
                                    <tr class="hover:bg-blue-50 transition-all duration-200">
                                        <td class="px-3 py-3 text-gray-900 font-medium">
                                            <div class="flex flex-col">
                                                <span class="font-semibold text-sm"><?php echo htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_pengajuan']))); ?></span>
                                                <span class="text-xs text-gray-500"><?php echo htmlspecialchars(date('H:i', strtotime($row['tanggal_pengajuan']))); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($row['kode_perkiraan']); ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-gray-700">
                                            <div class="max-w-48 truncate text-sm" title="<?php echo htmlspecialchars($row['keterangan']); ?>">
                                                <?php echo htmlspecialchars($row['keterangan']); ?>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <span class="font-bold text-red-600 text-sm">Rp <?php echo number_format($row['nominal'], 0, ',', '.'); ?></span>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $status_bg; ?>"
                                                  title="<?php echo htmlspecialchars($row['catatan_bendahara'] ?? ''); ?>">
                                                <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-4 py-16 text-center">
                                            <div class="flex flex-col items-center justify-center py-8">
                                                <div class="bg-gray-100 p-4 rounded-full mb-4">
                                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </div>
                                                <h4 class="text-lg font-semibold text-gray-500 mb-2">Belum ada pengeluaran</h4>
                                                <p class="text-sm text-gray-400 max-w-sm">Pengeluaran yang Anda catat akan muncul di sini. Mulai dengan mengisi form pengeluaran di atas.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Detail Pengeluaran (Dihapus untuk sementara) -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pengeluaranForm = document.getElementById('pengeluaranForm');
            const submitBtn = document.getElementById('submitPengeluaranBtn');
            const nominalInput = document.getElementById('nominal');
            const fotoInput = document.getElementById('foto_bukti');

            // Auto-format nominal input
            nominalInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d]/g, '');
                if (value) {
                    e.target.value = parseInt(value).toLocaleString('id-ID');
                }
            });

            nominalInput.addEventListener('focus', function(e) {
                // Remove formatting for easier editing
                e.target.value = e.target.value.replace(/[^\d]/g, '');
            });

            // AJAX Pengeluaran submission
            function submitPengeluaran() {
                const formData = new FormData(pengeluaranForm);

                // Remove formatting from nominal before sending
                const nominalValue = nominalInput.value.replace(/[^\d]/g, '');
                formData.set('nominal', nominalValue);

                // Add required action parameter
                formData.append('action', 'ajukan_pengeluaran');
                formData.append('bruder_id', <?php echo $id_bruder; ?>);

                // Show loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mx-auto"></div>';

                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Simpan Pengeluaran';

                    if (data.success) {
                        alert(data.message);
                        pengeluaranForm.reset();
                        // Refresh page to show new transaction
                        location.reload();
                    } else {
                        alert('Gagal menyimpan pengeluaran: ' + data.message);
                    }
                })
                .catch(error => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Simpan Pengeluaran';
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                    console.error('Pengeluaran error:', error);
                });
            }

            // Event listeners
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Validate form - foto bukti tidak wajib diisi
                const idPerkiraan = document.getElementById('id_perkiraan').value;
                const tanggal = document.getElementById('tanggal').value;
                const keterangan = document.getElementById('keterangan').value;
                const nominal = nominalInput.value.replace(/[^\d]/g, '');

                if (!idPerkiraan || !tanggal || !keterangan || !nominal) {
                    alert('Field bertanda * harus diisi! (Foto bukti bersifat optional)');
                    return;
                }

                if (confirm('Apakah Anda yakin ingin menyimpan pengeluaran ini?')) {
                    submitPengeluaran();
                }
            });

            // Enter key support for form
            pengeluaranForm.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && e.target.type !== 'textarea' && e.target.type !== 'file') {
                    e.preventDefault();
                    submitBtn.click();
                }
            });

            // File preview
            fotoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    if (file.size > maxSize) {
                        alert('Ukuran file terlalu besar. Maksimal 5MB.');
                        e.target.value = '';
                        return;
                    }

                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Format file tidak didukung. Gunakan JPG, PNG, atau GIF.');
                        e.target.value = '';
                        return;
                    }
                }
            });

            // Modal functions (dihapus untuk sementara)
        });
    </script>
</body>
</html>
