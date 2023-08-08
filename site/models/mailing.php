<?php
defined('_JEXEC') or die;

class InscriptionsModelMailing extends JModelAdmin
{

  public  $msg    = array();
  public  $id    = array();
  public  $listes = array();
  private $db2;

  /*
  ------------------------------------------------------------------

  */  
  public function __construct()
  {    

    parent::__construct();

    $user	= JFactory::getUser();
    $app = JFactory::getApplication();


    // Traitement auto
    $auto = $app->input->get('task');

    // Si id dans l'url = admin gérant un utilisateur
    $id = $app->input->getInt('userid', 0);
    
    // Sinon on récupére l'id de l'utilisateur
    if($id==0) {
      $id = (int) $user->id;
    }

    // On vérifie les droits
    if($id != (int) $user->id and $auto != "synchro") {
      InscriptionsHelper::checkUser();
    }            

    $this->userid = $id; 

    $this->connectDb();    // Connexion à la base mysql du VPS
    $this->getListes();    // Liste des listes de diffusion gérées

  }

  /*
  ------------------------------------------------------------------

  */
	public function getItem($pk = NULL)
	{

    $id = $this->userid; 

    $qry  = "select * from `a_adresses` where userid = ?";
    $this->adresses = $this->db2->objList($qry, $id);  

    if (count($this->adresses)==0) {
      $this->msg[] = "Utilisateur absent de la base adresse";
      $this->ajouter($id);
      $qry  = "select * from `a_adresses` where userid = ?";
      $this->adresses = $this->db2->objList($qry, $id);  
    }


    if (count($this->adresses)==0) {
      return false;
    }


    // On crée un tableau $this->listes1 avec un item pour chaque liste gérée
    // si l'utilisateur est abonné à la liste x on aura $this->listes1[x] = true;
    //

    foreach($this->listes as $l) {
      $this->listes1[$l->id] = array();
      foreach($this->adresses as $a) {
        $this->listes1[$l->id][$a->id] = false;
      }
    }


    // On liste les abonnements de l'utilisateur 
    // pour renseigner 


    $qry  = "select a.id as id_adresse, b.id_liste, a.email, a.nom, b.id as id_abonnement, d.list_exclusion  
      from a_adresses as a 
      join a_abonnements as b on b.id_adresse = a.id 
      join a_listes as c on c.id = b.id_liste 
      left join `exclusion_table` as d on d.user_exclusion = a.email and  d.list_exclusion = c.alias
      where a.userid = ?";
    $rows = $this->db2->objList($qry, $id);      

    foreach($rows as $r) {

      // Cas particulier des désabonnements via l'outil sympa d'adresses abonnées via la synchro adhérent
      // On supprime l'adresse de la table exclusion_table et on supprimer l'abonnement dans la table a_abonnements

      if ($r->list_exclusion != null) {

        $qry = "delete from `exclusion_table` where user_exclusion = ? and list_exclusion = ?";
        $this->db2->qry($qry, array($r->email, $r->list_exclusion));
        $qry = "delete from `a_abonnements` where id = ?";
        $this->db2->qry($qry, $r->id_abonnement);

      } else {

        $this->listes1[$r->id_liste][$r->id_adresse] = true;

      }      

    }

    
    return true;
    

	}
  
  /*
  ------------------------------------------------------------------

  */
  

