<?php
/*任务模块*/
namespace User\Controller;
use Common\Controller\MemberbaseController;

class TaskController extends MemberbaseController {

    /**
     *朋友圈点赞任务设置
     *列表页面
     */
    public function moments_like_list(){
        $moments_like_model = M('WxTaskMomentsLike');
        $device_group = M('MemberDeviceGroups');
        $user_id = sp_get_current_member_id();
        //参数
        $start_time = I('start_time');
        $end_time   = I('end_time');

        //时间筛选
        if($start_time){
            $start_time_st =  strtotime($start_time);
            $where['createtime'] = array('egt',$start_time_st);
        }
        if($end_time){
            $end_time_st =  strtotime($end_time)+60;
            $where['createtime'] = array('elt',$end_time_st);
        }
        if($start_time&&$end_time){
            $start_time_st =  strtotime($start_time);
            $end_time_st =  strtotime($end_time)+60;
            $where['createtime'] = array('between',array($start_time_st,$end_time_st));
        }
        $where['user_id'] = $user_id;


        $count = $moments_like_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $moments_like_model->field('id,like_amount,is_delay,delay_cycle,async,device_group_ids,createtime')->where($where)->order('createtime asc') ->limit($page->firstRow, $page->listRows)->select();
        foreach($list as $key=>$value){
            if($value['is_delay']==1){
                $list[$key]['plan_time'] =  $value['createtime']+$value['async']*60;
            }else{
                $list[$key]['plan_time'] = $value['createtime'];
            }
            $device_group_ids = unserialize($value['device_group_ids']);
            $len = count($device_group_ids);
            $in_str = '';
            $group_where['user_id'] = $user_id;
            foreach($device_group_ids as $k=>$v){
                if($key!=($len-1)){
                    $in_str .= $v.',';
                }else{
                    $in_str .= $v;
                }
            }
            if($len>1){
                $group_where['id'] = array('in',$in_str);
            }else if($len==1){
                $group_where['id'] = array('eq',$device_group_ids[0]);
            }else if($len==0){
                $group_where['id'] = array('eq',0);
            }
            $group_where['user_id'] = $user_id;
            $device_list = $device_group->field('id,name')->where($group_where)->select();
            $str = "";
            $i=0;
            foreach($device_list as $k0=>$v0){
                $i++;
                $str.=$i.'、'.$v0['name'].'；';
            }
            $list[$key]['device_group_list_str'] = $str;
            //清除特定元素
            unset($list[$key]['device_group_ids']);
        }
        $this->assign("list",$list);
        $this->assign("start_time",$start_time);
        $this->assign("end_time",$end_time?$end_time:date('Y-m-d H:i',NOW_TIME));
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     *朋友圈点赞任务设置
     *添加页面
     */
    public function moments_like_add(){
        $device_group = M('MemberDeviceGroups');
        $user_id = sp_get_current_member_id();
        $where = array(
            'user_id'=>$user_id,
        );
        $device_group_list = $device_group->where($where)->select();

        $this->assign('device_group_list',$device_group_list);
        $this->display();
    }

    /**
     *朋友圈点赞任务设置
     *添加提交
     */
    public function moments_like_add_post(){
        $moments_like_model = M('WxTaskMomentsLike');
        $user_id = sp_get_current_member_id();
        if(IS_POST){
            if($moments_like_model->create()!=false){
                $like_amount = I('like_amount');
                $is_delay = I('is_delay');
                $async = I('async',0);
                $device_group_ids = I('device_group_ids');
                $edit_id = I('edit_id');
                if(empty($like_amount)||empty($device_group_ids)){
                    $this->error("内容不能为空");
                }
                //新增
                if(!$edit_id){
                    $add = array(
                        'like_amount'=>$like_amount,
                        'is_delay'=>$is_delay,
                        'async'=>$async,
                        'device_group_ids'=>serialize($device_group_ids),
                        'user_id'=>$user_id,
                        'createtime'=>NOW_TIME
                    );
                    $rs = $moments_like_model->add($add);
                    if(!$rs){
                        $this->error("添加失败");
                    }else{
                        $this->success("添加成功",U('moments_like_list'));
                    }
                }else{
                    //编辑
                    $save = array(
                        'like_amount'=>$like_amount,
                        'is_delay'=>$is_delay,
                        'async'=>$async,
                        'device_group_ids'=>serialize($device_group_ids),
                    );
                    $rs = $moments_like_model->where(array('id'=>$edit_id))->save($save);
                    if(!$rs){
                        $this->error("编辑失败");
                    }else{
                        $this->success("编辑成功",U('moments_like_list'));
                    }
                }
            }else{
                $this->error($moments_like_model->getError());
            }
        }
    }

    /**
     *朋友圈点赞任务设置
     *编辑
     */
    public function moments_like_edit(){
        //参数
        $id = I('id');
        $device_group = M('MemberDeviceGroups');
        $moments_like_model = M('WxTaskMomentsLike');
        $user_id = sp_get_current_member_id();
        $where = array(
            'user_id'=>$user_id,
        );
        $device_group_list = $device_group->where($where)->select();
        $info = $moments_like_model->where(array('id'=>$id))->find();

        //查询是否已过了计划执行时间,过了不能编辑更改
        if($info['is_delay']==1){
            $plan_time =  $info['createtime']+$info['async']*60;
        }else{
            $plan_time =  $info['createtime'];
        }

        if(NOW_TIME>=$plan_time){
            $this->error("任务已执行,不能更改");
        }

        $checked_ids = unserialize($info['device_group_ids']);
        $in_str = '';
        $len = count($checked_ids);
        $group_where['user_id'] = $user_id;
        foreach($checked_ids as $k=>$v){
            if($k!=($len-1)){
                $in_str .= $v.',';
            }else{
                $in_str .= $v;
            }
        }
        $info['device_group_list'] = $device_group_list;
        $info['checked_ids'] = $in_str;
        $this->assign('info',$info);
        $this->display('moments_like_add');
    }

    /**
     *朋友圈点赞任务设置
     *删除
     */
    public function moments_like_del_post(){
        $ids = I('ids');
        $moments_like_model = M('WxTaskMomentsLike');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $moments_like_model->where(array('id'=>$value))->delete();
            if($del_rs){
                $rs++;
            }
        }
        if($rs>0){
            $return  = array('code'=>0,'message'=>'删除成功','data'=>$rs);
        }else{
            $return  = array('code'=>-1,'message'=>'删除失败','data'=>$rs);
        }
        $this->ajaxReturn($return);
    }

