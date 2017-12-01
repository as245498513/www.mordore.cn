<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
/**
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class ReportController extends AdminbaseController {
    /**
     * 音频收听记录报表
     */
    public function listen_history_list(){
        //参数
        $wx_user_name = I('wx_user_name');

        $start_time = I('start_time');
        $end_time   = I('end_time');

        $where = array();
        if($wx_user_name){
            $where['cmf_med_wx_users.wx_nickname'] = array('like','%'.$wx_user_name.'%');
        }

        //时间筛选
        if($start_time){
            $start_time_st =  strtotime($start_time);
            $where['cmf_med_listen_history.createtime'] = array('egt',$start_time_st);
        }
        if($end_time){
            $end_time_st =  strtotime($end_time)+60;
            $where['cmf_med_listen_history.createtime'] = array('elt',$end_time_st);
        }
        if($start_time&&$end_time){
            $start_time_st =  strtotime($start_time);
            $end_time_st =  strtotime($end_time)+60;
            $where['cmf_med_listen_history.createtime'] = array('between',array($start_time_st,$end_time_st));
        }

        $listen_history_model = M('med_listen_history');


        $count = $listen_history_model->join("cmf_med_wx_users ON cmf_med_wx_users.id = cmf_med_listen_history.user_id","left")->where($where)->count();
        $page = $this->page($count, 15);

        $list = $listen_history_model
                         ->field("cmf_med_listen_history.id,cmf_med_wx_users.wx_nickname,cmf_med_audio.audio_name,cmf_med_listen_history.my_listen_count listen_count,cmf_med_listen_history.createtime")
                         ->join("cmf_med_audio ON cmf_med_audio.id = cmf_med_listen_history.audio_id","left")
                         ->join("cmf_med_wx_users ON cmf_med_wx_users.id = cmf_med_listen_history.user_id","left")
                         ->where($where)
                         ->limit($page->firstRow, $page->listRows)
                         ->order("cmf_med_listen_history.createtime desc")
                         ->select();

        $this->assign("list",$list);
        $this->assign("wx_user_name",$wx_user_name);
        $this->assign("start_time",$start_time);
        $this->assign("end_time",$end_time?$end_time:date('Y-m-d H:i',NOW_TIME));
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

    /**
     * 微信用户信息报表
     */
    public function wx_user_list(){
        //参数
        $wx_nickname = I('wx_nickname');

        $start_time = I('start_time');
        $end_time   = I('end_time');

        $where = array();
        if($wx_nickname){
            $where['wx_nickname'] = array('like','%'.$wx_nickname.'%');
        }

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

        $wx_users_model = M('med_wx_users');


        $count = $wx_users_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $wx_users_model
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order("createtime desc")
            ->select();

        $this->assign("list",$list);
        $this->assign("wx_nickname",$wx_nickname);
        $this->assign("start_time",$start_time);
        $this->assign("end_time",$end_time?$end_time:date('Y-m-d H:i',NOW_TIME));
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     * 专辑收听数据报表
     */
    public function album_data_analysis(){
         //参数
         $album_name = I('album_name');

         $audio_album_model = M('med_audio_album');
         $album_history_model = M('med_album_listen_history');

         $where = array();
         if($album_name){
            $where['album_name'] = array('like','%'.$album_name.'%');
         }

        $count = $audio_album_model->where($where)->count();
        $page = $this->page($count, 15);

        $album_list = $audio_album_model->field("id,album_name,album_like_count")->where($where)->limit($page->firstRow, $page->listRows)->select();

        foreach($album_list as $k=>$v){
            $listen_count = $album_history_model->where(array('album_id'=>$v['id']))->sum("my_album_listen_count");
            $album_list[$k]['listen_count'] = $listen_count;
        }

        $this->assign("list",$album_list);
        $this->assign("album_name",$album_name);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

}