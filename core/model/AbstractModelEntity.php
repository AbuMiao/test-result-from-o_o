<?php
namespace core\model;
use Flight;
use Logger;
require_once "AbstractModel.php";

abstract class AbstractModelEntity extends AbstractModel{
    abstract protected function idName();
    public function id(){
        $id_name = $this->idName();
        return isset($this->$id_name) ? $this->$id_name : 0;
    }

    protected function allowedSegs(){
        $allowed_segs = parent::allowedSegs();
        $allowed_segs[] = $this->idName();
        return $allowed_segs;
    }

    // insert
    public function insertIntoDB(&$message=''){
        $id_name = $this->idName();
        $this->filterSegs();
        $this->$id_name = 0;
        if($this->isRequiredDataExists($message)){
            $this->$id_name = Flight::db()->insert($this->tableName(), $this->getData());
        }else{
            $this->$id_name = 0;
        }
        return $this->$id_name;
    }

    // update
    public function updateToDBById(&$message=''){
        $this->filterSegs();
        $id_name = $this->idName();
        $where = $id_name . "='" .$this->$id_name."'"; 
        return Flight::db()->update($this->tableName(), $this->getData(), $where);
    }
}
?>