<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$db            = null;
$errorConexion = '';
$errores       = [];
$usuarios      = [];

// Datos del formulario. Con $editando distinto de null, el formulario actualiza.
$editando = null;
$form     = ['username' => '', 'email' => ''];

try {
    $db = getDB();
} catch (PDOException $e) {
    $errorConexion = $e->getMessage();
}

if ($db !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string) ($_POST['accion'] ?? '');

    // ---------- Eliminar ----------
    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
        }

        // POST-Redirect-GET: al recargar con F5 no se repite la operacion.
        header('Location: index.php?ok=eliminado');
        exit;
    }

    // ---------- Crear o actualizar ----------
    if ($accion === 'guardar') {
        $id       = (int) ($_POST['id'] ?? 0);
        $username = trim((string) ($_POST['username'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));

        if (strlen($username) < 3 || strlen($username) > 50) {
            $errores[] = 'El usuario debe tener entre 3 y 50 caracteres.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo no tiene un formato valido.';
        }

        if ($errores === []) {
            try {
                if ($id > 0) {
                    $stmt = $db->prepare('UPDATE usuarios SET username = ?, email = ? WHERE id = ?');
                    $stmt->execute([$username, ($email !== '' ? $email : null), $id]);
                } else {
                    $stmt = $db->prepare('INSERT INTO usuarios (username, email) VALUES (?, ?)');
                    $stmt->execute([$username, ($email !== '' ? $email : null)]);
                }

                header('Location: index.php?ok=' . ($id > 0 ? 'actualizado' : 'creado'));
                exit;
            } catch (PDOException $e) {
                // 23000 = violacion de restriccion: aqui, el UNIQUE de username.
                if ($e->getCode() === '23000') {
                    $errores[] = 'Ya existe un usuario con ese nombre.';
                } else {
                    throw $e;
                }
            }
        }

        // Si hubo errores se repinta el formulario con lo que ya se habia escrito.
        $form     = ['username' => $username, 'email' => $email];
        $editando = $id > 0 ? $id : null;
    }
}

// ---------- Cargar un registro para editar ----------
if ($db !== null && $errores === [] && isset($_GET['editar'])) {
    $stmt = $db->prepare('SELECT id, username, email FROM usuarios WHERE id = ?');
    $stmt->execute([(int) $_GET['editar']]);
    $fila = $stmt->fetch();

    if ($fila !== false) {
        $editando = (int) $fila['id'];
        $form     = ['username' => (string) $fila['username'], 'email' => (string) ($fila['email'] ?? '')];
    }
}

// ---------- Listado ----------
if ($db !== null) {
    $usuarios = $db->query('SELECT id, username, email, created_at FROM usuarios ORDER BY id')->fetchAll();
}

$mensajes = [
    'creado'      => 'Usuario registrado correctamente.',
    'actualizado' => 'Usuario actualizado correctamente.',
    'eliminado'   => 'Usuario eliminado correctamente.',
];

