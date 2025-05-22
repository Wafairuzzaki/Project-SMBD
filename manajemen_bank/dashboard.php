<?php
include 'db.php';
include 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = $_SESSION['user'];
$id = $user['id_akun'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $conn->real_escape_string($_POST['nama']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    $no_hp = $conn->real_escape_string($_POST['no_hp']);

    $stmt = $conn->prepare("UPDATE akun_nasabah SET nama = ?, alamat = ?, no_hp = ? WHERE id_akun = ?");
    $stmt->bind_param("sssi", $nama, $alamat, $no_hp, $id);
    $stmt->execute();
    $_SESSION['user'] = $stmt->get_result() ?: getUserById($id, $conn);
    redirect('dashboard.php');
}

$stmt = $conn->prepare("SELECT * FROM akun_nasabah WHERE id_akun = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pengguna - UniBank</title>
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
        textarea {
            resize: none;
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
                    <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
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
        <h3 class="mb-3">Dashboard Pengguna</h3>
        <p><strong>Halo, <?= htmlspecialchars($userData['nama']) ?> 👋</strong></p>
        <p>Saldo Anda: <span class="badge bg-success">Rp<?= number_format($userData['saldo'], 0, ',', '.') ?></span></p>
    </div>

    <div class="card mb-4">
        <h4 class="mb-3">Edit Informasi Pribadi</h4>
        <form method="post">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama:</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($userData['nama']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat:</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="2"><?= htmlspecialchars($userData['alamat']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="no_hp" class="form-label">No HP:</label>
                <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= htmlspecialchars($userData['no_hp']) ?>" required pattern="\d{12,}" title="No HP minimal 12 angka">
            </div>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
