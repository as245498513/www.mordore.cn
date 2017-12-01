<?php
namespace User\Controller;
use Common\Controller\MemberbaseController;
class DevicegroupController extends MemberbaseController{

	protected $device_groups_mod;

 	public function _initialize() {
        parent::_initialize();
       	$this->device_groups_mod = M('member_device_groups');
	}
	
	//分组管理
	public function index(){
		$user_id = sp_get_current_member_id();
		$where = array(
			'user_id'=>$user_id
		);
		$rows = $this->device_groups_mod->where($where)->select();

		$this->assign('rows',$rows);

		$this->display();
	}

	public function add_display(){

		$this->display();
	}

	public function add_post(){
		$name = I('name');
		$user_id = sp_get_current_member_id();
		if(empty($name)){
			$this->error("名字不能为空");
		}
		$data = array(
			'user_id'=>$user_id,
			'name'=>$name,
			'createtime'=>NOW_TIME,
		);
		$ac = $this->device_groups_mod->add($data);
		if($ac){
			$this->success("添加成功");
		}else{
			$this->error("添加失败");
		}
	}
	

	public function edit_display(){
		$user_id = sp_get_current_member_id();
		$id = I('id');
		$where = array(
			'id'=>$id,
			'user_id'=>$user_id,
		);
		$item = $this->device_groups_mod->where($where)->find();

		$this->assign('item',$item);
		$this->display();
	}


	public function edit_post(){
		$name = I('name');
		if(empty($name)){
			$this->error("名字不能为空");
		}
		$id = I('id');
		$user_id = sp_get_current_member_id();
		$where = array(
			'id'=>$id,
			'user_id'=>$user_id,
		);
		$id = $this->device_groups_mod->where($where)->getField('id');
		if($id){
			$save = array(
				'name'=>$name,
			);
			$ac = $this->device_groups_mod->where($where)->save($save);
			if($ac){
				$this->success("操作成功");
			}
		}
		$this->error("操作失败");
	}


	public function del_post(){
		$id = I('id');
		$user_id = sp_get_current_member_id();
		$where = array(
			'id'=>$id,
			'user_id'=>$user_id,
		);
		$dgid = $this->device_groups_mod->where($where)->getField('id');
		if($dgid){
			$mwhere = array(
				'user_id'=>$user_id,
				'dev_group_id'=>$dgid,
			);
			$count = M('member_device')->where($mwhere)->count();
			if($count){
				$this->error("该分组下有设备");
			}
			$ac = $this->device_groups_mod->where($where)->delete();
			if($ac){
				$this->success("分组删除成功");
			}
		}

		$this->error("操作失败");
	}

}