<?php
/**
 * RoleCustomer: 顾客角色
 * @author   cywang <cywang@leqee.com>
 */
namespace application;
use Flight;
require_once 'Role.php';

class RoleCustomer extends Role{
    static protected function addRole($user_id, $role_data, &$message){}
    public function getPersonalInfo(){
    	$user_id = Flight::user()->getUserId();
    	$customer = array();
		$sql = "select count(*)	from neiru.user_address where user_id = '{$user_id}' and status in ('COMMON', 'DEFAULT')";
		$customer['address_count'] = Flight::db()->getOne($sql);

		$sql = "select count(*) from neiru.customer_coupon where customer_id = '{$user_id}' and status = 'AVAILABLE' and NOW() <= end_time";
		$customer['available_coupon_count'] = Flight::db()->getOne($sql);

		$sql = "SELECT count(*) 
				FROM neiru.customer_favorite cf 
				INNER JOIN neiru.product p on cf.favorite_id = p.product_id and p.`status` = 'OK' 
				WHERE cf.customer_id = '{$user_id}' and cf.`status` = 'OK' and cf.type = 'PRODUCT';";
		$customer['favorite_count'] = Flight::db()->getOne($sql);

		$sql = "SELECT count(*) 
				FROM neiru.customer_favorite cf 
				INNER JOIN neiru.worker w on cf.favorite_id = w.user_id and w.`status` = 'OK' 
				WHERE cf.customer_id = '{$user_id}' and cf.`status` = 'OK' and cf.type = 'WORKER';";
		$customer['favorite_count'] += Flight::db()->getOne($sql);

		$sql = "SELECT count(*) 
				FROM neiru.customer_favorite cf 
				INNER JOIN neiru.shop s on cf.favorite_id = s.shop_id and s.`status` = 'OK' 
				WHERE cf.customer_id = '{$user_id}' and cf.`status` = 'OK' and cf.type = 'SHOP';";
		$customer['favorite_count'] += Flight::db()->getOne($sql);

		$sql = "SELECT count(*) FROM neiru.`show` WHERE user_id = '{$user_id}';";
		$customer['show_count'] = Flight::db()->getOne($sql);

		$customer['new_message_count'] = 2;

		return $customer;
    }
}

?>
