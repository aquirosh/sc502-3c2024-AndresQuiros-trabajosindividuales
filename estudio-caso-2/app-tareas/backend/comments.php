<?php
require('db.php');

// Función para depuración
function debug_log($message, $data = null) {
    $log_file = fopen('comments_debug.log', 'a');
    $log_message = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log_message .= ": " . print_r($data, true);
    }
    fwrite($log_file, $log_message . "\n");
    fclose($log_file);
}

function createComment($taskId, $description)
{
    global $pdo;
    try {
        $sql = "INSERT INTO comments (task_id, description, created_at) VALUES (:task_id, :description, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'task_id' => $taskId,
            'description' => $description
        ]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        debug_log("Error al crear comentario", $e->getMessage());
        return 0;
    }
}

function getCommentsByTaskId($taskId)
{
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id, description, created_at FROM comments WHERE task_id = :task_id ORDER BY created_at DESC");
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        debug_log("Error al obtener comentarios", $ex->getMessage());
        return [];
    }
}

function deleteComment($id)
{
    global $pdo;
    try {
        $sql = "DELETE FROM comments WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["id" => $id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        debug_log("Error al eliminar comentario", $e->getMessage());
        return false;
    }
}

function isCommentOwnedByUser($commentId, $userId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT c.id 
            FROM comments c
            JOIN tasks t ON c.task_id = t.id
            WHERE c.id = :comment_id AND t.user_id = :user_id
        ");
        $stmt->execute([
            'comment_id' => $commentId,
            'user_id' => $userId
        ]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        debug_log("Error al verificar propiedad del comentario", $e->getMessage());
        return false;
    }
}

function isTaskOwnedByUser($taskId, $userId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = :task_id AND user_id = :user_id");
        $stmt->execute([
            'task_id' => $taskId,
            'user_id' => $userId
        ]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        debug_log("Error al verificar propiedad de la tarea", $e->getMessage());
        return false;
    }
}

function validateCommentInput($input)
{
    return isset($input['description'], $input['task_id']);
}

function getJsonInput()
{
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    debug_log("JSON Input recibido", $input);
    return $input;
}

// Configurar la respuesta como JSON
header('Content-Type: application/json');

// Iniciar la sesión
session_start();

// Verificar si hay una sesión activa
if (isset($_SESSION["user_id"])) {
    $userId = $_SESSION["user_id"];
    debug_log("Usuario autenticado", $userId);
    
    $method = $_SERVER['REQUEST_METHOD'];
    debug_log("Método HTTP original", $method);
    
    try {
        if ($method === 'GET') {
            // Obtener comentarios de una tarea específica
            if (isset($_GET['task_id'])) {
                $taskId = $_GET['task_id'];
                
                // Verificar que la tarea pertenezca al usuario
                if (isTaskOwnedByUser($taskId, $userId)) {
                    $comments = getCommentsByTaskId($taskId);
                    echo json_encode($comments);
                } else {
                    http_response_code(403);
                    echo json_encode(["error" => "No tienes acceso a esta tarea"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["error" => "ID de tarea no proporcionado"]);
            }
        } else if ($method === 'POST') {
            $input = getJsonInput();
            
            // Determinar la acción basada en parámetros
            $action = isset($input['action']) ? $input['action'] : 'create';
            debug_log("Action", $action);
            
            switch ($action) {
                case 'create':
                    // Crear un nuevo comentario
                    if (validateCommentInput($input)) {
                        $taskId = $input['task_id'];
                        
                        // Verificar que la tarea pertenezca al usuario
                        if (isTaskOwnedByUser($taskId, $userId)) {
                            $commentId = createComment($taskId, $input['description']);
                            
                            if ($commentId > 0) {
                                http_response_code(201);
                                echo json_encode([
                                    "id" => $commentId,
                                    "task_id" => $taskId,
                                    "description" => $input['description'],
                                    "created_at" => date('Y-m-d H:i:s')
                                ]);
                            } else {
                                http_response_code(500);
                                echo json_encode(['error' => "Error al crear el comentario"]);
                            }
                        } else {
                            http_response_code(403);
                            echo json_encode(["error" => "No tienes acceso a esta tarea"]);
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(["error" => "Datos insuficientes"]);
                    }
                    break;
                    
                case 'delete':
                    // Eliminar un comentario
                    if (isset($input['id'])) {
                        $commentId = $input['id'];
                        
                        // Verificar que el comentario pertenezca a una tarea del usuario
                        if (isCommentOwnedByUser($commentId, $userId)) {
                            if (deleteComment($commentId)) {
                                http_response_code(200);
                                echo json_encode(['success' => true, 'message' => "Comentario eliminado correctamente"]);
                            } else {
                                http_response_code(500);
                                echo json_encode(['error' => "Error al eliminar el comentario"]);
                            }
                        } else {
                            http_response_code(403);
                            echo json_encode(["error" => "No tienes acceso a este comentario"]);
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => "ID de comentario no proporcionado"]);
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(["error" => "Acción no válida"]);
            }
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido"]);
        }
    } catch (Exception $e) {
        debug_log("Error general en el proceso", $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => "Error en el servidor: " . $e->getMessage()]);
    }
} else {
    // No hay sesión activa
    debug_log("No hay sesión activa", "Error de autenticación");
    http_response_code(401);
    echo json_encode(["error" => "No hay sesión activa"]);
}
?>