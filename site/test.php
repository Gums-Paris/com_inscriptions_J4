<?php

exit;
define('_JEXEC', 1);
define('JPATH_BASE', '/home/gumspari/www');
define('JPATH_COMPONENT', '/home/gumspari/www/components/com_inscriptions');
require_once(JPATH_BASE.'/includes/defines.php');
require_once(JPATH_BASE.'/includes/framework.php');

JLoader::register('MyJMail', JPATH_COMPONENT . '/helpers/myjmail.php');

$mail = new MyJMail();
echo '<pre>'; print_r($mail);echo '</pre>';