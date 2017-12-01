<?php
/**
 * Created by PhpStorm.
 * User: CMJ
 * Date: 2017/9/5
 * Time: 11:40
 */
namespace Api\Controller;
use Common\Controller\MedbaseController;
use Think\Controller;
use Think\Exception;

class MedController extends MedbaseController{

    /**
     * 获取音频列表
     * @interface 2.1
     * @return array returnMsg
     */
    public function audio_list(){
        //参数
        $type = I('type');
        $albumid = I('albumid');
        $keyword = I('keyword');
        $difficulty = intval(I('difficulty'));
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $start = ($page - 1) * $pagesize;
        $limit = "{$start},{$pagesize}";

        $user_id = $this->wx_user_id;

        $audio_model = M('med_audio');
        $audio_to_album_model = M('med_audio_to_album');
        $audio_album_model = M('med_audio_album');
        $album_follow_model = M('med_user_like_album');
        $difficulty_model = M('med_audio_difficulty');

        if(empty($type)){
            $this->returnMsg(-2);
        }
        $where = array();
        //返回的数据库字段
        $field = "
                  cmf_med_audio.id audio_id,
                  cmf_med_audio.audio_difficulty,
                  cmf_med_audio.audio_name,
                  cmf_med_audio.audio_url,
                  cmf_med_audio.audio_cover,
                  cmf_med_audio.audio_description,
                  cmf_med_audio.audio_listen_count,
                  IF(cmf_med_audio_like.user_id <> '',1,0) is_like
                  ";

        //参数检查
        switch($type){
            case 1:
                if(empty($albumid)){
                    $this->returnMsg(-2);
                }else{
                    $where['cmf_med_audio_to_album.album_id'] = $albumid;
                    //分页
                    $count = $audio_to_album_model->where($where)->count();
                    //音频列表
                    $audio_list = $audio_to_album_model
                        ->field($field)
                        ->join("cmf_med_audio ON cmf_med_audio.id = cmf_med_audio_to_album.audio_id","left")
                        ->join("cmf_med_audio_like ON cmf_med_audio_like.audio_id = cmf_med_audio.id and cmf_med_audio_like.user_id = {$user_id}","left")
                        ->where($where)->limit($limit)->order('cmf_med_audio.createtime desc')->select();
                }
                //专辑信息
                $album_info = $audio_album_model->field("album_name,album_cover,album_desc")->where(array('id'=>$albumid))->find();
                if($album_info['album_desc'] == ''){
                    $album_info['album_desc'] = '';
                }
                $album_info['album_cover'] = $this->handler_img_url($album_info['album_cover'],"album");
                $is_exist = $album_follow_model->where(array('album_id'=>$albumid,'user_id'=>$user_id))->count();
                if($is_exist){
                    $album_info['is_follow'] = 1;
                }else{
                    $album_info['is_follow'] = 0;
                }
                //是否有下一页
                $hasNextPage = 0;
                if($page<($count/$pagesize)){
                    $hasNextPage=1;
                }
                //处理音频路径和图片路径
                foreach($audio_list as $k=>$v ){
                    $audio_list[$k]['audio_url'] = $this->handler_audio_url($v['audio_url']);
                    $audio_list[$k]['audio_cover'] = $this->handler_img_url($v['audio_cover'],"audio");
                }
                $data = array(
                    'album_info'=>$album_info,
                    'list'=>$audio_list,
                    'page'=>$page,
                    'pagesize'=>$pagesize,
                    'count'=>$count,
                    'hasNextPage'=>$hasNextPage
                );
                $this->returnMsg(0,"成功",$data);
            break;
            case 2:
                if(empty($keyword)){
                    $this->returnMsg(-2);
                }else{
                    $where['cmf_med_audio.audio_name'] = array('like','%'.$keyword.'%');
                    $count = $audio_model->where($where)->count();
                    $audio_list = $audio_model
                        ->field($field)
                        ->join("cmf_med_audio_like ON cmf_med_audio_like.audio_id = cmf_med_audio.id and cmf_med_audio_like.user_id = {$user_id}","left")
                        ->where($where)
                        ->limit($limit)
                        ->select();
                    //是否有下一页
                    $hasNextPage = 0;
                    if($page<($count/$pagesize)){
                        $hasNextPage=1;
                    }
                    //处理音频路径和图片路径
                    foreach($audio_list as $k=>$v ){
                        $audio_list[$k]['audio_url'] = $this->handler_audio_url($v['audio_url']);
                        $audio_list[$k]['audio_cover'] = $this->handler_img_url($v['audio_cover'],"audio");
                    }
                    $data = array(
                        'list'=>$audio_list,
                        'page'=>$page,
                        'pagesize'=>$pagesize,
                        'count'=>$count,
                        'hasNextPage'=>$hasNextPage
                    );
                    $this->returnMsg(0,"成功",$data);
                }
            break;
            case 3:
                if(empty($difficulty)){
                    $this->returnMsg(-2);
                }else{
                    $where['cmf_med_audio.audio_difficulty'] = $difficulty;
                    $count = $audio_model->where($where)->count();
                    $audio_list = $audio_model
                                     ->field($field)
                                     ->join("cmf_med_audio_like ON cmf_med_audio_like.audio_id = cmf_med_audio.id and cmf_med_audio_like.user_id = {$user_id}","left")
                                     ->where($where)
                                     ->limit($limit)
                                     ->select();
                    //是否有下一页
                    $hasNextPage = 0;
                    if($page<($count/$pagesize)){
                        $hasNextPage=1;
                    }
                    //获取难度分类信息
                    $difficulty_info =  $difficulty_model->field("difficulty_degree,difficulty_cover,difficulty_like_count,difficulty_desc")->where(array('difficulty_degree'=>$difficulty))->find();
                    $difficulty_info['difficulty_cover'] = $this->handler_img_url($difficulty_info['difficulty_cover'],"album");
                    //处理音频路径和图片路径
                    foreach($audio_list as $k=>$v ){
                        $audio_list[$k]['audio_url'] = $this->handler_audio_url($v['audio_url']);
                        $audio_list[$k]['audio_cover'] = $this->handler_img_url($v['audio_cover'],"audio");
                    }
                    $is_exist = $album_follow_model->where(array('difficulty_degree'=>$difficulty,'user_id'=>$user_id))->count();
                    if($is_exist){
                        $difficulty_info['is_follow'] = 1;
                    }else{
                        $difficulty_info['is_follow'] = 0;
                    }
                    $data = array(
                        'difficulty_info'=>$difficulty_info,
                        'list'=>$audio_list,
                        'page'=>$page,
                        'pagesize'=>$pagesize,
                        'count'=>$count,
                        'hasNextPage'=>$hasNextPage
                    );
                    $this->returnMsg(0,"成功",$data);
                }
            break;
        }
    }

