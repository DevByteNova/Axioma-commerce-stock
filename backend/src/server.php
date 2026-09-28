<?php
// backend/src/server.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/models/Usuario.php';

configure_secure_session();

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    $data = [];
}

// ==========================================
// MÓDULO 1: LÓGICA DE AUTENTICACIÓN (LOGIN)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'login') {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? ''; 

    if (empty($username) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Todos los campos son obligatorios."]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    $usuarioModel = new Usuario($db);
    $usuarioLogueado = $usuarioModel->buscarPorUsername($username);

    if ($usuarioLogueado) {
        if (password_verify($password, $usuarioLogueado['password_hash'])) {
            session_regenerate_id(true);
            
            $_SESSION['usuario_id'] = $usuarioLogueado['id'];
            $_SESSION['usuario_name'] = $usuarioLogueado['usuario'];
            $_SESSION['id_cliente'] = $usuarioLogueado['ID_CLIENTE'];
            $_SESSION['id_vendedor'] = $usuarioLogueado['ID_VENDEDOR'];
            
            // Asignar el rol real normalizado (Cliente/Vendedor/Administrador)
            // HACK: Normalizamos el rol aquí como solución rápida para evitar
            // inconsistencias de mayúsculas/minúsculas entre la DB y el
            // enrutador en `index.php`. Idealmente la DB debería guardar
            // valores consistentes o existir una capa de autorización.
            $rawRol = isset($usuarioLogueado['rol']) ? $usuarioLogueado['rol'] : 'Vendedor';
            $normRol = ucfirst(strtolower(trim($rawRol)));
            // Asegurar valores esperados; fallback a Vendedor si no es reconocido
            $allowed = ['Cliente', 'Vendedor', 'Administrador'];
            if (!in_array($normRol, $allowed)) {
                $normRol = 'Vendedor';
            }
            $_SESSION['usuario_rol'] = $normRol;

            // Determinar destino según rol para el frontend
            $destino = ($_SESSION['usuario_rol'] === 'Cliente') ? 'cliente_dashboard' : 'dashboard';

            // Devolver información útil al cliente (rol y destino) además del mensaje
            echo json_encode([
                "success" => true,
                "message" => "Autenticación exitosa.",
                "rol" => $_SESSION['usuario_rol'],
                "id_cliente" => $_SESSION['id_cliente'] ?? null,
                "id_vendedor" => $_SESSION['id_vendedor'] ?? null,
                "destino" => $destino
            ]);
            exit;
        } else {
            echo json_encode(["success" => false, "message" => "Contraseña incorrecta."]);
            exit;
        }
    } else {
        echo json_encode(["success" => false, "message" => "El usuario no existe."]);
        exit;
    }
}

