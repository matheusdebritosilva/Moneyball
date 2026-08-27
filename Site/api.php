<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

const DB_HOST = '127.0.0.1';
const DB_NAME = 'basquete_2k25';
const DB_USER = 'root';
const DB_PASS = '';

// Chave exigida para criar uma conta pela página de cadastro (cadastro.html).
// Troque esse valor antes de publicar o site em qualquer lugar público.
const SETUP_KEY = '2kstats-setup-2025';

function output(array $data, int $code = 200): never { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function body(): array { $data = json_decode(file_get_contents('php://input'), true); return is_array($data) ? $data : $_POST; }

// ---------------------------------------------------------------------
// Funções auxiliares para trabalhar com mysqli de forma procedural.
// mysqli_stmt_bind_param exige os parâmetros por referência, então esse
// helper monta as referências e chama mysqli_stmt_bind_param normalmente.
// Requer a extensão mysqlnd (padrão na maioria das instalações de PHP).
// ---------------------------------------------------------------------
function db_prepare(mysqli $conn, string $sql): mysqli_stmt {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) throw new RuntimeException(mysqli_error($conn));
    return $stmt;
}
function db_bind(mysqli_stmt $stmt, array $params): void {
    if (!$params) return;
    $types = '';
    foreach ($params as $p) {
        if (is_int($p)) $types .= 'i';
        elseif (is_float($p)) $types .= 'd';
        else $types .= 's';
    }
    $refs = [$stmt, $types];
    foreach ($params as $key => $value) $refs[] = &$params[$key];
    call_user_func_array('mysqli_stmt_bind_param', $refs);
}
function db_execute(mysqli $conn, string $sql, array $params = []): mysqli_stmt {
    $stmt = db_prepare($conn, $sql);
    db_bind($stmt, $params);
    if (!mysqli_stmt_execute($stmt)) throw new RuntimeException(mysqli_stmt_error($stmt));
    return $stmt;
}
function db_fetch_column(mysqli $conn, string $sql, array $params = []) {
    $stmt = db_execute($conn, $sql, $params);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_row($result);
    mysqli_stmt_close($stmt);
    return $row ? $row[0] : null;
}
function db_fetch_assoc(mysqli $conn, string $sql, array $params = []): ?array {
    $stmt = db_execute($conn, $sql, $params);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}
function db_fetch_all(mysqli $conn, string $sql, array $params = []): array {
    $stmt = db_execute($conn, $sql, $params);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_stmt_close($stmt);
    return $rows;
}
function db_query(mysqli $conn, string $sql): void {
    if (mysqli_query($conn, $sql) === false) throw new RuntimeException(mysqli_error($conn));
}

