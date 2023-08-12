<?php
defined('_JEXEC') or die;
use \Joomla\CMS\Factory;

// Include the component HTML helpers.
//JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
//JHtml::_('behavior.formvalidation');
//JHtml::_('behavior.keepalive');
//JHtml::_('formbehavior.chosen', 'select');

$app = Factory::getApplication();
$input = $app->input;

$script = str_replace( JPATH_ROOT, "", dirname(__FILE__)) . '/edit.js?i2';//.rand();
Factory::getDocument()->addScript($script);  

?>
<h3>Abonnement au crampon - Saison <?php echo $this->saison;  ?></h3>
<h5><?php echo $this->data->firstname.' '.$this->data->lastname;  ?></h5>
<form action="<?php echo JRoute::_('index.php?option=com_inscriptions&view=crampon&layout=edit&id=' . 
(int) $this->data->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
		  <fieldset class="adminform">
		    <div class="control-group">
			    <div class="controls">
  				   <?php echo $this->form[0];  ?>
          </div>
			    </div>
          <div class="controls" style=" border: 1px solid #ccc; margin-top: 15px;  
              font-size: 14px; line-height: 20px; padding: 5px 5px 5px 20px; width: 385px;">
  				   <span>Total à payer : </span>
             <span id="montant" style="font-weight: bold;"> €</span>             
			    </div>			
        <div style="margin-left: 50px; margin-top: 10px; margin-bottom: 10px;" >
      	  <button type="button" onclick="submitbutton('paiement')" class="btn btn-primary btn-lg" id="save">
		      Paiement par carte
	        </button> 
  	      <button type="button" onclick="submitbutton('cancel')" class="btn btn-default btn-lg" id="cancel" style="margin-left: 20px;">
	  	    Annuler
	        </button>
          </div> 	        
      </fieldset>

    <input type="hidden" name="jform[id]" value="<?php echo $this->data->id;  ?>" />  
    	<input type="hidden" name="option" value="com_inscriptions" />
	<input type="hidden" name="controller" value="crampon" />
  <input type="hidden" name="view" value="crampon" />
	<input type="hidden" name="task" value="" />  
		<?php echo JHtml::_('form.token'); ?> 

</form>
<script language="javascript" type="text/javascript">
cale_montant();
</script>
