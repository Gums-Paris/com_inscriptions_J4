function cale_montant() {
    cotisation = jQuery("input[type='radio'][name='jform[cb_adhesion]']:checked").val(); 
    assurance = jQuery("input[type='radio'][name='jform[cb_assurancegums]']:checked").val();
    crampon =  jQuery("input[type='radio'][name='jform[cb_crampon]']:checked").val();
    m = parseFloat(cotisation) + parseFloat(assurance) + parseFloat(crampon);
    x = m.toFixed(2) + ' €';
    jQuery('span#montant').text(x)
} 
function submitbutton(pressbutton)
{
	var form = document.adminForm;
  submitform( pressbutton );
}
