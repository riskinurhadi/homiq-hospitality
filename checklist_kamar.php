<?php
session_start();
include 'auth_check.php';
include 'koneksi.php';

// Pastikan hanya user dengan role 'housekeeping' atau 'admin' yang bisa mengakses
if (!in_array($_SESSION['role'], ['housekeeping', 'admin'])) {
    // Redirect ke halaman yang sesuai jika tidak punya akses
    header("Location: dashboard.php");
    exit();
}

$pageTitle = "Checklist Kebersihan Kamar";

// Proses form jika ada data yang di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_kamar'])) {
    $id_kamar = $_POST['id_kamar'];
    $status_kamar_input = $_POST['status_kamar'];

    // Menyesuaikan nilai status dengan ENUM di database
    $status_db = '';
    if ($status_kamar_input === 'Siap Jual') {
        $status_db = 'Tersedia';
    } elseif ($status_kamar_input === 'Kotor') {
        $status_db = 'Kotor';
    }

    if (!empty($status_db)) {
        // Update status kamar di database
        $stmt = $koneksi->prepare("UPDATE tbl_kamar SET status = ? WHERE id_kamar = ?");
        $stmt->bind_param("si", $status_db, $id_kamar);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect untuk menghindari re-submit form
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


// Ambil daftar kamar dari database, join dengan properti untuk mendapatkan nama properti
$query_kamar = "SELECT k.id_kamar, k.nama_kamar, k.status, p.nama_properti 
                FROM tbl_kamar k
                JOIN tbl_properti p ON k.id_properti = p.id_properti
                ORDER BY p.nama_properti, k.nama_kamar ASC";
$result_kamar = $koneksi->query($query_kamar);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - HOMIQ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Mengikuti style mobile-first dari permintaan sebelumnya */
        body {
            padding-bottom: 70px; /* Space for bottom navbar */
            background-color: #f8f9fa;
        }
        .main-content {
            margin-left: 0 !important; /* Full width for mobile-first */
        }
        .card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
        }
        .card-header {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .form-check-label {
            cursor: pointer;
        }
        /* Style untuk status kamar */
        .status-siap {
            border-left: 5px solid #198754; /* Hijau untuk Tersedia */
        }
        .status-kotor {
            border-left: 5px solid #dc3545; /* Merah untuk Kotor */
        }
        .status-lain {
            border-left: 5px solid #ffc107; /* Kuning untuk status lainnya (Maintenance, dll) */
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $pageTitle; ?></h1>
                </div>

                <div class="row">
                    <?php if ($result_kamar && $result_kamar->num_rows > 0): ?>
                        <?php while ($kamar = $result_kamar->fetch_assoc()): 
                            $status_class = '';
                            if ($kamar['status'] == 'Tersedia') {
                                $status_class = 'status-siap';
                            } elseif ($kamar['status'] == 'Kotor') {
                                $status_class = 'status-kotor';
                            } else {
                                $status_class = 'status-lain';
                            }
                        ?>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="card <?php echo $status_class; ?>">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($kamar['nama_properti']) . ' - ' . htmlspecialchars($kamar['nama_kamar']); ?></span>
                                    <span class="badge bg-dark"><?php echo htmlspecialchars($kamar['status']); ?></span>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="id_kamar" value="<?php echo $kamar['id_kamar']; ?>">
                                        
                                        <p class="fw-bold">Checklist Item:</p>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="ac_<?php echo $kamar['id_kamar']; ?>">
                                            <label class="form-check-label" for="ac_<?php echo $kamar['id_kamar']; ?>">AC Dingin</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="km_<?php echo $kamar['id_kamar']; ?>">
                                            <label class="form-check-label" for="km_<?php echo $kamar['id_kamar']; ?>">Kamar Mandi Bersih</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="sprei_<?php echo $kamar['id_kamar']; ?>">
                                            <label class="form-check-label" for="sprei_<?php echo $kamar['id_kamar']; ?>">Sprei & Bedding Bersih</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="air_<?php echo $kamar['id_kamar']; ?>">
                                            <label class="form-check-label" for="air_<?php echo $kamar['id_kamar']; ?>">Air Mengalir Normal</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="lampu_<?php echo $kamar['id_kamar']; ?>">
                                            <label class="form-check-label" for="lampu_<?php echo $kamar['id_kamar']; ?>">Semua Lampu Menyala</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="tv_<?php echo $kamar['id_kamar']; ?>">
                                            <label class="form-check-label" for="tv_<?php echo $kamar['id_kamar']; ?>">TV Berfungsi Normal</label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="extrabed_<?php echo $kamar['id_kamar']; ?>">
                                            <label class="form-check-label" for="extrabed_<?php echo $kamar['id_kamar']; ?>">Extra Bed (Opsional)</label>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" name="status_kamar" value="Siap Jual" class="btn btn-success">Konfirmasi Siap Jual</button>
                                            <button type="submit" name="status_kamar" value="Kotor" class="btn btn-danger">Tandai Masih Kotor</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">Belum ada data kamar. Silakan tambahkan kamar di halaman Manajemen Kamar.</div>
                        </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>