    /**
     * 获取音频详细信息
     * @interface 2.2
     * @return array returnMsg
     */
     public function audio_info(){
         //参数
         $audio_id = intval(I('audio_id'));
         $is_rand = I('is_rand',0);
         $user_id = $this->wx_user_id;

         $audio_model = M('med_audio');
         $audio_to_album_model = M('med_audio_to_album');

         //返回的数据库字段
         $field = "cmf_med_audio.id,
                   cmf_med_audio.audio_name,
                   cmf_med_audio.audio_reciter,
                   cmf_med_audio.audio_difficulty,
                   cmf_med_audio.audio_listen_count,
                   cmf_med_audio.audio_url,
                   cmf_med_audio.audio_cover,
                   cmf_med_audio.audio_description,
                   IF(cmf_med_audio_like.user_id <> '',1,0) is_like
                  ";

         if($is_rand){
             $order = "rand()";
             $audio_info = $audio_model->field($field)
                 ->join("cmf_med_audio_like ON cmf_med_audio_like.audio_id = cmf_med_audio.id and cmf_med_audio_like.user_id = {$user_id}","left")
                 ->order($order)
                 ->find();
         }else{
             //参数检查
             if(empty($audio_id)){
                 $this->returnMsg(-2);
             }
             $where = array('cmf_med_audio.id'=>$audio_id);
             $audio_info = $audio_model->field($field)
                 ->join("cmf_med_audio_like ON cmf_med_audio_like.audio_id = cmf_med_audio.id and cmf_med_audio_like.user_id = {$user_id}","left")
                 ->where($where)
                 ->find();
         }
         //处理读者姓名
         if($audio_info['audio_reciter'] == ''){
             $audio_info['audio_reciter'] = "佚名";
         }

         //处理音频和图片路径
         $audio_info['audio_url'] = $this->handler_audio_url( $audio_info['audio_url']);
         $audio_info['audio_cover'] = $this->handler_img_url( $audio_info['audio_cover'],"audio");

         //专辑分类
         $album_list = $audio_to_album_model->field("cmf_med_audio_album.id,cmf_med_audio_album.album_name")
                       ->join("cmf_med_audio_album ON cmf_med_audio_album.id = cmf_med_audio_to_album.album_id","left")
                       ->where(array("cmf_med_audio_to_album.audio_id"=>$audio_info['id']))
                       ->select();
         $audio_info["album_list"] = $album_list;

         $this->returnMsg(0,"成功",$audio_info);
     }

