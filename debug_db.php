<?php
// =====================================================================
//            DEBUG DATABASE - CEK KONDISI SAAT INI
// =====================================================================

$conn = new mysqli('localhost', 'root', '', 'db_fic_bruderan');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

echo "<h1>Debug Database - Status Saat Ini</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } table { border-collapse: collapse; width: 100%; margin: 10px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; } .highlight { background-color: #fff3cd; }</style>";

// Cek kode perkiraan per cabang
echo "<h2>📊 Status Kode Perkiraan per Cabang:</h2>";
echo "<table>";
echo "<tr><th>Cabang</th><th>Total Kode</th><th>Pengeluaran</th><th>Penerimaan</th><th>Status</th></tr>";

for ($cabang = 1; $cabang <= 5; $cabang++) {
    $total = $conn->query("SELECT COUNT(*) as jumlah FROM kode_perkiraan WHERE id_cabang = $cabang")->fetch_assoc()['jumlah'];
    $pengeluaran = $conn->query("SELECT COUNT(*) as jumlah FROM kode_perkiraan WHERE id_cabang = $cabang AND tipe_akun = 'Pengeluaran'")->fetch_assoc()['jumlah'];
    $penerimaan = $conn->query("SELECT COUNT(*) as jumlah FROM kode_perkiraan WHERE id_cabang = $cabang AND tipe_akun = 'Penerimaan'")->fetch_assoc()['jumlah'];

    $status = ($pengeluaran > 0) ? '✅ OK' : '❌ Kosong';
    $class = ($pengeluaran > 0) ? '' : 'class="highlight"';

    echo "<tr $class>";
    echo "<td>Cabang $cabang</td>";
    echo "<td>$total</td>";
    echo "<td>$pengeluaran</td>";
    echo "<td>$penerimaan</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Cek semua transaksi terbaru
echo "<h2>📋 Transaksi Terbaru (Limit 10):</h2>";
$result_all = $conn->query("SELECT t.id_transaksi, t.id_bruder, t.tanggal_transaksi, t.keterangan, t.nominal_penerimaan, t.nominal_pengeluaran, t.sumber_dana, b.nama_bruder FROM transaksi t JOIN bruder b ON t.id_bruder = b.id_bruder ORDER BY t.id_transaksi DESC LIMIT 10");

if ($result_all && $result_all->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Nama Bruder</th><th>Tanggal</th><th>Keterangan</th><th>Penerimaan</th><th>Pengeluaran</th><th>Sumber</th></tr>";
    while ($row = $result_all->fetch_assoc()) {
        $class = ($row['nominal_pengeluaran'] > 0) ? 'class="highlight"' : '';
        echo "<tr $class>";
        echo "<td>" . $row['id_transaksi'] . "</td>";
        echo "<td>" . $row['nama_bruder'] . "</td>";
        echo "<td>" . $row['tanggal_transaksi'] . "</td>";
        echo "<td>" . $row['keterangan'] . "</td>";
        echo "<td>" . number_format($row['nominal_penerimaan'], 0, ',', '.') . "</td>";
        echo "<td>" . number_format($row['nominal_pengeluaran'], 0, ',', '.') . "</td>";
        echo "<td>" . $row['sumber_dana'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Tidak ada transaksi ditemukan.</p>";
}

$conn->close();

echo "<hr>";
echo "<h2>🔧 Tools Testing:</h2>";
echo "<p>🔗 <a href='fix_kode_perkiraan.php' target='_blank'>Fix Kode Perkiraan (Jika masih kosong)</a></p>";
echo "<p>🔗 <a href='kas_pengeluaran_bruder.php' target='_blank'>Test Kas Pengeluaran Bruder</a></p>";
echo "<p>🔗 <a href='kas_penerimaan_bruder.php' target='_blank'>Test Kas Penerimaan Bruder</a></p>";
?>
