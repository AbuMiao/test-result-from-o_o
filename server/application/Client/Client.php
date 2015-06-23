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
?>