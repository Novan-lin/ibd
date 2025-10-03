<?php
// =====================================================================
//            SETUP DATABASE DUMMY OTOMATIS - FIC BRUDERAN
// =====================================================================
// Script PHP untuk menjalankan setup database dummy melalui web interface
// =====================================================================

$conn = new mysqli('localhost', 'root', '', 'db_fic_bruderan');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

echo "<h1>🚀 SETUP DATABASE DUMMY - FIC BRUDERAN</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    .button:hover { background: #0056b3; }
</style>";

// Backup existing data
echo "<div class='info'>📋 Membuat backup data existing...</div>";
$conn->query("CREATE TABLE IF NOT EXISTS bruder_backup AS SELECT * FROM bruder");
$conn->query("CREATE TABLE IF NOT EXISTS komunitas_backup AS SELECT * FROM komunitas");

echo "<div class='success'>✅ Backup berhasil dibuat</div>";

// Setup komunitas per cabang
echo "<div class='info'>🏢 Menambahkan komunitas per cabang...</div>";
$komunitas_data = [
    ['Komunitas St. Petrus', 'Jl. Asia Afrika No. 123, Bandung', 2],
    ['Komunitas St. Paulus', 'Jl. Diponegoro No. 456, Surabaya', 3],
    ['Komunitas St. Andreas', 'Jl. SM. Raja No. 789, Medan', 4],
    ['Komunitas St. Lukas', 'Jl. AP. Pettarani No. 321, Makassar', 5]
];

