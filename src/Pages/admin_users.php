<?php
// src/pages/admin_users.php
require_once __DIR__ . '/../../src/Includes/auth.php';
Auth::requireRole('admin');

$db  = DB::get();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Créer un compte
    if ($action === 'create') {
        $nom    = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $pass   = $_POST['password'] ?? '';
        $role   = in_array($_POST['role'], ['admin','user','merchant']) ? $_POST['role'] : 'user';

        if (!$nom || !$prenom || !$email || !$pass) {
            $msg = ['type' => 'err', 'text' => 'Tous les champs sont requis.'];
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 10]);
            try {
                $db->prepare('INSERT INTO users (nom, prenom, email, mot_de_passe, role) VALUES (?,?,?,?,?)')
                   ->execute([$nom, $prenom, $email, $hash, $role]);
                // Créer le compte associé
                $newId = $db->lastInsertId();
                $db->prepare('INSERT INTO comptes (user_id) VALUES (?)')->execute([$newId]);
                $msg = ['type' => 'ok', 'text' => "Compte $prenom $nom créé."];
            } catch (PDOException) {
                $msg = ['type' => 'err', 'text' => 'Email déjà utilisé.'];
            }
        }
    }

    // Activer / désactiver
    if ($action === 'toggle') {
        $db->prepare('UPDATE users SET actif = ? WHERE id = ?')
           ->execute([(int)$_POST['actif'], (int)$_POST['id']]);
        $msg = ['type' => 'ok', 'text' => 'Compte mis à jour.'];
    }

    // Créditer
    if ($action === 'credit') {
        require_once __DIR__ . '/../../src/includes/rfid.php';
        $montant = (float)str_replace(',', '.', $_POST['montant']);
        if ($montant > 0) {
            RFID::crediter((int)$_POST['id'], $montant);
            $msg = ['type' => 'ok', 'text' => "Compte crédité de $montant €."];
        }
    }
}

$users = $db->query('SELECT u.*, c.solde FROM users u LEFT JOIN comptes c ON c.user_id = u.id ORDER BY u.id')->fetchAll();

$title = 'Gestion des utilisateurs';
require '_header.php';
?>

<div class="wrap">
  <div class="ph">
    <div><h1>Utilisateurs</h1><p>Créer, modifier ou désactiver des comptes</p></div>
    <button class="btn btn-primary" onclick="document.getElementById('m-create').classList.add('open')">+ Nouveau</button>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msg['type'] === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($msg['text']) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>#</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Solde</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td style="color:var(--muted)"><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($u['email']) ?></td>
            <td><?php
			$rc = 'green'; $rl = 'User';
			if ($u['role'] === 'admin') { $rc = 'red'; $rl = 'Admin'; }
			elseif ($u['role'] === 'merchant') { $rc = 'blue'; $rl = 'Commerçant'; }
              echo "<span class='tag tag-$rc'>$rl</span>";
            ?></td>
            <td style="font-family:var(--mono)"><?= number_format((float)$u['solde'], 2, ',', ' ') ?> €</td>
            <td><?= $u['actif'] ? '<span class="tag tag-green">Actif</span>' : '<span class="tag tag-red">Inactif</span>' ?></td>
            <td>
              <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                <?php if ($u['role'] === 'user'): ?>
                <form method="POST" style="display:inline-flex;gap:.3rem">
                  <input type="hidden" name="action" value="credit">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <input type="number" name="montant" step="0.01" min="0.01" placeholder="€"
                         style="width:65px;padding:.28rem .45rem;font-size:.8rem">
                  <button class="btn btn-primary btn-sm">+€</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="margin:0">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="actif" value="<?= $u['actif'] ? 0 : 1 ?>">
                  <button class="btn btn-sm <?= $u['actif'] ? 'btn-danger' : 'btn-secondary' ?>">
                    <?= $u['actif'] ? 'Désactiver' : 'Réactiver' ?>
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

<!-- Modal création -->
<div id="m-create" class="modal-bg">
  <div class="modal">
    <div class="modal-head">
      <h2 style="font-size:1rem">Créer un compte</h2>
      <button class="modal-close" onclick="document.getElementById('m-create').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
        <div class="fg"><label>Prénom</label><input type="text" name="prenom" required></div>
        <div class="fg"><label>Nom</label><input type="text" name="nom" required></div>
      </div>
      <div class="fg"><label>Email</label><input type="email" name="email" required></div>
      <div class="fg"><label>Mot de passe</label><input type="password" name="password" required></div>
      <div class="fg">
        <label>Rôle</label>
        <select name="role">
          <option value="user">Utilisateur</option>
          <option value="merchant">Commerçant</option>
          <option value="admin">Administrateur</option>
        </select>
      </div>
      <button class="btn btn-primary btn-full">Créer</button>
    </form>
  </div>
</div>

<footer><?= APP_NAME ?></footer>
</body></html>
