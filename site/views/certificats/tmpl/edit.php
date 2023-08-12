<?php
defined('_JEXEC') or die;
use \Joomla\CMS\Factory;
?>
<h4>Vérification Certificat Médical</h4>
<h4 style="display: inline-block; margin-top: 0px; margin-right: 20px;"><?php echo $this->data->name; ?></h4>
<div style="display: inline-block;"><?php 
if ($this->data->cb_dateinscription > '0000-00-00') {
  echo ' - inscrit le '.Factory::getDate($this->data->cb_dateinscription)->format("d-m-Y");
} else {  
  echo ' - non inscrit à ce jour';
}
echo ' - <a href="mailto:'.$this->data->email.'">'.$this->data->email.'</a>';   
?></div>  

<form action="<?php echo JRoute::_('index.php?option=com_inscriptions&view=certificats&layout=edit'); ?>" 
  method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal ">

<div class="control-group">
<div class="control-label">
<label for="lend_out">Date</label></div>
<div class="controls">
<?php echo JHtml::_("calendar", $this->data->cb_certificat_date, "jform[cb_certificat_date]", 
    "cb_certificat_date", '%d-%m-%Y');?>
</div>
</div>



  <div style="margin-left: 50px; margin-top: 10px; margin-bottom: 10px;" >
<?php
if ($this->liste==0) { 
?>  
	  <button type="button" onclick="submitbutton('certificats.save')" 
      class="btn btn-primary btn-lg" id="certificats.save">
    Valider
    </button> 
<?php
} 
?>  
    <button type="button" onclick="submitbutton('certificats.suivant')" 
      class="btn btn-success btn-lg" id="certificats.suivant" style="margin-left: 20px;">
    Suivant
    </button>
    <button type="button" onclick="submitbutton('certificats.cancel')" 
      class="btn btn-default btn-lg" id="certificats.cancel" style="margin-left: 20px;">
    Annuler
    </button>
  </div> 	        
</fieldset>

<input type="hidden" name="jform[id]" value="<?php echo $this->data->id;  ?>" />  
<input type="hidden" name="jform[retour]" value="<?php echo $this->retour;  ?>" />  
<input type="hidden" name="option" value="com_inscriptions" />
<input type="hidden" name="task" value="" />  
<?php echo JHtml::_('form.token'); ?> 

</form>

<?php 
$file = $this->path_images.$this->data->id.'/'.$this->data->cb_certificat_file;
//$lien = JURI::root(false, JRoute::_('index.php?option=com_inscriptions&view=certificats&format=pdf')); 
$lien = JRoute::_('index.php?option=com_inscriptions&view=certificats&format=pdf');
?>
<iframe style="padding:0;margin:0;border:0"  
  src="<?php echo $lien . "&f=".$file; ?>"
  noresize="noresize" 
  frameborder="0" 
  border="0" 
  cellspacing="0" 
  scrolling="yes" 
  width="100%" 
  height="600px;" 
  marginwidth="0" 
  marginheight="0" 
  style = "z-index: 9600;">
</iframe> 




<script language="javascript" type="text/javascript">
</script>

<?php 
//echo var_dump($this->data); exit;
?>
