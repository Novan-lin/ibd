<?php
// =====================================================================
//            HALAMAN PERJALANAN BRUDER - APLIKASI FIC BRUDERAN
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

// Initialize variables
$success_message = '';
$error_message = '';
$edit_mode = false;
$edit_id = 0;

// Get current branch access
$current_cabang = $_SESSION['id_cabang'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tambah_perjalanan'])) {
        // Add new travel record
        $id_bruder = (int)$_POST['id_bruder'];
        $tanggal_berangkat = $_POST['tanggal_berangkat'];
        $tanggal_kembali = $_POST['tanggal_kembali'];
        $keterangan = $_POST['keterangan'];

        // Calculate jumlah hari
        $datetime1 = new DateTime($tanggal_berangkat);
        $datetime2 = new DateTime($tanggal_kembali);
        $jumlah_hari = $datetime1->diff($datetime2)->days + 1;

        if ($id_bruder > 0 && !empty($tanggal_berangkat) && !empty($tanggal_kembali) && !empty($keterangan)) {
            if ($tanggal_kembali <= $tanggal_berangkat) {
                $error_message = "Tanggal kembali harus setelah tanggal berangkat!";
            } else {
                $stmt = $conn->prepare("INSERT INTO perjalanan_bruder (id_bruder, tanggal_berangkat, tanggal_kembali, jumlah_hari, keterangan) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issis", $id_bruder, $tanggal_berangkat, $tanggal_kembali, $jumlah_hari, $keterangan);

                if ($stmt->execute()) {
                    $success_message = "Data perjalanan berhasil ditambahkan!";
                } else {
                    $error_message = "Gagal menambahkan data: " . $conn->error;
                }
                $stmt->close();
            }
        } else {
            $error_message = "Semua field harus diisi dengan lengkap!";
        }
    } elseif (isset($_POST['update_perjalanan'])) {
        // Update existing travel record
        $edit_id = (int)$_POST['edit_id'];
        $tanggal_berangkat = $_POST['tanggal_berangkat'];
        $tanggal_kembali = $_POST['tanggal_kembali'];
        $keterangan = $_POST['keterangan'];

        // Calculate jumlah hari
        $datetime1 = new DateTime($tanggal_berangkat);
        $datetime2 = new DateTime($tanggal_kembali);
        $jumlah_hari = $datetime1->diff($datetime2)->days + 1;

        if (!empty($tanggal_berangkat) && !empty($tanggal_kembali) && !empty($keterangan)) {
            if ($tanggal_kembali <= $tanggal_berangkat) {
                $error_message = "Tanggal kembali harus setelah tanggal berangkat!";
            } else {
                $stmt = $conn->prepare("UPDATE perjalanan_bruder SET tanggal_berangkat=?, tanggal_kembali=?, jumlah_hari=?, keterangan=? WHERE id_perjalanan=?");
                $stmt->bind_param("ssisi", $tanggal_berangkat, $tanggal_kembali, $jumlah_hari, $keterangan, $edit_id);

                if ($stmt->execute()) {
                    $success_message = "Data perjalanan berhasil diperbarui!";
                    $edit_mode = false;
                    $edit_id = 0;
                } else {
                    $error_message = "Gagal memperbarui data: " . $conn->error;
                }
                $stmt->close();
            }
        } else {
            $error_message = "Semua field harus diisi dengan lengkap!";
        }
    }
}

// Handle edit request
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM perjalanan_bruder WHERE id_perjalanan = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
        $edit_mode = true;
    }
    $stmt->close();
}

// Handle delete request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM perjalanan_bruder WHERE id_perjalanan = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        $success_message = "Data perjalanan berhasil dihapus!";
    } else {
        $error_message = "Gagal menghapus data: " . $conn->error;
    }
    $stmt->close();
}

// Get all brothers for the current branch (or all branches if admin)
$brothers_query = "SELECT b.id_bruder, b.nama_bruder, b.id_cabang, c.nama_cabang
                   FROM bruder b
                   JOIN cabang c ON b.id_cabang = c.id_cabang";
if ($_SESSION['level'] !== 'admin_cabang' || $current_cabang != 1) {
    $brothers_query .= " WHERE b.id_cabang = " . $current_cabang;
}
$brothers_query .= " ORDER BY c.nama_cabang, b.nama_bruder";

$brothers_result = $conn->query($brothers_query);

// Get all travel records with brother and branch info
$travel_query = "SELECT p.*, b.nama_bruder, b.id_bruder, c.nama_cabang, c.kode_cabang
                 FROM perjalanan_bruder p
                 JOIN bruder b ON p.id_bruder = b.id_bruder
                 JOIN cabang c ON b.id_cabang = c.id_cabang";
if ($_SESSION['level'] !== 'admin_cabang' || $current_cabang != 1) {
    $travel_query .= " WHERE b.id_cabang = " . $current_cabang;
}
$travel_query .= " ORDER BY p.tanggal_berangkat DESC";