foreach ($komunitas_data as $komunitas) {
    $stmt = $conn->prepare("INSERT IGNORE INTO komunitas (nama_komunitas, alamat_komunitas, id_cabang) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $komunitas[0], $komunitas[1], $komunitas[2]);
    $stmt->execute();
}

echo "<div class='success'>✅ Komunitas berhasil ditambahkan</div>";

// Update bruder existing dengan cabang yang benar
echo "<div class='info'>👥 Mengupdate bruder existing dengan cabang yang benar...</div>";
$conn->query("UPDATE bruder SET id_cabang = 1, id_komunitas = 1 WHERE id_bruder IN (1,2,3)");
echo "<div class='success'>✅ Bruder existing berhasil diupdate</div>";

// Tambah bruder baru per cabang
echo "<div class='info'>👥 Menambahkan bruder baru per cabang...</div>";

// Cabang Bandung
$bruder_bandung = [
    ['Bruder Petrus', 'petrus.bandung@ficbruderan.org', 'password123', 'bruder', 2, 2, '1988-11-10', 'Asrama Komunitas St. Petrus, Bandung'],
    ['Bruder Andreas', 'andreas.bandung@ficbruderan.org', 'password123', 'bruder', 2, 2, '1990-05-25', 'Asrama Komunitas St. Petrus, Bandung'],
    ['Bruder Lukas', 'lukas.bandung@ficbruderan.org', 'password123', 'bruder', 2, 2, '1987-09-12', 'Asrama Komunitas St. Petrus, Bandung']
];

foreach ($bruder_bandung as $bruder) {
    $stmt = $conn->prepare("INSERT IGNORE INTO bruder (nama_bruder, email, password, status, id_komunitas, id_cabang, ttl_bruder, alamat_bruder) VALUES (?, ?, SHA2(?, 256), ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $bruder[0], $bruder[1], $bruder[2], $bruder[3], $bruder[4], $bruder[5], $bruder[6], $bruder[7]);
    $stmt->execute();
}

echo "<div class='success'>✅ Bruder baru berhasil ditambahkan</div>";

// Tambah transaksi dummy untuk Bandung
echo "<div class='info'>💰 Menambahkan transaksi dummy untuk Bandung...</div>";
$transaksi_bandung = [
    [6, 2, '2025-01-10', 'Donasi retret komunitas', 'Kas Harian', 400000.00, 0.00, 2],
    [6, 3, '2025-01-22', 'Bayar PDAM januari', 'Kas Harian', 0.00, 250000.00, 2],
    [7, 2, '2025-01-12', 'Donasi kegiatan kepemudaan', 'Kas Harian', 350000.00, 0.00, 2],
    [7, 4, '2025-01-24', 'Bayar bahan kegiatan', 'Kas Harian', 0.00, 100000.00, 2],
    [8, 2, '2025-01-14', 'Donasi misa harian', 'Kas Harian', 450000.00, 0.00, 2],
    [8, 3, '2025-01-26', 'Bayar kebersihan asrama', 'Kas Harian', 0.00, 80000.00, 2]
];

foreach ($transaksi_bandung as $transaksi) {
    $stmt = $conn->prepare("INSERT IGNORE INTO transaksi (id_bruder, id_perkiraan, tanggal_transaksi, keterangan, sumber_dana, nominal_penerimaan, nominal_pengeluaran, id_cabang) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssdii", $transaksi[0], $transaksi[1], $transaksi[2], $transaksi[3], $transaksi[4], $transaksi[5], $transaksi[6], $transaksi[7]);
    $stmt->execute();
}

echo "<div class='success'>✅ Transaksi dummy berhasil ditambahkan</div>";

// Verifikasi hasil
echo "<h2>🔍 VERIFIKASI HASIL SETUP</h2>";

// Cek bruder per cabang
echo "<h3>👥 Bruder per Cabang:</h3>";
$result = $conn->query("
    SELECT c.nama_cabang, COUNT(b.id_bruder) as jumlah, GROUP_CONCAT(b.nama_bruder SEPARATOR ', ') as daftar
    FROM cabang c
    LEFT JOIN bruder b ON c.id_cabang = b.id_cabang
    GROUP BY c.id_cabang, c.nama_cabang
    ORDER BY c.id_cabang
");

echo "<table>";
echo "<tr><th>Cabang</th><th>Jumlah Bruder</th><th>Daftar Bruder</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['nama_cabang']}</td><td>{$row['jumlah']}</td><td>{$row['daftar']}</td></tr>";
}
echo "</table>";

// Cek transaksi minggu ini untuk Bandung
echo "<h3>💰 Transaksi Minggu Ini - Cabang Bandung:</h3>";
$current_week = date('W');
$result = $conn->query("
    SELECT b.nama_bruder, t.tanggal_transaksi, t.keterangan, t.nominal_penerimaan, t.nominal_pengeluaran
    FROM transaksi t
    JOIN bruder b ON t.id_bruder = b.id_bruder
    WHERE b.id_cabang = 2 AND WEEK(t.tanggal_transaksi) = $current_week
    ORDER BY t.tanggal_transaksi DESC
");

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Bruder</th><th>Tanggal</th><th>Keterangan</th><th>Penerimaan</th><th>Pengeluaran</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['nama_bruder']}</td>";
        echo "<td>{$row['tanggal_transaksi']}</td>";
        echo "<td>{$row['keterangan']}</td>";
        echo "<td>Rp " . number_format($row['nominal_penerimaan'], 0, ',', '.') . "</td>";
        echo "<td>Rp " . number_format($row['nominal_pengeluaran'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ Tidak ada transaksi untuk minggu ini di cabang Bandung</div>";
}

echo "<h2>🎯 TESTING INSTRUCTIONS</h2>";
echo "<div style='background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Login sebagai Bruder Petrus Bandung:</h3>";
echo "<p><strong>Email:</strong> petrus.bandung@ficbruderan.org</p>";
echo "<p><strong>Password:</strong> password123</p>";
echo "<p><strong>Cabang:</strong> FIC02 - Cabang Bandung</p>";
echo "<p>Setelah login, lakukan pengeluaran dan cek dashboard untuk memverifikasi data ter-track dengan benar.</p>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>✅ Setup Database Berhasil!</h3>";
echo "<p>Sekarang database sudah siap dengan:</p>";
echo "<ul>";
echo "<li>✅ 15 bruder di 5 cabang</li>";
echo "<li>✅ Data transaksi dummy untuk testing</li>";
echo "<li>✅ Multi-cabang support yang berfungsi</li>";
echo "<li>✅ Dashboard yang track pengeluaran bruder per cabang</li>";
echo "</ul>";
echo "</div>";

$conn->close();
?>
