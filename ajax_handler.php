<?php
// =====================================================================
//            AJAX HANDLER - CENTRAL POINT FOR ALL AJAX REQUESTS
// =====================================================================
// This file handles all AJAX requests from the frontend
// Returns JSON responses for better client-side handling
// =====================================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Start session for authentication
session_start();

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'db_fic_bruderan';

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");

    // Get the action from POST or GET request
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($action) {
        case 'login':
            $response = handleLogin($conn);
            break;
        case 'logout':
            $response = handleLogout();
            break;
        case 'get_bruder_list':
            $response = getBruderList($conn);
            break;
        case 'add_transaction':
        case 'tambah_pengeluaran':
        case 'tambah_penerimaan':
            $response = addTransaction($conn);
            break;
        case 'get_transactions':
            $response = getTransactions($conn);
            break;
        case 'search_bruder':
            $response = searchBruder($conn);
            break;
        case 'edit_transaction':
            $response = editTransaction($conn);
            break;
        case 'delete_transaction':
            $response = deleteTransaction($conn);
            break;
        case 'get_transaction_details':
            $response = getTransactionDetails($conn);
            break;
        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
    }

} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}

// Return JSON response
echo json_encode($response);
exit;

// =====================================================================
// LOGIN HANDLER
// =====================================================================
function handleLogin($conn) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($username) || empty($password) || empty($role)) {
        return ['success' => false, 'message' => 'Semua field harus diisi!'];
    }

    $redirect_url = 'dashboard.php'; // Default redirect

    if ($role === 'bruder') {
        // Login untuk bruder menggunakan tabel bruder
        $stmt = $conn->prepare("SELECT b.*, c.nama_cabang, c.kode_cabang FROM bruder b JOIN cabang c ON b.id_cabang = c.id_cabang WHERE b.email = ? AND b.password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['id_bruder'] = $user['id_bruder'];
            $_SESSION['nama_bruder'] = $user['nama_bruder'];
            $_SESSION['id_cabang'] = $user['id_cabang'];
            $_SESSION['nama_cabang'] = $user['nama_cabang'];
            $_SESSION['kode_cabang'] = $user['kode_cabang'];

            $redirect_url = 'dashboard_bruder.php';
            $cabang_name = $user['nama_cabang'];

            return [
                'success' => true,
                'message' => 'Login berhasil!',
                'cabang' => $cabang_name,
                'redirect' => $redirect_url
            ];
        } else {
            return ['success' => false, 'message' => 'Username atau Password salah!'];
        }
    } else {
        // Login untuk bendahara/admin/sekretariat menggunakan tabel login
        $stmt = $conn->prepare("SELECT l.*, c.nama_cabang, c.kode_cabang FROM login l JOIN cabang c ON l.id_cabang = c.id_cabang WHERE l.username = ? AND l.password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['id_cabang'] = $user['id_cabang'];
            $_SESSION['nama_cabang'] = $user['nama_cabang'];
            $_SESSION['kode_cabang'] = $user['kode_cabang'];
            $_SESSION['level'] = $user['level'];

            return [
                'success' => true,
                'message' => 'Login berhasil!',
                'cabang' => $user['nama_cabang'],
                'redirect' => $redirect_url
            ];
        } else {
            return ['success' => false, 'message' => 'Username atau Password salah!'];
        }
    }
}

// =====================================================================
// LOGOUT HANDLER
// =====================================================================
function handleLogout() {
    $_SESSION = array();
    session_destroy();
    return ['success' => true, 'message' => 'Logout berhasil!'];
}

// =====================================================================
// GET BRUDER LIST
// =====================================================================
function getBruderList($conn) {
    if (!isset($_SESSION['id_cabang'])) {
        return ['success' => false, 'message' => 'Unauthorized access'];
    }

    $user_cabang_id = $_SESSION['id_cabang'];

    $stmt = $conn->prepare("SELECT id_bruder, nama_bruder FROM bruder WHERE id_cabang = ? ORDER BY nama_bruder");
    $stmt->bind_param("i", $user_cabang_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $bruder_list = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bruder_list[] = $row;
        }
    }

    return ['success' => true, 'data' => $bruder_list];
}