$travel_result = $conn->query($travel_query);

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
    <title>Perjalanan Bruder - Aplikasi FIC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background-color: #004488; color: white; }
        th, td { padding: 0.75rem; border-bottom: 1px solid #E5E7EB; }
        th { background-color: #F9FAFB; font-weight: 600; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-[#003366] text-white flex flex-col">
            <!-- Logo di Sidebar -->
            <div class="p-6 text-center">
                <img src="https://placehold.co/120x120/FFFFFF/003366?text=FIC" alt="Logo FIC" class="w-24 h-24 mx-auto mb-4 rounded-full">
            </div>

            <!-- Menu Navigasi -->
            <nav class="flex-grow pt-4 space-y-2">
                <a href="dashboard.php" class="sidebar-link active flex items-center px-6 py-3 text-white bg-[#004488] transition">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                    Dashboard
                </a>
                <a href="laporan.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-[#004488] hover:text-white transition">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path><path d="M12 2.252A8.014 8.014 0 0117.748 12H12V2.252z"></path></svg>
                    Laporan Keuangan
                </a>
                <a href="perjalanan_bruder.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-[#004488] hover:text-white transition">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Perjalanan Bruder
                </a>
                <a href="bruder_management.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-[#004488] hover:text-white transition">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path></svg>
                    Kelola Bruder
                </a>
            </nav>

            <!-- Tombol Keluar -->
            <div class="p-6">
                <a href="perjalanan_bruder.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition duration-300 ease-in-out block">
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Perjalanan Bruder</h1>
                    <p class="text-gray-600">Kelola data perjalanan bruder dari semua cabang</p>
                </div>

                <!-- Alerts -->
                <?php if ($success_message): ?>
                    <div id="successAlert" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div id="errorAlert" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Travel Form -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 p-2 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">
                            <?php echo $edit_mode ? 'Edit Perjalanan' : 'Tambah Perjalanan Baru'; ?>
                        </h2>
                    </div>

                    <form method="POST" class="space-y-6">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="update_perjalanan" value="1">
                            <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                        <?php else: ?>
                            <input type="hidden" name="tambah_perjalanan" value="1">
                        <?php endif; ?>

                        <!-- Brother Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="id_bruder" class="block text-sm font-medium text-gray-700 mb-2">
                                    Pilih Bruder
                                </label>
                                <select name="id_bruder" id="id_bruder" required
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 py-3 px-4">
                                    <option value="">-- Pilih Bruder --</option>
                                    <?php if ($brothers_result && $brothers_result->num_rows > 0): ?>
                                        <?php while($brother = $brothers_result->fetch_assoc()): ?>
                                            <option value="<?php echo $brother['id_bruder']; ?>"
                                                    <?php echo ($edit_mode && $edit_data['id_bruder'] == $brother['id_bruder']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($brother['nama_bruder'] . ' - ' . $brother['nama_cabang']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Date Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="tanggal_berangkat" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tanggal Berangkat
                                    </label>
                                    <input type="date" name="tanggal_berangkat" id="tanggal_berangkat" required
                                           value="<?php echo $edit_mode ? $edit_data['tanggal_berangkat'] : ''; ?>"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 py-3 px-4">
                                </div>
                                <div>
                                    <label for="tanggal_kembali" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tanggal Kembali
                                    </label>
                                    <input type="date" name="tanggal_kembali" id="tanggal_kembali" required
                                           value="<?php echo $edit_mode ? $edit_data['tanggal_kembali'] : ''; ?>"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 py-3 px-4">
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                                Keterangan Perjalanan
                            </label>
                            <textarea name="keterangan" id="keterangan" rows="3" required
                                      placeholder="Jelaskan tujuan dan detail perjalanan..."
                                      class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 py-3 px-4"
                            ><?php echo $edit_mode ? htmlspecialchars($edit_data['keterangan']) : ''; ?></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-3">
                            <?php if ($edit_mode): ?>
                                <a href="perjalanan_bruder.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                                    Batal
                                </a>
                                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                                    Perbarui Perjalanan
                                </button>
                            <?php else: ?>
                                <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                                    Tambah Perjalanan
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Travel Records Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Riwayat Perjalanan</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Bruder</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cabang</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Berangkat</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Kembali</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah Hari</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($travel_result && $travel_result->num_rows > 0): ?>
                                    <?php $no = 1; ?>
                                    <?php while($row = $travel_result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $no++; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($row['nama_bruder']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($row['kode_cabang'] . ' - ' . $row['nama_cabang']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d/m/Y', strtotime($row['tanggal_berangkat'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d/m/Y', strtotime($row['tanggal_kembali'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $row['jumlah_hari']; ?> hari
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?php echo htmlspecialchars($row['keterangan']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="perjalanan_bruder.php?action=edit&id=<?php echo $row['id_perjalanan']; ?>"
                                                       class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                        Edit
                                                    </a>
                                                    <button onclick="confirmDelete(<?php echo $row['id_perjalanan']; ?>, '<?php echo htmlspecialchars($row['nama_bruder']); ?>')"
                                                            class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                        Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                            Belum ada data perjalanan bruder.
                                        </td>
                                    <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            if (successAlert) successAlert.remove();
            if (errorAlert) errorAlert.remove();
        }, 5000);

        function confirmDelete(id, namaBruder) {
            if (confirm(`Apakah Anda yakin ingin menghapus data perjalanan untuk ${namaBruder}?`)) {
                window.location.href = `perjalanan_bruder.php?action=delete&id=${id}`;
            }
        }

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('tanggal_berangkat').setAttribute('min', today);
        document.getElementById('tanggal_kembali').setAttribute('min', today);
    </script>
</body>
</html>