    /**
     * 音频收藏与取消收藏
     * @interface 2.3
     * @return array returnMsg
     */
    public function audio_like(){
        //参数
        $is_cancel = I('is_cancel');
        $audio_id = I('audio_id');
        $user_id = $this->wx_user_id;

        $audio_like_model = M('med_audio_like');
        $audio_model = M('med_audio');

        if(empty($audio_id)){
            $this->returnMsg(-2);
        }

        $where = array(
            'user_id'=>$user_id,
            'audio_id'=>$audio_id
        );
        $audio_like_info = $audio_like_model->field('id')->where($where)->find();
        $is_exist = $audio_model->where(array('id'=>$audio_id))->count();
        if(!$is_exist){
            $this->returnMsg(-1,"操作失败,该音频不存在!");
        }

        //取消收藏
        if($is_cancel==1){
            if($audio_like_info){
                $rs = $audio_like_model->where($where)->delete();
                if($rs){
                    $this->returnMsg(0,"取消收藏成功!");
                }else{
                    $this->returnMsg(-1,"取消收藏失败!");
                }
            }else{
                $this->returnMsg(-1,"请勿重复取消收藏!");
            }
        }else{
            if(!empty($audio_like_info)){
                $this->returnMsg(-1,"请勿重复收藏");
            }else{
                $add = array(
                    'audio_id'=>$audio_id,
                    'user_id'=>$user_id,
                    'createtime'=>time()
                );
                $rs = $audio_like_model->add($add);
                if($rs){
                    $this->returnMsg(0,"收藏成功!");
                }else{
                    $this->returnMsg(-1,"收藏失败!");
                }
            }
        }

    }

    /**
     * 用户点击量增加
     * @interface 2.4
     * @return array returnMsg
     */
    public function audio_click(){
        //参数
        $audio_id = I('audio_id');

        $user_id = $this->wx_user_id;

        $audio_model = M('med_audio');
        $audio_history_model = M('med_listen_history');
        $album_history_model = M('med_album_listen_history');
        $audio_to_album_model = M('med_audio_to_album');

        //检查参数
        if(empty($audio_id)){
            $this->returnMsg(-2);
        }
        try{
            $audio_history_model->startTrans();
            //该用户是否之前收听过该音频
            $where = array(
                'user_id'=>$user_id,
                'audio_id'=>$audio_id
            );
            $rs1 = $audio_history_model->where($where)->count();
            $audio_info = $audio_model->field("audio_difficulty")->where(array('id'=>$audio_id))->find();

            $album_list = $audio_to_album_model->field('album_id')->where(array('audio_id'=>$audio_id))->select();
            //有收听过
            if($rs1){
                //增加本人音频听数
                $audio_data['createtime'] = time();
                $audio_data['my_listen_count'] = array('exp','my_listen_count+1'); //音频收听数加1
                $rs2 = $audio_history_model->where($where)->save($audio_data);
                if($rs2){
                  //增加专辑收听次数
                    foreach($album_list as $key=>$value){
                        if($album_history_model->where(array("user_id"=>$user_id,"album_id"=>$value['album_id']))->count()){
                            $album_history_data['createtime'] = time();
                            $album_history_data['my_album_listen_count'] = array('exp','my_album_listen_count+1'); //专辑收听数加1
                            $album_history_model->where(array("user_id"=>$user_id,"album_id"=>$value['album_id']))->save($album_history_data);
                        }else{
                            $album_history_add = array(
                                'user_id'=>$user_id,
                                'album_id'=>$value['album_id'],
                                'createtime'=>time()
                            );
                            $album_history_model->add($album_history_add);
                        }
                    }
                    //增加难度系数收听次数
                    if($album_history_model->where(array("user_id"=>$user_id,"difficulty_degree"=>$audio_info['audio_difficulty']))->count()){
                        $difficulty_history_data['createtime'] = time();
                        $difficulty_history_data['my_album_listen_count'] = array('exp','my_album_listen_count+1'); //专辑收听数加1
                        $album_history_model->where(array("user_id"=>$user_id,"difficulty_degree"=>$audio_info['audio_difficulty']))->save($difficulty_history_data);
                    }else{
                        $difficulty_history_data = array(
                            'user_id'=>$user_id,
                            'difficulty_degree'=>$audio_info['audio_difficulty'],
                            'createtime'=>time()
                        );
                        $album_history_model->add($difficulty_history_data);
                    }
                }else{
                    $audio_history_model->rollback();
                    $this->returnMsg(-1,"音频播放次数增加失败!");
                }
            }else{
                $audio_history_add = array(
                    'audio_id'=>$audio_id,
                    'user_id'=>$user_id,
                    'createtime'=>time()
                );
                $rs3 = $audio_history_model->where($where)->add($audio_history_add);
                if($rs3){
                    //增加专辑收听次数
                    foreach($album_list as $key=>$value){
                        if($album_history_model->where(array("user_id"=>$user_id,"album_id"=>$value['album_id']))->count()){
                            $album_history_data['createtime'] = time();
                            $album_history_data['my_album_listen_count'] = array('exp','my_album_listen_count+1'); //专辑收听数加1
                            $album_history_model->where(array("user_id"=>$user_id,"album_id"=>$value['album_id']))->save($album_history_data);
                        }else{
                            $album_history_add = array(
                                'user_id'=>$user_id,
                                'album_id'=>$value['album_id'],
                                'createtime'=>time()
                            );
                            $album_history_model->add($album_history_add);
                        }
                    }
                    //增加难度系数收听次数
                    if($album_history_model->where(array("user_id"=>$user_id,"difficulty_degree"=>$audio_info['audio_difficulty']))->count()){
                        $difficulty_history_data['createtime'] = time();
                        $difficulty_history_data['my_album_listen_count'] = array('exp','my_album_listen_count+1'); //专辑收听数加1
                        $album_history_model->where(array("user_id"=>$user_id,"difficulty_degree"=>$audio_info['difficulty_degree']))->save($difficulty_history_data);
                    }else{
                        $difficulty_history_data = array(
                            'user_id'=>$user_id,
                            'difficulty_degree'=>$audio_info['audio_difficulty'],
                            'createtime'=>time()
                        );
                        $album_history_model->add($difficulty_history_data);
                    }
                }else{
                    $audio_history_model->rollback();
                    $this->returnMsg(-1,"音频播放次数增加失败!");
                }
            }
            $audio_model->where(array('id'=>$audio_id))->setInc("audio_listen_count");
            $audio_history_model->commit();
            $this->returnMsg(0,"成功!");
        }catch(Exception $e){
            $audio_history_model->rollback();
            $this->returnMsg(-1,"音频播放次数增加失败!");
        }
    }

