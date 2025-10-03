<?php
// =====================================================================
//            TEST DATABASE SETUP - FIC BRUDERAN
// =====================================================================
// Script untuk test dan verifikasi data dummy sudah ter-setup dengan benar
// =====================================================================

$conn = new mysqli('localhost', 'root', '', 'db_fic_bruderan');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

echo "<h1>🔍 TEST DATABASE SETUP - FIC BRUDERAN</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>";

// Test 1: Cek total bruder per cabang
echo "<h2>👥 Test 1: Bruder per Cabang</h2>";
$result = $conn->query("
    SELECT
        c.kode_cabang,
        c.nama_cabang,
        COUNT(b.id_bruder) as jumlah_bruder,
        GROUP_CONCAT(b.nama_bruder SEPARATOR ', ') as daftar_bruder
    FROM cabang c
    LEFT JOIN bruder b ON c.id_cabang = b.id_cabang
    GROUP BY c.id_cabang, c.kode_cabang, c.nama_cabang
    ORDER BY c.id_cabang
");

if ($result) {
    echo "<table>";
    echo "<tr><th>Cabang</th><th>Kode</th><th>Jumlah Bruder</th><th>Daftar Bruder</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['nama_cabang'] . "</td>";
        echo "<td>" . $row['kode_cabang'] . "</td>";
        echo "<td>" . $row['jumlah_bruder'] . "</td>";
        echo "<td>" . $row['daftar_bruder'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 2: Cek transaksi minggu ini untuk Bandung
echo "<h2>💰 Test 2: Transaksi Minggu Ini - Cabang Bandung</h2>";
$current_week = date('W');
$current_year = date('Y');

$result = $conn->query("
    SELECT
        b.nama_bruder,
        t.tanggal_transaksi,
        kp.kode_perkiraan,
        kp.nama_akun,
        t.keterangan,
        t.nominal_penerimaan,
        t.nominal_pengeluaran
    FROM transaksi t
    JOIN bruder b ON t.id_bruder = b.id_bruder
    JOIN kode_perkiraan kp ON t.id_perkiraan = kp.id_perkiraan
    WHERE b.id_cabang = 2
    AND WEEK(t.tanggal_transaksi) = $current_week
    AND YEAR(t.tanggal_transaksi) = $current_year
    ORDER BY t.tanggal_transaksi DESC
");

if ($result) {
    echo "<table>";
    echo "<tr><th>Bruder</th><th>Tanggal</th><th>Kode</th><th>Akun</th><th>Keterangan</th><th>Penerimaan</th><th>Pengeluaran</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['nama_bruder'] . "</td>";
        echo "<td>" . $row['tanggal_transaksi'] . "</td>";
        echo "<td>" . $row['kode_perkiraan'] . "</td>";
        echo "<td>" . $row['nama_akun'] . "</td>";
        echo "<td>" . $row['keterangan'] . "</td>";
        echo "<td>Rp " . number_format($row['nominal_penerimaan'], 0, ',', '.') . "</td>";
        echo "<td>Rp " . number_format($row['nominal_pengeluaran'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 3: Summary data untuk dashboard
echo "<h2>📊 Test 3: Summary Data untuk Dashboard</h2>";
$result = $conn->query("
    SELECT
        b.id_cabang,
        c.nama_cabang,
        COUNT(DISTINCT b.id_bruder) as total_bruder,
        COUNT(t.id_transaksi) as total_transaksi,
        COALESCE(SUM(t.nominal_penerimaan), 0) as total_penerimaan,
        COALESCE(SUM(t.nominal_pengeluaran), 0) as total_pengeluaran
    FROM cabang c
    LEFT JOIN bruder b ON c.id_cabang = b.id_cabang
    LEFT JOIN transaksi t ON b.id_bruder = t.id_bruder
    GROUP BY b.id_cabang, c.nama_cabang
    ORDER BY b.id_cabang
");

if ($result) {
    echo "<table>";
    echo "<tr><th>Cabang</th><th>Total Bruder</th><th>Total Transaksi</th><th>Total Penerimaan</th><th>Total Pengeluaran</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['nama_cabang'] . "</td>";
        echo "<td>" . $row['total_bruder'] . "</td>";
        echo "<td>" . $row['total_transaksi'] . "</td>";
        echo "<td>Rp " . number_format($row['total_penerimaan'], 0, ',', '.') . "</td>";
        echo "<td>Rp " . number_format($row['total_pengeluaran'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>🔧 Test 4: Login Credentials untuk Testing</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Bruder Petrus Bandung:</strong></p>";
echo "<p>Email: petrus.bandung@ficbruderan.org</p>";
echo "<p>Password: password123</p>";
echo "<p>Cabang: FIC02 - Cabang Bandung</p>";
echo "</div>";

echo "<div style='background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Bendahara Bandung:</strong></p>";
echo "<p>Username: bendaharabandung</p>";
echo "<p>Password: bendaharabandung</p>";
echo "<p>Level: bendahara</p>";
echo "</div>";

echo "<h2>📋 Test 5: Cara Manual Setup Database</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Jika script otomatis tidak bisa dijalankan:</strong></p>";
echo "<ol>";
echo "<li>Buka phpMyAdmin</li>";
echo "<li>Pilih database <code>db_fic_bruderan</code></li>";
echo "<li>Copy paste isi file <code>setup_dummy_data_multi_cabang.sql</code></li>";
echo "<li>Klik Execute/Run</li>";
echo "</ol>";
echo "</div>";

$conn->close();
?>
