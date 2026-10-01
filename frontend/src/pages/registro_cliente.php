<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Cliente | Pijamas Harv</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #eef2ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
        }

        .form-control, .form-select {
            border-radius: 0.75rem;
            padding: 0.8rem 0.9rem;
            border-color: #dfe3ea;
        }

        .form-control:focus, .form-select:focus {
            border-color: #5b6cff;
            box-shadow: 0 0 0 0.2rem rgba(91, 108, 255, 0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 0.8rem 1rem;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
        }

        .btn-outline-secondary {
            border-radius: 0.75rem;
        }

        .alert {
            border-radius: 0.75rem;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold mb-1">Crear cuenta de Cliente</h2>
                        <p class="text-muted mb-0">Únete a nuestra plataforma comercial</p>
                    </div>

                    <form id="registerForm" novalidate>
                        <input type="hidden" id="rol" value="Cliente">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" id="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="apellidos" class="form-label">Apellidos</label>
                                <input type="text" id="apellidos" class="form-control">
                            </div>

                            <div class="col-12">
                                <label for="username" class="form-label">Usuario</label>
                                <input type="text" id="username" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" id="email" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" id="password" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" id="direccion" class="form-control" placeholder="Calle, Ciudad, País">
                            </div>

                            <div class="col-12">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" id="telefono" class="form-control" placeholder="3001234567">
                            </div>
                        </div>

                        <div id="msg" class="alert mt-3 d-none" role="alert"></div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">Registrarse</button>
                            <a href="index.php?url=clientes" class="btn btn-outline-secondary">← Volver a Clientes</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const msg = document.getElementById('msg');
            msg.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning');
            msg.classList.add('alert-warning');
            msg.textContent = 'Enviando...';

            try {
                const res = await fetch('backend/src/server.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'register',
                        nombre: document.getElementById('nombre').value,
                        apellidos: document.getElementById('apellidos').value,
                        username: document.getElementById('username').value,
                        email: document.getElementById('email').value,
                        password: document.getElementById('password').value,
                        direccion: document.getElementById('direccion').value,
                        telefono: document.getElementById('telefono').value,
                        rol: document.getElementById('rol').value
                    })
                });

                if (!res.ok) {
                    const text = await res.text();
                    throw new Error('HTTP ' + res.status + ': ' + text);
                }

                const contentType = res.headers.get('content-type') || '';
                let data;
                if (contentType.includes('application/json')) {
                    data = await res.json();
                } else {
                    const text = await res.text();
                    throw new Error('Respuesta no-JSON del servidor: ' + text);
                }

                msg.classList.remove('alert-warning');
                msg.textContent = data.message || 'Respuesta recibida.';

                if (data.success) {
                    msg.classList.add('alert-success');
                    setTimeout(() => window.location.href = 'index.php?url=login', 1600);
                } else {
                    msg.classList.add('alert-danger');
                }
            } catch (err) {
                msg.classList.remove('alert-warning');
                msg.classList.add('alert-danger');
                msg.textContent = 'Error al enviar: ' + err.message;
            }
        });
    </script>
</body>
</html>