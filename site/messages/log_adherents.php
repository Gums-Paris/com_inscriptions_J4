<?php
// Contrôle périodique des modifs faites sur la base adhérent
//
// On compare les tables #__users et #__comprofiler par rapport à leur copie
// faite lors du dernier contrôle
// Pour simplifier on utilise la vue users qui relie les deux bases
//
//
// Contrôle d'accès par mot de passe dans l'url ou logname (tache cron)

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

if (isset($_SERVER["PATH"])) {
  $path = $_SERVER["PATH"];
} else {
  $path = "";
}
$mdp = filter_input(INPUT_GET, "mdp", FILTER_SANITIZE_STRING);

if ($mdp<>"fixia9"  and $path<>"/usr/local/bin:/usr/bin:/bin") {
  echo 'Non autorisé - '.$path;
  exit;
}
//
define('_JEXEC', 1);
define('JPATH_BASE', str_replace("/components/com_inscriptions/messages", "", __DIR__));

require_once(JPATH_BASE.'/includes/defines.php');
require_once(JPATH_BASE.'/includes/framework.php');
require_once(JPATH_BASE.'/includes/myjmail/myjmail.php');

$app    = JFactory::getApplication('site');
$db     = JFactory::getDBO();


//
//

// 
// Contrôle que nombre de cols dans #__comprofiler_log est bien le même que dans #__comprofiler
//
$query = "SELECT * FROM `#__comprofiler`";
$db->setQuery($query);
$nb_champs1 = count($db->loadAssoc());
$query = "SELECT * FROM `#__comprofiler_log`";
$db->setQuery($query);
$nb_champs2 = count($db->loadAssoc());

if($nb_champs1 <> $nb_champs2) {
  $msg = "Erreur : " . $nb_champs1 . " champs dans comprofiler - " 
      . $nb_champs2 . " champs dans comprofiler_log";
  mail_log($msg, " - Erreur");
  echo $msg;
  exit;
}



$l = "";
$l2 = "";
// 
// Contrôle des enregistrements supprimés
//
$query = "SELECT a.id FROM `#__comprofiler_log` as a left join #__comprofiler as b on a.id=b.id where b.id is NULL";
$db->setQuery($query);
$rows = $db->loadObjectList();
if (count($rows) > 0) {
  $l .= '<br><b>** '.count($rows).' enregistrements supprimés de la base</b>';
  //echo '<pre>';print_r($rows);echo '</pre>'; exit;
  foreach ($rows as $r) {
    $l .= '<br>'.user_info($r->id, "_log");
  }
} else {
    $l .= '<br>** Pas d\'enregistrement supprimé';
}
//
// Contrôle des enregistrements ajoutés
//
$query = "SELECT a.id FROM `#__comprofiler` as a left join #__comprofiler_log as b on a.id=b.id where b.id is NULL";
//$query = "SELECT a.id FROM `#__users` as a left join #__users_log as b on a.id=b.id where b.id is NULL";
$db->setQuery($query);
$rows = $db->loadObjectList();
if (count($rows) > 0) { 
  $l .= '<br><br><b>** '.count($rows).' enregistrements ajoutés à la base</b> ';  
  foreach ($rows as $r) {
    $l .= '<br>'.user_info($r->id);
  }
} else {
    $l .= '<br>** Pas d\'enregistrement ajouté';
}
//
// Certains champs ne sont pas à prendre en compte
//
$col_exclues = array(
  "id",
  "hits",
  "message_last_sent",
  "message_number_sent",
  "cb_crampon1",
  "lastvisitDate",
  "params"  
);


//
// 
//
$query = "SELECT * FROM `#__users` as u 
          LEFT JOIN `#__comprofiler` as c ON u.id=c.user_id";
$db->setQuery($query);
$rows  = $db->loadAssocList('user_id');

$query = "SELECT * FROM `#__users_log` as u 
          LEFT JOIN `#__comprofiler_log` as c ON u.id=c.user_id";                   
$db->setQuery($query);
$rows2 = $db->loadAssocList('user_id');

