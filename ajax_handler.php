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
        case 'ajukan_pengeluaran':
            $response = ajukanPengeluaran($conn);
            break;
        case 'approve_pengeluaran':
            $response = approvePengeluaran($conn);
            break;
        case 'reject_pengeluaran':
            $response = rejectPengeluaran($conn);
            break;
        case 'get_pending_approvals':
            $response = getPendingApprovals($conn);
            break;
        case 'edit_bruder':
            $response = editBruder($conn);
            break;
        case 'delete_bruder':
            $response = deleteBruder($conn);
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

// =====================================================================
// AJUKAN PENGELUARAN (APPROVAL WORKFLOW)
// =====================================================================
function ajukanPengeluaran($conn) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'bruder') {
        return ['success' => false, 'message' => 'Akses ditolak. Hanya bruder yang bisa mengajukan.'];
    }

    $id_bruder = (int)($_POST['bruder_id'] ?? 0);
    $id_perkiraan = (int)($_POST['id_perkiraan'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? ''; // Tanggal dari form, tapi kita akan pakai CURRENT_TIMESTAMP
    $keterangan = $_POST['keterangan'] ?? '';
    $nominal = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal']);
    $id_cabang = $_SESSION['id_cabang'] ?? 0;

    if ($id_bruder <= 0 || $id_perkiraan <= 0 || empty($keterangan) || $nominal <= 0 || $id_cabang <= 0) {
        return ['success' => false, 'message' => 'Data tidak lengkap. Semua field wajib diisi.'];
    }

    // Handle file upload
    $foto_bukti_filename = null;
    if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/bruder_photos/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                return ['success' => false, 'message' => 'Gagal membuat direktori upload.'];
            }
        }

        $file_extension = strtolower(pathinfo($_FILES['foto_bukti']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_extension, $allowed_extensions)) {
            return ['success' => false, 'message' => 'Format file foto tidak didukung. Gunakan JPG, PNG, atau GIF.'];
        }

        if ($_FILES['foto_bukti']['size'] > 5 * 1024 * 1024) { // 5MB max
            return ['success' => false, 'message' => 'Ukuran file foto terlalu besar. Maksimal 5MB.'];
        }

        $foto_bukti_filename = uniqid('bukti_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $foto_bukti_filename;

        if (!move_uploaded_file($_FILES['foto_bukti']['tmp_name'], $upload_path)) {
            return ['success' => false, 'message' => 'Gagal mengupload foto bukti.'];
        }
    }

    // Insert into the new approval table
    $stmt = $conn->prepare(
        "INSERT INTO pengajuan_pengeluaran (id_bruder, id_perkiraan, id_cabang, keterangan, nominal, foto_bukti)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iiisds", $id_bruder, $id_perkiraan, $id_cabang, $keterangan, $nominal, $foto_bukti_filename);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Pengajuan pengeluaran berhasil dikirim dan menunggu persetujuan.'];
    } else {
        // Jika gagal, hapus file yang sudah terupload
        if ($foto_bukti_filename && file_exists($upload_path)) {
            unlink($upload_path);
        }
        return ['success' => false, 'message' => 'Gagal menyimpan pengajuan: ' . $conn->error];
    }
}

