<?php

namespace application;
use Flight;
use Logger;
require_once __DIR__."/../Client/Client.php";
require_once __DIR__."/../../models/SessionModel.php";
require_once __DIR__."/User.php";

class Session{
	public function __construct($client_from, $access_token, $device_token, $remote_addr, $last_request_time){
		$this->model = new \model\SessionModel(
			array('client_from'=>$client_from, 'access_token'=>$access_token, 'device_token'=>$device_token));
		$this->model->getDataFromDB();
		$this->session_passed = $this->checkSessionAndRegisterUser($this->session_check_msg);
		$this->model->setData(array('remote_addr'=>$remote_addr, 'last_request_time'=>$last_request_time));
		$this->model->updateToDBById();
	}

	private function checkSessionAndRegisterUser(&$message){
		Logger::getLogger("Session")->debug("checkSession");
		Logger::getLogger("Session")->debug($this->model);

		$session_passed = true;
		if(!$this->model->user_id){
			$session_passed = false;
			$message .= 'token异常或session已过期';
		}

		//register default user
		User::RegisterUser($this->model->user_id);
		$user_roles = Flight::user()->getRoles();
		if(empty($user_roles)){
			$session_passed = false;
            $message .= '该用户在当前Client无角色';
		}

		return $session_passed;
	}

	public function isSessionOK(){
		if(!$this->session_passed){
			Flight::sendRouteResult(false, null, $this->session_check_msg, 401);
		}else{
			return true;
		}
	}

	public function expireSession(&$message){
		if($this->model->deleteFromDB())
			return true;
		else{
			$message .= "access_token[". $this->model->access_token ."]不存在";
	    	return false;
		}
	}

    public function saveSession(){
    	$new_model = $this->model;
		$prefix_access_token = Flight::user()->getMobile();
		$access_token = md5($prefix_access_token.time());
		$try_count = 5;
    	$new_model->setData(array('user_id'=>Flight::user()->getUserId(), 'access_token'=>$access_token));
		while(!$new_model->insertIntoDB() && $try_count--){
			$new_model->access_token = md5($prefix_access_token.time());
		}
		return $try_count;
    }

    public function accessToken(){
    	return $this->model->access_token;
    }
    public function deviceToken(){
    	return $this->model->device_token;
    }
    public function remoteAddr(){
    	return $this->model->remote_addr;
    }
    public function lastRequestTime(){
    	return $this->model->last_request_time;
    }
}


?>