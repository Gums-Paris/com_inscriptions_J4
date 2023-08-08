<?php
defined('_JEXEC') or die;
?>
<h4>Création d'un adhérent FFCAM</h4>
<h4>
<?php 
$date_naissance = InscriptionsHelper::dateMyHt($this->data->cb_datenaissance);
echo $this->data->firstname.' '.$this->data->lastname
  .' - Né(e) le : '.$date_naissance;  
?>
</h4>
<form action="<?php echo JRoute::_('index.php?option=com_inscriptions&view=ffcam&layout=new'); ?>" 
  method="post" name="adminForm" id="adminForm" class="form-validate">
<?php 
$disabled = false;

if (count($this->doublonsFfcam)>0) {

  $disabled = true;

  echo '<div>Attention '.count($this->doublonsFfcam).' adhérent(s) avec le même n° Ffcam existe déjà</div>';
  echo  '<ul>';
  foreach($this->doublonsFfcam as $d) {

    echo '<li>'.$d->firstname.' '.$d->lastname.' *'.$d->cb_nocaf.
    '- Né(e) le : '.InscriptionsHelper::dateMyHt($d->cb_datenaissance).
    '<button style="margin-left: 20px;" 
        type="button" onclick="submitbutton(\'ffcam.replace\')" 
        class="btn btn-primary btn-lg" id="new">
      Mettre à jour
     </button></li>';
     echo '<input type="hidden" name="jform[id]" value="'.$d->id.'">';
  }
  echo  '</ul>';

} elseif (count($this->doublons)>0) {

  echo '<div>Attention '.count($this->doublons).' adhérent(s) avec le même nom existe déjà</div>';
  echo  '<ul>';
  foreach($this->doublons as $d) {

    echo '<li>'.$d->firstname.' '.$d->lastname.' *'.$d->cb_nocaf.'- Né le : '.$d->cb_datenaissance.
    '<button style="margin-left: 20px;" 
        type="button" onclick="submitbutton(\'ffcam.replace\')" 
        class="btn btn-primary btn-lg" id="new">
      Mettre à jour
     </button></li>';
     echo '<input type="hidden" name="jform[id]" value="'.$d->id.'">';
  }
  echo  '</ul>';    
  
}

if ((int) $this->doublonsEmail->id >0) {
  echo '<div>Attention adresse mail '.$d->email.' déjà affectée à '
  .$this->doublonsEmail->id. ' - ' .$this->doublonsEmail->name.'</div>';
  echo '<br>Contacter l\'administrateur';
// A FAIRE = ajouter formulaire de saisie d'une nouvelle adresse mail  
  
  $disabled = true;
}



?>
<div style="margin-left: 50px; margin-top: 10px; margin-bottom: 10px;" >
<?php 
  if (! $disabled ) {
?>
  <button type="button" onclick="submitbutton('ffcam.nouveau')" class="btn btn-primary btn-lg" id="new">
  Créer nouvel adhérent
  </button>
<?php   
  }  
?>  
  <button type="button" onclick="submitbutton('ffcam.cancel')" class="btn btn-default btn-lg" 
    id="cancel" style="margin-left: 20px;">
  Annuler
  </button>
</div> 	        

<input type="hidden" name="jform[no]" value="<?php echo $this->data->no_adherent_complet;  ?>" />  
<input type="hidden" name="option" value="com_inscriptions" />
<input type="hidden" name="controller" value="ffcam" />
<input type="hidden" name="task" value="" />  
<?php echo JHtml::_('form.token'); ?> 

</form>
<script language="javascript" type="text/javascript">
</script>
