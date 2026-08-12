<?php
header("Content-Type: application/json; charset=UTF-8");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexion.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['items']) || empty($data['items'])) {
    echo json_encode([
        "success" => false,
        "message" => "El carrito está vacío o faltan datos del pedido."
    ]);
    exit();
}

$usuario_id = $_SESSION['usuario_id'] ?? 1; // ID por defecto si no está logueado
$items      = $data['items'];
$subtotal   = floatval($data['subtotal'] ?? 0);
$impuestos  = floatval($data['impuestos'] ?? 0);
$total      = floatval($data['total'] ?? 0);

// Iniciar transacción
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO pedidos (usuario_id, subtotal, impuestos, total) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iddd", $usuario_id, $subtotal, $impuestos, $total);
    $stmt->execute();
    $pedido_id = $stmt->insert_id;

    $stmt_det = $conn->prepare("INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");

    foreach ($items as $item) {
        $producto_id = intval($item['producto_id'] ?? 1);
        $cantidad    = intval($item['cantidad'] ?? 1);
        $precio      = floatval($item['precio'] ?? 0);

        // Si se envió sólo el nombre del producto, buscar id por nombre
        if ($producto_id <= 0 && !empty($item['nombre'])) {
            $stmt_p = $conn->prepare("SELECT id FROM productos WHERE nombre = ? LIMIT 1");
            $stmt_p->bind_param("s", $item['nombre']);
            $stmt_p->execute();
            $res_p = $stmt_p->get_result();
            if ($row_p = $res_p->fetch_assoc()) {
                $producto_id = $row_p['id'];
            } else {
                $producto_id = 1;
            }
        }

        $stmt_det->bind_param("iiid", $pedido_id, $producto_id, $cantidad, $precio);
        $stmt_det->execute();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Pedido guardado con éxito.",
        "pedido_id" => $pedido_id
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "message" => "Error al guardar el pedido: " . $e->getMessage()
    ]);
}
