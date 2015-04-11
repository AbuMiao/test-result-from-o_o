<?php
/**
 * SessionModel: session
 * @author   cywang <cywang@leqee.com>
 */

namespace model;
use Flight;
use Logger;
use Tools;

class SessionModel extends \core\model\AbstractModelEntityWithStamp{
    protected function requiredSegs(){
    	return array("user_id", "client_from");
    }
    protected function optionalSegsBesidesTime(){
    	return array('access_token','device_token','remote_addr');
    }
    protected function tableName(){
    	return "session";
    }
    protected function idName(){
    	return "session_id";
    }
    public function deleteFromDB(){
        assert(isset($this->access_token));
        return parent::deleteFromDBByKey('access_token');
    }
}
?>
