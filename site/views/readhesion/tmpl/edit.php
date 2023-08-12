<?php
defined('_JEXEC') or die;

// Include the component HTML helpers.
//JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
//JHtml::_('behavior.keepalive');
//JHtml::_('formbehavior.chosen', 'select');
//JHtml::_('behavior.formvalidator');

use \Joomla\CMS\Factory;
use \Joomla\CMS\HTML\HTMLHelper;

$app = Factory::getApplication();
$input = $app->input;

	HTMLHelper::_('jquery.framework');
	$script = str_replace( JPATH_ROOT, "", dirname(__FILE__)) . '/edit.js?i2';//.rand();

	Factory::getDocument()->addScript($script);  

?>
<h3>Renouvelement de l'adhésion au GUMS - Saison <?php echo $this->saison;  ?></h3>
<h5><?php echo $this->data->firstname.' '.$this->data->lastname;  ?></h5>
<form action="<?php echo JRoute::_('index.php?option=com_inscriptions&view=readhesion&layout=edit&id=' . 
(int) $this->data->id); ?>" enctype="multipart/form-data" method="post" name="adminForm" id="adminForm" class="form-validate">
		  <fieldset class="adminform">
        <div style="font-size: 14px; line-height: 20px; margin-bottom: 10px;">
          <?php echo $this->titre;  ?>
        </div>
		    <div class="control-group">
			    <div class="controls">
  				   <?php echo $this->form[0];  ?>
          </div>
          <div class="controls">
             <?php if ($this->clubext) {
                  echo $this->form[4];                                 
                } else {
                  echo $this->form[1];
                }                
              ?>
			    </div>
             <?php if ($this->clubext) {                  
                  echo '<div class="controls">'.$this->form[3].'</div>';                                                   
                }                
              ?>
			    </div>
          <div class="controls">
  				   <?php echo $this->form[2];  ?>
			    </div>
          <div class="controls" style=" border: 1px solid #ccc; margin-top: 15px;  
              font-size: 14px; line-height: 20px; padding: 5px 5px 5px 20px; width: 385px;">
  				   <span>Total à payer : </span>
             <span id="montant" style="font-weight: bold;"> €</span>             
			    </div>			
        <div style="margin-left: 50px; margin-top: 10px; margin-bottom: 10px;" >
      	  <button type="button" onclick="submitbutton('readhesion.save')" class="btn btn-primary btn-lg" id="save">
		      Paiement par carte
	        </button> 
  	      <button type="button" onclick="submitbutton('readhesion.cancel')" class="btn btn-default btn-lg" id="cancel" style="margin-left: 20px;">
	  	    Annuler
	        </button>
          </div> 	        
      </fieldset>

    <input type="hidden" name="jform[id]" value="<?php echo $this->data->id;  ?>" />  
    	<input type="hidden" name="option" value="com_inscriptions" />
	
	<input type="hidden" name="task" value="" />  
		<?php echo JHtml::_('form.token'); ?> 

</form>
<script language="javascript" type="text/javascript">
cale_montant();
</script>
