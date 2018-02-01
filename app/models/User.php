<?

class AuthException extends Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

//lookup person information
class User
{
    public function __construct($dn)
    {
        //action that this user can perform
        $this->action = array();

        $this->dn = $_SESSION["email"];//$dn;
        $this->dn_id = null;
        $this->contact_id = null;
        $this->contact_name = "Guest";
        $this->contact_email = "";
        $this->contact_phone = "";
        //$this->timezone = "UTC";
        $this->disable = true;

        $this->guest = true;

        if($_SESSION["email"] !== null) {
	
	       $this->lookupDN($_SESSION["email"]);
            if($this->dn_id !== null) {
                $this->guest = false;
                $this->lookupActions();
		}
        }
	
        
        if(config()->debug) {
            slog("Debug Dump of User Object");
            slog(print_r($this, true));
        }
        
    }

    private function lookupActions()
    {
        $model = new DNAuthorizationType();
        $dnauthtypes = $model->fetchAllByDNID($this->dn_id);

        $matrix_model = new AuthorizationTypeAction();
        $action_model = new Action();
        $actions = $action_model->fetchAll();

        //for each auth types that the user is member for..
        foreach($dnauthtypes as $dnauthtype) {
            $aids = $matrix_model->fetchAllByAuthTypeID($dnauthtype->authorization_type_id);

            //for each action under that auth type..
            foreach($aids as $aid) {
                $action_id = (int)$aid->action_id;
                //don't enter same action twice.
                if(isset($this->action[$action_id])) continue;

                //search for the action
                foreach($actions as $action) {
                    if($action->id == $action_id) {
                        $this->action[$action_id] = $action->name;
                        break;
                    }
                }
            }
        }
    }

