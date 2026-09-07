# Axioma - Panel de Control Administrativo

Sistema de gestión desarrollado bajo una arquitectura modular que integra el frontend, backend y gestión de base de datos para el control de operaciones.

## 🛠️ Tecnologías y Configuración
* **Lenguaje:** PHP 8+
* **Base de Datos:** MySQL gestionado mediante PDO
* **Interfaz:** Tailwind CSS integrado para el diseño de vistas
* **Arquitectura:** Estructura modular dividida en `frontend/` para las vistas y `backend/` para la lógica de conexión y consultas

## 📁 Estructura del Proyecto

Axioma/
├── backend/          # Lógica de conexión y consultas
├── frontend/         # Vistas e interfaz de usuario
├── database/         # Respaldo de la base de datos (.sql)
└── index.php         # Punto de entrada principal


## 🚀 Guía de Implementación
1. **Base de Datos:** Importa el archivo `.sql` ubicado en `/database` a través de phpMyAdmin.
2. **Conexión:** Configura tus credenciales (host, nombre de BD, usuario y clave) en el archivo `backend/src/conexion.php`.
3. **Integración:** Asegúrate de que las rutas relativas o absolutas de `require_once` en tus archivos (`dashboard.php`, etc.) apunten correctamente a `backend/src/conexion.php`.

## 🔒 Seguridad
* Autenticación basada en sesiones PHP con acceso restringido para administradores.
* Implementación de rutas absolutas para garantizar la correcta carga de dependencias en el entorno de desarrollo local.

---
*Desarrollado como proyecto académico.*
Desarrollado como proyecto académico.

## 📝 Cambios recientes y notas de ejecución

- Se añadieron helpers y scripts locales en la carpeta `tools/` (ej.: pruebas de login y simulación de compra). **No se deben subir**; la carpeta `tools/` está ahora en `.gitignore`.
- Se eliminó una marca visual (watermark) desde `frontend/src/public/css/styles.css`.
- Debug rápido: puedes forzar la pantalla de login con `index.php?force_login=1` (parche temporal para depuración).

## 🔧 Puntos importantes para desarrolladores

- Ruta de enrutamiento central: `index.php` — controla vistas y acciones por `?url=...`.
- Login por AJAX: el servidor devuelve JSON con `rol`, `id_cliente`, `id_vendedor` y `destino`. El frontend espera `destino` para redirigir correctamente.
- Claves de sesión usadas por el sistema actual:
	- `$_SESSION['usuario_id']` — id del usuario
	- `$_SESSION['usuario_name']` — nombre de usuario (fallback `nombre_usuario`)
	- `$_SESSION['id_cliente']` — id del cliente asociado
	- `$_SESSION['usuario_rol']` — rol normalizado (`Cliente`, `Vendedor`, `Administrador`)

## 💳 Compras y facturación

- Endpoint para procesar compras de cliente (POST): `index.php?url=procesar_compra_cliente`.
- El flujo realiza validación de stock, inserta en `ventas` y actualiza `productos.STOCK` dentro de una transacción.
- Se añadió una sección en el panel del cliente `frontend/src/pages/clientes_dashboard.php` para listar facturas (enlaza a `frontend/src/pages/ver_factura.php?id=...`).

## 🧪 Credenciales de prueba

- Usuario cliente: `santiago.girald` / `clientes2026` (ejemplo usado en pruebas).
