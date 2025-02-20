document.addEventListener('DOMContentLoaded', function() {
    let users = [];
    let userId = 1;

    const userForm = document.querySelector('form');
    const userTableBody = document.querySelector('tbody');

    userForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const name = document.getElementById('nombre').value;
        const email = document.getElementById('email').value;
        const role = document.getElementById('rol').value;

        if (role === 'Selecciona un rol') {
            alert('Por favor, selecciona un rol válido.');
            return;
        }

        const user = { id: userId++, name, email, role };
        users.push(user);
        renderUsers();
        userForm.reset();
    });

    function renderUsers() {
        userTableBody.innerHTML = '';

        users.forEach(user => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${user.id}</td>
                <td>${user.name}</td>
                <td>${user.email}</td>
                <td>${user.role}</td>
                <td>
                    <button class="btn btn-warning btn-sm edit-user" data-id="${user.id}">Editar</button>
                    <button class="btn btn-danger btn-sm delete-user" data-id="${user.id}">Eliminar</button>
                </td>
            `;
            userTableBody.appendChild(row);
        });

        document.querySelectorAll('.delete-user').forEach(button => {
            button.addEventListener('click', function(event) {
                const userId = parseInt(event.target.dataset.id);
                users = users.filter(user => user.id !== userId);
                renderUsers();
            });
        });

        document.querySelectorAll('.edit-user').forEach(button => {
            button.addEventListener('click', function(event) {
                const userId = parseInt(event.target.dataset.id);
                const user = users.find(u => u.id === userId);
                if (user) {
                    document.getElementById('nombre').value = user.name;
                    document.getElementById('email').value = user.email;
                    document.getElementById('rol').value = user.role;

                    users = users.filter(u => u.id !== userId);
                    renderUsers();
                }
            });
        });
    }
});
