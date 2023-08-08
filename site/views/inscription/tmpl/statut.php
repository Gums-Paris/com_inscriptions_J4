<?php // no direct access
defined('_JEXEC') or die('Restricted access'); 
$d = $this->data;
$a = $this->AnPrec;
?>                                               
<h3><?php echo $d->firstname." ".$d->lastname; ?></h3>
<div>
<h4>Votre adhésion est :<?php if($d->cb_adhesion>0) {echo '<b> à jour</b>';} else {echo '<b> à renouveller</b>';}  ?></h4>
<?php
echo '<pre>';print_r($d);echo '</pre>';
?>