  public function synchroAdherents() {

    // Table temporaire des adresses abonnées aux listes
    $db = JFactory::getDbo();
    $db->setQuery("truncate #__mailinglistes");
    $db->execute();

    $adresses = $this->db2->objList("select * from a_adresses");

    $this->msg[] = "Synchro des adresses mail";

    foreach($adresses as $a) {
      $db->insertObject('#__mailinglistes', $a);
    }      

    $qry = "select u.id, u.email from #__users as u 
            left join #__comprofiler as c on c.user_id = u.id 
            left join #__mailinglistes as m on m.userid = u.id
            where cb_adhesion > 0 
              and m.userid is null 
              and u.email not like '%@gumsparis.asso.fr'"; 
    $db->setQuery($qry);
    $rows = $db->loadObjectList();
    if(count($rows)>0) {
      foreach($rows as $r) {
        $id_adresse  = $this->ajouter($r->id, "", 1);
        $this->abonner($id_adresse, $this->listes["infos"]->id);
        $this->msg[] = "Utilisateur ".$r->id." ".$r->nom." créé dans a_adresses et abonné à infos";
      }
    } else {
      $this->msg[] = "Pas d'adresse à ajouter";
    }

    // Nettoyage des adresses et abonnements dont l'utilisateur a été supprimé dans Joomla
    //
    $qry = "select a.id, a.userid, a.nom, a.email from #__mailinglistes as a 
     left join #__users as u on u.id = a.userid 
     where u.id is null";
     $db->setQuery($qry);
     $rows = $db->loadObjectList();
     if(count($rows)>0) {
      foreach($rows as $r) {

        // Si l'ex-adhérent (supprimé de la base Gums) était abonné aux listes infos et débats on transforme 
        // son type d'abonnement pour le passer de "abonnement lié aux tables a_adresses/a_abonnement" (issues de la base Gums) 
        // à "abonné stardard Sympa" (comme si il s'était abonné lui-même sur listes.gumsparis.asso.fr)

        $qry = "update subscriber_table 
          set include_sources_subscriber = '', included_subscriber = 0, subscribed_subscriber	= 1, 
          inclusion_ext_subscriber	= null, inclusion_label_subscriber = null, inclusion_subscriber = null 
          where user_subscriber = ? and list_subscriber = ? ";
        $response = $this->db2->qry($qry, array($r->email, "infos"));
        if ($response->rowCount()>0) {
          $this->msg[] = "Transformation de l'abonnement à la liste info pour ".$r->userid." ".$r->nom."";  
        }

        $response = $this->db2->qry($qry, array($r->email, "debats"));
        if ($response->rowCount()>0) {
          $this->msg[] = "Transformation de l'abonnement à la liste debats pour ".$r->userid." ".$r->nom."";  
        }
        

        // On supprime son adresse de a_abonnements
        //
        $qry = "delete from a_abonnements where id_adresse = ?";
        $this->db2->qry($qry, $r->id);
        $this->msg[] = "Abonnements de ".$r->userid." ".$r->nom." supprimés";
        // 
        // On supprime son adresse de a_adresses 
        //
        $qry = "delete from a_adresses where id = ?";
        $this->db2->qry($qry, $r->id);
        $this->msg[] = "Adresse ".$r->id." de ".$r->userid." ".$r->nom." supprimée";

      }
    }

    // Recalage des adresses principales dans mailinglistes suite à changement 
    // d'adresse dans la base adhérent
    //
    $qry = "select a.id, a.userid, a.email, u.email as uemail, u.name from #__mailinglistes as a 
      left join #__users as u on u.id = a.userid 
      where a.principal = 1 and a.email <> u.email";
     $db->setQuery($qry);
     $rows = $db->loadObjectList();

     if(count($rows)>0) {
      foreach($rows as $r) {

        // Suppression des doublons éventuels d'adresses secondaires
        // 
        $qry2 = "select id from a_adresses where email = ? and principal = ? and userid = ?";
        $rows2 = $this->db2->objList($qry2, array($r->uemail, 0, $r->userid) );
        if(count($rows2)>0) {
          foreach($rows2 as $r2) {
            $qry3 = "update ignore a_abonnements set id_adresse = ? where id_adresse = ?";
            $this->db2->qry($qry3, array($r->id, $r2->id));
            $qry4 = "delete from a_adresses where id = ?";
            $this->db2->qry($qry4, $r2->id);
            $this->msg[] = "Adresse secondaire ".$r2->id." de ".$r->userid." ".$r->name." supprimée et abonnements recalés";
          }
        }

        $qry = "update a_adresses set email = ?, date = now() where id = ?";
        $this->db2->qry($qry, array($r->uemail, $r->id));
        $this->msg[] = "Adresse principale de ".$r->userid." ".$r->name." modifiée pour " . $r->uemail;


      }
    }




    
    
    // Recalage des désabonnements faits via l'interface Sympa pour des abonnés via le site
    //
    /*
    $qry = "select `exclusion_table` where `user_exclusion` = ?";
    $rows = $this->db2->qry($qry, $email);    
    */




    $db->setQuery("truncate #__mailinglistes");
    $db->execute();

  }



  /*
  ------------------------------------------------------------------

  */
    
