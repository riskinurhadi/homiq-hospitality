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
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 1200px;
        }

        .header-icon {
            font-size: 2.5rem;
            color: #005A9C;
        }
        
        h1 {
            color: #333;
            font-weight: 600;
        }

        .property-group {
            margin-bottom: 2.5rem;
        }

        .property-header {
            border-left: 5px solid #005A9C;
            background-color: #ffffff;
            color: #333;
            padding: 0.8rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-radius: 0.25rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .room-card {
            background-color: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 0.35rem;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            cursor: pointer;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            position: relative;
        }
        
        .room-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0, 90, 156, 0.1);
        }

        .room-card a {
            text-decoration: none;
            color: inherit;
            display: block;
            padding: 1.25rem;
        }
        
        .room-card .card-title {
            font-weight: 600;
            font-size: 1.15rem;
            color: #005A9C;
            margin-bottom: 0.25rem;
        }

        .room-card .card-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.75rem;
        }

        .status-badge {
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.3em 0.6em;
            border-radius: 0.25rem;
            color: #fff;
        }

        .status-Tersedia { background-color: #28a745; }
        .status-Kotor { background-color: #dc3545; }
        .status-Maintenance { background-color: #ffc107; color: #333 !important; }
        .status-Tidak-Tersedia { background-color: #6c757d; }

    </style>
</head>

<body>
    <div class="container py-5">
        <header class="text-center mb-5">
            <i class="fas fa-tasks header-icon"></i>
            <h1>Checklist Kamar</h1>
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
                echo '<h2 class="property-header"><i class="fas fa-building me-2"></i>' . htmlspecialchars($properti['nama_properti']) . '</h2>';

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
                        
                        echo '<div class="col-lg-3 col-md-4 col-sm-6">';
                        echo '<div class="card room-card">';
                        echo '<a href="form_checklist.php?id_kamar=' . $kamar['id_kamar'] . '">';
                        echo '<h5 class="card-title">Kamar ' . htmlspecialchars($kamar['nama_kamar']) . '</h5>';
                        echo '<h6 class="card-subtitle">Tipe: ' . htmlspecialchars($kamar['tipe_kamar']) . '</h6>';
                        echo '<span class="status-badge ' . $status_class . '">' . htmlspecialchars($kamar['status']) . '</span>';
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
