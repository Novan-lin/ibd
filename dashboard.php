<?php
// =====================================================================
//            HALAMAN DASHBOARD APLIKASI FIC BRUDERAN
// =====================================================================

// --- 1. Memulai Sesi & Keamanan ---
session_start();

// Cek apakah user sudah login. Jika belum, redirect ke halaman login.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Mengambil data user dari session untuk ditampilkan
$username = $_SESSION['username'];
$role = $_SESSION['role']; // 'bendahara' atau 'sekretariat'

// --- 2. Logika Logout ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Hapus semua data session
    $_SESSION = array();
    // Hancurkan session
    session_destroy();
    // Redirect ke halaman login
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Aplikasi FIC</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Style untuk sidebar aktif */
        .sidebar-link.active {
            background-color: #004488; /* Warna biru lebih gelap untuk item aktif */
            color: white;
        }
    </style>
    <!-- Load Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#002244]">
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
            </nav>

            <!-- Tombol Keluar -->
            <div class="p-6">
                <a href="dashboard.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition duration-300 ease-in-out block">
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 bg-[#002244] p-10 flex flex-col items-center justify-center text-white">
            <img src="https://placehold.co/150x150/FFFFFF/003366?text=FIC" alt="Logo FIC Center" class="w-36 h-36 mb-8 animate-pulse">
            <h1 class="text-4xl font-bold mb-2">Selamat Datang di Platform</h1>
            <h2 class="text-3xl font-semibold mb-4">Data Keuangan FIC</h2>
            <p class="text-lg">Anda login sebagai: <span class="font-bold capitalize bg-white text-[#003366] px-3 py-1 rounded-full"><?php echo htmlspecialchars($role); ?></span></p>
        </main>
    </div>
</body>
</html>
