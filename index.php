<?php
// index.php (Raíz del proyecto Axioma)

require_once __DIR__ . '/backend/src/config/security.php';
configure_secure_session();

// 1. Iniciar sesión
if (isset($_SESSION['usuario_rol'])) {
    $_SESSION['usuario_rol'] = ucfirst(strtolower(trim($_SESSION['usuario_rol'])));
}

function ensureVentasEstadoPedidoColumn(PDO $pdo): void
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM ventas LIKE 'estado_pedido'");
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE ventas ADD COLUMN estado_pedido VARCHAR(30) NOT NULL DEFAULT 'Pendiente' AFTER ID_PAGO");
    }
}

// Forzar pantalla de login para depuración: visita index.php?force_login=1
// HACK: Este bloque es un parche rápido para forzar la pantalla de login
// durante la depuración. No es una solución final; se deja temporalmente
// para acelerar pruebas de sesión. Eliminar o refactorizar en producción.
if (isset($_GET['force_login']) && $_GET['force_login'] == '1') {
    // Limpiar sesión sin destruir completamente (mantener compatibilidad)
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    // Reiniciar sesión vacía para evitar warnings posteriores
    session_start();
}

// 2. Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array(); 
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        ); 
    }
    session_destroy(); 
    header("Location: index.php?url=login"); 
    exit;
}

// 3. Activación de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 3.1 Manejo de guardar_venta (envío desde el dashboard)
$url = $_GET['url'] ?? '';
if ($url === 'guardar_venta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitud no válida. Actualiza la página e inténtalo de nuevo.');
    }
    require_once __DIR__ . '/backend/src/conexion.php';
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) {
        die("Error de conexión a la BD.");
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id_producto = intval($_POST['id_producto'] ?? 0);
    $id_cliente  = intval($_POST['id_cliente'] ?? 1);
    $id_pago     = intval($_POST['id_pago'] ?? 1);
    $cantidad    = intval($_POST['cantidad'] ?? 0);
    $id_vendedor = intval($_POST['id_vendedor'] ?? ($_SESSION['usuario_id'] ?? 1));

    if ($id_producto > 0 && $cantidad > 0) {
        try {
            ensureVentasEstadoPedidoColumn($pdo);
            $stmt = $pdo->prepare("SELECT PRECIO, STOCK FROM productos WHERE ID_PRODUCTO = ?");
            $stmt->execute([$id_producto]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($producto && $producto['STOCK'] >= $cantidad) {
                $total_venta = $producto['PRECIO'] * $cantidad;

                $pdo->beginTransaction();

                $sqlVenta = "INSERT INTO ventas (ID_PRODUCTO, ID_VENDEDOR, ID_CLIENTE, ID_PAGO, estado_pedido, cantidad, total, FECHA_VENTA) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmtVenta = $pdo->prepare($sqlVenta);
                $stmtVenta->execute([$id_producto, $id_vendedor, $id_cliente, $id_pago, 'Pagado', $cantidad, $total_venta]);

                $sqlStock = "UPDATE productos SET STOCK = STOCK - ? WHERE ID_PRODUCTO = ?";
                $stmtStock = $pdo->prepare($sqlStock);
                $stmtStock->execute([$cantidad, $id_producto]);

                $pdo->commit();

                // Redirigir según el rol del usuario que hizo la venta
                $redir = ($_SESSION['usuario_rol'] === 'Cliente') ? 'cliente_dashboard' : 'dashboard';
                header("Location: index.php?url=$redir&exito=venta_ok");
                exit();
            } else {
                die("Error: Stock insuficiente o producto no válido.");
            }
        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            die("Error al procesar la venta: " . $e->getMessage());
        }
    } else {
        die("Error: datos de venta inválidos.");
    }
}

// 3.2 Manejo de compra desde dashboard de cliente
if ($url === 'procesar_compra_cliente' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitud no válida. Actualiza la página e inténtalo de nuevo.');
    }
    // Verificar sesión y rol
    if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'Cliente') {
        header('Location: index.php?url=login');
        exit;
    }

    require_once __DIR__ . '/backend/src/conexion.php';
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) {
        die("Error de conexión a la BD.");
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id_producto = intval($_POST['id_producto'] ?? 0);
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $id_pago = intval($_POST['id_pago'] ?? 1);
    $id_cliente = intval($_SESSION['id_cliente'] ?? $_SESSION['usuario_id']);

    if ($id_producto <= 0 || $cantidad <= 0) {
        header('Location: index.php?url=cliente_dashboard&error=datos_invalidos');
        exit;
    }

    try {
        ensureVentasEstadoPedidoColumn($pdo);
        $stmt = $pdo->prepare("SELECT PRECIO, STOCK FROM productos WHERE ID_PRODUCTO = ?");
        $stmt->execute([$id_producto]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto || $producto['STOCK'] < $cantidad) {
            header('Location: index.php?url=cliente_dashboard&error=stock_insuficiente');
            exit;
        }

        $total = $producto['PRECIO'] * $cantidad;

        $pdo->beginTransaction();

        $sqlVenta = "INSERT INTO ventas (ID_PRODUCTO, ID_VENDEDOR, ID_CLIENTE, ID_PAGO, estado_pedido, cantidad, total, FECHA_VENTA) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmtVenta = $pdo->prepare($sqlVenta);
        // cliente compra directamente; no vendedor asociado
        // Algunas versiones del esquema no permiten NULL en ID_VENDEDOR,
        // usar 0 como valor por defecto para indicar "sin vendedor".
        // Usar un vendedor por defecto (id 1) si no hay vendedor asociado
        $id_vendedor_for_insert = 1;
        $stmtVenta->execute([$id_producto, $id_vendedor_for_insert, $id_cliente, $id_pago, 'Pagado', $cantidad, $total]);

        $sqlStock = "UPDATE productos SET STOCK = STOCK - ? WHERE ID_PRODUCTO = ?";
        $stmtStock = $pdo->prepare($sqlStock);
        $stmtStock->execute([$cantidad, $id_producto]);

        $pdo->commit();
        header('Location: index.php?url=cliente_dashboard&exito=compra_ok');
        exit;

    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        // HACK: Registrar detalle del error en un log temporal para depuración
        $logMsg = date('c') . " - Compra error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
        @file_put_contents(__DIR__ . '/tools/purchase_error.log', $logMsg, FILE_APPEND);
        header('Location: index.php?url=cliente_dashboard&error=compra_error');
        exit;
    }
}

