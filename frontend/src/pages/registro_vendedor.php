<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Axioma - Añadir Vendedor</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
</head>
<body class="vendor-registration-page">
<div class="auth-container">
    <div class="form-card">
        <h2>Añadir nuevo Vendedor</h2>
        <form id="registroVendedorForm">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" id="nombre" required placeholder="Ej. Juan Pérez">
            </div>
            <div class="form-group">
                <label>Nombre de Usuario</label>
                <input type="text" id="username" required placeholder="Ej. jperez">
            </div>
            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" id="email" required placeholder="ejemplo@pijamasharb.com">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" id="telefono" placeholder="Ej. 3101234567">
            </div>
            <div class="form-group">
                <label>Dirección</label>
                <input type="text" id="direccion" placeholder="Ej. Calle 100 # 15-20">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" id="password" required>
            </div>
            <button type="submit" id="submitBtn">Crear Vendedor</button>
        </form>
        <p id="mensaje"></p>
        <a href="index.php?url=dashboard">Volver al Dashboard</a>
    </div>
</div>
<script>
document.getElementById('registroVendedorForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = "Procesando...";

    const data = {
        action: 'registrar_vendedor',
        nombre: document.getElementById('nombre').value,
        username: document.getElementById('username').value,
        email: document.getElementById('email').value,
        telefono: document.getElementById('telefono').value,       // <-- Revisa que este id exista en tu HTML
        direccion: document.getElementById('direccion').value,   // <-- Revisa que este id exista en tu HTML
        password: document.getElementById('password').value
    };
    try {
        const response = await fetch('/Axioma-Commerce-Stock-suite-main/backend/src/server.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        const msg = document.getElementById('mensaje');
        msg.textContent = result.message;
        msg.style.color = result.success ? "green" : "red";
        
        if(result.success) {
            document.getElementById('registroVendedorForm').reset();
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('mensaje').textContent = "Error de conexión con el servidor.";
        document.getElementById('mensaje').style.color = "red";
    } finally {
        btn.disabled = false;
        btn.textContent = "Crear Vendedor";
    }
});
</script>

</body>
</html>