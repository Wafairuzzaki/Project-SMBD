<?php
include 'db.php';
include 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $conn->real_escape_string($_POST['nama']);
    $password = $conn->real_escape_string($_POST['password']);
    $nik = $conn->real_escape_string($_POST['nik']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    $no_hp = $conn->real_escape_string($_POST['no_hp']);
    $jenis_kelamin = $conn->real_escape_string($_POST['jenis_kelamin']);
    $saldo = intval($_POST['saldo']);

    if (strlen($nik) < 9) {
        $error = "NIK harus minimal 9 angka.";
    } elseif (!is_numeric($nik)) {
        $error = "NIK harus berupa angka.";
    } elseif ($saldo < 50000) {
        $error = "Saldo awal minimal Rp 50.000.";
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM akun_nasabah WHERE nik = ?");
        $stmt->bind_param("s", $nik);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $error = "NIK sudah terdaftar. Silakan gunakan NIK lain.";
        } else {
            $sql = "INSERT INTO akun_nasabah (nama, password, nik, alamat, no_hp, jenis_kelamin, saldo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $nama, $password, $nik, $alamat, $no_hp, $jenis_kelamin, $saldo);

            if ($stmt->execute()) {
                redirect('login.php');
            } else {
                $error = "Pendaftaran gagal. Silakan coba lagi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar - UniBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            background: linear-gradient(to right, #2b1055, #7597de);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(10px);
        }
        .form-control {
            background: rgba(255,255,255,0.8);
            border: none;
        }
        .btn-primary {
            background-color: #fff;
            color: #2b1055;
            font-weight: bold;
            border: none;
        }
        .btn-primary:hover {
            background-color: #2b1055;
            color: #fff;
        }
        h2 {
            margin-bottom: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="register-box">
        <h2>Daftar Akun</h2>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="post">
            <input type="text" name="nama" class="form-control mb-3" placeholder="Nama" required pattern=".*\S.*" title="Nama tidak boleh kosong atau hanya spasi">
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required pattern="^\S+$" title="Password tidak boleh mengandung spasi">
            <input type="text" name="nik" class="form-control mb-3" placeholder="NIK (minimal 9 angka)" pattern="\d{9,}" required>
            <textarea name="alamat" class="form-control mb-3" placeholder="Alamat" required></textarea>
            <input type="text" name="no_hp" class="form-control mb-3" placeholder="No HP (minimal 12 angka)" pattern="\d{12,}" title="No HP minimal 12 angka" required>
            <select name="jenis_kelamin" class="form-control mb-3" required>
                <option value="">Jenis Kelamin</option>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
            </select>
            <input type="number" name="saldo" class="form-control mb-3" placeholder="Saldo Awal (min 50000)" required>
            <button type="submit" class="btn btn-primary w-100">Daftar</button>
        </form>
        <p class="mt-3">Sudah punya akun? <a href="login.php" style="color: #fff; text-decoration: underline;">Login</a></p>
    </div>
</body>
</html>
