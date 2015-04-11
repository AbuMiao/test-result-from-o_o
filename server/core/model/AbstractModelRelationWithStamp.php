<?php
namespace core\model;
use Flight;
use Logger;
require_once __DIR__."/AbstractModelRelation.php";

abstract class AbstractModelRelationWithStamp extends AbstractModelRelation{
    protected function optionalSegs(){
        return array_merge(array("last_updated_time"), $this->optionalSegsBesidesTime());
    }
    protected function optionalSegsBesidesTime(){
        return array();
    }
    public function saveToDB(&$message=''){
        $this->preUpdate();
        return parent::saveToDB($message);
    }
    private function preUpdate(){
        $this->last_updated_time = date("Y-m-d H:i:s", time());
    }
}
?>