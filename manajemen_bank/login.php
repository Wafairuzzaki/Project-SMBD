<?php
include 'db.php';
include 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik = $conn->real_escape_string($_POST['nik']);
    $password = $conn->real_escape_string($_POST['password']);

    if ($nik === 'admin' && $password === '123') {
        $_SESSION['user'] = ['nik' => 'admin', 'nama' => 'Admin'];
        redirect('admin.php');
    } else {
        $sql = "SELECT * FROM akun_nasabah WHERE nik = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nik, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['status_akun'] === 'NonAktif') {
                $error = "Akun Anda telah dinonaktifkan.";
            } else {
                $_SESSION['user'] = $user;
                redirect('dashboard.php');
            }
        } else {
            $error = "NIK atau Password salah.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - UniBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #2b1055, #7597de);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
        }
        .login-form {
            flex: 1;
            padding: 40px;
        }
        .login-form h2 {
            color: #2b1055;
            margin-bottom: 30px;
        }
        .welcome-side {
            flex: 1;
            background: linear-gradient(to bottom right, #2b1055, #6f42c1, #1a1a40);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 50px;
        }
        .welcome-side h1 {
            font-size: 2.5rem;
        }
        .welcome-side p {
            font-size: 1.2rem;
            margin-top: 15px;
            text-align: center;
        }
        .form-control {
            border-radius: 12px;
        }
        .btn-primary {
            background-color: #2b1055;
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background-color: #3f2d6f;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-form">
        <h2>Login to UniBank</h2>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="post">
            <div class="mb-3">
                <label for="nik" class="form-label">NIK</label>
                <input type="text" class="form-control" id="nik" name="nik" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">LOGIN</button>
        </form>
        <p class="mt-3">Belum punya akun? <a href="register.php">Daftar</a></p>
    </div>
    <div class="welcome-side">
        <h1>Welcome to <strong>UniBank</strong></h1>
        <p>Log in to access your account securely and easily.</p>
    </div>
</div>

</body>
</html>
