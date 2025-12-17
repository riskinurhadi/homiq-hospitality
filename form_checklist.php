<?php
session_start();
include 'koneksi.php';
include 'auth_check.php';

// Cek apakah id_kamar ada di URL
if (!isset($_GET['id_kamar']) || empty($_GET['id_kamar'])) {
    header("Location: checklist_kamar.php");
    exit();
}

$id_kamar = $_GET['id_kamar'];
$id_user = $_SESSION['user_id']; // Asumsi user_id disimpan di session saat login

// Ambil data kamar dan properti
$query_kamar = "SELECT k.nama_kamar, k.tipe_kamar, k.status, p.nama_properti 
                FROM tbl_kamar k
                JOIN tbl_properti p ON k.id_properti = p.id_properti
                WHERE k.id_kamar = ?";
$stmt_kamar = $conn->prepare($query_kamar);
$stmt_kamar->bind_param("i", $id_kamar);
$stmt_kamar->execute();
$result_kamar = $stmt_kamar->get_result();
if ($result_kamar->num_rows === 0) {
    echo "Kamar tidak ditemukan.";
    exit();
}
$kamar = $result_kamar->fetch_assoc();
$stmt_kamar->close();

// Definisikan item checklist
$checklist_items = [
    'ac' => 'AC Dingin',
    'kamar_mandi' => 'Kamar Mandi Bersih',
    'sprei' => 'Sprei & Bedding Bersih',
    'air' => 'Air Mengalir Normal',
    'lampu' => 'Semua Lampu Menyala',
    'tv' => 'TV Berfungsi',
    'extra_bed' => 'Extra Bed (Jika ada permintaan)'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Checklist Kamar <?php echo htmlspecialchars($kamar['nama_kamar']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f4f7f6;
        }
        .card-header {
            background: linear-gradient(45deg, #6A11CB, #2575FC);
            color: white;
        }
        .form-check-input:checked {
            background-color: #2575FC;
            border-color: #2575FC;
        }
        .btn-submit {
            background-color: #2575FC;
            border-color: #2575FC;
            color: white;
            transition: background-color 0.3s;
        }
        .btn-submit:hover {
            background-color: #6A11CB;
            border-color: #6A11CB;
        }
        .checklist-item {
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: .375rem;
            margin-bottom: 1rem;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg">
                <div class="card-header text-center p-4">
                    <h2 class="mb-0"><i class="fas fa-clipboard-check"></i> Checklist Kamar <?php echo htmlspecialchars($kamar['nama_kamar']); ?></h2>
                    <h5 class="mb-0 fw-normal"><?php echo htmlspecialchars($kamar['nama_properti']); ?></h5>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="proses_update_checklist.php" method="POST">
                        <input type="hidden" name="id_kamar" value="<?php echo $id_kamar; ?>">
                        <input type="hidden" name="id_user" value="<?php echo $id_user; ?>">

                        <div class="mb-4">
                            <h4><i class="fas fa-list-ul"></i> Daftar Pengecekan</h4>
                            <p class="text-muted">Pastikan semua item dalam kondisi 'Baik' sebelum mengubah status kamar menjadi 'Tersedia'.</p>
                        </div>

                        <?php foreach ($checklist_items as $key => $label): ?>
                        <div class="checklist-item">
                            <div class="row align-items-center">
                                <div class="col-sm-6">
                                    <label class="form-label fw-bold"><?php echo $label; ?></label>
                                </div>
                                <div class="col-sm-6 text-sm-end">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status[<?php echo $key; ?>]" id="<?php echo $key; ?>_baik" value="Baik" required>
                                        <label class="form-check-label" for="<?php echo $key; ?>_baik"><i class="fas fa-check-circle text-success"></i> Baik</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status[<?php echo $key; ?>]" id="<?php echo $key; ?>_perbaikan" value="Perbaikan">
                                        <label class="form-check-label" for="<?php echo $key; ?>_perbaikan"><i class="fas fa-times-circle text-danger"></i> Perbaikan</label>
                                    </div>
                                </div>
                            </div>
                             <div class="mt-2">
                                <input type="text" class="form-control form-control-sm" name="catatan[<?php echo $key; ?>]" placeholder="Catatan (opsional)...">
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <hr class="my-4">

                        <div class="mb-4">
                             <h4><i class="fas fa-sync-alt"></i> Update Status Kamar</h4>
                            <select class="form-select" name="status_kamar" required>
                                <option value="">-- Pilih Status Kamar --</option>
                                <option value="Tersedia">Tersedia (Siap Jual)</option>
                                <option value="Kotor">Kotor (Perlu Dibersihkan)</option>
                                <option value="Maintenance">Maintenance (Butuh Perbaikan)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <h4><i class="fas fa-sticky-note"></i> Catatan Umum</h4>
                            <textarea class="form-control" name="catatan_umum" rows="3" placeholder="Tambahkan catatan umum jika diperlukan..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-submit btn-lg"><i class="fas fa-save"></i> Simpan Checklist & Update Status</button>
                            <a href="checklist_kamar.php" class="btn btn-secondary"><i class="fas fa-times"></i> Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