// Sistema de rutas y matriz de permisos por roles
$route = isset($_GET['url']) ? $_GET['url'] : 'catalogo';
$rolActual = $_SESSION['usuario_rol'] ?? '';

// Definir qué roles tienen permiso para cada ruta (Añadido 'Cliente' a su respectivo dashboard)
$rutasPermitidas = [
    'login'               => ['Invitado', 'Administrador', 'Vendedor', 'Cliente'],
    'catalogo'            => ['Invitado', 'Administrador', 'Vendedor', 'Cliente'],
    'error'               => ['Invitado', 'Administrador', 'Vendedor', 'Cliente'],
    'registro'            => ['Invitado', 'Administrador', 'Vendedor', 'Cliente'],
    'nuevo_cliente'       => ['Administrador', 'Vendedor'],
    'editar_cliente'      => ['Administrador', 'Vendedor'],
    'eliminar_cliente'    => ['Administrador', 'Vendedor'],
    'dashboard'           => ['Administrador', 'Vendedor'],
    'dashboard_cliente'   => ['Cliente'],
    'cliente_dashboard'   => ['Cliente'], // Ruta exclusiva para clientes
    'mi_cuenta'           => ['Administrador', 'Vendedor', 'Cliente'],
    'clientes'            => ['Administrador', 'Vendedor'],
    'usuarios'            => ['Administrador'],
    'registro_vendedor'   => ['Administrador'],
    'registrar_pijamas'   => ['Administrador', 'Vendedor'],
    'registrar_pijama'    => ['Administrador', 'Vendedor'],
    'procesar_compra'     => ['Cliente'],
    'inventario'          => ['Administrador'],
    'lista_vendedores'    => ['Administrador'],
    'ver_inventario'      => ['Administrador', 'Vendedor', 'Cliente'], // Permitir ver stock si lo deseas
    'ver_carrito'         => ['Cliente'],
    'eliminar_del_carrito'=> ['Cliente'],
    'agregar_carrito'     => ['Cliente']
];

if ($route === 'eliminar_del_carrito' && isset($_GET['id'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $idEliminar = (string) $_GET['id'];
    if (isset($_SESSION['carrito'][$idEliminar])) {
        unset($_SESSION['carrito'][$idEliminar]);
    }
    header('Location: index.php?url=ver_carrito');
    exit;
}

if ($route === 'agregar_carrito' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Solo los clientes compran; el precio y el nombre se leen de la base de datos,
    // nunca del formulario, para que no puedan alterarse desde el navegador.
    if ($rolActual !== 'Cliente') {
        header('Location: index.php?url=login');
        exit;
    }

    require_once __DIR__ . '/backend/src/conexion.php';
    require_once __DIR__ . '/backend/src/models/Catalogo.php';
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) {
        die("Error de conexión a la BD.");
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id = (int) ($_POST['id_producto'] ?? 0);
    $cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));
    $volver = in_array($_POST['volver'] ?? '', ['catalogo', 'cliente_dashboard'], true) ? $_POST['volver'] : '';
    $producto = $id > 0 ? (new Catalogo($pdo))->obtener($id) : null;

    if (!$producto || (int) $producto['STOCK'] <= 0) {
        header('Location: index.php?url=' . ($volver ?: 'catalogo') . '&carrito=sin_stock#catalogo');
        exit;
    }

    $enCarrito = (int) ($_SESSION['carrito'][$id]['cantidad'] ?? 0);
    $_SESSION['carrito'][$id] = [
        'nombre' => $producto['nombre'],
        'precio' => (float) $producto['PRECIO'],
        'cantidad' => min($enCarrito + $cantidad, (int) $producto['STOCK']),
    ];

    header('Location: index.php?url=' . ($volver ? $volver . '&carrito=agregado#catalogo' : 'ver_carrito'));
    exit;
}

