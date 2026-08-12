
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión directa para XAMPP local (ajusta si tu usuario/contraseña difieren)
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'sistema_comida';

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    $message = "Error de conexión al servidor MySQL: " . $conn->connect_error;
    if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "success" => false,
            "message" => $message
        ]);
        exit();
    }
    die($message);
}

if (!$conn->select_db($db)) {
    $createDbSql = "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$conn->query($createDbSql)) {
        die("No se pudo crear la base de datos '$db': " . $conn->error);
    }
    if (!$conn->select_db($db)) {
        die("No se pudo seleccionar la base de datos '$db': " . $conn->error);
    }
}

$conn->set_charset("utf8mb4");

$createUsuariosTable = "
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    primer_apellido VARCHAR(255) DEFAULT NULL,
    segundo_apellido VARCHAR(255) DEFAULT NULL,
    correo VARCHAR(255) NOT NULL UNIQUE,
    contraseña VARCHAR(255) NOT NULL,
    pais VARCHAR(100) DEFAULT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

$createProductosTable = "
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    origen VARCHAR(100) DEFAULT NULL,
    precio DECIMAL(10,2) DEFAULT 0,
    rating DECIMAL(3,1) DEFAULT 0,
    stock INT DEFAULT 0,
    imagen VARCHAR(255) DEFAULT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

$createPedidosTable = "
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    subtotal DECIMAL(10,2) DEFAULT 0,
    impuestos DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) DEFAULT 0,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

$createPedidoDetalleTable = "
CREATE TABLE IF NOT EXISTS pedido_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT DEFAULT 1,
    precio_unitario DECIMAL(10,2) DEFAULT 0,
    INDEX(pedido_id),
    INDEX(producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

foreach ([$createUsuariosTable, $createProductosTable, $createPedidosTable, $createPedidoDetalleTable] as $query) {
    if (!$conn->query($query)) {
        error_log("Error creando tabla: " . $conn->error);
    }
}

$createContactosTable = "
CREATE TABLE IF NOT EXISTS contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    primer_apellido VARCHAR(255) DEFAULT NULL,
    segundo_apellido VARCHAR(255) DEFAULT NULL,
    correo VARCHAR(255) NOT NULL,
    contraseña VARCHAR(255) DEFAULT NULL,
    pais VARCHAR(100) DEFAULT NULL,
    mensaje TEXT DEFAULT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (!$conn->query($createContactosTable)) {
    error_log("Error creando tabla contactos: " . $conn->error);
} else {
    $removeDuplicateContactsSql = "
        DELETE c1 FROM contactos c1
        INNER JOIN contactos c2
          ON c1.id > c2.id
         AND COALESCE(c1.nombre,'') = COALESCE(c2.nombre,'')
         AND COALESCE(c1.correo,'') = COALESCE(c2.correo,'')
         AND COALESCE(c1.mensaje,'') = COALESCE(c2.mensaje,'')
    ";
    if (!$conn->query($removeDuplicateContactsSql)) {
        error_log("Error eliminando duplicados de contactos: " . $conn->error);
    }
}

function ensureColumnExists($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows === 0) {
        $alter = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if (!$conn->query($alter)) {
            error_log("Error agregando columna $column a $table: " . $conn->error);
        }
    }
}

// Asegurarse de que la tabla `contactos` tenga las columnas usadas por registro.php
$requiredContactCols = [
    'primer_apellido' => "VARCHAR(255) DEFAULT NULL",
    'segundo_apellido' => "VARCHAR(255) DEFAULT NULL",
    'contraseña' => "VARCHAR(255) DEFAULT NULL",
    'pais' => "VARCHAR(100) DEFAULT NULL"
];

foreach ($requiredContactCols as $col => $definition) {
    ensureColumnExists($conn, 'contactos', $col, $definition);
}

$requiredUsuariosCols = [
    'correo' => "VARCHAR(255) NOT NULL UNIQUE",
    'contraseña' => "VARCHAR(255) NOT NULL",
    'pais' => "VARCHAR(100) DEFAULT NULL",
    'fecha_registro' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];
foreach ($requiredUsuariosCols as $col => $definition) {
    ensureColumnExists($conn, 'usuarios', $col, $definition);
}

$requiredProductosCols = [
    'descripcion' => "TEXT DEFAULT NULL",
    'origen' => "VARCHAR(100) DEFAULT NULL",
    'precio' => "DECIMAL(10,2) DEFAULT 0",
    'rating' => "DECIMAL(3,1) DEFAULT 0",
    'stock' => "INT DEFAULT 0",
    'imagen' => "VARCHAR(255) DEFAULT NULL",
    'fecha_creacion' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];
foreach ($requiredProductosCols as $col => $definition) {
    ensureColumnExists($conn, 'productos', $col, $definition);
}

$requiredPedidosCols = [
    'usuario_id' => "INT DEFAULT NULL",
    'subtotal' => "DECIMAL(10,2) DEFAULT 0",
    'impuestos' => "DECIMAL(10,2) DEFAULT 0",
    'total' => "DECIMAL(10,2) DEFAULT 0",
    'fecha' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];
foreach ($requiredPedidosCols as $col => $definition) {
    ensureColumnExists($conn, 'pedidos', $col, $definition);
}

$requiredPedidoDetalleCols = [
    'pedido_id' => "INT NOT NULL",
    'producto_id' => "INT NOT NULL",
    'cantidad' => "INT DEFAULT 1",
    'precio_unitario' => "DECIMAL(10,2) DEFAULT 0"
];
foreach ($requiredPedidoDetalleCols as $col => $definition) {
    ensureColumnExists($conn, 'pedido_detalle', $col, $definition);
}

?>
