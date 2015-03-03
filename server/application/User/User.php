<?php
/**
 * User: 用户(包括Customer和Worker)
 * @author   cywang <cywang@leqee.com>
 */
namespace application;
use Flight;
use Logger;

require_once __DIR__."/../../models/UserModel.php";
require_once __DIR__."/../Role/Role.php";
require_once __DIR__."/../Client/Client.php";

class User extends \core\data_struct\BasicData{
    static public function RegisterUser($user_id){
        $user_id = isset($user_id) ? $user_id : 0;
        $user_geo_coor = new \core\tools\ClsGeographicCoor(
                isset($_SERVER['HTTP_N_LONGITUDE']) ? $_SERVER['HTTP_N_LONGITUDE'] : 120,
                isset($_SERVER['HTTP_N_LATITUDE']) ? $_SERVER['HTTP_N_LATITUDE'] : 30
                );
        Flight::register('user', 'application\\User', 
            array(array(
                'geo_coor' => $user_geo_coor,
                'user_id' => $user_id
                )),
            function($user){
                $user->init();
            });
    }

    public function init(){
        //User的通用routes
        User::setRoute();

        //
        $this->model = new \Model\UserModel(array('user_id'=>$this->user_id));
        $this->model->getDataFromDB();
        $user_roles = explode(";", $this->model->role);

        $this->available_roles = Flight::client()->getRoles($user_roles);

        $role_instance_list = array();
        foreach ($this->available_roles as $role) {
            //不同role的专用route
            $role_cls_name = Role::convertRoleToClsName($role);
            require_once __DIR__.'/../Role/'.$role_cls_name.'.php';
            $role_cls_name = 'application\\'.$role_cls_name;
            $role_instance_list[] = new $role_cls_name;
            $files = glob(__DIR__.'/../Role/'. Role::convertRoleToFloderName($role) .'/*.php');
            foreach($files as $file)
            {
                if (file_exists($file)) {
                    require_once $file;
                    $class = 'application\\' . basename($file, ".php");
                    if(class_exists($class) && method_exists($class,'setRoute')) {
                        $class::setRoute();
                    }
                }
            }
        }
        $this->role_instance_list = $role_instance_list;
    }
	public function getGeoCoor(){
		$this->geo_coor = $this->geo_coor ? $this->geo_coor : new Tools\ClsGeographicCoor(120,30);
		return $this->geo_coor;
	}
	public function get3DCoor(){
		$this->td_coor = $this->td_coor ? $this->td_coor : Flight::new3DPosInsByGeographicCoor($this->getGeoCoor());
		return $this->td_coor;
	}
	public function getUserId(){
        $this->user_id = $this->user_id ? $this->user_id : 0;
		return $this->user_id;
	}
    public function getMobile(){
        if(isset($this->model->mobile))
            return $this->model->mobile;
        else
            return "";
    }
    public function getRoles(){
        $this->available_roles = $this->available_roles ? $this->available_roles : array();
        return $this->available_roles;
    }
    public function getShopId(){
        if(!isset($this->shop_id)){
            $shop_id = array();
            foreach ($this->role_instance_list as $role_ins) {
                $shop_id[] = $role_ins->getShopId();
            }
            if(empty($shop_id))
                $this->shop_id = 0;
            else{
                $shop_id = array_unique($shop_id);
                if(count($shop_id)!=1){
                    Flight::sendRouteResult(false, null, "多个角色所属店铺不一致，请检查");
                }
                $this->shop_id = $shop_id[0];
            }
        }
        return $this->shop_id;
    }

    public function isMyOrder($order_id){
        //todo
        return 1;
        $sql = $this->getSqlForIsMine($order_id);
        return Flight::db()->getOne($sql);
    }

    static public function setRoute(){
        User::setProfileRoute();
        User::setInfoRoute();
    }
	static private function setProfileRoute(){
		//sms_vert_code for login
        Flight::route(
            'POST /profile/sms_vert_code',
            array('application\\User', 'cbSmsVertCode')
        );
		//login
        Flight::route(
            'POST /profile/login',
            array('application\\User', 'cbLogin')
        );
		//logout
        Flight::route(
            'POST /profile/logout',
            array('application\\User', 'cbLogout')
        );
	}
    static private function setInfoRoute(){
        //infos
        Flight::route(
            'GET /personal_info',
            array('application\\User', 'cbPersonalInfo')
        );
        Flight::route(
            'PUT /personal_info',
            array('application\\User', 'cbUpdateUserBasicInfo')
        );

        //address_list
        Flight::route(
            'GET /address_list',
            array('application\\User', 'cbAddressList')
        );
        //address
        Flight::route(
            'POST /address',
            array('application\\User', 'cbAddNewAddress')
        );
        Flight::route(
            'DELETE /address/@address_id',
            array('application\\User', 'cbDeleteAddress')
        );
        Flight::route(
            'PUT /default_address/@address_id',
            array('application\\User', 'cbSetDefaultAddress')
        );
    }

