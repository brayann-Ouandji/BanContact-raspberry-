<?php
// src/pages/admin_badges.php
require_once __DIR__ . '/../../src/Includes/auth.php';
//require_once '/config.php';
Auth::requireRole('admin');

$db  = DB::get();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $uid    = strtoupper(trim($_POST['uid'] ?? ''));
        $userId = (int)$_POST['user_id'];
        if (!$uid || !$userId) {
            $msg = ['type' => 'err', 'text' => 'UID et utilisateur requis.'];
        } else {
            try {
                $db->prepare('INSERT INTO badges (uid, user_id) VALUES (?,?)')->execute([$uid, $userId]);
                $msg = ['type' => 'ok', 'text' => "Badge $uid enregistré."];
            } catch (PDOException $e) {
                $msg = ['type' => 'err', 'text' => 'Cet UID existe déjà.'];
            }
        }
    }

    if ($action === 'toggle') {
        $db->prepare('UPDATE badges SET actif = ? WHERE id = ?')
           ->execute([(int)$_POST['actif'], (int)$_POST['id']]);
        $msg = ['type' => 'ok', 'text' => 'Badge mis à jour.'];
    }

    if ($action === 'reassign') {
        $db->prepare('UPDATE badges SET user_id = ? WHERE id = ?')
           ->execute([(int)$_POST['user_id'], (int)$_POST['id']]);
        $msg = ['type' => 'ok', 'text' => 'Badge réassigné.'];
    }
}

$badges = $db->query('SELECT b.*, u.prenom, u.nom FROM badges b JOIN users u ON u.id = b.user_id ORDER BY b.id')->fetchAll();
$users  = $db->query("SELECT id, prenom, nom FROM users WHERE role = 'user' AND actif = 1 ORDER BY nom")->fetchAll();

$title = 'Gestion des badges';
require '_header.php';
?>

<div class="wrap">
  <div class="ph">
    <div><h1>Badges RFID</h1><p>Associer un badge à un utilisateur</p></div>
    <button class="btn btn-primary" onclick="document.getElementById('m-add').classList.add('open')">+ Nouveau badge</button>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msg['type'] === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($msg['text']) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>UID</th><th>Utilisateur</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($badges as $b): ?>
          <tr>
            <td style="font-family:var(--mono);color:var(--accent)"><?= htmlspecialchars($b['uid']) ?></td>
            <td><?= htmlspecialchars($b['prenom'].' '.$b['nom']) ?></td>
            <td><?= $b['actif'] ? '<span class="tag tag-green">Actif</span>' : '<span class="tag tag-red">Inactif</span>' ?></td>
            <td>
              <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                <!-- Réassigner -->
                <form method="POST" style="display:inline-flex;gap:.3rem">
                  <input type="hidden" name="action" value="reassign">
                  <input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <select name="user_id" style="padding:.28rem .45rem;font-size:.8rem">
                    <?php foreach ($users as $u): ?>
                      <option value="<?= $u['id'] ?>" <?= $u['id'] == $b['user_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-secondary btn-sm">Assigner</button>
                </form>
                <!-- Toggle -->
                <form method="POST" style="margin:0">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <input type="hidden" name="actif" value="<?= $b['actif'] ? 0 : 1 ?>">
                  <button class="btn btn-sm <?= $b['actif'] ? 'btn-danger' : 'btn-secondary' ?>">
                    <?= $b['actif'] ? 'Désactiver' : 'Activer' ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal ajout badge -->
<div id="m-add" class="modal-bg">
  <div class="modal">
    <div class="modal-head">
      <h2 style="font-size:1rem">Enregistrer un badge</h2>
      <button class="modal-close" onclick="document.getElementById('m-add').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="fg"><label>UID du badge</label><input type="text" name="uid" placeholder="Ex: AABBCCDD" required></div>
      <div class="fg">
        <label>Utilisateur associé</label>
        <select name="user_id" required>
          <option value="">-- Choisir --</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary btn-full">Enregistrer</button>
    </form>
  </div>
</div>

<footer><?= APP_NAME ?></footer>
</body></html>
