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
}

?>