// =====================================================================
// APPROVE PENGAJUAN PENGELUARAN
// =====================================================================
function approvePengeluaran($conn) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] !== 'admin_cabang' && $_SESSION['level'] !== 'bendahara')) {
        return ['success' => false, 'message' => 'Akses ditolak. Hanya bendahara atau admin cabang yang bisa menyetujui.'];
    }

    $id_pengajuan = (int)($_POST['id_pengajuan'] ?? 0);
    $diperiksa_oleh = $_SESSION['id'] ?? null; // ID user yang menyetujui (bendahara/admin)

    if ($id_pengajuan <= 0) {
        return ['success' => false, 'message' => 'ID Pengajuan tidak valid.'];
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Get details from pengajuan_pengeluaran
        $stmt_get = $conn->prepare(
            "SELECT id_bruder, id_perkiraan, id_cabang, tanggal_pengajuan, keterangan, nominal
             FROM pengajuan_pengeluaran
             WHERE id_pengajuan = ? AND status = 'pending'"
        );
        $stmt_get->bind_param("i", $id_pengajuan);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();

        if ($result_get->num_rows === 0) {
            throw new Exception("Pengajuan tidak ditemukan atau sudah tidak pending.");
        }
        $pengajuan_data = $result_get->fetch_assoc();
        $stmt_get->close();

        // 2. Insert into transaksi table using stored procedure
        $bruder_id = $pengajuan_data['id_bruder'];
        $id_perkiraan = $pengajuan_data['id_perkiraan'];
        $tanggal_transaksi = $pengajuan_data['tanggal_pengajuan'];
        $keterangan = $pengajuan_data['keterangan'];
        $nominal = $pengajuan_data['nominal'];
        $id_cabang = $pengajuan_data['id_cabang'];

        $stmt_proc = $conn->prepare("CALL catat_transaksi_keuangan_multi_cabang(?, ?, ?, ?, 'Kas Harian', 'Pengeluaran', ?, ?)");
        $stmt_proc->bind_param("iissdi", $bruder_id, $id_perkiraan, $tanggal_transaksi, $keterangan, $nominal, $id_cabang);
        if (!$stmt_proc->execute()) {
            throw new Exception("Gagal mencatat transaksi final: " . $stmt_proc->error);
        }
        $stmt_proc->close();

        // 3. Update status in pengajuan_pengeluaran
        $stmt_update = $conn->prepare(
            "UPDATE pengajuan_pengeluaran
             SET status = 'disetujui', tanggal_aksi = NOW(), diperiksa_oleh = ?
             WHERE id_pengajuan = ?"
        );
        $stmt_update->bind_param("ii", $diperiksa_oleh, $id_pengajuan);
        if (!$stmt_update->execute()) {
            throw new Exception("Gagal memperbarui status pengajuan: " . $stmt_update->error);
        }
        $stmt_update->close();

        $conn->commit();
        return ['success' => true, 'message' => 'Pengajuan berhasil disetujui dan transaksi dicatat!'];

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Gagal menyetujui pengajuan: ' . $e->getMessage()];
    }
}

// =====================================================================
// REJECT PENGAJUAN PENGELUARAN
// =====================================================================
function rejectPengeluaran($conn) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] !== 'admin_cabang' && $_SESSION['level'] !== 'bendahara')) {
        return ['success' => false, 'message' => 'Akses ditolak. Hanya bendahara atau admin cabang yang bisa menolak.'];
    }

    $id_pengajuan = (int)($_POST['id_pengajuan'] ?? 0);
    $catatan_bendahara = $_POST['catatan_bendahara'] ?? '';
    $diperiksa_oleh = $_SESSION['id'] ?? null; // ID user yang menolak (bendahara/admin)

    if ($id_pengajuan <= 0) {
        return ['success' => false, 'message' => 'ID Pengajuan tidak valid.'];
    }

    $stmt = $conn->prepare(
        "UPDATE pengajuan_pengeluaran
         SET status = 'ditolak', tanggal_aksi = NOW(), catatan_bendahara = ?, diperiksa_oleh = ?
         WHERE id_pengajuan = ? AND status = 'pending'"
    );
    $stmt->bind_param("sii", $catatan_bendahara, $diperiksa_oleh, $id_pengajuan);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Pengajuan berhasil ditolak.'];
        } else {
            return ['success' => false, 'message' => 'Pengajuan tidak ditemukan atau sudah tidak pending.'];
        }
    } else {
        return ['success' => false, 'message' => 'Gagal menolak pengajuan: ' . $conn->error];
    }
}