$aviso = $mensajes[$_GET['ok'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docker Ejemplo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #1a1a2e; color: #eee; min-height: 100vh; display: flex; flex-direction: column; }

        header { background: #16213e; border-bottom: 2px solid #0f3460; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        header .brand { font-weight: bold; font-size: 1rem; }
        .conn { font-size: 0.82rem; color: #556; }
        .conn span { color: #4a90d9; }

        main { flex: 1; width: 100%; max-width: 860px; margin: 2rem auto; padding: 0 1rem; }

        h2 { font-size: 1rem; color: #ccc; margin-bottom: 1rem; }

        .alert { padding: 10px 14px; border-radius: 4px; font-size: 0.85rem; margin-bottom: 1.2rem; }
        .alert.ok  { background: #12372a; color: #6ee7a8; border: 1px solid #1d5c45; }
        .alert.err { background: #3b1220; color: #ff9a9a; border: 1px solid #5c1d2e; }
        .alert ul  { margin: 6px 0 0 18px; }

        .panel { background: #16213e; border: 1px solid #0f3460; border-radius: 4px; padding: 16px; margin-bottom: 1.6rem; }
        .panel h3 { font-size: 0.85rem; color: #aaa; font-weight: normal; margin-bottom: 12px; }

        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
        .field { flex: 1 1 200px; }
        label { display: block; font-size: 0.78rem; color: #aaa; margin-bottom: 5px; }
        input[type=text], input[type=email] { width: 100%; padding: 8px 10px; background: #1a1a2e; border: 1px solid #0f3460; border-radius: 4px; color: #eee; font-size: 0.88rem; font-family: inherit; }
        input:focus { outline: none; border-color: #4a90d9; }

        .btn { padding: 8px 16px; border: 0; border-radius: 4px; background: #4a90d9; color: #fff; font-size: 0.85rem; font-family: inherit; cursor: pointer; }
        .btn:hover { background: #357ac0; }
        .btn.ghost { background: transparent; border: 1px solid #0f3460; color: #aaa; text-decoration: none; display: inline-block; }
        .btn.ghost:hover { border-color: #4a90d9; color: #fff; }
        .btn.mini { padding: 4px 10px; font-size: 0.78rem; }
        .btn.danger { background: transparent; border: 1px solid #5c1d2e; color: #ff9a9a; }
        .btn.danger:hover { background: #5c1d2e; color: #fff; }

        table { width: 100%; border-collapse: collapse; background: #16213e; border: 1px solid #0f3460; border-radius: 4px; font-size: 0.88rem; }
        thead { background: #0f3460; }
        th { padding: 10px 14px; text-align: left; font-weight: normal; color: #aaa; font-size: 0.82rem; }
        td { padding: 9px 14px; border-bottom: 1px solid #0f3460; color: #ddd; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #0f3460; }
        td.acciones { text-align: right; white-space: nowrap; }
        td.acciones form { display: inline; }
        .vacio { padding: 22px; text-align: center; color: #667; font-size: 0.88rem; }

        footer { text-align: center; padding: 12px; font-size: 0.78rem; color: #555; border-top: 1px solid #0f3460; background: #16213e; }
    </style>
</head>
<body>
    <header>
        <span class="brand">Docker Ejemplo</span>
        <span class="conn">Base de datos: <span><?= e(DB_NAME) ?></span> en <span><?= e(DB_HOST) ?>:<?= e(DB_PORT) ?></span></span>
    </header>

    <main>
        <?php if ($errorConexion !== ''): ?>

            <div class="alert err">
                <strong>No hay conexion con MySQL.</strong>
                <ul><li><?= e($errorConexion) ?></li></ul>
            </div>

        <?php else: ?>

            <?php if ($aviso !== ''): ?>
                <div class="alert ok"><?= e($aviso) ?></div>
            <?php endif; ?>

            <?php if ($errores !== []): ?>
                <div class="alert err">
                    Revisa los siguientes datos:
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="panel">
                <h3><?= $editando !== null ? 'Editar usuario #' . (int) $editando : 'Nuevo usuario' ?></h3>

                <form method="post" action="index.php">
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="id" value="<?= (int) $editando ?>">

                    <div class="row">
                        <div class="field">
                            <label for="username">Usuario</label>
                            <input type="text" id="username" name="username" maxlength="50"
                                   placeholder="usuario1" value="<?= e($form['username']) ?>" required>
                        </div>

                        <div class="field">
                            <label for="email">Correo</label>
                            <input type="email" id="email" name="email" maxlength="100"
                                   placeholder="usuario1@test.com" value="<?= e($form['email']) ?>">
                        </div>

                        <div>
                            <button type="submit" class="btn"><?= $editando !== null ? 'Actualizar' : 'Agregar' ?></button>
                            <?php if ($editando !== null): ?>
                                <a href="index.php" class="btn ghost">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <h2>Usuarios registrados (<?= count($usuarios) ?>)</h2>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Registrado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usuarios === []): ?>
                        <tr><td colspan="5" class="vacio">No hay usuarios registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= (int) $u['id'] ?></td>
                                <td><?= e($u['username']) ?></td>
                                <td><?= e($u['email'] ?? '—') ?></td>
                                <td><?= e(date('d/m/Y H:i', strtotime((string) $u['created_at']))) ?></td>
                                <td class="acciones">
                                    <a href="index.php?editar=<?= (int) $u['id'] ?>" class="btn ghost mini">Editar</a>
                                    <form method="post" action="index.php"
                                          onsubmit="return confirm('Eliminar a <?= e($u['username']) ?>?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="btn danger mini">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </main>

    <footer> Docker &mdash; PHP <?= e(PHP_VERSION) ?></footer>
</body>
</html>