    /**
     *朋友圈图文发布任务设置
     *列表页面
     */
    public function moments_imgtxt_list(){
        $moments_imgtxt_model = M('WxTaskMomentsImgtxt');
        $device_group = M('MemberDeviceGroups');
        $source_model = M('WxSourceMomentsImgtxt');

        $user_id = sp_get_current_member_id();
        //参数
        $start_time = I('start_time');
        $end_time   = I('end_time');

        //时间筛选
        if($start_time){
            $start_time_st =  strtotime($start_time);
            $where['createtime'] = array('egt',$start_time_st);
        }
        if($end_time){
            $end_time_st =  strtotime($end_time)+60;
            $where['createtime'] = array('elt',$end_time_st);
        }
        if($start_time&&$end_time){
            $start_time_st =  strtotime($start_time);
            $end_time_st =  strtotime($end_time)+60;
            $where['createtime'] = array('between',array($start_time_st,$end_time_st));
        }
        $where['user_id'] = $user_id;


        $count = $moments_imgtxt_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $moments_imgtxt_model->field('id,source_ids,is_remind,is_delay,delay_cycle,async,device_group_ids,createtime')->where($where)->order('createtime asc') ->limit($page->firstRow, $page->listRows)->select();
        foreach($list as $key=>$value){
            if($value['is_delay']==1){
                $list[$key]['plan_time'] =  $value['createtime']+$value['async']*60;
            }else{
                $list[$key]['plan_time'] = $value['createtime'];
            }

            //素材组
            $source_ids = $value['source_ids'];
            $source_ids = explode(",",$source_ids);

            $source_len = count($source_ids);
            if($source_len==1){
                  $source_where['id'] = $source_ids[0];
            }else if($source_len>1){
                $source_where['id'] = array('in',$source_ids);
            }else if($source_len==0){
                $source_where['id'] = 0;
            }
            $source_list = $source_model->field('id,title')->where($source_where)->select();
            $source_str = "";
            $j=0;
            foreach($source_list as $k1=>$v1){
                $j++;
                $source_str.=$j.'、'.$v1['title'].'；';
            }
            $list[$key]['source_list_str'] = $source_str;

            //设备分组
            $device_group_ids = unserialize($value['device_group_ids']);
            $len = count($device_group_ids);
            $in_str = '';
            $group_where['user_id'] = $user_id;
            foreach($device_group_ids as $k=>$v){
                if($key!=($len-1)){
                    $in_str .= $v.',';
                }else{
                    $in_str .= $v;
                }
            }
            if($len>1){
                $group_where['id'] = array('in',$in_str);
            }else if($len==1){
                $group_where['id'] = array('eq',$device_group_ids[0]);
            }else if($len==0){
                $group_where['id'] = array('eq',0);
            }
            $group_where['user_id'] = $user_id;
            $device_list = $device_group->field('id,name')->where($group_where)->select();
            $str = "";
            $i=0;
            foreach($device_list as $k0=>$v0){
                $i++;
                $str.=$i.'、'.$v0['name'].'；';
            }
            $list[$key]['device_group_list_str'] = $str;
            //清除特定元素
            unset($list[$key]['device_group_ids']);
        }
        $this->assign("list",$list);
        $this->assign("start_time",$start_time);
        $this->assign("end_time",$end_time?$end_time:date('Y-m-d H:i',NOW_TIME));
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     *朋友圈图文发布任务设置
     *添加页面
     */
    public function moments_imgtxt_add(){
        $device_group = M('MemberDeviceGroups');
        $user_id = sp_get_current_member_id();
        $where = array(
            'user_id'=>$user_id,
        );
        $device_group_list = $device_group->where($where)->select();

        $this->assign('device_group_list',$device_group_list);
        $this->display();
    }

    /**
     *朋友圈图文发布任务设置
     *添加提交
     */
    public function moments_imgtxt_add_post(){
        $moments_imgtxt_model = M('WxTaskMomentsImgtxt');
        $user_id = sp_get_current_member_id();
        if(IS_POST){
            if($moments_imgtxt_model->create()!=false){
                $is_remind = I('is_remind');
                $source_ids = I('source_ids');
                $is_delay = I('is_delay');
                $async = I('async',0);
                $device_group_ids = I('device_group_ids');
                $edit_id = I('edit_id');
                if(empty($source_ids)||empty($device_group_ids)){
                    $this->error("内容不能为空");
                }
                //新增
                if(!$edit_id){
                    $add = array(
                        'is_remind'=>$is_remind,
                        'source_ids'=>$source_ids,
                        'is_delay'=>$is_delay,
                        'async'=>$async,
                        'device_group_ids'=>serialize($device_group_ids),
                        'user_id'=>$user_id,
                        'createtime'=>NOW_TIME
                    );
                    $rs = $moments_imgtxt_model->add($add);
                    if(!$rs){
                        $this->error("添加失败");
                    }else{
                        $this->success("添加成功",U('moments_imgtxt_list'));
                    }
                }else{
                    //编辑
                    $save = array(
                        'is_remind'=>$is_remind,
                        'source_ids'=>$source_ids,
                        'is_delay'=>$is_delay,
                        'async'=>$async,
                        'device_group_ids'=>serialize($device_group_ids),
                    );
                    $rs = $moments_imgtxt_model->where(array('id'=>$edit_id))->save($save);
                    if(!$rs){
                        $this->error("编辑失败");
                    }else{
                        $this->success("编辑成功",U('moments_imgtxt_list'));
                    }
                }
            }else{
                $this->error($moments_imgtxt_model->getError());
            }
        }
    }

    /**
     *朋友圈图文发布任务设置
     *编辑
     */
    public function moments_imgtxt_edit(){
        //参数
        $id = I('id');
        $device_group = M('MemberDeviceGroups');
        $moments_imgtxt_model = M('WxTaskMomentsImgtxt');
        $source_model = M('WxSourceMomentsImgtxt');

        $user_id = sp_get_current_member_id();
        $where = array(
            'user_id'=>$user_id,
        );
        $device_group_list = $device_group->where($where)->select();
        $info = $moments_imgtxt_model->where(array('id'=>$id))->find();

        //查询是否已过了计划执行时间,过了不能编辑更改
        if($info['is_delay']==1){
            $plan_time =  $info['createtime']+$info['async']*60;
        }else{
            $plan_time =  $info['createtime'];
        }

        if(NOW_TIME>=$plan_time){
            $this->error("任务已执行,不能更改");
        }

        //设备组
        $checked_ids = unserialize($info['device_group_ids']);
        $in_str = '';
        $len = count($checked_ids);
        $group_where['user_id'] = $user_id;
        foreach($checked_ids as $k=>$v){
            if($k!=($len-1)){
                $in_str .= $v.',';
            }else{
                $in_str .= $v;
            }
        }

        //素材组
        $source_ids = $info['source_ids'];
        $source_ids = explode(",",$source_ids);

        $source_len = count($source_ids);
        if($source_len==1){
            $source_where['id'] = $source_ids[0];
        }else if($source_len>1){
            $source_where['id'] = array('in',$source_ids);
        }else if($source_len==0){
            $source_where['id'] = 0;
        }
        $source_list = $source_model->field('id,title')->where($source_where)->select();

        $info['source_list'] = $source_list;

        $info['device_group_list'] = $device_group_list;
        $info['checked_ids'] = $in_str;

        $this->assign('info',$info);
        $this->display('moments_imgtxt_add');
    }

    /**
     *朋友圈图文发布任务设置
     *删除
     */
    public function moments_imgtxt_del_post(){
        $ids = I('ids');
        $moments_imgtxt_model = M('WxTaskMomentsImgtxt');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $moments_imgtxt_model->where(array('id'=>$value))->delete();
            if($del_rs){
                $rs++;
            }
        }
        if($rs>0){
            $return  = array('code'=>0,'message'=>'删除成功','data'=>$rs);
        }else{
            $return  = array('code'=>-1,'message'=>'删除失败','data'=>$rs);
        }
        $this->ajaxReturn($return);
    }


