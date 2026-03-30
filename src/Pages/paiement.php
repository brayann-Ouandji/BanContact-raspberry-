<?php
// src/pages/paiement.php
require_once __DIR__ . '/../../src/includes/auth.php';
require_once __DIR__ . '/../../src/includes/rfid.php';
Auth::requireRole('admin', 'merchant');

$msg = null;

// ── AJAX : lecture badge ──
if (isset($_GET['action']) && $_GET['action'] === 'scan') {
    header('Content-Type: application/json');
    $uid  = RFID::readUID();
    $data = ['uid' => null, 'nom' => null, 'solde' => null, 'id' => null, 'error' => null];
    if (!$uid) {
        $data['error'] = 'Aucun badge détecté.';
    } else {
        $user = RFID::findUser($uid);
        if (!$user) {
            $data['error'] = "Badge $uid inconnu ou compte désactivé.";
        } else {
            $data['uid']   = $uid;
            $data['id']    = $user['id'];
            $data['nom']   = $user['prenom'] . ' ' . $user['nom'];
            $data['solde'] = number_format($user['solde'], 2, ',', ' ') . ' €';
        }
    }
    echo json_encode($data); exit;
}

// ── POST : valider le paiement ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId  = (int)($_POST['user_id'] ?? 0);
    $montant = (float)str_replace(',', '.', $_POST['montant'] ?? 0);
    if ($userId <= 0 || $montant <= 0) {
        $msg = ['type' => 'err', 'text' => 'Données invalides.'];
    } else {
        $r = RFID::payer($userId, $montant, Auth::id());
        $msg = $r['ok']
            ? ['type' => 'ok',  'text' => sprintf('Paiement de %.2f € validé. Nouveau solde : %.2f €', $montant, $r['solde'])]
            : ['type' => 'err', 'text' => $r['error']];
    }
}

$title = 'Terminal de paiement';
require '_header.php';
?>

<div class="wrap">
  <div class="ph">
    <div><h1>Terminal de paiement</h1><p>Approchez le badge du lecteur pour débiter le client</p></div>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msg['type'] === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($msg['text']) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.4rem">

    <!-- Scan -->
    <div class="card">
      <h2 style="font-size:.95rem;margin-bottom:1.1rem">1. Scanner le badge</h2>
      <div class="terminal" id="term">En attente du badge<span class="blink">_</span></div>
      <div style="display:flex;gap:.6rem;margin-top:1rem">
        <button class="btn btn-primary" onclick="scan()">⟳ Lire le badge</button>
        <button class="btn btn-secondary btn-sm" onclick="demo()">Simuler (démo)</button>
      </div>
      <div id="cinfo" style="display:none;margin-top:1.2rem">
        <div class="stat">
          <div class="lbl">Client identifié</div>
          <div class="val" id="cnom" style="font-size:1.2rem"></div>
          <div style="font-family:var(--mono);color:var(--accent);margin-top:.3rem" id="csolde"></div>
        </div>
      </div>
    </div>

    <!-- Montant -->
    <div class="card">
      <h2 style="font-size:.95rem;margin-bottom:1.1rem">2. Saisir le montant</h2>
      <form method="POST">
        <input type="hidden" name="user_id" id="uid_input">
        <div class="fg">
          <label>Montant (€)</label>
          <input type="number" name="montant" step="0.01" min="0.01" placeholder="0.00" required>
        </div>
        <button class="btn btn-primary btn-full" id="btn-pay" disabled type="submit">✓ Valider le paiement</button>
      </form>
    </div>

  </div>
</div>

<footer><?= APP_NAME ?></footer>

<script>
async function scan() {
  document.getElementById('term').innerHTML = 'Lecture en cours<span class="blink">_</span>';
  const r = await fetch('?action=scan').then(r => r.json()).catch(() => ({error: 'Erreur réseau'}));
  handle(r);
}
function demo() {
  handle({ uid: 'AABBCCDD', id: 2, nom: 'Claire Durand', solde: '50,00 €', error: null });
}
function handle(d) {
  const term = document.getElementById('term');
  if (d.error) { term.textContent = '✗ ' + d.error; return; }
  term.innerHTML = '✔ Badge : <strong>' + d.uid + '</strong>';
  document.getElementById('cnom').textContent  = d.nom;
  document.getElementById('csolde').textContent = 'Solde : ' + d.solde;
  document.getElementById('uid_input').value   = d.id;
  document.getElementById('cinfo').style.display = 'block';
  document.getElementById('btn-pay').disabled  = false;
}
</script>
</body></html>
