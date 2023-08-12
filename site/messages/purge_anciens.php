<?php
exit;
use \Joomla\CMS\Factory;

// Purge des anciens adhérents
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


// Suppression des adhérents n'ayant pas souscrit d'adhésion depuis .. $annee_limite

$annee_limite = 2017;
$derniere_archive = 2022;

$champs = array();
$liens = array();
$sommes = array();

for($a=$derniere_archive; $a>$annee_limite; $a--) {
  $champs[] = "a".$a.".cb_adhesion as a".$a;
  $liens[] = "left join `j3x_comprofiler".$a."` as a".$a." on a".$a.".id=actuel.id";
  $sommes[] =  "a".$a.".cb_adhesion";
}

$query = "SELECT actuel.id, actuel.firstname, actuel.lastname, actuel.cb_adhesion, sortie, blog, photo, ". implode(", ", $champs) .
" FROM `j3x_comprofiler` as actuel " . implode (" ", $liens) . 
" left join ( SELECT userid as sortie FROM `j3x_sorties_inscrits` group BY userid ) as sorties on sorties.sortie = actuel.id " .
" left join ( SELECT user_id as blog FROM `j3x_blog_postings` group BY user_id ) as blog on blog.blog = actuel.id " .
" left join ( SELECT owner as photo FROM `j3x_joomgallery` group BY owner ) as photos on photos.photo = actuel.id " .
" where actuel.cb_remarques <> 'xxx' and " . implode("+", $sommes) . "=0"; 

$db->setQuery($query);
$rows = $db->loadObjectList();

$log = array();
$vals = array();


if (count($rows)>0) {

  foreach($rows as $r) {

    if ((int) $r->sortie > 0 or (int) $r->blog > 0 or (int) $r->photo > 0 ) {
      $log[] = $r->id . ' ' . $r->firstname . ' ' . $r->lastname 
      . " => conservé (présent dans : ". ((int) $r->sortie > 0 ? " sorties " : "") 
      . ((int) $r->blog > 0 ? " blog " : "")  
      . ((int) $r->photo > 0 ? " photos " : "") . ")";  
    } else {
      $log[] = $r->id . ' ' . $r->firstname . ' ' . $r->lastname . " => supprimé";
      $vals[] = $r->id;  
    }

  }

  if (count($vals)>0) {

    $liste = implode(", ", $vals);

    $db->setQuery("delete from #__users where id in ( " . $liste . ")" );
    $db->query();
    $db->setQuery("delete from #__comprofiler where user_id in ( " . $liste . ")" );
    $db->query();
    $db->setQuery("delete from #__user_usergroup_map where user_id in ( " . $liste . ")" );
    $db->query();
  
  } else {

    $log[] = "Pas d'adhérent à supprimer";

  }

} else {

  $log[] = "Pas d'adhérent à purger";

} 

echo '<pre>'; print_r($log); echo '</pre>'; exit;

//--- Envoi du log 
/*
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

*/
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








