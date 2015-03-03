<?php
/**
 * TestEntityModel: 实例model测试用例类
 * @author   cywang <cywang@leqee.com>
 */
namespace Tests;
require_once "../core/model/AbstractModelEntity.php";
use Flight;
use Logger;
use Tools;
use Model;

class TestEntityModel extends core\model\AbstractModelEntity{
    protected function requiredSegs(){
    	return array("test_entity_required");
    }
    protected function optionalSegs(){
    	return array('test_entity_optional');
    }
    protected function tableName(){
    	return "neiru.test_entity";
    }
    protected function idName(){
    	return "test_entity_id";
    }
}

class EntityModelTester{
	static public function createEntity(){
		$message = "";
		$model = new TestEntityModel(array('test_entity_required'=>"1"));
		$model->insertIntoDB($message);
		print_r($message);
		print_r($model);
	}
	static public function updateEntity(){
		$message = "";
		$model = new TestEntityModel(array('test_entity_id'=>"2", 'test_entity_optional'=>'11'));
		print_r($model);
		$model->updateToDBById($message);
		print_r($message);
		print_r($model);
	}
}

require_once '../flight/Flight.php';
require_once '../tools/cls_mysql.php';
$config_data = parse_ini_file('../config/database.ini');
Flight::register('db', 'cls_mysql', array($config_data['host'], $config_data['username'], $config_data['password'], $config_data['database']));
//EntityModelTester::createEntity();
EntityModelTester::updateEntity();

?>
