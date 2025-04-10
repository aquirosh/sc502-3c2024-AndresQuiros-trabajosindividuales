<?php
require('db.php');

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
        echo $e->getMessage();
        return 0;
    }
}

function getCommentsByTaskId($taskId)
{
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM comments WHERE task_id = :task_id ORDER BY created_at DESC");
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        echo "Error al obtener los comentarios de la tarea: " . $ex->getMessage();
        return [];
    }
}

function editComment($id, $description)
{
    global $pdo;
    try {
        $sql = "UPDATE comments SET description = :description WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'description' => $description,
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
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
        echo $e->getMessage();
        return false;
    }
}

function validateCommentInput($input)
{
    if (isset($input['description'], $input['task_id'])) {
        return true;
    }
    return false;
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
        echo $e->getMessage();
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
        echo $e->getMessage();
        return false;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

function getJsonInput()
{
    return json_decode(file_get_contents("php://input"), true);
}

session_start();

if (isset($_SESSION["user_id"])) {
    try {
        $userId = $_SESSION["user_id"];
        
        switch ($method) {
            case 'GET':
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
                break;
                
            case 'POST':
                $input = getJsonInput();
                
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
                            echo json_encode(['error' => "Error general creando el comentario"]);
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
                
            case 'PUT':
                if (isset($_GET['id'])) {
                    $commentId = $_GET['id'];
                    
                    // Verificar que el comentario pertenezca a una tarea del usuario
                    if (isCommentOwnedByUser($commentId, $userId)) {
                        $input = getJsonInput();
                        
                        if (isset($input['description'])) {
                            if (editComment($commentId, $input['description'])) {
                                http_response_code(200);
                                
                                // Obtener los datos actualizados del comentario
                                global $pdo;
                                $stmt = $pdo->prepare("SELECT * FROM comments WHERE id = :id");
                                $stmt->execute(['id' => $commentId]);
                                $comment = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                echo json_encode($comment);
                            } else {
                                http_response_code(500);
                                echo json_encode(['error' => "Error interno al actualizar el comentario"]);
                            }
                        } else {
                            http_response_code(400);
                            echo json_encode(['error' => "Descripción no proporcionada"]);
                        }
                    } else {
                        http_response_code(403);
                        echo json_encode(["error" => "No tienes acceso a este comentario"]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["error" => "ID de comentario no proporcionado"]);
                }
                break;
                
            case 'DELETE':
                if (isset($_GET['id'])) {
                    $commentId = $_GET['id'];
                    
                    // Verificar que el comentario pertenezca a una tarea del usuario
                    if (isCommentOwnedByUser($commentId, $userId)) {
                        if (deleteComment($commentId)) {
                            http_response_code(200);
                            echo json_encode(['message' => "Comentario eliminado exitosamente"]);
                        } else {
                            http_response_code(500);
                            echo json_encode(['error' => "Error interno al eliminar el comentario"]);
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
                http_response_code(405);
                echo json_encode(["error" => "Método no permitido"]);
        }
    } catch (Exception $exp) {
        http_response_code(500);
        echo json_encode(['error' => "Error al procesar el request: " . $exp->getMessage()]);
    }
} else {
    http_response_code(401);
    echo json_encode(["error" => "Sesión no activa"]);
}
?>