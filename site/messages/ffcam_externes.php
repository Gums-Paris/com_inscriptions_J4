<?php
use \Joomla\CMS\Factory;

// 
//
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
//error_reporting(-1); 


// Contrôle d'accès par mot de passe dans l'url ou logname (tache cron)
if (isset($_SERVER["PATH"])) {
  $path = $_SERVER["PATH"];
} else {
  $path = "";
}
$mdp = filter_input(INPUT_GET, "mdp");

if ($mdp<>"fixia9" and $path<>"/usr/local/bin:/usr/bin:/bin") {
  echo 'Non autorisé - '.$path;
  exit;
}

//
define('_JEXEC', 1);
define('JPATH_BASE', str_replace("/components/com_inscriptions/messages", "", dirname(__FILE__)));
define('JPATH_COMPONENT', str_replace("messages", "", dirname(__FILE__)));

define('DS', DIRECTORY_SEPARATOR);
require_once(JPATH_BASE.DS.'includes'.DS.'defines.php');
require_once(JPATH_BASE.DS.'includes'.DS.'framework.php');
$mainframe = Factory::getApplication('site');
$lang = Factory::getLanguage();
$lang->load("", JPATH_SITE, "fr-FR", true);
$db         = Factory::getDBO();
$db->_debug = 1;
$query = $db->getQuery(true);

JLoader::register('MyJMail', JPATH_COMPONENT . '/helpers/myjmail.php');


//jimport('joomla.mail.mail');

//--------------------------------------------------------------------------------
//
// On scanne la table #__comprofiler pour récupérer les adhérents à jour de leur 
// inscriptions au GUMS, disposant d'un n°FFCAM d'un autre club et avec 
// le champ non coché
// 
//
$query = "SELECT id, firstname, lastname, cb_nocaf, cb_procffcam, cb_adhesion, cb_dateinscription FROM `#__comprofiler` 
  WHERE (cb_adhesion > '0' or cb_adhesion1 > '0') and cb_nocaf not like '7504%' and cb_nocaf != '' and cb_procffcam = 0 "; 
$db->setQuery($query);
$rows = $db->loadObjectList();

$log = array();
$check = array();


if (count($rows)>0) {

  include_once("/home/gumspari/ffcam/apiffcam.php");

  foreach($rows as $r) {

    $r->cb_nocaf = substr(preg_replace("`[^0-9]`", "", $r->cb_nocaf), 0, 12);

    try {   
      $result = $client->verifierUnAdherent($cnxFfcam, $r->cb_nocaf);
    } catch (Exception $e) {
      $log[] = "L'erreur ".$e->getMessage()." s'est produite pendant l'interrogation du webservice, la vérification n'a pas aboutie";  
      continue;
    }

    if ($result->existe === 1) {

      $prenom = prenom($result->prenom);
      $prenom2 = remove_accents($prenom);
      $prenom3 = remove_accents(($r->firstname));
      $nom = $result->nom;
      $nom2 = remove_accents($result->nom);
      $nom3 = remove_accents(($r->lastname));

      if (($prenom<>$r->firstname and $prenom2<>$r->firstname and $prenom2<>$prenom3) 
      or ($nom<>$r->lastname and $nom2<>$r->lastname and $nom2<>$nom3)) {

        $log[] = "Anomalie nom : ". $result->prenom.' '.$result->nom . ' // ' .$prenom. ' ' .$nom. ' <> ' . $r->firstname .' '. $r->lastname;

      } elseif ($result->inscription<>'0000-00-00') {

        if($r->cb_nocaf <> $result->id) {
          $correct_ffcam = ", cb_nocaf = '".$result->id."'";
        } else {
          $correct_ffcam = "";
        }

        $query = "UPDATE `#__comprofiler` set cb_procffcam=1". $correct_ffcam ." where id = ". $r->id;
        $db->setQuery($query);
        $message = $db->query(); 
        $log[] = $r->firstname . ' ' .$r->lastname . ' - ' . $r->cb_nocaf . ' => Licence FFCAM OK - MAJ : '.$message;

      } else {

        if($r->cb_nocaf <> $result->id) {

          $query = "UPDATE `#__comprofiler` set cb_procffcam=0". $correct_ffcam ." where id = ". $r->id;
          $db->setQuery($query);  
          $message = $db->query(); 

        } 

        $log[] = $r->firstname . ' ' .$r->lastname . ' - ' . $r->cb_nocaf .  ' - Dt_insc:' . $r->cb_dateinscription .  ' => Licence FFCAM Pas à jour';

      }

    } else {

      $log[] = $r->firstname . ' ' .$r->lastname . ' - ' . $r->cb_nocaf . ' => Licence non trouvée dans la base FFCAM';

    }


  }
  

} else {

  $log[] = "Pas d'adhérent FFCAM externes à vérifier";

} 



//--- Envoi du log 

  $email = 'dns@gumsparis.asso.fr';  
  //$email = 'test-ggj07@mail-tester.com'; 
  $sujet = "Log FFCAM externes";
 
  $texte = "<br />Check des licences FFCAM  : <br /><br />". implode("<br />", $log);
  $mail = new MyJMail();
  //$mail->setsender(array("dns@gumsparis.asso.fr", "GUMS Paris"));
  $mail->setSubject($sujet);
  $mail->ClearAddresses();
  $mail->addRecipient($email);
  $mail->MsgHTML($texte);  
  $mail->send();

  echo '<pre>';
  print_r($log);


//  Nettoie les accents 
//
//  
function remove_accents($str)   {
  setlocale(LC_ALL, "en_GB.UTF-8");
  $str = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $str);
  return preg_replace("/['^]([a-z])/ui", '\1', $str);
}

// Remet en forme le prénom
//
// 
function prenom($prenom) {

  $prenom = mb_convert_case($prenom, MB_CASE_TITLE, 'utf-8');
  $prenom = preg_replace_callback('`-(\w)`', function ($matches) {
          return '-'.strtoupper($matches[1]); }, $prenom);   

  return $prenom;  

}








