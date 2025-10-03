<?php
// =====================================================================
//            DEBUG DASHBOARD - FIC BRUDERAN
// =====================================================================
// Script untuk debug mengapa dashboard tidak menampilkan data pengeluaran bruder
// =====================================================================

$conn = new mysqli('localhost', 'root', '', 'db_fic_bruderan');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

echo "<h1>🔍 DEBUG DASHBOARD - FIC BRUDERAN</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    .debug-section { background: #f8f9fa; margin: 20px 0; padding: 15px; border-radius: 5px; }
    .debug-title { color: #495057; font-weight: bold; margin-bottom: 10px; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .highlight { background-color: #fff3cd; padding: 5px; border-radius: 3px; }
</style>";

// Debug 1: Cek Session dan User Info
echo "<div class='debug-section'>";
echo "<div class='debug-title'>🔐 DEBUG 1: Session dan User Info</div>";
session_start();
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Logged In:</strong> " . (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true ? '✅ Ya' : '❌ Tidak') . "</p>";
echo "<p><strong>Username:</strong> " . ($_SESSION['username'] ?? 'N/A') . "</p>";
echo "<p><strong>Role:</strong> " . ($_SESSION['role'] ?? 'N/A') . "</p>";
echo "<p><strong>ID Cabang:</strong> " . ($_SESSION['id_cabang'] ?? 'N/A') . "</p>";
echo "<p><strong>Nama Cabang:</strong> " . ($_SESSION['nama_cabang'] ?? 'N/A') . "</p>";
echo "<p><strong>Kode Cabang:</strong> " . ($_SESSION['kode_cabang'] ?? 'N/A') . "</p>";
echo "</div>";

// Debug 2: Cek Total Bruder per Cabang
echo "<div class='debug-section'>";
echo "<div class='debug-title'>👥 DEBUG 2: Total Bruder per Cabang</div>";
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
        $highlight = ($row['kode_cabang'] === 'FIC02') ? 'class="highlight"' : '';
        echo "<tr $highlight>";
        echo "<td>" . $row['nama_cabang'] . "</td>";
        echo "<td>" . $row['kode_cabang'] . "</td>";
        echo "<td>" . $row['jumlah_bruder'] . "</td>";
        echo "<td>" . $row['daftar_bruder'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// Debug 3: Cek Transaksi Minggu Ini per Cabang
echo "<div class='debug-section'>";
echo "<div class='debug-title'>💰 DEBUG 3: Transaksi Minggu Ini per Cabang</div>";
$current_week = date('W');
$current_year = date('Y');

echo "<p><strong>Minggu ke:</strong> $current_week</p>";
echo "<p><strong>Tahun:</strong> $current_year</p>";

$result = $conn->query("
    SELECT
        c.kode_cabang,
        c.nama_cabang,
        COUNT(t.id_transaksi) as jumlah_transaksi,
        COALESCE(SUM(t.nominal_penerimaan), 0) as total_penerimaan,
        COALESCE(SUM(t.nominal_pengeluaran), 0) as total_pengeluaran
    FROM cabang c
    LEFT JOIN transaksi t ON c.id_cabang = t.id_cabang
    WHERE WEEK(t.tanggal_transaksi) = $current_week
    AND YEAR(t.tanggal_transaksi) = $current_year
    GROUP BY c.id_cabang, c.kode_cabang, c.nama_cabang
    ORDER BY c.id_cabang
");

if ($result) {
    echo "<table>";
    echo "<tr><th>Cabang</th><th>Kode</th><th>Jumlah Transaksi</th><th>Total Penerimaan</th><th>Total Pengeluaran</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['kode_cabang'] === 'FIC02') ? 'class="highlight"' : '';
        echo "<tr $highlight>";
        echo "<td>" . $row['nama_cabang'] . "</td>";
        echo "<td>" . $row['kode_cabang'] . "</td>";
        echo "<td>" . $row['jumlah_transaksi'] . "</td>";
        echo "<td>Rp " . number_format($row['total_penerimaan'], 0, ',', '.') . "</td>";
        echo "<td>Rp " . number_format($row['total_pengeluaran'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// Debug 4: Cek Transaksi dengan JOIN Bruder (seperti di dashboard)
echo "<div class='debug-section'>";
echo "<div class='debug-title'>🔗 DEBUG 4: Transaksi dengan JOIN Bruder (Query Dashboard)</div>";

if (isset($_SESSION['id_cabang'])) {
    $current_cabang = $_SESSION['id_cabang'];

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
        WHERE b.id_cabang = $current_cabang
        AND WEEK(t.tanggal_transaksi) = $current_week
        AND YEAR(t.tanggal_transaksi) = $current_year
        ORDER BY t.tanggal_transaksi DESC
    ");

    if ($result) {
        echo "<p><strong>Query Dashboard Result:</strong> " . $result->num_rows . " transaksi ditemukan</p>";
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
    } else {
        echo "<div class='error'>❌ Query gagal dijalankan</div>";
    }
} else {
    echo "<div class='warning'>⚠️ Session id_cabang tidak ditemukan</div>";
}
echo "</div>";

// Debug 5: Cek Data Transaksi Manual untuk Bandung
echo "<div class='debug-section'>";
echo "<div class='debug-title'>🎯 DEBUG 5: Data Transaksi Manual untuk Bandung</div>";

$result = $conn->query("
    SELECT
        b.nama_bruder,
        b.id_cabang,
        c.nama_cabang,
        t.tanggal_transaksi,
        t.keterangan,
        t.nominal_penerimaan,
        t.nominal_pengeluaran
    FROM transaksi t
    JOIN bruder b ON t.id_bruder = b.id_bruder
    JOIN cabang c ON b.id_cabang = c.id_cabang
    WHERE b.id_cabang = 2
    ORDER BY t.tanggal_transaksi DESC
    LIMIT 10
");

if ($result) {
    echo "<p><strong>Total transaksi di Bandung:</strong> " . $result->num_rows . " transaksi</p>";
    echo "<table>";
    echo "<tr><th>Bruder</th><th>Cabang</th><th>Tanggal</th><th>Keterangan</th><th>Penerimaan</th><th>Pengeluaran</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['nama_bruder'] . "</td>";
        echo "<td>" . $row['nama_cabang'] . "</td>";
        echo "<td>" . $row['tanggal_transaksi'] . "</td>";
        echo "<td>" . $row['keterangan'] . "</td>";
        echo "<td>Rp " . number_format($row['nominal_penerimaan'], 0, ',', '.') . "</td>";
        echo "<td>Rp " . number_format($row['nominal_pengeluaran'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ Query gagal dijalankan</div>";
}
echo "</div>";

// Debug 6: Test Query yang Sama seperti di Dashboard
echo "<div class='debug-section'>";
echo "<div class='debug-title'>📊 DEBUG 6: Test Query Dashboard yang Sebenarnya</div>";

if (isset($_SESSION['id_cabang'])) {
    $current_cabang = $_SESSION['id_cabang'];

    // Query yang sama persis seperti di dashboard.php
    $weekly_data_query = "
        SELECT
            COALESCE(SUM(t.nominal_penerimaan), 0) as pemasukan,
            COALESCE(SUM(t.nominal_pengeluaran), 0) as pengeluaran,
            COUNT(*) as jumlah_transaksi
        FROM transaksi t
        JOIN bruder b ON t.id_bruder = b.id_bruder
        WHERE b.id_cabang = $current_cabang
        AND WEEK(t.tanggal_transaksi) = $current_week
        AND YEAR(t.tanggal_transaksi) = $current_year
    ";

    echo "<p><strong>Query yang digunakan:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . $weekly_data_query . "</pre>";

    $result = $conn->query($weekly_data_query);
    if ($result) {
        $data = $result->fetch_assoc();
        echo "<div class='success'>✅ Query berhasil dijalankan!</div>";
        echo "<p><strong>Hasil Query:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Pemasukan:</strong> Rp " . number_format($data['pemasukan'], 0, ',', '.') . "</li>";
        echo "<li><strong>Pengeluaran:</strong> Rp " . number_format($data['pengeluaran'], 0, ',', '.') . "</li>";
        echo "<li><strong>Jumlah Transaksi:</strong> " . $data['jumlah_transaksi'] . "</li>";
        echo "</ul>";

        if ($data['jumlah_transaksi'] == 0) {
            echo "<div class='warning'>⚠️ Tidak ada transaksi ditemukan untuk minggu ini!</div>";
            echo "<p><strong>Kemungkinan penyebab:</strong></p>";
            echo "<ul>";
            echo "<li>Data transaksi belum di-insert</li>";
            echo "<li>Tanggal transaksi bukan minggu ini</li>";
            echo "<li>Session cabang tidak sesuai</li>";
            echo "</ul>";
        }
    } else {
        echo "<div class='error'>❌ Query gagal dijalankan</div>";
        echo "<p><strong>Error:</strong> " . $conn->error . "</p>";
    }
} else {
    echo "<div class='error'>❌ Session id_cabang tidak ditemukan</div>";
}
echo "</div>";

// Debug 7: Insert Test Data untuk Testing
echo "<div class='debug-section'>";
echo "<div class='debug-title'>🧪 DEBUG 7: Insert Test Data untuk Testing</div>";

if (isset($_POST['insert_test_data'])) {
    // Insert test data untuk bruder Petrus Bandung
    $test_data = [
        [6, 2, date('Y-m-d'), 'Test pengeluaran bruder Petrus', 'Kas Harian', 0.00, 100000.00, 2],
        [6, 3, date('Y-m-d'), 'Test penerimaan bruder Petrus', 'Kas Harian', 200000.00, 0.00, 2]
    ];

    foreach ($test_data as $data) {
        $stmt = $conn->prepare("INSERT INTO transaksi (id_bruder, id_perkiraan, tanggal_transaksi, keterangan, sumber_dana, nominal_penerimaan, nominal_pengeluaran, id_cabang) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssdii", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7]);
        $stmt->execute();
    }

    echo "<div class='success'>✅ Test data berhasil di-insert!</div>";
    echo "<p><a href='debug_dashboard.php' class='button' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 10px 0;'>🔄 Refresh Halaman</a></p>";
}

echo "<form method='POST'>";
echo "<p><button type='submit' name='insert_test_data' class='button' style='background: #007bff;'>📝 Insert Test Data untuk Bruder Petrus</button></p>";
echo "</form>";
echo "</div>";

// Debug 8: Recommendations
echo "<div class='debug-section' style='background: #e8f4f8;'>";
echo "<div class='debug-title'>💡 DEBUG 8: Recommendations</div>";
echo "<h3>Langkah-langkah untuk memperbaiki masalah:</h3>";
echo "<ol>";
echo "<li><strong>Setup Database:</strong> Jalankan <a href='setup_database.php' target='_blank'>setup_database.php</a></li>";
echo "<li><strong>Login sebagai Bruder Petrus:</strong> Email: petrus.bandung@ficbruderan.org, Password: password123</li>";
echo "<li><strong>Lakukan pengeluaran:</strong> Di halaman kas_pengeluaran_bruder.php</li>";
echo "<li><strong>Cek Dashboard:</strong> Data akan muncul di section 'Kegiatan Minggu Ini'</li>";
echo "<li><strong>Debug jika masih error:</strong> Gunakan script ini untuk troubleshooting</li>";
echo "</ol>";
echo "</div>";

$conn->close();
?>
