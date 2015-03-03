<?php
/**
 * SessionModel: session
 * @author   cywang <cywang@leqee.com>
 */

namespace model;
use Flight;
use Logger;
use Tools;

class SessionModel extends \core\model\AbstractModelEntity{
    protected function requiredSegs(){
    	return array("user_id", "client_from", "created_time", "last_request_time");
    }
    protected function optionalSegs(){
    	return array('access_token','device_token','remote_addr');
    }
    protected function tableName(){
    	return "neiru.session";
    }
    protected function idName(){
    	return "session_id";
    }
    public function insertIntoDB(&$message=''){
    	$this->preInsert();
		return parent::insertIntoDB($message);
    }
    public function updateToDBById(&$message=''){
    	$this->preUpdate();
		return parent::updateToDBById($message);
    }
    public function deleteFromDB(){
        assert(isset($this->access_token));
        return parent::deleteFromDBByKey('access_token');
    }
    private function preInsert(){
        $this->preUpdate();
        $this->created_time = $this->last_updated_time;
    }
    private function preUpdate(){
        $this->last_updated_time = date("Y-m-d H:i:s", time());
    }
}
?>
