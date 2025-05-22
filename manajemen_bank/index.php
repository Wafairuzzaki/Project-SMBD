<?php include 'functions.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>UniBank - Sistem Manajemen Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            background: linear-gradient(to right, #2b1055, #7597de);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 30px;
        }
        .btn-custom {
            background-color: #fff;
            color: #2b1055;
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin: 10px;
            text-decoration: none;
        }
        .btn-custom:hover {
            background-color: #2b1055;
            color: #fff;
        }
    </style>
</head>
<body>
    <h1>Selamat Datang di <strong>UniBank</strong></h1>
    <div>
        <a href="login.php" class="btn-custom">Login</a>
        <a href="register.php" class="btn-custom">Daftar</a>
    </div>
</body>
</html>
