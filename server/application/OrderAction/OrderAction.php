<?php
/**
 * OrderAction: 订单操作
 * @author   cywang <cywang@leqee.com>
 */

namespace application;
use Flight;
use Logger;

require_once __DIR__."/../Order.php";

abstract class OrderAction extends Basic{
    // 下单
    static public function cbPlaceOrderByResque($task_id){
        require_once "OrderAction_placeOrder.php";
        $action_data = Flight::request()->data->getData();
        $action_data['action_note'] = "new order";
        $action_ins = new OrderAction_placeOrder($task_id, $action_data);
        $action_ins->placeOrder();
    }

    //订单更新操作
    static private function parseUpdateActionList($order_id, $input_action, &$action_cls_list){
        $action_cls_list = array();
        switch ($input_action) {
            case 'setWorker':
                $action_cls_list = array("OrderAction_setWorker");
                break;
            case 'confirmPayment':
                $sql = "select order_status from order where order_id = '{$order_id}';";
                $order_status = Flight::db()->getOne($sql);
                if($order_status == 'TO_CHECK')
                    $action_cls_list[] = "OrderAction_confirmOrder";
                $action_cls_list[] = "OrderAction_confirmPayment";
                if($order_status == 'SERVICE_FINISHED')
                    $action_cls_list[] = "OrderAction_confirmService";
                break;
            case 'setoff':
                $action_cls_list = array("OrderAction_setoff");
                break;
            case 'arrive':
                $sql = "select service_type from order where order_id = '{$order_id}';";
                $service_type = Flight::db()->getOne($sql);
                if($service_type=='to_the_door')
                    $action_cls_list = array("OrderAction_workerArrive");
                else if($service_type=='to_the_shop')
                    $action_cls_list = array("OrderAction_customerArrive");
                else
                    ;
                break;
            case 'startService':
                $action_cls_list = array("OrderAction_startService");
                break;
            case 'finishService':
                $action_cls_list = array("OrderAction_finishService");
                break;
            case 'confirmService':
                $action_cls_list = array("OrderAction_confirmService");
                break;
            case 'comment':
                $action_cls_list = array("OrderAction_comment");
                break;
            case 'cancelOrder':
                $sql = "select pay_status from order where order_id = '{$order_id}';";
                $pay_status = Flight::db()->getOne($sql);
                $action_cls_list[] = "OrderAction_cancelOrder";
                if($pay_status == "PAID")
                    $action_cls_list[] = "OrderAction_applyRefund";
                break;
            case 'finishRefund':
                $action_cls_list = array("OrderAction_finishRefund");
                break;
            case 'addFee':
                $action_cls_list = array("OrderAction_addFee", "OrderAction_finishService");
                break;
            default:
                # code...
                break;
        }
        return !empty($action_cls_list);
    }
    static public function cbUpdateOrder($order_id){
        // check order
        if(!Flight::user()->isMyOrder($order_id)){
            Flight::sendRouteResult(false, null, "order[" . $order_id . "]不存在或不属于当前用户");
        }

        $data = Flight::request()->data->getData();
        Logger::getLogger("OrderAction")->debug("Data for update order");
        Logger::getLogger("OrderAction")->debug($data);

        //check action data
        if(!isset($data['action']) || empty($data['action']))
            Flight::sendRouteResult(false, null, "no action in data");

        // parse action
        $action_cls_list = array();
        if(!OrderAction::parseUpdateActionList($order_id, $data['action'], $action_cls_list))
            Flight::sendRouteResult(false, null, 'action['.$data['action'].']解析错误');

        // do actions
        unset($data['action']);
        $message = "";
        $success = OrderAction::DoActionList($order_id, $action_cls_list, $data, $message);
        Flight::sendRouteResult($success, null, $message);
    }