    private function lookupDN($dn)
    {

      $sql_sso = "select * from contact_authorization_type where email = \"$dn\" or email1 = \"$dn\" or email2 = \"$dn\" or email3 = \"$dn\" limit 1 ";
      // print $sql_sso;     
      $row_sso = db("sso")->fetchRow($sql_sso);


      $sql_select_contact = "select * from contact where primary_email = '".$dn."' or  secondary_email = '".$dn."'";

      slog("UserModule: There is Contact: $sql_select_contact");
      $contact_no_sso_id=0;
      $contact_id=0;

      $row_select_contact = db("oim")->fetchRow($sql_select_contact);
      if($row_select_contact) {
	$contact_id = $row_select_contact->id;
	$sql_select_contact_record = "select * from contact_authorization_type where contact_id= $contact_id";

	slog("UserModule: There is contact_authorization_type with contact_id # $contact_id: $sql_select_contact_record");

	$row_select_contact_record = db("sso")->fetchRow($sql_select_contact_record);
	if($row_select_contact_record) {
	  $contact_no_sso_id=$row_select_contact_record->id;
	  $sso_id_last = $contact_no_sso_id;
	  $contact_flag=1;
	}
      }

      $sql_select_dn = "select * from dn where dn_string='".$_SERVER["SSL_CLIENT_S_DN"]."'";
      slog($sql_select_dn);
      $row_select_dn = db("oim")->fetchRow($sql_select_dn);
      if($row_select_dn) {
	$dn_string_id = $row_select_dn->id;
	$contact_id = $row_select_dn->contact_id;
	$sql_sso_dn = "select * from contact_authorization_type where contact_id=".$contact_id." ";
	slog($sql_sso_dn);

	slog("UserModule: There is Contact: $sql_sso_dn");

	$row_select_sso_dn = db("sso")->fetchRow($sql_sso_dn);
	if($row_select_sso_dn) {

	  $contact_no_sso_id=$row_select_sso_dn->id;
	  $sso_id_last = $contact_no_sso_id;
	  $dn_flag=1;
	}
      }

      if($row_sso) {

	$update_sso = "update  contact_authorization_type set given_name='".$_SESSION["given_name"]."', family_name ='".$_SESSION["family_name"]."', idp= '".$_SESSION["idp"]."', idp_name= '".$_SESSION["idp_name"]."',aud='".$_SESSION["aud"]."', name= '".$_SESSION["name"]."',  oidc= '".$_SESSION["oidc"]."', openid= '".$_SESSION["openid"]."', sub='".$_SESSION["sub"]."', access_token='".$_SESSION["access_token_sso"]."', remote_user='".$_SESSION["remote_user"]."', last_login=now() where id = ".$row_sso->id."";
	slog("UserModule- there SSO; ".$update_sso);

	//print $update_sso;
	
	db("sso")->exec($update_sso);
	$sso_idp = $row_sso->idp;
	$this->dn_id = $row_sso->id;
	$this->contact_id = $row_sso->contact_id;
	$this->contact_name = "".$row_sso->given_name." ". $row_sso->family_name."";
	
	//print "".$row_sso->given_name." ". $row_sso->family_name."<br>";                                                                                                                    
	$this->contact_email = $row_sso->email;
	$this->disable = ($row_sso->disable || $row_sso->dn_disable);
	
	
      }else{

	if ($contact_no_sso_id>0){
	  $update_sso_rec = "update  contact_authorization_type set email1='".$dn."', last_login=now() where id = ".$contact_no_sso_id."";
	  
	  slog("Found Record Updating Email:  ".$update_sso_rec);
	  $sso_id_last = $contact_no_sso_id;
	  db("sso")->exec($update_sso_rec);


	  if($contact_flag>0){
	    $dn_sql = "select dn.id as dn_id from dn left join dn_authorization_type on dn.id=dn_authorization_type.dn_id where contact_id=".$contact_id." and (disable=0 or disable is null) order by dn.id desc limit 1";
            $select_dn_rec = db("oim")->fetchRow($dn_sql);
            $dn_id= $select_dn_rec->dn_id;
            if($dn_id!=""){
              $sql = "select * from dn_authorization_type where dn_id=".$dn_id."";
              foreach(db("oim")->fetchAll($sql) as $row) {
                $authorization_type_id  = $row->authorization_type_id;
                db("sso")->exec("delete from contact_authorization_type_index where contact_authorization_type_id=".$sso_id_last." and authorization_type_id=".$authorization_type_id."");
                $insert_action = "INSERT INTO contact_authorization_type_index (contact_authorization_type_id, authorization_type_id) VALUES (".$sso_id_last.",".$authorization_type_id.")";
                db("sso")->exec($insert_action);
              }
            }else{
              $insert_1 = "INSERT INTO contact_authorization_type_index (contact_authorization_type_id, authorization_type_id) VALUES (".$sso_id_last.",1)";
              db("sso")->exec($insert_1);
            }

	  }elseif($dn_flag>0){

	    $sql = "select * from dn_authorization_type where dn_id=".$dn_string_id."";
	    foreach(db("oim")->fetchAll($sql) as $row) {
	      $authorization_type_id  = $row->authorization_type_id;
	      db("sso")->exec("delete from contact_authorization_type_index where contact_authorization_type_id=".$sso_id_last." and authorization_type_id=".$authorization_type_id."");
	      $insert_action = "INSERT INTO contact_authorization_type_index (contact_authorization_type_id, authorization_type_id) VALUES (".$sso_id_last.",".$authorization_type_id.")";
	      db("sso")->exec($insert_action);

	    }

	  }

	}else{
	    try {
	      db("sso")->beginTransaction();
	      
	      $insert_sso_dn_last = "insert into contact_authorization_type (given_name, family_name, email, idp, idp_name,aud, iat, name, iss, nonce, oidc, openid, sub, access_token, access_token_expires, remote_user,authorization_type_id, created) values('".$_SESSION["given_name"]."', '".$_SESSION["family_name"]."','".$_SESSION["email"]."', '".$_SESSION["idp"]."', '".$_SESSION["idp_name"]."','".$_SESSION["aud"]."', '".$_SESSION["iat"]."', '".$_SESSION["name"]."', '".$_SESSION["iss"]."', '".$_SESSION["nonce"]."', '".$_SESSION["oidc"]."', '".$_SESSION["openid"]."', '".$_SESSION["sub"]."', '".$_SESSION["access_token_sso"]."', '".$_SESSION["access_token_expires"]."', '".$_SESSION["remote_user"]."',1, now())";
	      slog($insert_sso_dn_last);
	      $insert_sso_dn=db("sso")->query($insert_sso_dn_last);
	      $sso_id_last=db("sso")->lastInsertId();
	      $insert_actions ="insert into contact_authorization_type_index (contact_authorization_type_id,authorization_type_id) values (".$sso_id_last.",1)";
	      db("sso")->exec($insert_actions);
	      db("sso")->commit();
	    } catch (Exception $e) {
	      db("sso")->rollBack();
            }
	}
      }      

      if($sso_id_last!=""){
	$sql_sso2 = "select * from contact_authorization_type where id=$sso_id_last ";
        slog("Get record:".$sql_sso2);
        $row_sso2 = db("sso")->fetchRow($sql_sso2);

        if($row_sso2) {
	  $this->dn_id = $row_sso2->id;
	  $this->contact_id = $row_sso2->contact_id;
	  $this->contact_name = "".$row_sso2->given_name." ". $row_sso2->family_name."";
	  $this->contact_email = $row_sso2->email;
	  $this->disable = ($row_sso2->disable || $row_sso2->dn_disable);
	}
      }
    }
    
    public function isGOCMachine() {
        $remote_ip = $_SERVER["REMOTE_ADDR"];
        foreach(config()->gocip as $prefix) {
            if(strpos($remote_ip, $prefix) === 0) {
                slog("Accessed from GOC machine: $remote_ip");
                return true;
            }
        }
        return false;
    }

    public function allows($action) {
      return in_array(config()->role_prefix.$action, $this->action);
      return true;
    }
    public function check($action) {
      if(!$this->allows($action)) {
           throw new AuthException("You don't have $action permission to access this page.");
       }
      return true;
    }

    public function isGuest() { return $this->guest; }
    public function isDisabled() { return $this->disable; }
    public function getPersonID() { return $this->contact_id; }
    public function getPersonName() { return $this->contact_name; }
    public function getPersonEmail() { return $this->contact_email; }
    public function getPersonPhone() { return $this->contact_phone; }
    public function getDN() { return $this->dn; }
    public function getDNID() { return $this->dn_id; }
    //public function getTimeZone() { return $this->timezone; }
}