if (count($rows) > 0) {

  // On calcule la longueur maxi du libellé des champs (pour mise en forme)
  $lib_max = 0;  
  $r = current($rows);
  foreach ($r as $key => $col) {
    // longueur maxi libellé champ
    if (strlen($key) > $lib_max) {
      $lib_max = strlen($key);
    }
  }

  // On balaye chaque enregistrement des tables  
  // et on compare avec l'enregistrement correspondant 
  // dans les tables log
  
  foreach ($rows as $u => $r) {

    $r2 = $rows2[$u];  //  enregistrement dans les tables log
    $l3 = "";
    foreach($r as $k=>$e)  {
      
      if (! in_array($k, $col_exclues)) {

        // champ modifié
        if ($e <> $r2[$k]) {                        
          if ($k=="password") {
            $l3 .= '<br>'.str_replace("*", "&nbsp;", str_pad($k, $lib_max, "*"))
            .' : ******* => *******';
          } else {
            if ( !(($e=="0" or $e == "0000-00-00") and is_null($r2[$k])) ) {
              $l3 .= '<br>'.str_replace("*", "&nbsp;", str_pad($k, $lib_max, "*"))
              .' : '.$r2[$k].' => '.$e;                        
            } 
          }                          
        }                      
      }
    }
    
    
    if ($l3<>"") {
      $l2 .= '<br><br><b>'.$r["name"].'</b> ('.$r["username"].')';
      $l2 .= $l3; 
    }   
  }
}
if (strlen($l2) > 0) {
  $l .= '<br><br><b>** Enregistrements modifiés</b>'.$l2.'<br>';
}
if (strlen($l) == 0) {
  $l .= 'Aucune modification';
}
//
// Recherche de la date du dernier contrôle
//
$query = "select date_format(max(date_log),'%d/%m/%Y - %H:%i') from #__log_adherents";
$db->setQuery($query);
$der_modif = $db->loadResult();
//
// Insertion du rapport dans la table log
//
$query = "insert into #__log_adherents (date_log, modifs) values (now(), '".$db->escape($l)."')"; 
$db->setQuery($query);
$result = $db->query();


//
// Mise à jour des deux tables témoins
//



$query = "truncate #__users_log"; 
$db->setQuery($query);
$result = $db->query();
$query = "insert into #__users_log select * from #__users"; 
$db->setQuery($query);
$result = $db->query();

$query = "truncate #__comprofiler_log"; 
$db->setQuery($query);
$result = $db->query();
$query = "insert into #__comprofiler_log select * from #__comprofiler"; 
$db->setQuery($query);
$result = $db->query();


//
// Envoi du rapport par mail
//

$l = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
* {	font-family: "Courier New", Courier, monospace; font-size: 12px;}
</style>
</head><body>'.$l;

$l = "Modifications depuis le ".$der_modif.'<br><br>'.$l;
//
//
//$destinataires = array ("benoit@dhalluin.com", "sylvain.doussot@gmail.com");

if ($_GET["mdp"]=="fixia9") {
echo $l;
echo '<br>fin';
}

mail_log($l);

//

function mail_log($msg, $subject = "") {

  $destinataires = array ("benoit@dhalluin.com");

  $mail = new MyJMail();
  $mail->setSubject("GUMS Paris - log adhérents " . $subject);
  $mail->ClearAddresses();
  $mail->addRecipient($destinataires);
  $mail->MsgHTML($msg);
  $mail->send();

}







function user_info($id, $ext = "")
{
  $db = JFactory::getDBO();
  $query = "SELECT u.id, `firstname` , `lastname` , username, email, `cb_dateinscription`
            FROM `#__users".$ext."` as u LEFT JOIN `#__comprofiler".$ext."` as c ON u.id=c.user_id   
            WHERE u.id =".$id;
  $db->setQuery($query);  
  $row = $db->loadRow();
  echo $id;
  echo '<pre>'; var_dump($row); echo '</pre>'; exit;

  if (count($row)==0) {
    return false;
  } else {
    return implode(" - ", $row);
  }
}
/*

drop table #__comprofiler_log
create table #__comprofiler_log like #__comprofiler

create or replace view users as select a.*,b.name,b.username,b.email,b.password,b.usertype,b.block,b.gid,b.registerDate,b.params 
FROM `#__comprofiler` AS a, #__users AS b 
WHERE a.user_id = b.id

create or replace view users_log as SELECT a.*,b.name,b.username,b.email,b.password,b.usertype,b.block,b.gid,b.registerDate,b.params 
FROM `#__comprofiler_log` AS a, #__users_log AS b 
WHERE a.user_id = b.id
*/
?>
