<?php
/**
 * UserModel: 用户
 * @author   cywang <cywang@leqee.com>
 */

namespace model;
use Flight;
use Logger;
use Tools;

class UserModel extends \core\model\AbstractModelEntityWithStamp{
    static public function getUserId($partner_id, $mobile){
        $sql = "SELECT user_id FROM user WHERE partner_id = '{$partner_id}' and mobile = '{$mobile}'";
        return Flight::db()->getOne($sql);
    }
    protected function requiredSegs(){
    	return array("partner_id", "mobile");
    }
    protected function optionalSegsBesidesTime(){
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
}
?>
