<?php
include 'db.php';
include 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = $_SESSION['user'];
$id_akun = $user['id_akun'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jumlah = intval($_POST['jumlah']);
    $jangka = intval($_POST['jangka']);

    // Validasi jumlah dan jangka
    if ($jumlah < 1000000 || $jumlah > 5000000) {
        $error = "Jumlah pinjaman harus antara Rp1.000.000 hingga Rp5.000.000";
    } elseif ($jangka < 2 || $jangka > 24) {
        $error = "Jangka waktu harus antara 2 hingga 24 bulan";
    } else {
        // Cek pinjaman aktif
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM pengajuan_pinjaman p
            WHERE p.id_akun = ? AND p.status_pinjaman = 'Disetujui'
              AND EXISTS (
                SELECT 1 FROM jadwal_pembayaran_pinjaman j
                WHERE j.id_pinjaman = p.id_pinjaman AND j.status_pembayaran = 'Belum Lunas'
              )
        ");
        $stmt->bind_param("i", $id_akun);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $error = "Anda masih memiliki pinjaman aktif yang belum lunas. Tidak bisa mengajukan pinjaman baru.";
        } else {
            $stmt = $conn->prepare("CALL sp_ajukan_pinjaman(?, ?, ?)");
            $stmt->bind_param("iii", $id_akun, $jumlah, $jangka);
            if ($stmt->execute()) {
                $success = "Pengajuan pinjaman sebesar Rp" . number_format($jumlah) . " selama $jangka bulan telah diajukan.";
            } else {
                $error = "Gagal mengajukan pinjaman.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Ajukan Pinjaman - UniBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(to right, #3e1e8c, #47217a);
            color: #fff;
        }
        .container {
            margin-top: 80px;
            max-width: 700px;
        }
        .card {
            background-color: #fff;
            color: #000;
            border-radius: 1rem;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: bold;
        }
        a.back-link {
            color: #47217a;
            text-decoration: underline;
            display: inline-block;
            margin-bottom: 15px;
            font-weight: 600;
        }
        table th, table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-gradient sticky-top" style="background: linear-gradient(90deg, #4e2c90, #2c1e60);">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="#">UniBank</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNavDropdown">
            <ul class="navbar-nav gap-2">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="setor.php">Setor</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="tarik.php">Tarik</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="transfer.php">Transfer</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Pinjaman</a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="pinjaman.php">Ajukan Pinjaman</a></li>
                        <li><a class="dropdown-item" href="pembayaran_pinjaman.php">Bayar Pinjaman</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link text-danger fw-bold" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="card mb-4">
        <h3 class="mb-3">Ajukan Pinjaman</h3>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" class="mb-4">
            <div class="mb-3">
                <label for="jumlah" class="form-label">Jumlah Pinjaman (Rp):</label>
                <input type="number" id="jumlah" name="jumlah" min="1000000" max="5000000" class="form-control" required value="<?= isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : '' ?>">
            </div>
            <div class="mb-3">
                <label for="jangka" class="form-label">Jangka Waktu (bulan):</label>
                <input type="number" id="jangka" name="jangka" min="2" max="24" class="form-control" required value="<?= isset($_POST['jangka']) ? (int)$_POST['jangka'] : '' ?>">
            </div>
            <button type="submit" class="btn btn-primary">Ajukan</button>
        </form>

        <h4>Pinjaman Aktif Saya</h4>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Jumlah</th>
                    <th>Jangka</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $stmt = $conn->prepare("SELECT jumlah_pinjaman, jangka, status_pinjaman FROM view_pinjaman_aktif WHERE id_akun = ?");
            $stmt->bind_param("i", $id_akun);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td>Rp<?= number_format($row['jumlah_pinjaman']) ?></td>
                    <td><?= htmlspecialchars($row['jangka']) ?> bulan</td>
                    <td><?= htmlspecialchars($row['status_pinjaman']) ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan="3" class="text-center">Tidak ada pinjaman aktif.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
