<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role']!=='particulier') {
    header('Location: index.php'); exit();
}
require_once 'db.php';
$uid = (int)$_SESSION['user_id'];
if (!isset($_SESSION['panier'])) $_SESSION['panier'] = [];

// ---- AJOUTER PLANTE ----
if (isset($_GET['add_plante'])) {
    $id_p       = (int)$_GET['add_plante'];
    $id_vendeur = (int)($_GET['vendeur']??0);
    $prix       = (float)($_GET['prix']??0);
    $stock      = (int)($_GET['stock']??0);
    if ($id_p && $id_vendeur && $prix > 0) {
        $conn = getConnection();
        $r = $conn->prepare("SELECT p.nom_commun,p.nom_scientifique,p.image_url,v.nom_boutique FROM Plante p, Vendeur v WHERE v.id_utilisateur=? AND p.id_plante=?");
        $r->bind_param("ii",$id_vendeur,$id_p); $r->execute();
        $pl = $r->get_result()->fetch_assoc();
        $conn->close();
        if ($pl) {
            $key = 'p_'.$id_p.'_'.$id_vendeur;
            if (isset($_SESSION['panier'][$key])) {
                $_SESSION['panier'][$key]['qte']++;
            } else {
                $_SESSION['panier'][$key] = [
                    'type'       => 'plante',
                    'id'         => $id_p,
                    'id_vendeur' => $id_vendeur,
                    'nom'        => $pl['nom_commun'],
                    'sci'        => $pl['nom_scientifique']??'',
                    'boutique'   => $pl['nom_boutique'],
                    'prix'       => $prix,
                    'image'      => $pl['image_url']??'',
                    'qte'        => 1,
                    'stock'      => $stock,
                ];
            }
        }
    }
    header('Location: panier.php'); exit();
}

// ---- AJOUTER ACCESSOIRE ----
if (isset($_GET['add_accessoire'])) {
    $id_a       = (int)$_GET['add_accessoire'];
    $id_vendeur = (int)($_GET['vendeur']??0);
    $prix       = (float)($_GET['prix']??0);
    $stock      = (int)($_GET['stock']??0);
    if ($id_a && $id_vendeur && $prix > 0) {
        $conn = getConnection();
        $r = $conn->prepare("SELECT a.nom_accessoire,a.description,v.nom_boutique FROM Accessoire a JOIN Vendeur v ON v.id_utilisateur=a.id_vendeur WHERE a.id_accessoire=?");
        $r->bind_param("i",$id_a); $r->execute();
        $acc = $r->get_result()->fetch_assoc();
        $conn->close();
        if ($acc) {
            $key = 'a_'.$id_a.'_'.$id_vendeur;
            if (isset($_SESSION['panier'][$key])) {
                $_SESSION['panier'][$key]['qte']++;
            } else {
                $_SESSION['panier'][$key] = [
                    'type'       => 'accessoire',
                    'id'         => $id_a,
                    'id_vendeur' => $id_vendeur,
                    'nom'        => $acc['nom_accessoire'],
                    'sci'        => $acc['description']??'',
                    'boutique'   => $acc['nom_boutique'],
                    'prix'       => $prix,
                    'image'      => '',
                    'qte'        => 1,
                    'stock'      => $stock,
                ];
            }
        }
    }
    header('Location: panier.php'); exit();
}

// ---- SUPPRIMER ----
if (isset($_GET['del'])) {
    unset($_SESSION['panier'][$_GET['del']]);
    header('Location: panier.php'); exit();
}

// ---- MODIFIER QUANTITE ----
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_qte'])) {
    foreach ($_POST['qte'] as $key => $qte) {
        $qte = max(1,(int)$qte);
        if (isset($_SESSION['panier'][$key])) {
            $_SESSION['panier'][$key]['qte'] = min($qte,$_SESSION['panier'][$key]['stock']);
        }
    }
    header('Location: panier.php'); exit();
}