  public function ajouter($id_user, $email = "", $principal = 1) {

    $user	= JFactory::getUser($id_user);
    if ($email == "") {
      $email = $user->email;      
    } 

    // On vérifie que l'adresse n'existe pas déjà dans la base a_adresses
    //
    $qry = "select id, userid from a_adresses where email = ?";
    $r = $this->db2->obj($qry, $email);

    if ((int) $r->id > 0) {
      $user2	= JFactory::getUser($r->userid);
      $this->msg[] = "Erreur - Adresse ".$email." existe déjà dans la base adresses pour ".$user2->name;
      return false;
    }


    $qry = "insert into `a_adresses` (`userid`,`email`,`nom` ,`principal`) 
        values (?, ?, ?, ?) ";    
    $data = array($id_user, $email, $user->name, $principal);    
    $r = $this->db2->qry($qry, $data);    
    
    $qry = "select id from a_adresses where userid = ? and email = ?";
    $id_adresse = (int) $this->db2->result($qry, array($id_user, $email));

    if ($id_adresse>0) {
      $this->msg[] = "Adresse ".$email." pour l'utilisateur ".$user->name." ajouté à base adresse";      
    } else {
      $this->msg[] = "Erreur ajout adresse ".$email." pour l'utilisateur ".$user->name;
      return false;
    }

    // On regarde si l'adresse existe dans la table des abonnés de la liste 
    //  critère included à 0 (abonnement "manuel") et reception différent de "nomail"
    
    $qry = "SELECT * FROM `subscriber_table` where `user_subscriber`= ? and `included_subscriber`= ? and `reception_subscriber` <> ?";
    $rows = $this->db2->objList($qry, array($email, 0, "nomail"));
    
    $this->msg[] = "L'adresse ".$email." était-elle présente dans les abonnés ? => " . count($rows);          
    
    foreach($rows as $r) {
      $qry = "delete from `subscriber_table` where 
        robot_subscriber	= ? and user_subscriber	= ? and list_subscriber = ? and subscribed_subscriber = ?";

      $data = array($r->robot_subscriber, $r->user_subscriber, $r->list_subscriber, 1);
      $this->db2->qry($qry, $data);          
      $this->msg[] = "Suppression de ".$r->user_subscriber." de la liste " . $r->list_subscriber;      
      $this->abonner($id_adresse, (int) $this->listes[$r->list_subscriber]->id);
    }

    // On retire l'adresse de la liste des exclusions
    // 
    $qry = "delete FROM `exclusion_table` where `user_exclusion` = ?";
    $rows = $this->db2->qry($qry, $email);    

    // On retourne l'id de l'adresse créée dans a_adresses
    return $id_adresse;
      
  }

  /*
  ------------------------------------------------------------------

  */
      
  public function supprimer($id_adresse) {

    // 
    //
    $qry = "delete from a_adresses where id = ?";
    $r = $this->db2->qry($qry, $id_adresse);

    $qry = "delete from a_abonnements where id_adresse = ?";
    $r = $this->db2->qry($qry, $id_adresse);

    $this->msg[] = $id_adresse ." supprimée  ";
      
  }


/*
------------------------------------------------------------------

*/
    
  public function abonner($id_adresse, $id_liste, $id_user = 0, $email = "") {

    if($id_adresse == 0) {
      $qry = "select from `a_adresses` where userid = ? and email = ?";
      (int) $id_adresse = $this->db2->result($qry, array($id_user, $email));
      if($id_adresse == 0) {
        return false;
      }
    }
    
    $qry = "replace into `a_abonnements` (`id_adresse`,`id_liste`,`maj`) 
        values (?, ?, ?) ";    
    
    $this->db2->qry($qry, array($id_adresse, $id_liste, date("Y-m-d H:i:s")));    

    $this->db2->qry($qry, array($id_adresse, $id_liste, date("Y-m-d H:i:s")));    
  //a.id as id_adresse, b.id_liste, a.email, a.nom, b.id as id_abonnement 
    $qry  = "select a.id, b.email, c.alias 
    from a_abonnements as a 
    left join a_adresses as b on b.id =a.id_adresse
    left join a_listes as c on c.id = a.id_liste
    where a.id_adresse = ? and a.id_liste = ?";
    $row = $this->db2->obj($qry, array($id_adresse, $id_liste));
    
    if ((int) $row->id > 0) {
      $this->msg[] = "Adresse <b>".$row->email."</b> abonnée à la liste <b>".$row->alias."</b>";
      return true;
    } else {
      $this->msg[] = "Erreur abonnement adresse <b>".$row->email."</b> pour la liste <b>".$row->alias."</b>";
      return false;
    }

  }