// =====================================================================
// ADD TRANSACTION
// =====================================================================
function addTransaction($conn) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_cabang'])) {
        return ['success' => false, 'message' => 'Unauthorized access'];
    }

    $bruder_id = (int)($_POST['bruder_id'] ?? 0);
    $id_perkiraan = (int)($_POST['id_perkiraan'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';
    $tipe = $_POST['tipe_transaksi'] ?? '';
    $nominal = (float)($_POST['nominal'] ?? 0);
    $sumber_dana = $_POST['sumber_dana'] ?? 'Kas Harian';
    $user_cabang_id = $_SESSION['id_cabang'];

    if ($bruder_id <= 0 || $id_perkiraan <= 0 || empty($tanggal) || empty($keterangan) || empty($tipe) || $nominal <= 0) {
        return ['success' => false, 'message' => 'Semua field harus diisi dengan benar!'];
    }

    // Verify bruder belongs to user's branch
    $stmt_check = $conn->prepare("SELECT id_cabang FROM bruder WHERE id_bruder = ?");
    $stmt_check->bind_param("i", $bruder_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
        return ['success' => false, 'message' => 'Bruder tidak ditemukan!'];
    }

    $bruder_data = $result_check->fetch_assoc();
    if ($bruder_data['id_cabang'] !== $user_cabang_id) {
        return ['success' => false, 'message' => 'Bruder tidak terdaftar di cabang Anda!'];
    }

    // Verify kode perkiraan exists and is accessible
    $stmt_kode = $conn->prepare("SELECT id_cabang FROM kode_perkiraan WHERE id_perkiraan = ?");
    $stmt_kode->bind_param("i", $id_perkiraan);
    $stmt_kode->execute();
    $result_kode = $stmt_kode->get_result();

    if ($result_kode->num_rows === 0) {
        return ['success' => false, 'message' => 'Kode perkiraan tidak ditemukan!'];
    }

    $kode_data = $result_kode->fetch_assoc();

    // Allow kode perkiraan from user's branch or from main branch (ID 1) as fallback
    if ($kode_data['id_cabang'] !== $user_cabang_id && $kode_data['id_cabang'] !== 1) {
        return ['success' => false, 'message' => 'Kode perkiraan tidak tersedia di cabang Anda!'];
    }

    // Prepare transaction data
    $nominal_penerimaan = ($tipe === 'Penerimaan') ? $nominal : 0;
    $nominal_pengeluaran = ($tipe === 'Pengeluaran') ? $nominal : 0;

    // Use the new multi-cabang stored procedure
    $stmt_proc = $conn->prepare("CALL catat_transaksi_keuangan_multi_cabang(?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_proc->bind_param("iissssdi", $bruder_id, $id_perkiraan, $tanggal, $keterangan, $sumber_dana, $tipe, $nominal, $user_cabang_id);

    if ($stmt_proc->execute()) {
        return ['success' => true, 'message' => 'Transaksi berhasil dicatat!'];
    } else {
        return ['success' => false, 'message' => 'Gagal mencatat transaksi: ' . $conn->error];
    }
}

// =====================================================================
// GET TRANSACTIONS
// =====================================================================
function getTransactions($conn) {
    if (!isset($_SESSION['id_cabang'])) {
        return ['success' => false, 'message' => 'Unauthorized access'];
    }

    $bruder_id = (int)($_GET['bruder_id'] ?? 0);
    $sumber_dana = $_GET['sumber_dana'] ?? 'Kas Harian';
    $user_cabang_id = $_SESSION['id_cabang'];

    if ($bruder_id <= 0) {
        return ['success' => false, 'message' => 'Invalid bruder ID'];
    }

    // Verify bruder belongs to user's branch
    $stmt_check = $conn->prepare("SELECT id_cabang FROM bruder WHERE id_bruder = ?");
    $stmt_check->bind_param("i", $bruder_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
        return ['success' => false, 'message' => 'Bruder tidak ditemukan!'];
    }

    $bruder_data = $result_check->fetch_assoc();
    if ($bruder_data['id_cabang'] !== $user_cabang_id) {
        return ['success' => false, 'message' => 'Akses ditolak! Bruder dari cabang berbeda.'];
    }

    $stmt = $conn->prepare(
        "SELECT t.id_transaksi, t.tanggal_transaksi, kp.pos, kp.kode_perkiraan, kp.nama_akun,
                t.keterangan, t.reff, t.nominal_penerimaan, t.nominal_pengeluaran, t.id_cabang
         FROM transaksi t
         JOIN kode_perkiraan kp ON t.id_perkiraan = kp.id_perkiraan
         WHERE t.id_bruder = ? AND t.sumber_dana = ? AND t.id_cabang = ?
         ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC"
    );

    $stmt->bind_param("isi", $bruder_id, $sumber_dana, $user_cabang_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $transactions = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }

    return ['success' => true, 'data' => $transactions];
}

// =====================================================================
// SEARCH BRUDER
// =====================================================================
function searchBruder($conn) {
    if (!isset($_SESSION['id_cabang'])) {
        return ['success' => false, 'message' => 'Unauthorized access'];
    }

    $search_term = $_GET['q'] ?? '';
    $user_cabang_id = $_SESSION['id_cabang'];

    if (strlen($search_term) < 2) {
        return ['success' => true, 'data' => []];
    }

    $search_pattern = "%$search_term%";
    $stmt = $conn->prepare("SELECT id_bruder, nama_bruder FROM bruder WHERE nama_bruder LIKE ? AND id_cabang = ? ORDER BY nama_bruder LIMIT 10");
    $stmt->bind_param("si", $search_pattern, $user_cabang_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $bruder_list = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bruder_list[] = $row;
        }
    }

    return ['success' => true, 'data' => $bruder_list];
}

// =====================================================================
// EDIT TRANSACTION
// =====================================================================
function editTransaction($conn) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        return ['success' => false, 'message' => 'Unauthorized access'];
    }

    $transaction_id = (int)($_POST['transaction_id'] ?? 0);
    $bruder_id = (int)($_POST['bruder_id'] ?? 0);
    $id_perkiraan = (int)($_POST['id_perkiraan'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';
    $tipe = $_POST['tipe_transaksi'] ?? '';
    $nominal = (float)($_POST['nominal'] ?? 0);

    if ($transaction_id <= 0 || $bruder_id <= 0 || $id_perkiraan <= 0 || empty($tanggal) || empty($keterangan) || empty($tipe) || $nominal <= 0) {
        return ['success' => false, 'message' => 'Semua field harus diisi dengan benar!'];
    }

    // Prepare transaction data
    $nominal_penerimaan = ($tipe === 'Penerimaan') ? $nominal : 0;
    $nominal_pengeluaran = ($tipe === 'Pengeluaran') ? $nominal : 0;

    // Update transaction using prepared statement
    $stmt = $conn->prepare(
        "UPDATE transaksi SET
            id_perkiraan = ?,
            tanggal_transaksi = ?,
            keterangan = ?,
            nominal_penerimaan = ?,
            nominal_pengeluaran = ?
         WHERE id_transaksi = ? AND id_bruder = ?"
    );

    $stmt->bind_param("issddii", $id_perkiraan, $tanggal, $keterangan, $nominal_penerimaan, $nominal_pengeluaran, $transaction_id, $bruder_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Transaksi berhasil diupdate!'];
        } else {
            return ['success' => false, 'message' => 'Transaksi tidak ditemukan atau tidak ada perubahan!'];
        }
    } else {
        return ['success' => false, 'message' => 'Gagal mengupdate transaksi: ' . $conn->error];
    }
}

