<?php
/**
 * XXT脚本控制器
 */
namespace Common\Controller;
use Think\Controller;

class MedbaseController extends Controller {
	protected $wx_user_id;
	public function __construct(){
		 $this->wx_user_id = $this->check_auth();

	}

	/**
	 * 检查微信授权
	 */
	public function check_auth(){
		//授权参数
		$token = I('token');

		if(empty($token)){
			$this->returnMsg(-2);
		}

		$where = array(
			'token'=>$token,
		);

		//查询当前微信是否授权
		$user_info = M('med_wx_users')->field('id')->where($where)->find();

		if(empty($user_info)){
			$this->returnMsg(-1,'未授权');
		}else{
             return $user_info['id'];
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