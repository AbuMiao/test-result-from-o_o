<?php
/**
 * RoleVisitor: 游客
 * @author   cywang <cywang@leqee.com>
 */
namespace application;
use Flight;
require_once 'Role.php';

class RoleVisitor extends Role{
    static protected function addRole($user_id, $role_data, &$message){}
}

?>
