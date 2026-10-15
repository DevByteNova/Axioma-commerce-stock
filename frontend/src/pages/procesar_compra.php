<?php
/**
 * Módulo de Pago Integrado (Tarjeta y PSE)
 * Tienda Axioma
 */

require_once __DIR__ . '/../../../backend/src/config/security.php';
configure_secure_session();

// 1. Validar que sea un cliente logueado
$rolActualCliente = strtolower(trim((string) ($_SESSION['usuario_rol'] ?? '')));
if (!isset($_SESSION['usuario_id']) || $rolActualCliente !== 'cliente') {
    header("Location: index.php?url=login");
    exit();
}

// 2. Validar que el carrito no esté vacío
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    header("Location: index.php?url=ver_carrito&error=" . urlencode("Tu carrito está vacío. Agrega productos antes de pagar."));
    exit();
}

// Cargar la conexión a la base de datos
require_once __DIR__ . '/../../../backend/src/conexion.php';
$db = new Database();
$conexion = $db->getConnection();

if (!$conexion) {
    die('No se pudo conectar a la base de datos.');
}

$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
ensureEstadoPedidoColumn($conexion);

$id_cliente = $_SESSION['id_cliente'] ?? $_SESSION['usuario_id'] ?? 1;
$stmt_cliente = $conexion->prepare('SELECT DIRECCION FROM clientes WHERE ID_CLIENTE = ?');
$stmt_cliente->execute([$id_cliente]);
$direccionCliente = (string) ($stmt_cliente->fetchColumn() ?: '');