    /**
     * 音频收听历史记录
     * @interface 2.5
     * @return array returnMsg
     */
    public function audio_history(){
        //参数
        $duration = I('duration',100);//需要查询多少天内的收听记录,默认10天
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $start = ($page - 1) * $pagesize;
        $limit = "{$start},{$pagesize}";
        $user_id = $this->wx_user_id;

        $listen_history_model = M('med_listen_history');


        //计算duration以前的时间戳
        $time_ago = time()-$duration*24*60*60;

        $where['cmf_med_listen_history.user_id'] = $user_id;
        $where['cmf_med_listen_history.createtime'] = array('egt',$time_ago);

        //返回的数据库字段
        $field = "
             cmf_med_audio.id audio_id,
             cmf_med_audio.audio_name,
             cmf_med_audio.audio_url,
             cmf_med_audio.audio_listen_count,
             IF(cmf_med_audio_like.user_id <> '',1,0) is_like
        ";
        $count = $listen_history_model->where($where)->count();

        $audio_list = $listen_history_model
            ->field($field)
            ->join("cmf_med_audio ON cmf_med_audio.id = cmf_med_listen_history.audio_id")
            ->join("cmf_med_audio_like ON cmf_med_audio_like.audio_id = cmf_med_audio.id and cmf_med_audio_like.user_id = {$user_id}","left")
            ->where($where)
            ->limit($limit)
            ->order("cmf_med_listen_history.createtime desc")
            ->select();

        //是否有下一页
        $hasNextPage = 0;
        if($page<($count/$pagesize)){
            $hasNextPage=1;
        }

        //处理音频路径
        foreach($audio_list as $key=>$value){
            $audio_list[$key]['audio_url'] = $this->handler_audio_url($value['audio_url']);
        }

        $data = array(
            'list'=>$audio_list,
            'page'=>$page,
            'pagesize'=>$pagesize,
            'count'=>$count,
            'hasNextPage'=>$hasNextPage
        );
        $this->returnMsg(0,"成功",$data);
    }

    /**
     * 专辑关注与取消关注
     * @interface 2.6
     * @return array returnMsg
     */
    public function album_follow(){
        //参数
        $is_cancel = I('is_cancel');
        $album_id = I('album_id',0);
        $difficulty_degree = I('difficulty_degree',0);

        $user_id = $this->wx_user_id;

        $album_like_model = M('med_user_like_album');
        $album_model = M('med_audio_album');
        $difficulty_model = M('med_audio_difficulty');

        if(empty($album_id)&&empty($difficulty_degree)){
            $this->returnMsg(-2);
        }

        $field = "";
        if($album_id){
            $field = "album_id";
            $where = array(
                'user_id'=>$user_id,
                'album_id'=>$album_id
            );
            $is_album_exist = $album_model->where(array('id'=>$album_id))->count();
            if(!$is_album_exist){
                $this->returnMsg(-1,"操作失败,该专辑不存在!");
            }
        }
        if($difficulty_degree){
            $field = "difficulty_degree";
            $where = array(
                'user_id'=>$user_id,
                'difficulty_degree'=>$difficulty_degree
            );
            $is_difficulty_exist = $difficulty_model->where(array('difficulty_degree'=>$difficulty_degree))->count();
            if(!$is_difficulty_exist){
                $this->returnMsg(-1,"操作失败,该难度系数的专辑不存在!");
            }
        }
        $album_like_info = $album_like_model->field($field)->where($where)->find();

        //取消收藏
        if($is_cancel==1){
            if($album_like_info){
                $rs = $album_like_model->where($where)->delete();
                if($rs){
                    if($difficulty_degree){
                        $difficulty_data['difficulty_like_count'] = array('exp','difficulty_like_count-1'); //关注总数减一
                        $rs2 = $difficulty_model->where(array('difficulty_degree'=>$difficulty_degree))->save($difficulty_data);
                    }else{
                        $album_data['album_like_count'] = array('exp','album_like_count-1'); //专辑关注数减1
                        $rs2 = $album_model->where(array('id'=>$album_id))->save($album_data);
                    }
                    if($rs2){
                        $this->returnMsg(0,"取消关注成功!");
                    }else{
                        $this->returnMsg(-1,"取消关注失败!");
                    }
                }else{
                    $this->returnMsg(-1,"取消关注失败!");
                }
            }else{
                $this->returnMsg(-1,"请勿重复取消关注!");
            }
        }else{
            if(!empty($album_like_info)){
                $this->returnMsg(-1,"请勿重复关注");
            }else{
                $add = array(
                    'album_id'=>$album_id,
                    'difficulty_degree'=>$difficulty_degree,
                    'user_id'=>$user_id,
                    'createtime'=>time()
                );
                $rs = $album_like_model->add($add);
                if($rs){
                    if($difficulty_degree){
                        $difficulty_data['difficulty_like_count'] = array('exp','difficulty_like_count+1'); //专辑关注数加1
                        $rs2 = $difficulty_model->where(array('difficulty_degree'=>$difficulty_degree))->save($difficulty_data);
                    }else{
                        $album_data['album_like_count'] = array('exp','album_like_count+1'); //专辑关注数加1
                        $rs2 = $album_model->where(array('id'=>$album_id))->save($album_data);
                    }
                    if($rs2){
                        $this->returnMsg(0,"关注成功!");
                    }else{
                        $this->returnMsg(-1,"关注失败!");
                    }
                }else{
                    $this->returnMsg(-1,"关注失败!");
                }
            }
        }
    }

