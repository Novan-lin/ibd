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

// --- LOGIKA PROSES FORM TAMBAH PERJALANAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_perjalanan'])) {
    $tanggal_berangkat = $_POST['tanggal_berangkat'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $keterangan = $_POST['keterangan'];

    // Hitung jumlah hari
    $datetime1 = new DateTime($tanggal_berangkat);
    $datetime2 = new DateTime($tanggal_kembali);
    $interval = $datetime1->diff($datetime2);
    $jumlah_hari = $interval->days + 1; // +1 karena inklusif

    if ($bruder_id > 0 && !empty($tanggal_berangkat) && !empty($tanggal_kembali) && !empty($keterangan)) {
        // Validasi tanggal kembali harus setelah tanggal berangkat
        if ($tanggal_kembali <= $tanggal_berangkat) {
            $error_message = "Tanggal kembali harus setelah tanggal berangkat!";
        } else {
            // Insert data perjalanan
            $stmt_insert = $conn->prepare("INSERT INTO perjalanan_bruder (id_bruder, tanggal_berangkat, tanggal_kembali, jumlah_hari, keterangan) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("issis", $bruder_id, $tanggal_berangkat, $tanggal_kembali, $jumlah_hari, $keterangan);

            if ($stmt_insert->execute()) {
                $success_message = "Data perjalanan berhasil disimpan!";
                // Refresh data perjalanan
                $stmt_perjalanan = $conn->prepare("SELECT * FROM perjalanan_bruder WHERE id_bruder = ? ORDER BY tanggal_berangkat DESC");
                $stmt_perjalanan->bind_param("i", $bruder_id);
                $stmt_perjalanan->execute();
                $perjalanan_data = $stmt_perjalanan->get_result();
                $stmt_perjalanan->close();
            } else {
                $error_message = "Gagal menyimpan data perjalanan: " . $conn->error;
            }
            $stmt_insert->close();
        }
    } else {
        $error_message = "Semua field harus diisi dengan lengkap!";
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

                <!-- Notifikasi -->
                <?php if (isset($success_message)): ?>
                    <div id="successAlert" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                    <div id="errorAlert" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Form Input Perjalanan -->
                <div class="mb-6 p-4 border border-gray-200 rounded-lg bg-gradient-to-br from-blue-50 to-indigo-100">
                    <div class="flex items-center mb-3">
                        <div class="bg-blue-100 p-1.5 rounded-md mr-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold text-gray-800">Input Perjalanan Bruder</h2>
                    </div>
                    <form id="perjalananForm" class="space-y-4">
                        <!-- Row 1: Tanggal Berangkat dan Kembali -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="md:col-span-1">
                                <label for="tanggal_berangkat" class="block text-xs font-medium text-gray-700 mb-1">
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Tanggal Berangkat
                                    </span>
                                </label>
                                <input type="date" name="tanggal_berangkat" id="tanggal_berangkat" required
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm">
                            </div>
                            <div class="md:col-span-1">
                                <label for="tanggal_kembali" class="block text-xs font-medium text-gray-700 mb-1">
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Tanggal Kembali
                                    </span>
                                </label>
                                <input type="date" name="tanggal_kembali" id="tanggal_kembali" required
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm">
                            </div>
                        </div>

                        <!-- Row 2: Jumlah Hari (Auto) dan Keterangan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="md:col-span-1">
                                <label for="jumlah_hari" class="block text-xs font-medium text-gray-700 mb-1">
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        Jumlah Hari
                                    </span>
                                </label>
                                <input type="text" name="jumlah_hari" id="jumlah_hari" readonly
                                       class="block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 py-2 px-3 text-sm font-medium text-gray-700"
                                       placeholder="Auto-calculated">
                            </div>
                            <div class="md:col-span-1">
                                <label for="keterangan" class="block text-xs font-medium text-gray-700 mb-1">
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                        </svg>
                                        Keterangan
                                    </span>
                                </label>
                                <input type="text" name="keterangan" id="keterangan" required placeholder="Contoh: Kunjungan ke komunitas"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-2 border-t border-gray-200">
                            <button type="button" id="submitPerjalananBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Simpan Perjalanan
                            </button>
                        </div>
                    </form>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tanggalBerangkatInput = document.getElementById('tanggal_berangkat');
            const tanggalKembaliInput = document.getElementById('tanggal_kembali');
            const jumlahHariInput = document.getElementById('jumlah_hari');
            const keteranganInput = document.getElementById('keterangan');
            const perjalananForm = document.getElementById('perjalananForm');
            const submitBtn = document.getElementById('submitPerjalananBtn');

            // Auto-calculate jumlah hari when dates change
            function calculateDays() {
                const berangkat = new Date(tanggalBerangkatInput.value);
                const kembali = new Date(tanggalKembaliInput.value);

                if (tanggalBerangkatInput.value && tanggalKembaliInput.value) {
                    if (kembali >= berangkat) {
                        const timeDiff = kembali.getTime() - berangkat.getTime();
                        const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // +1 for inclusive
                        jumlahHariInput.value = dayDiff + ' hari';
                    } else {
                        jumlahHariInput.value = 'Invalid range';
                    }
                }
            }

            // Event listeners for date inputs
            tanggalBerangkatInput.addEventListener('change', calculateDays);
            tanggalKembaliInput.addEventListener('change', calculateDays);

            // Form submission via AJAX
            function submitPerjalanan() {
                const formData = new FormData(perjalananForm);
                formData.append('tambah_perjalanan', '1');
                formData.append('bruder_id', <?php echo $bruder_id; ?>);

                // Show loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mx-auto"></div>';

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(text => {
                    // Check if response contains success/error indicators
                    if (text.includes('Data perjalanan berhasil disimpan!')) {
                        // Success - reload page to show new data
                        location.reload();
                    } else if (text.includes('Tanggal kembali harus setelah tanggal berangkat!')) {
                        showError('Tanggal kembali harus setelah tanggal berangkat!');
                        resetForm();
                    } else if (text.includes('Semua field harus diisi dengan lengkap!')) {
                        showError('Semua field harus diisi dengan lengkap!');
                        resetForm();
                    } else {
                        showError('Terjadi kesalahan saat menyimpan data.');
                        resetForm();
                    }
                })
                .catch(error => {
                    showError('Terjadi kesalahan. Silakan coba lagi.');
                    resetForm();
                    console.error('Perjalanan error:', error);
                });
            }

            function showError(message) {
                // Remove existing alerts
                const existingAlerts = document.querySelectorAll('#errorAlert, #successAlert');
                existingAlerts.forEach(alert => alert.remove());

                // Create error alert
                const alertDiv = document.createElement('div');
                alertDiv.id = 'errorAlert';
                alertDiv.className = 'mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg';
                alertDiv.innerHTML = message;

                // Insert before form
                const form = document.getElementById('perjalananForm');
                form.parentNode.insertBefore(alertDiv, form);

                // Auto-hide after 5 seconds
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }

            function resetForm() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Simpan Perjalanan';
            }

            // Event listener for submit button
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Validate form
                if (!tanggalBerangkatInput.value || !tanggalKembaliInput.value || !keteranganInput.value) {
                    showError('Semua field harus diisi!');
                    return;
                }

                if (new Date(tanggalKembaliInput.value) < new Date(tanggalBerangkatInput.value)) {
                    showError('Tanggal kembali harus setelah tanggal berangkat!');
                    return;
                }

                if (confirm('Apakah Anda yakin ingin menyimpan data perjalanan ini?')) {
                    submitPerjalanan();
                }
            });

            // Enter key support for form
            perjalananForm.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && e.target.type !== 'textarea') {
                    e.preventDefault();
                    submitBtn.click();
                }
            });

            // Set minimum date to today for departure
            const today = new Date().toISOString().split('T')[0];
            tanggalBerangkatInput.setAttribute('min', today);
            tanggalKembaliInput.setAttribute('min', today);
        });
    </script>
</body>
</html>
