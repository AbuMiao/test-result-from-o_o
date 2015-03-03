<?php
/**
 * TestRelationModel: 关系model测试用例类
 * @author   cywang <cywang@leqee.com>
 */
namespace Tests;
require_once "../core/model/AbstractModelEntity.php";
use Flight;
use Logger;
use Tools;
use Model;

class TestRelationModel extends core\model\AbstractModelRelation{
    protected function optionalSegs(){
    	return array('test_entity_optional');
    }
	protected function relationSegs(){
    	return array("entity_id1", "entity_id2");
    }
	protected function requiredSegsBesidesRelationSegs(){
    	return array("test_entity_required");
	}
    protected function tableName(){
    	return "neiru.test_relation";
    }
}

class RelationModelTester{
	static public function updateEntity(){
		$message = "";
		$model = new TestRelationModel(array('entity_id1'=>"2", 'entity_id2'=>"2", 'test_entity_required'=>'required'));
		print_r($model);
		$model->saveToDB($message);
		print_r($message);
		print_r($model);
	}
}

require_once '../flight/Flight.php';
require_once '../tools/cls_mysql.php';
$config_data = parse_ini_file('../config/database.ini');
Flight::register('db', 'cls_mysql', array($config_data['host'], $config_data['username'], $config_data['password'], $config_data['database']));
RelationModelTester::updateEntity();
?>
