<?php
namespace User\Controller;

use Common\Controller\AdminbaseController;

class IndexadminController extends AdminbaseController {
    protected $members_model,$role_model,$role_user_model;

    public function _initialize() {
        parent::_initialize();
        $this->members_model = D("Common/Users");
        $this->role_model = D("Common/Role");
        $this->role_user_model = D("Common/RoleUser");
    }

    public function index(){
        $where=array();
        $request=I('request.');
        
        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }
        
        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $keyword_complex=array();
            $keyword_complex['user_login']  = array('like', "%$keyword%");
            $keyword_complex['user_nicename']  = array('like',"%$keyword%");
            $keyword_complex['user_email']  = array('like',"%$keyword%");
            $keyword_complex['_logic'] = 'or';
            $where['_complex'] = $keyword_complex;
        }
        

    	
    	$count=$this->members_model->where($where)->count();
    	$page = $this->page($count, 20);
    	
    	$list = $this->members_model
    	->where($where)
    	->order("create_time DESC")
    	->limit($page->firstRow . ',' . $page->listRows)
    	->select();
    	
    	$this->assign('list', $list);
    	$this->assign("page", $page->show('Admin'));
    	
    	$this->display();
    }
    
    public function ban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = $this->members_model->where(array("id"=>$id,"user_type"=>1))->setField('user_status',0);
    		if ($result) {
    			$this->success("会员拉黑成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员拉黑失败,会员不存在,或者是管理员！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
    
    public function cancelban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = $this->members_model->where(array("id"=>$id,"user_type"=>1))->setField('user_status',1);
    		if ($result) {
    			$this->success("会员启用成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员启用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }


    public function add(){
        $roles=$this->role_model->where(array('status' => 1))->order("id DESC")->select();
        $this->assign("roles",$roles);
        $this->display();
    }

    public function add_post(){
        if(IS_POST){
            if(!empty($_POST['role_id']) && is_array($_POST['role_id'])){
                $role_ids=$_POST['role_id'];
                unset($_POST['role_id']);
                if ($this->members_model->create()!==false) {
                    $result=$this->members_model->add();
                    if ($result!==false) {
                        
                        foreach ($role_ids as $role_id){
                            $this->role_user_model->add(array("role_id"=>$role_id,"user_id"=>$result));
                        }
                        $this->success("添加成功！", U("indexadmin/index"));
                    } else {
                        $this->error("添加失败！");
                    }
                } else {
                    $this->error($this->members_model->getError());
                }
            }else{
                $this->error("请为此用户指定角色！");
            }

        }
    }

    public function edit(){
        $id = I('get.id',0,'intval');
        $roles=$this->role_model->where(array('status' => 1))->order("id DESC")->select();
        $this->assign("roles",$roles);
        
        $role_ids=$this->role_user_model->where(array("user_id"=>$id))->getField("role_id",true);
        $this->assign("role_ids",$role_ids);

        $user=$this->members_model->where(array("id"=>$id))->find();
        $this->assign($user);
        $this->display();
    }

    public function edit_post(){
        if (IS_POST) {
            if(!empty($_POST['role_id']) && is_array($_POST['role_id'])){
                if(empty($_POST['user_pass'])){
                    unset($_POST['user_pass']);
                }
                $role_ids = I('post.role_id/a');
                unset($_POST['role_id']);
                if ($this->members_model->create()!==false) {
                    $result=$this->members_model->save();
                    if ($result!==false) {
                        $uid = I('post.id',0,'intval');
                        $this->role_user_model->where(array("user_id"=>$uid))->delete();
                        foreach ($role_ids as $role_id){
                            if(sp_get_current_admin_id() != 1 && $role_id == 1){
                                $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                            }
                            $this->role_user_model->add(array("role_id"=>$role_id,"user_id"=>$uid));
                        }
                        $this->success("保存成功！");
                    } else {
                        $this->error("保存失败！");
                    }
                } else {
                    $this->error($this->members_model->getError());
                }
            }else{
                $this->error("请为此用户指定角色！");
            }

        }
    }

    /**
     *  删除
     */
    public function delete(){
        $id = I('get.id',0,'intval');
        if($id==1){
            $this->error("最高管理员不能删除！");
        }

        if ($this->members_model->delete($id)!==false) {
            M("RoleUser")->where(array("user_id"=>$id))->delete();
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }
   
}
