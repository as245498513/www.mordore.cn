<?php
/**
 * XXT脚本控制器
 */
namespace Common\Controller;
use Think\Controller;

class XxtbaseController extends Controller {

	public function __construct(){
		//api记录行为日志
		$param = I('');
		$param = serialize($param);
		$post_url = $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        $post_ip = get_client_ip();

		$add = array(
			'post_url'=>$post_url,
			'post_ip'=>$post_ip,
			'post_param'=>$param,
			'createtime'=>NOW_TIME
		);
		M('xxt_api_post_log')->add($add);
	}

	/**
	 * 检查设备授权
	 */
	public function check_auth_device(){
		//授权参数
		$unique_id = I('unique_id');
		$type = I('type');

		if(empty($unique_id)||empty($type)){
			$this->returnMsg(-2);
		}

		$where = array(
			'unique_id'=>$unique_id,
			'type'=>$type
		);
		//搜索当前设备授权是否有效
		$recent_dev = M('xxt_device_auth')->where($where)->order('overtime desc')->find();

		if(empty($recent_dev)){
			$this->returnMsg(-10000,'设备未授权');
		}
		else if($recent_dev['overtime'] < NOW_TIME){
			$this->returnMsg(-10001,'设备授权过期');
		}
	}

	/**
	 * json格式返回信息
	 * @return [type] [description]
	 */
	public function ajaxReturn($return){
		echo json_encode($return);
		exit();
	}

	public function returnMsg($code,$message=null,$data=null){
		//常用
		switch ($code) {
			case -2:
				$d_message = "参数错误";
				break;
			case -1:
				$d_message = "失败";
				break;
			case 0:
				$d_message = "成功";
				break;

			default:
				# code...
				break;
		}
		$message = $message ? $message : $d_message;

		$return = array(
			'code'=>$code,
			'message'=>$message,
			'data'=>$data,
		);
		$this->ajaxReturn($return);
	}
}