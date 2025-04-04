<?php
require('db.php');

function userRegistry($username, $password, $email){
    try{
        global $pdo;
        //encriptacion del password de usuario
        $passwordHashed = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO USERS (username, password, email) values (:username, :password, :email)";
        $stmt = $pdo -> prepare($sql);
        $stmt -> execute(params: ['username' => $username, 'password' => $passwordHashed, 'email' => $email]);
        return "User registered";
        
    }catch(Exception $e){
    }
}

$method = $_SERVER["REQUEST_METHOD"];
