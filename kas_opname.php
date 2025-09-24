<?php
// =====================================================================
//            HALAMAN KAS OPNAME (PERHITUNGAN & SQL DIPERBAIKI)
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
$saldo_menurut_catatan = 0;

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

    // --- LOGIKA UTAMA: MENGHITUNG SALDO DARI DATABASE (PERBAIKAN SQL) ---
    // Hanya hitung saldo dari sumber dana 'Kas Harian'
    $stmt_saldo = $conn->prepare(
        "SELECT 
            COALESCE((SUM(nominal_penerimaan) - SUM(nominal_pengeluaran)), 0) as saldo_catatan
         FROM transaksi
         WHERE id_bruder = ? AND sumber_dana = 'Kas Harian'"
    );
    $stmt_saldo->bind_param("i", $bruder_id);
    $stmt_saldo->execute();
    $result_saldo = $stmt_saldo->get_result();
    if ($result_saldo->num_rows > 0) {
        $data_saldo = $result_saldo->fetch_assoc();
        $saldo_menurut_catatan = $data_saldo['saldo_catatan'];
    }
    $stmt_saldo->close();
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
    <title>Kas Opname - <?php echo htmlspecialchars($bruder_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background-color: #F3F4F6; font-weight: 600; }
        .input-kas {
            border: 1px solid #D1D5DB;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            width: 100%;
            text-align: right;
        }
        .hasil-kas {
            padding: 0.5rem 0.75rem;
            width: 100%;
            text-align: right;
            font-weight: 500;
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
                <a href="kas_harian.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Kas Harian</a>
                <a href="bank.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Bank</a>
                <a href="bruder.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Bruder</a>
                <a href="lu_komunitas.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">LU Komunitas</a>
                <a href="evaluasi.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Evaluasi</a>
                <a href="buku_besar.php?id=<?php echo $bruder_id; ?>" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:bg-gray-100 transition">Buku Besar</a>
                <a href="kas_opname.php?id=<?php echo $bruder_id; ?>" class="sidebar-link active flex items-center px-6 py-3 text-gray-800 transition">Kas Opname</a>
            </nav>
            <div class="p-6 border-t">
                 <a href="laporan.php" class="w-full text-center mb-4 bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-300 transition block">
                    Kembali
                </a>
                <a href="kas_opname.php?action=logout" class="w-full text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-700 transition block">
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

                <!-- Form Kas Opname -->
                <div id="form-kas-opname" class="flex-grow overflow-y-auto pr-4 text-sm">
                    <!-- Bagian I -->
                    <div class="grid grid-cols-3 gap-4 items-center mb-4">
                        <label>I. Saldo kas menurut catatan pada tanggal</label>
                        <input type="text" class="input-kas col-span-1" value="<?php echo date('d F Y'); ?>" readonly>
                        <div class="relative">
                           <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                           <input type="text" id="saldo-catatan" class="input-kas pl-8 font-bold" value="<?php echo number_format($saldo_menurut_catatan, 0, ',', '.'); ?>" readonly>
                        </div>
                    </div>
                    <!-- Bagian II -->
                     <div class="grid grid-cols-3 gap-4 items-center mb-6">
                        <label>II. Kas kecil (uang saku) para Bruder</label>
                        <div class="col-span-1"></div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                           <input type="number" id="kas-kecil" class="input-kas pl-8" value="0" onkeyup="hitungTotal()">
                        </div>
                    </div>
                    <!-- Bagian III -->
                    <p class="mb-2 font-semibold">III. Hasil perhitungan kas tersebut adalah sebagai berikut:</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12">
                        <!-- Uang Kertas -->
                        <div>
                            <p class="mb-2 font-medium">1. Uang Kertas</p>
                            <div class="space-y-2">
                                <?php $uang_kertas = [100000, 50000, 20000, 10000, 5000, 2000, 1000]; ?>
                                <?php foreach($uang_kertas as $nominal): ?>
                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label>Rp <?php echo number_format($nominal, 0, ',', '.'); ?></label>
                                    <input type="number" data-nominal="<?php echo $nominal; ?>" class="input-kas lembar-kertas" onkeyup="hitungTotal()" placeholder="0" value="0">
                                    <div id="hasil-<?php echo $nominal; ?>" class="hasil-kas">Rp 0</div>
                                </div>
                                <?php endforeach; ?>
                                <div class="grid grid-cols-3 gap-2 items-center pt-2 border-t">
                                    <label class="col-span-2 font-bold text-right pr-4">Jumlah</label>
                                    <div id="total-kertas" class="hasil-kas font-bold">Rp 0</div>
                                </div>
                            </div>
                        </div>
                        <!-- Uang Logam -->
                        <div>
                            <p class="mb-2 font-medium">2. Uang Logam</p>
                             <div class="space-y-2">
                                <?php $uang_logam = [1000, 500, 200, 100]; ?>
                                <?php foreach($uang_logam as $nominal): ?>
                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <label>Rp <?php echo number_format($nominal, 0, ',', '.'); ?></label>
                                    <input type="number" data-nominal="<?php echo $nominal; ?>" class="input-kas lembar-logam" onkeyup="hitungTotal()" placeholder="0" value="0">
                                    <div id="hasil-<?php echo $nominal; ?>" class="hasil-kas">Rp 0</div>
                                </div>
                                <?php endforeach; ?>
                                <div class="grid grid-cols-3 gap-2 items-center pt-2 border-t">
                                    <label class="col-span-2 font-bold text-right pr-4">Jumlah</label>
                                    <div id="total-logam" class="hasil-kas font-bold">Rp 0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                     <!-- Total & Selisih -->
                    <div class="mt-8 pt-4 border-t-2 space-y-2">
                         <div class="grid grid-cols-3 gap-4 items-center">
                            <label class="col-span-2 font-bold text-right">Jumlah Menurut Hasil Perhitungan</label>
                             <div id="total-perhitungan" class="hasil-kas text-lg font-bold">Rp 0</div>
                        </div>
                         <div class="grid grid-cols-3 gap-4 items-center">
                            <label class="col-span-2 font-bold text-right">Selisih Lebih (Kurang)</label>
                             <div id="selisih" class="hasil-kas text-lg font-bold">Rp 0</div>
                        </div>
                    </div>
                </div>

                <div class="pt-6 text-right border-t mt-auto">
                    <button class="bg-[#003366] text-white font-bold py-2 px-8 rounded-full hover:bg-[#004488] transition">
                        Simpan
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script>
        function formatRupiah(angka) {
            if (isNaN(angka)) return 'Rp 0';
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
        }

        function parseToNumber(value) {
            return parseFloat(value.toString().replace(/[^0-9-]/g, '')) || 0;
        }

        function hitungTotal() {
            let totalKertas = 0;
            document.querySelectorAll('.lembar-kertas').forEach(input => {
                const nominal = parseFloat(input.dataset.nominal);
                const jumlahLembar = parseInt(input.value) || 0;
                const hasil = nominal * jumlahLembar;
                totalKertas += hasil;
                document.getElementById('hasil-' + nominal).textContent = formatRupiah(hasil);
            });
            document.getElementById('total-kertas').textContent = formatRupiah(totalKertas);

            let totalLogam = 0;
            document.querySelectorAll('.lembar-logam').forEach(input => {
                const nominal = parseFloat(input.dataset.nominal);
                const jumlahKeping = parseInt(input.value) || 0;
                const hasil = nominal * jumlahKeping;
                totalLogam += hasil;
                document.getElementById('hasil-' + nominal).textContent = formatRupiah(hasil);
            });
            document.getElementById('total-logam').textContent = formatRupiah(totalLogam);

            const kasKecil = parseFloat(document.getElementById('kas-kecil').value) || 0;
            
            const totalPerhitungan = totalKertas + totalLogam + kasKecil;
            document.getElementById('total-perhitungan').textContent = formatRupiah(totalPerhitungan);

            const saldoCatatanInput = document.getElementById('saldo-catatan');
            const saldoCatatan = parseToNumber(saldoCatatanInput.value);
            
            const selisih = totalPerhitungan - saldoCatatan;
            const selisihElement = document.getElementById('selisih');
            selisihElement.textContent = formatRupiah(selisih);

            selisihElement.classList.remove('text-red-600', 'text-green-600');
            if (selisih < 0) {
                selisihElement.classList.add('text-red-600');
            } else if (selisih > 0) {
                selisihElement.classList.add('text-green-600');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            hitungTotal();
        });
    </script>
</body>
</html>

