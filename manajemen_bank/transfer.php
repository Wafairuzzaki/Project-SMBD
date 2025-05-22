<?php
include 'db.php';
include 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = $_SESSION['user'];
$id_pengirim = $user['id_akun'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_penerima = intval($_POST['id_penerima']);
    $jumlah = intval($_POST['jumlah']);

    if ($jumlah < 20000) {
        $error = "Minimal transfer adalah Rp20.000";
    } elseif ($jumlah > $user['saldo']) {
        $error = "Saldo tidak mencukupi";
    } else {
        $stmt = $conn->prepare("CALL sp_transfer(?, ?, ?)");
        $stmt->bind_param("iii", $id_pengirim, $id_penerima, $jumlah);
        if ($stmt->execute()) {
            // Refresh data user setelah transfer
            $_SESSION['user'] = getUserById($id_pengirim, $conn);
            $success = "Transfer berhasil sebesar Rp" . number_format($jumlah, 0, ',', '.');
        } else {
            $error = "Gagal melakukan transfer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transfer - UniBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #3e1e8c, #47217a);
            color: #fff;
        }
        .container {
            margin-top: 80px;
            margin-bottom: 40px;
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
        table {
            background-color: #fff;
            color: #000;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        th, td {
            vertical-align: middle !important;
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
                    <a class="nav-link active text-white" href="transfer.php">Transfer</a>
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
        <h3 class="mb-3">Transfer Saldo</h3>

        <?php if (isset($error)) : ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($success)) : ?>
            <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="id_penerima" class="form-label">Pilih Penerima:</label>
                <select name="id_penerima" id="id_penerima" class="form-select" required>
                    <option value="">-- Pilih Nasabah --</option>
                    <?php
                    $query = "SELECT id_akun, nama FROM akun_nasabah WHERE status_akun = 'Aktif' AND id_akun != ?";
                    $stmt_select = $conn->prepare($query);
                    $stmt_select->bind_param("i", $id_pengirim);
                    $stmt_select->execute();
                    $result_select = $stmt_select->get_result();

                    while ($row = $result_select->fetch_assoc()):
                    ?>
                        <option value="<?= $row['id_akun'] ?>">
                            <?= htmlspecialchars($row['nama']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="jumlah" class="form-label">Jumlah Transfer (minimal Rp20.000):</label>
                <input type="number" name="jumlah" id="jumlah" min="20000" required class="form-control" placeholder="Masukkan jumlah transfer">
            </div>

            <button type="submit" class="btn btn-primary">Transfer</button>
        </form>
    </div>

    <div class="card">
        <h4 class="mb-3">Riwayat Transfer</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center">
                <thead class="table-dark">
                    <tr>
<th>Pengirim</th>
<th>Penerima</th>
<th>Jumlah Transfer</th>
<th>Status</th>
<th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $query = "SELECT * FROM view_riwayat_transfer_lengkap WHERE id_akun = ?";
                $stmt = $conn->prepare($query);

                if ($stmt === false) {
                    die("Gagal mempersiapkan query: " . $conn->error);
                }

                $stmt->bind_param("i", $id_pengirim);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
<tr>
    <td><?= htmlspecialchars($row['pengirim']) ?></td>
    <td><?= htmlspecialchars($row['penerima']) ?></td>
    <td>Rp<?= number_format($row['jumlah_transfer'], 0, ',', '.') ?></td>
    <td><?= htmlspecialchars($row['status_transfer']) ?></td>
    <td><?= htmlspecialchars($row['tanggal']) ?></td>
</tr>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr>
                        <td colspan="4">Tidak ada riwayat transfer.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
