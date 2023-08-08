<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

defined('_JEXEC') or die;

if(!defined('DS')){
   define('DS',DIRECTORY_SEPARATOR);
}

JLoader::register('InscriptionsHelper', JPATH_COMPONENT . '/helpers/inscriptions.php');
JLoader::register('MyJMail', JPATH_COMPONENT . '/helpers/myjmail.php');


$controller = JControllerLegacy::getInstance('Inscriptions');
$controller->execute(JFactory::getApplication()->input->get('task'));

$controller->redirect();


/*

$controller = JControllerLegacy::getInstance('Inscriptions');

$input = JFactory::getApplication()->input;

if ($controller = $input->getWord('controller', '') ) {
	require_once (JPATH_COMPONENT.DS.'controllers'.DS.$controller.'.php');
   // Create the controller
   $classname	= 'InscriptionsController'.$controller;
   $controller = new $classname();
}
 
$controller->execute($input->getCmd('task'));

$controller->redirect();
*/
?> 