    //订单更新操作之付款确认(第三方支付回调调用)
    static public function confirmPayment($order_sn, $pay_from, $trans_id, $trans_status, $amount, $ori_data_in_json, &$message = ''){
        $action_cls_list = array();
        $order_id = Flight::db()->getOne("select order_id from order where order_sn = '{$order_sn}';");
        if($order_id > 0){
            $data = array(
                "method" => "ONLINE",
                "sub_method" => $pay_from,
                "out_trans_id" => $trans_id,
                "out_trans_status" => $trans_status,
                "amount" => $amount,
                "ori_data" => $ori_data_in_json
                );
            // parse action
            $action_cls_list = array();
            assert(OrderAction::parseUpdateActionList($order_id, "confirmPayment", $action_cls_list));

            // do actions
            return OrderAction::DoActionList($order_id, $action_cls_list, $data, $message);
        }else{
            return false;
        }
    }

    //订单更新操作之批量取消超时订单(定时任务调用)
    static public function cancelTimeoutOrders(){
        $action_cls_list = array();
        $sql = "select order_id from order where pay_status = 'TO_PAY' and order_status = 'TO_CHECK' and order_time < DATE_ADD(now(), INTERVAL -15 MINUTE) limit 10;";
        $order_ids = Flight::db()->getCol($sql);
        foreach ($order_ids as $order_id) {
            // parse action
            $action_cls_list = array();
            assert(OrderAction::parseUpdateActionList($order_id, "cancelOrder", $action_cls_list));
            // do actions
            $message = "";
            OrderAction::DoActionList($order_id, $action_cls_list, array(), $message);
            print_r($order_id.": ".$message);
        }
    }

    // 删除订单
    static public function cbDeleteOrder($order_id){
        // check order
        if(!Flight::user()->isMyOrder($order_id)){
            $success = false;
            $message = "order[" . $order_id . "]不存在或不属于当前用户";
        }else{
            $action_cls_list = array("OrderAction_deleteOrder");
            $message = "";
            $success = OrderAction::DoActionList($order_id, $action_cls_list, array(), $message);   
        }
        Flight::sendRouteResult($success, null, $message);
    }
    
    static private function DoActionList($order_id, $action_cls_list, $action_data, &$message){
        Flight::db()->start_transaction();
        foreach ($action_cls_list as $action_class_name) {
            require_once $action_class_name.".php";
            $action_class_name = "application\\".$action_class_name;
            assert(class_exists($action_class_name));
            $action_ins = new $action_class_name($order_id, $action_data);
            if(!$action_ins->doAction($message)){
                Flight::db()->rollback();
                return false;
            }
        }
        Flight::db()->commit();
        return true;
    }

    public function __construct($order_id, $action, $action_data){
        if($order_id > 0){
            $sql = "select order_id, worker_id, service_type as order_service_type, order_status, pay_method, pay_status from order where order_id = '{$order_id}'";
            $data = Flight::db()->getRow($sql);
            assert(!empty($data));
        }else{
            $data = array();
        }
        $data['action'] = $action;
        $data = array_merge($data, $action_data);
        parent::__construct($data);
    }
    abstract protected function getAllowedRoles();
    protected function getAllowedServiceTypes(){
        return array("to_the_door", "to_the_shop");
    }
    private function doAction(&$message){
        // check privilege
        $roles = Flight::user()->getRoles();
        if(!array_intersect($roles, $this->getAllowedRoles())){
            $message .= "当前用户无权限进行".$this->action."操作";
            return false;
        }
        //check service type
        if(!in_array($this->order_service_type, $this->getAllowedServiceTypes())){
            $message .= "当前服务类型".$this->order_service_type."无需进行".$this->action."操作";
            return false;
        }

        // check 数据完整性
        $lacked_data_list = $this->getLackedData();
        if(!empty($lacked_data_list)){
            $message .= "data不完整，缺少".implode("/", $lacked_data_list)."数据";
            return false;
        }

        return $this->doSubAction($message) && $this->record($message);
    }
    protected function getLackedData(){
        $data_needed = $this->getNecessaryData();
        $lacked_data_list = array();
        foreach ($data_needed as $data_name) {
            if(!isset($this->$data_name))
                $lacked_data_list[] = $data_name;
        }
        return $lacked_data_list;
    }
    protected function getNecessaryData(){
        return array();
    }
    protected function doSubAction(&$message){
        $message .= "基类action";
        return false;
    }
    protected function record(&$message){
        $sql = "select order_id, order_status, pay_status from order where order_id = '{$this->order_id}'";
        $data = Flight::db()->getRow($sql);
        $data['action'] = $this->action;
        $data['action_type'] = OrderAction::getType($this->action);
        $data['action_time'] = date("Y-m-d H:i:s");
        $data['action_note'] = isset($this->action_note) ? $this->action_note : "";
        $order_action_record = new Basic($data);
        return $order_action_record->saveToDB('order_action');
    }

