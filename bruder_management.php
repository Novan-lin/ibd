<?php
// =====================================================================
//            HALAMAN KELOLA BRUDER - CRUD BRUDER
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

// Data dari session
$current_cabang = $_SESSION['id_cabang'];
$nama_cabang = $_SESSION['nama_cabang'];
$kode_cabang = $_SESSION['kode_cabang'];

// Ambil daftar komunitas untuk dropdown
$result_komunitas = $conn->query("SELECT id_komunitas, nama_komunitas FROM komunitas WHERE id_cabang = $current_cabang ORDER BY nama_komunitas");

// Ambil daftar bruder untuk ditampilkan
$result_bruder = $conn->query("
    SELECT b.*, k.nama_komunitas
    FROM bruder b
    LEFT JOIN komunitas k ON b.id_komunitas = k.id_komunitas
    WHERE b.id_cabang = $current_cabang
    ORDER BY b.nama_bruder
");

$bruder_list = [];
if ($result_bruder && $result_bruder->num_rows > 0) {
    while ($row = $result_bruder->fetch_assoc()) {
        $bruder_list[] = $row;
    }
}

$pesan_sukses = '';
$pesan_error = '';

// --- LOGIKA PROSES FORM TAMBAH BRUDER BARU ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_bruder'])) {
    $nama_bruder = $_POST['nama_bruder'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $id_komunitas = (int)($_POST['id_komunitas'] ?? 0);
    $ttl_bruder = $_POST['ttl_bruder'] ?? '';
    $alamat_bruder = $_POST['alamat_bruder'] ?? '';

    if (empty($nama_bruder) || empty($username) || empty($password)) {
        $pesan_error = "Nama bruder, username, dan password harus diisi!";
    } else {
        // Gunakan stored procedure yang sudah ada
        $stmt_proc = $conn->prepare("CALL tambah_bruder_baru(?, ?, ?, ?, ?, ?)");
        $stmt_proc->bind_param("sssis", $nama_bruder, $username, $password, $id_komunitas, $ttl_bruder, $alamat_bruder);

        if ($stmt_proc->execute()) {
            $pesan_sukses = "Bruder baru berhasil ditambahkan!";
            // Refresh halaman untuk update data
            echo "<script>window.location.reload();</script>";
        } else {
            $pesan_error = "Gagal menambahkan bruder: " . $conn->error;
        }
        $stmt_proc->close();
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
    <title>Kelola Bruder - <?php echo htmlspecialchars($nama_cabang); ?></title>
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
        <aside class="w-64 bg-white flex flex-col shadow-lg">
            <div class="p-4 text-center border-b border-gray-200">
                <img src="https://placehold.co/80x80/003366/FFFFFF?text=FIC" alt="Logo FIC" class="w-16 h-16 mx-auto mb-2 rounded-lg">
            </div>
            <nav class="flex-grow pt-2">
                <a href="dashboard.php" class="sidebar-link flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-all duration-200 rounded-lg mx-2 mb-1">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="laporan.php" class="sidebar-link flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-all duration-200 rounded-lg mx-2 mb-1">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Laporan Keuangan
                </a>
                <a href="perjalanan_bruder.php" class="sidebar-link flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700 transition-all duration-200 rounded-lg mx-2 mb-1">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    Perjalanan Bruder
                </a>
                <a href="bruder_management.php" class="sidebar-link active flex items-center px-4 py-3 text-blue-700 bg-blue-50 font-semibold rounded-lg mx-2 mb-1">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    Kelola Bruder
                </a>
            </nav>
            <div class="p-4 border-t border-gray-200">
                 <a href="dashboard.php" class="w-full text-center mb-3 bg-gray-100 text-gray-700 font-medium py-2.5 px-4 rounded-lg hover:bg-gray-200 transition-all duration-200 block">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Dashboard
                </a>
                <a href="bruder_management.php?action=logout" class="w-full text-center bg-red-500 text-white font-medium py-2.5 px-4 rounded-lg hover:bg-red-600 transition-all duration-200 block">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 p-6 bg-gray-50 overflow-y-auto">
            <div class="max-w-6xl mx-auto">
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Kelola Bruder</h1>
                            <p class="text-gray-600">Manajemen data bruder untuk cabang <?php echo htmlspecialchars($kode_cabang . ' - ' . $nama_cabang); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-500">Total Bruder</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo count($bruder_list); ?></p>
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

                <!-- Form Tambah Bruder Baru -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 p-2 rounded-lg mr-3">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold text-gray-800">Tambah Bruder Baru</h2>
                    </div>

                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="md:col-span-1">
                            <label for="nama_bruder" class="block text-sm font-medium text-gray-700 mb-1">Nama Bruder *</label>
                            <input type="text" name="nama_bruder" id="nama_bruder" required
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm"
                                   placeholder="Masukkan nama lengkap bruder">
                        </div>

                        <div class="md:col-span-1">
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                            <input type="text" name="username" id="username" required
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm"
                                   placeholder="Username untuk login">
                        </div>

                        <div class="md:col-span-1">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                            <input type="password" name="password" id="password" required
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm"
                                   placeholder="Password untuk login">
                        </div>

                        <div class="md:col-span-1">
                            <label for="id_komunitas" class="block text-sm font-medium text-gray-700 mb-1">Komunitas</label>
                            <select name="id_komunitas" id="id_komunitas"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm">
                                <option value="">-- Pilih Komunitas --</option>
                                <?php if (isset($result_komunitas)) {
                                    $result_komunitas->data_seek(0); // Reset pointer
                                    while($kom = $result_komunitas->fetch_assoc()): ?>
                                <option value="<?php echo $kom['id_komunitas']; ?>"><?php echo htmlspecialchars($kom['nama_komunitas']); ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <label for="ttl_bruder" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                            <input type="date" name="ttl_bruder" id="ttl_bruder"
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm">
                        </div>

                        <div class="md:col-span-2 lg:col-span-3">
                            <label for="alamat_bruder" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                            <textarea name="alamat_bruder" id="alamat_bruder" rows="2"
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 py-2 px-3 text-sm"
                                      placeholder="Alamat lengkap bruder"></textarea>
                        </div>

                        <div class="md:col-span-2 lg:col-span-3">
                            <button type="submit" name="tambah_bruder"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Tambah Bruder
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabel Daftar Bruder -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="bg-green-100 p-2 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800">Daftar Bruder</h3>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <?php echo count($bruder_list); ?> Total
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-white shadow-sm">
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left font-bold text-gray-900 border-b border-gray-200">No</th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-900 border-b border-gray-200">Nama Bruder</th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-900 border-b border-gray-200">Username</th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-900 border-b border-gray-200">Komunitas</th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-900 border-b border-gray-200">Status</th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-900 border-b border-gray-200">Tanggal Lahir</th>
                                    <th class="px-4 py-3 text-center font-bold text-gray-900 border-b border-gray-200">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (!empty($bruder_list)): ?>
                                    <?php $no = 1; foreach($bruder_list as $bruder): ?>
                                    <tr class="hover:bg-blue-50 transition-all duration-200">
                                        <td class="px-4 py-3 text-gray-900 font-medium"><?php echo $no++; ?></td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div class="bg-gray-200 w-8 h-8 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-xs font-medium text-gray-600">
                                                        <?php echo strtoupper(substr($bruder['nama_bruder'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($bruder['nama_bruder']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($bruder['email']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">
                                            <?php echo htmlspecialchars($bruder['nama_komunitas'] ?? 'Belum ditugaskan'); ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $bruder['status'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($bruder['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">
                                            <?php echo htmlspecialchars($bruder['ttl_bruder'] ? date('d-m-Y', strtotime($bruder['ttl_bruder'])) : '-'); ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <button onclick="editBruder(<?php echo htmlspecialchars(json_encode($bruder)); ?>)"
                                                        class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors duration-200">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Edit
                                                </button>
                                                <button onclick="hapusBruder(<?php echo $bruder['id_bruder']; ?>, '<?php echo htmlspecialchars($bruder['nama_bruder']); ?>')"
                                                        class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 transition-colors duration-200">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-4 py-16 text-center">
                                            <div class="flex flex-col items-center justify-center py-8">
                                                <div class="bg-gray-100 p-4 rounded-full mb-4">
                                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                    </svg>
                                                </div>
                                                <h4 class="text-lg font-semibold text-gray-500 mb-2">Belum ada bruder</h4>
                                                <p class="text-sm text-gray-400 max-w-sm">Tambahkan bruder pertama Anda dengan mengisi form di atas.</p>
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

    <!-- Modal Edit Bruder -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Edit Bruder</h3>
                <button id="closeEditModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="editBruderForm">
                <input type="hidden" id="editIdBruder" name="id_bruder">

                <div class="mb-4">
                    <label for="editNamaBruder" class="block text-sm font-medium text-gray-700 mb-2">Nama Bruder</label>
                    <input type="text" id="editNamaBruder" name="nama_bruder" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label for="editKomunitas" class="block text-sm font-medium text-gray-700 mb-2">Komunitas</label>
                    <select id="editKomunitas" name="id_komunitas"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Komunitas --</option>
                        <?php if (isset($result_komunitas)) {
                            $result_komunitas->data_seek(0); // Reset pointer
                            while($kom = $result_komunitas->fetch_assoc()): ?>
                        <option value="<?php echo $kom['id_komunitas']; ?>"><?php echo htmlspecialchars($kom['nama_komunitas']); ?></option>
                        <?php endwhile; } ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="editTtlBruder" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Lahir</label>
                    <input type="date" id="editTtlBruder" name="ttl_bruder"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label for="editAlamatBruder" class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
                    <textarea id="editAlamatBruder" name="alamat_bruder" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancelEditBtn"
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" id="saveEditBtn"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal elements
            const editModal = document.getElementById('editModal');
            const closeEditModal = document.getElementById('closeEditModal');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const saveEditBtn = document.getElementById('saveEditBtn');
            const editBruderForm = document.getElementById('editBruderForm');

            let currentEditBruderId = null;

            // Function to show edit modal
            window.editBruder = function(bruderData) {
                currentEditBruderId = bruderData.id_bruder;

                document.getElementById('editIdBruder').value = bruderData.id_bruder;
                document.getElementById('editNamaBruder').value = bruderData.nama_bruder;
                document.getElementById('editKomunitas').value = bruderData.id_komunitas || '';
                document.getElementById('editTtlBruder').value = bruderData.ttl_bruder || '';
                document.getElementById('editAlamatBruder').value = bruderData.alamat_bruder || '';

                editModal.classList.remove('hidden');
            };

            // Function to hide edit modal
            function hideEditModal() {
                editModal.classList.add('hidden');
                currentEditBruderId = null;
                editBruderForm.reset();
            }

            // Function to show loading in modal
            function showModalLoading(show = true) {
                saveEditBtn.disabled = show;
                if (show) {
                    saveEditBtn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mx-auto"></div>';
                } else {
                    saveEditBtn.innerHTML = 'Simpan Perubahan';
                }
            }

            // AJAX Edit bruder
            function editBruder() {
                const formData = new FormData(editBruderForm);
                formData.append('action', 'edit_bruder');
                formData.append('id_bruder', currentEditBruderId);

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
                        alert(data.message);
                        // Refresh page to show updated data
                        location.reload();
                    } else {
                        alert('Gagal mengedit bruder: ' + data.message);
                    }
                })
                .catch(error => {
                    showModalLoading(false);
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                    console.error('Edit bruder error:', error);
                });
            }

            // AJAX Delete bruder
            window.hapusBruder = function(bruderId, namaBruder) {
                if (confirm(`Apakah Anda yakin ingin menghapus bruder "${namaBruder}"?\n\nData yang sudah dihapus tidak dapat dikembalikan.`)) {
                    const formData = new FormData();
                    formData.append('action', 'delete_bruder');
                    formData.append('id_bruder', bruderId);

                    fetch('ajax_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            // Refresh page to show updated data
                            location.reload();
                        } else {
                            alert('Gagal menghapus bruder: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Terjadi kesalahan. Silakan coba lagi.');
                        console.error('Delete bruder error:', error);
                    });
                }
            };

            // Modal event listeners
            closeEditModal.addEventListener('click', hideEditModal);
            cancelEditBtn.addEventListener('click', hideEditModal);
            saveEditBtn.addEventListener('click', editBruder);

            // Close modal when clicking outside
            editModal.addEventListener('click', function(e) {
                if (e.target === editModal) {
                    hideEditModal();
                }
            });

            // Enter key support for edit form
            editBruderForm.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && e.target.type !== 'textarea') {
                    e.preventDefault();
                    editBruder();
                }
            });
        });
    </script>
</body>
</html>
