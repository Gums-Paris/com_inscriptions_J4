<?php
defined('_JEXEC') or die;
?>
<h4>Mise à jour d'un adhérent FFCAM</h4>
<h4><?php 
echo $this->data->firstname.' '.$this->data->lastname;
echo ' - No adhérent : '.$this->data->no_adherent_complet;
echo ' - Tarif : '.$this->data->categorie;
if($this->data->assurance_personne) { echo ' +ass.';}
if($this->data->inscrit_web) {echo ' - Web';}
?></h4>
<?php if ($this->data->lastupdatedate > '0000-00-00 00:00:00') { ?>
<div>Dernière modification de la fiche GUMS : 
<?php echo date("d-m-Y", strtotime($this->data->lastupdatedate)); ?></div>
<?php } ?>
<form action="<?php echo JRoute::_('index.php?option=com_inscriptions&view=ffcam&layout=edit'); ?>" 
  method="post" name="adminForm" id="adminForm" class="form-validate">
<table class="table">
<tr>
<th>Champ</th>
<th>Base GUMS</th>
<th>Base FFCAM</th>
<th width="5%">Modifier</th>
</tr>
<?php      
foreach($this->tableau as $t) {
 if ($t->inscription==1) {
   $checked = " checked";
 } else {
   $checked = "";
 }
 // Si adresse mail dans ffcam en doublon
 // 
 $tooltip = "";
 if ($t->doublon !== false) {
  $checked = " disabled";    
  $tooltip = '<span class="hasTooltip" title="" data-original-title="Adresse en doublon avec '.
  $t->doublon.'">
  <img src="/images/M_images/con_info2.png"></span>';     
 } 

?>
<tr>
<td><?php echo $this->titrechamps[$t->champ];?></td>
<td><i><?php echo $t->old ;?></i></td>
<td>
<?php echo $t->new . $tooltip ;?></td>
<td><input type="checkbox" name="jform[modif][<?php echo $t->champ; ?>]" 
<?php echo $checked ; ?>>
<input type="hidden" name="jform[value][<?php echo $t->champ; ?>]"  
    value="<?php echo $t->new; ?>">
</td>
</tr>
<?php
}
?>
</table>

  <div style="margin-left: 50px; margin-top: 10px; margin-bottom: 10px;" >
  Certificat Médical : 
  <?php   
    $date_certificat = InscriptionsHelper::dateMyHt($this->certificat->cb_certificat_date);
    
    if ($this->certificat->cb_certifmedical == '1') {    
      echo 'Ok => date = '.$date_certificat;
    } elseif ($this->certificat->cb_certificat_file <> "") {
      echo 'Non validé (date = '.$date_certificat.')';
    } else {
      echo 'Non reçu';
    } 
  ?>
  </div>

  <div style="margin-left: 50px; margin-top: 10px; margin-bottom: 10px;" >
	  <button type="button" onclick="submitbutton('save')" 
      class="btn btn-primary btn-lg" id="save">
    Valider
    </button> 
    <button type="button" onclick="submitbutton('later')" 
      class="btn btn-success btn-lg" id="suivant" style="margin-left: 20px;">
    Plus tard
    </button>
    <button type="button" onclick="submitbutton('suivant')" 
      class="btn btn-warning btn-lg" id="suivant" style="margin-left: 20px;">
    Taguer Sans Modif
    </button>
    <button type="button" onclick="submitbutton('cancel')" 
      class="btn btn-default btn-lg" id="cancel" style="margin-left: 20px;">
    Annuler
    </button>
  </div> 	        
</fieldset>


<input type="hidden" name="jform[no]" value="<?php echo $this->data->no_adherent_complet;  ?>" />  
<input type="hidden" name="jform[id]" value="<?php echo $this->data->id;  ?>" />  
<input type="hidden" name="jform[retour]" value="<?php echo $this->retour;  ?>" />  
<input type="hidden" name="option" value="com_inscriptions" />
<input type="hidden" name="controller" value="ffcam" />
<input type="hidden" name="task" value="" />  
<?php echo JHtml::_('form.token'); ?> 

</form>
<script language="javascript" type="text/javascript">
</script>
