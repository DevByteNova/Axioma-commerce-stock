# Lista de Tareas Pendientes (TODOs) - Sistema Pijamas Harv

## 1. Módulo de Autenticación y Seguridad - Terminado ✔
- [X] Programar la lógica de inicio de sesión (`login.php`) validando contraseñas cifradas.
- [X] Configurar el control de sesiones por roles (Administrador, Vendedor, Cliente).
- [X] Proteger las rutas privadas para evitar accesos directos por URL.

## 2. Módulo de Transacciones y Compras
- [ ] Desarrollar el carrito de compras dinámico (JavaScript / PHP).
- [ ] Conectar los métodos de pago para actualizar los estados de los pedidos en la base de datos MySQL.
- [ ] Implementar la generación automática de facturas en PDF (vía FPDF o Dompdf).

## 3. Paneles y Reportes
- [ ] Finalizar las consultas SQL en tiempo real para el panel del Administrador (ventas por vendedor y stock crítico).
- [ ] Conectar el historial de ventas del Vendedor con la tabla de transacciones de la base de datos.
- [ ] Programar el flujo del Cliente para visualizar sus compras previas y descargar facturas.

## 4. Control de Versiones y Despliegue
- [ ] Resolver la autenticación por token en Git (`git push`).
- [ ] Sincronizar el repositorio remoto con el proyecto local.