<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role']!=='particulier') {
    header('Location: index.php'); exit();
}
require_once 'db.php';
$conn = getConnection();
$uid  = (int)$_SESSION['user_id'];
$id_p = (int)($_GET['id']??0);
if (!$id_p) { header('Location: particulier.php'); exit(); }

$r = $conn->prepare("SELECT p.*,f.nom_famille,f.description as fam_desc,b.specialite,b.institution,u.nom as bot_nom,u.prenom as bot_prenom FROM Plante p LEFT JOIN Famille f ON f.id_famille=p.id_famille LEFT JOIN Botaniste b ON b.id_utilisateur=p.id_botaniste LEFT JOIN Utilisateur u ON u.id_utilisateur=p.id_botaniste WHERE p.id_plante=?");
$r->bind_param("i",$id_p); $r->execute();
$plante = $r->get_result()->fetch_assoc();
if (!$plante) { header('Location: particulier.php'); exit(); }

$pr = $conn->prepare("SELECT * FROM Pathologie WHERE id_plante=?");
$pr->bind_param("i",$id_p); $pr->execute();
$pathos = $pr->get_result()->fetch_all(MYSQLI_ASSOC);

$bq = $conn->prepare("SELECT c.id_catalogue,c.prix,c.stock,c.variete,c.id_vendeur,v.nom_boutique,v.adresse_boutique,v.telephone,u.nom,u.prenom FROM Catalogue c JOIN Vendeur v ON v.id_utilisateur=c.id_vendeur JOIN Utilisateur u ON u.id_utilisateur=v.id_utilisateur WHERE c.id_plante=? AND c.disponible=1 AND c.stock>0 ORDER BY c.prix ASC");
$bq->bind_param("i",$id_p); $bq->execute();
$boutiques = $bq->get_result()->fetch_all(MYSQLI_ASSOC);

$wl = $conn->prepare("SELECT id_liste FROM ListeSouhaits WHERE id_utilisateur=? AND id_plante=?");
$wl->bind_param("ii",$uid,$id_p); $wl->execute();
$in_wish = $wl->get_result()->num_rows > 0;

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_wish'])) {
    $w=$conn->prepare("INSERT IGNORE INTO ListeSouhaits(id_utilisateur,id_plante) VALUES(?,?)");
    $w->bind_param("ii",$uid,$id_p); $w->execute(); $in_wish=true;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['del_wish'])) {
    $d=$conn->prepare("DELETE FROM ListeSouhaits WHERE id_utilisateur=? AND id_plante=?");
    $d->bind_param("ii",$uid,$id_p); $d->execute(); $in_wish=false;
}
$conn->close();

$panier_count = isset($_SESSION['panier'])?count($_SESSION['panier']):0;

