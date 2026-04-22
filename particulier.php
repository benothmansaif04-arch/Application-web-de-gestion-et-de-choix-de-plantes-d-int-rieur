<?php
session_start();
if (!isset($_SESSION['user_id'])||$_SESSION['role']!=='particulier'){header('Location: index.php');exit();}
require_once 'db.php';
$conn=getConnection(); $uid=(int)$_SESSION['user_id']; $msg='';

$r=$conn->prepare("SELECT u.*,p.localisation,p.niveau_experience,p.disponibilite_arrosage FROM Utilisateur u LEFT JOIN Particulier p ON p.id_utilisateur=u.id_utilisateur WHERE u.id_utilisateur=?");
$r->bind_param("i",$uid);$r->execute();$profil=$r->get_result()->fetch_assoc();

// Verifier particulier
$chkP=$conn->prepare("SELECT id_utilisateur FROM Particulier WHERE id_utilisateur=?");
$chkP->bind_param("i",$uid);$chkP->execute();
if($chkP->get_result()->num_rows===0){
    $insP=$conn->prepare("INSERT INTO Particulier(id_utilisateur) VALUES(?)");
    $insP->bind_param("i",$uid);$insP->execute();
}

// Sauvegarder profil
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_profil'])){
    $loc=$_POST['localisation'];$niv=$_POST['niveau_experience'];$arr=$_POST['disponibilite_arrosage'];
    $s=$conn->prepare("UPDATE Particulier SET localisation=?,niveau_experience=?,disponibilite_arrosage=? WHERE id_utilisateur=?");
    $s->bind_param("sssi",$loc,$niv,$arr,$uid);$s->execute();
    $msg='Profil mis a jour.';$r->execute();$profil=$r->get_result()->fetch_assoc();
}

// Ajouter espace
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['add_espace'])){
    $nom=$_POST['nom_piece'];$type=$_POST['type_piece'];$lum=$_POST['esp_luminosite'];
    $tail=$_POST['esp_taille'];$hum=$_POST['esp_humidite'];$ani=isset($_POST['esp_animaux'])?1:0;
    $temp=(float)$_POST['temperature_approx'];$desc=$_POST['esp_description'];
    $s=$conn->prepare("INSERT INTO EspacePiece(id_utilisateur,nom_piece,type_piece,luminosite,taille,humidite,presence_animaux,temperature_approx,description) VALUES(?,?,?,?,?,?,?,?,?)");
    $s->bind_param("isssssisd",$uid,$nom,$type,$lum,$tail,$hum,$ani,$temp,$desc);$s->execute();$msg='Espace ajoute.';
}

// Modifier espace
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['edit_espace'])){
    $ie=(int)$_POST['id_espace'];
    $nom=$_POST['nom_piece'];$type=$_POST['type_piece'];$lum=$_POST['esp_luminosite'];
    $tail=$_POST['esp_taille'];$hum=$_POST['esp_humidite'];$ani=isset($_POST['esp_animaux'])?1:0;
    $temp=(float)$_POST['temperature_approx'];$desc=$_POST['esp_description'];
    $s=$conn->prepare("UPDATE EspacePiece SET nom_piece=?,type_piece=?,luminosite=?,taille=?,humidite=?,presence_animaux=?,temperature_approx=?,description=? WHERE id_espace=? AND id_utilisateur=?");
    $s->bind_param("sssssisd ii",$nom,$type,$lum,$tail,$hum,$ani,$temp,$desc,$ie,$uid);$s->execute();$msg='Espace modifie.';
}

// Supprimer espace
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['del_espace'])){
    $ie=(int)$_POST['id_espace'];$s=$conn->prepare("DELETE FROM EspacePiece WHERE id_espace=? AND id_utilisateur=?");$s->bind_param("ii",$ie,$uid);$s->execute();$msg='Espace supprime.';
}

// Wishlist
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['add_wish'])){
    $ip=(int)$_POST['id_plante'];$w=$conn->prepare("INSERT IGNORE INTO ListeSouhaits(id_utilisateur,id_plante) VALUES(?,?)");$w->bind_param("ii",$uid,$ip);$w->execute();
}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['del_wish'])){
    $ip=(int)$_POST['id_plante'];$d=$conn->prepare("DELETE FROM ListeSouhaits WHERE id_utilisateur=? AND id_plante=?");$d->bind_param("ii",$uid,$ip);$d->execute();
}

// Espaces
$espaces=$conn->prepare("SELECT * FROM EspacePiece WHERE id_utilisateur=? ORDER BY date_ajout DESC");
$espaces->bind_param("i",$uid);$espaces->execute();$espaces_list=$espaces->get_result()->fetch_all(MYSQLI_ASSOC);

