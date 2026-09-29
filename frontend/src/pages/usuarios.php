<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_rol']) || !in_array($_SESSION['usuario_rol'], ['Administrador', 'Vendedor'], true)) {
    header("Location: index.php?url=login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios | Pijamas Harv</title>
    <link rel="icon" href="./frontend/src/public/favicon/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./frontend/src/public/css/styles.css">
</head>
<body>
<?php require_once __DIR__ . '/../../../backend/src/controllers/header.php'; ?>

<div class="container">
    <div class="header-actions">
        <div>
            <h1>Personal & Usuarios</h1>
            <p class="page-description">Listado de cuentas corporativas y administración de accesos del sistema.</p>
        </div>
    </div>

    <div class="card user-form-card">
        <h2 id="formTitle">Registrar Nuevo Personal</h2>

        <form id="userCrudForm">
            <input type="hidden" id="userId" value="">

            <div class="form-group">
                <label for="nombre">Nombre Completo</label>
                <input type="text" id="nombre" required>
            </div>

            <div class="form-group">
                <label for="username">Nombre de Usuario</label>
                <input type="text" id="username" required>
            </div>

            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" placeholder="Escriba la clave">
                <p id="passHelp" class="password-help">Dejar en blanco si no desea cambiarla.</p>
            </div>

            <div class="form-group">
                <label for="rol">Rol del Sistema</label>
                <select id="rol">
                    <option value="Cliente">Cliente</option>
                    <option value="Vendedor">Vendedor</option>
                    <option value="Administrador">Administrador</option>
                </select>
            </div>

            <div id="formMsg" class="form-message"></div>

            <div class="user-form-actions">
                <button type="submit" id="saveBtn" class="btn btn-primary">Guardar Usuario</button>
                <button type="button" id="cancelBtn" class="btn user-cancel-button">Cancelar</button>
            </div>
        </form>
    </div>

    <div class="card user-table-card">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Identificador</th>
                    <th>Rol</th>
                    <th class="table-actions-heading">Acciones</th>
                </tr>
            </thead>
            <tbody id="usuariosTableBody">
                <tr>
                    <td colspan="4" class="table-message">Cargando base de datos de usuarios...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    let usuariosActivos = [];

    document.addEventListener('DOMContentLoaded', cargarUsuarios);

    async function cargarUsuarios() {
        try {
            const res = await fetch('backend/src/server.php?action=get_usuarios');
            const result = await res.json();

            if (result.success) {
                usuariosActivos = result.data;
                renderTabla();
            }
        } catch (error) {
            console.error('Fallo al conectar con el servidor', error);
        }
    }

    function renderTabla() {
        const tbody = document.getElementById('usuariosTableBody');
        tbody.innerHTML = '';

        if (usuariosActivos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="table-message">No hay registros cargados en la BD.</td></tr>';
            return;
        }

        usuariosActivos.forEach(user => {
            let badgeColor = 'background:#e2e8f0; color:#334155;';
            if (user.rol === 'Administrador') badgeColor = 'background:#fee2e2; color:#991b1b;';
            if (user.rol === 'Vendedor') badgeColor = 'background:#dbeafe; color:#1d4ed8;';

            tbody.innerHTML += `
                <tr>
                    <td>${user.nombre}<br><span class="user-email">${user.email}</span></td>
                    <td class="user-identifier">@${user.usuario}</td>
                    <td><span class="user-role-badge user-role-badge--${user.rol.toLowerCase()}">${user.rol}</span></td>
                    <td class="table-actions">
                        <button type="button" class="user-edit-button" onclick="prepararEdicion(${user.id})">Editar</button>
                        <button type="button" class="user-delete-button" onclick="eliminarUsuario(${user.id})">Eliminar</button>
                    </td>
                </tr>
            `;
        });
    }

    document.getElementById('userCrudForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const id = document.getElementById('userId').value;
        const actionType = id ? 'update_usuario' : 'register';

        const payload = {
            action: actionType,
            id: id,
            nombre: document.getElementById('nombre').value,
            username: document.getElementById('username').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            rol: document.getElementById('rol').value
        };

        const res = await fetch('backend/src/server.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        mostrarMensaje(data.message, data.success);

        if (data.success) {
            resetearFormulario();
            cargarUsuarios();
        }
    });

    function prepararEdicion(id) {
        const user = usuariosActivos.find(u => u.id == id);
        if (!user) return;

        document.getElementById('userId').value = user.id;
        document.getElementById('nombre').value = user.nombre;
        document.getElementById('username').value = user.usuario;
        document.getElementById('email').value = user.email;
        document.getElementById('rol').value = user.rol;

        document.getElementById('password').required = false;
        document.getElementById('passHelp').style.display = 'block';
        document.getElementById('formTitle').textContent = 'Modificar Credenciales';
        document.getElementById('saveBtn').textContent = 'Actualizar Cambios';
        document.getElementById('cancelBtn').style.display = 'inline-block';
    }

    async function eliminarUsuario(id) {
        if (!confirm('¿Está seguro de revocar los accesos y eliminar permanentemente este usuario?')) return;

        const res = await fetch('backend/src/server.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_usuario', id: id })
        });

        const data = await res.json();
        if (data.success) {
            cargarUsuarios();
        } else {
            alert(data.message);
        }
    }

    function resetearFormulario() {
        document.getElementById('userCrudForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('password').required = true;
        document.getElementById('passHelp').style.display = 'none';
        document.getElementById('formTitle').textContent = 'Registrar Nuevo Personal';
        document.getElementById('saveBtn').textContent = 'Guardar Usuario';
        document.getElementById('cancelBtn').style.display = 'none';
    }

    document.getElementById('cancelBtn').addEventListener('click', resetearFormulario);

    function mostrarMensaje(txt, exito) {
        const msg = document.getElementById('formMsg');
        msg.textContent = txt;
        msg.style.display = 'block';
        msg.className = exito ? 'alert-success' : 'alert-error';
        setTimeout(() => msg.style.display = 'none', 3000);
    }
</script>
</body>
</html>