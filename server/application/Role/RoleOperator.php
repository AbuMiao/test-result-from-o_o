<?php
/**
 * RoleOperator: 运营
 * @author   cywang <cywang@leqee.com>
 */
namespace application;
use Flight;
require_once 'Role.php';

class RoleOperator extends Role{
    static protected function addRole($user_id, $role_data, &$message){}
	public function init(){
		Flight::session()->isSessionOK();
		parent::init();
	}
    protected function getSqlForIsMine($order_id){
        return "SELECT 1 FROM neiru.`order` o WHERE order_id = '{$order_id}';";
    }
}

?>