    static public function getType($action){
        OrderAction::init();
        return isset(OrderAction::$status_mapping[$action]) ? OrderAction::$status_mapping[$action][0] : '未定义action[' . $action ."]";
    }

    static public function getName($action_type){
        OrderAction::init();
        return isset(OrderAction::$status_mapping[$action]) ? OrderAction::$status_mapping[$action][1] : '未定义action[' . $action ."]";
    }

    private static function init(){
        if(!isset(OrderAction::$status_mapping)){
            OrderAction::$status_mapping = array(
                "CUSTOMER_PLACE_ORDER" => array('ORDER', '生成订单'),
                "CUSTOMER_PAY_ORDER" => array('ORDER', '顾客付款'),
                "CUSTOMER_CANCEL" => array('ORDER', '顾客取消订单'),
                //...
                );
        }
    }
    private static $status_mapping;  


    static public function pay_refund($order_id, $type, $method, $sub_method, $out_trans_id, $out_trans_status, $amount, $ori_data, &$message){
        if(!in_array($type, array("PAY", "REFUND"))){
            $message .= "pay_type error[" . $type . "]";
            return false;
        }
        if(!in_array($method, array("ONLINE", "OFFLINE"))){
            $message .= "pay_method error[" . $method . "]";
            return false;
        }

        $sql = "SELECT pay_amount FROM neiru.`order` where order_id = '{$order_id}';";
        $pay_amount = Flight::db()->getOne($sql);

        $pay_refund_infos = array();
        Order::payRefundInfoOfOrders(array($order_id), $pay_refund_infos);
        if(isset($pay_refund_infos[$order_id])){
            $paid_summary = $pay_refund_infos[$order_id]['summary'];
        }else{
            $paid_summary = 0;
        }
        $to_amount = $pay_amount - $paid_summary;

        Logger::getLogger("Payment")->debug("Payment_weixin in pay_refund");
        Logger::getLogger("Payment")->debug($type." - ".$to_amount." - ".$amount);
        if($type == "PAY"){
            // 付款
            $pay_status = "PAID";
        }else{
            // 退款
            $pay_status = "REFUND_PASS_AND_SUCC";
            $to_amount = -$to_amount;
        }

        $amount = doubleval($amount);
        if(abs($to_amount-$amount)>0.001){
            $message .= "待付/退款金额" . $to_amount . "与当前金额" . $amount . "不一致";
            return false;
        }

        if($to_amount == 0){
            $message .= "待付/退款金额为0，无需付/退款";
            return false;
        }

        $pay_refund = new Basic(
            array(
                'order_id'=>$order_id, 'type'=>$type,
                'method'=>$method, 'sub_method'=>$sub_method, 'out_trans_id'=>$out_trans_id, 'out_trans_status'=>$out_trans_status,
                'amount'=>$amount, 'ori_data'=>$ori_data,
                'action_time'=>date('Y-m-d H:i:s')));
        Logger::getLogger("Payment")->debug($pay_refund);
        Logger::getLogger("Payment")->debug($pay_status);
        Logger::getLogger("Payment")->debug($message);
        return $pay_refund->saveToDB("neiru.order_pay_refund") && Order::updateOrderByID($order_id, array('pay_status'=> $pay_status));
    }
}


