<? $user = $_SERVER["REMOTE_USER"]; ?>
<? if (strlen($user) > 0): ?>
    <p>
    Usuário: <?=$user?> [<a href="/logout.php">Sair</a>]
    </p>
<? else: ?>
    <p>Você não está autenticado.</p>
<? endif; ?>

<a href="/admin.php">Admin</a>
<a href="/info.php">Info</a>

