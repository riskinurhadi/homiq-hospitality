<?php
// cetak_struk.php
// Hasilkan PDF struk/invoice untuk reservasi

require_once 'auth_check.php';
require_once 'koneksi.php';
require_once 'lib/fpdf/fpdf.php'; // Sesuaikan path jika perlu

// Validasi ID Reservasi
$id_reservasi = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_reservasi <= 0) {
    die('ID Reservasi tidak valid.');
}

// Ambil data reservasi lengkap
$query = "SELECT r.*, t.nama_lengkap, k.nama_kamar, p.nama_properti
          FROM tbl_reservasi r
          JOIN tbl_tamu t ON r.id_tamu = t.id_tamu
          JOIN tbl_kamar k ON r.id_kamar = k.id_kamar
          JOIN tbl_properti p ON k.id_properti = p.id_properti
          WHERE r.id_reservasi = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_reservasi);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die('Reservasi tidak ditemukan.');
}
$reservasi = $result->fetch_assoc();
$stmt->close();
$koneksi->close();

// Mulai Generate PDF
class PDF extends FPDF
{
    // Header
    function Header()
    {
        global $reservasi;
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Judul
        $this->Cell(0, 10, 'INVOICE - ' . $reservasi['nama_properti'], 0, 1, 'C');
        // Line break
        $this->Ln(10);
    }

    // Footer
    function Footer()
    {
        // Posisi 1.5 cm dari bawah
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Teks
        $this->Cell(0, 10, 'Terima kasih telah menginap di ' . $GLOBALS['reservasi']['nama_properti'], 0, 0, 'C');
    }

    // Tabel info
    function InfoReservasi($reservasi)
    {
        $this->SetFont('Arial', '', 12);
        $this->Cell(40, 10, 'ID Reservasi', 0);
        $this->Cell(0, 10, ': #' . $reservasi['id_reservasi'], 0, 1);

        $this->Cell(40, 10, 'Nama Tamu', 0);
        $this->Cell(0, 10, ': ' . $reservasi['nama_lengkap'], 0, 1);
        
        $checkin = new DateTime($reservasi['tgl_checkin']);
        $checkout = new DateTime($reservasi['tgl_checkout']);

        $this->Cell(40, 10, 'Check-in', 0);
        $this->Cell(0, 10, ': ' . $checkin->format('d M Y, H:i'), 0, 1);

        $this->Cell(40, 10, 'Check-out', 0);
        $this->Cell(0, 10, ': ' . $checkout->format('d M Y, H:i'), 0, 1);

        $this->Ln(5);
    }

    // Tabel Rincian Biaya
    function RincianBiaya($reservasi)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(130, 10, 'Deskripsi', 1, 0, 'C', true);
        $this->Cell(60, 10, 'Jumlah', 1, 1, 'C', true);

        $this->SetFont('Arial', '', 12);
        
        // Deskripsi item
        $durasi_str = '';
        if ($reservasi['jenis_booking'] && strpos($reservasi['jenis_booking'], 'Transit') !== false) {
            $durasi_str = ' (' . $reservasi['jenis_booking'] . ')';
        } else {
            $checkin = new DateTime($reservasi['tgl_checkin']);
            $checkout = new DateTime($reservasi['tgl_checkout']);

            // Reset time to midnight to calculate nights correctly
            $checkin->setTime(0, 0, 0);
            $checkout->setTime(0, 0, 0);

            $durasi = $checkin->diff($checkout)->days;
            $durasi_str = ' (' . $durasi . ' malam)';
        }

        $this->Cell(130, 10, 'Biaya Menginap Kamar ' . $reservasi['nama_kamar'] . $durasi_str, 1);
        $this->Cell(60, 10, 'Rp ' . number_format($reservasi['harga_total'], 0, ',', '.'), 1, 1, 'R');

        // Total
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(130, 12, 'TOTAL', 1, 0, 'R');
        $this->SetFillColor(230, 230, 230);
        $this->Cell(60, 12, 'Rp ' . number_format($reservasi['harga_total'], 0, ',', '.'), 1, 1, 'R', true);
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->InfoReservasi($reservasi);
$pdf->RincianBiaya($reservasi);
$pdf->Output('I', 'Struk_Reservasi_' . $id_reservasi . '.pdf');

?>