function soin_label($s){ return $s==='facile'?'Facile':($s==='moyen'?'Moyen':'Difficile'); }
function soin_color($s){ return $s==='facile'?'var(--vert-soin)':($s==='moyen'?'var(--jaune-soin)':'var(--rouge-soin)'); }
function arrosage_label($s){ return $s==='quotidien'?'Quotidien':($s==='hebdomadaire'?'Hebdomadaire':'Bimensuel'); }
function lum_label($s){ return $s==='faible'?'Faible':($s==='moyenne'?'Moyenne':'Forte'); }
function hum_label($s){ return $s==='faible'?'Faible':($s==='moyenne'?'Moyenne':'Elevee'); }
function lum_icon($s){
    if($s==='faible')  return '<svg viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 0 18A9 9 0 0 0 12 3zm0 16a7 7 0 1 1 0-14A7 7 0 0 1 12 19zm0-11a4 4 0 0 0 0 8V8z"/></svg>';
    if($s==='moyenne') return '<svg viewBox="0 0 24 24"><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0a.996.996 0 0 0 0-1.41l-1.06-1.06zm1.06-12.37l-1.06 1.06a.996.996 0 0 0 0 1.41c.39.39 1.03.39 1.41 0l1.06-1.06a.996.996 0 0 0 0-1.41.996.996 0 0 0-1.41 0zM7.05 18.36l-1.06 1.06a.996.996 0 0 0 0 1.41c.39.39 1.03.39 1.41 0l1.06-1.06a.996.996 0 0 0 0-1.41.996.996 0 0 0-1.41 0z"/></svg>';
    return '<svg viewBox="0 0 24 24"><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0a.996.996 0 0 0 0-1.41l-1.06-1.06zm1.06-12.37l-1.06 1.06a.996.996 0 0 0 0 1.41c.39.39 1.03.39 1.41 0l1.06-1.06a.996.996 0 0 0 0-1.41.996.996 0 0 0-1.41 0zM7.05 18.36l-1.06 1.06a.996.996 0 0 0 0 1.41c.39.39 1.03.39 1.41 0l1.06-1.06a.996.996 0 0 0 0-1.41.996.996 0 0 0-1.41 0z"/></svg>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=htmlspecialchars($plante['nom_commun'])?> — PlantApp</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
:root{
  --vert:#1a3a2a;--vert-m:#2d6a4f;--vert-c:#52b788;--vert-bg:#f0faf4;--vert-bd:#d1fae5;
  --vert-soin:#166534;--jaune-soin:#854d0e;--rouge-soin:#991b1b;
  --gris:#6b7280;--texte:#1f2937;--blanc:#fff;
}

/* ===== HERO ===== */
.hero{position:relative;height:480px;overflow:hidden;border-radius:0 0 40px 40px;}
.hero-img-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:brightness(.55);}
.hero-img-bg-ph{position:absolute;inset:0;background:linear-gradient(135deg,#1a3a2a,#2d6a4f);}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.7) 0%,rgba(0,0,0,.1) 60%,transparent 100%);}
.hero-content{position:relative;z-index:2;height:100%;display:flex;flex-direction:column;justify-content:flex-end;padding:2.5rem 3rem;}
.hero-breadcrumb{font-size:.8rem;color:rgba(255,255,255,.7);margin-bottom:1rem;}
.hero-breadcrumb a{color:rgba(255,255,255,.7);text-decoration:none;}
.hero-breadcrumb a:hover{color:white;}
.hero-badges{display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem;}
.hero-badge{padding:.3rem .9rem;border-radius:20px;font-size:.75rem;font-weight:600;backdrop-filter:blur(10px);}
.badge-famille{background:rgba(255,255,255,.18);color:white;border:1px solid rgba(255,255,255,.3);}
.badge-toxic{background:rgba(220,38,38,.85);color:white;}
.badge-safe{background:rgba(22,163,74,.85);color:white;}
.hero-title{font-family:'Playfair Display',serif;font-size:3.2rem;font-weight:700;color:white;line-height:1.1;margin-bottom:.4rem;}
.hero-sci{font-family:'Playfair Display',serif;font-style:italic;font-size:1.2rem;color:rgba(255,255,255,.75);margin-bottom:1.5rem;}
.hero-actions{display:flex;gap:.8rem;flex-wrap:wrap;}
.btn-hero-primary{padding:.8rem 2rem;background:linear-gradient(135deg,#2d6a4f,#1a3a2a);color:white;border:none;border-radius:10px;font-size:.92rem;font-weight:600;cursor:pointer;transition:all .3s;font-family:'Inter',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;}
.btn-hero-primary:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.3);}
.btn-hero-outline{padding:.8rem 1.6rem;background:rgba(255,255,255,.15);color:white;border:1.5px solid rgba(255,255,255,.4);border-radius:10px;font-size:.92rem;font-weight:600;cursor:pointer;transition:all .3s;font-family:'Inter',sans-serif;backdrop-filter:blur(8px);}
.btn-hero-outline:hover{background:rgba(255,255,255,.25);}
.btn-hero-outline.active{background:rgba(239,68,68,.25);border-color:rgba(239,68,68,.6);}

/* ===== LAYOUT PRINCIPAL ===== */
.page-wrap{max-width:1200px;margin:0 auto;padding:3rem 2rem;}
.content-grid{display:grid;grid-template-columns:1fr 380px;gap:2.5rem;align-items:start;}

/* ===== SCORE CARDS ===== */
.score-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2.5rem;}
.score-card{background:white;border-radius:14px;padding:1.3rem 1rem;text-align:center;box-shadow:0 2px 12px rgba(26,58,42,.08);border:1px solid #e5e7eb;transition:transform .2s;}
.score-card:hover{transform:translateY(-3px);}
.score-icon{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .7rem;flex-shrink:0;}
.score-icon svg{width:22px;height:22px;}
.score-label{font-size:.72rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem;}
.score-value{font-size:.95rem;font-weight:700;color:var(--texte);}

/* ===== SECTIONS ===== */
.section{background:white;border-radius:16px;padding:2rem;box-shadow:0 2px 12px rgba(26,58,42,.07);border:1px solid #e5e7eb;margin-bottom:1.5rem;}
.section-head{display:flex;align-items:center;gap:.8rem;margin-bottom:1.4rem;padding-bottom:1rem;border-bottom:2px solid var(--vert-bg);}
.section-head-icon{width:38px;height:38px;background:var(--vert-bg);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.section-head-icon svg{width:20px;height:20px;fill:var(--vert-m);}
.section-title{font-family:'Playfair Display',serif;font-size:1.25rem;color:var(--vert);font-weight:600;}
.desc-text{font-size:.95rem;color:#374151;line-height:1.85;}

/* ===== CARACTERISTIQUES ===== */
.chars-table{width:100%;border-collapse:collapse;}
.chars-table tr{border-bottom:1px solid #f3f4f6;}
.chars-table tr:last-child{border:none;}
.chars-table td{padding:.75rem .5rem;font-size:.9rem;}
.chars-table td:first-child{color:#6b7280;font-weight:500;width:50%;display:flex;align-items:center;gap:.6rem;}
.chars-table td:first-child svg{width:16px;height:16px;fill:#9ca3af;flex-shrink:0;}
.chars-table td:last-child{font-weight:600;color:var(--texte);}
.chars-table tr:hover td{background:#fafafa;}

/* Indicateur niveau -->barre -->*/
.level-bar{display:flex;gap:3px;margin-top:4px;}
.level-dot{width:10px;height:10px;border-radius:50%;background:#e5e7eb;}
.level-dot.active{background:var(--vert-m);}
.level-dot.hard{background:#ef4444;}
.level-dot.medium{background:#f59e0b;}

/* ===== TEMP RANGE ===== */
.temp-range{display:flex;align-items:center;gap:.8rem;margin-top:.3rem;}
.temp-bar{flex:1;height:6px;background:#e5e7eb;border-radius:3px;position:relative;overflow:hidden;}
.temp-fill{position:absolute;left:0;top:0;height:100%;background:linear-gradient(90deg,#3b82f6,#10b981,#f59e0b,#ef4444);border-radius:3px;}
.temp-min{font-size:.75rem;color:#3b82f6;font-weight:600;}
.temp-max{font-size:.75rem;color:#ef4444;font-weight:600;}

/* ===== PATHOLOGIES ===== */
.patho-item{border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;margin-bottom:.9rem;}
.patho-header-btn{width:100%;padding:1rem 1.2rem;background:#fafafa;border:none;display:flex;align-items:center;justify-content:space-between;cursor:pointer;font-family:'Inter',sans-serif;text-align:left;}
.patho-name-wrap{display:flex;align-items:center;gap:.7rem;}
.patho-dot{width:8px;height:8px;border-radius:50%;background:#ef4444;flex-shrink:0;}
.patho-name-text{font-size:.9rem;font-weight:600;color:var(--texte);}
.patho-chevron{width:16px;height:16px;fill:#9ca3af;transition:transform .2s;}
.patho-body{padding:1.2rem;background:white;display:none;border-top:1px solid #f3f4f6;}
.patho-body.open{display:block;}
.patho-row{margin-bottom:.9rem;}
.patho-row:last-child{margin:0;}
.patho-row-label{font-size:.73rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem;}
.patho-row-text{font-size:.88rem;color:#374151;line-height:1.65;}
.symptome-chip{display:inline-block;padding:.25rem .7rem;background:#fef2f2;border:1px solid #fca5a5;border-radius:20px;font-size:.78rem;color:#dc2626;margin:.15rem;}
.traitement-chip{display:inline-block;padding:.25rem .7rem;background:#f0fdf4;border:1px solid #86efac;border-radius:20px;font-size:.78rem;color:#166534;margin:.15rem;}

/* ===== SIDEBAR BOUTIQUES ===== */
.sidebar-card{background:white;border-radius:16px;padding:1.8rem;box-shadow:0 2px 12px rgba(26,58,42,.07);border:1px solid #e5e7eb;position:sticky;top:90px;}
.sidebar-title{font-family:'Playfair Display',serif;font-size:1.2rem;color:var(--vert);margin-bottom:.4rem;}
.sidebar-sub{font-size:.82rem;color:#9ca3af;margin-bottom:1.4rem;}
.boutique-item{border:1.5px solid #e5e7eb;border-radius:12px;padding:1.2rem;margin-bottom:.9rem;transition:all .2s;cursor:pointer;}
.boutique-item:hover{border-color:var(--vert-m);box-shadow:0 4px 14px rgba(26,58,42,.1);}
.boutique-item:last-child{margin:0;}
.b-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.6rem;}
.b-name{font-weight:700;color:var(--vert);font-size:.92rem;}
.b-price{font-size:1.25rem;font-weight:700;color:var(--vert-m);}
.b-addr{font-size:.78rem;color:#9ca3af;margin-bottom:.2rem;}
.b-tel{font-size:.78rem;color:#9ca3af;margin-bottom:.5rem;}
.b-meta{display:flex;align-items:center;justify-content:space-between;margin-bottom:.8rem;}
.b-stock{font-size:.75rem;color:#6b7280;}
.b-variete{font-size:.75rem;background:var(--vert-bg);color:var(--vert-m);border:1px solid var(--vert-bd);border-radius:20px;padding:2px 8px;}
.btn-add{width:100%;padding:.7rem;background:linear-gradient(135deg,var(--vert-m),var(--vert));color:white;border:none;border-radius:9px;font-size:.88rem;font-weight:600;cursor:pointer;transition:all .3s;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;}
.btn-add:hover{transform:translateY(-1px);box-shadow:0 5px 16px rgba(26,58,42,.3);}
.no-stock{background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:1rem;text-align:center;color:#854d0e;font-size:.85rem;}

/* ===== BOTANISTE ===== */
.botaniste-card{display:flex;align-items:center;gap:1rem;padding:1.2rem;background:var(--vert-bg);border-radius:12px;border:1px solid var(--vert-bd);}
.bot-avatar{width:50px;height:50px;background:var(--vert-m);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;color:white;flex-shrink:0;}
.bot-name{font-weight:700;color:var(--vert);font-size:.95rem;}
.bot-info{font-size:.8rem;color:#6b7280;margin-top:.15rem;}

/* ===== FAMILLE ===== */
.famille-badge{display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.2rem;background:var(--vert-bg);border:1px solid var(--vert-bd);border-radius:30px;font-size:.85rem;font-weight:600;color:var(--vert-m);}
.famille-badge svg{width:16px;height:16px;fill:var(--vert-m);}

/* ===== CART FLOAT ===== */
.cart-float{position:fixed;bottom:2rem;right:2rem;width:62px;height:62px;background:linear-gradient(135deg,var(--vert-m),var(--vert));border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 24px rgba(26,58,42,.4);text-decoration:none;z-index:999;transition:transform .25s,box-shadow .25s;}
.cart-float:hover{transform:scale(1.1);box-shadow:0 10px 32px rgba(26,58,42,.5);}
.cart-float svg{width:28px;height:28px;fill:white;}
.cart-badge{position:absolute;top:-4px;right:-4px;background:#ef4444;color:white;border-radius:50%;width:22px;height:22px;font-size:.72rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid white;font-family:'Inter',sans-serif;}

@media(max-width:900px){.content-grid{grid-template-columns:1fr;}.score-strip{grid-template-columns:repeat(2,1fr);}.hero-title{font-size:2rem;}.sidebar-card{position:static;}}
@media(max-width:500px){.score-strip{grid-template-columns:1fr 1fr;}.hero-content{padding:1.5rem;}.hero-title{font-size:1.6rem;}}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="particulier.php" class="navbar-brand">
    <div class="logo-icon"><svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z"/></svg></div>
    <div><h1>PlantApp</h1><span>Detail Plante</span></div>
  </a>
  <ul class="navbar-nav">
    <li><a href="particulier.php#catalogue">Catalogue</a></li>
    <li><a href="particulier.php#accessoires">Accessoires</a></li>
    <li><a href="particulier.php#wishlist">Wishlist</a></li>
    <li><a href="panier.php">Panier</a></li>
    <li><a href="logout.php" class="btn-logout">Deconnexion</a></li>
  </ul>
</nav>

<!-- ===== HERO ===== -->
<div class="hero">
  <?php if($plante['image_url']): ?>
    <img src="<?=htmlspecialchars($plante['image_url'])?>" alt="<?=htmlspecialchars($plante['nom_commun'])?>"
         class="hero-img-bg"
         onerror="this.style.display='none';document.querySelector('.hero-img-bg-ph').style.display='block'">
    <div class="hero-img-bg-ph" style="display:none;"></div>
  <?php else: ?>
    <div class="hero-img-bg-ph"></div>
  <?php endif; ?>
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <div class="hero-breadcrumb">
      <a href="particulier.php">Accueil</a> &rsaquo;
      <a href="particulier.php#catalogue">Catalogue</a> &rsaquo;
      <?=htmlspecialchars($plante['nom_commun'])?>
    </div>
    <div class="hero-badges">
      <?php if($plante['nom_famille']): ?>
        <span class="hero-badge badge-famille"><?=htmlspecialchars($plante['nom_famille'])?></span>
      <?php endif; ?>
      <?php if($plante['toxicite']): ?>
        <span class="hero-badge badge-toxic">Toxique pour animaux</span>
      <?php else: ?>
        <span class="hero-badge badge-safe">Non toxique</span>
      <?php endif; ?>
    </div>
    <h1 class="hero-title"><?=htmlspecialchars($plante['nom_commun'])?></h1>
    <p class="hero-sci"><?=htmlspecialchars($plante['nom_scientifique']??'')?></p>
    <div class="hero-actions">
      <?php if(!empty($boutiques)): ?>
        <a href="#boutiques" class="btn-hero-primary">
          <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:white;"><path d="M17 18c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 5.9 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63H19c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0 0 23.43 4H5.21l-.94-2H1zm6 16c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2z"/></svg>
          Acheter maintenant
        </a>
      <?php endif; ?>
      <form method="POST" style="display:inline;">
        <?php if(!$in_wish): ?>
          <button type="submit" name="add_wish" class="btn-hero-outline">Ajouter a la wishlist</button>
        <?php else: ?>
          <button type="submit" name="del_wish" class="btn-hero-outline active">Retirer de la wishlist</button>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>

<div class="page-wrap">

  <!-- SCORE STRIP -->
  <div class="score-strip">
    <div class="score-card">
      <div class="score-icon" style="background:#f0fdf4;">
        <svg viewBox="0 0 24 24" style="fill:#16a34a;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
      </div>
      <div class="score-label">Niveau de soin</div>
      <div class="score-value" style="color:<?=soin_color($plante['niveau_soin'])?>"><?=soin_label($plante['niveau_soin'])?></div>
      <div class="level-bar" style="justify-content:center;margin-top:.4rem;">
        <?php
          $lvl = $plante['niveau_soin']==='facile'?1:($plante['niveau_soin']==='moyen'?2:3);
          $col = $plante['niveau_soin']==='facile'?'active':($plante['niveau_soin']==='moyen'?'medium':'hard');
          for($i=1;$i<=3;$i++) echo '<div class="level-dot '.($i<=$lvl?$col:'').'"></div>';
        ?>
      </div>
    </div>
    <div class="score-card">
      <div class="score-icon" style="background:#eff6ff;">
        <svg viewBox="0 0 24 24" style="fill:#3b82f6;"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
      </div>
      <div class="score-label">Arrosage</div>
      <div class="score-value"><?=arrosage_label($plante['besoin_arrosage'])?></div>
    </div>
    <div class="score-card">
      <div class="score-icon" style="background:#fefce8;">
        <?=lum_icon($plante['besoin_luminosite'])?>
      </div>
      <div class="score-label">Luminosite</div>
      <div class="score-value"><?=lum_label($plante['besoin_luminosite'])?></div>
    </div>
    <div class="score-card">
      <div class="score-icon" style="background:#f0fdf4;">
        <svg viewBox="0 0 24 24" style="fill:#14b8a6;width:22px;height:22px;"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 0-16A8 8 0 0 1 12 20zm-2-8a2 2 0 1 1 4 0 2 2 0 0 1-4 0z"/></svg>
      </div>
      <div class="score-label">Humidite</div>
      <div class="score-value"><?=hum_label($plante['besoin_humidite'])?></div>
    </div>
  </div>

  <div class="content-grid">
    <!-- COLONNE GAUCHE -->
    <div>

      <!-- DESCRIPTION -->
      <div class="section">
        <div class="section-head">
          <div class="section-head-icon"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg></div>
          <h2 class="section-title">Description de la plante</h2>
        </div>
        <p class="desc-text"><?=htmlspecialchars($plante['description']??'Aucune description disponible pour cette plante.')?></p>

        <?php if($plante['nom_famille']): ?>
        <div style="margin-top:1.4rem;">
          <span class="famille-badge">
            <svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z"/></svg>
            Famille : <?=htmlspecialchars($plante['nom_famille'])?>
          </span>
          <?php if($plante['fam_desc']): ?>
            <p style="font-size:.82rem;color:#9ca3af;margin-top:.5rem;font-style:italic;"><?=htmlspecialchars($plante['fam_desc'])?></p>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- CARACTERISTIQUES DETAILLEES -->
      <div class="section">
        <div class="section-head">
          <div class="section-head-icon"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg></div>
          <h2 class="section-title">Caracteristiques botaniques</h2>
        </div>
        <table class="chars-table">
          <tr>
            <td>
              <svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z"/></svg>
              Nom scientifique
            </td>
            <td><em><?=htmlspecialchars($plante['nom_scientifique']??'N/A')?></em></td>
          </tr>
          <tr>
            <td>
              <svg viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
              Besoin en arrosage
            </td>
            <td><?=arrosage_label($plante['besoin_arrosage'])?></td>
          </tr>
          <tr>
            <td>
              <svg viewBox="0 0 24 24"><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1z"/></svg>
              Besoin en luminosite
            </td>
            <td><?=lum_label($plante['besoin_luminosite'])?></td>
          </tr>
          <tr>
            <td>
              <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z"/></svg>
              Besoin en humidite
            </td>
            <td><?=hum_label($plante['besoin_humidite'])?></td>
          </tr>
          <tr>
            <td>
              <svg viewBox="0 0 24 24"><path d="M15 13V5a3 3 0 0 0-6 0v8a5 5 0 1 0 6 0z"/></svg>
              Plage de temperature
            </td>
            <td>
              <div class="temp-range">
                <span class="temp-min"><?=$plante['temperature_min']?>°C</span>
                <div class="temp-bar"><div class="temp-fill" style="width:<?=min(100,max(10,(($plante['temperature_max']-$plante['temperature_min'])/40)*100))?>%"></div></div>
                <span class="temp-max"><?=$plante['temperature_max']?>°C</span>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
              Niveau de soin
            </td>
            <td>
              <span style="color:<?=soin_color($plante['niveau_soin'])?>; padding:3px 12px;border-radius:20px;display:inline-block;font-size:.82rem;font-weight:700;">
                <?=soin_label($plante['niveau_soin'])?>
              </span>
            </td>
          </tr>
          <tr>
            <td>
              <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
              Toxicite
            </td>
            <td>
              <span style="color:<?=$plante['toxicite']?'#dc2626':'#166534'?>;font-weight:600;">
                <?=$plante['toxicite']?'Toxique pour les animaux':'Non toxique'?>
              </span>
            </td>
          </tr>
        </table>
      </div>

      <!-- BOTANISTE -->
      <?php if($plante['bot_nom']): ?>
      <div class="section">
        <div class="section-head">
          <div class="section-head-icon"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg></div>
          <h2 class="section-title">Botaniste responsable</h2>
        </div>
        <div class="botaniste-card">
          <div class="bot-avatar"><?=strtoupper(substr($plante['bot_prenom'],0,1))?></div>
          <div>
            <div class="bot-name"><?=htmlspecialchars($plante['bot_prenom'].' '.$plante['bot_nom'])?></div>
            <div class="bot-info">
              <?php if($plante['specialite']): ?><?=htmlspecialchars($plante['specialite'])?><?php endif; ?>
              <?php if($plante['institution']): ?> — <?=htmlspecialchars($plante['institution'])?><?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- PATHOLOGIES -->
      <?php if(!empty($pathos)): ?>
      <div class="section">
        <div class="section-head">
          <div class="section-head-icon"><svg viewBox="0 0 24 24" style="fill:#ef4444;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></div>
          <h2 class="section-title">Pathologies et Traitements</h2>
        </div>
        <p style="font-size:.85rem;color:#9ca3af;margin-bottom:1.2rem;">Cliquez sur une pathologie pour voir les details.</p>
        <?php foreach($pathos as $i=>$pa): ?>
        <div class="patho-item">
          <button class="patho-header-btn" onclick="togglePatho(<?=$i?>)">
            <div class="patho-name-wrap">
              <span class="patho-dot"></span>
              <span class="patho-name-text"><?=htmlspecialchars($pa['nom_pathologie'])?></span>
            </div>
            <svg class="patho-chevron" id="chev-<?=$i?>" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
          </button>
          <div class="patho-body" id="patho-<?=$i?>">
            <div class="patho-row">
              <div class="patho-row-label">Symptomes</div>
              <div>
                <?php foreach(explode(',',$pa['symptomes']??'') as $s): ?>
                  <span class="symptome-chip"><?=htmlspecialchars(trim($s))?></span>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="patho-row">
              <div class="patho-row-label">Traitement recommande</div>
              <div>
                <?php foreach(explode(',',$pa['traitement']??'') as $t): ?>
                  <span class="traitement-chip"><?=htmlspecialchars(trim($t))?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>

    <!-- COLONNE DROITE — BOUTIQUES -->
    <div id="boutiques">
      <div class="sidebar-card">
        <div class="sidebar-title">Disponible en boutique</div>
        <div class="sidebar-sub"><?=count($boutiques)?> boutique(s) proposent cette plante</div>

        <?php if(empty($boutiques)): ?>
          <div class="no-stock">Cette plante n est pas disponible en boutique pour le moment.</div>
        <?php else: ?>
          <?php foreach($boutiques as $b): ?>
          <div class="boutique-item">
            <div class="b-top">
              <div>
                <div class="b-name"><?=htmlspecialchars($b['nom_boutique'])?></div>
                <?php if($b['adresse_boutique']): ?><div class="b-addr"><?=htmlspecialchars($b['adresse_boutique'])?></div><?php endif; ?>
                <?php if($b['telephone']): ?><div class="b-tel"><?=htmlspecialchars($b['telephone'])?></div><?php endif; ?>
              </div>
              <div class="b-price"><?=number_format($b['prix'],2)?> DT</div>
            </div>
            <div class="b-meta">
              <span class="b-stock"><?=$b['stock']?> unite(s) en stock</span>
              <?php if($b['variete']): ?><span class="b-variete"><?=htmlspecialchars($b['variete'])?></span><?php endif; ?>
            </div>
            <a href="panier.php?add_plante=<?=$id_p?>&vendeur=<?=$b['id_vendeur']?>&prix=<?=$b['prix']?>&stock=<?=$b['stock']?>" class="btn-add">
              <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:white;"><path d="M17 18c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 5.9 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63H19c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0 0 23.43 4H5.21l-.94-2H1zm6 16c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2z"/></svg>
              Ajouter au panier
            </a>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- Wishlist dans sidebar -->
        <div style="margin-top:1.2rem;padding-top:1.2rem;border-top:1px solid #f3f4f6;">
          <form method="POST">
            <?php if(!$in_wish): ?>
              <button type="submit" name="add_wish" class="btn btn-outline btn-full">Ajouter a la wishlist</button>
            <?php else: ?>
              <button type="submit" name="del_wish" class="btn btn-danger btn-full">Retirer de la wishlist</button>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CHARIOT FLOTTANT -->
<a href="panier.php" class="cart-float">
  <svg viewBox="0 0 24 24"><path d="M17 18c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 5.9 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63H19c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0 0 23.43 4H5.21l-.94-2H1zm6 16c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2z"/></svg>
  <?php if($panier_count > 0): ?>
    <span class="cart-badge"><?=$panier_count?></span>
  <?php endif; ?>
</a>

<script>
function togglePatho(i) {
    const body = document.getElementById('patho-'+i);
    const chev  = document.getElementById('chev-'+i);
    const open  = body.classList.toggle('open');
    chev.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
}
</script>
</body>
</html>
