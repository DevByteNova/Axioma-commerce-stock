<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <title>Axioma | Registro de Cliente</title>
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto w-full max-w-md">
        <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-slate-900">Crear cuenta de Cliente</h2>
        <p class="mt-2 text-center text-sm text-slate-600">Únete a nuestra plataforma comercial</p>
    </div>

    <div class="mt-8 sm:mx-auto w-full max-w-md">
        <div class="bg-white px-4 py-8 shadow-sm sm:rounded-lg sm:px-10 border border-slate-200">
            <form id="registerForm" class="space-y-6">
                <input type="hidden" id="rol" value="Cliente">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nombre</label>
                    <input type="text" id="nombre" required class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Apellidos</label>
                    <input type="text" id="apellidos" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Usuario</label>
                    <input type="text" id="username" required class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Correo Electrónico</label>
                    <input type="email" id="email" required class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Contraseña</label>
                    <input type="password" id="password" required class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Dirección</label>
                    <input type="text" id="direccion" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2" placeholder="Calle, Ciudad, País">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Teléfono</label>
                    <input type="text" id="telefono" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2" placeholder="3001234567">
                </div>

                <div id="msg" class="hidden text-sm p-3 rounded-md"></div>

                    <div class="registration-actions">
                        <a href="index.php?url=clientes" class="btn btn-secondary registration-back-link">← Volver a Clientes</a>
                        <button type="submit" class="w-full bg-slate-900 text-white py-2 rounded-md font-semibold hover:bg-slate-800">Registrarse</button>
                    </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const msg = document.getElementById('msg');
            msg.classList.remove('hidden');
            msg.textContent = 'Enviando...';
            msg.style.background = '#fffbe6';
            msg.style.color = '#92400e';

            try {
                const res = await fetch('/Axioma-Commerce-Stock-suite-main/backend/src/server.php', {
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
                    // Mostrar el HTML o texto devuelto para depuración
                    throw new Error('Respuesta no-JSON del servidor: ' + text);
                }
                msg.textContent = data.message || 'Respuesta recibida.';
                msg.classList.remove('bg-red-50', 'text-red-600', 'bg-green-50', 'text-green-600');

                if (data.success) {
                    msg.style.background = '#ecfdf5';
                    msg.style.color = '#064e3b';
                    setTimeout(() => window.location.href = 'index.php?url=login', 1600);
                } else {
                    msg.style.background = '#fef2f2';
                    msg.style.color = '#7f1d1d';
                }
            } catch (err) {
                msg.textContent = 'Error al enviar: ' + err.message;
                msg.style.background = '#fef2f2';
                msg.style.color = '#7f1d1d';
            }
        });
    </script>
</body>
</html>