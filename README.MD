# Axioma Commerce Stock

Sistema web de comercio y gestión de inventario para la administración de productos, clientes, vendedores, carrito de compras, pagos y facturación.

## 🛠️ Tecnologías y Configuración
* **Lenguaje:** PHP 8+
* **Base de datos:** MySQL/MariaDB mediante PDO
* **Interfaz:** PHP, HTML, Bootstrap 5 y Font Awesome
* **Facturación:** Dompdf para generar facturas en PDF
* **Arquitectura:** `frontend/` contiene las vistas y `backend/` contiene la conexión, controladores y modelos

## 📁 Estructura del Proyecto

 Axioma-commerce-stock/
├── backend/                 # Conexión, controladores y modelos
├── frontend/                # Vistas, páginas y recursos públicos
├── database/                # Respaldo de la base de datos
├── docs/                    # Documentación pendiente
├── index.php                # Router principal
├── composer.json            # Dependencias PHP
└── vendor/                  # Dependencias instaladas


## 🚀 Guía de Implementación
1. Inicia **Apache** y **MySQL** desde XAMPP.
2. Crea o importa la base de datos usando `database/axioma_bd.sql` desde phpMyAdmin.
3. Crea la configuración local desde la plantilla:

	```powershell
	Copy-Item .env.example .env
	```

	Después completa `.env` con el servidor, usuario, contraseña y base de datos de MySQL. `.env` está ignorado por Git y no debe publicarse.
4. Instala las dependencias si no existe `vendor/`:

	```bash
	composer install
	```

5. Abre el proyecto en el navegador:

	```text
	http://localhost/Axioma-commerce-stock-master/
	```

6. Ejecuta la prueba básica de integración:

	```powershell
	php tests/smoke.php
	```

7. Para producción, ejecuta `database/create_app_user.sql` con una contraseña segura y cambia `DB_USER` y `DB_PASSWORD` en `.env`.

> El archivo SQL aparece como `axioma_bd`. En instalaciones Windows normalmente no hay diferencia entre mayúsculas y minúsculas, pero conviene usar el mismo nombre configurado en `conexion.php`.

## 🔒 Seguridad
* Autenticación basada en sesiones PHP.
* Rutas protegidas según el rol: `Cliente`, `Vendedor` y `Administrador`.
* El checkout y las operaciones principales usan PDO y consultas preparadas.
* Los formularios principales usan tokens CSRF y las sesiones usan cookies protegidas.
* Las contraseñas se validan con `password_verify`; no hay contraseñas de respaldo en el código.
* No se almacenan números completos, CVV ni datos sensibles de tarjetas.
* La validación de tarjeta incluida es local para pruebas; no reemplaza una pasarela bancaria real.

---
*Desarrollado como proyecto académico.*

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

- El cliente agrega productos mediante `index.php?url=agregar_carrito`.
- El carrito se consulta en `index.php?url=ver_carrito`.
- El botón **Finalizar compra** abre `index.php?url=procesar_compra`.
- El checkout carga los métodos activos de `metodos_pago` y permite seleccionar tarjeta o PSE.
- Para tarjeta se solicitan titular, número de 16 dígitos, vencimiento y CVV.
- Para PSE se solicita el banco seleccionado.
- La compra crea una factura, registra las ventas, descuenta el inventario y vacía el carrito dentro de una transacción.
- Después de comprar, el dashboard muestra la factura recién creada.
- `frontend/src/pages/ver_factura.php?id=...` permite consultar la factura.
- `frontend/src/pages/generar_factura.php?id=...` genera el PDF con Dompdf.
- El administrador puede filtrar el reporte de ventas por fecha y exportarlo a CSV.
- Los estados permitidos de una venta son `Pendiente`, `Pagado`, `Enviado`, `Entregado` y `Cancelado`.

> Actualmente `ventas` no tiene una columna `ID_FACTURA`; las facturas relacionan las ventas mediante el cliente. Para separar estrictamente los productos de cada factura, se debe agregar esa relación en la base de datos.

## 🧪 Credenciales de prueba

- Usa un usuario con rol `Cliente` creado en la base de datos para probar el flujo.
- El SQL de ejemplo incluye usuarios de prueba, pero sus contraseñas dependen de los datos importados y de la lógica de autenticación vigente.
