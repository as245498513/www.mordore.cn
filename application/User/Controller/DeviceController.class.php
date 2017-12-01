<?php
namespace User\Controller;
use Common\Controller\MemberbaseController;
class DeviceController extends MemberbaseController{

	protected $device_mod;

 	public function _initialize() {
        parent::_initialize();
       	$this->device_mod = M('member_device');
	}
	
	//分组管理
	public function index(){
		$user_id = sp_get_current_member_id();
		$where = array(
			'd.user_id'=>$user_id
		);
		$count = $this->device_mod->alias("d")->join("left join __MEMBER_DEVICE_GROUPS__ AS  dg on d.dev_group_id = dg.id")
		->where($where)->count();

        $page = $this->page($count, 20);

		$lists = $this->device_mod->alias("d")->join("left join __MEMBER_DEVICE_GROUPS__ AS  dg on d.dev_group_id = dg.id")
		->where($where)->limit($page->firstRow, $page->listRows)->field("d.id,dg.name,d.dev_name,d.imei,d.unique_id,d.state")->select();
       	
        $this->assign("page", $page->show('Admin'));
		$this->assign('lists',$lists);

		$this->display();
	}

	public function add(){
		$user_id = sp_get_current_member_id();
		$dev_group = M('member_device_groups')->where(array('user_id'=>$user_id))->field("id,name")->select();
		$this->assign('dev_group',$dev_group);
		$this->display();
	}

	public function add_post(){
		$dev_name = I('dev_name');
		$dev_group_id = I('dev_group_id');
		$imei = I('imei');
		$unique_id = I('unique_id');
		$state = I('state');
		$user_id = sp_get_current_member_id();
		if(empty($dev_name)){
			$this->error("名字不能为空");
		}
		if(empty($unique_id)){
			$this->error("唯一码不能为空");
		}
		if(empty($dev_group_id)){
			$this->error("分组不能为空");
		}
		$data = array(
			'user_id'=>$user_id,
			'dev_group_id'=>$dev_group_id,
			'dev_name'=>$dev_name,
			'imei'=>$imei,
			'unique_id'=>$unique_id,
			'state'=>$state,
			'createtime'=>NOW_TIME,
		);
		$ac = $this->device_mod->add($data);
		if($ac){
			$this->success("添加成功");
		}else{
			$this->error("添加失败");
		}
	}
	

	public function edit(){
		$user_id = sp_get_current_member_id();
		$dev_group = M('member_device_groups')->where(array('user_id'=>$user_id))->field("id,name")->select();
		$this->assign('dev_group',$dev_group);
		$id = I('id');
		$where = array(
			'id'=>$id,
			'user_id'=>$user_id,
		);
		$item = $this->device_mod->where($where)->find();
		$this->assign('item',$item);
		$this->display();
	}


	public function edit_post(){
		$dev_name = I('dev_name');
		$dev_group_id = I('dev_group_id');
		$imei = I('imei');
		$unique_id = I('unique_id');
		$state = I('state');
		$user_id = sp_get_current_member_id();
		if(empty($dev_name)){
			$this->error("名字不能为空");
		}
		if(empty($unique_id)){
			$this->error("唯一码不能为空");
		}
		if(empty($dev_group_id)){
			$this->error("分组不能为空");
		}
		$id = I('id');

		$where = array(
			'id'=>$id,
			'user_id'=>$user_id,
		);
		$id = $this->device_mod->where($where)->getField('id');
		if($id){
			$save = array(
				'dev_group_id'=>$dev_group_id,
				'dev_name'=>$dev_name,
				'imei'=>$imei,
				'unique_id'=>$unique_id,
				'state'=>$state,
			);
			$ac = $this->device_mod->where($where)->save($save);
			if($ac){
				$this->success("操作成功");
			}
		}
		$this->error("操作失败");
	}

	//更换设备状态
	public function change_state(){
		$id = I('id');
		$user_id = sp_get_current_member_id();
		$where = array(
			'id'=>$id,
			'user_id'=>$user_id,
		);
		$state = I('state');
		$save = array(
			'state'=>$state,
		);
		$ac = $this->device_mod->where($where)->save($save);
		if($ac){
			$this->success("操作成功");
		}else{
			$this->error("操作失败");
		}
	}

	public function del_post(){
		$id = I('id');
		$user_id = sp_get_current_member_id();
		$where = array(
			'id'=>$id,
			'user_id'=>$user_id,
		);
		$dgid = $this->device_mod->where($where)->getField('id');
		if($dgid){
			$mwhere = array(
				'user_id'=>$user_id,
				'dev_group_id'=>$dgid,
			);
			$count = M('member_device')->where($mwhere)->count();
			if($count){
				$this->error("该分组下有设备");
			}
			$ac = $this->device_mod->where($where)->delete();
			if($ac){
				$this->success("分组删除成功");
			}
		}

		$this->error("操作失败");
	}

}