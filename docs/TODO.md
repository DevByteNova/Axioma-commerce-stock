# Lista de Tareas Pendientes (TODOs) - Sistema Pijamas Harv

## 1. Módulo de Autenticación y Seguridad - Terminado ✔
- [X] Programar la lógica de inicio de sesión (`login.php`) validando contraseñas cifradas.
- [X] Configurar el control de sesiones por roles (Administrador, Vendedor, Cliente).
- [X] Proteger las rutas privadas para evitar accesos directos por URL.

## 2. Módulo de Transacciones y Compras - Terminado ✔
- [X] Desarrollar el carrito de compras dinámico (JavaScript / PHP).
- [X] Conectar los métodos de pago para actualizar los estados de los pedidos en la base de datos MySQL.
- [X] Implementar la generación automática de facturas en PDF (vía FPDF o Dompdf).

## 3. Paneles y Reportes - Terminado ✔
- [X] Finalizar las consultas SQL en tiempo real para el panel del Administrador (ventas por vendedor y stock crítico).
- [X] Conectar el historial de ventas del Vendedor con la tabla de transacciones de la base de datos.
- [X] Programar el flujo del Cliente para visualizar sus compras previas y descargar facturas.

## 4. Control de Versiones y Despliegue - Opcional
- [X] Resolver la autenticación por token en Git (`git push`).
- [X] Sincronizar el repositorio remoto con el proyecto local.

## 5. Mejoras y Seguridad
- [X] Eliminar contraseñas de respaldo escritas en el código.
- [X] Agregar protección CSRF a los flujos principales de venta y checkout.
- [X] Configurar sesiones con cookies protegidas.
- [X] Agregar filtros por fecha al reporte de ventas.
- [X] Crear estados permitidos para las ventas y endpoint de actualización administrativa.
- [X] Agregar interfaz administrativa para exportar reportes en CSV.
- [X] Crear y verificar el usuario MySQL limitado para el entorno local.
- [X] Completar pruebas automatizadas básicas de conexión, compras y reportes.