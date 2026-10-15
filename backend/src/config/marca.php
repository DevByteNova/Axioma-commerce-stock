<?php
// backend/src/config/marca.php
// Identidad de la tienda. Cambiar aquí el nombre se refleja en todo el sitio.

const MARCA_TIENDA = 'Pijamas Decare';
const MARCA_COLECCION = 'Pijamas y ropa exterior';
const MARCA_ESLOGAN = 'Transforma tus noches con estilo y comodidad';
const MARCA_INSTAGRAM = 'https://www.instagram.com/creacionesdecare/';

// Carpeta (relativa a la raíz del proyecto) donde se guardan las fotos subidas
const RUTA_FOTOS_PRODUCTOS = 'frontend/src/public/uploads/productos';

/**
 * Devuelve la ruta de la imagen de un producto. Si no tiene foto propia
 * se usa la ilustración de su categoría.
 */
function imagen_producto(array $producto): string
{
    $imagen = trim((string) ($producto['imagen'] ?? ''));
    if ($imagen !== '' && is_file(dirname(__DIR__, 3) . '/' . $imagen)) {
        return $imagen;
    }
    $slug = preg_replace('/[^a-z]/', '', (string) ($producto['categoria_slug'] ?? ''));
    $ilustracion = 'frontend/src/public/img/categorias/' . ($slug !== '' ? $slug : 'general') . '.svg';
    if (!is_file(dirname(__DIR__, 3) . '/' . $ilustracion)) {
        $ilustracion = 'frontend/src/public/img/categorias/general.svg';
    }
    return $ilustracion;
}

function precio_cop($valor): string
{
    return '$' . number_format((float) $valor, 0, ',', '.');
}