// Recommandations par espace
$plantes_espace=[];$espace_sel=null;
if(isset($_GET['espace_id'])&&is_numeric($_GET['espace_id'])){
    $ie=(int)$_GET['espace_id'];
    $es=$conn->prepare("SELECT * FROM EspacePiece WHERE id_espace=? AND id_utilisateur=?");
    $es->bind_param("ii",$ie,$uid);$es->execute();$espace_sel=$es->get_result()->fetch_assoc();
    if($espace_sel){
        $lv=$espace_sel['luminosite'];$hv=$espace_sel['humidite'];
        $ac=$espace_sel['presence_animaux']?" AND p.toxicite=0":"";
        $sq="SELECT p.*,f.nom_famille,MIN(c.prix) as prix FROM Plante p LEFT JOIN Famille f ON f.id_famille=p.id_famille LEFT JOIN Catalogue c ON c.id_plante=p.id_plante AND c.disponible=1 WHERE p.besoin_luminosite=? AND p.besoin_humidite=?$ac GROUP BY p.id_plante";
        $s3=$conn->prepare($sq);$s3->bind_param("ss",$lv,$hv);$s3->execute();$plantes_espace=$s3->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Wishlist IDs
$wishlist_ids=[];
$wl=$conn->prepare("SELECT id_plante FROM ListeSouhaits WHERE id_utilisateur=?");
$wl->bind_param("i",$uid);$wl->execute();$wlr=$wl->get_result();
while($row=$wlr->fetch_assoc())$wishlist_ids[]=$row['id_plante'];

// ===== CATALOGUE AVEC FILTRES =====
$f_soin    = $_GET['f_soin']??'';
$f_arrosage= $_GET['f_arrosage']??'';
$f_tox     = $_GET['f_tox']??'';
$f_lum     = $_GET['f_lum']??'';

$where = "WHERE 1=1";
$params = []; $types = "";
if($f_soin)     { $where.=" AND p.niveau_soin=?";     $params[]=$f_soin;     $types.="s"; }
if($f_arrosage) { $where.=" AND p.besoin_arrosage=?";  $params[]=$f_arrosage; $types.="s"; }
if($f_lum)      { $where.=" AND p.besoin_luminosite=?";$params[]=$f_lum;     $types.="s"; }
if($f_tox==='0'){ $where.=" AND p.toxicite=0"; }
if($f_tox==='1'){ $where.=" AND p.toxicite=1"; }

$sql_cat="SELECT p.*,f.nom_famille,MIN(c.prix) as prix FROM Plante p LEFT JOIN Famille f ON f.id_famille=p.id_famille LEFT JOIN Catalogue c ON c.id_plante=p.id_plante AND c.disponible=1 $where GROUP BY p.id_plante ORDER BY p.nom_commun";
if($types){
    $stmt=$conn->prepare($sql_cat);
    $stmt->bind_param($types,...$params);
    $stmt->execute();
    $all_plantes=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $res=$conn->query($sql_cat);
    $all_plantes=$res?$res->fetch_all(MYSQLI_ASSOC):[];
}

// Wishlist complète
$ws=$conn->prepare("SELECT p.*,MIN(c.prix) as prix FROM ListeSouhaits ls JOIN Plante p ON p.id_plante=ls.id_plante LEFT JOIN Catalogue c ON c.id_plante=p.id_plante AND c.disponible=1 WHERE ls.id_utilisateur=? GROUP BY p.id_plante");
$ws->bind_param("i",$uid);$ws->execute();$wished=$ws->get_result()->fetch_all(MYSQLI_ASSOC);

// Accessoires
$acc_res=$conn->query("SELECT a.*,v.nom_boutique FROM Accessoire a JOIN Vendeur v ON v.id_utilisateur=a.id_vendeur WHERE a.stock>0 ORDER BY a.prix ASC");
$all_accessoires=$acc_res?$acc_res->fetch_all(MYSQLI_ASSOC):[];

// Historique commandes
$res_hist=$conn->prepare("
    SELECT c.*,
           COALESCE(p.nom_commun, a.nom_accessoire) as article_nom,
           v.nom_boutique
    FROM Commande c
    LEFT JOIN Plante p ON p.id_plante=c.id_plante
    LEFT JOIN Accessoire a ON a.id_accessoire=c.id_accessoire
    LEFT JOIN Vendeur v ON v.id_utilisateur=c.id_vendeur
    WHERE c.id_utilisateur=?
    ORDER BY c.date_commande DESC
");
$res_hist->bind_param("i",$uid);$res_hist->execute();
$historique=$res_hist->get_result()->fetch_all(MYSQLI_ASSOC);

$panier_count=isset($_SESSION['panier'])?count($_SESSION['panier']):0;
$conn->close();

function pimg($url,$nom){
    if($url)return '<img src="'.htmlspecialchars($url).'" alt="'.htmlspecialchars($nom).'" loading="lazy" onerror="this.parentNode.innerHTML=\'<div class=plant-img-placeholder><svg viewBox=\\\'0 0 24 24\\\'><path d=\\\'M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z\\\'/></svg></div>\'">';
    return '<div class="plant-img-placeholder"><svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z"/></svg></div>';
}
function stag($s){$c=$s==='facile'?'green':($s==='moyen'?'yellow':'red');return '<span class="tag tag-'.$c.'">'.ucfirst($s).'</span>';}
function statut_badge($s){
    $cl=$s==='confirme'?'tag-green':($s==='livre'?'tag-blue':'tag-yellow');
    $lb=$s==='en_attente'?'En attente':($s==='confirme'?'Confirme':'Livre');
    return '<span class="tag '.$cl.'">'.$lb.'</span>';
}
$leaf='<svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z"/></svg>';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>PlantApp — Espace Particulier</title>
<link rel="stylesheet" href="style.css">
<style>
/* Filtres */
.filters-bar{display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1.4rem;padding:1rem 1.2rem;background:white;border-radius:12px;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(26,58,42,.05);}
.filter-group{display:flex;flex-direction:column;gap:.3rem;}
.filter-group label{font-size:.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;}
.filter-select{padding:.45rem .8rem;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.85rem;color:#1f2937;background:#fafafa;outline:none;font-family:'Inter',sans-serif;transition:border-color .2s;cursor:pointer;}
.filter-select:focus{border-color:#2d6a4f;}
.filter-select.active{border-color:#2d6a4f;background:#f0faf4;color:#1a3a2a;font-weight:600;}
.btn-reset{padding:.45rem 1rem;background:#fef2f2;border:1.5px solid #fca5a5;color:#dc2626;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;align-self:flex-end;text-decoration:none;transition:all .2s;}
.btn-reset:hover{background:#dc2626;color:white;}
.filter-result{font-size:.82rem;color:#6b7280;align-self:flex-end;padding:.45rem 0;}

/* Modifier espace modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:1000;padding:1rem;}
.modal-box{background:white;border-radius:14px;padding:2rem;max-width:580px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px rgba(26,58,42,.2);}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;}
.modal-header h3{font-family:'Playfair Display',serif;font-size:1.2rem;color:#1a3a2a;}
.modal-close{background:none;border:none;font-size:1.3rem;cursor:pointer;color:#6b7280;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:background .2s;}
.modal-close:hover{background:#fef2f2;color:#dc2626;}

/* Historique */
.hist-item{display:flex;align-items:center;gap:1rem;padding:1rem 1.2rem;background:white;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:.75rem;box-shadow:0 2px 6px rgba(26,58,42,.05);}
.hist-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.hist-icon.plante{background:#f0faf4;}
.hist-icon.accessoire{background:#eff6ff;}
.hist-icon svg{width:22px;height:22px;}
.hist-info{flex:1;}
.hist-nom{font-weight:600;color:#1a3a2a;font-size:.92rem;}
.hist-meta{font-size:.78rem;color:#9ca3af;margin-top:.15rem;}
.hist-right{text-align:right;}
.hist-prix{font-size:1rem;font-weight:700;color:#2d6a4f;}
.hist-date{font-size:.75rem;color:#9ca3af;margin-top:.15rem;}

/* Accessoires */
.acc-shop-card{background:white;border-radius:12px;padding:1.2rem;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(26,58,42,.06);transition:all .25s;}
.acc-shop-card:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(26,58,42,.12);border-color:#2d6a4f;}
.acc-shop-card h4{font-family:'Playfair Display',serif;font-size:.92rem;color:#1a3a2a;margin-bottom:.3rem;}
.acc-boutique-tag{font-size:.72rem;background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;border-radius:20px;padding:2px 8px;display:inline-block;margin-bottom:.4rem;}
.acc-shop-card p{font-size:.8rem;color:#6b7280;margin-bottom:.5rem;line-height:1.5;}
.acc-price{font-size:1.05rem;font-weight:700;color:#2d6a4f;margin-bottom:.4rem;}
.acc-stock{font-size:.74rem;color:#9ca3af;margin-bottom:.6rem;}
.acc-img-wrap{width:100%;height:140px;border-radius:10px;overflow:hidden;background:#f0f9ff;margin-bottom:.8rem;}
.acc-img-wrap img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
.acc-shop-card:hover .acc-img-wrap img{transform:scale(1.05);}
.acc-img-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#eff6ff;}
.acc-img-ph svg{width:48px;height:48px;fill:#3b82f6;opacity:.4;}
</style>
</head>
<body>
<nav class="navbar">
  <a href="particulier.php" class="navbar-brand">
    <div class="logo-icon"><?=$leaf?></div>
    <div><h1>PlantApp</h1><span>Espace Particulier</span></div>
  </a>
  <ul class="navbar-nav">
    <li><a href="#profil">Mon Profil</a></li>
    <li><a href="#espaces">Mes Espaces</a></li>
    <li><a href="#catalogue">Catalogue</a></li>
    <li><a href="#accessoires">Accessoires</a></li>
    <li><a href="#wishlist">Wishlist</a></li>
    <li><a href="#historique">Mes Commandes</a></li>
    <li><a href="panier.php" style="position:relative;">Panier
      <?php if($panier_count>0):?><span style="position:absolute;top:-6px;right:-8px;background:#2d6a4f;color:white;border-radius:50%;width:17px;height:17px;font-size:.65rem;display:flex;align-items:center;justify-content:center;font-weight:700;"><?=$panier_count?></span><?php endif;?>
    </a></li>
    <li><a href="logout.php" class="btn-logout">Deconnexion</a></li>
  </ul>
</nav>

<div class="main-container">
  <div class="page-header">
    <h2>Bonjour, <?=htmlspecialchars($_SESSION['nom'])?></h2>
    <p>Gerez vos espaces et trouvez les plantes adaptees</p>
  </div>
  <?php if($msg):?><div class="alert alert-success"><?=htmlspecialchars($msg)?></div><?php endif;?>

  <!-- PROFIL -->
  <section id="profil">
    <div class="card">
      <div class="card-header"><div class="card-icon"><?=$leaf?></div><h3 class="card-title">Mon Profil</h3></div>
      <form method="POST">
        <div class="grid-3">
          <div class="form-group"><label>Localisation</label><input type="text" name="localisation" value="<?=htmlspecialchars($profil['localisation']??'')?>" placeholder="Ex: Tunis"></div>
          <div class="form-group"><label>Niveau experience</label>
            <select name="niveau_experience"><?php foreach(['debutant'=>'Debutant','intermediaire'=>'Intermediaire','expert'=>'Expert'] as $v=>$l):?><option value="<?=$v?>" <?=($profil['niveau_experience']??'')===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select>
          </div>
          <div class="form-group"><label>Disponibilite arrosage</label>
            <select name="disponibilite_arrosage"><?php foreach(['quotidien'=>'Quotidien','hebdomadaire'=>'Hebdomadaire','bimensuel'=>'Bimensuel'] as $v=>$l):?><option value="<?=$v?>" <?=($profil['disponibilite_arrosage']??'')===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select>
          </div>
        </div>
        <button type="submit" name="save_profil" class="btn btn-primary">Enregistrer</button>
      </form>
    </div>
  </section>

  <!-- ESPACES -->
  <section id="espaces" style="margin-top:2.5rem;">
    <div class="page-header"><h2>Mes Espaces</h2><p>Decrivez chaque piece pour recevoir des recommandations</p></div>
    <div class="grid-2">
      <div class="card">
        <div class="card-header"><div class="card-icon"><?=$leaf?></div><h3 class="card-title">Ajouter un espace</h3></div>
        <form method="POST">
          <div class="form-group"><label>Nom *</label><input type="text" name="nom_piece" required placeholder="Ex: Salon..."></div>
          <div class="form-group"><label>Type</label>
            <select name="type_piece"><option value="salon">Salon</option><option value="chambre">Chambre</option><option value="bureau">Bureau</option><option value="cuisine">Cuisine</option><option value="salle_bain">Salle de bain</option><option value="balcon">Balcon</option><option value="autre">Autre</option></select>
          </div>
          <div class="grid-2">
            <div class="form-group"><label>Luminosite</label><select name="esp_luminosite"><option value="faible">Faible</option><option value="moyenne" selected>Moyenne</option><option value="forte">Forte</option></select></div>
            <div class="form-group"><label>Taille</label><select name="esp_taille"><option value="petite">Petite</option><option value="moyenne" selected>Moyenne</option><option value="grande">Grande</option></select></div>
            <div class="form-group"><label>Humidite</label><select name="esp_humidite"><option value="faible">Faible</option><option value="moyenne" selected>Moyenne</option><option value="elevee">Elevee</option></select></div>
            <div class="form-group"><label>Temperature (C)</label><input type="number" name="temperature_approx" value="20" step="1" min="5" max="40"></div>
          </div>
          <div class="form-group"><label class="checkbox-label"><input type="checkbox" name="esp_animaux"> Presence d animaux</label></div>
          <div class="form-group"><label>Description</label><textarea name="esp_description" rows="2" style="width:100%;padding:.72rem;border:1.5px solid #e5e7eb;border-radius:8px;font-family:inherit;font-size:.9rem;background:#fafafa;outline:none;resize:vertical;"></textarea></div>
          <button type="submit" name="add_espace" class="btn btn-primary">Ajouter</button>
        </form>
      </div>
      <div style="display:flex;flex-direction:column;gap:1rem;">
        <?php if(empty($espaces_list)):?><div class="empty-state"><p>Aucun espace ajoute.</p></div>
        <?php else: foreach($espaces_list as $esp):?>
        <div class="espace-card <?=(isset($_GET['espace_id'])&&(int)$_GET['espace_id']===$esp['id_espace'])?'active':''?>">
          <div class="espace-top"><div><div class="espace-nom"><?=htmlspecialchars($esp['nom_piece'])?></div><div class="espace-type"><?=str_replace('_',' ',$esp['type_piece'])?></div></div></div>
          <div class="espace-attrs">
            <span class="badge-attr">Luminosite : <?=ucfirst($esp['luminosite'])?></span>
            <span class="badge-attr">Taille : <?=ucfirst($esp['taille'])?></span>
            <span class="badge-attr">Humidite : <?=ucfirst($esp['humidite'])?></span>
            <span class="badge-attr"><?=$esp['temperature_approx']?> C</span>
            <?php if($esp['presence_animaux']):?><span class="badge-attr red">Animaux</span><?php endif;?>
          </div>
          <div class="espace-actions">
            <a href="particulier.php?espace_id=<?=$esp['id_espace']?>" class="btn btn-outline btn-sm">Voir plantes</a>
            <button class="btn btn-sm" style="background:#f0faf4;color:#2d6a4f;border:1.5px solid #d1fae5;" onclick="openEditEspace(<?=htmlspecialchars(json_encode($esp),ENT_QUOTES)?>)">Modifier</button>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
              <input type="hidden" name="id_espace" value="<?=$esp['id_espace']?>">
              <button type="submit" name="del_espace" class="btn btn-danger btn-sm">Supprimer</button>
            </form>
          </div>
        </div>
        <?php endforeach; endif;?>
      </div>
    </div>

    <?php if($espace_sel):?>
    <div style="margin-top:2rem;">
      <div class="page-header"><h2>Plantes pour "<?=htmlspecialchars($espace_sel['nom_piece'])?>"</h2><p>Plantes filtrees par luminosite et humidite de votre espace</p></div>
      <?php if(empty($plantes_espace)):?><div class="empty-state"><p>Aucune plante compatible avec cet espace.</p></div>
      <?php else:?><div class="grid-4">
        <?php foreach($plantes_espace as $p):?>
        <a href="plante.php?id=<?=$p['id_plante']?>" style="text-decoration:none;">
        <div class="plant-card">
          <div class="plant-img-wrap"><?=pimg($p['image_url'],$p['nom_commun'])?><?php if($p['toxicite']):?><span class="toxic-badge">Toxique</span><?php endif;?></div>
          <div class="plant-card-body">
            <h4><?=htmlspecialchars($p['nom_commun'])?></h4>
            <p class="plant-sci"><?=htmlspecialchars($p['nom_scientifique']??'')?></p>
            <div class="plant-tags"><?=stag($p['niveau_soin'])?></div>
            <?php if($p['prix']):?><p class="plant-price">A partir de <?=number_format($p['prix'],2)?> DT</p><?php endif;?>
            <div class="btn btn-primary btn-sm btn-full" style="margin-top:.5rem;">Voir boutiques</div>
          </div>
        </div></a>
        <?php endforeach;?></div><?php endif;?>
    </div>
    <?php endif;?>
  </section>

  <!-- CATALOGUE AVEC FILTRES -->
  <section id="catalogue" style="margin-top:2.5rem;">
    <div class="page-header"><h2>Catalogue Plantes</h2><p><?=count($all_plantes)?> plante(s) trouvee(s)</p></div>

    <!-- BARRE DE FILTRES -->
    <form method="GET" action="particulier.php">
      <input type="hidden" name="anchor" value="catalogue">
      <div class="filters-bar">
        <div class="filter-group">
          <label>Niveau de soin</label>
          <select name="f_soin" class="filter-select <?=$f_soin?'active':''?>" onchange="this.form.submit()">
            <option value="">Tous</option>
            <option value="facile"    <?=$f_soin==='facile'?'selected':''?>>Facile</option>
            <option value="moyen"     <?=$f_soin==='moyen'?'selected':''?>>Moyen</option>
            <option value="difficile" <?=$f_soin==='difficile'?'selected':''?>>Difficile</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Arrosage</label>
          <select name="f_arrosage" class="filter-select <?=$f_arrosage?'active':''?>" onchange="this.form.submit()">
            <option value="">Tous</option>
            <option value="quotidien"    <?=$f_arrosage==='quotidien'?'selected':''?>>Quotidien</option>
            <option value="hebdomadaire" <?=$f_arrosage==='hebdomadaire'?'selected':''?>>Hebdomadaire</option>
            <option value="bimensuel"    <?=$f_arrosage==='bimensuel'?'selected':''?>>Bimensuel</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Luminosite</label>
          <select name="f_lum" class="filter-select <?=$f_lum?'active':''?>" onchange="this.form.submit()">
            <option value="">Toutes</option>
            <option value="faible"  <?=$f_lum==='faible'?'selected':''?>>Faible</option>
            <option value="moyenne" <?=$f_lum==='moyenne'?'selected':''?>>Moyenne</option>
            <option value="forte"   <?=$f_lum==='forte'?'selected':''?>>Forte</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Toxicite</label>
          <select name="f_tox" class="filter-select <?=$f_tox!==''?'active':''?>" onchange="this.form.submit()">
            <option value="">Toutes</option>
            <option value="0" <?=$f_tox==='0'?'selected':''?>>Non toxique</option>
            <option value="1" <?=$f_tox==='1'?'selected':''?>>Toxique</option>
          </select>
        </div>
        <?php if($f_soin||$f_arrosage||$f_lum||$f_tox!==''):?>
          <a href="particulier.php#catalogue" class="btn-reset">Reinitialiser</a>
        <?php endif;?>
        <div class="filter-result"><?=count($all_plantes)?> resultat(s)</div>
      </div>
    </form>

    <div class="search-bar"><input type="text" id="searchInput" placeholder="Rechercher une plante..." onkeyup="filterPlants()"></div>

    <?php if(empty($all_plantes)):?>
      <div class="empty-state"><p>Aucune plante ne correspond aux filtres selectionnes.</p></div>
    <?php else:?>
    <div class="grid-4" id="allPlantsGrid">
      <?php foreach($all_plantes as $p):?>
      <a href="plante.php?id=<?=$p['id_plante']?>" style="text-decoration:none;">
      <div class="plant-card plant-item" data-name="<?=strtolower(htmlspecialchars($p['nom_commun']))?>">
        <div class="plant-img-wrap"><?=pimg($p['image_url'],$p['nom_commun'])?><?php if($p['toxicite']):?><span class="toxic-badge">Toxique</span><?php endif;?></div>
        <div class="plant-card-body">
          <h4><?=htmlspecialchars($p['nom_commun'])?></h4>
          <p class="plant-sci"><?=htmlspecialchars($p['nom_scientifique']??'')?></p>
          <div class="plant-tags"><?=stag($p['niveau_soin'])?><span class="tag tag-blue"><?=$p['besoin_arrosage']?></span></div>
          <?php if($p['prix']):?><p class="plant-price">A partir de <?=number_format($p['prix'],2)?> DT</p><?php endif;?>
          <div class="btn btn-outline btn-sm btn-full" style="margin-top:.5rem;">Voir boutiques</div>
        </div>
      </div></a>
      <?php endforeach;?>
    </div>
    <?php endif;?>
  </section>

  <!-- ACCESSOIRES -->
  <section id="accessoires" style="margin-top:2.5rem;">
    <div class="page-header"><h2>Accessoires</h2><p><?=count($all_accessoires)?> accessoires disponibles</p></div>
    <?php if(empty($all_accessoires)):?>
      <div class="empty-state"><p>Aucun accessoire disponible.</p></div>
    <?php else:?>
    <div class="grid-4">
      <?php foreach($all_accessoires as $a):?>
      <div class="acc-shop-card">
        <div class="acc-img-wrap">
          <?php if(!empty($a['image_url'])): ?>
            <img src="<?=htmlspecialchars($a['image_url'])?>" alt="<?=htmlspecialchars($a['nom_accessoire'])?>"
              onerror="this.parentNode.innerHTML='<div class=acc-img-ph><svg viewBox='0 0 24 24'><path d='M20 6h-2.18c.07-.44.18-.88.18-1.36C18 2.06 15.96 0 13.5 0c-1.5 0-2.78.78-3.5 1.94C9.28.78 8 0 6.5 0 4.04 0 2 2.06 2 4.64c0 .48.11.92.18 1.36H0v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6z'/></svg></div>'">
          <?php else: ?>
            <div class="acc-img-ph"><svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.36C18 2.06 15.96 0 13.5 0c-1.5 0-2.78.78-3.5 1.94C9.28.78 8 0 6.5 0 4.04 0 2 2.06 2 4.64c0 .48.11.92.18 1.36H0v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6z"/></svg></div>
          <?php endif; ?>
        </div>
        <span class="acc-boutique-tag"><?=htmlspecialchars($a['nom_boutique'])?></span>
        <h4><?=htmlspecialchars($a['nom_accessoire'])?></h4>
        <p><?=htmlspecialchars($a['description']??'')?></p>
        <div class="acc-price"><?=number_format($a['prix'],2)?> DT</div>
        <div class="acc-stock"><?=$a['stock']?> unite(s) disponible(s)</div>
        <a href="panier.php?add_accessoire=<?=$a['id_accessoire']?>&vendeur=<?=$a['id_vendeur']?>&prix=<?=$a['prix']?>&stock=<?=$a['stock']?>" class="btn btn-primary btn-sm btn-full">Ajouter au panier</a>
      </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>
  </section>

  <!-- WISHLIST -->
  <section id="wishlist" style="margin-top:2.5rem;">
    <div class="page-header"><h2>Ma Wishlist</h2><p><?=count($wished)?> plante(s)</p></div>
    <?php if(empty($wished)):?><div class="empty-state"><p>Votre wishlist est vide.</p></div>
    <?php else:?><div class="grid-4">
      <?php foreach($wished as $p):?>
      <a href="plante.php?id=<?=$p['id_plante']?>" style="text-decoration:none;">
      <div class="plant-card">
        <div class="plant-img-wrap"><?=pimg($p['image_url'],$p['nom_commun'])?></div>
        <div class="plant-card-body">
          <h4><?=htmlspecialchars($p['nom_commun'])?></h4>
          <p class="plant-sci"><?=htmlspecialchars($p['nom_scientifique']??'')?></p>
          <?php if($p['prix']):?><p class="plant-price">A partir de <?=number_format($p['prix'],2)?> DT</p><?php endif;?>
          <div class="btn btn-primary btn-sm btn-full" style="margin-top:.5rem;">Voir boutiques</div>
        </div>
      </div></a>
      <?php endforeach;?></div>
    <?php endif;?>
  </section>

  <!-- HISTORIQUE COMMANDES -->
  <section id="historique" style="margin-top:2.5rem;">
    <div class="page-header"><h2>Mes Commandes</h2><p><?=count($historique)?> commande(s) passee(s)</p></div>
    <?php if(empty($historique)):?>
      <div class="empty-state"><p>Vous n avez pas encore passe de commande.</p></div>
    <?php else:?>
    <?php foreach($historique as $h):?>
    <div class="hist-item">
      <div class="hist-icon <?=$h['type_commande']?>">
        <?php if($h['type_commande']==='plante'):?>
          <svg viewBox="0 0 24 24" style="fill:#2d6a4f;"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z"/></svg>
        <?php else:?>
          <svg viewBox="0 0 24 24" style="fill:#3b82f6;"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.36C18 2.06 15.96 0 13.5 0c-1.5 0-2.78.78-3.5 1.94C9.28.78 8 0 6.5 0 4.04 0 2 2.06 2 4.64c0 .48.11.92.18 1.36H0v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6z"/></svg>
        <?php endif;?>
      </div>
      <div class="hist-info">
        <div class="hist-nom"><?=htmlspecialchars($h['article_nom']??'Article')?></div>
        <div class="hist-meta">
          Boutique : <?=htmlspecialchars($h['nom_boutique']??'-')?> &nbsp;|&nbsp;
          Quantite : <?=$h['quantite']?> u. &nbsp;|&nbsp;
          <?=$h['type_commande']==='plante'?'Plante':'Accessoire'?>
        </div>
      </div>
      <div class="hist-right">
        <div class="hist-prix"><?=number_format($h['prix_total'],2)?> DT</div>
        <div style="margin-top:.3rem;"><?=statut_badge($h['statut'])?></div>
        <div class="hist-date"><?=date('d/m/Y H:i',strtotime($h['date_commande']))?></div>
      </div>
    </div>
    <?php endforeach;?>
    <?php endif;?>
  </section>
</div>

<!-- MODAL MODIFIER ESPACE -->
<div id="editEspaceModal" class="modal-overlay" style="display:none;">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Modifier l espace</h3>
      <button onclick="closeEditEspace()" class="modal-close">&#x2715;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="id_espace" id="edit_esp_id">
      <div class="form-group"><label>Nom *</label><input type="text" name="nom_piece" id="edit_esp_nom" required></div>
      <div class="form-group"><label>Type</label>
        <select name="type_piece" id="edit_esp_type">
          <option value="salon">Salon</option><option value="chambre">Chambre</option>
          <option value="bureau">Bureau</option><option value="cuisine">Cuisine</option>
          <option value="salle_bain">Salle de bain</option><option value="balcon">Balcon</option>
          <option value="autre">Autre</option>
        </select>
      </div>
      <div class="grid-2">
        <div class="form-group"><label>Luminosite</label>
          <select name="esp_luminosite" id="edit_esp_lum">
            <option value="faible">Faible</option><option value="moyenne">Moyenne</option><option value="forte">Forte</option>
          </select>
        </div>
        <div class="form-group"><label>Taille</label>
          <select name="esp_taille" id="edit_esp_tail">
            <option value="petite">Petite</option><option value="moyenne">Moyenne</option><option value="grande">Grande</option>
          </select>
        </div>
        <div class="form-group"><label>Humidite</label>
          <select name="esp_humidite" id="edit_esp_hum">
            <option value="faible">Faible</option><option value="moyenne">Moyenne</option><option value="elevee">Elevee</option>
          </select>
        </div>
        <div class="form-group"><label>Temperature (C)</label>
          <input type="number" name="temperature_approx" id="edit_esp_temp" step="1" min="5" max="40">
        </div>
      </div>
      <div class="form-group"><label class="checkbox-label"><input type="checkbox" name="esp_animaux" id="edit_esp_ani"> Presence d animaux</label></div>
      <div class="form-group"><label>Description</label>
        <textarea name="esp_description" id="edit_esp_desc" rows="2" style="width:100%;padding:.72rem;border:1.5px solid #e5e7eb;border-radius:8px;font-family:inherit;font-size:.9rem;background:#fafafa;outline:none;resize:vertical;"></textarea>
      </div>
      <div style="display:flex;gap:1rem;">
        <button type="submit" name="edit_espace" class="btn btn-primary">Enregistrer</button>
        <button type="button" onclick="closeEditEspace()" class="btn btn-outline">Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- CHARIOT FLOTTANT -->
<a href="panier.php" style="position:fixed;bottom:2rem;right:2rem;width:62px;height:62px;background:linear-gradient(135deg,#2d6a4f,#1a3a2a);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 24px rgba(26,58,42,.4);text-decoration:none;z-index:999;transition:transform .25s,box-shadow .25s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
  <svg viewBox="0 0 24 24" style="width:28px;height:28px;fill:white;"><path d="M17 18c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 5.9 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63H19c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0 0 23.43 4H5.21l-.94-2H1zm6 16c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2z"/></svg>
  <?php if($panier_count>0):?><span style="position:absolute;top:-4px;right:-4px;background:#ef4444;color:white;border-radius:50%;width:22px;height:22px;font-size:.72rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid white;"><?=$panier_count?></span><?php endif;?>
</a>

<script src="script.js"></script>
<script>
function filterPlants() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.plant-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}
function openEditEspace(esp) {
    document.getElementById('edit_esp_id').value   = esp.id_espace;
    document.getElementById('edit_esp_nom').value  = esp.nom_piece;
    document.getElementById('edit_esp_temp').value = esp.temperature_approx;
    document.getElementById('edit_esp_desc').value = esp.description||'';
    document.getElementById('edit_esp_ani').checked = esp.presence_animaux==1;
    setSelectVal('edit_esp_type', esp.type_piece);
    setSelectVal('edit_esp_lum',  esp.luminosite);
    setSelectVal('edit_esp_tail', esp.taille);
    setSelectVal('edit_esp_hum',  esp.humidite);
    document.getElementById('editEspaceModal').style.display = 'flex';
}
function closeEditEspace() {
    document.getElementById('editEspaceModal').style.display = 'none';
}
function setSelectVal(id, val) {
    const s = document.getElementById(id);
    if(s) for(let o of s.options) if(o.value===val){o.selected=true;break;}
}
document.addEventListener('keydown', e => { if(e.key==='Escape') closeEditEspace(); });
</script>
</body>
</html>
