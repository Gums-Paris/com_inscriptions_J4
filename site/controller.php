<?php
/**
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * 
 */
class InscriptionsController extends JControllerLegacy
{

  public function display($cachable = false, $urlparams = false) {
// forcer la vue par défaut si aucun paramètre renseignés dans l'url

    InscriptionsHelper::checkUser();


    if ( ! JRequest::getCmd( 'view' ) ) {

      JRequest::setVar('view', 'readhesion' );
      JRequest::setVar('layout', 'default' );


    }

    parent::display();

  }

}
