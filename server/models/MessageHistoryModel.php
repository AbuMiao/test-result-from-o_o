<?php
/**
 * MessageHistoryModel: 短信历史记录
 * @author   cywang <cywang@leqee.com>
 */

namespace model;
use Flight;
use Logger;
use Tools;

class MessageHistoryModel extends \core\model\AbstractModelEntityWithStamp{
    protected function requiredSegs(){
    	return array("dest_mobile", "content", "server_name");
    }
    protected function optionalSegsBesidesTime(){
    	return array('result','type');
    }
    protected function tableName(){
    	return "message_history";
    }
    protected function idName(){
    	return "message_history_id";
    }
}
?>
