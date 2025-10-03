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
$current_cabang = $_SESSION['id_cabang'];

// Koneksi database
$conn = new mysqli('localhost', 'root', '', 'db_fic_bruderan');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Fungsi untuk format mata uang
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Query data statistik bruder
$bruder_cabang = $conn->query("SELECT COUNT(*) as total FROM bruder WHERE id_cabang = $current_cabang")->fetch_assoc()['total'];
$total_bruder = $conn->query("SELECT COUNT(*) as total FROM bruder")->fetch_assoc()['total'];

// Bruder baru bulan ini (asumsi tidak ada kolom created_at, jadi skip untuk sementara)
$bruder_baru = 0;

// Query data keuangan minggu ini (termasuk data bruder dari cabang ini)
$current_week = date('W');
$current_year = date('Y');
$weekly_data = $conn->query("
    SELECT
        COALESCE(SUM(t.nominal_penerimaan), 0) as pemasukan,
        COALESCE(SUM(t.nominal_pengeluaran), 0) as pengeluaran,
        COUNT(*) as jumlah_transaksi
    FROM transaksi t
    JOIN bruder b ON t.id_bruder = b.id_bruder
    JOIN cabang c ON b.id_cabang = c.id_cabang
    WHERE b.id_cabang = $current_cabang
    AND WEEK(t.tanggal_transaksi) = $current_week
    AND YEAR(t.tanggal_transaksi) = $current_year
")->fetch_assoc();

// Query saldo kas saat ini (termasuk data bruder dari cabang ini)
$kas_balance = $conn->query("
    SELECT COALESCE(SUM(t.nominal_penerimaan) - SUM(t.nominal_pengeluaran), 0) as saldo
    FROM transaksi t
    JOIN bruder b ON t.id_bruder = b.id_bruder
    JOIN cabang c ON b.id_cabang = c.id_cabang
    WHERE b.id_cabang = $current_cabang
")->fetch_assoc()['saldo'];

// Query data grafik bulanan (12 bulan terakhir - termasuk data bruder dari cabang ini)
$monthly_data = $conn->query("
    SELECT
        DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') as bulan,
        DATE_FORMAT(t.tanggal_transaksi, '%M %Y') as label_bulan,
        COALESCE(SUM(t.nominal_penerimaan), 0) as pemasukan,
        COALESCE(SUM(t.nominal_pengeluaran), 0) as pengeluaran
    FROM transaksi t
    JOIN bruder b ON t.id_bruder = b.id_bruder
    JOIN cabang c ON b.id_cabang = c.id_cabang
    WHERE b.id_cabang = $current_cabang
    AND t.tanggal_transaksi >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
    GROUP BY YEAR(t.tanggal_transaksi), MONTH(t.tanggal_transaksi)
    ORDER BY bulan
");

$months = [];
$income_data = [];
$expense_data = [];

if ($monthly_data->num_rows > 0) {
    while ($row = $monthly_data->fetch_assoc()) {
        $months[] = $row['label_bulan'];
        $income_data[] = $row['pemasukan'];
        $expense_data[] = $row['pengeluaran'];
    }
}

// Jika tidak ada data bulan, buat data dummy
if (empty($months)) {
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
    $income_data = [5000000, 7500000, 6000000, 8000000, 7000000, 9000000];
    $expense_data = [3000000, 4000000, 3500000, 4500000, 4000000, 5000000];
}

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
        /* Chart container styling untuk kontrol height */
        .chart-container {
            max-height: 120px !important;
            overflow: hidden;
        }
        .chart-container canvas {
            max-height: 120px !important;
            height: 120px !important;
        }
        /* Pastikan chart tidak melebihi parent container */
        #monthlyChart {
            max-height: 120px !important;
            height: 120px !important;
        }
    </style>
    <!-- Load Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Load Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <button id="logoutBtn" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition duration-300 ease-in-out">
                    Keluar
                </button>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 bg-gray-50 p-2">
            <div class="max-w-5xl mx-auto">
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-sm p-3 mb-3">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-lg font-bold text-gray-900">KASA - KEUANGAN ANGGARAN</h1>
                            <p class="text-gray-600 text-xs">& SISTEM ADMINISTRATIF</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="relative">
                                <input type="text" id="searchInput" placeholder="Search..."
                                       class="pl-6 pr-2 py-1 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 w-32">
                                <svg class="w-3 h-3 absolute left-2 top-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <div class="bg-blue-600 text-white px-2 py-1 rounded-lg">
                                <span class="text-xs font-medium"><?php echo htmlspecialchars($_SESSION['kode_cabang'] . ' - ' . $_SESSION['nama_cabang']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics Cards -->
                <div class="grid grid-cols-3 gap-2 mb-3">
                    <div class="bg-white rounded-lg shadow-sm p-2 border-l-4 border-blue-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-600">Bruder FIC Cabang</p>
                                <p class="text-lg font-bold text-gray-900" id="bruderCabangCount">-</p>
                            </div>
                            <div class="bg-blue-100 p-1.5 rounded-full">
                                <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-2 border-l-4 border-green-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-600">Bruder Baru</p>
                                <p class="text-lg font-bold text-gray-900" id="bruderBaruCount">-</p>
                                <p class="text-xs text-gray-500">Bulan ini</p>
                            </div>
                            <div class="bg-green-100 p-1.5 rounded-full">
                                <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-2 border-l-4 border-purple-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-600">Total Bruder</p>
                                <p class="text-lg font-bold text-gray-900" id="totalBruderCount">-</p>
                                <p class="text-xs text-gray-500">Semua cabang</p>
                            </div>
                            <div class="bg-purple-100 p-1.5 rounded-full">
                                <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">
                    <!-- Monthly Chart -->
                    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-3">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-xs font-semibold text-gray-900">Laporan Keuangan Bulanan</h3>
                            <div class="flex space-x-1">
                                <button class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-700 rounded">3</button>
                                <button class="px-1.5 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">6</button>
                                <button class="px-1.5 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">12</button>
                            </div>
                        </div>
                        <div class="chart-container" style="position: relative; height: 120px; width: 100%;">
                            <canvas id="monthlyChart" style="max-height: 120px;"></canvas>
                        </div>
                    </div>

                    <!-- Current Week Summary -->
                    <div class="bg-white rounded-lg shadow-sm p-3">
                        <h3 class="text-xs font-semibold text-gray-900 mb-2">Kegiatan Minggu Ini</h3>
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between p-1.5 bg-green-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></div>
                                    <span class="text-xs font-medium text-gray-700">Pemasukan</span>
                                </div>
                                <span class="text-xs font-bold text-green-600" id="weeklyIncome">-</span>
                            </div>
                            <div class="flex items-center justify-between p-1.5 bg-red-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></div>
                                    <span class="text-xs font-medium text-gray-700">Pengeluaran</span>
                                </div>
                                <span class="text-xs font-bold text-red-600" id="weeklyExpense">-</span>
                            </div>
                            <div class="flex items-center justify-between p-1.5 bg-blue-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full mr-1.5"></div>
                                    <span class="text-xs font-medium text-gray-700">Kegiatan</span>
                                </div>
                                <span class="text-xs font-bold text-blue-600" id="weeklyActivities">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-sm p-3 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium opacity-90">Saldo Kas Yayasan</p>
                                <p class="text-sm font-bold" id="kasBalance">-</p>
                            </div>
                            <svg class="w-4 h-4 opacity-90" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-sm p-3 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium opacity-90">Pengeluaran Minggu Ini</p>
                                <p class="text-sm font-bold" id="weeklyPengeluaran">-</p>
                            </div>
                            <svg class="w-4 h-4 opacity-90" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-sm p-3 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium opacity-90">Pemasukan Minggu Ini</p>
                                <p class="text-sm font-bold" id="weeklyPemasukan">-</p>
                            </div>
                            <svg class="w-4 h-4 opacity-90" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-sm p-3 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium opacity-90">Jumlah Transaksi</p>
                                <p class="text-sm font-bold" id="totalTransaksi">-</p>
                            </div>
                            <svg class="w-4 h-4 opacity-90" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
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

            // Populate dashboard with PHP data
            document.getElementById('bruderCabangCount').textContent = '<?php echo $bruder_cabang; ?>';
            document.getElementById('bruderBaruCount').textContent = '<?php echo $bruder_baru; ?>';
            document.getElementById('totalBruderCount').textContent = '<?php echo $total_bruder; ?>';
            document.getElementById('kasBalance').textContent = '<?php echo formatRupiah($kas_balance); ?>';
            document.getElementById('weeklyPengeluaran').textContent = '<?php echo formatRupiah($weekly_data["pengeluaran"]); ?>';
            document.getElementById('weeklyPemasukan').textContent = '<?php echo formatRupiah($weekly_data["pemasukan"]); ?>';
            document.getElementById('totalTransaksi').textContent = '<?php echo $weekly_data["jumlah_transaksi"]; ?>';

            // Populate weekly activities summary
            document.getElementById('weeklyIncome').textContent = '<?php echo formatRupiah($weekly_data["pemasukan"]); ?>';
            document.getElementById('weeklyExpense').textContent = '<?php echo formatRupiah($weekly_data["pengeluaran"]); ?>';
            document.getElementById('weeklyActivities').textContent = '<?php echo $weekly_data["jumlah_transaksi"]; ?>';

            // Initialize Chart.js dengan konfigurasi height yang ketat
            const ctx = document.getElementById('monthlyChart').getContext('2d');

            // Set canvas size explicitly
            const canvas = document.getElementById('monthlyChart');
            canvas.height = 120;

            const monthlyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'Pemasukan',
                        data: <?php echo json_encode($income_data); ?>,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Pengeluaran',
                        data: <?php echo json_encode($expense_data); ?>,
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: false, // Matikan responsive untuk kontrol penuh
                    maintainAspectRatio: false, // Matikan aspect ratio
                    devicePixelRatio: 1, // Kontrol pixel ratio
                    plugins: {
                        legend: {
                            display: false // Sembunyikan legend untuk hemat ruang
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            ticks: {
                                font: {
                                    size: 9 // Font kecil untuk ticks
                                },
                                maxRotation: 45
                            }
                        },
                        y: {
                            display: true,
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 8 // Font kecil untuk ticks
                                },
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value / 1000000).toFixed(0) + 'M';
                                    }
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    elements: {
                        point: {
                            radius: 2, // Point kecil untuk hemat ruang
                            hoverRadius: 4
                        },
                        line: {
                            borderWidth: 1.5 // Line tipis untuk compact view
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    }
                }
            });

            // Pastikan chart tidak melebihi tinggi container
            const chartContainer = document.querySelector('.chart-container');
            if (chartContainer) {
                chartContainer.style.height = '120px';
                chartContainer.style.overflow = 'hidden';
            }

            // Search functionality
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                // Add search functionality here if needed
                console.log('Searching for:', searchTerm);
            });
        });
    </script>
</body>
</html>
