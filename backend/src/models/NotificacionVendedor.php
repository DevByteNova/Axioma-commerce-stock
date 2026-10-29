<?php
// backend/src/models/NotificacionVendedor.php

class NotificacionVendedor
{
    private static function rutaArchivo(int $idVendedor): string
    {
        if ($idVendedor < 1) {
            throw new InvalidArgumentException('El vendedor de la notificación no es válido.');
        }

        return dirname(__DIR__, 2) . '/storage/notificaciones_vendedor/' . $idVendedor . '.json';
    }

    private static function actualizarArchivo(int $idVendedor, callable $actualizar)
    {
        $ruta = self::rutaArchivo($idVendedor);
        $directorio = dirname($ruta);

        if (!is_dir($directorio) && !mkdir($directorio, 0770, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo crear el almacenamiento de notificaciones.');
        }

        $archivo = fopen($ruta, 'c+');
        if ($archivo === false) {
            throw new RuntimeException('No se pudo abrir el almacenamiento de notificaciones.');
        }

        $bloqueado = false;
        try {
            if (!flock($archivo, LOCK_EX)) {
                throw new RuntimeException('No se pudo bloquear el almacenamiento de notificaciones.');
            }
            $bloqueado = true;

            rewind($archivo);
            $contenido = stream_get_contents($archivo);
            if ($contenido === false) {
                throw new RuntimeException('No se pudieron leer las notificaciones del vendedor.');
            }

            $notificaciones = $contenido === ''
                ? []
                : json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($notificaciones)) {
                throw new RuntimeException('El almacenamiento de notificaciones contiene datos no válidos.');
            }

            $resultado = $actualizar($notificaciones);
            $json = json_encode(array_values($notificaciones), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            rewind($archivo);
            if (!ftruncate($archivo, 0)) {
                throw new RuntimeException('No se pudieron guardar las notificaciones del vendedor.');
            }
            $bytesEscritos = 0;
            $longitud = strlen($json);
            while ($bytesEscritos < $longitud) {
                $bytes = fwrite($archivo, substr($json, $bytesEscritos));
                if ($bytes === false || $bytes === 0) {
                    throw new RuntimeException('No se pudieron guardar las notificaciones del vendedor.');
                }
                $bytesEscritos += $bytes;
            }
            if (!fflush($archivo)) {
                throw new RuntimeException('No se pudieron guardar las notificaciones del vendedor.');
            }

            return $resultado;
        } finally {
            if ($bloqueado) {
                flock($archivo, LOCK_UN);
            }
            fclose($archivo);
        }
    }

    public static function registrarNuevoPedido(int $idVendedor, array $pedido): void
    {
        $idFactura = filter_var($pedido['ID_FACTURA'] ?? null, FILTER_VALIDATE_INT);
        if (!$idFactura || $idFactura < 1) {
            throw new InvalidArgumentException('La factura de la notificación no es válida.');
        }

        $notificacion = [
            'ID_FACTURA' => $idFactura,
            'FECHA_CREACION' => (string) ($pedido['FECHA_CREACION'] ?? date('Y-m-d H:i:s')),
            'cliente' => trim((string) ($pedido['cliente'] ?? '')) ?: 'Cliente',
            'lineas_pedido' => max(0, (int) ($pedido['lineas_pedido'] ?? 0)),
            'total_pedido' => max(0, (float) ($pedido['total_pedido'] ?? 0)),
        ];

        self::actualizarArchivo($idVendedor, static function (array &$notificaciones) use ($notificacion): void {
            foreach ($notificaciones as $existente) {
                if ((int) ($existente['ID_FACTURA'] ?? 0) === $notificacion['ID_FACTURA']) {
                    return;
                }
            }

            $notificaciones[] = $notificacion;
        });
    }

    public static function obtenerNoLeidas(int $idVendedor): array
    {
        return self::actualizarArchivo($idVendedor, static function (array &$notificaciones): array {
            usort(
                $notificaciones,
                static fn(array $a, array $b): int => strcmp(
                    (string) ($b['FECHA_CREACION'] ?? ''),
                    (string) ($a['FECHA_CREACION'] ?? '')
                )
            );
            return $notificaciones;
        });
    }

    public static function marcarLeidas(int $idVendedor): void
    {
        self::actualizarArchivo($idVendedor, static function (array &$notificaciones): void {
            $notificaciones = [];
        });
    }
}
