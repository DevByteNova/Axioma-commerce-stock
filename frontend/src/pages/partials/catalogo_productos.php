<?php
/**
 * Catálogo de pijamas con filtros (categoría, talla, precio, búsqueda y orden).
 *
 * Variables esperadas:
 *   $pdo          PDO conectado a Axioma_BD
 *   $rutaCatalogo Ruta del enrutador donde se muestra ('catalogo' o 'cliente_dashboard')
 *   $puedeComprar true si el usuario es Cliente y puede agregar al carrito
 */
require_once __DIR__ . '/../../../../backend/src/config/marca.php';
require_once __DIR__ . '/../../../../backend/src/models/Catalogo.php';

$catalogo = new Catalogo($pdo);
$filtros = Catalogo::leerFiltros($_GET);

try {
    $categorias = $catalogo->categorias();
    $productos = $catalogo->buscar($filtros);
    $precioTope = max(10000, (int) (ceil($catalogo->precioMaximo() / 10000) * 10000));
    $errorCatalogo = '';
} catch (PDOException $e) {
    error_log('Catálogo: ' . $e->getMessage());
    $categorias = [];
    $productos = [];
    $precioTope = 100000;
    $errorCatalogo = 'No fue posible cargar el catálogo. Verifique que la base de datos tenga la migración database/migracion_catalogo.sql.';
}

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// Construye la URL del catálogo cambiando solo algunos filtros
$urlFiltro = static function (array $cambios) use ($filtros, $rutaCatalogo): string {
    $params = array_filter(array_merge($filtros, $cambios), static fn($v) => $v !== '' && $v !== 0 && $v !== 'recientes');
    return 'index.php?' . http_build_query(['url' => $rutaCatalogo] + $params) . '#catalogo';
};
$hayFiltros = $filtros['q'] !== '' || $filtros['categoria'] !== '' || $filtros['talla'] !== '' || $filtros['precio_max'] > 0;
?>
<section id="catalogo" class="catalogo">
    <div class="catalogo__cabecera">
        <div>
            <span class="catalogo__eyebrow"><?php echo $e(MARCA_COLECCION); ?></span>
            <h2 class="catalogo__titulo">Escoge tu pijama</h2>
            <p class="catalogo__sub">Filtra por categoría, talla y precio. Todas las prendas se consultan en tiempo real desde el inventario.</p>
        </div>
    </div>

    <?php if (($_GET['carrito'] ?? '') === 'agregado'): ?>
        <div class="alert alert-success d-flex justify-content-between align-items-center" role="status">
            <span><i class="fa-solid fa-circle-check me-2"></i>Producto agregado al carrito.</span>
            <a href="index.php?url=ver_carrito" class="btn btn-sm btn-marca">Ver carrito</a>
        </div>
    <?php elseif (($_GET['carrito'] ?? '') === 'sin_stock'): ?>
        <div class="alert alert-warning" role="status">Ese producto ya no tiene unidades disponibles.</div>
    <?php endif; ?>

    <?php if ($errorCatalogo !== ''): ?>
        <div class="alert alert-warning"><?php echo $e($errorCatalogo); ?></div>
    <?php endif; ?>

    <nav class="catalogo__categorias" aria-label="Categorías">
        <a href="<?php echo $e($urlFiltro(['categoria' => ''])); ?>"
           class="chip <?php echo $filtros['categoria'] === '' ? 'chip--activo' : ''; ?>">Todas</a>
        <?php foreach ($categorias as $cat): ?>
            <a href="<?php echo $e($urlFiltro(['categoria' => $cat['slug']])); ?>"
               class="chip <?php echo $filtros['categoria'] === $cat['slug'] ? 'chip--activo' : ''; ?>">
                <?php echo $e($cat['nombre']); ?> <span class="chip__num"><?php echo (int) $cat['total']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <form class="catalogo__filtros" method="GET" action="index.php#catalogo">
        <input type="hidden" name="url" value="<?php echo $e($rutaCatalogo); ?>">
        <?php if ($filtros['categoria'] !== ''): ?>
            <input type="hidden" name="categoria" value="<?php echo $e($filtros['categoria']); ?>">
        <?php endif; ?>

        <label class="filtro filtro--busqueda">
            <span class="filtro__label">Buscar</span>
            <input type="search" name="q" value="<?php echo $e($filtros['q']); ?>" placeholder="Ej: satín, franela, osito…" class="form-control">
        </label>

        <fieldset class="filtro">
            <legend class="filtro__label">Talla</legend>
            <div class="tallas">
                <label class="talla"><input type="radio" name="talla" value="" <?php echo $filtros['talla'] === '' ? 'checked' : ''; ?>><span>Todas</span></label>
                <?php foreach (Catalogo::TALLAS as $t): ?>
                    <label class="talla"><input type="radio" name="talla" value="<?php echo $t; ?>" <?php echo $filtros['talla'] === $t ? 'checked' : ''; ?>><span><?php echo $t; ?></span></label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <label class="filtro">
            <span class="filtro__label">Precio máximo: <output id="precioSalida"><?php echo precio_cop($filtros['precio_max'] ?: $precioTope); ?></output></span>
            <input type="range" class="form-range" name="precio_max" min="10000" max="<?php echo $precioTope; ?>" step="5000"
                   value="<?php echo $filtros['precio_max'] ?: $precioTope; ?>" id="precioRango">
        </label>

        <label class="filtro">
            <span class="filtro__label">Ordenar por</span>
            <select name="orden" class="form-select">
                <option value="recientes" <?php echo $filtros['orden'] === 'recientes' ? 'selected' : ''; ?>>Más recientes</option>
                <option value="precio_asc" <?php echo $filtros['orden'] === 'precio_asc' ? 'selected' : ''; ?>>Menor precio</option>
                <option value="precio_desc" <?php echo $filtros['orden'] === 'precio_desc' ? 'selected' : ''; ?>>Mayor precio</option>
                <option value="nombre" <?php echo $filtros['orden'] === 'nombre' ? 'selected' : ''; ?>>Nombre (A-Z)</option>
            </select>
        </label>

        <div class="filtro filtro--acciones">
            <button type="submit" class="btn btn-marca">Aplicar filtros</button>
            <?php if ($hayFiltros): ?>
                <a href="index.php?url=<?php echo $e($rutaCatalogo); ?>#catalogo" class="btn btn-link">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <p class="catalogo__conteo">
        <?php echo count($productos); ?> <?php echo count($productos) === 1 ? 'pijama encontrada' : 'pijamas encontradas'; ?>
    </p>

    <?php if (empty($productos) && $errorCatalogo === ''): ?>
        <div class="catalogo__vacio">
            <p>No encontramos pijamas con esos filtros.</p>
            <a href="index.php?url=<?php echo $e($rutaCatalogo); ?>#catalogo" class="btn btn-marca-outline">Ver todo el catálogo</a>
        </div>
    <?php endif; ?>

    <div class="catalogo__grid">
        <?php foreach ($productos as $p):
            $id = (int) $p['ID_PRODUCTO'];
            $stock = (int) $p['STOCK'];
            $imagen = imagen_producto($p);
        ?>
            <article class="producto">
                <button type="button" class="producto__foto" data-bs-toggle="modal" data-bs-target="#producto-<?php echo $id; ?>"
                        aria-label="Ver detalle de <?php echo $e($p['nombre']); ?>">
                    <img src="<?php echo $e($imagen); ?>" alt="<?php echo $e($p['nombre']); ?>" loading="lazy">
                    <?php if ($stock <= 5): ?><span class="producto__aviso">¡Últimas <?php echo $stock; ?>!</span><?php endif; ?>
                </button>
                <div class="producto__cuerpo">
                    <span class="producto__categoria"><?php echo $e($p['categoria'] ?? 'Sin categoría'); ?></span>
                    <h3 class="producto__nombre"><?php echo $e($p['nombre']); ?></h3>
                    <div class="producto__meta">
                        <span class="producto__precio"><?php echo precio_cop($p['PRECIO']); ?></span>
                        <span class="producto__talla">Talla <?php echo $e($p['talla']); ?></span>
                    </div>
                    <button type="button" class="btn btn-marca-outline w-100" data-bs-toggle="modal" data-bs-target="#producto-<?php echo $id; ?>">
                        Ver detalle
                    </button>
                </div>
            </article>

            <div class="modal fade" id="producto-<?php echo $id; ?>" tabindex="-1" aria-labelledby="producto-<?php echo $id; ?>-titulo" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content detalle">
                        <button type="button" class="btn-close detalle__cerrar" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        <div class="detalle__grid">
                            <img class="detalle__foto" src="<?php echo $e($imagen); ?>" alt="<?php echo $e($p['nombre']); ?>">
                            <div class="detalle__info">
                                <span class="producto__categoria"><?php echo $e($p['categoria'] ?? 'Sin categoría'); ?></span>
                                <h3 id="producto-<?php echo $id; ?>-titulo" class="detalle__nombre"><?php echo $e($p['nombre']); ?></h3>
                                <p class="detalle__precio"><?php echo precio_cop($p['PRECIO']); ?></p>
                                <p class="detalle__descripcion"><?php echo $e($p['DESCRIPCION']); ?></p>
                                <dl class="detalle__datos">
                                    <div><dt>Talla</dt><dd><?php echo $e($p['talla']); ?></dd></div>
                                    <div><dt>Disponibles</dt><dd><?php echo $stock; ?> unidades</dd></div>
                                </dl>

                                <?php if ($puedeComprar): ?>
                                    <form action="index.php?url=agregar_carrito" method="POST" class="detalle__compra">
                                        <input type="hidden" name="id_producto" value="<?php echo $id; ?>">
                                        <input type="hidden" name="volver" value="<?php echo $e($rutaCatalogo); ?>">
                                        <label class="filtro__label" for="cantidad-<?php echo $id; ?>">Cantidad</label>
                                        <div class="detalle__fila">
                                            <input type="number" id="cantidad-<?php echo $id; ?>" name="cantidad" value="1" min="1" max="<?php echo $stock; ?>" class="form-control detalle__cantidad" required>
                                            <button type="submit" class="btn btn-marca flex-grow-1">
                                                <i class="fa-solid fa-cart-plus me-1"></i> Agregar al carrito
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <a href="index.php?url=login" class="btn btn-marca w-100">Inicia sesión para comprar</a>
                                    <p class="detalle__nota">¿No tienes cuenta? <a href="index.php?url=registro">Regístrate aquí</a>.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
    (function () {
        const rango = document.getElementById('precioRango');
        const salida = document.getElementById('precioSalida');
        if (!rango || !salida) return;
        const formato = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
        rango.addEventListener('input', () => { salida.textContent = formato.format(rango.value); });
    })();
</script>
