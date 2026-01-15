<?php
session_start();

// If the user is already authenticated — redirect to homepage
if (!empty($_SESSION['tg_user'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /');
    }
    exit;
}

// Handle POST request — Telegram authentication via auth.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Expecting Telegram data under POST['user']
    if (!isset($_POST['user'])) {
        http_response_code(400);
        exit('No Telegram data received');
    }

    // Include TelegramUnifiedAuth class
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

    $auth = new TelegramUnifiedAuth('YOU_CAN_PUT_YOUR_TG_TOKEN_HERE_OR_INSIDE_THE_CLASS');

    $is_check = $auth->check($_POST['user']);

    if (!$is_check) {
        http_response_code(403);
        exit($is_check === 0 ? 'The data is expired' : 'Invalid Telegram auth');
    }

    $data = $auth->get($_POST['user']);

    if (empty($data['id'])) {
        http_response_code(403);
        exit('Telegram ID was not found');
    }

    $_SESSION['tg_user'] = $data;
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Telegram WebApp Authentication</title>

    <!-- Telegram Mini App JS API -->
    <script src="https://telegram.org/js/telegram-web-app.js?59"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<h3>Authenticating via Telegram, please wait...</h3>

<div id="tg-auth-container"></div>

<script>
$(document).ready(function () {

    // Check if this is opened inside Telegram Mini App
    if (typeof window.Telegram === 'undefined' || !Telegram.WebApp || !Telegram.WebApp.initData) {
        // Not a Mini App — redirect to homepage
        $(location).attr('href', '/');
        return;
    }

    // Send initData to server for validation via AJAX
    $.post('/webapp.php', { user: Telegram.WebApp.initData })
        .done(function () {
            // Auth successful — redirect to homepage
            $(location).attr('href', '/');
        })
        .fail(function (xhr) {
            $('#tg-auth-container').html(
                $('<div>').append(
                    $('<h3>').text('Telegram authentication failed'),
                    $('<pre>').text(xhr.responseText)
                )
            );
        });

});
</script>

</body>
</html>
