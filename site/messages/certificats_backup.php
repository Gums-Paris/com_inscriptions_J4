<?php
// Contrôle des modifs faites sur la base sorties / car_couchettes
//
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

// Contrôle d'accès par mot de passe dans l'url ou logname (tache cron)
if (isset($_SERVER["PATH"])) {
  $path = $_SERVER["PATH"];
} else {
  $path = "";
}
$mdp = filter_input(INPUT_GET, "mdp", FILTER_SANITIZE_STRING);

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
$mainframe = JFactory::getApplication('site');
$lang = JFactory::getLanguage();
$lang->load("", JPATH_SITE, "fr-FR", true);
$db         = JFactory::getDBO();
$db->_debug = 1;
//


exec("rsync -avu /home/gumspari/www/images/comprofiler/plug_cbfilefield/ /home/gumspari/certificats_backup/", $output, $return);

$message = array();

foreach($output as $o) {

  if(substr($o, 0, 4)=="sent" or substr($o, 0, 5)=="total") {    
    $message[] = $o;
  } else {
    preg_match_all("`^([0-9]{1,5})\/(.*)`", $o, $matches);
    //echo '<pre>'; print_r($matches); echo '</pre>';
    if (isset($matches[2][0]) and $matches[2][0]<>'') {
      
      $qry = "select name from #__users where id = ".(int) $matches[1][0];
      $db->setQuery($qry);
      $name = $db->loadResult();
      if ($name=="") {
        $name = $matches[1][0];
      }

      $message[] = 'Backup du certificat "'.$matches[2][0].'" - adhérent '.$name;
    }

  }
  
}

echo implode("<br>\n", $message); 

exit;


?>
