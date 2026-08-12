<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexion.php';

$mensaje = "";

$checkCol = $conn->query("SHOW COLUMNS FROM productos LIKE 'imagen'");
if ($checkCol && $checkCol->num_rows === 0) {
    $conn->query("ALTER TABLE productos ADD COLUMN imagen VARCHAR(255) NULL DEFAULT NULL");
}

// Procesar nuevo producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_producto') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $origen = trim($_POST['origen'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $rating = floatval($_POST['rating'] ?? 5.0);
    $stock = intval($_POST['stock'] ?? 10);
    $imagen_path = null;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['imagen']['tmp_name'])) {
        $directorio = __DIR__ . '/img/uploads';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $info = pathinfo($_FILES['imagen']['name']);
        $ext = strtolower($info['extension'] ?? 'jpg');
        $nombreArchivo = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $info['filename'] ?? 'imagen') . '.' . $ext;
        $rutaDestino = $directorio . '/' . $nombreArchivo;

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            $imagen_path = 'img/uploads/' . $nombreArchivo;
        }
    }

    if (!empty($nombre) && $precio > 0) {
        $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, origen, precio, rating, stock, imagen) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssddis", $nombre, $descripcion, $origen, $precio, $rating, $stock, $imagen_path);
        if ($stmt->execute()) {
            $mensaje = "¡Producto '$nombre' agregado exitosamente a la base de datos!";
        } else {
            $mensaje = "Error al agregar producto: " . $conn->error;
        }
    } else {
        $mensaje = "Por favor ingresa un nombre y precio válido.";
    }
}

// Consultas
$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY id DESC");
$productos = $conn->query("SELECT * FROM productos ORDER BY id DESC");
$pedidos = $conn->query("SELECT p.*, u.nombre as cliente, u.correo FROM pedidos p LEFT JOIN usuarios u ON p.usuario_id = u.id ORDER BY p.id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Administración - Sistema Comida</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .admin-container {
      max-width: 1200px;
      margin: 30px auto;
      padding: 0 20px;
    }
    .admin-card {
      background: white;
      color: var(--accent);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 30px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.05);
    }
    .admin-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--accent-dark);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    .admin-table th, .admin-table td {
      padding: 12px;
      border-bottom: 1px solid #eee;
      text-align: left;
      color: var(--accent);
    }
    .admin-table th {
      background: #faf8f5;
      font-weight: 700;
      color: var(--accent-dark);
    }
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 16px;
    }
    .alert-success {
      background: #dcfce7;
      color: #15803d;
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 600;
    }
  </style>
