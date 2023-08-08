<?php
if($_SERVER["HTTPS"] != "on")
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}
ini_set('display_errors',1);
error_reporting(E_ALL);
include_once("/home/gumspari/cipher.php");

$file = filter_input(INPUT_POST, "file", FILTER_SANITIZE_STRING);
if ($file=="") {
  $file = dirname(__FILE__)."/mailinglist.php";
}
$file_mdp = filter_input(INPUT_POST, "file_mdp", FILTER_SANITIZE_STRING);
if ($file_mdp=="") {
  $file_mdp = "api_soap";
}

$mdp = filter_input(INPUT_POST, "mdp", FILTER_SANITIZE_STRING);


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr-fr" lang="fr-fr" >
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <style>
  label {
    display: inline-block;
    line-height: 32px;
    margin-right: 10px;
    text-align: right;
    width: 150px;    
  }
  </style>
</head>
<body>
<form action="mdp_crypt.php" method="post">
<label>Script à protéger</label><input type="text" name="file" value="<?php echo $file; ?>" style="width: 500px;"><br />
<label>Nom du fichier mdp</label><input type="text" name="file_mdp" value="<?php echo $file_mdp; ?>" style="width: 100px;"><br />
<label for "mdp">Mot de passe</label><input type="password" name="mdp" value="<?php echo $mdp; ?>" style="width: 100px;"><br /><br />
<input type="submit" name="submit" value="Mettre à jour" style="margin-left: 50px;">
</form>
<br /><br />
<?php
if ($mdp<>"") {
  crypte($mdp, $file_mdp, $file);
}
?>
