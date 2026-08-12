<?php
header("Content-Type: application/json; charset=UTF-8");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexion.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    $data = $_POST;
}

$user = trim($data['user'] ?? $data['correo'] ?? '');
$pass = trim($data['password'] ?? $data['contraseña'] ?? '');

if (empty($user) || empty($pass)) {
    echo json_encode([
        "success" => false,
        "message" => "Por favor ingresa usuario/correo y contraseña."
    ]);
    exit();
}

$stmt = $conn->prepare("SELECT id, nombre, primer_apellido, correo, contraseña FROM usuarios WHERE correo = ? OR nombre = ?");
$stmt->bind_param("ss", $user, $user);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $hash = $row['contraseña'];
    $valid = password_verify($pass, $hash) || ($pass === $hash);
    
    if ($valid) {
        $_SESSION['usuario_id'] = $row['id'];
        $_SESSION['usuario_nombre'] = $row['nombre'];
        $_SESSION['usuario_correo'] = $row['correo'];

        echo json_encode([
            "success" => true,
            "message" => "Inicio de sesión exitoso.",
            "usuario" => [
                "id" => $row['id'],
                "nombre" => $row['nombre'],
                "correo" => $row['correo']
            ]
        ]);
        exit();
    }
}

echo json_encode([
    "success" => false,
    "message" => "Usuario o contraseña incorrectos."
]);