// =====================================================================
// DELETE TRANSACTION
// =====================================================================
function deleteTransaction($conn) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        return ['success' => false, 'message' => 'Unauthorized access'];
    }

    $transaction_id = (int)($_POST['transaction_id'] ?? 0);
    $bruder_id = (int)($_POST['bruder_id'] ?? 0);

    if ($transaction_id <= 0 || $bruder_id <= 0) {
        return ['success' => false, 'message' => 'Invalid transaction or bruder ID!'];
    }

    // Delete transaction using prepared statement
    $stmt = $conn->prepare("DELETE FROM transaksi WHERE id_transaksi = ? AND id_bruder = ?");
    $stmt->bind_param("ii", $transaction_id, $bruder_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Transaksi berhasil dihapus!'];
        } else {
            return ['success' => false, 'message' => 'Transaksi tidak ditemukan!'];
        }
    } else {
        return ['success' => false, 'message' => 'Gagal menghapus transaksi: ' . $conn->error];
    }
}

// =====================================================================
// GET TRANSACTION DETAILS
// =====================================================================
function getTransactionDetails($conn) {
    $transaction_id = (int)($_GET['transaction_id'] ?? 0);
    $bruder_id = (int)($_GET['bruder_id'] ?? 0);

    if ($transaction_id <= 0 || $bruder_id <= 0) {
        return ['success' => false, 'message' => 'Invalid transaction or bruder ID'];
    }

    $stmt = $conn->prepare(
        "SELECT t.*, kp.kode_perkiraan, kp.nama_akun, kp.pos
         FROM transaksi t
         JOIN kode_perkiraan kp ON t.id_perkiraan = kp.id_perkiraan
         WHERE t.id_transaksi = ? AND t.id_bruder = ?"
    );

    $stmt->bind_param("ii", $transaction_id, $bruder_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $transaction = $result->fetch_assoc();
        return ['success' => true, 'data' => $transaction];
    } else {
        return ['success' => false, 'message' => 'Transaction not found'];
    }
}
?>
