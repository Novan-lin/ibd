<?php
// =====================================================================
//            HALAMAN KAS PENGELUARAN BRUDER - VIEW LENGKAP (TAB BARU)
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

// Ambil Data Transaksi Pengeluaran untuk ditampilkan (lengkap tanpa batasan)
$stmt_trans = $conn->prepare(
    "SELECT t.id_transaksi, t.tanggal_transaksi, kp.pos, kp.kode_perkiraan, kp.nama_akun, t.keterangan, t.reff,
            t.nominal_penerimaan, t.nominal_pengeluaran, t.id_cabang, c.nama_cabang, c.kode_cabang
     FROM transaksi t
     JOIN kode_perkiraan kp ON t.id_perkiraan = kp.id_perkiraan
     JOIN cabang c ON t.id_cabang = c.id_cabang
     WHERE t.id_bruder = ? AND t.sumber_dana = 'Kas Harian' AND t.nominal_pengeluaran > 0
     ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC"
);
$stmt_trans->bind_param("i", $id_bruder);
$stmt_trans->execute();
$transactions = $stmt_trans->get_result();
$stmt_trans->close();

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
    <title>Riwayat Pengeluaran Lengkap - <?php echo htmlspecialchars($nama_bruder); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .header-link {
            color: #0066CC;
            text-decoration: none;
        }
        .header-link:hover {
            text-decoration: underline;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-full mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="kas_pengeluaran_bruder.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali ke View Compact
                    </a>
                    <div class="h-6 w-px bg-gray-300"></div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Riwayat Pengeluaran Lengkap</h1>
                        <p class="text-sm text-gray-600">View lengkap tanpa batasan lebar - <?php echo htmlspecialchars($nama_bruder); ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-500">Cabang</p>
                    <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['kode_cabang'] . ' - ' . $_SESSION['nama_cabang']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten Utama -->
    <main class="max-w-full mx-auto p-6">
        <!-- Info Summary -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $transactions ? $transactions->num_rows : 0; ?></div>
                    <div class="text-sm text-gray-600">Total Pengeluaran</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">
                        Rp <?php
                        $total_nominal = 0;
                        if ($transactions) {
                            $transactions->data_seek(0); // Reset pointer
                            while($row = $transactions->fetch_assoc()) {
                                $total_nominal += $row['nominal_pengeluaran'];
                            }
                            $transactions->data_seek(0); // Reset lagi untuk tabel
                        }
                        echo number_format($total_nominal, 0, ',', '.');
                        ?>
                    </div>
                    <div class="text-sm text-gray-600">Total Nominal</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600"><?php echo htmlspecialchars($id_bruder); ?></div>
                    <div class="text-sm text-gray-600">ID Bruder</div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-medium text-gray-600"><?php echo date('d M Y H:i'); ?></div>
                    <div class="text-xs text-gray-500">Waktu Generate</div>
                </div>
            </div>
        </div>

        <!-- Tabel Lengkap -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Data Pengeluaran Lengkap</h3>
                <p class="text-sm text-gray-600">Semua informasi ditampilkan tanpa batasan lebar</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left font-bold text-gray-900 border-b border-gray-200">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Tanggal & Waktu
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900 border-b border-gray-200">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    Pos
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900 border-b border-gray-200">Kode Perkiraan</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900 border-b border-gray-200">Nama Akun Lengkap</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900 border-b border-gray-200">Keterangan Lengkap</th>
                            <th class="px-6 py-4 text-right font-bold text-gray-900 border-b border-gray-200">
                                <div class="flex items-center justify-end">
                                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    Nominal Pengeluaran
                                </div>
                            </th>
                            <th class="px-6 py-4 text-center font-bold text-gray-900 border-b border-gray-200">Cabang</th>
                            <th class="px-6 py-4 text-center font-bold text-gray-900 border-b border-gray-200">Referensi</th>
                            <th class="px-6 py-4 text-center font-bold text-gray-900 border-b border-gray-200">Status Bukti</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($transactions && $transactions->num_rows > 0): ?>
                            <?php $no = 1; ?>
                            <?php while($row = $transactions->fetch_assoc()): ?>
                            <tr class="hover:bg-blue-50 transition-all duration-200">
                                <td class="px-6 py-4 text-gray-900 font-medium">
                                    <div class="flex flex-col">
                                        <span class="font-semibold"><?php echo htmlspecialchars(date('d F Y', strtotime($row['tanggal_transaksi']))); ?></span>
                                        <span class="text-sm text-gray-500"><?php echo htmlspecialchars(date('H:i:s', strtotime($row['tanggal_transaksi']))); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($row['pos']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 font-semibold"><?php echo htmlspecialchars($row['kode_perkiraan']); ?></td>
                                <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($row['nama_akun']); ?></td>
                                <td class="px-6 py-4 text-gray-700">
                                    <div class="max-w-xs">
                                        <?php echo htmlspecialchars($row['keterangan']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-bold text-red-600 text-lg">Rp <?php echo number_format($row['nominal_pengeluaran'], 0, ',', '.'); ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <?php echo htmlspecialchars($row['kode_cabang'] . ' - ' . $row['nama_cabang']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-gray-700">
                                    <?php echo htmlspecialchars($row['reff'] ?? 'Tidak ada'); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center">
                                        <span class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Tersimpan
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php $no++; endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center py-8">
                                        <div class="bg-gray-100 p-4 rounded-full mb-4">
                                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <h4 class="text-lg font-semibold text-gray-500 mb-2">Belum ada pengeluaran</h4>
                                        <p class="text-sm text-gray-400 max-w-sm">Pengeluaran yang Anda catat akan muncul di sini dengan detail lengkap.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="mt-6 bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between text-sm text-gray-600">
                <div>
                    <span>Generated on: <?php echo date('d F Y \a\t H:i:s'); ?></span>
                </div>
                <div class="flex items-center space-x-4">
                    <span>Total Records: <?php echo $transactions ? $transactions->num_rows : 0; ?></span>
                    <a href="kas_pengeluaran_bruder.php" class="text-blue-600 hover:text-blue-800">
                        ← Kembali ke View Compact
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto refresh setiap 30 detik jika diperlukan
            setTimeout(() => {
                console.log('View lengkap kas pengeluaran dimuat');
            }, 1000);
        });
    </script>
</body>
</html>
