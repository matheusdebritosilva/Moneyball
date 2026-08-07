<?php

session_start();

require_once "conexao.php";

$mensagem = "";
$tipoMensagem = "";

// Se já estiver logado, mostra mensagem de boas-vindas em vez do formulário
$logado = isset($_SESSION['usuario_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$logado) {

    $email       = trim($_POST['email'] ?? '');
    $senhaTexto  = $_POST['senha'] ?? '';

    if ($email === '' || $senhaTexto === '') {
        $mensagem = "Preencha e-mail e senha.";
        $tipoMensagem = "erro";
    } else {
        try {
            // Busca o usuário pelo e-mail
            $sql = "SELECT id_usuario, nome, senha FROM usuarios WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            $usuario = $stmt->fetch();

            // IMPORTANTE: nunca informe se o erro foi "e-mail não existe" ou
            // "senha errada" separadamente. Isso ajuda um atacante a descobrir
            // quais e-mails estão cadastrados no sistema (enumeração de usuários).
            if (!$usuario || !password_verify($senhaTexto, $usuario['senha'])) {
                $mensagem = "E-mail ou senha inválidos.";
                $tipoMensagem = "erro";
            } else {
                // Login correto: cria a sessão do usuário
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['usuario_nome'] = $usuario['nome'];

                $logado = true;
                $mensagem = "Login realizado com sucesso! Bem-vindo, " . $usuario['nome'] . ".";
                $tipoMensagem = "sucesso";
            }

        } catch (PDOException $e) {
            $mensagem = "Erro: " . $e->getMessage();
            $tipoMensagem = "erro";
        }
    }
}

// Logout simples via ?logout=1
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button, a.botao {
            display: block;
            margin-top: 20px;
            width: 100%;
            padding: 10px;
            background: #2d6cdf;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            text-align: center;
            text-decoration: none;
            box-sizing: border-box;
        }
        button:hover, a.botao:hover {
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
        .bem-vindo {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Login</h2>

        <?php if ($mensagem !== ""): ?>
            <div class="msg <?= $tipoMensagem ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($logado): ?>
            <div class="bem-vindo">
                <p>Você está logado como <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong>.</p>
                <a class="botao" href="login.php?logout=1">Sair</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>

                <button type="submit">Entrar</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
