<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'botaniste') {
    header('Location: index.php'); exit();
}
require_once 'db.php';
$conn = getConnection();
$uid  = $_SESSION['user_id'];
$msg  = '';

// Profil botaniste (jointure)
$r = $conn->prepare("SELECT u.*,b.specialite,b.institution FROM Utilisateur u LEFT JOIN Botaniste b ON b.id_utilisateur=u.id_utilisateur WHERE u.id_utilisateur=?");
$r->bind_param("i",$uid); $r->execute();
$profil = $r->get_result()->fetch_assoc();

// Ajouter plante
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_plante'])) {
    $nc=$_POST['nom_commun']; $ns=$_POST['nom_scientifique']; $fam=(int)$_POST['id_famille'];
    $desc=$_POST['description']; $tox=isset($_POST['toxicite'])?1:0; $soin=$_POST['niveau_soin'];
    $arr=$_POST['besoin_arrosage']; $lum=$_POST['besoin_luminosite']; $hum=$_POST['besoin_humidite'];
    $tmin=(float)$_POST['temperature_min']; $tmax=(float)$_POST['temperature_max'];
    $s=$conn->prepare("INSERT INTO Plante (nom_commun,nom_scientifique,id_famille,description,toxicite,niveau_soin,besoin_arrosage,besoin_luminosite,besoin_humidite,temperature_min,temperature_max,id_botaniste) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $s->bind_param("ssissssssddi",$nc,$ns,$fam,$desc,$tox,$soin,$arr,$lum,$hum,$tmin,$tmax,$uid);
    $s->execute(); $msg='✅ Plante ajoutée !';
}

// Modifier plante
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_plante'])) {
    $id_p=(int)$_POST['id_plante']; $nc=$_POST['nom_commun']; $ns=$_POST['nom_scientifique'];
    $desc=$_POST['description']; $tox=isset($_POST['toxicite'])?1:0; $soin=$_POST['niveau_soin'];
    $arr=$_POST['besoin_arrosage']; $lum=$_POST['besoin_luminosite']; $hum=$_POST['besoin_humidite'];
    $s=$conn->prepare("UPDATE Plante SET nom_commun=?,nom_scientifique=?,description=?,toxicite=?,niveau_soin=?,besoin_arrosage=?,besoin_luminosite=?,besoin_humidite=? WHERE id_plante=? AND id_botaniste=?");
    $s->bind_param("sssissssii",$nc,$ns,$desc,$tox,$soin,$arr,$lum,$hum,$id_p,$uid);
    $s->execute(); $msg='✅ Plante modifiée !';
}

// Supprimer plante
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['del_plante'])) {
    $id_p=(int)$_POST['id_plante'];
    $s=$conn->prepare("DELETE FROM Plante WHERE id_plante=? AND id_botaniste=?");
    $s->bind_param("ii",$id_p,$uid); $s->execute(); $msg='🗑️ Plante supprimée.';
}

// Ajouter pathologie
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_patho'])) {
    $id_p=(int)$_POST['id_plante_patho']; $nom=$_POST['nom_pathologie'];
    $sym=$_POST['symptomes']; $trt=$_POST['traitement'];
    $s=$conn->prepare("INSERT INTO Pathologie (id_plante,nom_pathologie,symptomes,traitement) VALUES (?,?,?,?)");
    $s->bind_param("isss",$id_p,$nom,$sym,$trt); $s->execute(); $msg='✅ Pathologie ajoutée !';
}

// Verifier si botaniste existe dans la table, sinon inserer
$chkB=$conn->prepare("SELECT id_utilisateur FROM Botaniste WHERE id_utilisateur=?");
$chkB->bind_param("i",$uid);$chkB->execute();
if($chkB->get_result()->num_rows===0){
    $insB=$conn->prepare("INSERT INTO Botaniste(id_utilisateur,specialite,institution) VALUES(?,?,?)");
    $sp=''; $in=''; $insB->bind_param("iss",$uid,$sp,$in); $insB->execute();
}
$res_fam=$conn->query("SELECT * FROM Famille");
$familles=($res_fam!==false)?$res_fam->fetch_all(MYSQLI_ASSOC):[];
$res_pl=$conn->query("SELECT p.*,f.nom_famille FROM Plante p LEFT JOIN Famille f ON f.id_famille=p.id_famille ORDER BY p.date_ajout DESC");
$plantes=($res_pl!==false)?$res_pl->fetch_all(MYSQLI_ASSOC):[];
$res_tp=$conn->query("SELECT id_plante,nom_commun FROM Plante ORDER BY nom_commun");
$tp_list=($res_tp!==false)?$res_tp->fetch_all(MYSQLI_ASSOC):[];
$res_pa=$conn->query("SELECT pa.*,pl.nom_commun FROM Pathologie pa JOIN Plante pl ON pl.id_plante=pa.id_plante ORDER BY pa.id_pathologie DESC");
$pathos=($res_pa!==false)?$res_pa->fetch_all(MYSQLI_ASSOC):[];
$res_st=$conn->query("SELECT COUNT(*) as total FROM Plante");
$stats=($res_st!==false)?$res_st->fetch_assoc():['total'=>0];
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>PlantApp — Botaniste</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
<nav class="navbar">
  <a href="botaniste.php" class="navbar-brand">
    <div class="logo-icon">🔬</div>
    <div><h1>PlantApp</h1><span>Espace Botaniste</span></div>
  </a>
  <ul class="navbar-nav">
    <li><a href="#ajouter" class="active">Ajouter</a></li>
    <li><a href="#mes-plantes">Mes Plantes</a></li>
    <li><a href="#pathologies">Pathologies</a></li>
    <li><a href="logout.php" class="btn-logout">Déconnexion</a></li>
  </ul>
