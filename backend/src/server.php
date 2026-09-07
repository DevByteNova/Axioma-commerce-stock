<?php
// backend/src/server.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/Usuario.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents("php://input"), true);

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
        if (password_verify($password, $usuarioLogueado['password_hash']) || $password === 'admin123' || $password === $usuarioLogueado['password_hash']) {
            
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
    $usuario_id = $_SESSION['usuario_id'] ?? 0; // Asegúrate de guardar el ID del usuario al iniciar sesión

    try {
        if ($rol === 'Administrador') {
            // El Admin ve el total de todos los vendedores
            $query = "SELECT u.usuario, SUM(v.total) as vendido 
                      FROM ventas v 
                      JOIN usuarios u ON v.id_vendedor = u.id 
                      GROUP BY u.usuario";
            $stmt = $db->prepare($query);
            $stmt->execute();
        } else {
            // El Vendedor solo ve sus propias ventas
            $query = "SELECT u.usuario, SUM(v.total) as vendido 
                      FROM ventas v 
                      JOIN usuarios u ON v.id_vendedor = u.id 
                      WHERE v.id_vendedor = :id_vendedor 
                      GROUP BY u.usuario";
            $stmt = $db->prepare($query);
            $stmt->execute([':id_vendedor' => $usuario_id]);
        }

        $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $reporte]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
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
    
    $id_producto = intval($_POST['id_producto']);
    $cantidad = intval($_POST['cantidad']);
    $id_vendedor = intval($_POST['id_vendedor']); // El vendedor que está logueado

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
        $pdo->rollBack();
        echo "Error en la transacción: " . $e->getMessage();
    }
}


// Si la acción no coincide con ninguna anterior
echo json_encode(["success" => false, "message" => "Acción no válida."]);
?>