// =====================================================================
// GET PENDING APPROVALS FOR BENDAHARA
// =====================================================================
function getPendingApprovals($conn) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_cabang'])) {
        return ['success' => false, 'message' => 'Unauthorized access'];
    }

    $id_cabang = (int)($_GET['id_cabang'] ?? 0);

    if ($id_cabang <= 0) {
        return ['success' => false, 'message' => 'ID Cabang tidak valid.'];
    }

    $stmt = $conn->prepare(
        "SELECT p.id_pengajuan, p.tanggal_pengajuan, b.nama_bruder, kp.nama_akun, p.keterangan, p.nominal, p.foto_bukti
         FROM pengajuan_pengeluaran p
         JOIN bruder b ON p.id_bruder = b.id_bruder
         JOIN kode_perkiraan kp ON p.id_perkiraan = kp.id_perkiraan
         WHERE p.id_cabang = ? AND p.status = 'pending'
         ORDER BY p.tanggal_pengajuan ASC"
    );
    $stmt->bind_param("i", $id_cabang);
    $stmt->execute();
    $result = $stmt->get_result();

    $approvals = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $approvals[] = $row;
        }
    }

    return ['success' => true, 'data' => $approvals];
}

// =====================================================================
// EDIT BRUDER
// =====================================================================
function editBruder($conn) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        return ['success' => false, 'message' => 'Unauthorized access'];
    }

    $id_bruder = (int)($_POST['id_bruder'] ?? 0);
    $nama_bruder_baru = $_POST['nama_bruder'] ?? '';
    $id_komunitas = (int)($_POST['id_komunitas'] ?? 0);
    $ttl_baru = $_POST['ttl_bruder'] ?? '';
    $alamat_baru = $_POST['alamat_bruder'] ?? '';

    if ($id_bruder <= 0 || empty($nama_bruder_baru)) {
        return ['success' => false, 'message' => 'ID Bruder dan nama harus diisi!'];
    }

    // Gunakan stored procedure yang sudah ada
    $stmt = $conn->prepare("CALL update_data_bruder(?, ?, ?, ?)");
    $stmt->bind_param("isss", $id_bruder, $nama_bruder_baru, $alamat_baru, $ttl_baru);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Data bruder berhasil diupdate!'];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupdate data bruder: ' . $conn->error];
    }
}

// =====================================================================
// DELETE BRUDER
// =====================================================================
function deleteBruder($conn) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        return ['success' => false, 'message' => 'Unauthorized access'];
    }

    $id_bruder = (int)($_POST['id_bruder'] ?? 0);

    if ($id_bruder <= 0) {
        return ['success' => false, 'message' => 'ID Bruder tidak valid!'];
    }

    // Cek apakah bruder memiliki transaksi
    $stmt_check = $conn->prepare("SELECT COUNT(*) as total_transaksi FROM transaksi WHERE id_bruder = ?");
    $stmt_check->bind_param("i", $id_bruder);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $transaksi_count = $result_check->fetch_assoc()['total_transaksi'];

    if ($transaksi_count > 0) {
        return ['success' => false, 'message' => 'Tidak dapat menghapus bruder yang memiliki riwayat transaksi!'];
    }

    // Cek apakah bruder memiliki pengajuan yang pending
    $stmt_check2 = $conn->prepare("SELECT COUNT(*) as total_pengajuan FROM pengajuan_pengeluaran WHERE id_bruder = ? AND status = 'pending'");
    $stmt_check2->bind_param("i", $id_bruder);
    $stmt_check2->execute();
    $result_check2 = $stmt_check2->get_result();
    $pengajuan_count = $result_check2->fetch_assoc()['total_pengajuan'];

    if ($pengajuan_count > 0) {
        return ['success' => false, 'message' => 'Tidak dapat menghapus bruder yang memiliki pengajuan pending!'];
    }

    // Delete bruder
    $stmt = $conn->prepare("DELETE FROM bruder WHERE id_bruder = ?");
    $stmt->bind_param("i", $id_bruder);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Bruder berhasil dihapus!'];
        } else {
            return ['success' => false, 'message' => 'Bruder tidak ditemukan!'];
        }
    } else {
        return ['success' => false, 'message' => 'Gagal menghapus bruder: ' . $conn->error];
    }
}
?>