function deleteRemovedUsers(mysqli $conn, array $users, string $currentEmail): void {
    $emails = [];
    foreach ($users as $user) {
        $email = strtolower(trim((string)($user['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) $emails[] = $email;
    }
    if (!$emails) return;
    $placeholders = implode(',', array_fill(0, count($emails), '?'));
    $params = array_merge($emails, [$currentEmail, 'admin@2kstats.gg']);
    db_execute($conn, "DELETE FROM usuarios WHERE email NOT IN ($placeholders) AND email NOT IN (?, ?)", $params);
}
function saveUsers(mysqli $conn, array $users): void {
    foreach ($users as $user) {
        $name = trim((string)($user['name'] ?? ''));
        $email = strtolower(trim((string)($user['email'] ?? '')));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
        $role = (string)($user['role'] ?? 'Viewer');
        $role = $role === 'Admin' ? 'Administrador' : (in_array($role, ['Analista', 'Viewer'], true) ? $role : 'Viewer');
        $password = (string)($user['password'] ?? '');
        $id = db_fetch_column($conn, 'SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1', [$email]);
        if ($id && $password !== '') {
            db_execute($conn, 'UPDATE usuarios SET nome = ?, perfil = ?, senha = ? WHERE id_usuario = ?', [$name, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
        } elseif ($id) {
            db_execute($conn, 'UPDATE usuarios SET nome = ?, perfil = ? WHERE id_usuario = ?', [$name, $role, $id]);
        } else {
            db_execute($conn, 'INSERT INTO usuarios (nome, email, senha, perfil) VALUES (?, ?, ?, ?)', [$name, $email, password_hash($password !== '' ? $password : bin2hex(random_bytes(16)), PASSWORD_DEFAULT), $role]);
        }
    }
}
function syncTeams(mysqli $conn, array $teams): array {
    // Sincroniza a tabela `times` a partir do array vindo do app e devolve
    // um mapa [teamId do app] => [id_time real do banco] para vincular os jogadores.
    $map = [];
    foreach ($teams as $team) {
        $appId = (int)($team['id'] ?? 0);
        $name = trim((string)($team['name'] ?? ''));
        if ($appId <= 0 || $name === '') continue;
        $city = trim((string)($team['city'] ?? ''));
        $id = db_fetch_column($conn, 'SELECT id_time FROM times WHERE app_id = ? LIMIT 1', [$appId]);
        if ($id) {
            db_execute($conn, 'UPDATE times SET nome = ?, cidade = ? WHERE id_time = ?', [$name, $city, $id]);
            $map[$appId] = (int)$id;
        } else {
            db_execute($conn, "INSERT INTO times (nome, cidade, conferencia, divisao, overall_rating, app_id) VALUES (?, ?, 'Leste', 'Geral', 70, ?)", [$name, $city, $appId]);
            $map[$appId] = mysqli_insert_id($conn);
        }
    }
    return $map;
}
function deleteRemovedTeams(mysqli $conn, array $teams): void {
    $ids = array_values(array_filter(array_map(fn($t) => (int)($t['id'] ?? 0), $teams)));
    if (!$ids) return;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    db_execute($conn, "DELETE FROM times WHERE app_id IS NOT NULL AND app_id NOT IN ($placeholders)", $ids);
}
function existingTeamMap(mysqli $conn): array {
    $map = [];
    foreach (db_fetch_all($conn, 'SELECT app_id, id_time FROM times WHERE app_id IS NOT NULL') as $row) {
        $map[(int)$row['app_id']] = (int)$row['id_time'];
    }
    return $map;
}
function syncPlayers(mysqli $conn, array $players, array $teamMap): void {
    // Sincroniza a tabela `jogadores` com o array de jogadores do app, usando o
    // campo app_id (o id local do app) para casar cada jogador com a linha certa.
    $positions = ['PG', 'SG', 'SF', 'PF', 'C'];
    foreach ($players as $p) {
        $appId = (int)($p['id'] ?? 0);
        $name = trim((string)($p['name'] ?? ''));
        if ($appId <= 0 || $name === '') continue;
        $pos = in_array($p['pos'] ?? '', $positions, true) ? $p['pos'] : 'PG';
        $idTime = $teamMap[(int)($p['teamId'] ?? 0)] ?? null;
        $vals = [
            $name, $pos, $idTime,
            isset($p['age']) ? (int)$p['age'] : null,
            isset($p['games']) ? (int)$p['games'] : null,
            $p['pts'] ?? null, $p['reb'] ?? null, $p['ast'] ?? null,
            $p['stl'] ?? null, $p['blk'] ?? null, $p['fg'] ?? null,
        ];
        $id = db_fetch_column($conn, 'SELECT id_jogador FROM jogadores WHERE app_id = ? LIMIT 1', [$appId]);
        if ($id) {
            db_execute($conn, 'UPDATE jogadores SET nome=?, posicao=?, id_time=?, idade=?, jogos=?, pontos_media=?, rebotes_media=?, assistencias_media=?, roubos_media=?, tocos_media=?, aproveitamento_fg=? WHERE id_jogador=?', [...$vals, $id]);
        } else {
            db_execute($conn, 'INSERT INTO jogadores (nome, posicao, id_time, idade, jogos, pontos_media, rebotes_media, assistencias_media, roubos_media, tocos_media, aproveitamento_fg, app_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', [...$vals, $appId]);
        }
    }
}
function deleteRemovedPlayers(mysqli $conn, array $players): void {
    $ids = array_values(array_filter(array_map(fn($p) => (int)($p['id'] ?? 0), $players)));
    if (!$ids) return;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    db_execute($conn, "DELETE FROM jogadores WHERE app_id IS NOT NULL AND app_id NOT IN ($placeholders)", $ids);
}
function ensureSchema(mysqli $conn): void {
    // Ajusta as tabelas criadas em banco_de_dados.sql para acompanhar o app:
    // adiciona app_id (liga o registro local do app à linha real do banco) e
    // as colunas de estatísticas que o formulário de jogadores realmente usa.
    $userCols = array_column(db_fetch_all($conn, 'SHOW COLUMNS FROM usuarios'), 'Field');
    if (!in_array('perfil', $userCols, true)) {
        db_query($conn, "ALTER TABLE usuarios ADD COLUMN perfil ENUM('Administrador','Analista','Viewer') NOT NULL DEFAULT 'Viewer'");
    }
    $timeCols = array_column(db_fetch_all($conn, 'SHOW COLUMNS FROM times'), 'Field');
    if (!in_array('app_id', $timeCols, true)) {
        db_query($conn, 'ALTER TABLE times ADD COLUMN app_id INT NULL, ADD UNIQUE KEY uq_times_app_id (app_id)');
    }
    $playerCols = array_column(db_fetch_all($conn, 'SHOW COLUMNS FROM jogadores'), 'Field');
    if (!in_array('app_id', $playerCols, true)) {
        db_query($conn, 'ALTER TABLE jogadores
            ADD COLUMN app_id INT NULL,
            ADD UNIQUE KEY uq_jogadores_app_id (app_id),
            ADD COLUMN idade TINYINT UNSIGNED NULL,
            ADD COLUMN jogos SMALLINT UNSIGNED NULL,
            ADD COLUMN pontos_media DECIMAL(4,1) NULL,
            ADD COLUMN rebotes_media DECIMAL(4,1) NULL,
            ADD COLUMN assistencias_media DECIMAL(4,1) NULL,
            ADD COLUMN roubos_media DECIMAL(4,1) NULL,
            ADD COLUMN tocos_media DECIMAL(4,1) NULL,
            ADD COLUMN aproveitamento_fg DECIMAL(4,1) NULL,
            MODIFY altura_cm SMALLINT UNSIGNED NULL,
            MODIFY peso_kg SMALLINT UNSIGNED NULL,
            MODIFY numero_camisa TINYINT UNSIGNED NULL');
    }
}
function database(): mysqli {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
    if (!$conn) throw new RuntimeException('Erro na conexão: ' . mysqli_connect_error());
    mysqli_set_charset($conn, 'utf8mb4');
    mysqli_select_db($conn, DB_NAME);
    db_query($conn, 'CREATE TABLE IF NOT EXISTS app_state (id TINYINT PRIMARY KEY, content LONGTEXT NOT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB');
    ensureSchema($conn);
    $hasAdmin = (int)db_fetch_column($conn, "SELECT COUNT(*) FROM usuarios WHERE email = 'admin@2kstats.gg'");
    if (!$hasAdmin) {
        db_execute($conn, 'INSERT INTO usuarios (nome,email,senha,perfil) VALUES (?,?,?,?)', ['Administrador', 'admin@2kstats.gg', password_hash('admin123', PASSWORD_DEFAULT), 'Administrador']);
    }
    return $conn;
}
function requireLogin(): array { if (empty($_SESSION['user'])) output(['error' => 'Não autenticado'], 401); return $_SESSION['user']; }

try {
    $action = $_GET['action'] ?? '';
    if ($action === 'login') {
        $data = body(); $email = strtolower(trim((string)($data['email'] ?? ''))); $password = (string)($data['password'] ?? '');
        $conn = database();
        $user = db_fetch_assoc($conn, 'SELECT id_usuario,nome,email,senha,perfil FROM usuarios WHERE email = ? LIMIT 1', [$email]);
        if (!$user || !password_verify($password, $user['senha'])) output(['error' => 'E-mail ou senha inválidos.'], 422);
        $_SESSION['user'] = ['id'=>(int)$user['id_usuario'], 'name'=>$user['nome'], 'email'=>$user['email'], 'role'=>$user['perfil'] === 'Administrador' ? 'Admin' : 'Viewer']; output(['user'=>$_SESSION['user']]);
    }
    if ($action === 'logout') { session_destroy(); output(['ok'=>true]); }
    if ($action === 'register') {
        $data = body();
        if ((string)($data['setupKey'] ?? '') !== SETUP_KEY) output(['error' => 'Chave de cadastro inválida.'], 403);
        $name = trim((string)($data['name'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');
        $confirm = (string)($data['confirmPassword'] ?? '');
        if ($name === '') output(['error' => 'Informe o nome.'], 422);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) output(['error' => 'E-mail inválido.'], 422);
        if (strlen($password) < 6) output(['error' => 'A senha deve ter pelo menos 6 caracteres.'], 422);
        if ($password !== $confirm) output(['error' => 'As senhas não coincidem.'], 422);
        $conn = database();
        if (db_fetch_column($conn, 'SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1', [$email])) output(['error' => 'Já existe uma conta com esse e-mail.'], 409);
        db_execute($conn, 'INSERT INTO usuarios (nome, email, senha, perfil) VALUES (?, ?, ?, ?)', [$name, $email, password_hash($password, PASSWORD_DEFAULT), 'Administrador']);
        $row = db_fetch_assoc($conn, 'SELECT id_usuario,nome,email,perfil FROM usuarios WHERE email = ? LIMIT 1', [$email]);
        $_SESSION['user'] = ['id' => (int)$row['id_usuario'], 'name' => $row['nome'], 'email' => $row['email'], 'role' => 'Admin'];
        output(['user' => $_SESSION['user']]);
    }
    $user = requireLogin(); $conn = database();
    if ($action === 'session') output(['user'=>$user]);
    if ($action === 'load') { $row = db_fetch_column($conn, 'SELECT content FROM app_state WHERE id = 1'); output(['state'=>$row ? json_decode($row, true) : null, 'user'=>$user]); }
    if ($action === 'save') {
        if ($user['role'] === 'Viewer') output(['error'=>'Seu perfil tem somente permissão de leitura.'], 403);
        $state = body()['state'] ?? null; if (!is_array($state)) output(['error'=>'Dados inválidos.'], 422);
        $state['role'] = $user['role'];
        if (isset($state['users']) && is_array($state['users'])) {
            if ($user['role'] !== 'Admin') output(['error'=>'Somente administradores podem gerenciar usuários.'], 403);
            deleteRemovedUsers($conn, $state['users'], $user['email']);
            saveUsers($conn, $state['users']);
        }
        if (isset($state['teams']) && is_array($state['teams'])) {
            deleteRemovedTeams($conn, $state['teams']);
            $teamMap = syncTeams($conn, $state['teams']);
        } else {
            $teamMap = existingTeamMap($conn);
        }
        if (isset($state['players']) && is_array($state['players'])) {
            deleteRemovedPlayers($conn, $state['players']);
            syncPlayers($conn, $state['players'], $teamMap);
        }
        db_execute($conn, 'INSERT INTO app_state (id,content) VALUES (1,?) ON DUPLICATE KEY UPDATE content=VALUES(content)', [json_encode($state, JSON_UNESCAPED_UNICODE)]);
        output(['ok'=>true]);
    }
    output(['error'=>'Ação inválida.'], 404);
} catch (Throwable $e) { output(['error'=>'Erro no servidor: '.$e->getMessage()], 500); }