  public function desabonner($id_adresse, $id_liste) {

    $qry  = "select a.id, b.email, c.alias 
    from a_abonnements as a 
    left join a_adresses as b on b.id =a.id_adresse
    left join a_listes as c on c.id = a.id_liste
    where a.id_adresse = ? and a.id_liste = ?";
    $row = $this->db2->obj($qry, array($id_adresse, $id_liste));

    $qry = "delete from `a_abonnements` where `id_adresse` = ? and `id_liste` = ? ";    
    $this->db2->qry($qry, array($id_adresse, $id_liste));    
    $this->msg[] = "Adresse <b>" . $row->email ."</b> retirée de la liste  <b>". $row->alias . "</b>";

  }



  /*
  ------------------------------------------------------------------

  */
      
  private function getListes() {  

    $qry = "select id, alias, titre, formulaire from `a_listes` order by formulaire desc, id";      
    $this->listes = $this->db2->objListIndex($qry, "alias");        

  }

  
  /*
  ------------------------------------------------------------------

  */
      
  public function synchroPerma(){

    
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select($db->quoteName(array('a.id', 'email', 'name'))) 
      ->from($db->quoteName('#__users', 'a'))
      ->join('LEFT', $db->quoteName('#__comprofiler', 'b') . ' ON ' . $db->quoteName('b.user_id') . ' = ' . $db->quoteName('a.id'))
      ->where($db->quoteName('cb_fonction') . ' LIKE ' . $db->quote('%Permanenci%'))
      ->order($db->quoteName('lastname') . ' ASC');
    $db->setQuery($query);
    $permanenciers = $db->loadObjectList();


    $qry  = "select a.id as id_adresse, b.id_liste, a.email, a.nom, b.id as id_abonnement 
    from a_adresses as a 
    left join a_abonnements as b on b.id_adresse = a.id 
    left join a_listes as c on c.id = id_liste  
    where c.alias = ? ";
    $abonnes = $this->db2->objListIndex($qry, "email" , "perma");      

    $this->msg[] = "Synchro liste perma : " .count($permanenciers). " permanenciers - " .count($abonnes). " abonnés";

    foreach($permanenciers as $p) {

      $adresses[] = $p->email;

      // Est-ce que l'adresse mail du permanencier existe dans la table des abonnés ?

      if(array_key_exists($p->email, $abonnes) == false) {

        // Sinon est-ce que l'adresse mail du permanencier existe dans la table a_adresses ?

        $qry  = "select id, email from a_adresses where email = ?";
        $s = $this->db2->obj($qry, $p->email); 

        if(! ($s->email == $p->email) ) {

          // Si non on l'ajoute à a_adresses

          $this->ajouter($p->id, $p->email, 1);
          $s = $this->db2->obj($qry, $p->email);
          $this->msg[] = "Ajout de ". $p->email. " aux adresses pour " .$p->name;

        }

        // Verif qu'on a bien un id adresse
        if ((int) $s->id == 0 ) {
          echo 'Erreur'; exit;
        }

        // Abonne l'adresse à la liste
        $this->abonner($s->id, $this->listes["perma"]->id, $p->id);
        $this->msg[] = $p->name ." - ". $p->email. " - adresse ". $s->id . " ajouté aux abonnés";

      } else {

        //$this->msg[] = $p->name ." - ". $p->email. " déjà abonné";

      }

    }

    foreach ($abonnes as $a) {

      if (! in_array($a->email, $adresses)) {
        $this->msg[] =  $a->nom .  " - " . $a->email ." à retirer des abonnés";
        $this->desabonner($a->id_adresse, $a->id_liste);      
      }

    }


  }       



  /*
  ------------------------------------------------------------------

  */
      
