<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: '.$_SESSION['role'].'.php'); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['login'])) {
    require_once 'db.php';
    $email = trim($_POST['email']??'');
    $mdp   = md5($_POST['mot_de_passe']??'');
    $conn  = getConnection();
    $stmt  = $conn->prepare("SELECT * FROM Utilisateur WHERE email=? AND mot_de_passe=?");
    $stmt->bind_param("ss",$email,$mdp); $stmt->execute();
    $user  = $stmt->get_result()->fetch_assoc();
    if ($user) {
        $_SESSION['user_id']=$user['id_utilisateur'];
        $_SESSION['nom']=$user['prenom'].' '.$user['nom'];
        $_SESSION['role']=$user['role'];
        $conn->close();
        header('Location: '.$user['role'].'.php'); exit();
    } else { $error='Email ou mot de passe incorrect.'; $conn->close(); }
}
$page = $_GET['page']??'login';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>PlantApp</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',sans-serif;min-height:100vh;display:flex;background:#f5f9f6;}
.left{flex:1;position:relative;overflow:hidden;}
.left-bg{position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=1400&q=85')center/cover no-repeat;transition:transform 8s ease;}
.left-overlay{position:absolute;inset:0;background:linear-gradient(160deg,rgba(10,35,20,.75),rgba(45,106,79,.45));}
.left-content{position:relative;z-index:2;height:100%;display:flex;flex-direction:column;justify-content:center;align-items:flex-start;padding:4rem 3.5rem;color:white;}
.brand-icon{width:60px;height:60px;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.3);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem;backdrop-filter:blur(10px);}
.brand-icon svg{width:30px;height:30px;fill:white;}
.left-content h1{font-family:'Playfair Display',serif;font-size:3.6rem;font-weight:700;line-height:1.15;margin-bottom:1.2rem;}
.left-content p{font-size:1rem;line-height:1.8;opacity:.88;max-width:370px;margin-bottom:2.5rem;}
.feat{display:flex;flex-direction:column;gap:.8rem;}
.feat-item{display:flex;align-items:center;gap:.85rem;padding:.7rem 1rem;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:10px;backdrop-filter:blur(8px);font-size:.88rem;}
.dot{width:7px;height:7px;border-radius:50%;background:#4ade80;box-shadow:0 0 8px #4ade80;flex-shrink:0;}
.right{width:480px;min-height:100vh;background:white;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:3rem 2.5rem;box-shadow:-4px 0 30px rgba(0,0,0,.08);}
.box{width:100%;max-width:380px;}
.tabs{display:flex;background:#f3f4f6;border-radius:10px;padding:4px;margin-bottom:2rem;}
.tab{flex:1;padding:.65rem;border:none;background:transparent;border-radius:8px;font-size:.88rem;font-weight:500;cursor:pointer;color:#6b7280;transition:all .2s;font-family:'Inter',sans-serif;}
.tab.active{background:white;color:#1a3a2a;font-weight:600;box-shadow:0 2px 8px rgba(0,0,0,.1);}
.ftitle{font-family:'Playfair Display',serif;font-size:1.85rem;color:#1a3a2a;margin-bottom:.35rem;}
.fsub{color:#9ca3af;font-size:.86rem;margin-bottom:1.7rem;}
.err{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:.8rem 1rem;border-radius:9px;margin-bottom:1.2rem;font-size:.87rem;}
.fg{margin-bottom:1.05rem;}
.fg label{display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.04em;}
.fg input,.fg select{width:100%;padding:.78rem .95rem;border:1.5px solid #e5e7eb;border-radius:9px;font-size:.9rem;color:#1f2937;background:#fafafa;outline:none;transition:all .2s;font-family:'Inter',sans-serif;}
.fg input:focus,.fg select:focus{border-color:#2d6a4f;box-shadow:0 0 0 3px rgba(45,106,79,.1);background:white;}
.roles{display:flex;gap:.55rem;margin-bottom:1.15rem;}
.rbtn{flex:1;padding:.65rem .4rem;border:1.5px solid #e5e7eb;border-radius:9px;background:#fafafa;cursor:pointer;text-align:center;transition:all .2s;font-family:'Inter',sans-serif;}
.rbtn .ri{font-size:1.2rem;display:block;margin-bottom:.25rem;}
.rbtn .rn{font-size:.76rem;font-weight:600;color:#4b5563;}
.rbtn.sel{border-color:#2d6a4f;background:#f0faf4;box-shadow:0 0 0 3px rgba(45,106,79,.1);}
.rbtn.sel .rn{color:#1a3a2a;}
.xf{display:none;}.xf.show{display:block;}
.submit{width:100%;padding:.88rem;background:linear-gradient(135deg,#2d6a4f,#1a3a2a);color:white;border:none;border-radius:9px;font-size:.93rem;font-weight:600;cursor:pointer;transition:all .3s;font-family:'Inter',sans-serif;margin-top:.5rem;}
.submit:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(26,58,42,.35);}
.panel{display:none;}.panel.active{display:block;}
@media(max-width:860px){.left{display:none;}.right{width:100%;}}
</style>
</head>
<body>
<div class="left">
  <div class="left-bg"></div>
  <div class="left-overlay"></div>
  <div class="left-content">
    <div class="brand-icon"><svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2z"/></svg></div>
    <h1>PlantApp</h1>
    <p>La plateforme intelligente de gestion et de selection de plantes d'interieur pour tous les profils.</p>
    <div class="feat">
      <div class="feat-item"><span class="dot"></span>Recommandations personnalisees par espace</div>
      <div class="feat-item"><span class="dot"></span>Catalogue complet avec informations botaniques</div>
      <div class="feat-item"><span class="dot"></span>Achat en ligne avec paiement securise</div>
    </div>
  </div>
</div>
<div class="right">
  <div class="box">
    <div class="tabs">
      <button class="tab <?=$page!=='register'?'active':''?>" onclick="show('login')">Se connecter</button>
      <button class="tab <?=$page==='register'?'active':''?>" onclick="show('register')">Creer un compte</button>
    </div>
    <!-- LOGIN -->
    <div class="panel <?=$page!=='register'?'active':''?>" id="p-login">
      <h2 class="ftitle">Connexion</h2>
      <p class="fsub">Accedez a votre espace personnel</p>
      <?php if($error): ?><div class="err"><?=htmlspecialchars($error)?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="login" value="1">
        <div class="fg"><label>Email</label><input type="email" name="email" placeholder="exemple@email.com" required></div>
        <div class="fg"><label>Mot de passe</label><input type="password" name="mot_de_passe" placeholder="Votre mot de passe" required></div>
        <button type="submit" class="submit">Se connecter</button>
      </form>
    </div>
    <!-- REGISTER -->
    <div class="panel <?=$page==='register'?'active':''?>" id="p-register">
      <h2 class="ftitle">Creer un compte</h2>
      <p class="fsub">Choisissez votre profil et inscrivez-vous</p>
      <form method="POST" action="register.php">
        <div class="fg"><label>Nom</label><input type="text" name="nom" placeholder="Votre nom" required></div>
        <div class="fg"><label>Prenom</label><input type="text" name="prenom" placeholder="Votre prenom" required></div>
        <div class="fg"><label>Email</label><input type="email" name="email" placeholder="votre@email.com" required></div>
        <label style="font-size:.78rem;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.55rem;display:block;">Type de compte</label>
        <div class="roles">
          <div class="rbtn sel" onclick="selRole('particulier',this)"><span class="ri">&#127807;</span><span class="rn">Particulier</span></div>
          <div class="rbtn" onclick="selRole('botaniste',this)"><span class="ri">&#128300;</span><span class="rn">Botaniste</span></div>
          <div class="rbtn" onclick="selRole('vendeur',this)"><span class="ri">&#127978;</span><span class="rn">Vendeur</span></div>
        </div>
        <input type="hidden" name="role" id="roleInput" value="particulier">
        <div class="xf show" id="f-particulier">
          <div class="fg"><label>Localisation</label><input type="text" name="localisation" placeholder="Ex: Tunis, Sfax..."></div>
          <div class="fg"><label>Niveau d'experience</label><select name="niveau_experience"><option value="debutant">Debutant</option><option value="intermediaire">Intermediaire</option><option value="expert">Expert</option></select></div>
        </div>
        <div class="xf" id="f-botaniste">
          <div class="fg"><label>Specialite</label><input type="text" name="specialite" placeholder="Ex: Botanique tropicale"></div>
          <div class="fg"><label>Institution</label><input type="text" name="institution" placeholder="Ex: ENSIT Tunis"></div>
        </div>
        <div class="xf" id="f-vendeur">
          <div class="fg"><label>Nom de la boutique</label><input type="text" name="nom_boutique" placeholder="Ex: Plantes et Co"></div>
          <div class="fg"><label>Telephone</label><input type="text" name="telephone" placeholder="Ex: +216 71 000 000"></div>
        </div>
        <div class="fg"><label>Mot de passe</label><input type="password" name="mot_de_passe" placeholder="Choisissez un mot de passe" required></div>
        <button type="submit" class="submit">Creer mon compte</button>
      </form>
    </div>
  </div>
</div>
<script>
function show(p){
  document.getElementById('p-login').classList.toggle('active',p==='login');
  document.getElementById('p-register').classList.toggle('active',p==='register');
  document.querySelectorAll('.tab').forEach((b,i)=>b.classList.toggle('active',(p==='login'&&i===0)||(p==='register'&&i===1)));
}
function selRole(r,el){
  document.querySelectorAll('.rbtn').forEach(b=>b.classList.remove('sel'));
  el.classList.add('sel');
  document.getElementById('roleInput').value=r;
  ['particulier','botaniste','vendeur'].forEach(x=>{
    const f=document.getElementById('f-'+x);
    if(f)f.classList.toggle('show',x===r);
  });
}
</script>
</body>
</html>
