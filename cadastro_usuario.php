<?php

require_once "conexao.php";

$mensagem = "";
$tipoMensagem = ""; // "sucesso" ou "erro"

// Só processa quando o formulário for enviado (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Pega os dados enviados pelo formulário
    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senhaTexto = $_POST['senha'] ?? '';

    // Validação simples dos campos
    if ($nome === '' || $email === '' || $senhaTexto === '') {
        $mensagem = "Preencha todos os campos.";
        $tipoMensagem = "erro";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "E-mail inválido.";
        $tipoMensagem = "erro";
    } elseif (strlen($senhaTexto) < 6) {
        $mensagem = "A senha deve ter pelo menos 6 caracteres.";
        $tipoMensagem = "erro";
    } else {
        try {
            $senha = password_hash($senhaTexto, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios (nome, email, senha)
                    VALUES (:nome, :email, :senha)";

            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':senha', $senha);

            if ($stmt->execute()) {
                $mensagem = "Usuário '$nome' inserido com sucesso!";
                $tipoMensagem = "sucesso";
            } else {
                $mensagem = "Erro ao inserir usuário.";
                $tipoMensagem = "erro";
            }

        } catch (PDOException $e) {
            // Código 23000 = violação de restrição (ex: e-mail duplicado, que é UNIQUE)
            if ($e->getCode() == 23000) {
                $mensagem = "Já existe um usuário cadastrado com este e-mail.";
            } else {
                $mensagem = "Erro: " . $e->getMessage();
            }
            $tipoMensagem = "erro";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Usuário</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            padding-top: 60px;
        }
        .card {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            width: 350px;
        }
        h2 {
            margin-top: 0;
            text-align: center;
        }
        label {
            display: block;
            margin-top: 12px;
            margin-bottom: 4px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            margin-top: 20px;
            width: 100%;
            padding: 10px;
            background: #2d6cdf;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
        }
        button:hover {
            background: #1f56b8;
        }
        .msg {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }
        .sucesso {
            background: #d4edda;
            color: #155724;
        }
        .erro {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Cadastrar Usuário</h2>

        <?php if ($mensagem !== ""): ?>
            <div class="msg <?= $tipoMensagem ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" required
                   value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required minlength="6">

            <button type="submit">Cadastrar</button>
        </form>
    </div>
</body>
</html>