  public function synchroEscalade(){

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select($db->quoteName(array('a.id', 'email', 'name'))) 
      ->from($db->quoteName('#__users', 'a'))
      ->join('LEFT', $db->quoteName('#__comprofiler', 'b') . ' ON ' . $db->quoteName('b.user_id') . ' = ' . $db->quoteName('a.id'))
      ->where($db->quoteName('cb_escalade') . " <> ''")
      ->where($db->quoteName('cb_escalade') . " IS NOT NULL")
      ->order($db->quoteName('lastname') . ' ASC');
    $db->setQuery($query);
    $encadrants = $db->loadObjectList();


    $qry  = "select a.id as id_adresse, b.id_liste, a.email, a.nom, b.id as id_abonnement 
    from a_adresses as a 
    left join a_abonnements as b on b.id_adresse = a.id 
    left join a_listes as c on c.id = id_liste  
    where c.alias = ? ";
    $abonnes = $this->db2->objListIndex($qry, "email" , "encadrants-escalade");      

    $this->msg[] = "Synchro liste encadrants-escalade : " .count($encadrants). " encadrants - " .count($abonnes). " abonnés";

    foreach($encadrants as $p) {

      $adresses[] = $p->email;

      // Est-ce que l'adresse mail de l'encadrant existe dans la base des abonnés ?

      if(array_key_exists($p->email, $abonnes) == false) {

        // Sinon est-ce que l'adresse mail de l'encadrant existe dans la base a_adresses ?

        $qry  = "select id, email from a_adresses where email = ?";
        $s = $this->db2->obj($qry, $p->email); 

        if(! ($s->email == $p->email) ) {

          // Si non on l'ajoute à a_adresses

          $this->ajouter($p->id, $p->email, 1);
          $s = $this->db2->obj($qry, $p->email);
          $this->msg[] = "Ajout de ". $p->email. " aux adresses pour " .$p->name;

        }

        // Verif qu'on a bien un id adresse
        if ((int) $s->id == 0 ) {
          echo 'Erreur'; exit;
        }

        // Abonne l'adresse à la liste
        $this->abonner($s->id, $this->listes["encadrants-escalade"]->id, $p->id);
        $this->msg[] = $p->name ." - ". $p->email. " - adresse ". $s->id . " ajouté aux abonnés";

      } else {

        //$this->msg[] = $p->name ." - ". $p->email. " déjà abonné";

      }

    }

    foreach ($abonnes as $a) {

      if (! in_array($a->email, $adresses)) {
        $this->msg[] =  $a->nom .  " - " . $a->email ." à retirer des abonnés";
        $this->desabonner($a->id_adresse, $a->id_liste);      
      }

    }  


  }       

  /*
  ------------------------------------------------------------------

  */
      
  public function synchroCd(){

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select($db->quoteName(array('a.id', 'email', 'name'))) 
      ->from($db->quoteName('#__users', 'a'))
      ->join('LEFT', $db->quoteName('#__comprofiler', 'b') . ' ON ' . $db->quoteName('b.user_id') . ' = ' . $db->quoteName('a.id'))
      ->where($db->quoteName('cb_membrecd') . " = 'Oui'")
      ->where($db->quoteName('cb_membrecd') . " IS NOT NULL")
      ->order($db->quoteName('lastname') . ' ASC');
    $db->setQuery($query);
    $encadrants = $db->loadObjectList();


    $qry  = "select a.id as id_adresse, b.id_liste, a.email, a.nom, b.id as id_abonnement 
    from a_adresses as a 
    left join a_abonnements as b on b.id_adresse = a.id 
    left join a_listes as c on c.id = id_liste  
    where c.alias = ? ";
    $abonnes = $this->db2->objListIndex($qry, "email" , "cd");      

    $this->msg[] = "Synchro liste cd : " .count($encadrants). " membres cd - " .count($abonnes). " abonnés";

    foreach($encadrants as $p) {

      $adresses[] = $p->email;

      // Est-ce que l'adresse mail de l'encadrant existe dans la base des abonnés ?

      if(array_key_exists($p->email, $abonnes) == false) {

        // Sinon est-ce que l'adresse mail de l'encadrant existe dans la base a_adresses ?

        $qry  = "select id, email from a_adresses where email = ?";
        $s = $this->db2->obj($qry, $p->email); 

        if(! ($s->email == $p->email) ) {

          // Si non on l'ajoute à a_adresses

          $this->ajouter($p->id, $p->email, 1);
          $s = $this->db2->obj($qry, $p->email);
          $this->msg[] = "Ajout de ". $p->email. " aux adresses pour " .$p->name;

        }

        // Verif qu'on a bien un id adresse
        if ((int) $s->id == 0 ) {
          echo 'Erreur'; exit;
        }

        // Abonne l'adresse à la liste
        $this->abonner($s->id, $this->listes["cd"]->id, $p->id);
        $this->msg[] = $p->name ." - ". $p->email. " - adresse ". $s->id . " ajouté aux abonnés";

      } else {

        //$this->msg[] = $p->name ." - ". $p->email. " déjà abonné";

      }

    }

    foreach ($abonnes as $a) {

      if (! in_array($a->email, $adresses)) {
        $this->msg[] =  $a->nom .  " - " . $a->email ." à retirer des abonnés";
        $this->desabonner($a->id_adresse, $a->id_liste);      
      }

    }  

  }       

