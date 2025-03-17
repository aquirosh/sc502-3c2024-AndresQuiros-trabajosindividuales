<?php
$host = "127.0.0.1";
$dbname = "todo_app";
$user = "root";
$password = "NmQh0103%";

try {

    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa".PHP_EOL;


} catch (PDOException $e) {
    die("Error de conexion". $e->getMessage());
}
?>