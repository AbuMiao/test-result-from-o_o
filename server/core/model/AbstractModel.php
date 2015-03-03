<?php
namespace core\model;
use Flight;
use Logger;
use Tools;
require_once __DIR__."/../data_struct/BasicData.php";

abstract class AbstractModel extends \core\data_struct\BasicData{
    //record check
    static public function isRecordExist($model_name, $conds){
        $model_class_name = "model\\".$model_name."Model";
        if(class_exists($model_class_name)){
            $model = new $model_class_name($conds);
            return $model->isRecordOfModelExist();
        }else{
            Logger::getLogger("AbstractModel")->warn($model_class_name . ' does not exist');
            return false;
        }
    }
    protected function isRecordOfModelExist(){
        $table_name = $this->tableName();
        $keys = $this->keys();
        $sql = "select 1 from " . $table_name . " where 1 ";
        foreach ($keys as $key) {
            $sql .= " and " . $key . " = '". $this->$key ."'";
        }
        return Flight::db()->getOne($sql);
    }

    //segs check
    abstract protected function requiredSegs();
    protected function optionalSegs(){
        return array();
    }
    protected function allowedSegs(){
        return array_merge($this->requiredSegs(), $this->optionalSegs());
    }
    abstract protected function tableName();
    protected function isRequiredDataExists(&$message){
        $required_segs = $this->requiredSegs();
        $absent_data_names = array();
        foreach ($required_segs as $data_name) {
            if(!isset($this->$data_name)){
                $absent_data_names[] = $data_name;
            }
        }
        if(!empty($absent_data_names)){
            $message .= implode("|", $absent_data_names) . " are(is) required but not available";
            return false;
        }else{
            return true;
        }
    }
    protected function filterSegs(){
        $table_segs = $this->allowedSegs();
        $invalid_data_names = array();
        $keys = $this->keys();
        foreach ($keys as $key) {
            if(!in_array($key, $table_segs)){
                $invalid_data_names[] = $key;
            }
        }
        foreach ($invalid_data_names as $invalid_data_name) {
            unset($this->$invalid_data_name);
        }
    }
    //set data
    public function setData($data){
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    // select
    public function getDataFromDB(){
        //根据当前model里已有字段获取剩余字段
        return $this->selectDataFromDB($this->keys());
    }
    protected function selectDataFromDB($seg_names){
        $table_name = $this->tableName();
        $conds = array();
        $allowed_segs = $this->allowedSegs();
        foreach ($seg_names as $seg_name) {
            assert(in_array($seg_name, $allowed_segs), $seg_name." of ".$table_name." is not allowed");
            if(!isset($this->$seg_name)){
                print_r($this);die();
            }
            assert(isset($this->$seg_name), $seg_name." of ".$table_name." is not set");
            $conds[] = $seg_name . " = '{$this->$seg_name}' ";
        }
        $sql = "select * from " . $table_name . " where " . implode(" and ", $conds) . ";";
        $data = Flight::db()->getRow($sql);
        if(!empty($data)){
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
        return $data;
    }
    //根据当前model数据，获取指定字段值
    public function selectSegsValueFromDB($select_seg_names){
        $table_name = $this->tableName();
        $allowed_segs = $this->allowedSegs();

        $conds = array();
        $select_seg_names = array_intersect($select_seg_names, $allowed_segs);
        assert(!empty($select_seg_names));
        $select_segs = implode(",", $select_seg_names);
        $where_keys = $this->keys();
        foreach ($where_keys as $key) {
            assert(in_array($key, $allowed_segs), $key ." is not in allowed_segs of Model ".__CLASS__);
            assert(isset($this->$key),  $key ." is set in Model ".__CLASS__);
            $conds[] = $key . " = '{$this->$key}' ";
        }
        $sql = "select ".$select_segs." from " . $table_name . " where " . implode(" and ", $conds) . ";";
        $data = Flight::db()->getRow($sql);
        if(!empty($data)){
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
        return $data;
    }

    // delete：切勿乱用，当前仅session允许，在类内声明为public
    protected function deleteFromDBByKey($where_key){
        $table_name = $this->tableName();
        $where = $where_key . "='" .$this->$where_key."'"; 
        return Flight::db()->delete($table_name, $where);
    }
}
?>