  /*
  ------------------------------------------------------------------

  */
  
    
  public function synchroBureau(){

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select($db->quoteName(array('a.id', 'email', 'name'))) 
      ->from($db->quoteName('#__users', 'a'))
      ->join('LEFT', $db->quoteName('#__comprofiler', 'b') . ' ON ' . $db->quoteName('b.user_id') . ' = ' . $db->quoteName('a.id'))
      ->where($db->quoteName('cb_membrebureau') . " = 'Oui'")
      ->where($db->quoteName('cb_membrebureau') . " IS NOT NULL")
      ->order($db->quoteName('lastname') . ' ASC');
    $db->setQuery($query);
    $encadrants = $db->loadObjectList();


    $qry  = "select a.id as id_adresse, b.id_liste, a.email, a.nom, b.id as id_abonnement 
    from a_adresses as a 
    left join a_abonnements as b on b.id_adresse = a.id 
    left join a_listes as c on c.id = id_liste  
    where c.alias = ? ";
    $abonnes = $this->db2->objListIndex($qry, "email" , "bureau");      

    $this->msg[] = "Synchro liste bureau : " .count($encadrants). " membres bureau - " .count($abonnes). " abonnés";

    foreach($encadrants as $p) {

      $adresses[] = $p->email;

      // Est-ce que l'adresse mail de l'encadrant existe dans la base des abonnés ?

      if(array_key_exists($p->email, $abonnes) == false) {

        // Sinon est-ce que l'adresse mail de l'encadrant existe dans la base a_adresses ?

        $qry  = "select id, email from a_adresses where email = ?";
        $s = $this->db2->obj($qry, $p->email); 

        if(! ($s->email == $p->email) ) {

          // Si non on l'ajoute à a_adresses

          $this->ajouter($p->id, $p->email, 1);
          $s = $this->db2->obj($qry, $p->email);
          $this->msg[] = "Ajout de ". $p->email. " aux adresses pour " .$p->name;

        }

        // Verif qu'on a bien un id adresse
        if ((int) $s->id == 0 ) {
          echo 'Erreur'; exit;
        }

        // Abonne l'adresse à la liste
        $this->abonner($s->id, $this->listes["bureau"]->id, $p->id);
        $this->msg[] = $p->name ." - ". $p->email. " - adresse ". $s->id . " ajouté aux abonnés";

      } else {

        //$this->msg[] = $p->name ." - ". $p->email. " déjà abonné";

      }

    }

    foreach ($abonnes as $a) {

      if (! in_array($a->email, $adresses)) {
        $this->msg[] =  $a->nom .  " - " . $a->email ." à retirer des abonnés";
        $this->desabonner($a->id_adresse, $a->id_liste);      
      }

    }  

  }       

  /*
  ------------------------------------------------------------------

  */
    
  public function synchroResCores(){

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select($db->quoteName(array('a.id', 'email', 'name'))) 
      ->from($db->quoteName('#__users', 'a'))
      ->join('LEFT', $db->quoteName('#__comprofiler', 'b') . ' ON ' . $db->quoteName('b.user_id') . ' = ' . $db->quoteName('a.id'))
      ->where($db->quoteName('cb_skirando') . " like '%res'")
      ->where($db->quoteName('cb_skirando') . " IS NOT NULL")
      ->order($db->quoteName('lastname') . ' ASC');
    $db->setQuery($query);
    $encadrants = $db->loadObjectList();


    $qry  = "select a.id as id_adresse, b.id_liste, a.email, a.nom, b.id as id_abonnement 
    from a_adresses as a 
    left join a_abonnements as b on b.id_adresse = a.id 
    left join a_listes as c on c.id = id_liste  
    where c.alias = ? ";
    $abonnes = $this->db2->objListIndex($qry, "email" , "res-cores");      

    $this->msg[] = "Synchro liste res-cores : " .count($encadrants). " encadrants ski - " .count($abonnes). " abonnés";

    foreach($encadrants as $p) {

      $adresses[] = $p->email;

      // Est-ce que l'adresse mail de l'encadrant existe dans la base des abonnés ?

      if(array_key_exists($p->email, $abonnes) == false) {

        // Sinon est-ce que l'adresse mail de l'encadrant existe dans la base a_adresses ?

        $qry  = "select id, email from a_adresses where email = ?";
        $s = $this->db2->obj($qry, $p->email); 

        if(! ($s->email == $p->email) ) {

          // Si non on l'ajoute à a_adresses

          $this->ajouter($p->id, $p->email, 1);
          $s = $this->db2->obj($qry, $p->email);
          $this->msg[] = "Ajout de ". $p->email. " aux adresses pour " .$p->name;

        }

        // Verif qu'on a bien un id adresse
        if ((int) $s->id == 0 ) {
          echo 'Erreur'; exit;
        }

        // Abonne l'adresse à la liste
        $this->abonner($s->id, $this->listes["res-cores"]->id, $p->id);
        $this->msg[] = $p->name ." - ". $p->email. " - adresse ". $s->id . " ajouté aux abonnés";

      } else {

        //$this->msg[] = $p->name ." - ". $p->email. " déjà abonné";

      }
      

  }

  }