// ---- COMMANDER ----
$success = false;
$errors  = [];
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['commander'])) {
    $nom_carte = trim($_POST['nom_carte']??'');
    $num_carte = preg_replace('/\s+/','',$_POST['numero_carte']??'');
    $exp       = trim($_POST['date_expiration']??'');
    $cvv       = trim($_POST['cvv']??'');
    $adresse   = trim($_POST['adresse']??'');
    $ville     = trim($_POST['ville']??'');
    $cp        = trim($_POST['code_postal']??'');

    if (!$nom_carte)             $errors[] = 'Le nom du titulaire est requis.';
    if (strlen($num_carte) < 16) $errors[] = 'Le numero de carte doit contenir 16 chiffres.';
    if (!preg_match('/^\d{2}\/\d{2}$/',$exp)) $errors[] = 'Format date expiration : MM/AA.';
    if (strlen($cvv) < 3)        $errors[] = 'CVV invalide.';
    if (!$adresse)               $errors[] = 'L adresse est requise.';
    if (!$ville)                 $errors[] = 'La ville est requise.';
    if (!$cp)                    $errors[] = 'Le code postal est requis.';
    if (empty($_SESSION['panier'])) $errors[] = 'Votre panier est vide.';

    if (empty($errors)) {
        $conn = getConnection();
        foreach ($_SESSION['panier'] as $item) {
            $total      = $item['prix'] * $item['qte'];
            $num_masque = '****'.substr($num_carte,-4);
            $id_vendeur = (int)$item['id_vendeur'];
            $type       = $item['type'];

            if ($type === 'plante') {
                $id_plante    = (int)$item['id'];
                $id_accessoire = null;
                $conn->query("UPDATE Catalogue SET stock=stock-".$item['qte']." WHERE id_plante=$id_plante AND id_vendeur=$id_vendeur AND stock>=".$item['qte']);
            } else {
                $id_plante    = null;
                $id_accessoire = (int)$item['id'];
                $conn->query("UPDATE Accessoire SET stock=stock-".$item['qte']." WHERE id_accessoire=$id_accessoire AND stock>=".$item['qte']);
            }

            // mysqli ne supporte pas null directement dans bind_param
            // on utilise des variables intermediaires
            $v_plante     = ($type==='plante') ? (int)$item['id'] : 0;
            $v_accessoire = ($type==='accessoire') ? (int)$item['id'] : 0;
            // 0 signifie pas de plante/accessoire (NULL equivalent)

            $s = $conn->prepare("INSERT INTO Commande(id_utilisateur,id_vendeur,id_plante,id_accessoire,type_commande,quantite,prix_total,nom_carte,numero_carte,date_expiration,adresse_livraison,ville,code_postal) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $s->bind_param("iiiisidssssss",
                $uid, $id_vendeur, $v_plante, $v_accessoire,
                $type, $item['qte'], $total,
                $nom_carte, $num_masque, $exp,
                $adresse, $ville, $cp
            );
            $s->execute();
            if($s->error) error_log("Commande error: ".$s->error);

            $sv = $conn->prepare("UPDATE Vendeur SET solde = solde + ? WHERE id_utilisateur = ?");
            $sv->bind_param("di",$total,$id_vendeur);
            $sv->execute();
        }
        $conn->close();
        $_SESSION['panier'] = [];
        $success = true;
    }
}

