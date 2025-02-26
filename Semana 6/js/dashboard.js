document.addEventListener('DOMContentLoaded', function(){

    // Datos ficticios para tareas
    const tasks = [
        {
            id: 1,
            title: "Complete Project Report",
            description: "Prepare and submit the final project report by the end of the week.",
            due_date: "2024-08-25"
        },
        {
            id: 2,
            title: "Team Meeting",
            description: "Schedule a team meeting to discuss the next sprint.",
            due_date: "2024-08-26"
        },
        {
            id: 3,
            title: "Code Review",
            description: "Review the codebase and ensure all pull requests are merged.",
            due_date: "2024-08-27"
        }
    ];    

    let editingTaskId = null;
    let taskCounter = tasks.length;

    //carga las tareas en el DOM
    function loadTasks(){
        const taskList = document.getElementById('task-list');
        taskList.innerHTML = '';

        tasks.forEach(function(task){
            //aqui vamos a tener un element del arreglo de tareas por cada uno de los elementos
            const taskCard = document.createElement('div');
            taskCard.className = 'col-md-4 mb-3';
            taskCard.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">${task.title}</h5>
                    <p class="card-text">${task.description}</p>
                    <p class="card-text text-muted" > ${task.due_date}</p>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button class="btn btn-secondary btn-sm edit-task" data-id="${task.id}">Edit</button>
                    <button class="btn btn-danger btn-sm delete-task" data-id="${task.id}">Delete</button>
                </div>
            </div>
            `;
            taskList.appendChild(taskCard);
        });
        console.log(tasks);

        //selecciona todos los botones que tengan la clase edit-task
        document.querySelectorAll('.edit-task').forEach(function(btnEdit){
            //para cada boton, vamos a manajar el evento click, este evento lo va a manejar la funcion handleEditTask 
            //definida mas abajo.
            btnEdit.addEventListener('click', handleEditTask);
        });

        document.querySelectorAll('.delete-task').forEach(function(btnDelete){
            //para cada boton, vamos a manajar el evento click, este evento lo va a manejar la funcion handleEditTask 
            //definida mas abajo.
            btnDelete.addEventListener('click', handleDeleteTask);
        });
    
    }

    function handleEditTask(event){
        // alert('se presiono el boton con taskid ' + event.target.dataset.id);
        //obtener la tarea que el usuario selecciono
        editingTaskId = parseInt(event.target.dataset.id);
        //buscar la informacion de la tarea en el arreglo de tareas
        const task = tasks.find(t => t.id === editingTaskId);
        //cargar la tarea en el formulario
        document.getElementById('task-title').value = task.title;
        document.getElementById('task-desc').value = task.description;
        document.getElementById('due-date').value = task.due_date;
        //cambiar el titulo del modal
        document.getElementById('taskModalLabel').textContent = 'Edit task';
        //abrir el modal
        const modal = new bootstrap.Modal(document.getElementById('taskModal'));
        modal.show();
    }

    function handleDeleteTask(event){
        const id = parseInt(event.target.dataset.id);
        const taskIndex = tasks.findIndex( t => t.id === id);
        tasks.splice(taskIndex,1);
        loadTasks();
    }

    document.getElementById('task-form').addEventListener('submit',function(e){
        e.preventDefault();
        const title = document.getElementById('task-title').value;
        const description = document.getElementById('task-desc').value;
        const dueDate = document.getElementById('due-date').value;

        if(!editingTaskId){
            //modo agregar tarea
            taskCounter = taskCounter + 1;
            const newTask = {
                id: taskCounter,
                title:title,
                description: description,
                due_date: dueDate
            };
            tasks.push(newTask);
        }else{
            //modo de edicion
            let task = tasks.find( t => t.id === editingTaskId);
            task.title = title;
            task.description = description;
            task.due_date = dueDate;
        }
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('taskModal'));
        modal.hide();
        loadTasks();
    });

    document.getElementById('taskModal').addEventListener('show.bs.modal',function(){
        if(!editingTaskId){
            //solo si estamos en modo agregar
            document.getElementById('task-form').reset();
            document.getElementById('taskModalLabel').textContent = 'Add Task';
        }
        
    });

    document.getElementById('taskModal').addEventListener('hidden.bs.modal', function(){
        editingTaskId = null;
    })


    loadTasks();

});