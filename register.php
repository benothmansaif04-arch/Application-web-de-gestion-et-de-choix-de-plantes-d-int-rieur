<?php
session_start();
require_once 'db.php';
if ($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: index.php');exit();}
$nom=$_POST['nom']??''; $prenom=$_POST['prenom']??'';
$email=$_POST['email']??''; $mdp=md5($_POST['mot_de_passe']??'');
$role=$_POST['role']??'particulier';
$conn=getConnection();
$chk=$conn->prepare("SELECT id_utilisateur FROM Utilisateur WHERE email=?");
$chk->bind_param("s",$email); $chk->execute();
if($chk->get_result()->num_rows>0){header('Location: index.php?page=register&error=email');exit();}
$s=$conn->prepare("INSERT INTO Utilisateur(nom,prenom,email,mot_de_passe,role) VALUES(?,?,?,?,?)");
$s->bind_param("sssss",$nom,$prenom,$email,$mdp,$role); $s->execute();
$id=$conn->insert_id;
if($role==='particulier'){
    $loc=$_POST['localisation']??''; $niv=$_POST['niveau_experience']??'debutant';
    $s2=$conn->prepare("INSERT INTO Particulier(id_utilisateur,localisation,niveau_experience) VALUES(?,?,?)");
    $s2->bind_param("iss",$id,$loc,$niv); $s2->execute();
}elseif($role==='botaniste'){
    $sp=$_POST['specialite']??''; $in=$_POST['institution']??'';
    $s2=$conn->prepare("INSERT INTO Botaniste(id_utilisateur,specialite,institution) VALUES(?,?,?)");
    $s2->bind_param("iss",$id,$sp,$in); $s2->execute();
}elseif($role==='vendeur'){
    $bo=$_POST['nom_boutique']??''; $te=$_POST['telephone']??'';
    $s2=$conn->prepare("INSERT INTO Vendeur(id_utilisateur,nom_boutique,telephone) VALUES(?,?,?)");
    $s2->bind_param("iss",$id,$bo,$te); $s2->execute();
}
$_SESSION['user_id']=$id; $_SESSION['nom']=$prenom.' '.$nom; $_SESSION['role']=$role;
$conn->close();
header('Location: '.$role.'.php'); exit();
?>