// ==========================================
// MÓDULO 2: REGISTRO GENERAL
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'register') {
    $nombre = $data['nombre'] ?? '';
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    // Normalizar rol entrante
    $rol = isset($data['rol']) ? ucfirst(strtolower(trim($data['rol']))) : 'Cliente';
    $allowedRoles = ['Cliente', 'Vendedor', 'Administrador'];
    if (!in_array($rol, $allowedRoles)) {
        $rol = 'Cliente';
    }

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Faltan datos requeridos."]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    try {
        $db->beginTransaction();

        $id_cliente = null;
        // Si se registra un Cliente, crear entrada en clientes y asociar
        if (strtolower($rol) === 'cliente') {
            $sqlCli = "INSERT INTO clientes (NOMBRE, APELLIDOS, email, DIRECCION, TELEFONO) VALUES (?, ?, ?, ?, ?)";
            $stmtCli = $db->prepare($sqlCli);
            $stmtCli->execute([
                $nombre,
                $data['apellidos'] ?? '',
                $email,
                $data['direccion'] ?? '',
                $data['telefono'] ?? ''
            ]);
            $id_cliente = $db->lastInsertId();
        }

        // Insertar en usuarios usando password_hash y columna correcta
        $queryUser = "INSERT INTO usuarios (usuario, email, password_hash, ID_CLIENTE, rol) VALUES (:user, :email, :pass, :id_cliente, :rol)";
        $stmtUser = $db->prepare($queryUser);
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmtUser->bindParam(':user', $username);
        $stmtUser->bindParam(':email', $email);
        $stmtUser->bindParam(':pass', $hashed_password);
        $stmtUser->bindParam(':id_cliente', $id_cliente);
        $stmtUser->bindParam(':rol', $rol);
        $stmtUser->execute();

        $db->commit();
        echo json_encode(["success" => true, "message" => "Registro de $rol completado."]);
    } catch (Exception $e) {
        if ($db && $db->inTransaction()) $db->rollBack();
        echo json_encode(["success" => false, "message" => "Error al registrar: " . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// MÓDULO 3: REGISTRO DE VENDEDOR (ADMIN ONLY)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'registrar_vendedor') {
    
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
        echo json_encode(["success" => false, "message" => "No tienes permisos de administrador."]);
        exit;
    }

    $nombre = $data['nombre'] ?? '';
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $telefono = $data['telefono'] ?? '';
    $direccion = $data['direccion'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($nombre) || empty($username) || empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Todos los campos obligatorios deben llenarse."]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    try {
        $db->beginTransaction();

        // 1. Insertar en usuarios (AQUÍ FALTABA EL PUNTO Y COMA AL FINAL)
        $queryUsuario = "INSERT INTO usuarios (usuario, email, password_hash, rol) VALUES (:user, :email, :pass, 'Vendedor')";
        $stmtUsuario = $db->prepare($queryUsuario);
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmtUsuario->execute([':user' => $username, ':email' => $email, ':pass' => $hashed_password]);

        // 2. Insertar en vendedor
        $queryVendedor = "INSERT INTO vendedor (NOMBRE, CORREO, TELEFONO, DIRECCION) VALUES (:nombre, :email, :telefono, :direccion)";
        $stmtVendedor = $db->prepare($queryVendedor);
        
        // Verificamos y enviamos los parámetros
        $params = [
            ':nombre' => $nombre,
            ':email' => $email,
            ':telefono' => !empty($telefono) ? $telefono : 'Sin teléfono', 
            ':direccion' => !empty($direccion) ? $direccion : 'Sin dirección' 
        ];
        
        $stmtVendedor->execute($params);

        $db->commit();
        echo json_encode(["success" => true, "message" => "Vendedor registrado exitosamente."]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["success" => false, "message" => "Error al registrar: " . $e->getMessage()]);
    }
    exit;
}
// ==========================================
// MÓDULO 4: REPORTE DE VENTAS (ADMIN Y VENDEDOR)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'obtener_reporte_ventas') {
    
    if (!isset($_SESSION['usuario_rol'])) {
        echo json_encode(["success" => false, "message" => "No autorizado."]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    $rol = $_SESSION['usuario_rol'];
    $id_vendedor = $_SESSION['id_vendedor'] ?? 0;
    $desde = trim((string) ($data['desde'] ?? ''));
    $hasta = trim((string) ($data['hasta'] ?? ''));
    $condicionesFecha = '';
    $parametrosFecha = [];

    if ($desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
        $condicionesFecha .= ' AND DATE(v.FECHA_VENTA) >= :desde';
        $parametrosFecha[':desde'] = $desde;
    }
    if ($hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
        $condicionesFecha .= ' AND DATE(v.FECHA_VENTA) <= :hasta';
        $parametrosFecha[':hasta'] = $hasta;
    }

    try {
        if ($rol === 'Administrador') {
            // El Admin ve el total agrupado por vendedor real.
            $query = "SELECT ved.NOMBRE AS usuario, COALESCE(SUM(v.total), 0) AS vendido
                      FROM ventas v 
                      JOIN vendedor ved ON v.ID_VENDEDOR = ved.ID_VENDEDOR
                      WHERE 1 = 1 $condicionesFecha
                      GROUP BY ved.ID_VENDEDOR, ved.NOMBRE
                      ORDER BY vendido DESC";
            $stmt = $db->prepare($query);
            $stmt->execute($parametrosFecha);
        } elseif ($rol === 'Vendedor' && $id_vendedor > 0) {
            // El Vendedor solo ve sus propias ventas.
            $query = "SELECT ved.NOMBRE AS usuario, COALESCE(SUM(v.total), 0) AS vendido
                      FROM ventas v 
                      JOIN vendedor ved ON v.ID_VENDEDOR = ved.ID_VENDEDOR
                      WHERE v.ID_VENDEDOR = :id_vendedor $condicionesFecha
                      GROUP BY ved.ID_VENDEDOR, ved.NOMBRE";
            $stmt = $db->prepare($query);
            $stmt->execute([':id_vendedor' => $id_vendedor] + $parametrosFecha);
        } else {
            echo json_encode(["success" => false, "message" => "Rol no autorizado para este reporte."]);
            exit;
        }

        $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $reporte]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
    exit;
}
// ==========================================
// MÓDULO: ACTUALIZAR ESTADO DE VENTA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'actualizar_estado_venta') {
    if (($_SESSION['usuario_rol'] ?? '') !== 'Administrador' || !verify_csrf_token($data['csrf_token'] ?? null)) {
        echo json_encode(["success" => false, "message" => "No autorizado."]);
        exit;
    }

    $id_venta = filter_var($data['id_venta'] ?? null, FILTER_VALIDATE_INT);
    $estadosPermitidos = ['Pendiente', 'Pagado', 'Enviado', 'Entregado', 'Cancelado'];
    $estado = trim((string) ($data['estado'] ?? ''));

    if (!$id_venta || !in_array($estado, $estadosPermitidos, true)) {
        echo json_encode(["success" => false, "message" => "Datos de estado inválidos."]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    $stmtColumn = $db->query("SHOW COLUMNS FROM ventas LIKE 'estado_pedido'");
    if ($stmtColumn->rowCount() === 0) {
        $db->exec("ALTER TABLE ventas ADD COLUMN estado_pedido VARCHAR(30) NOT NULL DEFAULT 'Pendiente' AFTER ID_PAGO");
    }
    $stmt = $db->prepare('UPDATE ventas SET estado_pedido = :estado WHERE ID_VENTAS = :id_venta');
    $stmt->execute([':estado' => $estado, ':id_venta' => $id_venta]);
    echo json_encode(["success" => true, "message" => "Estado actualizado."]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'exportar_reporte_ventas') {
    if (($_SESSION['usuario_rol'] ?? '') !== 'Administrador') {
        http_response_code(403);
        exit('No autorizado.');
    }

    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->query("SELECT ved.NOMBRE AS vendedor, COALESCE(SUM(v.total), 0) AS total_vendido
                        FROM ventas v
                        INNER JOIN vendedor ved ON v.ID_VENDEDOR = ved.ID_VENDEDOR
                        GROUP BY ved.ID_VENDEDOR, ved.NOMBRE
                        ORDER BY total_vendido DESC");

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte-ventas.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Vendedor', 'Total vendido']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [$row['vendedor'], $row['total_vendido']]);
    }
    fclose($output);
    exit;
}
// ==========================================
// MÓDULO 5: GESTION DE PRODUCTOS
// ==========================================
require_once __DIR__ . '/models/Producto.php';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'agregar_producto'){
    $database = new Database();
    $prod = new Producto($database->getConnection());

    if ($prod->agregar($data['descripcion'], $data['precio'], $data['stock'])){
        echo json_encode(["success" => true, "message" => "Producto agregado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al agregar producto."]);
    }
    exit;
}

// MODULO 6: LISTAR INVENTARIO
require_once __DIR__ . '/models/Producto.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_inventario') {
    $database = new Database();
    $prod = new Producto($database->getConnection());
    echo json_encode($prod->listarTodos());
    exit;
}

// Para transacciones

// En tu server.php (o el archivo que procesa las peticiones del backend)

// 1. Si la petición es para registrar una venta (POST)
if (isset($_POST['accion']) && $_POST['accion'] === 'registrar_venta') {
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo conectar a la base de datos.']);
        exit;
    }

    $id_producto = intval($_POST['id_producto'] ?? 0);
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $id_vendedor = intval($_POST['id_vendedor'] ?? 0);

    try {
        // Verificar stock y precio del producto
        $stmt = $pdo->prepare("SELECT PRECIO, STOCK FROM productos WHERE ID_PRODUCTO = ?");
        $stmt->execute([$id_producto]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            echo json_encode(['status' => 'error', 'mensaje' => 'El producto no existe.']);
            exit;
        }

        if ($producto['STOCK'] < $cantidad) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Stock insuficiente.']);
            exit;
        }

        $total_venta = $producto['PRECIO'] * $cantidad;

        // Iniciar transacción de base de datos
        $pdo->beginTransaction();

        // Registrar en la tabla ventas (según tu estructura de la BD)
        $sqlVenta = "INSERT INTO ventas (ID_PRODUCTO, ID_VENDEDOR, cantidad, total, FECHA_VENTA) VALUES (?, ?, ?, ?, NOW())";
        $stmtVenta = $pdo->prepare($sqlVenta);
        $stmtVenta->execute([$id_producto, $id_vendedor, $cantidad, $total_venta]);

        // Descontar el stock del producto
        $sqlStock = "UPDATE productos SET STOCK = STOCK - ? WHERE ID_PRODUCTO = ?";
        $stmtStock = $pdo->prepare($sqlStock);
        $stmtStock->execute([$cantidad, $id_producto]);

        $pdo->commit();

        // Redireccionar de vuelta al dashboard o retornar éxito
        header("Location: index.php?url=dashboard&exito=venta_ok");
        exit();

    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "Error en la transacción: " . $e->getMessage();
    }
}

