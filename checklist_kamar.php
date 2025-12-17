<?php
session_start();
include 'koneksi.php';
include 'auth_check.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Kebersihan Kamar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .property-group {
            margin-bottom: 2rem;
        }

        .property-header {
            background: linear-gradient(45deg, #6A11CB, #2575FC);
            color: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .room-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            cursor: pointer;
            border: none;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.15);
        }

        .room-card a {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .room-card .card-body {
            padding: 1.5rem;
            position: relative;
        }
        
        .room-card .card-title {
            font-weight: bold;
            font-size: 1.25rem;
            color: #333;
        }

        .room-card .card-text {
            color: #666;
        }
        
        .status-icon {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 1.5rem;
        }

        .status-Tersedia { color: #28a745; }
        .status-Kotor { color: #dc3545; }
        .status-Maintenance { color: #ffc107; }
        .status-Tidak-Tersedia { color: #6c757d; }

    </style>
</head>

<body>
    <div class="container py-5">
        <header class="text-center mb-5">
            <h1><i class="fas fa-tasks"></i> Checklist Kamar</h1>
            <p class="lead text-muted">Pilih kamar untuk memulai inspeksi kebersihan dan kelayakan.</p>
        </header>

        <?php
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            echo '<div class="alert alert-' . $message['type'] . ' alert-dismissible fade show" role="alert">';
            echo $message['message'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['flash_message']);
        }
        ?>

        <?php
        // Fetch properties
        $query_properti = "SELECT * FROM tbl_properti ORDER BY nama_properti ASC";
        $result_properti = mysqli_query($conn, $query_properti);

        if (mysqli_num_rows($result_properti) > 0) {
            while ($properti = mysqli_fetch_assoc($result_properti)) {
                echo '<div class="property-group">';
                echo '<h2 class="property-header"><i class="fas fa-building"></i> ' . htmlspecialchars($properti['nama_properti']) . '</h2>';

                // Fetch rooms for the current property
                $id_properti = $properti['id_properti'];
                $query_kamar = "SELECT * FROM tbl_kamar WHERE id_properti = ? ORDER BY nama_kamar ASC";
                $stmt = $conn->prepare($query_kamar);
                $stmt->bind_param("i", $id_properti);
                $stmt->execute();
                $result_kamar = $stmt->get_result();

                if ($result_kamar->num_rows > 0) {
                    echo '<div class="row g-4">';
                    while ($kamar = $result_kamar->fetch_assoc()) {
                        $status_class = 'status-' . str_replace(' ', '-', $kamar['status']);
                        $icon_class = 'fas fa-question-circle'; // Default
                        switch ($kamar['status']) {
                            case 'Tersedia':
                                $icon_class = 'fas fa-check-circle';
                                break;
                            case 'Kotor':
                                $icon_class = 'fas fa-times-circle';
                                break;
                            case 'Maintenance':
                                $icon_class = 'fas fa-tools';
                                break;
                            case 'Tidak Tersedia':
                                $icon_class = 'fas fa-ban';
                                break;
                        }

                        echo '<div class="col-lg-3 col-md-4 col-sm-6">';
                        echo '<div class="card room-card">';
                        // The entire card is a link
                        echo '<a href="form_checklist.php?id_kamar=' . $kamar['id_kamar'] . '">';
                        echo '<div class="card-body">';
                        echo '<i class="' . $icon_class . ' ' . $status_class . ' status-icon" title="Status: ' . htmlspecialchars($kamar['status']) . '"></i>';
                        echo '<h5 class="card-title">Kamar ' . htmlspecialchars($kamar['nama_kamar']) . '</h5>';
                        echo '<p class="card-text mb-0">Tipe: ' . htmlspecialchars($kamar['tipe_kamar']) . '</p>';
                        echo '</div>';
                        echo '</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>'; // end .row
                } else {
                    echo '<p class="text-center text-muted">Tidak ada kamar yang terdaftar di properti ini.</p>';
                }
                $stmt->close();
                echo '</div>'; // end .property-group
            }
        } else {
            echo '<p class="text-center">Tidak ada properti yang ditemukan.</p>';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
