<?php

require_once "conexao.php";

try {

    $nome = "Teste";
    $email = "teste" . time() . "@email.com"; // Gera um e-mail único
    $senha = password_hash("123456", PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nome, email, senha)
            VALUES (:nome, :email, :senha)";

    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':senha', $senha);

    if ($stmt->execute()) {
        echo "<h2>Usuário inserido com sucesso!</h2>";
        echo "<p>Nome: $nome</p>";
        echo "<p>Email: $email</p>";
        echo "<p>Senha (hash): $senha</p>";
    } else {
        echo "Erro ao inserir usuário.";
    }

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}

?>