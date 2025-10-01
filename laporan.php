<?php
// =====================================================================
//            HALAMAN LAPORAN KEUANGAN APLIKASI FIC BRUDERAN
// =====================================================================

// --- 1. Memulai Sesi & Keamanan ---
session_start();

// Cek apakah user sudah login. Jika belum, redirect ke halaman login.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// Mengambil data user dari session
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// --- 2. Konfigurasi & Koneksi Database ---
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
// PENTING: Pastikan ini adalah nama database yang benar
$db_name = 'db_fic_bruderan'; 

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// --- 3. Logika Mengambil Data Bruder ---
// Query untuk mengambil semua data dari tabel bruder
// Ganti `bruder` dengan nama tabel Anda jika berbeda.
$sql = "SELECT id_bruder, nama_bruder FROM bruder"; 
$result_bruder = $conn->query($sql);

// --- 4. Logika Logout ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Aplikasi FIC</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Style untuk sidebar aktif */
        .sidebar-link.active {
            background-color: #004488;
            color: white;
        }
    </style>
    <!-- Load Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg flex flex-col">
            <!-- Logo di Sidebar -->
            <div class="p-6 text-center border-b">
                <img src="https://placehold.co/100x100/003366/FFFFFF?text=FIC" alt="Logo FIC" class="w-20 h-20 mx-auto mb-2">
                <h2 class="text-xl font-bold text-gray-700">Aplikasi FIC</h2>
            </div>

            <!-- Menu Navigasi -->
            <nav class="flex-grow pt-4">
                <a href="dashboard.php" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-200 transition">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </a>
                <a href="laporan.php" class="sidebar-link active flex items-center px-6 py-3 text-gray-600 hover:bg-gray-200 transition">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Laporan Keuangan
                </a>
            </nav>

            <!-- Tombol Keluar -->
            <div class="p-6 border-t">
                <a href="laporan.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition duration-300 ease-in-out block">
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 bg-[#002244] p-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 h-full overflow-y-auto">
                <!-- Header Konten -->
                <div class="border-b pb-4 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h1 class="text-3xl font-bold text-gray-800">Daftar Anggaran Bruder</h1>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-500">Cabang</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['kode_cabang'] . ' - ' . $_SESSION['nama_cabang']); ?></p>
                        </div>
                    </div>
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Cari nama..." class="border rounded-full py-2 px-4 pl-10 w-64 focus:outline-none focus:ring-2 focus:ring-[#003366]">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                    </div>
                </div>

                <!-- Loading Spinner -->
                <div id="loadingSpinner" class="flex justify-center mb-4 hidden">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#003366]"></div>
                </div>

                <!-- Search Results Message -->
                <div id="searchMessage" class="mb-4 text-gray-600 hidden"></div>

                <!-- Tabel Data Dinamis -->
                <div class="w-full">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b-2 border-gray-200 bg-gray-50">
                                <th class="py-3 px-4 font-bold text-lg text-gray-600">No.</th>
                                <th class="py-3 px-4 font-bold text-lg text-gray-600">Nama</th>
                                <th class="py-3 px-4 font-bold text-lg text-gray-600 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_bruder && $result_bruder->num_rows > 0): ?>
                                <?php $nomor = 1; ?>
                                <?php while($row = $result_bruder->fetch_assoc()): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-4 px-4 text-gray-700"><?php echo $nomor++; ?></td>
                                    <td class="py-4 px-4 text-gray-700 font-medium"><?php echo htmlspecialchars($row['nama_bruder']); ?></td>
                                    <td class="py-4 px-4 text-center">
                                        <!-- Tombol 'Pilih' bisa diarahkan ke halaman detail dengan ID bruder -->
                                        <a href="anggaran.php?id=<?php echo $row['id_bruder']; ?>" class="bg-[#003366] text-white font-semibold py-2 px-6 rounded-full hover:bg-[#004488] transition">
                                            Pilih
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-10 text-gray-500">
                                        Tidak ada data bruder yang ditemukan.
                                    </td>
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
            const searchInput = document.getElementById('searchInput');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const searchMessage = document.getElementById('searchMessage');
            const tbody = document.querySelector('tbody');

            let searchTimeout;

            // Function to show loading
            function showLoading(show = true) {
                loadingSpinner.classList.toggle('hidden', !show);
            }

            // Function to show search message
            function showSearchMessage(message, type = 'info') {
                searchMessage.textContent = message;
                searchMessage.className = `mb-4 text-${type === 'error' ? 'red' : 'gray'}-600`;
                searchMessage.classList.remove('hidden');
            }

            // Function to hide search message
            function hideSearchMessage() {
                searchMessage.classList.add('hidden');
            }

            // Function to update table with search results
            function updateTable(bruderList) {
                let html = '';

                if (bruderList && bruderList.length > 0) {
                    bruderList.forEach((bruder, index) => {
                        html += `
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-4 px-4 text-gray-700">${index + 1}</td>
                                <td class="py-4 px-4 text-gray-700 font-medium">${bruder.nama_bruder}</td>
                                <td class="py-4 px-4 text-center">
                                    <a href="anggaran.php?id=${bruder.id_bruder}" class="bg-[#003366] text-white font-semibold py-2 px-6 rounded-full hover:bg-[#004488] transition">
                                        Pilih
                                    </a>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="3" class="text-center py-10 text-gray-500">Tidak ada data bruder yang ditemukan.</td></tr>';
                }

                tbody.innerHTML = html;
            }

            // Function to perform search
            function performSearch(query) {
                if (query.length < 2) {
                    // Load all bruder if query is too short
                    loadAllBruder();
                    return;
                }

                showLoading(true);
                hideSearchMessage();

                const urlParams = new URLSearchParams({
                    action: 'search_bruder',
                    q: query
                });

                fetch(`ajax_handler.php?${urlParams}`)
                .then(response => response.json())
                .then(data => {
                    showLoading(false);

                    if (data.success) {
                        updateTable(data.data);
                        if (data.data && data.data.length > 0) {
                            showSearchMessage(`Ditemukan ${data.data.length} hasil untuk "${query}"`);
                        } else {
                            showSearchMessage(`Tidak ditemukan hasil untuk "${query}"`, 'error');
                        }
                    } else {
                        showSearchMessage('Terjadi kesalahan saat mencari data', 'error');
                        console.error('Search error:', data.message);
                    }
                })
                .catch(error => {
                    showLoading(false);
                    showSearchMessage('Terjadi kesalahan. Silakan coba lagi.', 'error');
                    console.error('Search error:', error);
                });
            }

            // Function to load all bruder
            function loadAllBruder() {
                showLoading(true);
                hideSearchMessage();

                fetch('ajax_handler.php?action=get_bruder_list')
                .then(response => response.json())
                .then(data => {
                    showLoading(false);

                    if (data.success) {
                        updateTable(data.data);
                    } else {
                        showSearchMessage('Gagal memuat data bruder', 'error');
                        console.error('Load bruder error:', data.message);
                    }
                })
                .catch(error => {
                    showLoading(false);
                    showSearchMessage('Terjadi kesalahan saat memuat data', 'error');
                    console.error('Load bruder error:', error);
                });
            }

            // Search input event listener with debounce
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.trim();

                // Clear previous timeout
                clearTimeout(searchTimeout);

                // Set new timeout for debounce (300ms)
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            });

            // Initial load - load all bruder
            loadAllBruder();

            // Enter key support
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const query = e.target.value.trim();
                    performSearch(query);
                }
            });
        });
    </script>
</body>
</html>
