<?php 
  defined('_JEXEC') or die('Restricted access');
	JHTML::_('behavior.modal');
	JHTML::_('behavior.tooltip');
use \Joomla\CMS\Factory;
	
  // Chemin vers le répertoire actuel
  $ch_js = str_replace( $_SERVER['DOCUMENT_ROOT'], "", dirname(__FILE__));
  $document = Factory::getDocument();  
  // Ajout du javascript associé au formulaire 
  //$document->addScript( $ch_js.DS.'form.js');
  include($_SERVER['DOCUMENT_ROOT']."/includes/payzen/function.php");  
    
?>
<h1 class="componentheading">Enregistrement empreinte carte bancaire</h1>

<div style="margin-bottom: 10px;">
<span class="liste_titre">Caution pour location de matériel</span>
</div>

<div style="margin-left: 20px;">
<table cellspacing="1" cellpadding="2" border="0" style="line-height: 20px;">
  <tr>
    <td style="width: 100px;">Montant</td>
    <td style="width: 200px; text-align: right; font-weight: bold;"><?php echo $this->montant;?> €</td>
    <td></td>
  </tr>
  <tr>
    <td>Date de validité : </td>
    <td style="text-align: right; font-weight: bold;"><?php echo $this->date;?></td>    
    <td></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="text-align: right; font-weight: bold;">ou date d'échéance de la carte</td>    
    <td></td>
  </tr>

</table>
<br />

<?php 

$field = array();
$field["vads_amount"] = round($this->montant,2)*100;  
$field["vads_capture_delay"] = 365;
$field["vads_validation_mode"] = 1;
$field["vads_order_id"] = $this->data->id; 
$field["vads_cust_id"] = 'caution';
$field["vads_cust_name"] = str_replace("'", " ", $this->data->name);
$field["vads_cust_email"] = $this->data->email;
$field["vads_url_return"] = "http://".$_SERVER["SERVER_NAME"].JRoute::_('index.php?option=com_inscriptions&amp;view=caution&amp;layout=aprespaiement', false);
$field["vads_return_mode"] = "GET"; 

$return = JRoute::_('index.php?option=com_inscriptions&view=caution&task=annuler&controller=caution', false);
$form = get_formHtml_request2($field, 0, $return );

echo $form;

?>
<div style="margin: 25px;">
Le système est sécurisé par le prestataire bancaire <a href="http://www.payzen.eu/mentions/" target="_blank">Payzen</a>,  
à aucun moment GUMS n'a connaissance de ton n° de CB 
<div>
</div>
