<?php // no direct access
defined('_JEXEC') or die('Restricted access'); 
?>
<h4>Statut de la synchro fichier FFCAM</h4>
<div>
<?php echo $this->statut; ?>
</div>
<div style="margin-top: 10px;">
<?php echo $this->lien_modifier . $this->nb_a_modifier; ?> adhérents</a> avec problème de synchronisation adhésion 
<br><br><a href="<a href="index.php?option=com_inscriptions&view=ffcam">Retour</a> 
</div>
<div>
<ul>    
<?php 
//echo '<pre>'; print_r($this->a_modifier);echo '</pre>';
foreach($this->a_modifier as $a) {
    $l = '<a href="index.php/ffcam?view=ffcam&layout=edit&no='.$a->no_adherent_complet.'">'.$a->prenom.' '.$a->nom.' - '.$a->no_adherent_complet.'</a>';
    echo '<li style="line-height: 15px">'.$l.'</li>';
}
?>  
</ul>  
</div>    