// ==========================================
// MÓDULO 7: REGISTRO DE VENTA (TRANSACCIÓN)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'registrar_venta') {
    
    if (!isset($_SESSION['usuario_rol']) || ($_SESSION['usuario_rol'] !== 'Vendedor' && $_SESSION['usuario_rol'] !== 'Administrador')) {
        echo json_encode(["success" => false, "message" => "No autorizado."]);
        exit;
    }

    $id_producto = intval($data['id_producto'] ?? 0);
    $cantidad = intval($data['cantidad'] ?? 0);
    $id_vendedor = $_SESSION['id_vendedor'] ?? ($_SESSION['usuario_id'] ?? 0);

    if ($id_producto <= 0 || $cantidad <= 0) {
        echo json_encode(["success" => false, "message" => "Datos de venta inválidos."]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    try {
        $db->beginTransaction();

        // Verificar stock y precio
        $stmt = $db->prepare("SELECT PRECIO, STOCK FROM productos WHERE ID_PRODUCTO = ?");
        $stmt->execute([$id_producto]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            echo json_encode(["success" => false, "message" => "El producto no existe."]);
            exit;
        }

        if (intval($producto['STOCK']) < $cantidad) {
            echo json_encode(["success" => false, "message" => "Stock insuficiente."]);
            exit;
        }

        $total_venta = floatval($producto['PRECIO']) * $cantidad;

        // Registrar venta
        $sqlVenta = "INSERT INTO ventas (ID_PRODUCTO, ID_VENDEDOR, cantidad, total, FECHA_VENTA) VALUES (?, ?, ?, ?, NOW())";
        $stmtVenta = $db->prepare($sqlVenta);
        $stmtVenta->execute([$id_producto, $id_vendedor, $cantidad, $total_venta]);

        // Descontar stock
        $sqlStock = "UPDATE productos SET STOCK = STOCK - ? WHERE ID_PRODUCTO = ?";
        $stmtStock = $db->prepare($sqlStock);
        $stmtStock->execute([$cantidad, $id_producto]);

        $db->commit();
        echo json_encode(["success" => true, "message" => "Venta registrada exitosamente."]);

    } catch (Exception $e) {
        if ($db && $db->inTransaction()) $db->rollBack();
        echo json_encode(["success" => false, "message" => "Error en la transacción: " . $e->getMessage()]);
    }
    exit;
}
// ==========================================
// MÓDULO: PROCESAR PEDIDO Y TRANSACCIÓN SEGURA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'procesar_pedido') {
    
    // Verificar que el usuario sea Cliente autenticado
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Cliente') {
        echo json_encode(["success" => false, "message" => "Acción no autorizada."]);
        exit;
    }

    $items = $data['items'] ?? [];
    $metodo_pago = trim($data['metodo_pago'] ?? 'Efectivo');
    $id_cliente = $_SESSION['id_cliente'] ?? null;

    if (empty($items) || !$id_cliente) {
        echo json_encode(["success" => false, "message" => "Datos de compra incompletos o sesión de cliente inválida."]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    try {
        // 1. Iniciar transacción estricta
        $db->beginTransaction();
        $total_general = 0;
        $detalles_validados = [];

        foreach ($items as $item) {
            $id_prod = intval($item['id']);
            $cant = intval($item['cantidad']);

            if ($cant <= 0) continue;

            // 2. Consultar precio y stock real bloqueando la fila para evitar concurrencia (Race Condition)
            $stmtProd = $db->prepare("SELECT PRECIO, STOCK, DESCRIPCION FROM productos WHERE ID_PRODUCTO = ? FOR UPDATE");
            $stmtProd->execute([$id_prod]);
            $prodInfo = $stmtProd->fetch(PDO::FETCH_ASSOC);

            if (!$prodInfo) {
                throw new Exception("El producto con ID $id_prod no existe.");
            }

            if (intval($prodInfo['STOCK']) < $cant) {
                throw new Exception("Stock insuficiente para el producto: " . $prodInfo['DESCRIPCION']);
            }

            $precio_unitario = floatval($prodInfo['PRECIO']);
            $subtotal = $precio_unitario * $cant;
            $total_general += $subtotal;

            $detalles_validados[] = [
                'id_producto' => $id_prod,
                'cantidad' => $cant,
                'precio_unitario' => $precio_unitario,
                'subtotal' => $subtotal
            ];
        }

        // 3. Insertar el pedido principal en la tabla `pedido` o `ventas`
        $sqlPedido = "INSERT INTO pedido (ID_CLIENTE, TOTAL, METODO_PAGO, ESTADO, FECHA) VALUES (?, ?, ?, 'Completado', NOW())";
        $stmtPed = $db->prepare($sqlPedido);
        $stmtPed->execute([$id_cliente, $total_general]);
        $id_pedido = $db->lastInsertId();

        // 4. Insertar detalles e ir descontando el inventario de forma segura
        foreach ($detalles_validados as $det) {
            $sqlDetalle = "INSERT INTO detalle_pedido (ID_PEDIDO, ID_PRODUCTO, CANTIDAD, PRECIO_UNITARIO, SUBTOTAL) VALUES (?, ?, ?, ?, ?)";
            $stmtDet = $db->prepare($sqlDetalle);
            $stmtDet->execute([$id_pedido, $det['id_producto'], $det['cantidad'], $det['precio_unitario'], $det['subtotal']]);

            $sqlStock = "UPDATE productos SET STOCK = STOCK - ? WHERE ID_PRODUCTO = ?";
            $stmtStock = $db->prepare($sqlStock);
            $stmtStock->execute([$det['cantidad'], $det['id_producto']]);
        }

        // Todo correcto, confirmamos cambios en la base de datos
        $db->commit();
        echo json_encode(["success" => true, "id_pedido" => $id_pedido, "message" => "Compra procesada exitosamente."]);

    } catch (Exception $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(["success" => false, "message" => "Error en la transacción: " . $e->getMessage()]);
    }
    exit;
}
// Si la acción no coincide con ninguna anterior
echo json_encode(["success" => false, "message" => "Acción no válida."]);
?>
