<?php
session_start();

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['nik'] === 'admin';
}

function getUserById($id, $conn) {
    $stmt = $conn->prepare("SELECT * FROM akun_nasabah WHERE id_akun = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Fungsi tambahan untuk notifikasi
function set_notification($message, $type = 'success') {
    $_SESSION['notification'] = ['message' => $message, 'type' => $type];
}

function get_notification() {
    if (isset($_SESSION['notification'])) {
        $notif = $_SESSION['notification'];
        unset($_SESSION['notification']);
        return $notif;
    }
    return null;
}

function showSuccessMessage($message) {
    echo "<div class='alert alert-success'>$message</div>";
}
?>