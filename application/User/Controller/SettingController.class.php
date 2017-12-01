<?php
namespace User\Controller;
use Common\Controller\MemberbaseController;
class SettingController extends MemberbaseController{

	
	public function password(){
		$this->display();
	}
	
	public function password_post(){
		if (IS_POST) {
			if(empty($_POST['old_password'])){
				$this->error("原始密码不能为空！");
			}
			if(empty($_POST['password'])){
				$this->error("新密码不能为空！");
			}
			$user_obj = D("Common/Members");
			$uid=sp_get_current_member_id();
			$admin=$user_obj->where(array("id"=>$uid))->find();
			$old_password=I('post.old_password');
			$password=I('post.password');
			if(sp_compare_password($old_password,$admin['user_pass'])){
				if($password==I('post.repassword')){
					if(sp_compare_password($password,$admin['user_pass'])){
						$this->error("新密码不能和原始密码相同！");
					}else{
						$data['user_pass']=sp_password($password);
						$data['id']=$uid;
						$r=$user_obj->save($data);
						if ($r!==false) {
							session('MEMBER_ID',null);
							$this->success("修改成功,3秒后重新登录","Public/login");
						} else {
							$this->error("修改失败！");
						}
					}
				}else{
					$this->error("密码输入不一致！");
				}
	
			}else{
				$this->error("原始密码不正确！");
			}
		}
	}
	
}