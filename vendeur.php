<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendeur') {
    header('Location: index.php'); exit();
}
require_once 'db.php';
$conn = getConnection();
$uid  = (int)$_SESSION['user_id'];
$msg  = '';

$chkV = $conn->prepare("SELECT id_utilisateur FROM Vendeur WHERE id_utilisateur=?");
$chkV->bind_param("i",$uid); $chkV->execute();
if ($chkV->get_result()->num_rows === 0) {
    $insV = $conn->prepare("INSERT INTO Vendeur(id_utilisateur,nom_boutique,adresse_boutique,telephone,solde) VALUES(?,?,?,?,?)");
    $nb='Ma Boutique'; $na=''; $nt=''; $s0=0.00;
    $insV->bind_param("isssd",$uid,$nb,$na,$nt,$s0); $insV->execute();
}

$r = $conn->prepare("SELECT u.*,v.nom_boutique,v.adresse_boutique,v.telephone,v.solde FROM Utilisateur u LEFT JOIN Vendeur v ON v.id_utilisateur=u.id_utilisateur WHERE u.id_utilisateur=?");
$r->bind_param("i",$uid); $r->execute();
$profil = $r->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_profil'])) {
    $nb=trim($_POST['nom_boutique']); $na=trim($_POST['adresse_boutique']); $nt=trim($_POST['telephone']);
    $s=$conn->prepare("UPDATE Vendeur SET nom_boutique=?,adresse_boutique=?,telephone=? WHERE id_utilisateur=?");
    $s->bind_param("sssi",$nb,$na,$nt,$uid); $s->execute(); $msg='Profil boutique mis a jour.';
    $r->execute(); $profil=$r->get_result()->fetch_assoc();
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_catalogue'])) {
    $id_p=(int)$_POST['id_plante']; $prix=(float)$_POST['prix']; $stock=(int)$_POST['stock']; $var=trim($_POST['variete']);
    $s=$conn->prepare("INSERT INTO Catalogue(id_vendeur,id_plante,prix,stock,variete) VALUES(?,?,?,?,?)");
    $s->bind_param("iidis",$uid,$id_p,$prix,$stock,$var); $s->execute(); $msg='Plante ajoutee au catalogue.';
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_catalogue'])) {
    $id_c=(int)$_POST['id_catalogue']; $prix=(float)$_POST['prix']; $stock=(int)$_POST['stock']; $dispo=isset($_POST['disponible'])?1:0;
    $s=$conn->prepare("UPDATE Catalogue SET prix=?,stock=?,disponible=? WHERE id_catalogue=? AND id_vendeur=?");
    $s->bind_param("diiii",$prix,$stock,$dispo,$id_c,$uid); $s->execute(); $msg='Catalogue mis a jour.';
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['del_catalogue'])) {
    $id_c=(int)$_POST['id_catalogue'];
    $s=$conn->prepare("DELETE FROM Catalogue WHERE id_catalogue=? AND id_vendeur=?");
    $s->bind_param("ii",$id_c,$uid); $s->execute(); $msg='Article supprime.';
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_accessoire'])) {
    $nom=trim($_POST['nom_accessoire']); $desc=trim($_POST['description']); $prix=(float)$_POST['prix']; $stk=(int)$_POST['stock']; $img=trim($_POST['image_url']??'');
    $s=$conn->prepare("INSERT INTO Accessoire(id_vendeur,nom_accessoire,description,prix,stock,image_url) VALUES(?,?,?,?,?,?)");
    $s->bind_param("issdis",$uid,$nom,$desc,$prix,$stk,$img); $s->execute(); $msg='Accessoire ajoute.';
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['del_accessoire'])) {
    $id_a=(int)$_POST['id_accessoire'];
    $s=$conn->prepare("DELETE FROM Accessoire WHERE id_accessoire=? AND id_vendeur=?");
    $s->bind_param("ii",$id_a,$uid); $s->execute(); $msg='Accessoire supprime.';
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_statut'])) {
    $id_cmd=(int)$_POST['id_commande']; $new_statut=$_POST['nouveau_statut'];
    if(in_array($new_statut,['en_attente','confirme','livre'])){
        $s=$conn->prepare("UPDATE Commande SET statut=? WHERE id_commande=? AND id_vendeur=?");
        $s->bind_param("sii",$new_statut,$id_cmd,$uid); $s->execute(); $msg='Statut mis a jour.';
    }
}

