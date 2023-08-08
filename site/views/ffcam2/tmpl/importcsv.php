<?php // no direct access
defined('_JEXEC') or die('Restricted access'); 
?>
<h4>Import CSV FFCAM</h4>
<form action="<?php echo JRoute::_('index.php?option=com_inscriptions&task=ffcam2.importecsv'); ?>" 
    method="post" enctype="multipart/form-data">
<div>
   <label for="file">Sélectionner le fichier à envoyer</label>
   <input type="file" id="file" name="file">
 </div>
 <div>
   <button>Envoyer</button>
 </div>
</form>

<div>
 <?php
 echo '<pre>'; print_r($this->log); echo '</pre>'; 
 ?> 
</div>

