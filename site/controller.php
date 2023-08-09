<?php
/**
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use \Joomla\CMS\Factory;

/**
 * 
 */
class InscriptionsController extends JControllerLegacy
{

  public function display($cachable = false, $urlparams = false) {
// forcer la vue par défaut si aucun paramètre renseignés dans l'url

    InscriptionsHelper::checkUser();
    
	$jinput = Factory::getApplication()->input;

 //   if ( ! JRequest::getCmd( 'view' ) ) {
	if ( ! $jinput->getCmd('view')) {

//      JRequest::setVar('view', 'readhesion' );
//      JRequest::setVar('layout', 'default' );
      $jinput->set('view', 'readhesion' );
      $jinput->set('layout', 'default' );

    }

    parent::display();

  }

}
