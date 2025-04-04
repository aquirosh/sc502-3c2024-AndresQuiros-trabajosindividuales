<?php
require('db.php');

function createTask($userId, $title, $description, $dueDate)
{
    global $pdo;
    try {
        $sql = "insert into tasks (user_id, title, description, due_date) value (:user_id, :title, :description, :due_date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate
        ]);
        return $pdo->lastInsertId();

    } catch (Exception $e) {
        echo $e->getMessage();
        return 0;
    }
}

function getTasksByUser($userId)
{
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM tasks where user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        echo "Error al obtener las tareas del usuario" . $ex->getMessage();
        return [];
    }
}

function editTask($id, $title, $description, $dueDate)
{
    global $pdo;
    try {
        $sql = "update tasks set title = :title, description = :description, due_date = :due_date where id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate,
            'id' => $id
        ]);
        //si la cantidad de filas editadas es mayor a cero, retorne true, sino, retorne false;

        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
    }
}

function deleteTask($id)
{
    global $pdo;
    try {
        $sql = "delete from tasks where id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["id" => $id]);
        //si la cantidad de filas editadas es mayor a cero, retorne true, sino, retorne false;
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
    }
}