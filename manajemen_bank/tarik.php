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
    if ($jumlah >= 20000 && $jumlah <= $user['saldo']) {
        $stmt = $conn->prepare("CALL sp_tarik(?, ?)");
        $stmt->bind_param("ii", $id_akun, $jumlah);
        $stmt->execute();
        $_SESSION['user'] = getUserById($id_akun, $conn); // Refresh data
        $success = "Berhasil menarik sebesar Rp" . number_format($jumlah, 0, ',', '.');
    } elseif ($jumlah > $user['saldo']) {
        $error = "Saldo tidak mencukupi.";
    } else {
        $error = "Minimal penarikan adalah Rp20.000.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tarik Saldo - UniBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #3e1e8c, #47217a);
            color: #fff;
        }
        .container {
            margin-top: 80px;
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
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-gradient sticky-top" style="background: linear-gradient(90deg, #4e2c90, #2c1e60);">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
            UniBank
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNavDropdown">
            <ul class="navbar-nav gap-2">
                <li class="nav-item">
                    <a class="nav-link text-white" href="dashboard.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="setor.php">Setor</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="tarik.php">Tarik</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="transfer.php">Transfer</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        Pinjaman
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="pinjaman.php">Ajukan Pinjaman</a></li>
                        <li><a class="dropdown-item" href="pembayaran_pinjaman.php">Bayar Pinjaman</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger fw-bold" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="container">
    <div class="card mb-4">
        <h3 class="mb-3">Tarik Saldo</h3>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="jumlah" class="form-label">Jumlah Penarikan (min Rp20.000):</label>
                <input type="number" class="form-control" id="jumlah" name="jumlah" min="20000" required>
            </div>
            <button type="submit" class="btn btn-primary">Tarik</button>
        </form>
    </div>

    <div class="card">
        <h4 class="mb-3">Riwayat Penarikan</h4>
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Jumlah</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM view_riwayat_tarik WHERE id_akun = $id_akun");
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td>Rp<?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                    <td><?= $row['tanggal_transaksi'] ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="2" class="text-center">Belum ada riwayat penarikan</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
