document.addEventListener('DOMContentLoaded', function () {
    // URLs de las APIs
    const TASKS_API_URL = "backend/tasks.php";
    const COMMENTS_API_URL = "backend/comments.php";
    
    // Variables de estado
    let isEditMode = false;
    let editingTaskId = null;
    let tasks = [];

    // Cargar todas las tareas del usuario desde el servidor
    async function loadTasks() {
        try {
            const response = await fetch(TASKS_API_URL, { 
                method: 'GET', 
                credentials: 'include' 
            });
            
            if (response.ok) {
                tasks = await response.json();
                renderTasks();
            } else {
                if (response.status === 401) {
                    // Sesión no activa, redirigir al login
                    window.location.href = "index.html";
                } else {
                    console.error("Error al obtener tareas:", await response.text());
                }
            }
        } catch (err) {
            console.error("Error en la solicitud de tareas:", err);
        }
    }

    // Renderizar las tareas en la interfaz de usuario
    function renderTasks() {
        const taskList = document.getElementById('task-list');
        taskList.innerHTML = '';
        
        if (tasks.length === 0) {
            taskList.innerHTML = '<div class="col-12 text-center"><p>No tasks yet. Add your first task!</p></div>';
            return;
        }
        
        tasks.forEach(function (task) {
            // Crear el elemento para los comentarios
            let commentsHTML = '';
            
            if (task.comments && task.comments.length > 0) {
                commentsHTML += '<div class="comment-section">';
                commentsHTML += '<h6>Comments:</h6>';
                
                task.comments.forEach(comment => {
                    const date = new Date(comment.created_at);
                    const formattedDate = `${date.toLocaleDateString()} ${date.toLocaleTimeString()}`;
                    
                    commentsHTML += `
                        <div class="comment-item">
                            <div class="comment-text">${comment.description}</div>
                            <div class="comment-actions">
                                <button type="button" class="btn btn-sm btn-danger delete-comment" 
                                        data-comment-id="${comment.id}" data-task-id="${task.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <span class="comment-date">${formattedDate}</span>
                        </div>
                    `;
                });
                
                commentsHTML += '</div>';
            }

            // Crear el formulario para agregar comentarios
            commentsHTML += `
                <div class="comment-form">
                    <div class="input-group">
                        <input type="text" class="form-control new-comment-text" placeholder="Add a comment...">
                        <button class="btn btn-outline-primary add-comment-btn" data-task-id="${task.id}">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            `;

            // Crear la tarjeta de la tarea
            const taskCard = document.createElement('div');
            taskCard.className = 'col-md-4 mb-3';
            taskCard.innerHTML = `
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">${task.title}</h5>
                        <p class="card-text">${task.description}</p>
                        <p class="card-text">
                            <small class="text-muted">Due: ${new Date(task.due_date).toLocaleDateString()}</small>
                        </p>
                        ${commentsHTML}
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <button class="btn btn-sm btn-secondary edit-task" data-task-id="${task.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-task" data-task-id="${task.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            `;
            
            taskList.appendChild(taskCard);
        });

        // Agregar event listeners para los elementos
        addEventListeners();
    }

    // Agregar event listeners a los elementos dinámicos
    function addEventListeners() {
        // Event listeners para tareas
        document.querySelectorAll('.edit-task').forEach(button => {
            button.addEventListener('click', handleEditTask);
        });

        document.querySelectorAll('.delete-task').forEach(button => {
            button.addEventListener('click', handleDeleteTask);
        });

        // Event listeners para comentarios
        document.querySelectorAll('.add-comment-btn').forEach(button => {
            button.addEventListener('click', handleAddComment);
        });

        document.querySelectorAll('.delete-comment').forEach(button => {
            button.addEventListener('click', handleDeleteComment);
        });

        // Event listener para presionar Enter en el campo de comentario
        document.querySelectorAll('.new-comment-text').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const taskId = this.nextElementSibling.dataset.taskId;
                    const commentText = this.value.trim();
                    if (commentText) {
                        addComment(taskId, commentText);
                        this.value = '';
                    }
                }
            });
        });
    }

    // Manejar la edición de una tarea
    function handleEditTask(event) {
        const taskId = parseInt(event.currentTarget.dataset.taskId);
        const task = tasks.find(t => t.id === parseInt(taskId));
        
        if (task) {
            // Llenar el formulario con los datos de la tarea
            document.getElementById('task-id').value = task.id;
            document.getElementById('task-title').value = task.title;
            document.getElementById('task-desc').value = task.description;
            document.getElementById('due-date').value = task.due_date;
            
            // Cambiar el modo del formulario a edición
            isEditMode = true;
            editingTaskId = parseInt(task.id);
            document.getElementById('taskModalLabel').textContent = 'Edit Task';
            
            // Mostrar el modal
            const taskModal = new bootstrap.Modal(document.getElementById('taskModal'));
            taskModal.show();
        }
    }

    // Manejar la eliminación de una tarea
    async function handleDeleteTask(event) {
        if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
            return;
        }
        
        const taskId = parseInt(event.currentTarget.dataset.taskId);
        
        try {
            // Usar POST con action=delete
            const response = await fetch(TASKS_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: taskId
                }),
                credentials: 'include'
            });
            
            if (response.ok) {
                // Eliminar la tarea de la lista local
                tasks = tasks.filter(task => task.id !== taskId);
                renderTasks();
                
                // Mostrar notificación
                alert('Task deleted successfully!');
            } else {
                const errorText = await response.text();
                console.error('Error deleting task:', errorText);
                alert('Error deleting task. Please try again.');
            }
        } catch (error) {
            console.error('Error in delete request:', error);
            alert('Network error. Please check your connection and try again.');
        }
    }

    // Manejar la adición de un comentario desde el botón
    function handleAddComment(event) {
        const taskId = parseInt(event.currentTarget.dataset.taskId);
        const inputElement = event.currentTarget.previousElementSibling;
        const commentText = inputElement.value.trim();
        
        if (commentText) {
            addComment(taskId, commentText);
            inputElement.value = '';
        }
    }

    // Agregar un comentario a una tarea
    async function addComment(taskId, commentText) {
        try {
            const response = await fetch(COMMENTS_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'create',
                    task_id: taskId,
                    description: commentText
                }),
                credentials: 'include'
            });
            
            if (response.ok) {
                const newComment = await response.json();
                
                // Actualizar la lista local de tareas
                const task = tasks.find(t => t.id === parseInt(taskId));
                if (task) {
                    if (!task.comments) {
                        task.comments = [];
                    }
                    
                    // Añadir el nuevo comentario al inicio
                    task.comments.unshift(newComment);
                    
                    // Volver a renderizar para mostrar el nuevo comentario
                    renderTasks();
                }
            } else {
                const errorText = await response.text();
                console.error('Error adding comment:', errorText);
                alert('Error adding comment. Please try again.');
            }
        } catch (error) {
            console.error('Error in add comment request:', error);
            alert('Network error. Please check your connection and try again.');
        }
    }

    // Manejar la eliminación de un comentario
    async function handleDeleteComment(event) {
        if (!confirm('Are you sure you want to delete this comment?')) {
            return;
        }
        
        const commentId = parseInt(event.currentTarget.dataset.commentId);
        const taskId = parseInt(event.currentTarget.dataset.taskId);
        
        try {
            // Usar POST con action=delete
            const response = await fetch(COMMENTS_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: commentId
                }),
                credentials: 'include'
            });
            
            if (response.ok) {
                // Actualizar la lista local de tareas
                const task = tasks.find(t => t.id === parseInt(taskId));
                if (task && task.comments) {
                    task.comments = task.comments.filter(comment => comment.id !== commentId);
                    
                    // Volver a renderizar para eliminar el comentario de la UI
                    renderTasks();
                }
            } else {
                const errorText = await response.text();
                console.error('Error deleting comment:', errorText);
                alert('Error deleting comment. Please try again.');
            }
        } catch (error) {
            console.error('Error in delete comment request:', error);
            alert('Network error. Please check your connection and try again.');
        }
    }

    // Procesar el formulario de tareas (crear/editar)
    document.getElementById('task-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const title = document.getElementById('task-title').value.trim();
        const description = document.getElementById('task-desc').value.trim();
        const dueDate = document.getElementById('due-date').value;
        
        // Validar datos antes de enviar
        if (!title || !description || !dueDate) {
            alert('Please fill in all required fields');
            return;
        }
        
        try {
            let taskData;
            
            if (isEditMode && editingTaskId) {
                // Actualizar tarea existente
                taskData = {
                    action: 'update',
                    id: editingTaskId,
                    title: title,
                    description: description,
                    due_date: dueDate
                };
            } else {
                // Crear nueva tarea
                taskData = {
                    action: 'create',
                    title: title,
                    description: description,
                    due_date: dueDate
                };
            }

            console.log("Enviando datos:", JSON.stringify(taskData));
            
            // Usar siempre POST
            const response = await fetch(TASKS_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(taskData),
                credentials: 'include'
            });
            
            console.log('Response status:', response.status);
            
            if (response.ok) {
                // Cerrar el modal
                const taskModal = bootstrap.Modal.getInstance(document.getElementById('taskModal'));
                taskModal.hide();
                
                // Resetear el formulario y el modo
                document.getElementById('task-form').reset();
                isEditMode = false;
                editingTaskId = null;
                document.getElementById('taskModalLabel').textContent = 'Add Task';
                
                // Recargar todas las tareas para actualizar la interfaz
                await loadTasks();
                
                // Mensaje de éxito
                alert(isEditMode ? 'Task updated successfully!' : 'Task created successfully!');
            } else {
                const responseText = await response.text();
                console.error('Error saving task. Status:', response.status, 'Response:', responseText);
                alert('Error saving task. Please try again. Status: ' + response.status);
            }
        } catch (error) {
            console.error('Exception in task submission:', error);
            alert('Network error. Please check your connection and try again.');
        }
    });

    // Event listener para el cierre del modal de tareas
    document.getElementById('taskModal').addEventListener('hidden.bs.modal', function() {
        // Resetear el formulario y el modo
        document.getElementById('task-form').reset();
        isEditMode = false;
        editingTaskId = null;
        document.getElementById('taskModalLabel').textContent = 'Add Task';
    });

    // Cargar las tareas al iniciar
    loadTasks();
});