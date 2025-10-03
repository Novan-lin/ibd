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
$pending_approvals = null; // Variabel baru untuk pengajuan
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
        "SELECT t.tanggal_transaksi,kp.id_perkiraan,kp.tipe_akun,t.id_transaksi,kp.kode_perkiraan, kp.pos, kp.kode_perkiraan, kp.nama_akun, t.keterangan, t.reff, t.nominal_penerimaan, t.nominal_pengeluaran
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

// Ambil data pengajuan yang menunggu persetujuan untuk cabang ini
$id_cabang_session = $_SESSION['id_cabang'] ?? 0;
if ($id_cabang_session > 0) {
    $stmt_pending = $conn->prepare(
        "SELECT p.id_pengajuan, p.tanggal_pengajuan, b.nama_bruder, kp.nama_akun, p.keterangan, p.nominal, p.foto_bukti
         FROM pengajuan_pengeluaran p
         JOIN bruder b ON p.id_bruder = b.id_bruder
         JOIN kode_perkiraan kp ON p.id_perkiraan = kp.id_perkiraan
         WHERE p.id_cabang = ? AND p.status = 'pending'
         ORDER BY p.tanggal_pengajuan ASC"
    );
    $stmt_pending->bind_param("i", $id_cabang_session);
    $stmt_pending->execute();
    $pending_approvals = $stmt_pending->get_result();
    $stmt_pending->close();
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

        /* CSS Fallback untuk memastikan tabel selalu terlihat */
        #pendingApprovalsTable {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            background-color: white;
            display: table;
        }

        #pendingApprovalsTable thead {
            background-color: #F9FAFB;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        #pendingApprovalsTable tbody {
            display: table-row-group;
        }

        #pendingApprovalsTable tr {
            display: table-row;
            border-bottom: 1px solid #E5E7EB;
        }

        #pendingApprovalsTable td {
            display: table-cell;
            padding: 0.75rem;
            vertical-align: middle;
        }

        /* Pastikan tabel selalu terlihat */
        #pendingApprovalsTable {
            min-height: 100px;
        }
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
        <main class="flex-1 p-4 overflow-y-auto" style="max-height: calc(100vh - 2rem);">
            <div class="bg-white rounded-2xl shadow-lg p-6 max-w-full overflow-y-auto" style="height: calc(100vh - 4rem);">
                <!-- Header -->
                <div class="border-b pb-4 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-2xl font-bold text-gray-800">Kas Harian</h1>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-500">Cabang</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['kode_cabang'] . ' - ' . $_SESSION['nama_cabang']); ?></p>
                        </div>
                    </div>
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
                </div>

                <!-- Notifikasi -->
                <?php if ($pesan_sukses): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert"><?php echo $pesan_sukses; ?></div>
                <?php endif; ?>
                <?php if ($pesan_error): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg" role="alert"><?php echo $pesan_error; ?></div>
                <?php endif; ?>

                <!-- Loading Spinner for Form -->
                <div id="formLoadingSpinner" class="mb-4 hidden flex justify-center">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-[#003366]"></div>
                </div>

                <!-- Form Messages -->
                <div id="formSuccessMessage" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg hidden" role="alert">
                    <span class="block sm:inline"></span>
                </div>
                <div id="formErrorMessage" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg hidden" role="alert">
                    <span class="block sm:inline"></span>
                </div>

                <!-- Form Tambah Transaksi -->
                <div class="mb-8 p-6 border rounded-lg bg-gray-50">
                    <h2 class="text-xl font-bold text-gray-700 mb-4">Tambah Transaksi Kas Harian</h2>
                    <form id="transactionForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                        <div>
                            <label for="tanggal" class="block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" required value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#003366]">
                        </div>
                        <div>
                            <label for="id_perkiraan" class="block text-sm font-medium text-gray-700">Kode Perkiraan</label>
                            <select name="id_perkiraan" id="id_perkiraan" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#003366]">
                                <option value="">-- Pilih Akun --</option>
                                <?php if (isset($result_perkiraan)) { while($kp = $result_perkiraan->fetch_assoc()): ?>
                                <option value="<?php echo $kp['id_perkiraan']; ?>"><?php echo htmlspecialchars($kp['kode_perkiraan'] . ' - ' . $kp['nama_akun']); ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                        <div>
                            <label for="tipe_transaksi" class="block text-sm font-medium text-gray-700">Tipe</label>
                            <select name="tipe_transaksi" id="tipe_transaksi" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#003366]">
                                <option value="Pengeluaran">Pengeluaran</option>
                                <option value="Penerimaan">Penerimaan</option>
                            </select>
                        </div>
                        <div class="md:col-span-2 lg:col-span-1">
                            <label for="keterangan" class="block text-sm font-medium text-gray-700">Keterangan</label>
                            <input type="text" name="keterangan" id="keterangan" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#003366]">
                        </div>
                        <div>
                            <label for="nominal" class="block text-sm font-medium text-gray-700">Nominal (Rp)</label>
                            <input type="number" step="0.01" name="nominal" id="nominal" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#003366]">
                        </div>
                        <div class="lg:col-span-3 text-right">
                            <button type="button" id="submitTransactionBtn" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-full text-white bg-[#003366] hover:bg-[#004488] transition duration-200">
                                Simpan Transaksi
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Bagian Persetujuan Pengeluaran -->
                <div class="mb-8 p-6 border rounded-lg bg-gray-50">
                    <h2 class="text-xl font-bold text-gray-700 mb-4">Menunggu Persetujuan (<?php echo $pending_approvals ? $pending_approvals->num_rows : 0; ?>)</h2>
                    <div class="overflow-y-auto" style="max-height: 300px;">
                        <table id="pendingApprovalsTable" class="w-full text-sm">
                            <thead class="sticky top-0 bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2">Tanggal</th>
                                    <th class="px-3 py-2">Bruder</th>
                                    <th class="px-3 py-2">Keterangan</th>
                                    <th class="px-3 py-2 text-right">Nominal</th>
                                    <th class="px-3 py-2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($pending_approvals && $pending_approvals->num_rows > 0): ?>
                                    <?php while($row = $pending_approvals->fetch_assoc()): ?>
                                    <tr class="border-b hover:bg-gray-100">
                                        <td class="px-3 py-2"><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_pengajuan']))); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($row['nama_bruder']); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                        <td class="px-3 py-2 text-right font-semibold">Rp <?php echo number_format($row['nominal'], 0, ',', '.'); ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <?php if (!empty($row['foto_bukti'])): ?>
                                                    <a href="uploads/bruder_photos/<?php echo htmlspecialchars($row['foto_bukti']); ?>" target="_blank" class="text-blue-600 hover:underline text-xs p-1 bg-blue-100 rounded">Lihat Foto</a>
                                                <?php endif; ?>
                                                <button class="approve-btn text-green-600 hover:underline text-xs p-1 bg-green-100 rounded" data-id="<?php echo $row['id_pengajuan']; ?>">Setujui</button>
                                                <button class="reject-btn text-red-600 hover:underline text-xs p-1 bg-red-100 rounded" data-id="<?php echo $row['id_pengajuan']; ?>">Tolak</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-gray-500">Tidak ada pengajuan yang menunggu persetujuan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tabel Riwayat Kas Harian -->
                <h2 class="text-xl font-bold text-gray-700 mb-4 mt-8">Riwayat Transaksi Final</h2>
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
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions && $transactions->num_rows > 0): 
                                 ?>
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
                                      <td class="text-center">
                                    <div class="flex space-x-2 justify-center">
                                        <button class="edit-btn bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600"
                                                data-transaction-id="<?=$row['id_transaksi']?>"
                                                data-tanggal="<?=$row['tanggal_transaksi']?>"
                                                data-perkiraan="<?=$row['kode_perkiraan']?>"
                                                data-tipe="<?=$row['tipe_akun']?>"
                                                data-nominal="<?= strtolower($row['tipe_akun']) === 'penerimaan' ?floatval($row['nominal_penerimaan']): floatval($row['nominal_pengeluaran']) ?>"
                                                data-keterangan="<?=$row['keterangan']?>">
                                            Edit
                                        </button>
                                        <button class="delete-btn bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600"
                                                data-transaction-id="<?=$row['id_transaksi']?>">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
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

    <!-- Edit Transaction Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Edit Transaksi</h3>
                <button id="closeEditModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="editTransactionForm">
                <input type="hidden" id="editTransactionId" name="transaction_id">

                <div class="mb-4">
                    <label for="editTanggal" class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                    <input type="date" id="editTanggal" name="tanggal" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#003366]">
                </div>

                <div class="mb-4">
                    <label for="editPerkiraan" class="block text-sm font-medium text-gray-700 mb-2">Kode Perkiraan</label>
                    <select id="editPerkiraan" name="id_perkiraan" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#003366]">
                        <option value="">-- Pilih Akun --</option>
                        <?php if (isset($result_perkiraan)) {
                            $result_perkiraan->data_seek(0); // Reset pointer
                            while($kp = $result_perkiraan->fetch_assoc()): ?>
                        <option value="<?php echo $kp['id_perkiraan']; ?>"><?php echo htmlspecialchars($kp['kode_perkiraan'] . ' - ' . $kp['nama_akun']); ?></option>
                        <?php endwhile; } ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="editTipe" class="block text-sm font-medium text-gray-700 mb-2">Tipe Transaksi</label>
                    <select id="editTipe" name="tipe_transaksi" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#003366]">
                        <option value="Pengeluaran">Pengeluaran</option>
                        <option value="Penerimaan">Penerimaan</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="editKeterangan" class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                    <input type="text" id="editKeterangan" name="keterangan" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#003366]">
                </div>

                <div class="mb-4">
                    <label for="editNominal" class="block text-sm font-medium text-gray-700 mb-2">Nominal (Rp)</label>
                    <input type="number" step="0.01" id="editNominal" name="nominal" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#003366]">
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancelEditBtn"
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="button" id="saveEditBtn"
                            class="px-4 py-2 bg-[#003366] text-white rounded-md hover:bg-[#004488]">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const transactionForm = document.getElementById('transactionForm');
            const submitBtn = document.getElementById('submitTransactionBtn');
            const formLoadingSpinner = document.getElementById('formLoadingSpinner');
            const formSuccessMessage = document.getElementById('formSuccessMessage');
            const formErrorMessage = document.getElementById('formErrorMessage');
            const bruderId = <?php echo $bruder_id; ?>;

            // Function to show form messages
            function showFormMessage(element, message, type = 'error') {
                element.querySelector('span').textContent = message;
                element.className = `mb-4 px-4 py-3 rounded-lg ${type === 'error' ?
                    'bg-red-100 border border-red-400 text-red-700' :
                    'bg-green-100 border border-green-400 text-green-700'}`;
                element.classList.remove('hidden');
            }

            function hideFormMessages() {
                formSuccessMessage.classList.add('hidden');
                formErrorMessage.classList.add('hidden');
            }

            function showFormLoading(show = true) {
                formLoadingSpinner.classList.toggle('hidden', !show);
                submitBtn.disabled = show;
                if (show) {
                    submitBtn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mx-auto"></div>';
                } else {
                    submitBtn.innerHTML = 'Simpan Transaksi';
                }
            }

            // AJAX Transaction submission
            function submitTransaction() {
                const formData = new FormData(transactionForm);
                formData.append('action', 'add_transaction');
                formData.append('bruder_id', bruderId);
                formData.append('sumber_dana', 'Kas Harian');

                hideFormMessages();
                showFormLoading(true);

                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    showFormLoading(false);

                    if (data.success) {
                        showFormMessage(formSuccessMessage, data.message, 'success');
                        transactionForm.reset();
                        // Refresh transaction table
                        loadTransactions();
                        // Hide success message after 3 seconds
                        setTimeout(() => {
                            formSuccessMessage.classList.add('hidden');
                        }, 3000);
                    } else {
                        showFormMessage(formErrorMessage, data.message);
                    }
                })
                .catch(error => {
                    showFormLoading(false);
                    showFormMessage(formErrorMessage, 'Terjadi kesalahan. Silakan coba lagi.');
                    console.error('Transaction error:', error);
                });
            }

            // Load transactions dynamically
            function loadTransactions() {
                if (bruderId <= 0) return;

                const urlParams = new URLSearchParams({
                    action: 'get_transactions',
                    bruder_id: bruderId,
                    sumber_dana: 'Kas Harian'
                });

                fetch(`ajax_handler.php?${urlParams}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateTransactionTable(data.data);
                        // alert('success');
                    } else {
                        console.error('Failed to load transactions:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading transactions:', error);
                });
            }

            // Update transaction table
            function updateTransactionTable(transactions) {
                const tbody = document.querySelector('tbody');
                let html = '';

                if (transactions && transactions.length > 0) {
                    transactions.forEach(transaction => {
                        const date = new Date(transaction.tanggal_transaksi);
                        const formattedDate = date.toLocaleDateString('id-ID');

                        // Determine transaction type and nominal
                        let tipeTransaksi = '';
                        let nominalValue = 0;
                        if (transaction.nominal_penerimaan > 0) {
                            tipeTransaksi = 'Penerimaan';
                            nominalValue = transaction.nominal_penerimaan;
                        } else if (transaction.nominal_pengeluaran > 0) {
                            tipeTransaksi = 'Pengeluaran';
                            nominalValue = transaction.nominal_pengeluaran;
                        }

                        html += `
                            <tr data-transaction-id="${transaction.id_transaksi}">
                                <td>${formattedDate}</td>
                                <td>${transaction.pos || ''}</td>
                                <td>${transaction.kode_perkiraan || ''}</td>
                                <td>${transaction.nama_akun || ''}</td>
                                <td>${transaction.keterangan || ''}</td>
                                <td>${transaction.reff || ''}</td>
                                <td class="text-right">Rp ${Number(transaction.nominal_penerimaan || 0).toLocaleString('id-ID')}</td>
                                <td class="text-right">Rp ${Number(transaction.nominal_pengeluaran || 0).toLocaleString('id-ID')}</td>
                                <td class="text-center">
                                    <div class="flex space-x-2 justify-center">
                                        <button class="edit-btn bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600"
                                                data-transaction-id="${transaction.id_transaksi}"
                                                data-tanggal="${transaction.tanggal_transaksi}"
                                                data-perkiraan="${transaction.id_perkiraan}"
                                                data-tipe="${tipeTransaksi}"
                                                data-nominal="${nominalValue}"
                                                data-keterangan="${transaction.keterangan || ''}">
                                            Edit
                                        </button>
                                        <button class="delete-btn bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600"
                                                data-transaction-id="${transaction.id_transaksi}">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="9" class="text-center py-10 text-gray-500">Belum ada transaksi kas harian untuk bruder ini.</td></tr>';
                }

                tbody.innerHTML = html;

                // Attach event listeners to new buttons
                attachActionButtonListeners();
            }

            // Event listeners
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                submitTransaction();
            });

            // Enter key support for form
            transactionForm.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && e.target.type !== 'textarea') {
                    e.preventDefault();
                    submitTransaction();
                }
            });

            // Load transactions on page load if bruder_id exists
            if (bruderId > 0) {
                loadTransactions();
            }

            // Auto-format nominal input
            const nominalInput = document.getElementById('nominal');
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

            // Modal elements
            const editModal = document.getElementById('editModal');
            const closeEditModal = document.getElementById('closeEditModal');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const saveEditBtn = document.getElementById('saveEditBtn');
            const editTransactionForm = document.getElementById('editTransactionForm');

            let currentEditTransactionId = null;

            // Function to show edit modal
            function showEditModal(transactionData) {
                currentEditTransactionId = transactionData.transactionId;    

                document.getElementById('editTransactionId').value = transactionData.transactionId;
                document.getElementById('editTanggal').value = transactionData.tanggal;
                document.getElementById('editPerkiraan').value = transactionData.perkiraan;
                document.getElementById('editTipe').value = transactionData.tipe;
                document.getElementById('editKeterangan').value = transactionData.keterangan;
                document.getElementById('editNominal').value = transactionData.nominal;

                editModal.classList.remove('hidden');
            }

            // Function to hide edit modal
            function hideEditModal() {
                editModal.classList.add('hidden');
                currentEditTransactionId = null;
                editTransactionForm.reset();
            }

            // Function to show loading in modal
            function showModalLoading(show = true) {
                saveEditBtn.disabled = show;
                if (show) {
                    saveEditBtn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mx-auto"></div>';
                } else {
                    saveEditBtn.innerHTML = 'Simpan';
                }
            }

            // AJAX Edit transaction
            function editTransaction() {
                const formData = new FormData(editTransactionForm);
                formData.append('action', 'edit_transaction');
                formData.append('bruder_id', bruderId);

                showModalLoading(true);

                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    showModalLoading(false);

                    if (data.success) {
                        hideEditModal();
                        showFormMessage(formSuccessMessage, data.message, 'success');
                        loadTransactions(); // Refresh table
                        setTimeout(() => {
                            formSuccessMessage.classList.add('hidden');
                        }, 3000);
                    } else {
                        alert('Gagal mengedit transaksi: ' + data.message);
                    }
                })
                .catch(error => {
                    showModalLoading(false);
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                    console.error('Edit transaction error:', error);
                });
            }

            // AJAX Delete transaction
            function deleteTransaction(transactionId) {
                if (!confirm('Apakah Anda yakin ingin menghapus transaksi ini?')) {
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'delete_transaction');
                formData.append('transaction_id', transactionId);
                formData.append('bruder_id', bruderId);

                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showFormMessage(formSuccessMessage, data.message, 'success');
                        loadTransactions(); // Refresh table
                        setTimeout(() => {
                            formSuccessMessage.classList.add('hidden');
                        }, 3000);
                    } else {
                        alert('Gagal menghapus transaksi: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                    console.error('Delete transaction error:', error);
                });
            }

            // Attach event listeners to action buttons
            function attachActionButtonListeners() {
                // Edit buttons
                document.querySelectorAll('.edit-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const data = this.dataset;
                        showEditModal({
                            transactionId: data.transactionId,
                            tanggal: data.tanggal,
                            perkiraan: data.perkiraan,
                            tipe: data.tipe,
                            nominal: data.nominal,
                            keterangan: data.keterangan
                        });
                    });
                });

                // Delete buttons
                document.querySelectorAll('.delete-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const transactionId = this.dataset.transactionId;
                        deleteTransaction(transactionId);
                    });
                });
            }

            // Modal event listeners
            closeEditModal.addEventListener('click', hideEditModal);
            cancelEditBtn.addEventListener('click', hideEditModal);
            saveEditBtn.addEventListener('click', editTransaction);

            // Close modal when clicking outside
            editModal.addEventListener('click', function(e) {
                if (e.target === editModal) {
                    hideEditModal();
                }
            });

            // Enter key support for edit form
            editTransactionForm.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && e.target.type !== 'textarea') {
                    e.preventDefault();
                    editTransaction();
                }
            });

            // Auto-format edit nominal input
            const editNominalInput = document.getElementById('editNominal');
            editNominalInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d]/g, '');
                if (value) {
                    e.target.value = parseInt(value).toLocaleString('id-ID');
                }
            });

            editNominalInput.addEventListener('focus', function(e) {
                e.target.value = e.target.value.replace(/[^\d]/g, '');
            });

            // =====================================================================
            // LOGIKA PERSETUJUAN PENGAJUAN
            // =====================================================================

            const approvalSection = document.querySelector('.mb-8.p-6.border.rounded-lg.bg-gray-50');

            // Modal Tolak Pengajuan
            const rejectModal = document.createElement('div');
            rejectModal.id = 'rejectModal';
            rejectModal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50';
            rejectModal.innerHTML = `
                <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Tolak Pengajuan</h3>
                        <button type="button" class="close-reject-modal text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <form id="rejectForm">
                        <input type="hidden" id="rejectPengajuanId" name="id_pengajuan">
                        <div class="mb-4">
                            <label for="catatanBendahara" class="block text-sm font-medium text-gray-700 mb-2">Catatan Penolakan (Opsional)</label>
                            <textarea id="catatanBendahara" name="catatan_bendahara" rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" class="close-reject-modal px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit" id="submitRejectBtn"
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                Tolak Pengajuan
                            </button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(rejectModal);

            function showRejectModal(pengajuanId) {
                document.getElementById('rejectPengajuanId').value = pengajuanId;
                rejectModal.classList.remove('hidden');
            }

            function hideRejectModal() {
                rejectModal.classList.add('hidden');
                document.getElementById('rejectForm').reset();
            }

            document.querySelectorAll('.close-reject-modal').forEach(btn => {
                btn.addEventListener('click', hideRejectModal);
            });

            rejectModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    hideRejectModal();
                }
            });

            document.getElementById('rejectForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'reject_pengeluaran');
                
                const submitRejectBtn = document.getElementById('submitRejectBtn');
                submitRejectBtn.disabled = true;
                submitRejectBtn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mx-auto"></div>';

                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    submitRejectBtn.disabled = false;
                    submitRejectBtn.innerHTML = 'Tolak Pengajuan';
                    if (data.success) {
                        alert(data.message);
                        hideRejectModal();
                        loadPendingApprovals(); // Refresh the pending list
                    } else {
                        alert('Gagal menolak pengajuan: ' + data.message);
                    }
                })
                .catch(error => {
                    submitRejectBtn.disabled = false;
                    submitRejectBtn.innerHTML = 'Tolak Pengajuan';
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                    console.error('Reject error:', error);
                });
            });

            // Fungsi untuk memuat ulang daftar pengajuan yang tertunda
            function loadPendingApprovals() {
                if (<?php echo $id_cabang_session; ?> <= 0) return;

                const urlParams = new URLSearchParams({
                    action: 'get_pending_approvals',
                    id_cabang: <?php echo $id_cabang_session; ?>
                });

                console.log('Loading pending approvals...');

                fetch(`ajax_handler.php?${urlParams}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        console.log('Updating table with data:', data.data);
                        updatePendingApprovalsTable(data.data);
                    } else {
                        console.error('Failed to load pending approvals:', data.message);
                        // Pastikan tabel tetap terlihat meski error
                        updatePendingApprovalsTable([]);
                    }
                })
                .catch(error => {
                    console.error('Error loading pending approvals:', error);
                    // Pastikan tabel tetap terlihat meski error
                    updatePendingApprovalsTable([]);
                });
            }

            // Fungsi untuk memperbarui tabel pengajuan yang tertunda
            function updatePendingApprovalsTable(approvals) {
                console.log('updatePendingApprovalsTable called with:', approvals);

                const table = document.getElementById('pendingApprovalsTable');
                if (!table) {
                    console.error('Table #pendingApprovalsTable not found!');
                    return;
                }

                const tbody = table.querySelector('tbody');
                if (!tbody) {
                    console.error('tbody not found in #pendingApprovalsTable!');
                    return;
                }

                let html = '';

                if (approvals && approvals.length > 0) {
                    console.log('Processing', approvals.length, 'approvals');
                    approvals.forEach((row, index) => {
                        console.log('Processing row', index, ':', row);
                        const fotoLink = row.foto_bukti ?
                            `<a href="uploads/bruder_photos/${row.foto_bukti}" target="_blank" class="text-blue-600 hover:underline text-xs p-1 bg-blue-100 rounded">Lihat Foto</a>` : '';

                        html += `
                            <tr class="border-b hover:bg-gray-100">
                                <td class="px-3 py-2">${new Date(row.tanggal_pengajuan).toLocaleString('id-ID')}</td>
                                <td class="px-3 py-2">${row.nama_bruder || 'N/A'}</td>
                                <td class="px-3 py-2">${row.keterangan || 'N/A'}</td>
                                <td class="px-3 py-2 text-right font-semibold">Rp ${Number(row.nominal || 0).toLocaleString('id-ID')}</td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex justify-center space-x-2">
                                        ${fotoLink}
                                        <button class="approve-btn text-green-600 hover:underline text-xs p-1 bg-green-100 rounded" data-id="${row.id_pengajuan}">Setujui</button>
                                        <button class="reject-btn text-red-600 hover:underline text-xs p-1 bg-red-100 rounded" data-id="${row.id_pengajuan}">Tolak</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    console.log('No approvals found, showing empty state');
                    html = '<tr><td colspan="5" class="text-center py-4 text-gray-500">Tidak ada pengajuan yang menunggu persetujuan.</td></tr>';
                }

                console.log('Setting tbody innerHTML with length:', html.length);
                tbody.innerHTML = html;

                // Re-attach listeners after updating table
                setTimeout(() => {
                    attachApprovalButtonListeners();
                    console.log('Event listeners attached');
                }, 100);
            }

            // Attach event listeners for approve/reject buttons
            function attachApprovalButtonListeners() {
                document.querySelectorAll('.approve-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const pengajuanId = this.dataset.id;
                        if (confirm('Apakah Anda yakin ingin MENYETUJUI pengajuan ini?')) {
                            handleApproval(pengajuanId, 'approve');
                        }
                    });
                });

                document.querySelectorAll('.reject-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const pengajuanId = this.dataset.id;
                        showRejectModal(pengajuanId);
                    });
                });
            }

            // Handle approval/rejection AJAX call
            function handleApproval(pengajuanId, actionType) {
                const formData = new FormData();
                formData.append('action', actionType === 'approve' ? 'approve_pengeluaran' : 'reject_pengeluaran');
                formData.append('id_pengajuan', pengajuanId);
                // If rejecting, catatan_bendahara will be handled by the modal form

                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        loadPendingApprovals(); // Refresh the pending list
                        loadTransactions(); // Refresh the final transactions list
                    } else {
                        alert('Gagal ' + (actionType === 'approve' ? 'menyetujui' : 'menolak') + ' pengajuan: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                    console.error('Approval/Rejection error:', error);
                });
            }

            // Initial load of pending approvals
            loadPendingApprovals();
            attachApprovalButtonListeners(); // Attach listeners on initial load
        });
    </script>
</body>
</html>