 /*
  ------------------------------------------------------------------

  */
      
  public function synchroRes(){

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select($db->quoteName(array('a.id', 'email', 'name'))) 
      ->from($db->quoteName('#__users', 'a'))
      ->join('LEFT', $db->quoteName('#__comprofiler', 'b') . ' ON ' . $db->quoteName('b.user_id') . ' = ' . $db->quoteName('a.id'))
      ->where($db->quoteName('cb_skirando') . " = 'res'")
      ->where($db->quoteName('cb_skirando') . " IS NOT NULL")
      ->order($db->quoteName('lastname') . ' ASC');
    $db->setQuery($query);
    $encadrants = $db->loadObjectList();


    $qry  = "select a.id as id_adresse, b.id_liste, a.email, a.nom, b.id as id_abonnement 
    from a_adresses as a 
    left join a_abonnements as b on b.id_adresse = a.id 
    left join a_listes as c on c.id = id_liste  
    where c.alias = ? ";
    $abonnes = $this->db2->objListIndex($qry, "email" , "res");      

    $this->msg[] = "Synchro liste res : " .count($encadrants). " encadrants ski (res) - " .count($abonnes). " abonnés";

    foreach($encadrants as $p) {

      $adresses[] = $p->email;

      // Est-ce que l'adresse mail de l'encadrant existe dans la base des abonnés ?

      if(array_key_exists($p->email, $abonnes) == false) {

        // Sinon est-ce que l'adresse mail de l'encadrant existe dans la base a_adresses ?

        $qry  = "select id, email from a_adresses where email = ?";
        $s = $this->db2->obj($qry, $p->email); 

        if(! ($s->email == $p->email) ) {

          // Si non on l'ajoute à a_adresses

          $this->ajouter($p->id, $p->email, 1);
          $s = $this->db2->obj($qry, $p->email);
          $this->msg[] = "Ajout de ". $p->email. " aux adresses pour " .$p->name;

        }

        // Verif qu'on a bien un id adresse
        if ((int) $s->id == 0 ) {
          echo 'Erreur'; exit;
        }

        // Abonne l'adresse à la liste
        $this->abonner($s->id, $this->listes["res"]->id, $p->id);
        $this->msg[] = $p->name ." - ". $p->email. " - adresse ". $s->id . " ajouté aux abonnés";

      } else {

        //$this->msg[] = $p->name ." - ". $p->email. " déjà abonné";

      }

  }

  }