    /**
     *朋友圈评论任务设置
     *列表页面
     */
    public function moments_comment_list(){
        $moments_comment_model = M('WxTaskMomentsComment');
        $device_group = M('MemberDeviceGroups');
        $source_model = M('WxSourceMomentsComments');
        $user_id = sp_get_current_member_id();

        //参数
        $start_time = I('start_time');
        $end_time   = I('end_time');

        //时间筛选
        if($start_time){
            $start_time_st =  strtotime($start_time);
            $where['createtime'] = array('egt',$start_time_st);
        }
        if($end_time){
            $end_time_st =  strtotime($end_time)+60;
            $where['createtime'] = array('elt',$end_time_st);
        }
        if($start_time&&$end_time){
            $start_time_st =  strtotime($start_time);
            $end_time_st =  strtotime($end_time)+60;
            $where['createtime'] = array('between',array($start_time_st,$end_time_st));
        }
        $where['user_id'] = $user_id;


        $count = $moments_comment_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $moments_comment_model->field('id,comment_amount,source_ids,is_delay,delay_cycle,async,device_group_ids,createtime')->where($where)->order('createtime asc') ->limit($page->firstRow, $page->listRows)->select();
        foreach($list as $key=>$value){
            if($value['is_delay']==1){
                $list[$key]['plan_time'] =  $value['createtime']+$value['async']*60;
            }else{
                $list[$key]['plan_time'] = $value['createtime'];
            }

            //素材组
            $source_ids = $value['source_ids'];
            $source_ids = explode(",",$source_ids);

            $source_len = count($source_ids);
            if($source_len==1){
                $source_where['id'] = $source_ids[0];
            }else if($source_len>1){
                $source_where['id'] = array('in',$source_ids);
            }else if($source_len==0){
                $source_where['id'] = 0;
            }
            $source_list = $source_model->field('id,title')->where($source_where)->select();
            $source_str = "";
            $j=0;
            foreach($source_list as $k1=>$v1){
                $j++;
                $source_str.=$j.'、'.$v1['title'].'；';
            }
            $list[$key]['source_list_str'] = $source_str;

            //设备分组
            $device_group_ids = unserialize($value['device_group_ids']);
            $len = count($device_group_ids);
            $in_str = '';
            $group_where['user_id'] = $user_id;
            foreach($device_group_ids as $k=>$v){
                if($key!=($len-1)){
                    $in_str .= $v.',';
                }else{
                    $in_str .= $v;
                }
            }
            if($len>1){
                $group_where['id'] = array('in',$in_str);
            }else if($len==1){
                $group_where['id'] = array('eq',$device_group_ids[0]);
            }else if($len==0){
                $group_where['id'] = array('eq',0);
            }
            $group_where['user_id'] = $user_id;
            $device_list = $device_group->field('id,name')->where($group_where)->select();
            $str = "";
            $i=0;
            foreach($device_list as $k0=>$v0){
                $i++;
                $str.=$i.'、'.$v0['name'].'；';
            }
            $list[$key]['device_group_list_str'] = $str;
            //清除特定元素
            unset($list[$key]['device_group_ids']);
        }
        $this->assign("list",$list);
        $this->assign("start_time",$start_time);
        $this->assign("end_time",$end_time?$end_time:date('Y-m-d H:i',NOW_TIME));
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     *朋友圈评论任务设置
     *添加页面
     */
    public function moments_comment_add(){
        $device_group = M('MemberDeviceGroups');
        $user_id = sp_get_current_member_id();
        $where = array(
            'user_id'=>$user_id,
        );
        $device_group_list = $device_group->where($where)->select();

        $this->assign('device_group_list',$device_group_list);
        $this->display();
    }

    /**
     *朋友圈评论任务设置
     *添加提交
     */
    public function moments_comment_add_post(){
        $moments_comment_model = M('WxTaskMomentsComment');
        $user_id = sp_get_current_member_id();
        if(IS_POST){
            if($moments_comment_model->create()!=false){
                $comment_amount = I('comment_amount');
                $source_ids = I('source_ids');
                $is_delay = I('is_delay');
                $async = I('async',0);
                $device_group_ids = I('device_group_ids');
                $edit_id = I('edit_id');
                if(empty($source_ids)||empty($device_group_ids)){
                    $this->error("内容不能为空");
                }
                //新增
                if(!$edit_id){
                    $add = array(
                        'comment_amount'=>$comment_amount,
                        'source_ids'=>$source_ids,
                        'is_delay'=>$is_delay,
                        'async'=>$async,
                        'device_group_ids'=>serialize($device_group_ids),
                        'user_id'=>$user_id,
                        'createtime'=>NOW_TIME
                    );
                    $rs = $moments_comment_model->add($add);
                    if(!$rs){
                        $this->error("添加失败");
                    }else{
                        $this->success("添加成功",U('moments_comment_list'));
                    }
                }else{
                    //编辑
                    $save = array(
                        'comment_amount'=>$comment_amount,
                        'source_ids'=>$source_ids,
                        'is_delay'=>$is_delay,
                        'async'=>$async,
                        'device_group_ids'=>serialize($device_group_ids),
                    );
                    $rs = $moments_comment_model->where(array('id'=>$edit_id))->save($save);
                    if(!$rs){
                        $this->error("编辑失败");
                    }else{
                        $this->success("编辑成功",U('moments_comment_list'));
                    }
                }
            }else{
                $this->error($moments_comment_model->getError());
            }
        }
    }

    /**
     *朋友圈评论任务设置
     *编辑
     */
    public function moments_comment_edit(){
        //参数
        $id = I('id');
        $device_group = M('MemberDeviceGroups');
        $moments_comment_model = M('WxTaskMomentsComment');
        $source_model = M('WxSourceMomentsComments');

        $user_id = sp_get_current_member_id();
        $where = array(
            'user_id'=>$user_id,
        );
        $device_group_list = $device_group->where($where)->select();
        $info = $moments_comment_model->where(array('id'=>$id))->find();

        //查询是否已过了计划执行时间,过了不能编辑更改
        if($info['is_delay']==1){
            $plan_time =  $info['createtime']+$info['async']*60;
        }else{
            $plan_time =  $info['createtime'];
        }

        if(NOW_TIME>=$plan_time){
            $this->error("任务已执行,不能更改");
        }

        //设备组
        $checked_ids = unserialize($info['device_group_ids']);
        $in_str = '';
        $len = count($checked_ids);
        $group_where['user_id'] = $user_id;
        foreach($checked_ids as $k=>$v){
            if($k!=($len-1)){
                $in_str .= $v.',';
            }else{
                $in_str .= $v;
            }
        }

        //素材组
        $source_ids = $info['source_ids'];
        $source_ids = explode(",",$source_ids);

        $source_len = count($source_ids);
        if($source_len==1){
            $source_where['id'] = $source_ids[0];
        }else if($source_len>1){
            $source_where['id'] = array('in',$source_ids);
        }else if($source_len==0){
            $source_where['id'] = 0;
        }
        $source_list = $source_model->field('id,title')->where($source_where)->select();

        $info['source_list'] = $source_list;

        $info['device_group_list'] = $device_group_list;
        $info['checked_ids'] = $in_str;

        $this->assign('info',$info);
        $this->display('moments_comment_add');
    }

    /**
     *朋友圈评论任务设置
     *删除
     */
    public function moments_comment_del_post(){
        $ids = I('ids');
        $moments_comment_model = M('WxTaskMomentsComment');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $moments_comment_model->where(array('id'=>$value))->delete();
            if($del_rs){
                $rs++;
            }
        }
        if($rs>0){
            $return  = array('code'=>0,'message'=>'删除成功','data'=>$rs);
        }else{
            $return  = array('code'=>-1,'message'=>'删除失败','data'=>$rs);
        }
        $this->ajaxReturn($return);
    }