// 3. Procesar el formulario cuando el cliente hace clic en "Pagar Ahora"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pago'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header("Location: index.php?url=procesar_compra&error=" . urlencode("La sesión del formulario expiró. Inténtalo de nuevo."));
        exit();
    }

    $direccion_entrega = trim((string) ($_POST['direccion_entrega'] ?? ''));
    if ($direccion_entrega === '' || mb_strlen($direccion_entrega) < 8 || mb_strlen($direccion_entrega) > 255) {
        header("Location: index.php?url=procesar_compra&error=" . urlencode("Ingresa una dirección de entrega válida (entre 8 y 255 caracteres)."));
        exit();
    }
    
    $id_pago = intval($_POST['id_pago']);
    $id_vendedor = 1;
    $fecha_actual = date('Y-m-d H:i:s');
    $fecha_solo = date('Y-m-d');

    // Validar que el método de pago exista en la base de datos
    $stmt_check = $conexion->prepare("SELECT ID_PAGO, nombre_metodo FROM metodos_pago WHERE ID_PAGO = :id_pago AND estado = 1");
    $stmt_check->execute([':id_pago' => $id_pago]);
    $metodo_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$metodo_data) {
        header("Location: index.php?url=procesar_compra&error=" . urlencode("El método de pago seleccionado no es válido."));
        exit();
    }

    // Validaciones específicas según el método de pago
    $nombre_metodo = strtolower($metodo_data['nombre_metodo']);
    if (strpos($nombre_metodo, 'tarjeta') !== false) {
        $nombre_tarjeta = trim($_POST['nombre_tarjeta'] ?? '');
        $num_tarjeta = preg_replace('/\D+/', '', $_POST['num_tarjeta'] ?? '');
        $exp_tarjeta = trim($_POST['exp_tarjeta'] ?? '');
        $cvv_tarjeta = preg_replace('/\D+/', '', $_POST['cvv_tarjeta'] ?? '');

        if ($nombre_tarjeta === '' || !preg_match('/^[\p{L} .\'-]{3,80}$/u', $nombre_tarjeta)) {
            header("Location: index.php?url=procesar_compra&error=" . urlencode("Escribe el nombre del titular tal como aparece en la tarjeta."));
            exit();
        }
        if (strlen($num_tarjeta) !== 16 || !numeroTarjetaValido($num_tarjeta)) {
            header("Location: index.php?url=procesar_compra&error=" . urlencode("El número de tarjeta debe tener 16 dígitos válidos."));
            exit();
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $exp_tarjeta)) {
            header("Location: index.php?url=procesar_compra&error=" . urlencode("La fecha debe tener el formato MM/AA."));
            exit();
        }
        [$mes_expiracion, $ano_expiracion] = array_map('intval', explode('/', $exp_tarjeta));
        $ahora = new DateTimeImmutable('first day of this month');
        $expira = DateTimeImmutable::createFromFormat('!Y-m-d', sprintf('20%02d-%02d-01', $ano_expiracion, $mes_expiracion));
        if (!$expira || $expira < $ahora) {
            header("Location: index.php?url=procesar_compra&error=" . urlencode("La tarjeta ya está vencida."));
            exit();
        }
        if (strlen($cvv_tarjeta) !== 3 && strlen($cvv_tarjeta) !== 4) {
            header("Location: index.php?url=procesar_compra&error=" . urlencode("El CVV debe tener 3 o 4 dígitos."));
            exit();
        }
    } elseif (strpos($nombre_metodo, 'pse') !== false) {
        $banco_pse = trim($_POST['banco_pse'] ?? '');
        if (empty($banco_pse)) {
            header("Location: index.php?url=procesar_compra&error=" . urlencode("Debe seleccionar un banco para el pago por PSE."));
            exit();
        }
    }

    // Iniciar Transacción SQL para asegurar consistencia (Factura, Ventas y Stock)
    $conexion->beginTransaction();

    try {
        ensureDireccionEntregaColumn($conexion);
        $stmt_direccion = $conexion->prepare('UPDATE clientes SET DIRECCION = ? WHERE ID_CLIENTE = ?');
        $stmt_direccion->execute([$direccion_entrega, $id_cliente]);

        // Registrar cabecera en 'facturas'
        $sql_factura = "INSERT INTO facturas (FECHA, ID_CLIENTE, DIRECCION_ENTREGA) VALUES (?, ?, ?)";
        $stmt_fac = $conexion->prepare($sql_factura);
        $stmt_fac->execute([$fecha_solo, $id_cliente, $direccion_entrega]);
        $id_factura_generada = (int) $conexion->lastInsertId();

        $estado_pedido = 'Pagado';

        // Registrar detalle en 'ventas' y actualizar stock de productos
        foreach ($_SESSION['carrito'] as $id_producto => $item) {
            $cantidad = is_array($item) ? intval($item['cantidad']) : intval($item);
            $precio_unitario = is_array($item) ? floatval($item['precio']) : 0;
            
            if ($precio_unitario <= 0) {
                $stmt_p = $conexion->prepare("SELECT precio FROM productos WHERE ID_PRODUCTO = ?");
                $stmt_p->execute([$id_producto]);
                if ($row_p = $stmt_p->fetch(PDO::FETCH_ASSOC)) {
                    $precio_unitario = floatval($row_p['PRECIO']);
                }
            }

            $total_linea = $cantidad * $precio_unitario;

            $sql_venta = "INSERT INTO ventas (ID_PRODUCTO, cantidad, total, FECHA_VENTA, ID_VENDEDOR, ID_CLIENTE, ID_PAGO, estado_pedido) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_venta = $conexion->prepare($sql_venta);
            $stmt_venta->execute([$id_producto, $cantidad, $total_linea, $fecha_actual, $id_vendedor, $id_cliente, $id_pago, $estado_pedido]);

            $sql_stock = "UPDATE productos SET STOCK = STOCK - ? WHERE ID_PRODUCTO = ?";
            $stmt_stock = $conexion->prepare($sql_stock);
            $stmt_stock->execute([$cantidad, $id_producto]);
        }

        // Confirmar transacción y vaciar carrito
        $conexion->commit();
        unset($_SESSION['carrito']);

        header("Location: index.php?url=dashboard_cliente&compra=exitoso&factura=" . $id_factura_generada);
        exit();

    } catch (Exception $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        header("Location: index.php?url=procesar_compra&error=" . urlencode("Error al procesar la compra: " . $e->getMessage()));
        exit();
    }
}

