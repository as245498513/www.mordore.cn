<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
/**
 * 数据分析
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class DataAnalysisController extends AdminbaseController {
    /**
     * 统计每台设备的账号存活率
     */
    public function alive_percent_list(){
        $regist_log_model = M('xxt_script_register_log');
        $dev_name = I('dev_name');
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
        if($dev_name){
            $where['dev_name'] = array('like','%'.$dev_name.'%');
        }
        //筛选出检测过的数据
        $where['check_up_num'] = array('gt',0);

        $count = $regist_log_model->field('dev_name')->where($where)->group('dev_name')->select();
        $page = $this->page(count($count), 15);
        //设备列表
        $dev_name_list = $regist_log_model->field('dev_name')->where($where)->group('dev_name')->limit($page->firstRow, $page->listRows)->select();
        foreach($dev_name_list as $key=>$value){
            $dev_name_list[$key]['id'] = $key+1;
            //注册总数
            $where['dev_name'] = $value['dev_name'];
            $regist_total = M('xxt_script_register_log')->where($where)->count();
            $dev_name_list[$key]['retist_total'] = $regist_total;

            //存活总数
            $where2['account_status'] = 1;
            $alive_where = array_merge($where, $where2);
            $alive_total = M('xxt_script_register_log')->where($alive_where)->count();
            $dev_name_list[$key]['alive_total'] = $alive_total;

            //存活率
            $dev_name_list[$key]['alive_percent'] =  sprintf("%.2f",($alive_total/$regist_total*100))."%";
        }
        $this->assign("start_time",$start_time);
        $this->assign("end_time",$end_time);
        $this->assign("list",$dev_name_list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }


    /**
     * 所有账号统计表
     */
}