$res_cat=$conn->query("SELECT c.*,p.nom_commun,p.nom_scientifique FROM Catalogue c JOIN Plante p ON p.id_plante=c.id_plante WHERE c.id_vendeur=$uid ORDER BY c.id_catalogue DESC");
$catalogue=($res_cat!==false)?$res_cat->fetch_all(MYSQLI_ASSOC):[];
$res_all=$conn->query("SELECT id_plante,nom_commun FROM Plante ORDER BY nom_commun");
$all_plantes=($res_all!==false)?$res_all->fetch_all(MYSQLI_ASSOC):[];
$res_acc=$conn->query("SELECT * FROM Accessoire WHERE id_vendeur=$uid ORDER BY id_accessoire DESC");
$accessoires=($res_acc!==false)?$res_acc->fetch_all(MYSQLI_ASSOC):[];
$res_stats=$conn->query("SELECT COALESCE(SUM(stock),0) as total_stock,COUNT(*) as nb FROM Catalogue WHERE id_vendeur=$uid");
$stats=($res_stats!==false)?$res_stats->fetch_assoc():['nb'=>0,'total_stock'=>0];

// Commandes plantes
$res_cmd=$conn->query("SELECT c.*,u.nom,u.prenom,p.nom_commun FROM Commande c JOIN Utilisateur u ON u.id_utilisateur=c.id_utilisateur LEFT JOIN Plante p ON p.id_plante=c.id_plante WHERE c.id_vendeur=$uid AND c.type_commande='plante' ORDER BY c.date_commande DESC");
$commandes_plantes=($res_cmd!==false)?$res_cmd->fetch_all(MYSQLI_ASSOC):[];

// Commandes accessoires
$res_cmd_acc=$conn->query("SELECT c.*,u.nom,u.prenom,a.nom_accessoire FROM Commande c JOIN Utilisateur u ON u.id_utilisateur=c.id_utilisateur LEFT JOIN Accessoire a ON a.id_accessoire=c.id_accessoire WHERE c.id_vendeur=$uid AND c.type_commande='accessoire' ORDER BY c.date_commande DESC");
$commandes_acc=($res_cmd_acc!==false)?$res_cmd_acc->fetch_all(MYSQLI_ASSOC):[];



$conn->close();
$leaf='<svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z"/></svg>';
function statut_badge($s){$cl=$s==='confirme'?'tag-green':($s==='livre'?'tag-blue':'tag-yellow');$lb=$s==='en_attente'?'En attente':($s==='confirme'?'Confirme':'Livre');return '<span class="tag '.$cl.'">'.$lb.'</span>';}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>PlantApp — Vendeur</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <a href="vendeur.php" class="navbar-brand">
    <div class="logo-icon"><?=$leaf?></div>
    <div><h1>PlantApp</h1><span>Espace Vendeur</span></div>
  </a>
  <ul class="navbar-nav">
    <li><a href="#profil">Ma Boutique</a></li>
    <li><a href="#catalogue">Catalogue</a></li>
    <li><a href="#accessoires">Accessoires</a></li>
    <li><a href="#commandes">Commandes</a></li>
    <li><a href="logout.php" class="btn-logout">Deconnexion</a></li>
  </ul>
