<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

const DB_HOST = '127.0.0.1';
const DB_NAME = 'basquete_2k25';
const DB_USER = 'root';
const DB_PASS = '';

function output(array $data, int $code = 200): never { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function body(): array { $data = json_decode(file_get_contents('php://input'), true); return is_array($data) ? $data : $_POST; }
function deleteRemovedUsers(PDO $pdo, array $users, string $currentEmail): void {
    $emails = [];
    foreach ($users as $user) {
        $email = strtolower(trim((string)($user['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) $emails[] = $email;
    }
    if (!$emails) return;
    $placeholders = implode(',', array_fill(0, count($emails), '?'));
    $params = array_merge($emails, [$currentEmail, 'admin@2kstats.gg']);
    $pdo->prepare("DELETE FROM usuarios WHERE email NOT IN ($placeholders) AND email NOT IN (?, ?)")->execute($params);
}
function saveUsers(PDO $pdo, array $users): void {
    $find = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1');
    $update = $pdo->prepare('UPDATE usuarios SET nome = ?, perfil = ? WHERE id_usuario = ?');
    $updatePassword = $pdo->prepare('UPDATE usuarios SET nome = ?, perfil = ?, senha = ? WHERE id_usuario = ?');
    $insert = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, perfil) VALUES (?, ?, ?, ?)');
    foreach ($users as $user) {
        $name = trim((string)($user['name'] ?? ''));
        $email = strtolower(trim((string)($user['email'] ?? '')));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
        $role = (string)($user['role'] ?? 'Viewer');
        $role = $role === 'Admin' ? 'Administrador' : (in_array($role, ['Analista', 'Viewer'], true) ? $role : 'Viewer');
        $password = (string)($user['password'] ?? '');
        $find->execute([$email]);
        $id = $find->fetchColumn();
        if ($id && $password !== '') $updatePassword->execute([$name, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
        elseif ($id) $update->execute([$name, $role, $id]);
        else $insert->execute([$name, $email, password_hash($password !== '' ? $password : bin2hex(random_bytes(16)), PASSWORD_DEFAULT), $role]);
    }
}
function syncTeams(PDO $pdo, array $teams): array {
    // Sincroniza a tabela `times` a partir do array vindo do app e devolve
    // um mapa [teamId do app] => [id_time real do banco] para vincular os jogadores.
    $map = [];
    $find = $pdo->prepare('SELECT id_time FROM times WHERE app_id = ? LIMIT 1');
    $update = $pdo->prepare('UPDATE times SET nome = ?, cidade = ? WHERE id_time = ?');
    $insert = $pdo->prepare("INSERT INTO times (nome, cidade, conferencia, divisao, overall_rating, app_id) VALUES (?, ?, 'Leste', 'Geral', 70, ?)");
    foreach ($teams as $team) {
        $appId = (int)($team['id'] ?? 0);
        $name = trim((string)($team['name'] ?? ''));
        if ($appId <= 0 || $name === '') continue;
        $city = trim((string)($team['city'] ?? ''));
        $find->execute([$appId]);
        $id = $find->fetchColumn();
        if ($id) { $update->execute([$name, $city, $id]); $map[$appId] = (int)$id; }
        else { $insert->execute([$name, $city, $appId]); $map[$appId] = (int)$pdo->lastInsertId(); }
    }
    return $map;
}
function deleteRemovedTeams(PDO $pdo, array $teams): void {
    $ids = array_values(array_filter(array_map(fn($t) => (int)($t['id'] ?? 0), $teams)));
    if (!$ids) return;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM times WHERE app_id IS NOT NULL AND app_id NOT IN ($placeholders)")->execute($ids);
}
function existingTeamMap(PDO $pdo): array {
    $map = [];
    foreach ($pdo->query('SELECT app_id, id_time FROM times WHERE app_id IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[(int)$row['app_id']] = (int)$row['id_time'];
    }
    return $map;
}
function syncPlayers(PDO $pdo, array $players, array $teamMap): void {
    // Sincroniza a tabela `jogadores` com o array de jogadores do app, usando o
    // campo app_id (o id local do app) para casar cada jogador com a linha certa.
    $find = $pdo->prepare('SELECT id_jogador FROM jogadores WHERE app_id = ? LIMIT 1');
    $update = $pdo->prepare('UPDATE jogadores SET nome=?, posicao=?, id_time=?, idade=?, jogos=?, pontos_media=?, rebotes_media=?, assistencias_media=?, roubos_media=?, tocos_media=?, aproveitamento_fg=? WHERE id_jogador=?');
    $insert = $pdo->prepare('INSERT INTO jogadores (nome, posicao, id_time, idade, jogos, pontos_media, rebotes_media, assistencias_media, roubos_media, tocos_media, aproveitamento_fg, app_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
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
        $find->execute([$appId]);
        $id = $find->fetchColumn();
        if ($id) $update->execute([...$vals, $id]);
        else $insert->execute([...$vals, $appId]);
    }
}
function deleteRemovedPlayers(PDO $pdo, array $players): void {
    $ids = array_values(array_filter(array_map(fn($p) => (int)($p['id'] ?? 0), $players)));
    if (!$ids) return;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM jogadores WHERE app_id IS NOT NULL AND app_id NOT IN ($placeholders)")->execute($ids);
}
function ensureSchema(PDO $pdo): void {
    // Ajusta as tabelas criadas em banco_de_dados.sql para acompanhar o app:
    // adiciona app_id (liga o registro local do app à linha real do banco) e
    // as colunas de estatísticas que o formulário de jogadores realmente usa.
    $timeCols = $pdo->query('SHOW COLUMNS FROM times')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('app_id', $timeCols, true)) {
        $pdo->exec('ALTER TABLE times ADD COLUMN app_id INT NULL, ADD UNIQUE KEY uq_times_app_id (app_id)');
    }
    $playerCols = $pdo->query('SHOW COLUMNS FROM jogadores')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('app_id', $playerCols, true)) {
        $pdo->exec('ALTER TABLE jogadores
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
function database(): PDO {
    $pdo = new PDO('mysql:host='.DB_HOST.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('USE `'.DB_NAME.'`');
    $pdo->exec('CREATE TABLE IF NOT EXISTS app_state (id TINYINT PRIMARY KEY, content LONGTEXT NOT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB');
    ensureSchema($pdo);
    $hasAdmin = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE email = 'admin@2kstats.gg'")->fetchColumn();
    if (!$hasAdmin) { $stmt = $pdo->prepare('INSERT INTO usuarios (nome,email,senha,perfil) VALUES (?,?,?,?)'); $stmt->execute(['Administrador', 'admin@2kstats.gg', password_hash('admin123', PASSWORD_DEFAULT), 'Administrador']); }
    return $pdo;
}
function requireLogin(): array { if (empty($_SESSION['user'])) output(['error' => 'Não autenticado'], 401); return $_SESSION['user']; }

try {
    $action = $_GET['action'] ?? '';
    if ($action === 'login') {
        $data = body(); $email = strtolower(trim((string)($data['email'] ?? ''))); $password = (string)($data['password'] ?? '');
        $stmt = database()->prepare('SELECT id_usuario,nome,email,senha,perfil FROM usuarios WHERE email = ? LIMIT 1'); $stmt->execute([$email]); $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user['senha'])) output(['error' => 'E-mail ou senha inválidos.'], 422);
        $_SESSION['user'] = ['id'=>(int)$user['id_usuario'], 'name'=>$user['nome'], 'email'=>$user['email'], 'role'=>$user['perfil'] === 'Administrador' ? 'Admin' : 'Viewer']; output(['user'=>$_SESSION['user']]);
    }
    if ($action === 'logout') { session_destroy(); output(['ok'=>true]); }
    $user = requireLogin(); $pdo = database();
    if ($action === 'session') output(['user'=>$user]);
    if ($action === 'load') { $row = $pdo->query('SELECT content FROM app_state WHERE id = 1')->fetchColumn(); output(['state'=>$row ? json_decode($row, true) : null, 'user'=>$user]); }
    if ($action === 'save') {
        if ($user['role'] === 'Viewer') output(['error'=>'Seu perfil tem somente permissão de leitura.'], 403);
        $state = body()['state'] ?? null; if (!is_array($state)) output(['error'=>'Dados inválidos.'], 422);
        $state['role'] = $user['role'];
        if (isset($state['users']) && is_array($state['users'])) {
            if ($user['role'] !== 'Admin') output(['error'=>'Somente administradores podem gerenciar usuários.'], 403);
            deleteRemovedUsers($pdo, $state['users'], $user['email']);
            saveUsers($pdo, $state['users']);
        }
        if (isset($state['teams']) && is_array($state['teams'])) {
            deleteRemovedTeams($pdo, $state['teams']);
            $teamMap = syncTeams($pdo, $state['teams']);
        } else {
            $teamMap = existingTeamMap($pdo);
        }
        if (isset($state['players']) && is_array($state['players'])) {
            deleteRemovedPlayers($pdo, $state['players']);
            syncPlayers($pdo, $state['players'], $teamMap);
        }
        $stmt = $pdo->prepare('INSERT INTO app_state (id,content) VALUES (1,?) ON DUPLICATE KEY UPDATE content=VALUES(content)'); $stmt->execute([json_encode($state, JSON_UNESCAPED_UNICODE)]);
        output(['ok'=>true]);
    }
    output(['error'=>'Ação inválida.'], 404);
} catch (Throwable $e) { output(['error'=>'Erro no servidor: '.$e->getMessage()], 500); }
