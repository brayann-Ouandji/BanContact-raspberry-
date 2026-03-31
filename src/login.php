<?php
// src/login.php
require_once __DIR__ . '/Includes/auth.php';
require_once '/config.php';
Auth::start();
if (Auth::check()) { header('Location: ' . BASE_URL . 'dashboard.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$email || !$pass) {
        $err = 'Veuillez remplir tous les champs.';
    } elseif (!Auth::login($email, $pass)) {
        $err = 'Email ou mot de passe incorrect.';
    } else {
        header('Location: ' . BASE_URL . 'dashboard.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Connexion — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <span class="brand">RFID<span style="color:var(--text)">Pay</span></span>
    <p class="sub">Connectez-vous à votre espace</p>

    <?php if ($err): ?>
      <div class="alert alert-err"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="fg">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="vous@exemple.com" required autofocus>
      </div>
      <div class="fg">
        <label>Mot de passe</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button class="btn btn-primary btn-full" style="margin-top:.5rem">Se connecter</button>
    </form>
  </div>
</div>
</body>
</html>
