<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Axioma | Inicio de Sesión</title>
        <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    </head>
    <body class="login-page">
        <div class="container">
            <div class="left">
                <div class="card">
                    <div class="brand">
                        <div class="logo-box">∀</div>
                        <span>AXIOMA</span>
                    </div>

                    <h2>Iniciar sesión en su cuenta</h2>
                    <p class="lead">Introduzca sus credenciales para acceder al sistema operativo.</p>

                    <form id="loginForm">
                        <div class="form-group">
                            <label for="username">Usuario o Correo Corporativo</label>
                            <input type="text" id="username" name="username" required autocomplete="username">
                        </div>

                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <input type="password" id="password" name="password" required autocomplete="current-password">
                        </div>

                        <div id="errorMessage"></div>

                        <div class="form-group">
                            <button type="submit" id="submitBtn"><span id="btnText">Ingresar al Sistema</span></button>
                        </div>
                    </form>

                    <div class="note">Conexión cifrada de entorno seguro. El acceso no autorizado está estrictamente monitoreado y auditado por la administración de Axioma.</div>
                </div>
            </div>

            <div class="right">
                <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1400&q=80" alt="Axioma banner">
                <div class="overlay">
                    <div class="content">
                        <span class="badge">Enterprise Suite</span>
                        <h1 class="h1">Precisión lógica en cada transacción.</h1>
                        <p class="desc">Optimice sus flujos de venta y mantenga el control absoluto de su inventario en tiempo real con la infraestructura de Axioma.</p>
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
                        const destino = data.destino || (data.rol === 'Cliente' ? 'cliente_dashboard' : 'dashboard');
                        window.location.href = `index.php?url=${destino}`;
                    } else {
                        errorMessage.textContent = data.message || 'Credenciales incorrectas.';
                        errorMessage.classList.add('show');
                        submitBtn.disabled = false;
                        btnText.textContent = "Ingresar al Sistema";
                    }
                } catch (error) {
                    console.error(error);
                    alert("Error crítico en la petición: " + error.message);
                    errorMessage.textContent = "Error de conexión con el servidor de Axioma.";
                    errorMessage.classList.add('show');
                    submitBtn.disabled = false;
                    btnText.textContent = "Ingresar al Sistema";
                }
            });
        </script>
    </body>
</html>