    /**
     *分享链接任务设置
     *列表页面
     */
    public function moments_shareurl_list(){
        $moments_shareurl_model = M('WxTaskMomentsShareurl');
        $device_group = M('MemberDeviceGroups');
        $source_model = M('WxSourceShareurl');

        $user_id = sp_get_current_member_id();
        //参数
        $start_time = I('start_time');
        $end_time   = I('end_time');

        //时间筛选
        if($start_time){
            $start_time_st =  strtotime($start_time);
            $where['createtime'] = array('egt',$start_time_st);
        }
        if($end_time){
            $end_time_st =  strtotime($end_time)+60;
            $where['createtime'] = array('elt',$end_time_st);
        }
        if($start_time&&$end_time){
            $start_time_st =  strtotime($start_time);
            $end_time_st =  strtotime($end_time)+60;
            $where['createtime'] = array('between',array($start_time_st,$end_time_st));
        }
        $where['user_id'] = $user_id;


        $count = $moments_shareurl_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $moments_shareurl_model->field('id,content,source_ids,is_delay,delay_cycle,async,device_group_ids,createtime')->where($where)->order('createtime asc') ->limit($page->firstRow, $page->listRows)->select();
        foreach($list as $key=>$value){
            if($value['is_delay']==1){
                $list[$key]['plan_time'] =  $value['createtime']+$value['async']*60;
            }else{
                $list[$key]['plan_time'] = $value['createtime'];
            }

            //素材组
            $source_ids = $value['source_ids'];
            $source_ids = explode(",",$source_ids);

            $source_len = count($source_ids);
            if($source_len==1){
                $source_where['id'] = $source_ids[0];
            }else if($source_len>1){
                $source_where['id'] = array('in',$source_ids);
            }else if($source_len==0){
                $source_where['id'] = 0;
            }
            $source_list = $source_model->field('id,title')->where($source_where)->select();
            $source_str = "";
            $j=0;
            foreach($source_list as $k1=>$v1){
                $j++;
                $source_str.=$j.'、'.$v1['title'].'；';
            }
            $list[$key]['source_list_str'] = $source_str;

            //设备分组
            $device_group_ids = unserialize($value['device_group_ids']);
            $len = count($device_group_ids);
            $in_str = '';
            $group_where['user_id'] = $user_id;
            foreach($device_group_ids as $k=>$v){
                if($key!=($len-1)){
                    $in_str .= $v.',';
                }else{
                    $in_str .= $v;
                }
            }
            if($len>1){
                $group_where['id'] = array('in',$in_str);
            }else if($len==1){
                $group_where['id'] = array('eq',$device_group_ids[0]);
            }else if($len==0){
                $group_where['id'] = array('eq',0);
            }
            $group_where['user_id'] = $user_id;
            $device_list = $device_group->field('id,name')->where($group_where)->select();
            $str = "";
            $i=0;
            foreach($device_list as $k0=>$v0){
                $i++;
                $str.=$i.'、'.$v0['name'].'；';
            }
            $list[$key]['device_group_list_str'] = $str;
            //清除特定元素
            unset($list[$key]['device_group_ids']);
        }
        $this->assign("list",$list);
        $this->assign("start_time",$start_time);
        $this->assign("end_time",$end_time?$end_time:date('Y-m-d H:i',NOW_TIME));
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     *分享链接任务设置
     *添加页面
     */
    public function moments_shareurl_add(){
        $device_group = M('MemberDeviceGroups');
        $user_id = sp_get_current_member_id();
        $where = array(
            'user_id'=>$user_id,
        );
        $device_group_list = $device_group->where($where)->select();

        $this->assign('device_group_list',$device_group_list);
        $this->display();
    }

    /**
     *分享链接任务设置
     *添加提交
     */
    public function moments_shareurl_add_post(){
        $moments_shareurl_model = M('WxTaskMomentsShareurl');
        $user_id = sp_get_current_member_id();
        if(IS_POST){
            if($moments_shareurl_model->create()!=false){
                $content = I('content');
                $source_ids = I('source_ids');
                $is_delay = I('is_delay');
                $async = I('async',0);
                $device_group_ids = I('device_group_ids');
                $edit_id = I('edit_id');
                if(empty($source_ids)||empty($device_group_ids)){
                    $this->error("内容不能为空");
                }
                //新增
                if(!$edit_id){
                    $add = array(
                        'content'=>$content,
                        'source_ids'=>$source_ids,
                        'is_delay'=>$is_delay,
                        'async'=>$async,
                        'device_group_ids'=>serialize($device_group_ids),
                        'user_id'=>$user_id,
                        'createtime'=>NOW_TIME
                    );
                    $rs = $moments_shareurl_model->add($add);
                    if(!$rs){
                        $this->error("添加失败");
                    }else{
                        $this->success("添加成功",U('moments_shareurl_list'));
                    }
                }else{
                    //编辑
                    $save = array(
                        'content'=>$content,
                        'source_ids'=>$source_ids,
                        'is_delay'=>$is_delay,
                        'async'=>$async,
                        'device_group_ids'=>serialize($device_group_ids),
                    );
                    $rs = $moments_shareurl_model->where(array('id'=>$edit_id))->save($save);
                    if(!$rs){
                        $this->error("编辑失败");
                    }else{
                        $this->success("编辑成功",U('moments_shareurl_list'));
                    }
                }
            }else{
                $this->error($moments_shareurl_model->getError());
            }
        }
    }

    /**
     *分享链接任务设置
     *编辑
     */
    public function moments_shareurl_edit(){
        //参数
        $id = I('id');
        $device_group = M('MemberDeviceGroups');
        $moments_shareurl_model = M('WxTaskMomentsShareurl');
        $source_model = M('WxSourceShareurl');

        $user_id = sp_get_current_member_id();
        $where = array(
            'user_id'=>$user_id,
        );
        $device_group_list = $device_group->where($where)->select();
        $info = $moments_shareurl_model->where(array('id'=>$id))->find();

        //查询是否已过了计划执行时间,过了不能编辑更改
        if($info['is_delay']==1){
            $plan_time =  $info['createtime']+$info['async']*60;
        }else{
            $plan_time =  $info['createtime'];
        }

        if(NOW_TIME>=$plan_time){
            $this->error("任务已执行,不能更改");
        }

        //设备组
        $checked_ids = unserialize($info['device_group_ids']);
        $in_str = '';
        $len = count($checked_ids);
        $group_where['user_id'] = $user_id;
        foreach($checked_ids as $k=>$v){
            if($k!=($len-1)){
                $in_str .= $v.',';
            }else{
                $in_str .= $v;
            }
        }

        //素材组
        $source_ids = $info['source_ids'];
        $source_ids = explode(",",$source_ids);

        $source_len = count($source_ids);
        if($source_len==1){
            $source_where['id'] = $source_ids[0];
        }else if($source_len>1){
            $source_where['id'] = array('in',$source_ids);
        }else if($source_len==0){
            $source_where['id'] = 0;
        }
        $source_list = $source_model->field('id,title')->where($source_where)->select();

        $info['source_list'] = $source_list;

        $info['device_group_list'] = $device_group_list;
        $info['checked_ids'] = $in_str;

        $this->assign('info',$info);
        $this->display('moments_shareurl_add');
    }

    /**
     *分享链接任务设置
     *删除
     */
    public function moments_shareurl_del_post(){
        $ids = I('ids');
        $moments_shareurl_model = M('WxTaskMomentsShareurl');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $moments_shareurl_model->where(array('id'=>$value))->delete();
            if($del_rs){
                $rs++;
            }
        }
        if($rs>0){
            $return  = array('code'=>0,'message'=>'删除成功','data'=>$rs);
        }else{
            $return  = array('code'=>-1,'message'=>'删除失败','data'=>$rs);
        }
        $this->ajaxReturn($return);
    }



    /**
     *筛选条件拼接表头
     */
    public function add_table_pre($where_arr,$pre){
        $result = array();
       foreach($where_arr as $key=>$value){
           $nkey = $pre.'.'.$key;
           $result[$nkey] = $value;
       }
        return $result;
    }
}