<?php
namespace core\monitor;
use Flight;

abstract class Monitor{
	public $title = '页面标题';
	public $search_condition_list = array('检索参数');
	public $msg = "";

	public function __construct($title, $search_condition_list){
		$this->title = $title;
		$this->search_condition_list = $search_condition_list;
	}
	abstract public function generateDataForMonitor();
	private function generateDataForMonitorSample(){
		//order
		$sql = "SELECT * from order where order_id = 150";
		$result = Monitor::GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'订单表[order]', $sql, 'order_id');
		$purchase_info_for_generate[] = $result['monitor_info'];

		return $purchase_info_for_generate;
	}

	function monitor(){
		$this->monitor_data = $this->generateDataForMonitor();
		Flight::render('monitors/monitor', get_object_vars($this));
	}

	static function GetTableMonitorInfoAndAdditionalQueryInfoFromSQL($table_name, $sql, $primary_key_name, $ref_name_array=array()){
		$extend_ref_name_array = array();
		if(!in_array($primary_key_name, $ref_name_array)){
			$extend_ref_name_array = array_merge(array($primary_key_name), $ref_name_array);
		}else{
			$extend_ref_name_array = $ref_name_array;
		}

		$ref_list = $data_list=array();
		Flight::db()->getAllRefBy($sql, $extend_ref_name_array, $ref_list, $data_list);

		$table_info = array('table_name'=>$table_name, 'attr_list' =>array(), 'item_list' => array());
		$ref_str_list = array();
		if(empty($data_list)){
			$table_info['attr_list'][] = '无记录';
		}else{
			foreach ($ref_name_array as $ref_name) {
				# code...
				$ref_data_list = $ref_list[$ref_name];
				$ref_str_list[$ref_name] = "'". implode($ref_data_list, "','") . "'";
			}

			$data_list = $data_list[$primary_key_name];
			$sample = reset($data_list);
			$attr_list = array();
			foreach ($sample[0] as $key => $value) {
				$attr_list[] = $key;
			}
			foreach ($data_list as $key => &$value) {
				$value = $value[0];
			}
			$table_info['attr_list'] = $attr_list;
			$table_info['item_list'] = $data_list;
		}

		$result = array('monitor_info' => $table_info, 'query_info' => $ref_str_list);
		return $result;
	}
}
?>