function ensureEstadoPedidoColumn(PDO $conexion): void
{
    $stmt = $conexion->prepare("SHOW COLUMNS FROM ventas LIKE 'estado_pedido'");
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        $conexion->exec("ALTER TABLE ventas ADD COLUMN estado_pedido VARCHAR(30) NOT NULL DEFAULT 'Pendiente' AFTER ID_PAGO");
    }
}

function ensureDireccionEntregaColumn(PDO $conexion): void
{
    $stmt = $conexion->prepare("SHOW COLUMNS FROM facturas LIKE 'DIRECCION_ENTREGA'");
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        $conexion->exec("ALTER TABLE facturas ADD COLUMN DIRECCION_ENTREGA VARCHAR(255) NULL AFTER ID_CLIENTE");
    }
}

function numeroTarjetaValido(string $numero): bool
{
    $suma = 0;
    $duplicar = false;

    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $digito = (int) $numero[$i];
        if ($duplicar) {
            $digito *= 2;
            if ($digito > 9) {
                $digito -= 9;
            }
        }
        $suma += $digito;
        $duplicar = !$duplicar;
    }

    return $suma % 10 === 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra | Pijamas Decare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5">
                    
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary fs-4">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark">Pasarela de Pagos</h3>
                            <p class="text-muted small mb-0">Selecciona tu medio de pago y completa los datos de forma segura.</p>
                        </div>
                    </div>

                    <!-- Alertas de Error -->
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="index.php?url=procesar_compra" method="POST" id="formPago">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="mb-4">
                            <label for="direccion_entrega" class="form-label fw-semibold">Dirección de entrega</label>
                            <textarea name="direccion_entrega" id="direccion_entrega" class="form-control" rows="2" maxlength="255" minlength="8" placeholder="Ej. Calle 10 # 20-30, apartamento 402" autocomplete="street-address" required><?php echo htmlspecialchars($direccionCliente, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <div class="form-text">Puedes cambiarla para esta compra. La dirección quedará guardada en tu historial.</div>
                        </div>
                        
                        <!-- Selector de Método de Pago (Cargado dinámicamente de la BD) -->
                        <div class="mb-4">
                            <label for="id_pago" class="form-label fw-semibold">Método de Pago:</label>
                            <select name="id_pago" id="id_pago" class="form-select form-select-lg" required onchange="toggleMetodoPago(this)">
                                <option value="">Seleccione una opción...</option>
                                <?php
                                try {
                                    $stmt_m = $conexion->query("SELECT ID_PAGO, nombre_metodo FROM metodos_pago WHERE estado = 1 ORDER BY ID_PAGO");
                                    while ($metodo = $stmt_m->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . (int) $metodo['ID_PAGO'] . '" data-nombre="' . htmlspecialchars(strtolower($metodo['nombre_metodo']), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($metodo['nombre_metodo']) . '</option>';
                                    }
                                } catch (Exception $e) {
                                    echo '<option value="" disabled>Error al cargar métodos</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Sección Dinámica: Tarjeta de Crédito / Débito -->
                        <div id="seccionTarjeta" class="p-3 bg-white border rounded-3 mb-4 shadow-sm" style="display: none;">
                            <h6 class="fw-bold text-primary mb-3"><i class="fa-regular fa-credit-card me-1"></i> Información de Tarjeta</h6>
                            <div class="mb-3">
                                <label for="nombre_tarjeta" class="form-label small text-muted">Nombre del titular</label>
                                <input type="text" name="nombre_tarjeta" id="nombre_tarjeta" class="form-control" placeholder="Como aparece en la tarjeta" autocomplete="cc-name" maxlength="80">
                            </div>
                            <div class="mb-3">
                                <label for="num_tarjeta" class="form-label small text-muted">Número de la Tarjeta <span id="marcaTarjeta" class="fw-semibold"></span></label>
                                <input type="text" name="num_tarjeta" id="num_tarjeta" class="form-control" placeholder="0000 0000 0000 0000" inputmode="numeric" autocomplete="cc-number" maxlength="19" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label small text-muted">Fecha de Expiración</label>
                                    <input type="text" name="exp_tarjeta" class="form-control" placeholder="MM/AA" inputmode="numeric" autocomplete="cc-exp" maxlength="5" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">CVV</label>
                                    <input type="password" name="cvv_tarjeta" class="form-control" placeholder="123" inputmode="numeric" autocomplete="cc-csc" maxlength="4" required>
                                </div>
                            </div>
                        </div>

                        <!-- Sección Dinámica: PSE (Pago Seguro en Línea) -->
                        <div id="seccionPse" class="p-3 bg-white border rounded-3 mb-4 shadow-sm" style="display: none;">
                            <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-building-columns me-1"></i> PSE - Pago Seguro</h6>
                            <div class="mb-3">
                                <label class="form-label small text-muted">Seleccione su Banco</label>
                                <select name="banco_pse" class="form-select">
                                    <option value="">Seleccione banco...</option>
                                    <option value="Bancolombia">Bancolombia</option>
                                    <option value="Davivienda">Davivienda</option>
                                    <option value="BBVA">BBVA Colombia</option>
                                    <option value="Banco de Bogotá">Banco de Bogotá</option>
                                    <option value="Nequi">Nequi</option>
                                    <option value="Daviplata">Daviplata</option>
                                </select>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="index.php?url=ver_carrito" class="btn btn-outline-secondary px-4 rounded-pill">
                                <i class="fa-solid fa-arrow-left me-1"></i> Volver al Carrito
                            </a>
                            <button type="submit" class="btn btn-success px-5 rounded-pill fw-semibold shadow-sm">
                                <i class="fa-solid fa-check me-1"></i> Pagar Ahora
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Script JavaScript para alternar campos visuales según el select -->
    <script>
        const numeroTarjeta = document.getElementById('num_tarjeta');
        const marcaTarjeta = document.getElementById('marcaTarjeta');
        const fechaTarjeta = document.querySelector('[name="exp_tarjeta"]');
        const camposTarjeta = document.querySelectorAll('#seccionTarjeta input');

        numeroTarjeta.addEventListener('input', function () {
            const digitos = this.value.replace(/\D/g, '').slice(0, 16);
            this.value = digitos.replace(/(.{4})/g, '$1 ').trim();
            marcaTarjeta.textContent = detectarMarca(digitos);
        });

        fechaTarjeta.addEventListener('input', function () {
            const digitos = this.value.replace(/\D/g, '').slice(0, 4);
            this.value = digitos.length > 2 ? digitos.slice(0, 2) + '/' + digitos.slice(2) : digitos;
        });

        function detectarMarca(numero) {
            if (/^4/.test(numero)) {
                return 'Visa';
            }
            if (/^(5[1-5]|2[2-7])/.test(numero)) {
                return 'Mastercard';
            }
            if (/^3[47]/.test(numero)) {
                return 'American Express';
            }
            if (/^6(?:011|5)/.test(numero)) {
                return 'Discover';
            }
            return numero.length >= 2 ? 'Marca no reconocida' : '';
        }

        function toggleMetodoPago(selectEl) {
            const opcion = selectEl.options[selectEl.selectedIndex];
            const nombre = opcion.getAttribute('data-nombre') || '';

            const seccionTarjeta = document.getElementById('seccionTarjeta');
            const seccionPse = document.getElementById('seccionPse');

            seccionTarjeta.style.display = 'none';
            seccionPse.style.display = 'none';
            camposTarjeta.forEach(function (campo) {
                campo.disabled = true;
            });

            if (nombre.includes('tarjeta')) {
                seccionTarjeta.style.display = 'block';
                camposTarjeta.forEach(function (campo) {
                    campo.disabled = false;
                });
            } else if (nombre.includes('pse')) {
                seccionPse.style.display = 'block';
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>