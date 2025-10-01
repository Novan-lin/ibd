<?php
// =====================================================================
//            FIX KODE PERKIRAAN - COPY DARI CABANG UTAMA KE CABANG LAIN
// =====================================================================

$conn = new mysqli('localhost', 'root', '', 'db_fic_bruderan');
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

echo "<h1>Fix Kode Perkiraan untuk Semua Cabang</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; }</style>";

// Fungsi untuk copy kode perkiraan dari cabang utama ke cabang lain
function copyKodePerkiraan($conn, $dari_cabang, $ke_cabang) {
    echo "<h2>Copy Kode Perkiraan dari Cabang $dari_cabang ke Cabang $ke_cabang</h2>";

    // Cek berapa kode perkiraan yang sudah ada di cabang tujuan
    $cek_existing = $conn->query("SELECT COUNT(*) as jumlah FROM kode_perkiraan WHERE id_cabang = $ke_cabang");
    $existing = $cek_existing->fetch_assoc();

    if ($existing['jumlah'] > 0) {
        echo "<p class='error'>Cabang $ke_cabang sudah memiliki {$existing['jumlah']} kode perkiraan. Skip copy.</p>";
        return false;
    }

    // Copy kode perkiraan dari cabang utama
    $sql_copy = "INSERT INTO kode_perkiraan (kode_perkiraan, nama_akun, pos, tipe_akun, id_cabang)
                 SELECT kode_perkiraan, nama_akun, pos, tipe_akun, $ke_cabang
                 FROM kode_perkiraan
                 WHERE id_cabang = $dari_cabang";

    if ($conn->query($sql_copy) === TRUE) {
        $jumlah_copied = $conn->affected_rows;
        echo "<p class='success'>✅ Berhasil copy $jumlah_copied kode perkiraan ke Cabang $ke_cabang</p>";
        return true;
    } else {
        echo "<p class='error'>❌ Gagal copy kode perkiraan: " . $conn->error . "</p>";
        return false;
    }
}

// Copy kode perkiraan untuk semua cabang (2-5) dari cabang utama (1)
$target_cabangs = [2, 3, 4, 5];
$success_count = 0;

foreach ($target_cabangs as $cabang) {
    if (copyKodePerkiraan($conn, 1, $cabang)) {
        $success_count++;
    }
}

echo "<hr>";
echo "<h2>Ringkasan:</h2>";
echo "<p>Total cabang yang berhasil difix: $success_count dari " . count($target_cabangs) . "</p>";

// Tampilkan jumlah kode perkiraan per cabang setelah fix
echo "<h2>Status Kode Perkiraan per Cabang:</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f2f2f2;'><th>Cabang</th><th>Total Kode Perkiraan</th><th>Pengeluaran</th><th>Penerimaan</th></tr>";

for ($cabang = 1; $cabang <= 5; $cabang++) {
    $total = $conn->query("SELECT COUNT(*) as jumlah FROM kode_perkiraan WHERE id_cabang = $cabang")->fetch_assoc()['jumlah'];
    $pengeluaran = $conn->query("SELECT COUNT(*) as jumlah FROM kode_perkiraan WHERE id_cabang = $cabang AND tipe_akun = 'Pengeluaran'")->fetch_assoc()['jumlah'];
    $penerimaan = $conn->query("SELECT COUNT(*) as jumlah FROM kode_perkiraan WHERE id_cabang = $cabang AND tipe_akun = 'Penerimaan'")->fetch_assoc()['jumlah'];

    echo "<tr>";
    echo "<td>Cabang $cabang</td>";
    echo "<td>$total</td>";
    echo "<td>$pengeluaran</td>";
    echo "<td>$penerimaan</td>";
    echo "</tr>";
}

echo "</table>";

$conn->close();

echo "<hr>";
echo "<h2>Testing:</h2>";
echo "<p>🔗 <a href='test_db.php' target='_blank'>Lihat Test Database</a></p>";
echo "<p>🔗 <a href='kas_pengeluaran_bruder.php' target='_blank'>Test Kas Pengeluaran Bruder</a></p>";
echo "<p>🔗 <a href='kas_penerimaan_bruder.php' target='_blank'>Test Kas Penerimaan Bruder</a></p>";
?>
