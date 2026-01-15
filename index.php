<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Telegram Auth Demo | Minimal & Secure Telegram Login Example (PHP)</title>

  <!-- Telegram Mini App JS -->
  <script src="https://telegram.org/js/telegram-web-app.js?59"></script>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<?php if (empty($_SESSION['tg_user'])): ?>

  <h2>You are not authenticated</h2>

  <div id="tg-login-container"></div>

  <script>
  function onTelegramAuth(user) {
      $.post('/webapp.php', { user: user })
        .done(() => location.reload())
        .fail(xhr => {
            $('body').html(
              '<h3>Authentication error</h3><pre>' + xhr.responseText + '</pre>'
            );
        });
  }

  $(function () {
      const $c = $('#tg-login-container');

      if (typeof window.Telegram === 'undefined' || !Telegram.WebApp || !Telegram.WebApp.initData) {
          // No Mini App — load Telegram Login Widget
          $('<script>', {
              src: 'https://telegram.org/js/telegram-widget.js?22',
              async: true,
              'data-telegram-login': 'tglogin_nipaa_bot',
              'data-size': 'large',
              'data-onauth': 'onTelegramAuth(user)',
              'data-userpic': 'true',
              'data-request-access': 'read'
          }).appendTo($c);
      } else {
          // Mini App available — show Mini App login button
          $('<button>', {
              text: 'Log in via Telegram Mini App',
              click: () => location.href = '/webapp.php'
          }).appendTo($c);
      }
  });
  </script>

<?php else: ?>

  <h2>You are authenticated</h2>
  <p>Hello, <b><?= htmlspecialchars($_SESSION['tg_user']['first_name']) ?></b></p>
  <p>Telegram ID: <?= (int)$_SESSION['tg_user']['id'] ?></p>

  <form method="post" action="/logout.php">
      <button type="submit">Log out</button>
  </form>

<?php endif; ?>

</body>
</html>
