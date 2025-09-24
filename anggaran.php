<?php
/// =====================================================================
//            HALAMAN DATA BRUDER (setelah pemilihan)
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

if ($bruder_id > 0) {
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
    <title>Data - <?php echo htmlspecialchars($bruder_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background-color: #F3F4F6; font-weight: 600; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#002244]">
    <div class="flex h-screen">
        <aside class="w-64 bg-white flex flex-col">
            <div class="p-6 text-center border-b">
                <img src="https://placehold.co/100x100/003366/FFFFFF?text=FIC" alt="Logo FIC" class="w-20 h-20 mx-auto mb-2 rounded-full">
            </div>
            <!-- NAVIGASI YANG SUDAH DIPERBAIKI -->
            <nav class="flex-grow pt-4">
                <a href="anggaran.php?id=<?php echo $bruder_id; ?>" class="sidebar-link active flex items-center px-6 py-3 text-gray-800 transition">Data</a>
                <a href="kode_perkiraan.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kode Perkiraan</a>
                <a href="kas_harian.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Harian</a>
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
                <a href="anggaran.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition">
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 p-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 h-full flex flex-col">
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
                <div class="text-center text-gray-500 pt-10">
                    <h2 class="text-2xl font-semibold mb-2">Halaman Data Bruder</h2>
                    <p>Ini adalah halaman utama untuk Bruder <?php echo htmlspecialchars($bruder_name); ?>. Silakan pilih menu di samping untuk melanjutkan.</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

