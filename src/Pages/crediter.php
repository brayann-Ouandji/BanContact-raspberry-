<?php
// src/pages/crediter.php
require_once __DIR__ . '/../../src/Includes/auth.php';
require_once __DIR__ . '/../../src/Includes/rfid.php';
Auth::requireRole('user');

$msg = null;
$s   = DB::get()->prepare('SELECT solde FROM comptes WHERE user_id = ?');
$s->execute([Auth::id()]);
$solde = (float)$s->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $montant = (float)str_replace(',', '.', $_POST['montant'] ?? 0);
    if ($montant < 1 || $montant > 500) {
        $msg = ['type' => 'err', 'text' => 'Montant invalide (1 € – 500 €).'];
    } else {
        $r = RFID::crediter(Auth::id(), $montant);
        if ($r['ok']) {
            $solde = $r['solde'];
            $msg   = ['type' => 'ok', 'text' => sprintf('%.2f € crédités. Nouveau solde : %.2f €', $montant, $solde)];
        } else {
            $msg = ['type' => 'err', 'text' => $r['error']];
        }
    }
}

$title = 'Créditer mon compte';
require '_header.php';
?>

<div class="wrap" style="max-width:500px">
  <div class="ph">
    <div>
      <h1>Créditer mon compte</h1>
      <p>Solde actuel : <strong style="color:var(--accent);font-family:var(--mono)"><?= number_format($solde,2,',',' ') ?> €</strong></p>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msg['type'] === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($msg['text']) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST">
      <div class="fg">
        <label>Montant à ajouter (€)</label>
        <input type="number" name="montant" step="0.01" min="1" max="500" placeholder="Ex : 20.00" required>
      </div>
      <div style="display:flex;gap:.5rem;margin-bottom:1.4rem;flex-wrap:wrap">
        <?php foreach ([5,10,20,50] as $v): ?>
          <button type="button" class="btn btn-secondary btn-sm"
            onclick="document.querySelector('[name=montant]').value='<?= $v ?>'">
            <?= $v ?> €
          </button>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-primary btn-full">✓ Confirmer le crédit</button>
    </form>
  </div>
</div>

<footer><?= APP_NAME ?></footer>
</body></html>
