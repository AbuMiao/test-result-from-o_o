<?php
namespace core\model;
use Flight;
use Logger;
require_once __DIR__."/AbstractModelEntity.php";

abstract class AbstractModelEntityWithStamp extends AbstractModelEntity{
    protected function optionalSegs(){
        return array_merge(array("created_time", "last_updated_time"), $this->optionalSegsBesidesTime());
    }
    protected function optionalSegsBesidesTime(){
        return array();
    }
    public function insertIntoDB(&$message=''){
        $this->preInsert();
        return parent::insertIntoDB($message);
    }
    public function updateToDBById(&$message=''){
        $this->preUpdate();
        return parent::updateToDBById($message);
    }
    private function preInsert(){
        $this->preUpdate();
        $this->created_time = $this->last_updated_time;
    }
    protected function preUpdate(){
        $this->last_updated_time = date("Y-m-d H:i:s", time());
    }
}
?>