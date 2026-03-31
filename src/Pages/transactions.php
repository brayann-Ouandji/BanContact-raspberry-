<?php
// src/pages/transactions.php
require_once __DIR__ . '/../../src/Includes/auth.php';
Auth::require();

$db     = DB::get();
$userId = Auth::id();
$role   = Auth::role();
$type   = in_array($_GET['type'] ?? '', ['credit','paiement']) ? $_GET['type'] : 'all';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

// Filtres
$where = ($role === 'admin') ? '1=1' : "t.user_id = $userId";
if ($type !== 'all') $where .= " AND t.type = '$type'";

$txs = $db->query("
    SELECT t.*, u.prenom, u.nom, m.prenom AS m_prenom, m.nom AS m_nom
    FROM transactions t
    JOIN users u ON u.id = t.user_id
    LEFT JOIN users m ON m.id = t.merchant_id
    WHERE $where
    ORDER BY t.created_at DESC
    LIMIT $limit OFFSET $offset
")->fetchAll();

$total = $db->query("SELECT COUNT(*) FROM transactions t WHERE $where")->fetchColumn();
$pages = max(1, ceil($total / $limit));

$title = 'Historique';
require '_header.php';
?>

<div class="wrap">
  <div class="ph">
    <div><h1>Historique des transactions</h1><p><?= $total ?> transaction(s)</p></div>
    <div style="display:flex;gap:.4rem">
      <?php foreach (['all' => 'Tout', 'credit' => 'Crédits', 'paiement' => 'Paiements'] as $k => $v): ?>
        <a href="?type=<?= $k ?>" class="btn btn-sm <?= $type === $k ? 'btn-primary' : 'btn-secondary' ?>"><?= $v ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <?php if ($role === 'admin'): ?><th>Titulaire</th><?php endif; ?>
            <th>Type</th><th>Montant</th>
            <?php if ($role !== 'user'): ?><th>Commerçant</th><?php endif; ?>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$txs): ?>
          <tr><td colspan="5" style="color:var(--muted);text-align:center;padding:2rem">Aucune transaction.</td></tr>
        <?php endif; ?>
        <?php foreach ($txs as $t): ?>
          <tr>
            <?php if ($role === 'admin'): ?>
              <td><?= htmlspecialchars($t['prenom'].' '.$t['nom']) ?></td>
            <?php endif; ?>
            <td><?= $t['type'] === 'credit'
              ? '<span class="tag tag-green">Crédit</span>'
              : '<span class="tag tag-red">Paiement</span>' ?></td>
            <td style="font-family:var(--mono)">
              <?= $t['type'] === 'paiement' ? '-' : '+' ?><?= number_format($t['montant'],2,',',' ') ?> €
            </td>
            <?php if ($role !== 'user'): ?>
              <td style="color:var(--muted)"><?= $t['merchant_id'] ? htmlspecialchars($t['m_prenom'].' '.$t['m_nom']) : '—' ?></td>
            <?php endif; ?>
            <td style="color:var(--muted);font-size:.83rem"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
    <div style="display:flex;gap:.4rem;justify-content:center;margin-top:1.4rem">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?type=<?= $type ?>&page=<?= $i ?>" class="btn btn-sm <?= $i===$page ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<footer><?= APP_NAME ?></footer>
</body></html>
