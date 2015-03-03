<?php

namespace application;
use Flight;
use Logger;

abstract class Client{
    static public function RegisterClient($client){
    	$client = strtolower($client);
        $client_arr = explode("_", $client);
        $client = "";
        foreach ($client_arr as $c_seg) {
            $client .= ucfirst($c_seg);
        }
        Flight::register('client', 'application\\Client'.ucfirst($client));
    }

	public function __construct($client_from){
		$this->client_from = $client_from;
	}
	public function getRoles($user_roles){
		$roles = array_intersect($this->getAllowedRoles(), $user_roles);
		return array_values($roles);
	}
	abstract protected function getAllowedRoles();
}

class ClientCustomer extends Client{
	public function __construct(){
		parent::__construct("CUSTOMER");
	}
	public function getRoles($user_roles){
		return $this->getAllowedRoles();
	}
	protected function getAllowedRoles(){
		return array("CUSTOMER", "PAY_TOOL");
	}
}

class ClientShop extends Client{
	public function __construct(){
		parent::__construct("SHOP");
	}
	public function getRoles($user_roles){
		$roles = parent::getRoles($user_roles);
		if(empty($roles))
			return array("VISITOR");
		else
			return $roles;
	}
	protected function getAllowedRoles(){
		return array("WORKER", "SHOP_MANAGER");
	}
}

class ClientPayTool extends Client{
	public function __construct(){
		parent::__construct("PAY_TOOL");
	}
	public function getRoles($user_roles){
		return $this->getAllowedRoles();
	}
	protected function getAllowedRoles(){
		return array("PAY_TOOL");
	}
}

class ClientOperation extends Client{
	public function __construct(){
		parent::__construct("OPERATION");
	}
	protected function getAllowedRoles(){
		return array("CUSTOMER_SERVICE", "OPERATOR");
	}
}
?>