<?php // no direct access
//JHTML::_('behavior.modal');
defined('_JEXEC') or die('Restricted access'); 
use \Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('jquery.framework');

$script = str_replace( JPATH_ROOT, "", dirname(__FILE__)) . '/mailing.js';
JFactory::getDocument()->addScript($script); 

?>

<h3>Mes abonnements aux listes de diffusion</h3>
<div>
<?php


echo '<input type="hidden" id="userid" value="'.$this->userid.'">';
echo '<input type="hidden" id="url" value="'. $_SERVER["REQUEST_URI"] .'">';

echo '<hr><h5 style="color: #aa0000;">Mes adresses mail</h5>';
echo '<ul>';
  
foreach($this->adresses as $a) {

/*
    [id] => 514
    [userid] => 62
    [email] => benoit@dhalluin.com
    [nom] => Benoit D'HALLUIN
    [principal] => 1
    [date] => 2020-12-06 01:25:24
*/  

  echo '<li>';
  echo '<div style="display: inline-block; width: 330px;">'.$a->email.'</div>';
  if ($a->principal) {
    echo '<div style="display: inline-block;">de base</div>';
  } else {
    echo '<div style="display: inline-block;">';
    echo '<button id="adr_'.$a->id.'" class="adrsup">Supprimer</a>';    
    echo '</div>';
  }
  echo '</li>';

}


echo '<li>';
echo '<div style="display: inline-block;  width: 330px;">
<input type="text" id="ajout" style="width: 310px; margin-top: 8px;">
</div>';
echo '<div style="display: inline-block;">';
echo '<button id="ajouter">Ajouter</a>';    
echo '</div>';
echo '</li>';
echo '</ul>';
echo '</div>';

echo '<hr><div><h5 style="color: #aa0000;">Mes abonnements aux listes</h5>';

$listes_auto = array();

foreach($this->listes as $l) {

  if ($l->formulaire) {

    echo '<div>';
    echo 'Liste <b>'.$l->alias.'</b> : ';
    echo '<i>' . $l->titre . '</i>' ;
    echo '</div>';
  
    echo '<ul>';
    foreach($this->adresses as $a) {
      echo '<li>';
      echo '<div style="display: inline-block;  width: 330px;">';
      echo $a->email;
      echo '</div>';
      if($this->listes1[$l->id][$a->id]) {
        $checked = "checked";
      } else {
        $checked = "";
      }
      echo '<div style="display: inline-block;  width: 20px;">';
      echo '<input type="checkbox" class="cba" id="cb_'.$a->id.'_'.$l->id.'" '.$checked.'>'; 
      echo '</div>';

      echo '</li>';
    }
    echo '</ul>';

  } else {
    
    foreach($this->adresses as $a) {
      if($this->listes1[$l->id][$a->id]) {
        $l->email = $a->email;
        $listes_auto[] = $l;
        
      }
    }

  }  

}

if (count($listes_auto)) {
  echo '<hr><h5 style="color: #aa0000;">Mes abonnements aux listes automatiques</h5><ul>';
  foreach($listes_auto as $l) {
    echo '<li>'.$l->alias.'</li>';
  }
  echo '</ul>';

}

  
?>

</div>

<div id="message" style="margin-top: 20px; margin-left: 0px; margin-right: 40px; clear: both; height: auto; padding: 10px;"
 class="">
<?php
  echo implode('<br>', $this->msg);
?>
</textarea> 

