<?php
include 'db.php';
include 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = $_SESSION['user'];
$id_akun = $user['id_akun'];

// Handle payment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_jadwal = intval($_POST['id_jadwal']);

    // Get installment details
    $stmt = $conn->prepare("SELECT jumlah_bayar, id_pinjaman FROM jadwal_pembayaran_pinjaman WHERE id_jadwal = ?");
    $stmt->bind_param("i", $id_jadwal);
    $stmt->execute();
    $stmt->bind_result($jumlah_bayar, $id_pinjaman);
    $stmt->fetch();
    $stmt->close();

    if (!$jumlah_bayar) {
        $error = "Jadwal pembayaran tidak ditemukan.";
    } elseif ($user['saldo'] < $jumlah_bayar) {
        $error = "Saldo tidak mencukupi untuk membayar cicilan ini.";
    } else {
        // Validasi apakah ini cicilan terawal
        $stmt_cek = $conn->prepare("
            SELECT id_jadwal FROM jadwal_pembayaran_pinjaman j
            JOIN pengajuan_pinjaman p ON j.id_pinjaman = p.id_pinjaman
            WHERE p.id_akun = ? AND j.status_pembayaran = 'Belum Lunas'
            ORDER BY j.tanggal_jatuh_tempo ASC
            LIMIT 1
        ");
        $stmt_cek->bind_param("i", $id_akun);
        $stmt_cek->execute();
        $stmt_cek->bind_result($jadwal_terawal);
        $stmt_cek->fetch();
        $stmt_cek->close();

        if ($id_jadwal != $jadwal_terawal) {
            $error = "Hanya bisa membayar cicilan yang paling awal.";
        } else {
            // Call stored procedure
            $stmt = $conn->prepare("CALL sp_bayar_pinjaman(?, ?, ?)");
            $stmt->bind_param("idd", $id_akun, $id_jadwal, $jumlah_bayar);
            if ($stmt->execute()) {
                // Refresh session user data
                $_SESSION['user'] = getUserById($id_akun, $conn);
                $success = "Berhasil membayar cicilan sebesar Rp" . number_format($jumlah_bayar);
            } else {
                $error = "Gagal melakukan pembayaran.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Pembayaran Pinjaman - UniBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(to right, #3e1e8c, #47217a);
            color: #fff;
        }
        .container {
            margin-top: 80px;
            max-width: 900px;
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
            color: #fff;
            text-decoration: underline;
            display: inline-block;
            margin-bottom: 15px;
        }
        table th, table td {
            vertical-align: middle;
        }
        .btn-bayar {
            min-width: 120px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-gradient sticky-top" style="background: linear-gradient(90deg, #4e2c90, #2c1e60);">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="#">UniBank</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNavDropdown">
            <ul class="navbar-nav gap-2">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="setor.php">Setor</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="tarik.php">Tarik</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="transfer.php">Transfer</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">Pinjaman</a>
                    <ul class="dropdown-menu dropdown-menu-end">
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
        <h3 class="mb-3">Pembayaran Cicilan Pinjaman</h3>
        <a href="dashboard.php" class="back-link">← Kembali ke Dashboard</a>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <h5>Jadwal Pembayaran Belum Lunas (Harus Bayar Yang Paling Awal)</h5>
        <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>Jumlah Bayar</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT j.id_jadwal, j.jumlah_bayar, j.tanggal_jatuh_tempo 
                    FROM jadwal_pembayaran_pinjaman j
                    JOIN pengajuan_pinjaman p ON j.id_pinjaman = p.id_pinjaman
                    WHERE p.id_akun = $id_akun AND j.status_pembayaran = 'Belum Lunas'
                    ORDER BY j.tanggal_jatuh_tempo ASC LIMIT 1";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td>Rp<?= number_format($row['jumlah_bayar'], 2) ?></td>
                    <td><?= htmlspecialchars($row['tanggal_jatuh_tempo']) ?></td>
                    <td>Belum Lunas</td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="id_jadwal" value="<?= $row['id_jadwal'] ?>">
                            <button type="submit" name="bayar_cicilan" class="btn btn-success btn-bayar">Bayar Sekarang</button>
                        </form>
                    </td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="4">Tidak ada jadwal pembayaran yang belum lunas.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>

        <h5>Semua Jadwal Pembayaran</h5>
        <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>Jumlah Bayar</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $stmt = $conn->prepare("SELECT jumlah_bayar, tanggal_jatuh_tempo, status_pembayaran FROM view_jadwal_pembayaran_pengguna WHERE id_akun = ? ORDER BY tanggal_jatuh_tempo ASC");
            $stmt->bind_param("i", $id_akun);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td>Rp<?= number_format($row['jumlah_bayar'], 2) ?></td>
                    <td><?= htmlspecialchars($row['tanggal_jatuh_tempo']) ?></td>
                    <td><?= htmlspecialchars($row['status_pembayaran']) ?></td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="3" class="text-center">Tidak ada jadwal pembayaran.</td>
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
