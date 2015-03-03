<?php
/**
 * Role: 角色
 * @author   cywang <cywang@leqee.com>
 */
namespace application;
use Flight;
use Logger;
use Tools;

abstract class Role{
    static public function addRoleToUser($user_id, $role, $role_data, &$message){
        print_r("Role::addRoleToUser");
    	$role_cls_name = Role::convertRoleToClsName($role);
    	require_once $role_cls_name.".php";
        $role_cls_name = "application\\" . $role_cls_name;
    	return Role::addRoleToUserModel($user_id, $role, $message) && $role_cls_name::addRole($user_id, $role_data, $message);
    }
    static private function addRoleToUserModel($user_id, $role, &$message){
        print_r("Role::addRoleToUserModel");
		require_once __DIR__."/../../model/UserModel.php";
		$user_model = new \Model\UserModel(array("user_id"=>$user_id));
		$user_model->getDataFromDB();
        $roles = empty($user_model->role) ? array() : explode(";", $user_model->role);
        if(!in_array($role, $roles)){
            $roles[] = $role;
            $user_model->role = implode(";", $roles);
            return $user_model->updateToDBById($message);
        }else{
        	$message .= "该用户已是".$role;
        	return true;
        }
    }

    static public function convertRoleToClsName($role){
        return "Role".Role::convertRoleToFloderName($role);
    }
    static public function convertRoleToFloderName($role){
        $role = strtolower($role);
        $role_arr = explode("_", $role);
        $role = "";
        foreach ($role_arr as $role_seg) {
            $role .= ucfirst($role_seg);
        }
        return $role;
    }

    public function getShopId(){
    	return 0;
    }
    public function getPersonalInfo(){
    	return array();
    }
}
?>
