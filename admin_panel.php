<?php
// admin_panel.php
session_start();
require_once 'api/auth_check.php';

// Verificar que el usuario tenga rol de admin antes de cargar el HTML
if (getCurrentRole() !== 'admin') {
    header("Location: index.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Con Tribu Ir</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .admin-container {
            max-width: 1000px;
            margin: 100px auto 40px;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .admin-search {
            margin-bottom: 1.5rem;
        }
        .admin-search input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .role-select {
            padding: 0.4rem;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .message-box {
            display: none;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body style="background-color: #f5f7fa;">
    <!-- Simple Header para volver -->
    <header id="header" class="scrolled">
        <div class="container">
            <div class="header-content">
                <div class="logo" onclick="window.location.href='index.html'" style="cursor: pointer;">
                    <img src="photos/LogoOficial.png" alt="Logo Con Tribu Ir" class="logo-icon">
                    <span>Con Tribu Ir</span>
                </div>
                <div class="header-buttons">
                    <button class="btn btn-secondary" onclick="window.location.href='index.html'">
                        ← Volver al Inicio
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <div class="admin-header">
            <h2>Gestión de Usuarios</h2>
            <span style="background: #ffc107; padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: bold; font-size: 0.9rem;">Panel Admin</span>
        </div>

        <div id="statusMessage" class="message-box"></div>

        <div class="admin-search">
            <input type="text" id="searchInput" placeholder="Buscar por nombre o correo electrónico...">
        </div>

        <div style="overflow-x: auto;">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Usuario</th>
                        <th>Correo Electrónico</th>
                        <th>Rol Actual</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <tr><td colspan="5" style="text-align: center;">Cargando usuarios...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let allUsers = [];

        document.addEventListener('DOMContentLoaded', () => {
            loadUsers();

            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    filterUsers(e.target.value);
                });
            }
        });

        async function loadUsers() {
            try {
                const response = await fetch('api/get_users.php');
                if (response.status === 401 || response.status === 403) {
                    window.location.href = 'index.html';
                    return;
                }
                const data = await response.json();
                allUsers = data;
                renderUsers(allUsers);
            } catch (error) {
                console.error("Error al cargar usuarios:", error);
                document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Error al cargar la lista de usuarios.</td></tr>';
            }
        }

        function filterUsers(query) {
            const q = query.toLowerCase();
            const filtered = allUsers.filter(u => 
                (u.name && u.name.toLowerCase().includes(q)) || 
                (u.email && u.email.toLowerCase().includes(q))
            );
            renderUsers(filtered);
        }

        function renderUsers(usersArray) {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';

            if (usersArray.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No se encontraron usuarios.</td></tr>';
                return;
            }

            usersArray.forEach(user => {
                const tr = document.createElement('tr');
                
                tr.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.name}</td>
                    <td>${user.email}</td>
                    <td><span style="background: #eef2f5; padding: 4px 8px; border-radius: 4px; font-size: 0.9em;">${user.role}</span></td>
                    <td>
                        <select class="role-select" onchange="updateRole(${user.id}, this.value, '${user.name}')">
                            <option value="student" ${user.role === 'student' ? 'selected' : ''}>Estudiante</option>
                            <option value="instructor" ${user.role === 'instructor' ? 'selected' : ''}>Instructor</option>
                            <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                        </select>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        async function updateRole(userId, newRole, userName) {
            if (!confirm(`¿Estás seguro de cambiar el rol de ${userName} a ${newRole}?`)) {
                // Revert select visually if cancelled
                loadUsers(); 
                return;
            }

            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('new_role', newRole);

                const response = await fetch('api/update_user_role.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok && result.status === 'success') {
                    showMessage(result.message, 'success');
                    // Actualizar variable local y re-renderizar
                    const uIndex = allUsers.findIndex(u => u.id === userId);
                    if (uIndex > -1) {
                        allUsers[uIndex].role = newRole;
                    }
                    filterUsers(document.getElementById('searchInput').value);
                } else {
                    showMessage(result.message || 'Error al actualizar', 'error');
                    loadUsers(); // refresh para revertir
                }

            } catch (error) {
                console.error("Error al actualizar rol:", error);
                showMessage('Error de conexión al actualizar', 'error');
                loadUsers();
            }
        }

        function showMessage(msg, type) {
            const box = document.getElementById('statusMessage');
            box.textContent = msg;
            box.className = 'message-box message-' + type;
            box.style.display = 'block';

            setTimeout(() => {
                box.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
