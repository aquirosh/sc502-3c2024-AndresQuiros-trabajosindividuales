document.addEventListener('DOMContentLoaded', function () {

    const TASKS_API_URL = "backend/tasks.php";
    const COMMENTS_API_URL = "backend/comments.php";
    let isEditMode = false;
    let edittingId;
    let tasks = [];
    let isEditingComment = false;
    let editingCommentId = null;

    async function loadTasks() {
        //go to the backed to obtain the data
        try {
            const response = await fetch(TASKS_API_URL, { method: 'GET', credentials: 'include' });
            if (response.ok) {
                tasks = await response.json();
                renderTasks(tasks);
            } else {
                if (response.status == 401) {
                    //estamos tratando de consutlar sin sesion
                    window.location.href = "index.html";
                }
                console.error("Error al obtener tareas");
            }

        } catch (err) {
            console.error(err);
        }
    }

    function renderTasks() {
        const taskList = document.getElementById('task-list');
        taskList.innerHTML = '';
        tasks.forEach(function (task) {

            let commentsList = '';
            if (task.comments && task.comments.length > 0) {
                commentsList = '<ul class="list-group list-group-flush">';
                task.comments.forEach(comment => {
                    commentsList += `<li class="list-group-item">${comment.description} 
                    <div class="float-end">
                        <button type="button" class="btn btn-sm btn-link edit-comment" data-taskid="${task.id}" data-commentid="${comment.id}">Edit</button>
                        <button type="button" class="btn btn-sm btn-link remove-comment" data-taskid="${task.id}" data-commentid="${comment.id}">Remove</button>
                    </div>
                    </li>`;
                });
                commentsList += '</ul>';
            }
            const taskCard = document.createElement('div');
            taskCard.className = 'col-md-4 mb-3';
            taskCard.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">${task.title}</h5>
                    <p class="card-text">${task.description}</p>
                    <p class="card-text"><small class="text-muted">Due: ${task.due_date}</small> </p>
                    ${commentsList}
                     <button type="button" class="btn btn-sm btn-link add-comment"  data-id="${task.id}">Add Comment</button>

                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button class="btn btn-secondary btn-sm edit-task" data-id="${task.id}">Edit</button>
                    <button class="btn btn-danger btn-sm delete-task" data-id="${task.id}">Delete</button>
                </div>
            </div>
            `;
            taskList.appendChild(taskCard);
        });

        document.querySelectorAll('.edit-task').forEach(function (button) {
            button.addEventListener('click', handleEditTask);
        });

        document.querySelectorAll('.delete-task').forEach(function (button) {
            button.addEventListener('click', handleDeleteTask);
        });

        document.querySelectorAll('.add-comment').forEach(function (button) {
            button.addEventListener('click', function (e) {
                // Reset comment form
                document.getElementById("comment-task-id").value = e.target.dataset.id;
                document.getElementById("comment-id").value = "";
                document.getElementById("task-comment").value = "";
                isEditingComment = false;
                editingCommentId = null;
                
                // Update form title
                document.getElementById("comment-form-title").textContent = "Add Comment";
                document.getElementById("comment-submit-btn").textContent = "Save Comment";
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById("commentModal"));
                modal.show();
            });
        });

        document.querySelectorAll('.edit-comment').forEach(function (button) {
            button.addEventListener('click', function (e) {
                const taskId = parseInt(e.target.dataset.taskid);
                const commentId = parseInt(e.target.dataset.commentid);
                const task = tasks.find(t => t.id === taskId);
                const comment = task.comments.find(c => c.id === commentId);
                
                // Prepare form for editing comment
                document.getElementById("comment-task-id").value = taskId;
                document.getElementById("comment-id").value = commentId;
                document.getElementById("task-comment").value = comment.description;
                isEditingComment = true;
                editingCommentId = commentId;
                
                // Update form title
                document.getElementById("comment-form-title").textContent = "Edit Comment";
                document.getElementById("comment-submit-btn").textContent = "Update Comment";
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById("commentModal"));
                modal.show();
            });
        });

        document.querySelectorAll('.remove-comment').forEach(function (button) {
            button.addEventListener('click', handleDeleteComment);
        });
    }

    function handleEditTask(event) {
        try {
            //localizar la tarea quieren editar
            const taskId = parseInt(event.target.dataset.id);
            const task = tasks.find(t => t.id === taskId);
            //cargar los datos en el formulario 
            document.getElementById('task-title').value = task.title;
            document.getElementById('task-desc').value = task.description;
            document.getElementById('due-date').value = task.due_date;
            //ponerlo en modo edicion
            isEditMode = true;
            edittingId = taskId;
            //mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById("taskModal"));
            modal.show();


        } catch (error) {
            alert("Error trying to edit a task");
            console.error(error);
        }
    }


    async function handleDeleteTask(event) {
        const id = parseInt(event.target.dataset.id);
        try {
            const response = await fetch(`${TASKS_API_URL}?id=${id}`, { credentials: 'include', method: 'DELETE' });
            if (response.ok) {
                loadTasks();
            } else {
                console.error("Problema al eliminar la tarea");
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function handleDeleteComment(event) {
        const taskId = parseInt(event.target.dataset.taskid);
        const commentId = parseInt(event.target.dataset.commentid);
        
        if (confirm('¿Estás seguro de que deseas eliminar este comentario?')) {
            try {
                const response = await fetch(`${COMMENTS_API_URL}?id=${commentId}`, { 
                    credentials: 'include', 
                    method: 'DELETE'
                });
                
                if (response.ok) {
                    // Actualizar la interfaz de usuario
                    const task = tasks.find(t => t.id === taskId);
                    if (task && task.comments) {
                        const commentIndex = task.comments.findIndex(c => c.id === commentId);
                        if (commentIndex !== -1) {
                            task.comments.splice(commentIndex, 1);
                            renderTasks();
                        }
                    }
                } else {
                    console.error("Problema al eliminar el comentario");
                    const errorData = await response.json();
                    alert(`Error: ${errorData.error || 'No se pudo eliminar el comentario'}`);
                }
            } catch (err) {
                console.error(err);
                alert("Error al procesar la solicitud");
            }
        }
    }

    document.getElementById('comment-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        
        const comment = document.getElementById('task-comment').value;
        const taskId = parseInt(document.getElementById('comment-task-id').value);
        const commentId = document.getElementById('comment-id').value;
        
        const commentData = {
            description: comment,
            task_id: taskId
        };
        
        try {
            let response;
            
            if (isEditingComment && commentId) {
                // Actualizar comentario existente
                response = await fetch(`${COMMENTS_API_URL}?id=${commentId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(commentData),
                    credentials: 'include'
                });
            } else {
                // Agregar nuevo comentario
                response = await fetch(COMMENTS_API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(commentData),
                    credentials: 'include'
                });
            }
            
            if (response.ok) {
                const responseData = await response.json();
                
                // Actualizar el array de tareas local
                const task = tasks.find(t => t.id === taskId);
                
                if (!task.comments) {
                    task.comments = [];
                }
                
                if (isEditingComment) {
                    // Actualizar comentario existente
                    const commentIndex = task.comments.findIndex(c => c.id === parseInt(commentId));
                    if (commentIndex !== -1) {
                        task.comments[commentIndex].description = comment;
                    }
                } else {
                    // Agregar nuevo comentario
                    const newComment = {
                        id: responseData.id || parseInt(responseData.message.split(':')[1].trim()),
                        task_id: taskId,
                        description: comment,
                        created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
                    };
                    task.comments.unshift(newComment); 
                }
                
                // Cerrar el modal y renderizar
                const modal = bootstrap.Modal.getInstance(document.getElementById('commentModal'));
                modal.hide();
                
                // Resetear variables de edición
                isEditingComment = false;
                editingCommentId = null;
                
                renderTasks();
            } else {
                const errorData = await response.json();
                alert(`Error: ${errorData.error || 'No se pudo guardar el comentario'}`);
            }
        } catch (err) {
            console.error(err);
            alert("Error al procesar la solicitud");
        }
    });

    document.getElementById('task-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const title = document.getElementById("task-title").value;
        const description = document.getElementById("task-desc").value;
        const dueDate = document.getElementById("due-date").value;

        if (isEditMode) {
            const response = await fetch(`${TASKS_API_URL}?id=${edittingId}`,
                {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ title: title, description: description, due_date: dueDate })
                });
            if (!response.ok) {
                console.error("no se pudo actualizar la tarea");
            }

        } else {
            const newTask = {
                title: title,
                description: description,
                due_date: dueDate
            };
            const response = await fetch(TASKS_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': "application/json"
                },
                body: JSON.stringify(newTask),
                credentials: 'include'
            });
            if (!response.ok) {
                console.error("No se pudo agregar la tarea");
            }
        }
        const modal = bootstrap.Modal.getInstance(document.getElementById('taskModal'));
        modal.hide();
        loadTasks();
    });

    document.getElementById('commentModal').addEventListener('show.bs.modal', function () {
        
    });

    document.getElementById('taskModal').addEventListener('show.bs.modal', function () {
        if (!isEditMode) {
            document.getElementById('task-form').reset();
        }
    });

    document.getElementById("taskModal").addEventListener('hidden.bs.modal', function () {
        edittingId = null;
        isEditMode = false;
    });
    
    loadTasks();
});