<?php
require('db.php');

// Función para depuración
function debug_log($message, $data = null) {
    $log_file = fopen('tasks_debug.log', 'a');
    $log_message = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log_message .= ": " . print_r($data, true);
    }
    fwrite($log_file, $log_message . "\n");
    fclose($log_file);
}

function createTask($userId, $title, $description, $dueDate)
{
    global $pdo;
    try {
        $sql = "INSERT INTO tasks (user_id, title, description, due_date) VALUES (:user_id, :title, :description, :due_date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate
        ]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        debug_log("Error creating task", $e->getMessage());
        return 0;
    }
}

function getTasksByUser($userId)
{
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = :user_id ORDER BY due_date ASC");
        $stmt->execute(['user_id' => $userId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener los comentarios para cada tarea
        foreach ($tasks as &$task) {
            $commentStmt = $pdo->prepare("SELECT id, task_id, description, created_at FROM comments WHERE task_id = :task_id ORDER BY created_at DESC");
            $commentStmt->execute(['task_id' => $task['id']]);
            $task['comments'] = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $tasks;
    } catch (Exception $ex) {
        debug_log("Error getting tasks", $ex->getMessage());
        return [];
    }
}

function editTask($id, $title, $description, $dueDate)
{
    global $pdo;
    try {
        $sql = "UPDATE tasks SET title = :title, description = :description, due_date = :due_date WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate,
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        debug_log("Error editing task", $e->getMessage());
        return false;
    }
}

function deleteTask($id)
{
    global $pdo;
    try {
        $sql = "DELETE FROM tasks WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["id" => $id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        debug_log("Error deleting task", $e->getMessage());
        return false;
    }
}

function validateInput($input)
{
    return isset($input['title'], $input['description'], $input['due_date']);
}

function getJsonInput()
{
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    debug_log("Input recibido", $input);
    return $input;
}

// Configurar cabeceras
header('Content-Type: application/json');

// Iniciar sesión
session_start();

// Verificar si hay una sesión activa
if (isset($_SESSION["user_id"])) {
    $userId = $_SESSION["user_id"];
    $method = $_SERVER['REQUEST_METHOD'];
    
    debug_log("Request method", $method);
    
    try {
        if ($method === 'GET') {
            // Obtener todas las tareas del usuario
            $tasks = getTasksByUser($userId);
            echo json_encode($tasks);
            
        } else if ($method === 'POST') {
            $input = getJsonInput();
            
            // Determinar la acción basada en parámetros
            $action = isset($input['action']) ? $input['action'] : 'create';
            debug_log("Action", $action);
            
            switch ($action) {
                case 'create':
                    // Crear una nueva tarea
                    if (validateInput($input)) {
                        $taskId = createTask($userId, $input['title'], $input['description'], $input['due_date']);
                        
                        if ($taskId > 0) {
                            debug_log("Task created", $taskId);
                            http_response_code(201);
                            
                            // Obtener la tarea recién creada
                            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
                            $stmt->execute(['id' => $taskId]);
                            $newTask = $stmt->fetch(PDO::FETCH_ASSOC);
                            $newTask['comments'] = [];
                            
                            echo json_encode($newTask);
                        } else {
                            http_response_code(500);
                            echo json_encode(['error' => "Error creating task"]);
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(["error" => "Missing required fields"]);
                    }
                    break;
                    
                case 'update':
                    // Actualizar una tarea existente
                    if (isset($input['id']) && validateInput($input)) {
                        $taskId = $input['id'];
                        
                        // Verificar que la tarea pertenezca al usuario
                        $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = :id AND user_id = :user_id");
                        $stmt->execute([
                            'id' => $taskId,
                            'user_id' => $userId
                        ]);
                        
                        if ($stmt->rowCount() > 0) {
                            if (editTask($taskId, $input['title'], $input['description'], $input['due_date'])) {
                                debug_log("Task updated", $taskId);
                                http_response_code(200);
                                
                                // Obtener la tarea actualizada
                                $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
                                $stmt->execute(['id' => $taskId]);
                                $updatedTask = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                // Obtener los comentarios
                                $commentStmt = $pdo->prepare("SELECT id, task_id, description, created_at FROM comments WHERE task_id = :task_id ORDER BY created_at DESC");
                                $commentStmt->execute(['task_id' => $taskId]);
                                $updatedTask['comments'] = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                echo json_encode($updatedTask);
                            } else {
                                http_response_code(500);
                                echo json_encode(['error' => "Error updating task"]);
                            }
                        } else {
                            http_response_code(403);
                            echo json_encode(['error' => "You don't have access to this task"]);
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => "Task ID or required fields missing"]);
                    }
                    break;
                    
                case 'delete':
                    // Eliminar una tarea
                    if (isset($input['id'])) {
                        $taskId = $input['id'];
                        
                        // Verificar que la tarea pertenezca al usuario
                        $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = :id AND user_id = :user_id");
                        $stmt->execute([
                            'id' => $taskId,
                            'user_id' => $userId
                        ]);
                        
                        if ($stmt->rowCount() > 0) {
                            if (deleteTask($taskId)) {
                                debug_log("Task deleted", $taskId);
                                http_response_code(200);
                                echo json_encode(['success' => true, 'message' => "Task deleted successfully"]);
                            } else {
                                http_response_code(500);
                                echo json_encode(['error' => "Error deleting task"]);
                            }
                        } else {
                            http_response_code(403);
                            echo json_encode(['error' => "You don't have access to this task"]);
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => "Task ID not provided"]);
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(["error" => "Invalid action"]);
            }
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
        }
    } catch (Exception $e) {
        debug_log("Error general", $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => "Server error: " . $e->getMessage()]);
    }
} else {
    // No hay sesión activa
    http_response_code(401);
    echo json_encode(["error" => "No active session"]);
}
?>