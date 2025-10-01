<?php
// =====================================================================
//            HALAMAN DASHBOARD BRUDER - APLIKASI FIC BRUDERAN
// =====================================================================

// --- 1. Memulai Sesi & Keamanan ---
session_start();

// Cek apakah bruder sudah login. Jika belum, redirect ke halaman login.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'bruder') {
    header("Location: login.php");
    exit;
}

// Mengambil data bruder dari session untuk ditampilkan
$username = $_SESSION['username'];
$nama_bruder = $_SESSION['nama_bruder'];
$id_bruder = $_SESSION['id_bruder'];

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
    <title>Dashboard Bruder - Aplikasi FIC</title>
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

            <!-- Menu Navigasi untuk Bruder -->
            <nav class="flex-grow pt-4 space-y-2">
                <a href="dashboard_bruder.php" class="sidebar-link active flex items-center px-6 py-3 text-white bg-[#004488] transition">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                    Dashboard
                </a>
                <div class="px-6 py-2">
                    <p class="text-xs font-semibold text-gray-300 uppercase tracking-wider">Kas</p>
                </div>
                <a href="kas_penerimaan_bruder.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-[#004488] hover:text-white transition">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"></path></svg>
                    Kas Penerimaan
                </a>
                <a href="kas_pengeluaran_bruder.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-[#004488] hover:text-white transition">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"></path></svg>
                    Kas Pengeluaran
                </a>
            </nav>

            <!-- Tombol Keluar -->
            <div class="p-6">
                <button id="logoutBtn" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition duration-300 ease-in-out">
                    Keluar
                </button>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 bg-[#002244] p-10 flex flex-col items-center justify-center text-white">
            <img src="https://placehold.co/150x150/FFFFFF/003366?text=FIC" alt="Logo FIC Center" class="w-36 h-36 mb-8 animate-pulse">
            <h1 class="text-4xl font-bold mb-2">Selamat Datang, Bruder</h1>
            <h2 class="text-3xl font-semibold mb-4"><?php echo htmlspecialchars($nama_bruder); ?></h2>
            <div class="text-center space-y-2">
                <p class="text-lg">Cabang: <span class="font-bold bg-white text-[#003366] px-3 py-1 rounded-full"><?php echo htmlspecialchars($_SESSION['kode_cabang'] . ' - ' . $_SESSION['nama_cabang']); ?></span></p>
                <p class="text-lg">Role: <span class="font-bold bg-white text-[#003366] px-3 py-1 rounded-full">Bruder</span></p>
            </div>

            <!-- Quick Actions -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl">
                <div class="bg-white bg-opacity-10 p-6 rounded-xl backdrop-blur-sm">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 mr-3 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"></path>
                        </svg>
                        <h3 class="text-xl font-semibold">Kas Penerimaan</h3>
                    </div>
                    <p class="text-gray-300 mb-4">Catat semua penerimaan kas harian Anda dengan mudah</p>
                    <a href="kas_penerimaan_bruder.php" class="inline-block bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                        Buka Kas Penerimaan
                    </a>
                </div>

                <div class="bg-white bg-opacity-10 p-6 rounded-xl backdrop-blur-sm">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 mr-3 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"></path>
                        </svg>
                        <h3 class="text-xl font-semibold">Kas Pengeluaran</h3>
                    </div>
                    <p class="text-gray-300 mb-4">Catat pengeluaran dengan upload foto bukti untuk verifikasi</p>
                    <a href="kas_pengeluaran_bruder.php" class="inline-block bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        Buka Kas Pengeluaran
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.getElementById('logoutBtn');

            // AJAX Logout function
            function performLogout() {
                logoutBtn.disabled = true;
                logoutBtn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mx-auto"></div>';

                const formData = new FormData();
                formData.append('action', 'logout');

                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirect to login page after successful logout
                        window.location.href = 'login.php';
                    } else {
                        alert('Gagal logout: ' + data.message);
                        // Reset button
                        logoutBtn.disabled = false;
                        logoutBtn.innerHTML = 'Keluar';
                    }
                })
                .catch(error => {
                    alert('Terjadi kesalahan saat logout. Silakan coba lagi.');
                    console.error('Logout error:', error);
                    // Reset button
                    logoutBtn.disabled = false;
                    logoutBtn.innerHTML = 'Keluar';
                });
            }

            // Event listener for logout button
            logoutBtn.addEventListener('click', function() {
                if (confirm('Apakah Anda yakin ingin keluar?')) {
                    performLogout();
                }
            });
        });
    </script>
</body>
</html>
