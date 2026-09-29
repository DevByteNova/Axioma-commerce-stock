<?php
// backend/src/models/Catalogo.php
// Consultas del catálogo público de pijamas (filtros, categorías y detalle).

class Catalogo
{
    public const TALLAS = ['S', 'M', 'L', 'XL'];
    public const ORDENES = [
        'recientes'   => 'p.ID_PRODUCTO DESC',
        'precio_asc'  => 'p.PRECIO ASC',
        'precio_desc' => 'p.PRECIO DESC',
        'nombre'      => 'p.nombre ASC',
    ];

    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /** Categorías con el número de productos disponibles en cada una. */
    public function categorias(): array
    {
        $sql = "SELECT c.ID_CATEGORIA, c.nombre, c.slug, c.descripcion,
                       COUNT(p.ID_PRODUCTO) AS total
                FROM categorias c
                LEFT JOIN productos p ON p.ID_CATEGORIA = c.ID_CATEGORIA AND p.STOCK > 0
                GROUP BY c.ID_CATEGORIA, c.nombre, c.slug, c.descripcion
                ORDER BY c.ID_CATEGORIA";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Normaliza los filtros recibidos por GET.
     * Claves: q, categoria (slug), talla, precio_max, orden.
     */
    public static function leerFiltros(array $entrada): array
    {
        $talla = strtoupper(trim((string) ($entrada['talla'] ?? '')));
        $orden = (string) ($entrada['orden'] ?? 'recientes');
        $precioMax = (int) ($entrada['precio_max'] ?? 0);

        return [
            'q'          => mb_substr(trim((string) ($entrada['q'] ?? '')), 0, 60),
            'categoria'  => preg_replace('/[^a-z]/', '', strtolower((string) ($entrada['categoria'] ?? ''))),
            'talla'      => in_array($talla, self::TALLAS, true) ? $talla : '',
            'precio_max' => $precioMax > 0 ? $precioMax : 0,
            'orden'      => array_key_exists($orden, self::ORDENES) ? $orden : 'recientes',
        ];
    }

    /** Productos con stock que cumplen los filtros. */
    public function buscar(array $filtros): array
    {
        $where = ['p.STOCK > 0'];
        $params = [];

        if ($filtros['q'] !== '') {
            $where[] = '(p.nombre LIKE :q OR p.DESCRIPCION LIKE :q2)';
            $params[':q'] = '%' . $filtros['q'] . '%';
            $params[':q2'] = '%' . $filtros['q'] . '%';
        }
        if ($filtros['categoria'] !== '') {
            $where[] = 'c.slug = :categoria';
            $params[':categoria'] = $filtros['categoria'];
        }
        if ($filtros['talla'] !== '') {
            $where[] = 'p.talla = :talla';
            $params[':talla'] = $filtros['talla'];
        }
        if ($filtros['precio_max'] > 0) {
            $where[] = 'p.PRECIO <= :precio_max';
            $params[':precio_max'] = $filtros['precio_max'];
        }

        $sql = "SELECT p.ID_PRODUCTO, p.nombre, p.DESCRIPCION, p.PRECIO, p.STOCK, p.talla, p.imagen,
                       c.nombre AS categoria, c.slug AS categoria_slug
                FROM productos p
                LEFT JOIN categorias c ON c.ID_CATEGORIA = p.ID_CATEGORIA
                WHERE " . implode(' AND ', $where) . "
                ORDER BY " . self::ORDENES[$filtros['orden']];

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Precio más alto del catálogo, para el control deslizante del filtro. */
    public function precioMaximo(): int
    {
        return (int) ceil((float) $this->conn->query("SELECT COALESCE(MAX(PRECIO), 0) FROM productos WHERE STOCK > 0")->fetchColumn());
    }

    public function obtener(int $idProducto): ?array
    {
        $stmt = $this->conn->prepare("SELECT ID_PRODUCTO, nombre, PRECIO, STOCK FROM productos WHERE ID_PRODUCTO = ?");
        $stmt->execute([$idProducto]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        return $producto ?: null;
    }
}