    /**
     * 正常首页布局接口
     * @interface 2.7
     * @return array returnMsg
     */
    public function layout_index_normal(){
        $med_loft_model =  M('med_loft');
        $med_loft_item = M('med_loft_item');
        $album_item = M('med_audio_album');
        $difficulty_item = M('med_audio_difficulty');

        $loft_list = $med_loft_model
                               ->field('id loft_id,loft_type,loft_display')
                               ->order("loft_sort asc,id asc")
                               ->select();

        if($loft_list){
            $result = array();
            $i = 0;
            foreach($loft_list as $key=>$value){
                $item_list = $med_loft_item->where(array('loft_id'=>$value['loft_id']))->select();
                $adv_list = array();
                foreach($item_list as $k=>$v){
                    if($value['loft_type']!='adv'){

                        $result[$i]['loft_type'] = $value['loft_type'];
                        $result[$i]['loft_display'] = $value['loft_display'];
                        if($v['album_ids']){
                            $album_ids = unserialize($v['album_ids']);
                            $album_list = array();
                            foreach($album_ids as $k=>$v){
                                $album_list[$k] = $album_item->field('id album_id,album_name,album_cover,album_like_count')->where(array('id'=>$v['album_id']))->find();
                                $album_list[$k]['album_cover'] = $this->handler_img_url($album_list[$k]['album_cover']);
                            }
                            $result[$i]['item_list'] = $album_list;
                        }
                        else if($v['difficulty_ids']){
                            $difficulty_ids = unserialize($v['difficulty_ids']);
                            $difficulty_list = array();
                            foreach($difficulty_ids as $k=>$v){
                                $difficulty_list[$k] = $difficulty_item->field("difficulty_degree,difficulty_cover,difficulty_like_count")->where(array('id'=>$v))->find();
                                $difficulty_list[$k]['difficulty_degree'] = intval($difficulty_list[$k]['difficulty_degree']);
                                $difficulty_list[$k]['difficulty_cover'] = $this->handler_img_url($difficulty_list[$k]['difficulty_cover']);
                            }
                            $result[$i]['item_list'] = $difficulty_list;
                        }
                        $i=$i+1;
                    }else{ //当loft_type=adv的时候
                        $adv_list_item = array(
                            'adv_img'=>$this->handler_img_url($v['adv_img']),
                            'adv_url'=>$v['adv_url']?$v['adv_url']:'',
                            'adv_title'=>$v['adv_title']?$v['adv_title']:''
                        );
                        array_push($adv_list,$adv_list_item);
                    }
                }
                if($value['loft_type']=='adv'){
                    $loft_adv = array(
                        'loft_type'=>$value['loft_type'],
                        'loft_display'=>$value['loft_display'],
                        'item_list'=>$adv_list
                    );
                }
            }
            array_unshift($result,$loft_adv);
            $this->returnMsg(0,"成功",$result);
        }else{
            $this->returnMsg(-1,"首页暂无数据");
        }
    }