	//sms_vert_code
    static public function cbSmsVertCode(){
        $success = false;
        $message = "";
    	$sms_code = User::generateSmsVertCode();
		$sms_msg = User::generateSmsContent($sms_code);
    	$mobile = Flight::request()->data->mobile;
    	if (!preg_match("/^\d{11}$/",$mobile)){
            $success = false;
            $message = "手机号必须为1开头的11位数字,请检查手机号";
    	}else{
			Flight::sendSMSCode(array($mobile), $sms_msg);
	    	$sql = "INSERT INTO neiru.sms_vert (mobile,sms_code,expiry_stamp,created_time, last_updated_time)
						VALUES ('{$mobile}','{$sms_code}', UNIX_TIMESTAMP(now())+600,NOW(),NOW())
					ON DUPLICATE KEY UPDATE sms_code='{$sms_code}', expiry_stamp=UNIX_TIMESTAMP(now())+600,last_updated_time=NOW()";
			if(!Flight::db()->query($sql)){
                $success = false;
                $message = "服务器错误(数据库插入记录失败)".$sql;
            }
            else{
                $success = true;
            }
    	}
    	Flight::sendRouteResult($success, null, $message);
    }

    static private function generateSmsVertCode(){
		$c = "0123456789";
		$l = 4;
		$rand = "";
		srand((double)microtime()*1000000);

		for($i=0; $i<$l; $i++) { 
			$rand.= $c[rand()%strlen($c)];
		} 

    	return $rand;
    }

	static private function generateSmsContent($sms_code) {
		$content = "注册验证码:" . $sms_code . ",请于10分钟内完成验证。"; 
		return $content;
	}	

    static private function checkLoginData($mobile, $sms_code, &$message){
		if (!preg_match("/^\d{11}$/",$mobile)){
    		$message = "手机号必须为1开头的11位数字，请检查";
    		return false;
    	}else{
    		return true;
    	}
    }

    //login
    static public function cbLogin(){
    	$data = Flight::request()->data;
    	$mobile = $data->mobile;
    	$sms_code = $data->sms_code;
        $message = "";
        $success = User::checkLoginData($mobile, $sms_code, $message);
        $data = null;
        if($success){
            $sql = "SELECT sms_code from neiru.sms_vert where mobile = '{$mobile}' and expiry_stamp > UNIX_TIMESTAMP(now());";
            $sent_sms_code = Flight::db()->getOne($sql);
            if (!isset($sent_sms_code)) {
                $success = false;
                $message = "该手机号未申请验证码或验证码已过期，请先申请手机验证码";
            } else if($sms_code != $sent_sms_code) {
                $success = false;
                $message = "验证码不正确，请重新申请";
            } else {
                $sql = "SELECT user_id FROM neiru.user WHERE mobile = '{$mobile}'";
                $user_id = Flight::db()->getOne($sql);
                if(!$user_id) {
                    // 不存在的用户插入数据
                    $user_model = new \Model\UserModel(array('mobile'=>$mobile));
                    $user_id = $user_model->insertIntoDB($message);
                    assert($user_id);
                }
                User::RegisterUser($user_id);
                Flight::db()->start_transaction();
                if(Flight::user()->updateLoginData($message) && Flight::session()->saveSession()) {
                    Flight::db()->commit();
                    $success = true;
                    $data = array('roles'=> Flight::user()->getRoles(), 'access_token' => Flight::session()->accessToken());
                } else {
                    Flight::db()->rollback();
                    $success = false;
                }
            }            
        }
        Flight::sendRouteResult($success, $data, $message);
    }

    private function updateLoginData(&$message){
        $this->model->last_device_id = Flight::session()->deviceToken();
        $this->model->last_ip = Flight::session()->remoteAddr();
        $this->model->last_login_time = Flight::session()->lastRequestTime();
        return $this->model->updateToDBById($message);
    }
	
	//logout
    static public function cbLogout(){
    	$message = "";
        $success = Flight::session()->expireSession($message);
    	Flight::sendRouteResult($success, null, $message);
    }