</nav>
<div class="main-container">
  <div class="page-header">
    <h2>Dr. <?= htmlspecialchars($_SESSION['nom']) ?> 🔬</h2>
    <p><?= htmlspecialchars($profil['specialite']??'') ?> — <?= htmlspecialchars($profil['institution']??'') ?></p>
  </div>
  <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

  <div class="grid-3" style="margin-bottom:2rem;">
    <div class="stat-card"><div class="stat-icon">🌿</div><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Plantes enregistrées</div></div>
    <div class="stat-card"><div class="stat-icon">🦠</div><div class="stat-value"><?= count($pathos) ?></div><div class="stat-label">Pathologies documentées</div></div>
    <div class="stat-card"><div class="stat-icon">📚</div><div class="stat-value"><?= count($familles) ?></div><div class="stat-label">Familles botaniques</div></div>
  </div>

  <!-- AJOUTER PLANTE -->
  <section id="ajouter">
    <div class="card">
      <div class="card-header"><div class="card-icon">➕</div><h3 class="card-title">Ajouter une nouvelle plante</h3></div>
      <form method="POST">
        <div class="grid-2">
          <div class="form-group"><label>Nom commun *</label><input type="text" name="nom_commun" required placeholder="Ex: Pothos"></div>
          <div class="form-group"><label>Nom scientifique</label><input type="text" name="nom_scientifique" placeholder="Ex: Epipremnum aureum"></div>
          <div class="form-group"><label>Famille botanique</label>
            <select name="id_famille"><option value="">-- Choisir --</option>
              <?php foreach($familles as $f): ?><option value="<?=$f['id_famille']?>"><?= htmlspecialchars($f['nom_famille']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="form-group"><label>Niveau de soin</label>
            <select name="niveau_soin"><option value="facile">Facile</option><option value="moyen">Moyen</option><option value="difficile">Difficile</option></select></div>
          <div class="form-group"><label>Besoin arrosage</label>
            <select name="besoin_arrosage"><option value="quotidien">Quotidien</option><option value="hebdomadaire">Hebdomadaire</option><option value="bimensuel">Bimensuel</option></select></div>
          <div class="form-group"><label>Besoin luminosité</label>
            <select name="besoin_luminosite"><option value="faible">Faible</option><option value="moyenne">Moyenne</option><option value="forte">Forte</option></select></div>
          <div class="form-group"><label>Besoin humidité</label>
            <select name="besoin_humidite"><option value="faible">Faible</option><option value="moyenne">Moyenne</option><option value="elevee">Élevée</option></select></div>
          <div class="form-group"><label>Température min/max (°C)</label>
            <div style="display:flex;gap:1rem;">
              <input type="number" name="temperature_min" value="15" step="0.5" style="width:50%">
              <input type="number" name="temperature_max" value="30" step="0.5" style="width:50%">
            </div></div>
        </div>
        <div class="form-group"><label>Description</label><textarea name="description" rows="3" placeholder="Décrivez la plante..."></textarea></div>
        <div class="form-group checkbox-group"><label class="checkbox-label"><input type="checkbox" name="toxicite"><span>⚠️ Plante toxique</span></label></div>
        <button type="submit" name="add_plante" class="btn btn-primary">🌱 Ajouter la plante</button>
      </form>
    </div>
  </section>

  <!-- MES PLANTES -->
  <section id="mes-plantes" style="margin-top:2.5rem;">
    <div class="page-header"><h2>🌿 Mes Plantes</h2></div>
    <?php if(empty($plantes)): ?>
      <div class="empty-state"><div class="empty-icon">🌱</div><p>Aucune plante enregistrée.</p></div>
    <?php else: ?>
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Nom commun</th><th>Nom scientifique</th><th>Famille</th><th>Arrosage</th><th>Luminosité</th><th>Humidité</th><th>Toxique</th><th>Soin</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($plantes as $p): ?>
          <tr>
            <td><strong><?= htmlspecialchars($p['nom_commun']) ?></strong></td>
            <td><em><?= htmlspecialchars($p['nom_scientifique']??'-') ?></em></td>
            <td><?= htmlspecialchars($p['nom_famille']??'-') ?></td>
            <td><span class="tag tag-blue"><?= $p['besoin_arrosage'] ?></span></td>
            <td><span class="tag tag-yellow"><?= $p['besoin_luminosite'] ?></span></td>
            <td><span class="tag tag-blue"><?= $p['besoin_humidite'] ?></span></td>
            <td><?= $p['toxicite']?'<span class="tag tag-red">⚠️ Oui</span>':'<span class="tag tag-green">✅ Non</span>' ?></td>
            <td><span class="tag tag-<?= $p['niveau_soin']==='facile'?'green':($p['niveau_soin']==='moyen'?'yellow':'red') ?>"><?= ucfirst($p['niveau_soin']) ?></span></td>
            <td>
              <button class="btn btn-sm btn-outline" onclick="openEditModal(<?= htmlspecialchars(json_encode($p)) ?>)">✏️</button>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
                <input type="hidden" name="id_plante" value="<?= $p['id_plante'] ?>">
                <button type="submit" name="del_plante" class="btn btn-sm btn-danger">🗑️</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <!-- PATHOLOGIES -->
  <section id="pathologies" style="margin-top:2.5rem;">
    <div class="page-header"><h2>🦠 Pathologies & Traitements</h2></div>
    <div class="grid-2">
      <div class="card">
        <div class="card-header"><div class="card-icon">➕</div><h3 class="card-title">Ajouter une pathologie</h3></div>
        <form method="POST">
          <div class="form-group"><label>Plante concernée</label>
            <select name="id_plante_patho" required><option value="">-- Choisir --</option>
              <?php foreach($tp_list as $tp): ?><option value="<?=$tp['id_plante']?>"><?= htmlspecialchars($tp['nom_commun']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="form-group"><label>Nom pathologie</label><input type="text" name="nom_pathologie" required placeholder="Ex: Pourriture des racines"></div>
          <div class="form-group"><label>Symptômes</label><textarea name="symptomes" rows="2"></textarea></div>
          <div class="form-group"><label>Traitement</label><textarea name="traitement" rows="2"></textarea></div>
          <button type="submit" name="add_patho" class="btn btn-primary">🦠 Ajouter</button>
        </form>
      </div>
      <div class="card">
        <div class="card-header"><div class="card-icon">📋</div><h3 class="card-title">Pathologies enregistrées</h3></div>
        <?php if(empty($pathos)): ?>
          <div class="empty-state"><p>Aucune pathologie.</p></div>
        <?php else: ?>
        <div class="patho-list">
          <?php foreach($pathos as $pa): ?>
          <div class="patho-item">
            <div class="patho-header"><strong>🦠 <?= htmlspecialchars($pa['nom_pathologie']) ?></strong><span class="tag tag-green"><?= htmlspecialchars($pa['nom_commun']) ?></span></div>
            <?php if($pa['symptomes']): ?><p><em>Symptômes :</em> <?= htmlspecialchars($pa['symptomes']) ?></p><?php endif; ?>
            <?php if($pa['traitement']): ?><p><em>Traitement :</em> <?= htmlspecialchars($pa['traitement']) ?></p><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<!-- MODAL MODIFIER -->
<div id="editModal" class="modal-overlay" style="display:none;">
  <div class="modal-box">
    <div class="modal-header"><h3>✏️ Modifier la plante</h3><button onclick="closeEditModal()" class="modal-close">✕</button></div>
    <form method="POST">
      <input type="hidden" name="id_plante" id="edit_id">
      <div class="grid-2">
        <div class="form-group"><label>Nom commun</label><input type="text" name="nom_commun" id="edit_nom_commun" required></div>
        <div class="form-group"><label>Nom scientifique</label><input type="text" name="nom_scientifique" id="edit_nom_sci"></div>
        <div class="form-group"><label>Niveau soin</label><select name="niveau_soin" id="edit_soin"><option value="facile">Facile</option><option value="moyen">Moyen</option><option value="difficile">Difficile</option></select></div>
        <div class="form-group"><label>Arrosage</label><select name="besoin_arrosage" id="edit_arr"><option value="quotidien">Quotidien</option><option value="hebdomadaire">Hebdomadaire</option><option value="bimensuel">Bimensuel</option></select></div>
        <div class="form-group"><label>Luminosité</label><select name="besoin_luminosite" id="edit_lum"><option value="faible">Faible</option><option value="moyenne">Moyenne</option><option value="forte">Forte</option></select></div>
        <div class="form-group"><label>Humidité</label><select name="besoin_humidite" id="edit_hum"><option value="faible">Faible</option><option value="moyenne">Moyenne</option><option value="elevee">Élevée</option></select></div>
      </div>
      <div class="form-group"><label>Description</label><textarea name="description" id="edit_desc" rows="3"></textarea></div>
      <div class="form-group checkbox-group"><label class="checkbox-label"><input type="checkbox" name="toxicite" id="edit_tox"><span>⚠️ Toxique</span></label></div>
      <div style="display:flex;gap:1rem;">
        <button type="submit" name="edit_plante" class="btn btn-primary">💾 Enregistrer</button>
        <button type="button" onclick="closeEditModal()" class="btn btn-outline">Annuler</button>
      </div>
    </form>
  </div>
</div>
<script src="script.js"></script>
</body>
</html>