    /**
     * 我的关注专辑列表接口
     * @interface 2.8
     * @return array returnMsg
     */
    public function album_like_list(){
        //参数
        $album_like_model=M('med_user_like_album');
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $start = ($page - 1) * $pagesize;
        $limit = "{$start},{$pagesize}";


        //获取用户信息
        $user_id = $user_id = $this->wx_user_id;

        //返回的数据库字段
        $field = "
             cmf_med_audio_album.id album_id,
             cmf_med_audio_album.album_name,
             cmf_med_audio_album.album_cover,
             cmf_med_audio_album.album_desc
        ";
        $where = array('cmf_med_user_like_album.user_id'=>$user_id);
        $count = $album_like_model->where($where)->count();
        $album_like_list = $album_like_model
                               ->field($field)
                               ->join("cmf_med_audio_album ON cmf_med_audio_album.id = cmf_med_user_like_album.album_id")
                               ->where($where)
                               ->limit($limit)
                               ->order('cmf_med_user_like_album.createtime desc')
                               ->select();
        //处理图片路径
        foreach($album_like_list as $key=>$value){
            $album_like_list[$key]["album_cover"] = $this->handler_img_url($value['album_cover'],"album");
        }

        //是否有下一页
        $hasNextPage = 0;
        if($page<($count/$pagesize)){
            $hasNextPage=1;
        }

        $data = array(
            'list'=>$album_like_list,
            'page'=>$page,
            'pagesize'=>$pagesize,
            'count'=>$count,
            'hasNextPage'=>$hasNextPage
        );
        $this->returnMsg(0,"成功",$data);

    }

    /**
     * 我的收藏音频列表接口
     * @interface 2.9
     * @return array returnMsg
     */
    public function audio_like_list(){
        //参数
        $audio_like_model=M('med_audio_like');
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $start = ($page - 1) * $pagesize;
        $limit = "{$start},{$pagesize}";


        //获取用户信息
        $user_id = $user_id = $this->wx_user_id;

        //返回的数据库字段
        $field = "
             cmf_med_audio.id audio_id,
             cmf_med_audio.audio_name,
             cmf_med_audio.audio_listen_count,
             cmf_med_audio.audio_url
        ";
        $where = array('cmf_med_audio_like.user_id'=>$user_id);
        $count = $audio_like_model->where($where)->count();
        $audio_like_list = $audio_like_model
            ->field($field)
            ->join("cmf_med_audio ON cmf_med_audio.id = cmf_med_audio_like.audio_id")
            ->where($where)
            ->limit($limit)
            ->order('cmf_med_audio_like.createtime desc')
            ->select();
        //处理图片路径
        foreach($audio_like_list as $key=>$value){
            $audio_like_list[$key]["is_like"] = "1";
            $audio_like_list[$key]["audio_url"] = $this->handler_audio_url($value['audio_url']);
            $audio_like_list[$key]["audio_cover"] = $this->handler_img_url($value['audio_cover'],"audio");
        }

        //是否有下一页
        $hasNextPage = 0;
        if($page<($count/$pagesize)){
            $hasNextPage=1;
        }

        $data = array(
            'list'=>$audio_like_list,
            'page'=>$page,
            'pagesize'=>$pagesize,
            'count'=>$count,
            'hasNextPage'=>$hasNextPage
        );
        $this->returnMsg(0,"成功",$data);

    }

    /**
     * 我的收听列表接口
     * @interface 2.10
     * @return array returnMsg
     */
    public function my_listen_list(){
        //参数
        $my_listen_model=M('med_listen_history');
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $start = ($page - 1) * $pagesize;
        $limit = "{$start},{$pagesize}";


        //获取用户信息
        $user_id = $user_id = $this->wx_user_id;

        //返回的数据库字段
        $field = "
             cmf_med_audio.id audio_id,
             cmf_med_audio.audio_name,
             cmf_med_audio.audio_url,
             cmf_med_listen_history.my_listen_count,
             IF(cmf_med_audio_like.user_id <> '',1,0) is_like
        ";
        $where = array('cmf_med_listen_history.user_id'=>$user_id);
        $count = $my_listen_model->where($where)->count();
        $my_listen_list = $my_listen_model
            ->field($field)
            ->join("cmf_med_audio ON cmf_med_audio.id = cmf_med_listen_history.audio_id")
            ->join("cmf_med_audio_like ON cmf_med_audio_like.audio_id = cmf_med_audio.id and cmf_med_audio_like.user_id = {$user_id}","left")
            ->where($where)
            ->limit($limit)
            ->order('cmf_med_listen_history.createtime desc')
            ->select();
        //处理图片路径
        foreach($my_listen_list as $key=>$value){
            $my_listen_list[$key]["audio_url"] = $this->handler_audio_url($value['audio_url']);
            $my_listen_list[$key]["audio_cover"] = $this->handler_img_url($value['audio_cover'],"audio");
        }

        //是否有下一页
        $hasNextPage = 0;
        if($page<($count/$pagesize)){
            $hasNextPage=1;
        }

        $data = array(
            'list'=>$my_listen_list,
            'page'=>$page,
            'pagesize'=>$pagesize,
            'count'=>$count,
            'hasNextPage'=>$hasNextPage
        );
        $this->returnMsg(0,"成功",$data);

    }

