<?php

$host = "localhost";
$banco = "basquete_2k25";
$usuario = "root";
$senha = "";

$conn = mysqli_connect($host, $usuario, $senha, $banco);

if (!$conn) {
    die("Erro na conexão: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
