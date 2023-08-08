<?php // no direct access
defined('_JEXEC') or die('Restricted access'); 
JHtml::_('behavior.formvalidation');

?>                                               
<div>
Si vous disposez d'un identifiant / mot de passe actif, connectez vous... (en bas à gauche de cet écran)
</div>
<div>
Si vous n'avez pas été adhérent ces deux dernières années et que vous souhaitez vous inscrire en ligne, commencez par enregistrer votre adresse mail
</div>
<form action="<?php echo JRoute::_('index.php?option=com_inscriptions&view=nouveau&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="contact-form" class="form-validate">

<?php 
echo $this->form->renderField('prenom');
echo $this->form->renderField('nom');
echo $this->form->renderField('email');
// display de captcha field in your form
$captcha = JCaptcha::getInstance('recaptcha', array('namespace' => 'anything'));
echo $captcha->display('recaptcha', 'recaptcha');

// check recaptcha answer (return true or false)
$captcha = JCaptcha::getInstance('recaptcha', array('namespace' => 'anything'));
$answer = $captcha->checkAnswer('anything');

echo JHtml::_('form.token'); 
?>

</form>