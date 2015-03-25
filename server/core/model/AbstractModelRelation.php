<?php
namespace core\model;
use Flight;
use Logger;
require_once __DIR__."/AbstractModel.php";

abstract class AbstractModelRelation extends AbstractModel{
    protected function requiredSegs(){
    	return array_merge($this->relationSegs(), $this->requiredSegsBesidesRelationSegs());
    }
	abstract protected function relationSegs();
	protected function requiredSegsBesidesRelationSegs(){
		return array();
	}
    // save: insert if not exists, otherwise update
    public function saveToDB(&$message=''){
        $this->filterSegs();
        if($this->isRequiredDataExists($message)){
            $seg_names = $seg_values = $updates = array();
            $keys = $this->keys();
            foreach ($keys as $key) {
                assert(isset($this->$key));
                $seg_names[] = $key;
                $seg_values[] = "'".$this->$key."'";
                $updates[] = $key . "='".$this->$key."'";
            }
            $sql = "INSERT INTO " . $this->tableName() ." (".implode(",", $seg_names).")
                    VALUES (".implode(",", $seg_values).")
                    ON DUPLICATE KEY
                    UPDATE ".implode(",", $updates);
            return Flight::db()->query($sql);
        }else{
            return false;
        }
    }
}
?>