  public function synchroEncadrantsAlpi(){

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select($db->quoteName(array('a.id', 'email', 'name'))) 
      ->from($db->quoteName('#__users', 'a'))
      ->join('LEFT', $db->quoteName('#__comprofiler', 'b') . ' ON ' . $db->quoteName('b.user_id') . ' = ' . $db->quoteName('a.id'))
      ->where($db->quoteName('cb_alpi') . " like '%res'")
      ->where($db->quoteName('cb_alpi') . " IS NOT NULL")
      ->order($db->quoteName('lastname') . ' ASC');
    $db->setQuery($query);
    $encadrants = $db->loadObjectList();


    $qry  = "select a.id as id_adresse, b.id_liste, a.email, a.nom, b.id as id_abonnement 
    from a_adresses as a 
    left join a_abonnements as b on b.id_adresse = a.id 
    left join a_listes as c on c.id = id_liste  
    where c.alias = ? ";
    $abonnes = $this->db2->objListIndex($qry, "email" , "encadrants-alpinisme");      

    $this->msg[] = "Synchro liste encadrants-alpinisme : " .count($encadrants). " encadrants alpi - " .count($abonnes). " abonnés";

    foreach($encadrants as $p) {

      $adresses[] = $p->email;

      // Est-ce que l'adresse mail de l'encadrant existe dans la base des abonnés ?

      if(array_key_exists($p->email, $abonnes) == false) {

        // Sinon est-ce que l'adresse mail de l'encadrant existe dans la base a_adresses ?

        $qry  = "select id, email from a_adresses where email = ?";
        $s = $this->db2->obj($qry, $p->email); 

        if(! ($s->email == $p->email) ) {

          // Si non on l'ajoute à a_adresses

          $this->ajouter($p->id, $p->email, 1);
          $s = $this->db2->obj($qry, $p->email);
          $this->msg[] = "Ajout de ". $p->email. " aux adresses pour " .$p->name;

        }

        // Verif qu'on a bien un id adresse
        if ((int) $s->id == 0 ) {
          echo 'Erreur'; exit;
        }

        // Abonne l'adresse à la liste
        $this->abonner($s->id, $this->listes["encadrants-alpinisme"]->id, $p->id);
        $this->msg[] = $p->name ." - ". $p->email. " - adresse ". $s->id . " ajouté aux abonnés";

      } else {

        //$this->msg[] = $p->name ." - ". $p->email. " déjà abonné";

      }

  }

  }


  public function synchroEncadrantsCascade(){

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select($db->quoteName(array('a.id', 'email', 'name'))) 
      ->from($db->quoteName('#__users', 'a'))
      ->join('LEFT', $db->quoteName('#__comprofiler', 'b') . ' ON ' . $db->quoteName('b.user_id') . ' = ' . $db->quoteName('a.id'))
      ->where($db->quoteName('cb_cascade') . " like '%res'")
      ->where($db->quoteName('cb_cascade') . " IS NOT NULL")
      ->order($db->quoteName('lastname') . ' ASC');
    $db->setQuery($query);
    $encadrants = $db->loadObjectList();


    $qry  = "select a.id as id_adresse, b.id_liste, a.email, a.nom, b.id as id_abonnement 
    from a_adresses as a 
    left join a_abonnements as b on b.id_adresse = a.id 
    left join a_listes as c on c.id = id_liste  
    where c.alias = ? ";
    $abonnes = $this->db2->objListIndex($qry, "email" , "encadrants-cascade");      

    $this->msg[] = "Synchro liste encadrants-cascade : " .count($encadrants). " encadrants cascade - " .count($abonnes). " abonnés";

    foreach($encadrants as $p) {

      $adresses[] = $p->email;

      // Est-ce que l'adresse mail de l'encadrant existe dans la base des abonnés ?

      if(array_key_exists($p->email, $abonnes) == false) {

        // Sinon est-ce que l'adresse mail de l'encadrant existe dans la base a_adresses ?

        $qry  = "select id, email from a_adresses where email = ?";
        $s = $this->db2->obj($qry, $p->email); 

        if(! ($s->email == $p->email) ) {

          // Si non on l'ajoute à a_adresses

          $this->ajouter($p->id, $p->email, 1);
          $s = $this->db2->obj($qry, $p->email);
          $this->msg[] = "Ajout de ". $p->email. " aux adresses pour " .$p->name;

        }

        // Verif qu'on a bien un id adresse
        if ((int) $s->id == 0 ) {
          echo 'Erreur'; exit;
        }

        // Abonne l'adresse à la liste
        $this->abonner($s->id, $this->listes["encadrants-cascade"]->id, $p->id);
        $this->msg[] = $p->name ." - ". $p->email. " - adresse ". $s->id . " ajouté aux abonnés";

      } else {

        //$this->msg[] = $p->name ." - ". $p->email. " déjà abonné";

      }

  }

  }



 /*
  ------------------------------------------------------------------

  */

  /*
  ------------------------------------------------------------------

  */
    
	private function connectDb(){

    include("/home/gumspari/www/includes/base/spdo.php");
    $this->db2 = SPDO::getInstance("sympa");
 
  }       


  /*
  ------------------------------------------------------------------

  */
      
    public function getForm($data = array(), $loadData = true){  
    return true;  
    }       



  /*
  ------------------------------------------------------------------

  */
  function store($data)
	{
		return true;
	}

  /*
  ------------------------------------------------------------------
  */  
  
}
