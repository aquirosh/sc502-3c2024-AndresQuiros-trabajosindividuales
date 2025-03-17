<?php
require('db.php');

function createTask($userId, $title, $description, $dueDate) {
    try{
        global $pdo;
        $sql = "INSERT INTO tasks (user_id, title, description, due_date) VALUES (:user_id, :title, :description, :due_date)";
        $stmt = $pdo->prepare($sql);
        $stmt -> execute([
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate]);
        return $pdo->lastInsertId();

    } catch(Exception $e) {
        echo "Error: " . $e->getMessage();
        return 0;
    }
}


function getTaskByUser($user_id) {
    try{
        global $pdo;
        $sql = "SELECT * FROM tasks WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt -> execute(['user_id' => $user_id]);
        $tasks = $stmt -> fetchAll(PDO::FETCH_ASSOC);
        return $tasks;

    } catch(Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

function editTask($id, $title, $description, $dueDate) {
    try{
        global $pdo;
        $sql = "UPDATE tasks SET title = :title, description = :description, due_date = :due_date WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt -> execute([
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate]);
        return "Tarea editada";

    } catch(Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

function deleteTask($id) {
    try{
        global $pdo;
        $sql = "DELETE FROM tasks WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt -> execute(['id' => $id]);
        return "Tarea eliminada";

    } catch(Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}