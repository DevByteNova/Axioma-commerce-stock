<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_rol']) || !in_array($_SESSION['usuario_rol'], ['Administrador', 'Vendedor'], true)) {
    header("Location: index.php?url=login");
    exit;
}

require_once __DIR__ . '/../../../backend/src/conexion.php';
require_once __DIR__ . '/../../../backend/src/config/marca.php';
require_once __DIR__ . '/../../../backend/src/models/Catalogo.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mensaje = "";
$error = "";

try {
    $categorias = $db->query("SELECT ID_CATEGORIA, nombre FROM categorias ORDER BY ID_CATEGORIA")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
    $error = "Falta la tabla de categorías. Ejecute database/migracion_catalogo.sql en phpMyAdmin.";
}

/**
 * Guarda la foto subida y devuelve su ruta relativa, o null si no se envió.
 * Lanza RuntimeException si el archivo no es válido.
 */
function guardar_foto_producto(array $archivo): ?string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("No se pudo subir la foto (código {$archivo['error']}).");
    }
    if ($archivo['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException("La foto supera el tamaño máximo de 2 MB.");
    }

    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    if (!isset($permitidos[$tipo])) {
        throw new RuntimeException("La foto debe ser JPG, PNG o WEBP.");
    }

    $carpeta = dirname(__DIR__, 3) . '/' . RUTA_FOTOS_PRODUCTOS;
    if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true)) {
        throw new RuntimeException("No se pudo crear la carpeta de fotos.");
    }

    $nombreArchivo = bin2hex(random_bytes(8)) . '.' . $permitidos[$tipo];
    if (!move_uploaded_file($archivo['tmp_name'], $carpeta . '/' . $nombreArchivo)) {
        throw new RuntimeException("No se pudo guardar la foto.");
    }
    return RUTA_FOTOS_PRODUCTOS . '/' . $nombreArchivo;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $talla = trim($_POST['talla'] ?? '');
    $precio = (float) ($_POST['precio'] ?? 0);
    $stock = (int) ($_POST['stock'] ?? -1);
    $idCategoria = (int) ($_POST['categoria'] ?? 0);
    $categoriaValida = in_array($idCategoria, array_map('intval', array_column($categorias, 'ID_CATEGORIA')), true);

    if ($nombre === '' || $descripcion === '' || !in_array($talla, Catalogo::TALLAS, true) || !$categoriaValida) {
        $error = "Por favor completa todos los campos.";
    } elseif ($precio <= 0 || $stock < 0) {
        $error = "El precio debe ser mayor que cero y el stock no puede ser negativo.";
    } else {
        try {
            $imagen = guardar_foto_producto($_FILES['imagen'] ?? []);
            $query = "INSERT INTO productos (nombre, DESCRIPCION, talla, PRECIO, STOCK, ID_CATEGORIA, imagen)
                      VALUES (:nombre, :descripcion, :talla, :precio, :stock, :categoria, :imagen)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':talla' => $talla,
                ':precio' => $precio,
                ':stock' => $stock,
                ':categoria' => $idCategoria,
                ':imagen' => $imagen,
            ]);
            $mensaje = "¡Pijama registrada exitosamente! Ya aparece en el catálogo.";
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (Exception $e) {
            $error = "Error al registrar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pijama | <?php echo htmlspecialchars(MARCA_TIENDA); ?></title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
</head>
<body>
    <div class="container product-form-container">
        <div class="header-actions">
            <h1>Registrar Nueva Pijama</h1>
            <a href="index.php?url=dashboard" class="btn btn-primary">← Volver al Dashboard</a>
        </div>

        <div class="card product-form-card">
            <?php if (!empty($mensaje)): ?>
                <div class="alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="index.php?url=registrar_pijamas" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nombre">Nombre o Modelo de la Pijama:</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="255" placeholder="Ej: Pijama de franela ositos">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <input type="text" id="descripcion" name="descripcion" required maxlength="250" placeholder="Ej: Pijama de franela con estampado de ositos">
                </div>

                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo (int) $cat['ID_CATEGORIA']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="talla">Talla:</label>
                    <select id="talla" name="talla" required>
                        <option value="">Seleccione una talla</option>
                        <?php foreach (Catalogo::TALLAS as $t): ?>
                            <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="precio">Precio ($):</label>
                    <input type="number" step="100" min="100" id="precio" name="precio" required placeholder="Ej: 45000">
                </div>

                <div class="form-group">
                    <label for="stock">Cantidad en Stock:</label>
                    <input type="number" min="0" id="stock" name="stock" required placeholder="0">
                </div>

                <div class="form-group">
                    <label for="imagen">Foto de la pijama (opcional, JPG/PNG/WEBP, máx. 2 MB):</label>
                    <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
                </div>

                <button type="submit" class="btn btn-primary full-width-button">Guardar Pijama</button>
            </form>
        </div>
    </div>
</body>
</html>