// Validación 1: Verificar si la ruta existe
if (!array_key_exists($route, $rutasPermitidas)) {
    http_response_code(404);
    $_GET['error'] = '404';
    require_once __DIR__ . '/frontend/src/pages/error.php';
    exit;
}

// Validación 2: Si la ruta no es pública (login, registro, error o catálogo), exigir autenticación
$rutasPublicas = ['login', 'registro', 'error', 'catalogo'];
if (!in_array($route, $rutasPublicas, true) && !isset($_SESSION['usuario_id'])) {
    header('Location: index.php?url=login');
    exit;
}

// Si ya está logueado y quiere ir al login, redirigirlo a su dashboard correspondiente según su rol
if ($route === 'login' && isset($_SESSION['usuario_id'])) {
    $destino = ($rolActual === 'Cliente') ? 'cliente_dashboard' : 'dashboard';
    header("Location: index.php?url=$destino");
    exit;
}

// Validación 3: Control de acceso por roles
if (!in_array($route, $rutasPublicas, true) && !in_array($rolActual, $rutasPermitidas[$route])) {
    http_response_code(403);
    $_GET['error'] = '403';
    require_once __DIR__ . '/frontend/src/pages/error.php';
    exit;
}

// 4. Cargador de vistas mediante switch adaptado
switch ($route) {
    case 'login':
        require_once __DIR__ . '/frontend/src/pages/login.php';
        break;

    case 'catalogo':
        require_once __DIR__ . '/frontend/src/pages/catalogo.php';
        break;

    case 'error':
        $codigoError = (int) ($_GET['error'] ?? 404);
        if (in_array($codigoError, [403, 404, 500], true)) {
            http_response_code($codigoError);
        } else {
            http_response_code(404);
            $_GET['error'] = '404';
        }
        require_once __DIR__ . '/frontend/src/pages/error.php';
        break;

    case 'dashboard':
        if ($rolActual === 'Vendedor') {
            require_once __DIR__ . '/frontend/src/pages/dashboard_vendedor.php';
        } else {
            require_once __DIR__ . '/frontend/src/pages/dashboard.php';
        }
        break;

    case 'mi_cuenta':
        require_once __DIR__ . '/frontend/src/pages/mi_cuenta.php';
        break;

    case 'dashboard_cliente':
    case 'cliente_dashboard':
        require_once __DIR__ . '/frontend/src/pages/clientes_dashboard.php';
        break;

    case 'ver_carrito':
        require_once __DIR__ . '/frontend/src/pages/carrito.php';
        break;

    case 'procesar_compra':
        require_once __DIR__ . '/frontend/src/pages/procesar_compra.php';
        break;

    case 'registro':
    case 'nuevo_cliente':
        require_once __DIR__ . '/frontend/src/pages/registro_cliente.php';
        break;

    case 'editar_cliente':
        require_once __DIR__ . '/frontend/src/pages/editar_cliente.php';
        break;

    case 'eliminar_cliente':
        require_once __DIR__ . '/frontend/src/pages/eliminar_cliente.php';
        break;

    case 'clientes':
        require_once __DIR__ . '/frontend/src/pages/clientes.php';
        break;

    case 'usuarios':
        require_once __DIR__ . '/frontend/src/pages/usuarios.php';
        break;

    case 'registro_vendedor':
        require_once __DIR__ . '/frontend/src/pages/registro_vendedor.php';
        break;

    case 'inventario':
        require_once __DIR__ . '/frontend/src/pages/inventario.php';
        break;

    case 'ver_inventario':
        require_once __DIR__ . '/frontend/src/pages/ver_inventario.php';
        break;

    case 'lista_vendedores':
        require_once __DIR__ . '/frontend/src/pages/lista_vendedores.php';
        break;

    case 'registrar_pijamas':
    case 'registrar_pijama':
        require_once __DIR__ . '/frontend/src/pages/registar_pijamas.php';
        break;

    default:
        require_once __DIR__ . '/frontend/src/pages/login.php';
        break;
}
?>