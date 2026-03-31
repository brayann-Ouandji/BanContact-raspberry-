<?php
// src/dashboard.php
require_once __DIR__ . '/Includes/auth.php';
//require_once '/config.php';
Auth::require();

$db     = DB::get();
$userId = Auth::id();
$role   = Auth::role();

// Solde du compte courant
$solde = $db->prepare('SELECT solde FROM comptes WHERE user_id = ?');
$solde->execute([$userId]);
$solde = (float)($solde->fetchColumn() ?? 0);

// Stats selon rôle
if ($role === 'admin') {
    $stats = $db->query("
        SELECT
          (SELECT COUNT(*) FROM users WHERE role = 'user'     AND actif = 1) AS nb_users,
          (SELECT COUNT(*) FROM users WHERE role = 'merchant' AND actif = 1) AS nb_merchants,
          (SELECT COUNT(*) FROM badges)                                       AS nb_badges,
          (SELECT IFNULL(SUM(montant),0) FROM transactions WHERE type = 'paiement') AS total
    ")->fetch();
} elseif ($role === 'merchant') {
    $s = $db->prepare("SELECT COUNT(*) AS nb, IFNULL(SUM(montant),0) AS total FROM transactions WHERE merchant_id = ? AND type = 'paiement'");
    $s->execute([$userId]);
    $stats = $s->fetch();
}

// Dernières transactions
$limit = ($role === 'admin') ? 10 : 5;
if ($role === 'admin') {
    $txs = $db->query("
        SELECT t.*, u.prenom, u.nom
        FROM transactions t JOIN users u ON u.id = t.user_id
        ORDER BY t.created_at DESC LIMIT $limit
    ")->fetchAll();
} else {
    $s = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit");
    $s->execute([$userId]);
    $txs = $s->fetchAll();
}

$title = 'Tableau de bord';
require 'Pages/_header.php';
?>

<div class="wrap">
  <div class="ph">
    <div>
      <h1>Tableau de bord</h1>
      <p>Bienvenue, <?= htmlspecialchars($_SESSION['user_nom']) ?> 👋</p>
    </div>
    <?php if ($role === 'user'): ?>
      <a href="Pages/crediter.php" class="btn btn-primary">+ Créditer mon compte</a>
    <?php endif; ?>
  </div>

  <div class="grid">
    <?php if ($role === 'admin'): ?>
      <div class="stat"><div class="lbl">Utilisateurs actifs</div><div class="val green"><?= $stats['nb_users'] ?></div></div>
      <div class="stat"><div class="lbl">Commerçants actifs</div><div class="val blue"><?= $stats['nb_merchants'] ?></div></div>
      <div class="stat"><div class="lbl">Badges enregistrés</div><div class="val"><?= $stats['nb_badges'] ?></div></div>
      <div class="stat"><div class="lbl">Total paiements</div><div class="val green"><?= number_format($stats['total'], 2, ',', ' ') ?> €</div></div>
    <?php elseif ($role === 'merchant'): ?>
      <div class="stat"><div class="lbl">Transactions reçues</div><div class="val blue"><?= $stats['nb'] ?></div></div>
      <div class="stat"><div class="lbl">Total encaissé</div><div class="val green"><?= number_format($stats['total'], 2, ',', ' ') ?> €</div></div>
    <?php else: ?>
      <div class="stat"><div class="lbl">Solde disponible</div><div class="val green"><?= number_format($solde, 2, ',', ' ') ?> €</div></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2 style="font-size:1rem;margin-bottom:1.4rem">
      <?= $role === 'admin' ? 'Dernières transactions (toutes)' : 'Mes dernières transactions' ?>
    </h2>
    <?php if (!$txs): ?>
      <p style="color:var(--muted)">Aucune transaction pour l'instant.</p>
    <?php else: ?>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <?php if ($role === 'admin'): ?><th>Titulaire</th><?php endif; ?>
            <th>Type</th><th>Montant</th><th>Date</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($txs as $t): ?>
          <tr>
            <?php if ($role === 'admin'): ?>
              <td><?= htmlspecialchars($t['prenom'].' '.$t['nom']) ?></td>
            <?php endif; ?>
            <td>
              <?php if ($t['type'] === 'credit'): ?>
                <span class="tag tag-green">Crédit</span>
              <?php else: ?>
                <span class="tag tag-red">Paiement</span>
              <?php endif; ?>
            </td>
            <td style="font-family:var(--mono)">
              <?= $t['type'] === 'paiement' ? '-' : '+' ?><?= number_format($t['montant'], 2, ',', ' ') ?> €
            </td>
            <td style="color:var(--muted);font-size:.83rem"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<footer><?= APP_NAME ?></footer>
</body></html>