</head>
<body class="catalog-page">
  <header class="navbar">
    <div class="navbar-content">
      <div class="navbar-brand">
        <div class="brand-logo">
          <svg width="32" height="32" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="22" r="8" fill="white"/>
            <path d="M 18 28 L 22 35 L 38 35 L 42 28 Z" fill="white"/>
            <rect x="16" y="35" width="28" height="3" fill="white"/>
          </svg>
        </div>
        <div>
          <h2>Panel de Administración</h2>
          <p>BASE DE DATOS: sistema_comida</p>
        </div>
      </div>

      <nav class="top-nav" style="margin:0;">
        <a href="menu.html">Menú</a>
        <a href="pedidos.php">Pedidos</a>
        <a href="admin.php" class="active">Administración</a>
      </nav>

      <div class="navbar-actions">
        <a href="logout.php" class="logout-btn" style="text-decoration:none; display:inline-block; text-align:center;">Salir</a>
      </div>
    </div>
  </header>

  <main class="admin-container">
    <h1>Gestión de Base de Datos (sistema_comida)</h1>

    <?php if (!empty($mensaje)): ?>
      <div class="alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <!-- AGREGAR NUEVO PRODUCTO -->
    <div class="admin-card">
      <div class="admin-title"> Agregar Nuevo Platillo al Catálogo</div>
      <form method="POST" action="admin.php" enctype="multipart/form-data">
        <input type="hidden" name="accion" value="crear_producto">
        <div class="form-grid">
          <div>
            <label>Nombre del Platillo</label>
            <input type="text" name="nombre" required placeholder="Ej. Ceviche Peruano">
          </div>
          <div>
            <label>País de Origen</label>
            <input type="text" name="origen" placeholder="Ej. Perú">
          </div>
          <div>
            <label>Precio ($)</label>
            <input type="number" step="0.01" name="precio" required placeholder="14.99">
          </div>
          <div>
            <label>Rating (1.0 - 5.0)</label>
            <input type="number" step="0.1" name="rating" value="4.8">
          </div>
          <div>
            <label>Stock Inicial</label>
            <input type="number" name="stock" value="50">
          </div>
        </div>
        <div style="margin-bottom:12px;">
          <label>Descripción</label>
          <input type="text" name="descripcion" placeholder="Breve descripción del plato típico...">
        </div>
        <div style="margin-bottom:16px;">
          <label>Imagen del platillo</label>
          <input type="file" name="imagen" accept="image/*">
        </div>
        <button type="submit" style="width: auto; padding: 12px 28px;">Guardar Platillo </button>
      </form>
    </div>

    <!-- TABLA DE PRODUCTOS -->
    <div class="admin-card">
      <div class="admin-title"> Platillos en la Base de Datos (`productos`)</div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Origen</th>
            <th>Precio</th>
            <th>Rating</th>
            <th>Stock</th>
            <th>Imagen</th>
            <th>Fecha Creación</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($productos && $productos->num_rows > 0): ?>
            <?php while ($prod = $productos->fetch_assoc()): ?>
              <tr>
                <td><strong>#<?php echo $prod['id']; ?></strong></td>
                <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                <td><?php echo htmlspecialchars($prod['origen'] ?? '-'); ?></td>
                <td>$<?php echo number_format($prod['precio'], 2); ?></td>
                <td>⭐ <?php echo $prod['rating']; ?></td>
                <td><?php echo $prod['stock']; ?> uds.</td>
                <td>
                  <?php if (!empty($prod['imagen'])): ?>
                    <img src="<?php echo htmlspecialchars($prod['imagen']); ?>" alt="Imagen de <?php echo htmlspecialchars($prod['nombre']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                  <?php else: ?>
                    <span style="color:#999;">Sin imagen</span>
                  <?php endif; ?>
                </td>
                <td><?php echo $prod['fecha_creacion']; ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="7">No hay productos registrados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- TABLA DE USUARIOS -->
    <div class="admin-card">
      <div class="admin-title"> Usuarios Registrados (`usuarios`)</div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre Completo</th>
            <th>Correo</th>
            <th>País</th>
            <th>Fecha Registro</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($usuarios && $usuarios->num_rows > 0): ?>
            <?php while ($u = $usuarios->fetch_assoc()): ?>
              <tr>
                <td><strong>#<?php echo $u['id']; ?></strong></td>
                <td><?php echo htmlspecialchars($u['nombre'] . ' ' . $u['primer_apellido'] . ' ' . ($u['segundo_apellido'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars($u['correo']); ?></td>
                <td><?php echo htmlspecialchars($u['pais'] ?? '-'); ?></td>
                <td><?php echo $u['fecha_registro']; ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="5">No hay usuarios registrados en la base de datos.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- TABLA DE PEDIDOS -->
    <div class="admin-card">
      <div class="admin-title">🛒 Historial Global de Pedidos (`pedidos`)</div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID Pedido</th>
            <th>Cliente</th>
            <th>Correo</th>
            <th>Subtotal</th>
            <th>Impuestos</th>
            <th>Total</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($pedidos && $pedidos->num_rows > 0): ?>
            <?php while ($ped = $pedidos->fetch_assoc()): ?>
              <tr>
                <td><strong>#<?php echo $ped['id']; ?></strong></td>
                <td><?php echo htmlspecialchars($ped['cliente'] ?? 'Invitado'); ?></td>
                <td><?php echo htmlspecialchars($ped['correo'] ?? '-'); ?></td>
                <td>$<?php echo number_format($ped['subtotal'], 2); ?></td>
                <td>$<?php echo number_format($ped['impuestos'], 2); ?></td>
                <td><strong>$<?php echo number_format($ped['total'], 2); ?></strong></td>
                <td><?php echo $ped['fecha']; ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="7">No se han realizado pedidos aún.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</body>
</html>
