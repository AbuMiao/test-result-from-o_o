<?php
/**
 * SmsVertModel: 短信验证码(可迁到redis)
 * @author   cywang <cywang@leqee.com>
 */

namespace model;
use Flight;
use Logger;
use Tools;

class SmsVertModel extends \core\model\AbstractModelRelationWithStamp{
	static public function getCurrSmsCode($partner_id, $mobile){
        $sql = "SELECT sms_code from sms_vert where partner_id = '{$partner_id}' and mobile = '{$mobile}' and expiry_stamp > UNIX_TIMESTAMP(now());";
        return Flight::db()->getOne($sql);
    }
	protected function relationSegs(){
    	return array("partner_id", "mobile");
    }
	protected function requiredSegsBesidesRelationSegs(){
		return array("sms_code", "expiry_stamp");
	}
    protected function tableName(){
    	return "sms_vert";
    }
}
?>
