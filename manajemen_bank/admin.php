<?php
include 'db.php';
include 'functions.php';

$notification = ""; // Variabel untuk menyimpan notifikasi

// Setujui Pinjaman
if (isset($_GET['setujui'])) {
    $id = intval($_GET['setujui']);
    $stmt = $conn->prepare("CALL sp_setujui_pinjaman(?)");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $notification = "Pinjaman berhasil disetujui.";
}

// Tolak Pinjaman
if (isset($_GET['tolak'])) {
    $id = intval($_GET['tolak']);
    $stmt = $conn->prepare("UPDATE pengajuan_pinjaman SET status_pinjaman = 'Ditolak' WHERE id_pinjaman = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $notification = "Pinjaman berhasil ditolak.";
}

// Update Status Akun
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id = intval($_POST['id_akun']);
    $status = $conn->real_escape_string($_POST['status_akun']);

    if ($status !== 'Aktif' && $status !== 'NonAktif') {
        $notification = "Status tidak valid.";
    } else {
        $stmt = $conn->prepare("UPDATE akun_nasabah SET status_akun = ? WHERE id_akun = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();

        if ($status === 'NonAktif') {
            $notification = "Akun berhasil dinonaktifkan.";
        } else {
            $notification = "Akun berhasil diaktifkan kembali.";
        }
    }
}

// Hapus Akun
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    
    // Cek apakah akun bisa dihapus
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pengajuan_pinjaman WHERE id_akun = ? AND status_pinjaman = 'Disetujui'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $notification = "Tidak bisa menghapus akun yang masih memiliki pinjaman aktif.";
    } else {
        $stmt = $conn->prepare("DELETE FROM akun_nasabah WHERE id_akun = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $notification = "Akun berhasil dihapus.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin UniBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        
        body {
            background: linear-gradient(135deg, #1d1e4f, #47217a);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .bg-primary {
            background: #3e1e8c !important;
        }
        .bg-warning {
            background: #ffb200 !important;
        }
        .bg-secondary {
            background: #6c63ff !important;
        }
        .bg-info {
            background: #00bcd4 !important;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .btn {
            border-radius: 20px;
        }
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
        }
        .container {
            max-width: 1200px;
        }
        h2 {
            color: #ffffff;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <?php if (!empty($notification)): ?>
<div class="alert alert-info text-center mt-3 mb-4">
    <?= htmlspecialchars($notification) ?>
</div>
<?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>UniBank Admin</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <!-- Daftar Nasabah -->
    <div class="card mb-4">
        <div class="card-header text-white bg-primary">
            Daftar Nasabah
        </div>
        <div class="card-body bg-white">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NIK</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM akun_nasabah");
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $row['nama'] ?></td>
                        <td><?= $row['nik'] ?></td>
                        <td><?= $row['status_akun'] ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="id_akun" value="<?= $row['id_akun'] ?>">
                                <select name="status_akun" onchange="this.form.submit()" class="form-select form-select-sm d-inline w-auto" required>
                                    <option value="Aktif" <?= $row['status_akun'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="NonAktif" <?= $row['status_akun'] == 'NonAktif' ? 'selected' : '' ?>>NonAktif</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                            <a href="?hapus=<?= $row['id_akun'] ?>" onclick="return confirm('Yakin hapus?')" class="btn btn-danger btn-sm ms-2">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pinjaman Menunggu -->
    <div class="card mb-4">
        <div class="card-header text-dark bg-warning">
            Pinjaman Menunggu Persetujuan
        </div>
        <div class="card-body bg-white">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jumlah</th>
                        <th>Jangka</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
<?php
$sql = "SELECT p.id_pinjaman, a.nama, p.jumlah_pinjaman, p.jangka 
        FROM pengajuan_pinjaman p
        JOIN akun_nasabah a ON p.id_akun = a.id_akun
        WHERE p.status_pinjaman = 'Menunggu'";

$result = $conn->query($sql);

if (!$result) {
    echo "<div class='alert alert-danger'>Query error: " . $conn->error . "</div>";
}
?>

<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['nama']) ?></td>
            <td>Rp<?= number_format($row['jumlah_pinjaman']) ?></td>
            <td><?= htmlspecialchars($row['jangka']) ?> bulan</td>
            <td>
                <a href="?setujui=<?= $row['id_pinjaman'] ?>" class="btn btn-success btn-sm">Setujui</a>
                <a href="?tolak=<?= $row['id_pinjaman'] ?>" class="btn btn-danger btn-sm">Tolak</a>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="4" class="text-center">Tidak ada pinjaman menunggu.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Rekap Pinjaman -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            Rekap Pinjaman Pengguna
        </div>
        <div class="card-body bg-white">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Total Pengajuan</th>
                        <th>Total Pinjaman</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM view_rekap_pinjaman");
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $row['nama'] ?></td>
                        <td><?= $row['total_pengajuan'] ?></td>
                        <td>Rp<?= number_format($row['total_pinjaman']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pinjaman Aktif -->
    <div class="card mb-5">
        <div class="card-header bg-info text-white">
            Pinjaman Aktif
        </div>
        <div class="card-body bg-white">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jumlah</th>
                        <th>Jangka</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM view_pinjaman_aktif");
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $row['nama'] ?></td>
                        <td>Rp<?= number_format($row['jumlah_pinjaman']) ?></td>
                        <td><?= $row['jangka'] ?> bulan</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
