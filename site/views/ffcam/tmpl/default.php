<?php // no direct access
defined('_JEXEC') or die('Restricted access'); 
use \Joomla\CMS\Factory;
?>
<h4>Statut de la synchro fichier FFCAM</h4>
<div>
<?php echo $this->statut; ?>
</div>
<div style="margin-top: 10px;">
<?php echo $this->comparaison[0]; ?> adhérents à <?php echo $this->lien_modifier; ?>modifier</a> 
<br><?php echo $this->comparaison[1]; ?> adhérents à <?php echo $this->lien_ajouter; ?>créer</a>
<br><?php echo $this->comparaison[2]; ?> adhérents à supprimer
<?php
  if ($this->comparaison[3]>0) {  
  
   $erreurs = Factory::getApplication()->getUserState( "ffcam.erreurs_synchro");
  
    echo '<br>Attention = nouvel adhérent avec adresse mail en doublon';        
    foreach($erreurs as $k) {                
      echo '<li>'.$k.'</li>';
    }
    echo 'Contactez l\'administrateur';        
  
  } 
   
?> 
<br><br><?php echo $this->compare_tout; ?>Contrôle global adhésions</a> 
</div> 

<?php
$app =Factory::getApplication();
$a_modifier2 = $app->getUserState("ffcam.a_modifier2");
echo '<div style="font-size: 10px; margin-top: 30px;">';
if (count($a_modifier2)>0) {
  echo 'A modifier <ul>';
  foreach($a_modifier2 as $e) {
    echo '<li style="line-height: 12px">'.$e.'</li>';
  }
  echo '</ul>';
}

$a_ajouter2 = $app->getUserState("ffcam.a_ajouter2");
if (count($a_ajouter2)>0) {
  echo 'A créer <ul>';
  foreach($a_ajouter2 as $e) {
    echo '<li style="line-height: 12px">'.$e.'</li>';
  }
  echo '</ul>';
}
echo '</div>';

?>
