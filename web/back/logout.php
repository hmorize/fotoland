<? $user = $_SERVER["REMOTE_USER"];
if (strlen($user) > 0):
    header("HTTP/1.1 401 Unauthorized");
    header('WWW-Authenticate: Basic realm="Login required"');
    echo 'Saiu!';
else:
    ?>
        <p>Você não está autenticado.</p>
        <a href="/admin.php">Admin</a>
        <a href="/info.php">Info</a>
    <?
endif;
?>
