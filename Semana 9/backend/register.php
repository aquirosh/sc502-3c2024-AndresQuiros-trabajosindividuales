<?php
require('db.php');

function userRegistry($username, $password, $email) {
    try{
        global $pdo;
        //Encriptacion de password
        $passwordHashed = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (username, password, email) VALUES (:username, :password, :email)";
        $stmt = $pdo->prepare($sql);
        $stmt -> execute([
            'username' => $username,
            'password' => $passwordHashed,
            'email' => $email]);
        return "Usuario registered";


    } catch(Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}