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
	protected function relationSegs(){
    	return array("mobile");
    }
	protected function requiredSegsBesidesRelationSegs(){
		return array("sms_code", "expiry_stamp");
	}
    protected function tableName(){
    	return "sms_vert";
    }
}
?>
