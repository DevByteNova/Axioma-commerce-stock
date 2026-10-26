<?php require_once __DIR__ . '/../../../backend/src/config/marca.php'; ?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Iniciar sesión | <?php echo htmlspecialchars(MARCA_TIENDA); ?></title>
        <link rel="icon" href="./frontend/src/public/favicon/favicon.svg" type="image/svg+xml">
        <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    </head>
    <body class="login-page">
        <div class="container">
            <div class="left">
                <div class="card">
                    <div class="brand">
                        <div class="logo-box" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V10"/><path d="M12 14c-3.5 0-6-2.5-6-6 3.5 0 6 2.5 6 6z"/><path d="M12 12c3.5 0 6-2.5 6-6-3.5 0-6 2.5-6 6z"/><path d="M12 9c-1.4-1.4-1.4-3.6 0-5 1.4 1.4 1.4 3.6 0 5z"/></svg>
                        </div>
                        <span><?php echo htmlspecialchars(MARCA_TIENDA); ?></span>
                    </div>

                    <h2>Iniciar sesión en su cuenta</h2>
                    <p class="lead">Ingresa para comprar, revisar tus pedidos o administrar la tienda.</p>

                    <form id="loginForm">
                        <div class="form-group">
                            <label for="username">Usuario o correo electrónico</label>
                            <input type="text" id="username" name="username" required autocomplete="username">
                        </div>

                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <input type="password" id="password" name="password" required autocomplete="current-password">
                        </div>

                        <div id="errorMessage"></div>

                        <div class="form-group">
                            <button type="submit" id="submitBtn"><span id="btnText">Ingresar</span></button>
                        </div>
                    </form>

                    <div class="note">¿No tienes cuenta? <a href="index.php?url=registro">Regístrate</a> · <a href="index.php?url=catalogo">Ver el catálogo sin iniciar sesión</a></div>
                </div>
            </div>

            <div class="right">
                <img src="frontend/src/public/img/categorias/mujer.svg" alt="">
                <div class="overlay">
                    <div class="content">
                        <span class="badge"><?php echo htmlspecialchars(MARCA_COLECCION); ?></span>
                        <h1 class="h1"><?php echo htmlspecialchars(MARCA_ESLOGAN); ?>.</h1>
                        <p class="desc">Pijamas para mujer, hombre, niños y fechas especiales, con inventario y facturación en línea.</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('loginForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                e.stopPropagation();

                const usernameInput = document.getElementById('username').value;
                const passwordInput = document.getElementById('password').value;

                const submitBtn = document.getElementById('submitBtn');
                const btnText = document.getElementById('btnText');
                const errorMessage = document.getElementById('errorMessage');

                submitBtn.disabled = true;
                btnText.textContent = "Verificando credenciales...";
                errorMessage.classList.remove('show');

                try {
                    const response = await fetch('backend/src/server.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'login', username: usernameInput, password: passwordInput })
                    });

                    const rawText = await response.text();
                    const data = JSON.parse(rawText);

                    if (data.success) {
                        const rol = String(data.rol || '').toLowerCase();
                        const destino = data.destino || (rol === 'cliente' ? 'cliente_dashboard' : 'dashboard');
                        window.location.href = `index.php?url=${destino}`;
                    } else {
                        errorMessage.textContent = data.message || 'Credenciales incorrectas.';
                        errorMessage.classList.add('show');
                        submitBtn.disabled = false;
                        btnText.textContent = "Ingresar";
                    }
                } catch (error) {
                    console.error(error);
                    alert("Error crítico en la petición: " + error.message);
                    errorMessage.textContent = "Error de conexión con el servidor.";
                    errorMessage.classList.add('show');
                    submitBtn.disabled = false;
                    btnText.textContent = "Ingresar";
                }
            });
        </script>
    </body>
</html>