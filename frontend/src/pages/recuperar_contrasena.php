<?php
require_once __DIR__ . '/../../../backend/src/config/marca.php';
require_once __DIR__ . '/../../../backend/src/config/security.php';

configure_secure_session();
header('Cache-Control: no-store');
$token = (string) ($_GET['token'] ?? '');
$isReset = $token !== '';
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="referrer" content="no-referrer">
        <title><?php echo $isReset ? 'Crear contraseña nueva' : 'Recuperar contraseña'; ?> | <?php echo htmlspecialchars(MARCA_TIENDA); ?></title>
        <link rel="icon" href="./frontend/src/public/favicon/favicon.svg" type="image/svg+xml">
        <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    </head>
    <body class="login-page">
        <div class="container">
            <main class="left">
                <section class="card">
                    <div class="brand">
                        <div class="logo-box" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V10"/><path d="M12 14c-3.5 0-6-2.5-6-6 3.5 0 6 2.5 6 6z"/><path d="M12 12c3.5 0 6-2.5 6-6-3.5 0-6 2.5-6 6z"/><path d="M12 9c-1.4-1.4-1.4-3.6 0-5 1.4 1.4 1.4 3.6 0 5z"/></svg>
                        </div>
                        <span><?php echo htmlspecialchars(MARCA_TIENDA); ?></span>
                    </div>

                    <?php if ($isReset): ?>
                        <h2>Crear contraseña nueva</h2>
                        <p class="lead">Elige una contraseña de al menos 8 caracteres.</p>
                        <form id="resetForm">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-group">
                                <label for="password">Nueva contraseña</label>
                                <input type="password" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirma la contraseña</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" maxlength="72" autocomplete="new-password">
                            </div>
                            <div id="formMessage" role="status" aria-live="polite"></div>
                            <div class="form-group">
                                <button type="submit" id="submitBtn"><span id="btnText">Guardar contraseña</span></button>
                            </div>
                        </form>
                    <?php else: ?>
                        <h2>Recuperar contraseña</h2>
                        <p class="lead">Ingresa el correo asociado a tu cuenta y te enviaremos un enlace para crear una nueva contraseña.</p>
                        <form id="requestForm">
                            <div class="form-group">
                                <label for="email">Correo electrónico</label>
                                <input type="email" id="email" name="email" required maxlength="254" autocomplete="email">
                            </div>
                            <div id="formMessage" role="status" aria-live="polite"></div>
                            <div class="form-group">
                                <button type="submit" id="submitBtn"><span id="btnText">Enviar instrucciones</span></button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="note"><a href="index.php?url=login">Volver a iniciar sesión</a></div>
                </section>
            </main>
            <div class="right" aria-hidden="true">
                <img src="frontend/src/public/img/categorias/mujer.svg" alt="">
                <div class="overlay">
                    <div class="content">
                        <span class="badge"><?php echo htmlspecialchars(MARCA_COLECCION); ?></span>
                        <h1 class="h1"><?php echo htmlspecialchars(MARCA_ESLOGAN); ?>.</h1>
                        <p class="desc">Estamos aquí para ayudarte a recuperar el acceso a tu cuenta.</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const form = document.querySelector('#<?php echo $isReset ? 'resetForm' : 'requestForm'; ?>');
            const message = document.getElementById('formMessage');
            const submitButton = document.getElementById('submitBtn');
            const buttonText = document.getElementById('btnText');
            const csrfToken = <?php echo json_encode(csrf_token()); ?>;
            const isReset = <?php echo $isReset ? 'true' : 'false'; ?>;

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                message.textContent = '';
                message.className = '';
                submitButton.disabled = true;
                buttonText.textContent = isReset ? 'Actualizando...' : 'Enviando...';

                const values = new FormData(form);
                const payload = {
                    action: isReset ? 'restablecer_contrasena' : 'solicitar_recuperacion',
                    csrf_token: csrfToken
                };
                if (isReset) {
                    payload.token = values.get('token');
                    payload.password = values.get('password');
                    payload.confirm_password = values.get('confirm_password');
                } else {
                    payload.email = values.get('email');
                }

                try {
                    const response = await fetch('backend/src/server.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    message.textContent = result.message || 'No fue posible procesar la solicitud.';
                    message.className = response.ok && result.success ? 'success' : 'error';
                    if (response.ok && result.success && isReset) {
                        form.hidden = true;
                        window.setTimeout(() => {
                            window.location.href = 'index.php?url=login';
                        }, 1800);
                    }
                } catch (error) {
                    message.textContent = 'Error de conexión. Inténtalo de nuevo.';
                    message.className = 'error';
                } finally {
                    submitButton.disabled = false;
                    buttonText.textContent = isReset ? 'Guardar contraseña' : 'Enviar instrucciones';
                }
            });
        </script>
    </body>
</html>
