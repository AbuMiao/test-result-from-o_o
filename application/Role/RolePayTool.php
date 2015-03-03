<?php
/**
 * RolePayTool: 第三方支付角色
 * @author   cywang <cywang@leqee.com>
 */
namespace application;
use Flight;
require_once 'Role.php';

class RolePayTool extends Role{
    static protected function addRole($user_id, $role_data, &$message){}
}

?>