    /**
     * 热度和精选50首接口
     * 热度取点播量最高的10首音频
     * 精选50首选最新上传的50首音频
     * @interface 2.11
     * @return array returnMsg
     */
    public function audio_hot_latest_list(){
        $audio_model = M('med_audio');
        $audio_to_ablum_model = M("med_audio_to_album");
        $audio_like_model = M("med_audio_like");

        $type = I("type",1);
        $page = I('page',1);
        $pagesize = I('pagesize',10);
        $start = ($page - 1) * $pagesize;
        $limit = "{$start},{$pagesize}";

        //热度
        if($type == 1){
            $order = "audio_listen_count desc";
        }else if($type == 2){//精选50首
            $where['is_special'] = 1;
            $order = "createtime desc";
        }

        $user_id = $this->wx_user_id;

        $count = $audio_model->where($where)->count();


        $audio_list = $audio_model
                       ->field("id audio_id,audio_difficulty,audio_name,audio_url,audio_cover,audio_description audio_desc,audio_listen_count")
                       ->where($where)
                       ->order($order)
                       ->limit($limit)
                       ->select();

        foreach($audio_list as $key=>$value){
            $album_list = $audio_to_ablum_model
                                  ->field("cmf_med_audio_album.id,cmf_med_audio_album.album_name")
                                  ->join("cmf_med_audio_album ON cmf_med_audio_album.id = cmf_med_audio_to_album.album_id")
                                  ->where(array('cmf_med_audio_to_album.audio_id'=>$value['audio_id']))
                                  ->select();


            //处理图片和音频路径
            $audio_list[$key]["audio_url"] = $this->handler_audio_url($value['audio_url']);
            $audio_list[$key]["audio_cover"] = $this->handler_img_url($value['audio_cover'],"audio");

            $like_info = $audio_like_model->where(array('user_id'=>$user_id,'audio_id'=>$value['audio_id']))->count();
            if($like_info){
                $audio_list[$key]['is_like'] = "1";
            }else{
                $audio_list[$key]['is_like'] = "0";
            }

            $audio_list[$key]['album_list'] = $album_list;
        }
        //是否有下一页
        $hasNextPage = 0;
        if($page<($count/$pagesize)){
            $hasNextPage=1;
        }
        $data = array(
            'list'=>$audio_list,
            'page'=>$page,
            'pagesize'=>$pagesize,
            'count'=>$count,
            'hasNextPage'=>$hasNextPage
        );

        $this->returnMsg(0,"成功",$data);


    }

    /**
     * 个性化首页接口
     * 根据后台设置的算法，每个人的首页排序不一样
     * @interface 2.12
     * @return array returnMsg
     */
    public function layout_index_special(){
         $setting_model = M("med_special_setting");
         $album_history_model =  M("med_album_listen_history");
         $audio_album = M("med_audio_album");
         $med_loft_item = M('med_loft_item');
         $difficulty_item = M('med_audio_difficulty');

         $user_id = $this->wx_user_id;
         $setting_info = $setting_model->find();
        //点播次数排序权重
         $click_percent = $setting_info['click_percent'];
         //分享次数排序权重
         $share_percent = $setting_info['share_percent'];

         $album_list = $audio_album->field('id album_id,album_name,album_cover,album_like_count,class_type')->select();
         foreach($album_list as $key=>$value){
             $album_history = $album_history_model->field("my_album_listen_count lcount,my_album_share_count scount")->where(array('user_id'=>$user_id,'album_id'=>$value['album_id']))->find();
             if($album_history){
                 $weight = $album_history['lcount']*$click_percent+$album_history['scount']*$share_percent; //排序权重
             }else{
                 $weight = 0;
             }
             $album_list[$key]['wight'] = $weight;
         }

        //按照权重算法排序
        $album_list_sort = $this->multi_array_sort($album_list,'wight','SORT_DESC');
        $album_list_sort = array_slice($album_list_sort,0,9);
        $home2_list = array();
        $home4_list = array();
        foreach($album_list_sort as $key=>$value){
            $album_list_sort[$key]['album_cover'] = $this->handler_img_url($album_list_sort[$key]['album_cover']);
            //主题专辑加入一个数组
            if($value['class_type'] == 1){
                array_push($home2_list,$album_list_sort[$key]);
            }else if($value['class_type'] == 2){
                array_push($home4_list,$album_list_sort[$key]);
            }
        }

        //构造个性化首页数据
        $result = array();
        //adv板块
        $where['adv_img'] = array('neq','');
        $adv_list = $med_loft_item->field("adv_img,adv_url,adv_title")->where($where)->select();

        foreach($adv_list as $key=>$value){
            $adv_list[$key]['adv_img'] = $this->handler_img_url($value['adv_img']);
            $adv_list[$key]['adv_url'] = $value['adv_url']?$value['adv_url']:'';
            $adv_list[$key]['adv_title'] = $value['adv_title']?$value['adv_title']:'';
        }
        $adv_loft = array(
            'loft_type'=>'adv',
            'loft_display'=>1,
            'item_list'=>$adv_list
        );
        array_push($result,$adv_loft);


        //home1板块
       /* $home1_loft = array(
            'loft_type'=>'home1',
            'loft_display'=>1,
            'item_list'=>array_slice($album_list_sort,0,4)
        );
        array_push($result,$home1_loft);*/

        //home2版块
        //主题专辑版块
        $home2_loft = array(
            'loft_type'=>'home2',
            'loft_display'=>1,
            'item_list'=>$home2_list
        );
        array_push($result,$home2_loft);

        //home4版块
        //精选专辑版块
        $home4_loft = array(
            'loft_type'=>'home4',
            'loft_display'=>1,
            'item_list'=>$home4_list
        );
        array_push($result,$home4_loft);

        //难度系数模块
        //取5个难度系数版块
        $difficulty_item = $difficulty_item->field("difficulty_degree,difficulty_cover,difficulty_like_count")->limit(5)->select();
        foreach($difficulty_item as $key=>$value){
            $difficulty_item[$key]['difficulty_cover'] = $this->handler_img_url($value['difficulty_cover']);
        }
        $home3_loft = array(
            'loft_type'=>'home3',
            'loft_display'=>1,
            'item_list'=>$difficulty_item
        );
        array_push($result,$home3_loft);

        if($result){
            $this->returnMsg(0,"成功",$result);
        }else{
            $this->returnMsg(-1,"暂无数据");
        }

    }


