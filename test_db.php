<?php
// =====================================================================
//            TEST DATABASE - CEK DATA PENGELUARAN BRUDER
// =====================================================================

session_start();

$conn = new mysqli('localhost', 'root', '', 'db_fic_bruderan');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

echo "<h1>Test Database - Cek Data Pengeluaran Bruder</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>";

// Cek session jika ada
if (isset($_SESSION['id_bruder'])) {
    echo "<h2>Session Info:</h2>";
    echo "<p>ID Bruder: " . $_SESSION['id_bruder'] . "</p>";
    echo "<p>Nama Bruder: " . $_SESSION['nama_bruder'] . "</p>";
    echo "<p>ID Cabang: " . $_SESSION['id_cabang'] . "</p>";
    echo "<p>Nama Cabang: " . $_SESSION['nama_cabang'] . "</p>";
    echo "<p>Kode Cabang: " . $_SESSION['kode_cabang'] . "</p>";
    echo "<hr>";
}

// Cek semua transaksi di tabel transaksi
echo "<h2>Semua Transaksi (Limit 10):</h2>";
$result_all = $conn->query("SELECT t.id_transaksi, t.id_bruder, t.tanggal_transaksi, t.keterangan, t.nominal_penerimaan, t.nominal_pengeluaran, t.sumber_dana, b.nama_bruder FROM transaksi t JOIN bruder b ON t.id_bruder = b.id_bruder ORDER BY t.id_transaksi DESC LIMIT 10");

if ($result_all && $result_all->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID Transaksi</th><th>ID Bruder</th><th>Nama Bruder</th><th>Tanggal</th><th>Keterangan</th><th>Penerimaan</th><th>Pengeluaran</th><th>Sumber Dana</th></tr>";
    while ($row = $result_all->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id_transaksi'] . "</td>";
        echo "<td>" . $row['id_bruder'] . "</td>";
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

echo "<hr>";

// Cek bruder yang sedang login (jika ada session)
if (isset($_SESSION['id_bruder'])) {
    $id_bruder = $_SESSION['id_bruder'];

    echo "<h2>Transaksi Bruder {$id_bruder}:</h2>";
    $result_bruder = $conn->query("SELECT t.id_transaksi, t.tanggal_transaksi, t.keterangan, t.nominal_penerimaan, t.nominal_pengeluaran, t.sumber_dana FROM transaksi t WHERE t.id_bruder = $id_bruder ORDER BY t.id_transaksi DESC");

    if ($result_bruder && $result_bruder->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID Transaksi</th><th>Tanggal</th><th>Keterangan</th><th>Penerimaan</th><th>Pengeluaran</th><th>Sumber Dana</th></tr>";
        while ($row = $result_bruder->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id_transaksi'] . "</td>";
            echo "<td>" . $row['tanggal_transaksi'] . "</td>";
            echo "<td>" . $row['keterangan'] . "</td>";
            echo "<td>" . number_format($row['nominal_penerimaan'], 0, ',', '.') . "</td>";
            echo "<td>" . number_format($row['nominal_pengeluaran'], 0, ',', '.') . "</td>";
            echo "<td>" . $row['sumber_dana'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Tidak ada transaksi untuk bruder ini.</p>";
    }
}

echo "<hr>";
echo "<h2>Cek Kode Perkiraan per Cabang:</h2>";

// Cek kode perkiraan untuk setiap cabang
for ($cabang = 1; $cabang <= 5; $cabang++) {
    $result_kode = $conn->query("SELECT COUNT(*) as jumlah FROM kode_perkiraan WHERE id_cabang = $cabang AND tipe_akun = 'Pengeluaran'");

    if ($result_kode) {
        $row = $result_kode->fetch_assoc();
        echo "<p>Cabang $cabang: " . $row['jumlah'] . " kode perkiraan pengeluaran</p>";
    }
}

$conn->close();
?>