</nav>
<div class="main-container">
  <div class="page-header">
    <h2><?=htmlspecialchars($profil['nom_boutique']??$_SESSION['nom'])?></h2>
    <p><?=htmlspecialchars($profil['adresse_boutique']??'')?><?=$profil['telephone']?' — '.htmlspecialchars($profil['telephone']):''?></p>
  </div>
  <?php if($msg):?><div class="alert alert-success"><?=htmlspecialchars($msg)?></div><?php endif;?>

  <!-- STATS -->
  <div class="grid-4" style="margin-bottom:2rem;">
    <div class="stat-card"><div class="stat-value"><?=$stats['nb']?></div><div class="stat-label">Plantes en catalogue</div></div>
    <div class="stat-card"><div class="stat-value"><?=$stats['total_stock']?></div><div class="stat-label">Unites en stock</div></div>
    <div class="stat-card"><div class="stat-value"><?=count($commandes_plantes)+count($commandes_acc)?></div><div class="stat-label">Commandes recues</div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-color:#86efac;">
      <div class="stat-value" style="color:#166534;"><?=number_format($profil['solde']??0,2)?> DT</div>
      <div class="stat-label">Mon solde</div>
    </div>
  </div>

  <!-- PROFIL -->
  <section id="profil" style="margin-bottom:2.5rem;">
    <div class="card">
      <div class="card-header"><div class="card-icon"><?=$leaf?></div><h3 class="card-title">Informations de ma boutique</h3></div>
      <form method="POST">
        <div class="grid-3">
          <div class="form-group"><label>Nom boutique</label><input type="text" name="nom_boutique" value="<?=htmlspecialchars($profil['nom_boutique']??'')?>" placeholder="Plantes et Co"></div>
          <div class="form-group"><label>Adresse</label><input type="text" name="adresse_boutique" value="<?=htmlspecialchars($profil['adresse_boutique']??'')?>" placeholder="Avenue Habib Bourguiba"></div>
          <div class="form-group"><label>Telephone</label><input type="text" name="telephone" value="<?=htmlspecialchars($profil['telephone']??'')?>" placeholder="+216 71 000 000"></div>
        </div>
        <button type="submit" name="save_profil" class="btn btn-primary">Enregistrer</button>
      </form>
    </div>
  </section>

  <!-- CATALOGUE -->
  <section id="catalogue">
    <div class="page-header"><h2>Mon Catalogue</h2></div>
    <?php if(empty($catalogue)):?><div class="empty-state"><p>Catalogue vide. Ajoutez des plantes ci-dessous.</p></div>
    <?php else:?>
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Plante</th><th>Variete</th><th>Prix (DT)</th><th>Stock</th><th>Disponible</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($catalogue as $c):?>
          <tr>
            <td><strong><?=htmlspecialchars($c['nom_commun'])?></strong><br><em style="font-size:.78rem;color:#9ca3af"><?=htmlspecialchars($c['nom_scientifique']??'')?></em></td>
            <td><?=htmlspecialchars($c['variete']??'-')?></td>
            <td><span class="price-tag"><?=number_format($c['prix'],2)?></span></td>
            <td><span class="tag <?=$c['stock']>10?'tag-green':($c['stock']>0?'tag-yellow':'tag-red')?>"><?=$c['stock']?> u.</span></td>
            <td><?=$c['disponible']?'<span class="tag tag-green">Oui</span>':'<span class="tag tag-red">Non</span>'?></td>
            <td style="white-space:nowrap;">
              <button class="btn btn-sm btn-outline" onclick="openCatalogModal(<?=htmlspecialchars(json_encode($c),ENT_QUOTES)?>)">Modifier</button>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
                <input type="hidden" name="id_catalogue" value="<?=$c['id_catalogue']?>">
                <button type="submit" name="del_catalogue" class="btn btn-sm btn-danger">Supprimer</button>
              </form>
            </td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>
    <?php endif;?>
    <div class="card" style="margin-top:1.5rem;">
      <div class="card-header"><div class="card-icon"><?=$leaf?></div><h3 class="card-title">Ajouter une plante</h3></div>
      <form method="POST">
        <div class="grid-3">
          <div class="form-group"><label>Plante *</label>
            <select name="id_plante" required><option value="">-- Choisir --</option>
              <?php foreach($all_plantes as $p):?><option value="<?=$p['id_plante']?>"><?=htmlspecialchars($p['nom_commun'])?></option><?php endforeach;?>
            </select>
          </div>
          <div class="form-group"><label>Prix (DT) *</label><input type="number" name="prix" step="0.01" min="0" required placeholder="15.00"></div>
          <div class="form-group"><label>Stock *</label><input type="number" name="stock" min="0" required placeholder="Quantite"></div>
          <div class="form-group"><label>Variete</label><input type="text" name="variete" placeholder="Ex: Golden Pothos"></div>
        </div>
        <button type="submit" name="add_catalogue" class="btn btn-primary">Ajouter au catalogue</button>
      </form>
    </div>
  </section>

  <!-- ACCESSOIRES -->
  <section id="accessoires" style="margin-top:2.5rem;">
    <div class="page-header"><h2>Accessoires</h2></div>
    <div class="grid-2">
      <div class="card">
        <div class="card-header"><div class="card-icon"><?=$leaf?></div><h3 class="card-title">Ajouter un accessoire</h3></div>
        <form method="POST">
          <div class="form-group"><label>Nom *</label><input type="text" name="nom_accessoire" required placeholder="Ex: Pot en terre cuite"></div>
          <div class="form-group"><label>Description</label><textarea name="description" rows="2" style="width:100%;padding:.72rem;border:1.5px solid #e5e7eb;border-radius:8px;font-family:inherit;font-size:.9rem;background:#fafafa;outline:none;resize:vertical;"></textarea></div>
          <div class="grid-2">
            <div class="form-group"><label>Prix (DT)</label><input type="number" name="prix" step="0.01" placeholder="0.00"></div>
            <div class="form-group"><label>Stock</label><input type="number" name="stock" placeholder="0"></div>
          </div>
          <div class="form-group"><label>Image (nom du fichier)</label><input type="text" name="image_url" placeholder="images/pot_terre_cuite.jpg"></div>
          <button type="submit" name="add_accessoire" class="btn btn-primary">Ajouter</button>
        </form>
      </div>
      <div class="card">
        <div class="card-header"><div class="card-icon"><?=$leaf?></div><h3 class="card-title">Mes accessoires</h3></div>
        <?php if(empty($accessoires)):?><div class="empty-state"><p>Aucun accessoire.</p></div>
        <?php else:?><div class="acc-list">
          <?php foreach($accessoires as $a):?>
          <div class="acc-item">
            <?php if(!empty($a['image_url'])): ?>
              <img src="<?=htmlspecialchars($a['image_url'])?>" alt="<?=htmlspecialchars($a['nom_accessoire'])?>"
                style="width:52px;height:52px;border-radius:8px;object-fit:cover;flex-shrink:0;"
                onerror="this.style.display='none'">
            <?php endif; ?>
            <div><strong style="font-size:.9rem;color:#1a3a2a"><?=htmlspecialchars($a['nom_accessoire'])?></strong><p style="font-size:.8rem;color:#6b7280"><?=htmlspecialchars($a['description']??'')?></p></div>
            <div style="text-align:right;flex-shrink:0;margin-left:.8rem;">
              <div class="price-tag"><?=number_format($a['prix'],2)?> DT</div>
              <span class="tag tag-blue" style="margin-top:.3rem;display:inline-block"><?=$a['stock']?> u.</span>
              <form method="POST" style="margin-top:.4rem;" onsubmit="return confirm('Supprimer ?')">
                <input type="hidden" name="id_accessoire" value="<?=$a['id_accessoire']?>">
                <button type="submit" name="del_accessoire" class="btn btn-danger btn-sm">Supprimer</button>
              </form>
            </div>
          </div>
          <?php endforeach;?></div>
        <?php endif;?>
      </div>
    </div>
  </section>

  <!-- COMMANDES -->
  <section id="commandes" style="margin-top:2.5rem;">
    <div class="page-header"><h2>Commandes Recues</h2></div>

    <!-- Commandes plantes -->
    <h3 style="font-size:1rem;font-weight:600;color:#1a3a2a;margin-bottom:.8rem;">Plantes</h3>
    <?php if(empty($commandes_plantes)):?>
      <div class="empty-state" style="margin-bottom:1.5rem;"><p>Aucune commande de plantes.</p></div>
    <?php else:?>
    <div class="table-wrapper" style="margin-bottom:2rem;">
      <table class="data-table">
        <thead><tr><th>N</th><th>Client</th><th>Plante</th><th>Qte</th><th>Total</th><th>Adresse</th><th>Ville</th><th>Statut</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach($commandes_plantes as $cmd):?>
          <tr>
            <td><strong>#<?=$cmd['id_commande']?></strong></td>
            <td><?=htmlspecialchars($cmd['prenom'].' '.$cmd['nom'])?></td>
            <td><strong><?=htmlspecialchars($cmd['nom_commun'])?></strong></td>
            <td><?=$cmd['quantite']?> u.</td>
            <td><span class="price-tag"><?=number_format($cmd['prix_total'],2)?> DT</span></td>
            <td style="font-size:.8rem;"><?=htmlspecialchars($cmd['adresse_livraison']??'-')?></td>
            <td><?=htmlspecialchars($cmd['ville']??'-')?></td>
            <td><?=statut_badge($cmd['statut'])?></td>
            <td style="font-size:.78rem;"><?=date('d/m/Y H:i',strtotime($cmd['date_commande']))?></td>
            <td>
              <form method="POST">
                <input type="hidden" name="id_commande" value="<?=$cmd['id_commande']?>">
                <select name="nouveau_statut" style="padding:.3rem;border:1px solid #e5e7eb;border-radius:6px;font-size:.78rem;font-family:inherit;">
                  <option value="en_attente" <?=$cmd['statut']==='en_attente'?'selected':''?>>En attente</option>
                  <option value="confirme"   <?=$cmd['statut']==='confirme'?'selected':''?>>Confirme</option>
                  <option value="livre"      <?=$cmd['statut']==='livre'?'selected':''?>>Livre</option>
                </select>
                <button type="submit" name="update_statut" class="btn btn-primary btn-sm" style="margin-top:.3rem;">Maj</button>
              </form>
            </td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>
    <?php endif;?>

    <!-- Commandes accessoires -->
    <h3 style="font-size:1rem;font-weight:600;color:#1a3a2a;margin-bottom:.8rem;">Accessoires</h3>
    <?php if(empty($commandes_acc)):?>
      <div class="empty-state"><p>Aucune commande d accessoires.</p></div>
    <?php else:?>
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>N</th><th>Client</th><th>Accessoire</th><th>Qte</th><th>Total</th><th>Adresse</th><th>Ville</th><th>Statut</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach($commandes_acc as $cmd):?>
          <tr>
            <td><strong>#<?=$cmd['id_commande']?></strong></td>
            <td><?=htmlspecialchars($cmd['prenom'].' '.$cmd['nom'])?></td>
            <td><strong><?=htmlspecialchars($cmd['nom_accessoire'])?></strong></td>
            <td><?=$cmd['quantite']?> u.</td>
            <td><span class="price-tag"><?=number_format($cmd['prix_total'],2)?> DT</span></td>
            <td style="font-size:.8rem;"><?=htmlspecialchars($cmd['adresse_livraison']??'-')?></td>
            <td><?=htmlspecialchars($cmd['ville']??'-')?></td>
            <td><?=statut_badge($cmd['statut'])?></td>
            <td style="font-size:.78rem;"><?=date('d/m/Y H:i',strtotime($cmd['date_commande']))?></td>
            <td>
              <form method="POST">
                <input type="hidden" name="id_commande" value="<?=$cmd['id_commande']?>">
                <select name="nouveau_statut" style="padding:.3rem;border:1px solid #e5e7eb;border-radius:6px;font-size:.78rem;font-family:inherit;">
                  <option value="en_attente" <?=$cmd['statut']==='en_attente'?'selected':''?>>En attente</option>
                  <option value="confirme"   <?=$cmd['statut']==='confirme'?'selected':''?>>Confirme</option>
                  <option value="livre"      <?=$cmd['statut']==='livre'?'selected':''?>>Livre</option>
                </select>
                <button type="submit" name="update_statut" class="btn btn-primary btn-sm" style="margin-top:.3rem;">Maj</button>
              </form>
            </td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>
    <?php endif;?>
  </section>

</div>

<div id="catalogModal" class="modal-overlay" style="display:none;">
  <div class="modal-box">
    <div class="modal-header"><h3>Modifier l article</h3><button onclick="closeCatalogModal()" class="modal-close">x</button></div>
    <form method="POST">
      <input type="hidden" name="id_catalogue" id="cat_id">
      <div class="form-group"><label>Plante</label><input type="text" id="cat_nom" disabled style="background:#f0faf4;"></div>
      <div class="grid-2">
        <div class="form-group"><label>Prix (DT)</label><input type="number" name="prix" id="cat_prix" step="0.01" min="0" required></div>
        <div class="form-group"><label>Stock</label><input type="number" name="stock" id="cat_stock" min="0" required></div>
      </div>
      <div class="form-group"><label class="checkbox-label"><input type="checkbox" name="disponible" id="cat_dispo"> Disponible a la vente</label></div>
      <div style="display:flex;gap:1rem;">
        <button type="submit" name="update_catalogue" class="btn btn-primary">Enregistrer</button>
        <button type="button" onclick="closeCatalogModal()" class="btn btn-outline">Annuler</button>
      </div>
    </form>
  </div>
</div>
<script src="script.js"></script>
</body>
</html>