    /**
     * 分享次数记录接口
     * @interface 2.13
     * @return array returnMsg
     */
    public function audio_share_count_increase(){
        //参数
        $difficulty_degree = I("difficulty_degree");
        $audio_id = I("audio_id");
        $album_id = I("album_id");

        $album_history_model =  M("med_album_listen_history");
        $audio_model =  M("med_audio");
        $album_model =  M("med_audio_album");
        if((empty($difficulty_degree)&&empty($audio_id))&&empty($album_id)){
            $this->returnMsg(-2);
        }

        $user_id = $this->wx_user_id;

        if($audio_id){
            $audio_info = $audio_model->where(array('id'=>$audio_id))->find();
            $difficulty_degree = $audio_info['audio_difficulty'];
        }
        if($album_id){
            if(!$album_model->where(array('id'=>$album_id))->count()){
                $this->returnMsg(-1,'该专辑不存在,操作失败!');
            }
            //增加专辑分享次数

            if($album_history_model->where(array("user_id"=>$user_id,"album_id"=>$album_id))->count()){
                $album_history_data['createtime'] = time();
                $album_history_data['my_album_share_count'] = array('exp','my_album_share_count+1'); //专辑收听数加1
                $rs = $album_history_model->where(array("user_id"=>$user_id,"album_id"=>$album_id))->save($album_history_data);
            }else{
                $album_history_add = array(
                    'user_id'=>$user_id,
                    'album_id'=>$album_id,
                    'my_album_share_count'=>1,
                    'createtime'=>time()
                );
                $rs = $album_history_model->add($album_history_add);
            }
        }else{
            //增加难度系数分享次数
            if($album_history_model->where(array("user_id"=>$user_id,"difficulty_degree"=>$difficulty_degree))->count()){
                $difficulty_history_data['createtime'] = time();
                $difficulty_history_data['my_album_share_count'] = array('exp','my_album_share_count+1'); //专辑收听数加1
                $rs = $album_history_model->where(array("user_id"=>$user_id,"difficulty_degree"=>$difficulty_degree))->save($difficulty_history_data);
            }else{
                $difficulty_history_data = array(
                    'user_id'=>$user_id,
                    'difficulty_degree'=>$difficulty_degree,
                    'my_album_share_count'=>1,
                    'createtime'=>time()
                );
                $rs = $album_history_model->add($difficulty_history_data);
            }
        }
        if($rs){
            $this->returnMsg(0,"分享次数已增加成功!");
        }else{
            $this->returnMsg(-1,"分享次数增加失败!");
        }
    }


    /**
     * 二维数组根据字段进行排序
     * @params array $array 需要排序的数组
     * @params string $field 排序的字段
     * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
     */
    function multi_array_sort($array, $field, $sort = 'SORT_DESC')
    {
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }

    //处理音频路径
    public function handler_audio_url($audio_url){
        return C('HTTP_HEAD').$_SERVER['HTTP_HOST'].$audio_url;
    }

    //处理图片路径
    public function handler_img_url($img_url,$type){
        if($img_url){
            return C('HTTP_HEAD').$_SERVER['HTTP_HOST'].$img_url;
        }else{//如果没有,则显示默认图片
           if($type == "audio"){
               return C('HTTP_HEAD').$_SERVER['HTTP_HOST'].C('AUDIO_COVER_DEFAULT');
           }else if($type == "album"){
               return C('HTTP_HEAD').$_SERVER['HTTP_HOST'].C('ALBUM_COVER_DEFAULT');
           }
        }
    }






}