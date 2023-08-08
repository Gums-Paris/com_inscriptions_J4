<?php // no direct access
defined('_JEXEC') or die('Restricted access'); 
?>
<h3>Liste certificats <?php if ($this->liste==0) {echo 'non validés';}?></h3>
<ul>
<?php
foreach ($this->data as $r) {
  $lien = JRoute::_('index.php?option=com_inscriptions&view=certificats&layout=edit&id='
            .$r->id);
  echo '<li>
    <a href="'.$lien.'">'
    .$r->name.'</a></li>';
}          
?>
</ul>
<br><br>
<h3>Adhérents licenciés FFCAM sans certificats ( <?php echo count($this->sans);?> )</h3>

<ul>
<?php
foreach ($this->sans as $r) {
  echo '<li><div style="display: inline-block; width: 200px;">'    
    .$r->name.'</div><div style="display: inline-block; width: 150px;">'    
    .$r->cb_dateinscription
    . '</li>';
}          
?>
