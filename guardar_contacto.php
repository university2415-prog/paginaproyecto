<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método no permitido.';
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

if ($nombre === '' || $correo === '' || $mensaje === '') {
    header('Location: contacto.html?status=error');
    exit;
}

$stmt = $conn->prepare('INSERT INTO contactos (nombre, correo, mensaje) VALUES (?, ?, ?)');
if (!$stmt) {
    error_log('Error preparando consulta contactos: ' . $conn->error);
    header('Location: contacto.html?status=error');
    exit;
}

$stmt->bind_param('sss', $nombre, $correo, $mensaje);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    header('Location: contacto.php?status=success');
    exit;
}

error_log('Error guardando contacto: ' . $conn->error);
header('Location: contacto.php?status=error');
exit;
