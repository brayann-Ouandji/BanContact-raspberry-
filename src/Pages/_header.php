<?php
// src/pages/_header.php  — inclus par toutes les pages protégées
$role = Auth::role();
if ($role === 'admin') {
    $roleLabel = 'Admin';
    $roleClass = 'red';
} elseif ($role === 'merchant') {
    $roleLabel = 'Commerçant';
    $roleClass = 'blue';
} else {
    $roleLabel = 'Utilisateur';
    $roleClass = 'green';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $title ?? APP_NAME ?></title>
  <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
<header>
  <a class="logo" href="../dashboard.php">RFID<span>Pay</span></a>
  <nav>
    <a href="../dashboard.php">Tableau de bord</a>
    <?php if ($role === 'admin' || $role === 'merchant'): ?>
    <a href="paiement.php">Paiement</a>
    <?php endif; ?>
    <?php if ($role === 'admin'): ?>
    <a href="admin_users.php">Utilisateurs</a>
    <a href="admin_badges.php">Badges</a>
    <?php endif; ?>
    <a href="transactions.php">Historique</a>
  </nav>
  <div style="display:flex;align-items:center;gap:.9rem">
    <span class="tag tag-<?= $roleClass ?>"><?= $roleLabel ?></span>
    <span style="font-size:.88rem;color:var(--muted)"><?= htmlspecialchars($_SESSION['user_nom']) ?></span>
    <form method="POST" action="../logout.php" style="margin:0">
      <button class="btn-logout">Déconnexion</button>
    </form>
  </div>
</header>
