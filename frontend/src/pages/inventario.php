<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" sizes="any">
    <link rel="icon" href="./frontend/src/public/favicon/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="./frontend/src/public/favicon/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="./frontend/src/public/favicon/apple-touch-icon.png">
    <link rel="manifest" href="./frontend/src/public/favicon/site.webmanifest">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
</head>
<body>
    <div class="auth-container">
    <div class="form-card">
        <h2>Registrar Producto</h2>
        <form id="formProducto">
            <div class="form-group">
                <label>Descripción del Producto</label>
                <input type="text" name="descripcion" placeholder="Ej. Monitor 24 pulgadas" required>
            </div>
            <div class="form-group">
                <label>Precio</label>
                <input type="number" name="precio" placeholder="0.00" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Cantidad Inicial (Stock)</label>
                <input type="number" name="stock" placeholder="0" required>
            </div>
            <button type="submit" id="submitBtn">Guardar Producto</button>
        </form>
        <p id="mensaje"></p>
        <a href="index.php?url=dashboard">Volver al Dashboard</a>
    </div>
</div>

<script>
document.getElementById('formProducto').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = "Guardando...";

    const data = Object.fromEntries(new FormData(e.target));
    data.action = 'agregar_producto';

    try {
        const res = await fetch('./backend/src/server.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await res.json();
        
        const msg = document.getElementById('mensaje');
        msg.textContent = result.message;
        msg.style.color = result.success ? "green" : "red";
        
        if(result.success) {
            e.target.reset(); // Limpia el formulario si se guardó bien
        }
    } catch (error) {
        console.error("Error:", error);
    } finally {
        btn.disabled = false;
        btn.textContent = "Guardar Producto";
    }
});
</script>
</body>
</html>