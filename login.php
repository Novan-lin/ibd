<?php
// =====================================================================
//            HALAMAN LOGIN APLIKASI FIC BRUDERAN (VERSI AWAL)
// =====================================================================
// CATATAN PENTING:
// Versi ini menggunakan database di mana password disimpan sebagai
// teks biasa. Ini SANGAT TIDAK AMAN dan hanya untuk tujuan awal.
// Sangat disarankan untuk segera beralih ke versi aman.
// =====================================================================

// --- 1. Konfigurasi Database ---
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'db_fic_bruderan';

// --- 2. Inisialisasi Sesi & Koneksi ---
session_start();
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// --- 3. Logika Proses Login ---
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ''; // 'bendahara' atau 'sekretariat'

    if (!empty($username) && !empty($password) && !empty($role)) {
        // PERINGATAN: Query ini rentan SQL Injection karena tidak menggunakan prepared statements.
        $sql = "SELECT * FROM login WHERE username = '$username' AND password = '$password'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            // Login berhasil
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;

            // Arahkan ke halaman dashboard (buat file dashboard.php nanti)
            header("Location: dashboard.php");
            exit;
        } else {
            // Login gagal
            $error_message = 'Username atau Password salah!';
        }
    } else {
        $error_message = 'Semua field harus diisi!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi FIC Bruderan</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Anda bisa menambahkan custom style di sini jika perlu */
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <!-- Load Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#003366] min-h-screen flex items-center justify-center p-4">

    <main class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="p-8 md:p-10">
            <div class="flex flex-col items-center text-center">
                <!-- Ganti 'logo.png' dengan path logo Anda -->
                <img src="https://placehold.co/120x120/003366/FFFFFF?text=FIC" alt="Logo FIC" class="w-24 h-24 md:w-28 md:h-28 mb-6">
            </div>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-5">
                <!-- Input Username (di desain tertulis Email, namun kita gunakan username) -->
                <div>
                    <label for="username" class="sr-only">Username</label>
                    <input type="text" name="username" id="username" placeholder="Username" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#003366] transition duration-200">
                </div>

                <!-- Input Password -->
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input type="password" name="password" id="password" placeholder="Password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#003366] transition duration-200">
                </div>

                <!-- Pesan Error -->
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                        <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Tombol Login -->
                <div class="space-y-3 pt-2">
                     <button type="submit" name="role" value="bendahara"
                            class="w-full bg-[#003366] text-white font-bold py-3 px-4 rounded-lg hover:bg-[#002244] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#003366] transition duration-300 ease-in-out">
                        Login Bendahara
                    </button>
                    <button type="submit" name="role" value="sekretariat"
                            class="w-full bg-[#003366] text-white font-bold py-3 px-4 rounded-lg hover:bg-[#002244] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#003366] transition duration-300 ease-in-out">
                        Login Sekretariat
                    </button>
                </div>
            </form>
        </div>
    </main>

</body>
</html>

