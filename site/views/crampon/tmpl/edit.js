function cale_montant() {
    crampon =  jQuery("input[type='radio'][name='jform[cb_crampon]']:checked").val();
    m = parseFloat(crampon);
    x = m.toFixed(2) + ' €';
    jQuery('span#montant').text(x)
} 
function submitbutton(pressbutton)
{
	var form = document.adminForm;
  submitform( pressbutton );
}
