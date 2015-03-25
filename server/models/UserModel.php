<?php
/**
 * UserModel: 用户
 * @author   cywang <cywang@leqee.com>
 */

namespace model;
use Flight;
use Logger;
use Tools;

class UserModel extends \core\model\AbstractModelEntity{
    protected function requiredSegs(){
    	return array("mobile", "register_time", "last_updated_time");
    }
    protected function optionalSegs(){
    	return array('nick','gender','birthday','avatar',
            'role','status',
            'last_device_id', 'last_ip', 'last_login_time');
    }
    protected function tableName(){
    	return "user";
    }
    protected function idName(){
    	return "user_id";
    }
    public function insertIntoDB(&$message=''){
    	$this->preInsert();
        $this->register_time = $this->last_updated_time = date("Y-m-d H:i:s", time());
		return parent::insertIntoDB($message);
    }
    public function updateToDBById(&$message=''){
    	$this->preUpdate();
		$this->last_updated_time = date("Y-m-d H:i:s", time());
		return parent::updateToDBById($message);
    }
    private function preInsert(){
        if(empty($this->nick)){
            $this->nick = Flight::generateName();
        }
        $this->preUpdate();
    }
    private function preUpdate(){
		if(isset($this->avatar_data) && !empty($this->avatar_data)){
			$image_id = Tools\ClsImageTools::generateImage($this->avatar_data);
			$this->avatar = Tools\ClsImageTools::getImgUrl($image_id);
		}
    }
}
?>