    //info
    //basic info
    static public function cbPersonalInfo(){
        Flight::session()->isSessionOK();
        Flight::sendRouteResult(true, array('personal_info'=>Flight::user()->personalInfo()));
    }
    protected function personalInfo(){
        $sql = "select user_id, nick, avatar, gender, birthday from neiru.user where user_id = '{$this->user_id}'";
        $personal_info = Flight::db()->getRow($sql);
        foreach ($this->role_instance_list as $role_ins) {
            $personal_info = array_merge($personal_info, $role_ins->getPersonalInfo());
        }
        return $personal_info;
    }
    static public function cbUpdateUserBasicInfo(){
        Flight::session()->isSessionOK();
        $message = "";
        $success = Flight::user()->updateBasicInfo(Flight::request()->data->getData(), $message);
        Flight::sendRouteResult($success, null, $message);
    }
    private function updateBasicInfo($data, &$message){
        $this->model->setData($data);
        return $this->model->updateToDBById($message);
    }

    //address list
    static public function cbAddressList(){
        Flight::session()->isSessionOK();
        $user_id = Flight::user()->getUserId();
        assert($user_id);
        $data = Flight::request()->query;
        $sql = "select address_id, title, detail, status, longitude, latitude from neiru.user_address where user_id = '{$user_id}' and status in ('COMMON','DEFAULT')";
        $sql .= isset($data['city_code']) ? " and city_code = '{$data['city_code']}'" : "";
        $address_list = Flight::db()->getAll($sql);
        Flight::sendRouteResult(true, array('address_list'=>$address_list));
    }
    //address
    static public function cbAddNewAddress(){
        Flight::session()->isSessionOK();
        
        $data = Flight::request()->data->getData();
        $user_id = Flight::user()->getUserId();
        assert($user_id);
        $data['user_id'] = $user_id;

        $data['city_code'] = isset($data['city_code'])&&!empty($data['city_code']) ? $data['city_code'] : Flight::get("default_city_code");
        $data['title'] = isset($data['title']) ? $data['title'] : "";
        $data['detail'] = isset($data['detail']) ? $data['detail'] : "";
        if(empty($data['title'])){
            $success = false;
            $message = '地址名(title)或地址详情(detail)缺失';
        }else{
            if(isset($data['longitude']) && isset($data['latitude'])){
                $pos = Flight::new3DPosInsByGeographicCoor((object)$data);
                $data['pos_x'] = $pos->x;
                $data['pos_y'] = $pos->y;
                $data['pos_z'] = $pos->z;
            }

            $data['created_time'] = $data['last_updated_time'] = date("Y-m-d H:i:s");

            //insert
            $address = new Basic($data);
            $success = $address->saveToDB("neiru.user_address");
            assert($success);
        }
        Flight::sendRouteResult($success, null, $message);
    }

    static public function cbDeleteAddress($address_id){
        Flight::session()->isSessionOK();
        assert(Flight::user()->getUserId());

        $success = User::setAddressStatus($address_id, 'DELETED', $message);
        Flight::sendRouteResult($success, null, $message);
    }
    static public function cbSetDefaultAddress($address_id){
        Flight::session()->isSessionOK();
        $user_id = Flight::user()->getUserId();
        assert($user_id);

        Flight::db()->start_transaction();
        $sql = "select address_id from neiru.user_address where user_id = '{$user_id}' and status = 'DEFAULT';";
        $curr_default_address_id = Flight::db()->getOne($sql);
        $curr_default_address = new Basic(
            array("address_id"=>$curr_default_address_id, 'status'=>'COMMON', 'last_updated_time'=>date("Y-m-d H:i:s")));

        $message = "";
        if( (!$curr_default_address_id || ($curr_default_address_id && User::setAddressStatus($curr_default_address_id, 'COMMON', $message)))
            &&
            User::setAddressStatus($address_id, 'DEFAULT', $message) ){
            Flight::db()->commit();
            $success = 'true';
        }else{
            Flight::db()->rollback();
            $success = 'false';
        }
        Flight::sendRouteResult($success, null, $message);
    }

    static private function setAddressStatus($address_id, $status, &$message){
        $address = new Basic(
            array("address_id"=>$address_id, 'status'=>$status, 'last_updated_time'=>date("Y-m-d H:i:s")));
        if($address->updateDB("neiru.user_address", array('status', 'last_updated_time'), 'address_id')){
            return true;
        }else{
            $message .= "数据库更新记录失败";
            return false;
        }
    }


}
?>