$total_general = 0;
foreach ($_SESSION['panier'] as $item) $total_general += $item['prix'] * $item['qte'];
$nb_plantes     = count(array_filter($_SESSION['panier'], fn($i)=>$i['type']==='plante'));
$nb_accessoires = count(array_filter($_SESSION['panier'], fn($i)=>$i['type']==='accessoire'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Panier — PlantApp</title>
<link rel="stylesheet" href="style.css">
<style>
.panier-layout{display:grid;grid-template-columns:1fr 400px;gap:2rem;align-items:start;}
.panier-item{display:flex;align-items:center;gap:1.2rem;padding:1.2rem;background:white;border-radius:12px;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(26,58,42,.06);margin-bottom:.9rem;}
.panier-img{width:72px;height:72px;border-radius:10px;overflow:hidden;flex-shrink:0;background:#f0faf4;}
.panier-img img{width:100%;height:100%;object-fit:cover;}
.panier-img-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;}
.panier-img-ph svg{width:28px;height:28px;fill:#52b788;opacity:.6;}
.panier-info{flex:1;}
.panier-info h4{font-family:'Playfair Display',serif;font-size:.95rem;color:#1a3a2a;margin-bottom:.15rem;}
.type-badge{font-size:.7rem;padding:2px 8px;border-radius:20px;display:inline-block;margin-bottom:.25rem;font-weight:600;}
.type-plante{background:#dcfce7;color:#166534;}
.type-accessoire{background:#dbeafe;color:#1e40af;}
.boutique-badge{font-size:.72rem;background:#f0faf4;color:#2d6a4f;border:1px solid #d1fae5;border-radius:20px;padding:2px 8px;display:inline-block;margin-bottom:.2rem;}
.panier-info p{font-size:.78rem;color:#6b7280;font-style:italic;}
.panier-prix{font-size:1rem;font-weight:700;color:#2d6a4f;margin-top:.3rem;}
.qte-input{width:58px;padding:.38rem .55rem;border:1.5px solid #e5e7eb;border-radius:7px;text-align:center;font-size:.9rem;}
.del-btn{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;border-radius:7px;padding:.38rem .75rem;cursor:pointer;font-size:.78rem;font-weight:600;transition:all .2s;white-space:nowrap;text-decoration:none;display:inline-block;}
.del-btn:hover{background:#dc2626;color:white;}
.section-sep{font-size:.78rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin:1rem 0 .5rem;padding-bottom:.4rem;border-bottom:1px solid #e5e7eb;}
.summary-card{background:white;border-radius:12px;padding:1.8rem;box-shadow:0 2px 12px rgba(26,58,42,.08);border:1px solid #e5e7eb;position:sticky;top:80px;}
.summary-title{font-family:'Playfair Display',serif;font-size:1.2rem;color:#1a3a2a;margin-bottom:1.2rem;}
.summary-line{display:flex;justify-content:space-between;font-size:.86rem;color:#4b5563;margin-bottom:.55rem;padding-bottom:.55rem;border-bottom:1px solid #f3f4f6;}
.summary-total{display:flex;justify-content:space-between;font-size:1.1rem;font-weight:700;color:#1a3a2a;margin-top:1rem;padding-top:1rem;border-top:2px solid #1a3a2a;}
.pay-title{font-family:'Playfair Display',serif;font-size:1.3rem;color:#1a3a2a;margin-bottom:1.4rem;padding-bottom:.8rem;border-bottom:2px solid #f0faf4;}
.pay-section{font-size:.76rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin:1.1rem 0 .65rem;}
.card-row{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;}
.card-preview{background:linear-gradient(135deg,#1a3a2a,#2d6a4f);border-radius:14px;padding:1.4rem;color:white;margin-bottom:1.4rem;}
.card-preview .cp-bank{font-size:.72rem;opacity:.7;margin-bottom:.5rem;}
.card-preview .cp-num{font-size:1.05rem;letter-spacing:.15em;margin-bottom:.75rem;font-weight:600;}
.card-preview .cp-bottom{display:flex;justify-content:space-between;font-size:.76rem;opacity:.85;}
.error-list{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:1rem 1.2rem;border-radius:10px;margin-bottom:1.4rem;}
.error-list li{font-size:.86rem;margin-left:1.2rem;margin-bottom:.3rem;}
.success-box{background:#f0fdf4;border:2px solid #86efac;color:#166534;padding:2.5rem;border-radius:12px;text-align:center;}
.success-box h3{font-family:'Playfair Display',serif;font-size:1.6rem;margin-bottom:.8rem;}
@media(max-width:900px){.panier-layout{grid-template-columns:1fr;}}
</style>
</head>
<body>
<nav class="navbar">
  <a href="particulier.php" class="navbar-brand">
    <div class="logo-icon"><svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z"/></svg></div>
    <div><h1>PlantApp</h1><span>Mon Panier</span></div>
  </a>
  <ul class="navbar-nav">
    <li><a href="particulier.php#catalogue">Catalogue</a></li>
    <li><a href="particulier.php#accessoires">Accessoires</a></li>
    <li><a href="logout.php" class="btn-logout">Deconnexion</a></li>
  </ul>
</nav>

<div class="main-container">
  <div class="page-header">
    <h2>Mon Panier</h2>
    <p><?=count($_SESSION['panier'])?> article(s) — <?=$nb_plantes?> plante(s) et <?=$nb_accessoires?> accessoire(s)</p>
  </div>

  <?php if($success): ?>
  <div class="success-box">
    <h3>Commande confirmee !</h3>
    <p>Votre commande a ete enregistree avec succes. Les vendeurs vont traiter vos commandes dans les meilleurs delais.</p>
    <a href="particulier.php" class="btn btn-primary" style="margin-top:.5rem;display:inline-block;">Retour au catalogue</a>
  </div>

  <?php elseif(empty($_SESSION['panier'])): ?>
  <div class="empty-state">
    <p>Votre panier est vide.</p>
    <a href="particulier.php#catalogue" class="btn btn-primary" style="margin-top:1rem;display:inline-block;">Voir le catalogue</a>
  </div>

  <?php else: ?>
  <?php if(!empty($errors)): ?>
  <ul class="error-list"><?php foreach($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach;?></ul>
  <?php endif; ?>

  <div class="panier-layout">
    <div>
      <form method="POST">
        <?php $has_p=false; foreach($_SESSION['panier'] as $key=>$item): if($item['type']!=='plante') continue; if(!$has_p){echo '<div class="section-sep">Plantes</div>';$has_p=true;} ?>
        <div class="panier-item">
          <div class="panier-img">
            <?php if($item['image']): ?>
              <img src="<?=htmlspecialchars($item['image'])?>" alt="<?=htmlspecialchars($item['nom'])?>"
                onerror="this.parentNode.innerHTML='<div class=panier-img-ph><svg viewBox=\'0 0 24 24\'><path d=\'M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z\'/></svg></div>'">
            <?php else: ?>
              <div class="panier-img-ph"><svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z"/></svg></div>
            <?php endif; ?>
          </div>
          <div class="panier-info">
            <span class="type-badge type-plante">Plante</span>
            <h4><?=htmlspecialchars($item['nom'])?></h4>
            <span class="boutique-badge"><?=htmlspecialchars($item['boutique'])?></span>
            <p><?=htmlspecialchars($item['sci'])?></p>
            <div class="panier-prix"><?=number_format($item['prix'],2)?> DT / unite</div>
          </div>
          <input type="number" name="qte[<?=$key?>]" value="<?=$item['qte']?>" min="1" max="<?=$item['stock']?>" class="qte-input">
          <a href="panier.php?del=<?=urlencode($key)?>" class="del-btn" onclick="return confirm('Retirer ?')">Retirer</a>
        </div>
        <?php endforeach; ?>

        <?php $has_a=false; foreach($_SESSION['panier'] as $key=>$item): if($item['type']!=='accessoire') continue; if(!$has_a){echo '<div class="section-sep">Accessoires</div>';$has_a=true;} ?>
        <div class="panier-item">
          <div class="panier-img">
            <div class="panier-img-ph"><svg viewBox="0 0 24 24" style="width:28px;height:28px;fill:#3b82f6;opacity:.7;"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.36C18 2.06 15.96 0 13.5 0c-1.5 0-2.78.78-3.5 1.94C9.28.78 8 0 6.5 0 4.04 0 2 2.06 2 4.64c0 .48.11.92.18 1.36H0v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6zm-6.5-4c.83 0 1.5.67 1.5 1.5S14.33 5 13.5 5 12 4.33 12 3.5 12.67 2 13.5 2zM6.5 2C7.33 2 8 2.67 8 3.5S7.33 5 6.5 5 5 4.33 5 3.5 5.67 2 6.5 2zM11 18H9v-5H7l4-7 4 7h-2v5h-2z"/></svg></div>
          </div>
          <div class="panier-info">
            <span class="type-badge type-accessoire">Accessoire</span>
            <h4><?=htmlspecialchars($item['nom'])?></h4>
            <span class="boutique-badge"><?=htmlspecialchars($item['boutique'])?></span>
            <p><?=htmlspecialchars($item['sci'])?></p>
            <div class="panier-prix"><?=number_format($item['prix'],2)?> DT / unite</div>
          </div>
          <input type="number" name="qte[<?=$key?>]" value="<?=$item['qte']?>" min="1" max="<?=$item['stock']?>" class="qte-input">
          <a href="panier.php?del=<?=urlencode($key)?>" class="del-btn" onclick="return confirm('Retirer ?')">Retirer</a>
        </div>
        <?php endforeach; ?>

        <button type="submit" name="update_qte" class="btn btn-outline" style="margin-top:.5rem;">Mettre a jour les quantites</button>
      </form>
    </div>

    <div>
      <div class="summary-card" style="margin-bottom:1.5rem;">
        <div class="summary-title">Recapitulatif</div>
        <?php foreach($_SESSION['panier'] as $item): ?>
        <div class="summary-line">
          <span><?=htmlspecialchars($item['nom'])?> x<?=$item['qte']?><br><small style="color:#9ca3af"><?=htmlspecialchars($item['boutique'])?></small></span>
          <span><?=number_format($item['prix']*$item['qte'],2)?> DT</span>
        </div>
        <?php endforeach; ?>
        <div class="summary-line"><span>Livraison</span><span>Gratuite</span></div>
        <div class="summary-total"><span>Total</span><span><?=number_format($total_general,2)?> DT</span></div>
      </div>

      <div class="summary-card">
        <div class="pay-title">Paiement</div>
        <div class="card-preview">
          <div class="cp-bank">PlantApp Secure Payment</div>
          <div class="cp-num" id="previewNum">**** **** **** ****</div>
          <div class="cp-bottom"><span id="previewName">Nom du titulaire</span><span id="previewExp">MM/AA</span></div>
        </div>
        <form method="POST">
          <div class="pay-section">Carte bancaire</div>
          <div class="fg"><label>Nom du titulaire</label>
            <input type="text" name="nom_carte" placeholder="Ex: SAMI BEN ALI" required oninput="document.getElementById('previewName').textContent=this.value||'Nom du titulaire'">
          </div>
          <div class="fg"><label>Numero de carte</label>
            <input type="text" name="numero_carte" placeholder="1234 5678 9012 3456" maxlength="19" required
              oninput="this.value=this.value.replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim().substring(0,19);let n=this.value.replace(/\s/g,'');document.getElementById('previewNum').textContent=n.length>0?n.replace(/(.{4})/g,'$1 ').trim():'**** **** **** ****';">
          </div>
          <div class="card-row">
            <div class="fg"><label>Date expiration</label>
              <input type="text" name="date_expiration" placeholder="MM/AA" maxlength="5" required
                oninput="this.value=this.value.replace(/[^0-9\/]/g,'');if(this.value.length===2&&!this.value.includes('/'))this.value+='/';document.getElementById('previewExp').textContent=this.value||'MM/AA';">
            </div>
            <div class="fg"><label>CVV</label>
              <input type="password" name="cvv" placeholder="***" maxlength="3" required oninput="this.value=this.value.replace(/\D/g,'')">
            </div>
          </div>
          <div class="pay-section">Adresse de livraison</div>
          <div class="fg"><label>Adresse</label><input type="text" name="adresse" placeholder="Ex: 5 Avenue Bourguiba" required></div>
          <div class="card-row">
            <div class="fg"><label>Ville</label><input type="text" name="ville" placeholder="Ex: Tunis" required></div>
            <div class="fg"><label>Code postal</label><input type="text" name="code_postal" placeholder="1001" required></div>
          </div>
          <button type="submit" name="commander" class="btn btn-primary btn-full" style="margin-top:1rem;padding:.9rem;">
            Confirmer — <?=number_format($total_general,2)?> DT
          </button>
          <p style="font-size:.73rem;color:#9ca3af;text-align:center;margin-top:.6rem;">Paiement securise et protege.</p>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
