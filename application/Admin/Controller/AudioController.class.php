<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Lib\UploadHandler;


/**
 * 音频处理控制器
 * User: CMJ
 * Date: 2017/8/16
 * Time: 13:36
 */

class AudioController extends AdminbaseController{

    public function audio_info_index(){
        $audio_album_model = M('med_audio_album');
        $audio_difficulty_model = M("med_audio_difficulty");

        $album_list = $audio_album_model->select();
        $difficulty_list = $audio_difficulty_model->select();

        $this->assign("difficulty_list",$difficulty_list);
        $this->assign('album_list',$album_list);
        $this->display();
    }

    /**
     * 上传处理
     */
    public function check_audio_isupload(){
        $audio_name = I("audio_name");
        $audio_name_arr = explode(".",$audio_name);
        $audio_model = M('med_audio');
        if($audio_model->where(array('audio_name'=>$audio_name_arr[0]))->count()){
            $return  = array('code'=>1,'message'=>'音频文件已存在!');
        }
        $this->ajaxReturn($return);
    }

    /**
     * 删除文件
     * @params string filename
     */
    function del_file(){
        $filename = I("filename");
        if(!is_file(".".C("AUDIO_UPLOAD_PATH").$filename)){
            $return  = array('code'=>-1,'message'=>$filename);
        }else{
            $rs = unlink(".".C("AUDIO_UPLOAD_PATH").$filename);
            if($rs){
                $return  = array('code'=>0,'message'=>'删除成功!');
            }else{
                $return  = array('code'=>-1,'message'=>'删除失败!');
            }
        }
        $this->ajaxReturn($return);
    }

    /**
     * 上传处理
     */
    public function uploadHandler(){
        //$this->ajaxReturn(array('code'=>1,'message'=>'音频文件已存在!',$_FILES));
        new UploadHandler();
    }

    /**
     * 上传页面显示
     */
    public function audio_upload_index(){
        $this->display("audio_upload_index");
    }

    /**
     * 处理上传信息
     */
    public function audio_info_post(){
        //参数
        $audio_name = I('audio_name');
        $audio_reciter = I('audio_reciter');
        $audio_description = I('audio_description');
        $audio_size = I('audio_size');
        $photos_url = I('photos_url');
        $photos_url_edit = I('photos_url_edit');
        $audio_duration = I('audio_duration');
        $album_ids = I('album_ids');
        $difficulty = I('difficulty');
        $edit_id = I('edit_id');
        $is_special = I('is_special',0);


        $audio_model = M('med_audio');
        $audio2album_model = M('med_audio_to_album');

        //检查参数
        if(empty($difficulty)){
            $this->error("难度系数为必选项!");
        }
        if(empty($audio_name)||$audio_size<=0||$audio_duration<=0||empty($album_ids)){
              $this->error("音频有误,请重新上传!");
        }


        if($photos_url[0]){
            $audio_cover = C('IMG_UPLOAD_PATH').$photos_url[0];
        }else{
            $audio_cover = '';
        }

        if($photos_url_edit){
            $audio_cover = $photos_url_edit;
        }
        //将音频名称和音频格式分开保存
        $audio_name_arr = explode(".",$audio_name);
        if($audio_name_arr[1]==''){
            $audio_name_arr[1] = 'mp3';
        }


        if(IS_POST){
            try{
                $audio_model->startTrans();

                $add = array(
                    'audio_name'=>$audio_name_arr[0],
                    'audio_type'=>$audio_name_arr[1],
                    'audio_size'=>$audio_size,
                    'audio_duration'=>$audio_duration,
                    'audio_description'=>$audio_description,
                    'audio_reciter'=>$audio_reciter,
                    'audio_cover'=>$audio_cover,
                    'audio_url'=>C('AUDIO_UPLOAD_PATH').$audio_name,
                    'audio_difficulty'=>$difficulty,
                    'is_special'=>$is_special,
                    'createtime'=>time()
                );

                //是否编辑
                if($edit_id){
                    $rs = $audio_model->where(array('id'=>$edit_id))->save($add);
                    if($rs){
                        //删除原来所选分类
                        $audio2album_model->where(array('audio_id'=>$edit_id))->delete();

                        foreach($album_ids as $key=>$value){
                            $audio2album_model->add(array('audio_id'=>$edit_id, 'album_id'=>$value,'createtime'=>time()));
                        }
                        $audio_model->commit();
                        $this->success("音频编辑成功!");
                    }else{
                        $audio_model->rollback();
                        $this->error("音频编辑失败!");
                    }
                }else{
                    $is_exist = $audio_model->where(array('audio_name'=>$audio_name_arr[0],'audio_size'=>$audio_size,'audio_duration'=>$audio_duration))->count();
                    if($is_exist){
                        $this->error("该音频已存在,请勿重复提交!");
                    }

                    $rs = $audio_model->add($add);
                    if($rs){
                        foreach($album_ids as $key=>$value){
                            $audio2album_model->add(array('audio_id'=>$rs, 'album_id'=>$value,'createtime'=>time()));
                        }
                        $audio_model->commit();
                        $this->success("音频添加成功!");
                    }else{
                        $audio_model->rollback();
                        $this->error("音频添加失败!");
                    }
                }
            }catch(Exception $e){
                $audio_model->rollback();
                $this->error("音频添加失败!");
            }
        }else{
            $this->error("提交失败!");
        }

    }
    /**
     * 新增
     * 专辑分类
     * 页面显示
     */
    public function audio_album_add(){
          $this->display();
    }
    /**
     * 编辑
     * 专辑分类
     * 页面显示
     */
    public function audio_album_edit(){
        //参数
        $id = I('id');
        $info = M('med_audio_album')->where(array('id'=>$id))->find();
        if($info){
            $this->assign("info",$info);
        }else{
            $this->error("编辑错误!");
        }
        $this->display("audio_album_add");
    }

    /**
     * 新增提交
     * 编辑提交
     * 专辑分类
     */
    public function audio_album_post(){
        //参数
        $album_name = trim(I('album_name'));
        $album_desc = trim(I('album_desc'));
        $photos_url = I('photos_url');
        $album_cover_edit = I('album_cover');
        $class_type=I("class_type");

        if($photos_url){
            $album_cover = C('IMG_UPLOAD_PATH').$photos_url[0];
        }else{
            $album_cover = '';
        }

        if($album_cover_edit){
            $album_cover = $album_cover_edit;
        }

        $audio_album_model = M('med_audio_album');

        $edit_id = I('edit_id');

        if(IS_POST){
            if(empty($album_name)){
                $this->error("专辑名称不能为空!");
            }

            $add = array(
                'album_name'=>$album_name,
                'album_desc'=>$album_desc,
                'album_cover'=>$album_cover,
                'class_type'=>$class_type,
                'createtime'=>time()
            );
            //编辑
            if($edit_id){
                $rs = $audio_album_model->where(array('id'=>$edit_id))->save($add);
                if($rs){
                    $this->success("专辑编辑成功!");
                }else{
                    $this->error("专辑编辑失败!");
                }
            }else{//添加
                $rs = $audio_album_model->add($add);
                if($rs){
                    $this->success("专辑添加成功!");
                }else{
                    $this->error("专辑添加失败!");
                }
            }
        }else{
            $this->error("提交失败!");
        }
    }

    /**
     * 删除
     * 专辑分类
     */
    public function audio_album_del(){
         //参数
        $album_id = I('id');
        $audio_album_model = M('med_audio_album');
        $audio_to_album_model = M('med_audio_to_album');
        $user_like_album_model = M('med_user_like_album');
        $album_listen_history_model = M('med_album_listen_history');


        if(empty($album_id)){
            $return  = array('code'=>-1,'message'=>'参数错误');
        }else{
            $cover_url = $audio_album_model->where(array('id'=>$album_id))->getField('album_cover');
            //判断专辑是否有关联,有则不允许删除专辑名称
            $is_exist1 = $audio_to_album_model->where(array('album_id'=>$album_id))->count();
            $is_exist2 = $user_like_album_model->where(array('album_id'=>$album_id))->count();
            $is_exist3 = $album_listen_history_model->where(array('album_id'=>$album_id))->count();
            if($is_exist1||$is_exist2||$is_exist3){
                $return  = array('code'=>-3,'message'=>'删除的专辑分类存在关联,不允许删除!');
            }else{
                //删除数据库信息
                $rs = $audio_album_model->where(array('id'=>$album_id))->delete();
                //删除图片
                if($rs){
                    if($cover_url){
                        unlink(".".$cover_url);
                    }
                    $return  = array('code'=>0,'message'=>'删除成功','data'=>$rs);
                }else{
                    $return  = array('code'=>-1,'message'=>'删除失败');
                }
            }
        }
        $this->ajaxReturn($return);
    }

    /**
     * 列表
     * 专辑分类
     */
    public function audio_album_list(){
        //参数
        $album_name = I('album_name');
        $start_time = I('start_time');
        $end_time   = I('end_time');
        $audio_album_model = M('med_audio_album');

        if($album_name){
            $where['album_name'] = array('like','%'.$album_name.'%');
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

        //分页
        $count = $audio_album_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $audio_album_model->where($where)->limit($page->firstRow,$page->listRows)->order('createtime desc')->select();

        $this->assign('list',$list);
        $this->assign("page", $page->show('Admin'));
        $this->assign("start_time",$start_time);
        $this->assign("end_time",$end_time?$end_time:date('Y-m-d H:i',NOW_TIME));
        $this->assign("album_name",$album_name);
        $this->display();
    }

    /**
     * 列表
     * 音频上传
     */
    public function audio_upload_list(){
        //参数
        $audio_name = I('audio_name');
        $album_name = I('album_name');
        $audio_reciter = I('audio_reciter');
        $start_time = I('start_time');
        $end_time   = I('end_time');
        $check_is_special = I('check_is_special');

        $med_audio_to_album_model = M('med_audio_to_album');
        $audio_model = M('med_audio');

        if($audio_name){
            $where['audio_name'] = array('like','%'.$audio_name.'%');
        }

        if($audio_reciter){
            $where['audio_reciter'] = array('like','%'.$audio_reciter.'%');
        }

        if($album_name){
            $join_where["cmf_med_audio_album.album_name"] = array('like','%'.$album_name.'%');
            $audio_id_list = $med_audio_to_album_model
                             ->field("cmf_med_audio_to_album.audio_id")
                             ->join("cmf_med_audio_album ON cmf_med_audio_album.id = cmf_med_audio_to_album.album_id","left")
                             ->where($join_where)
                             ->group("cmf_med_audio_to_album.audio_id")
                             ->select();
            $len = count($audio_id_list);
            if($len==0){
                $where['id'] = 0;
            }else if($len==1){
                $where['id'] = intval($audio_id_list[0]['audio_id']);
            }else{
                $in_str = array();
                foreach($audio_id_list as $key=>$value){
                    $in_str[] = $value['audio_id'];
                }
                $where['id'] = array('in',implode(',',$in_str));
            }
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

        if($check_is_special){
            $where['is_special'] = 1;
        }

        //分页
        $count = $audio_model->where($where)->count();
        $page = $this->page($count, 15);
        $list = $audio_model->where($where)->limit($page->firstRow,$page->listRows)->order('createtime desc')->select();
        foreach($list as $key=>$value){
            $album_list = $med_audio_to_album_model
                          ->field("cmf_med_audio_album.album_name")
                          ->join("cmf_med_audio_album ON cmf_med_audio_album.id = cmf_med_audio_to_album.album_id")
                          ->where(array("cmf_med_audio_to_album.audio_id"=>$value['id']))
                          ->select();
            $list[$key]["album_list"] = $album_list;
        }

        $this->assign('list',$list);
        $this->assign("page", $page->show('Admin'));
        $this->assign("start_time",$start_time);
        $this->assign("end_time",$end_time?$end_time:date('Y-m-d H:i',NOW_TIME));
        $this->assign("audio_name",$audio_name);
        $this->assign("audio_reciter",$audio_reciter);
        $this->assign("album_name",$album_name);
        $this->assign("check_is_special",$check_is_special);
        $this->display();
    }

    /**
     * 编辑
     * 音频信息
     * 页面显示
     */
    public function audio_info_edit(){
        //参数
        $id = I('id');
        $med_audio_to_album_model = M('med_audio_to_album');
        $audio_difficulty_model = M("med_audio_difficulty");
        $audio_album_model = M('med_audio_album');


        $info = M('med_audio')->where(array('id'=>$id))->find();

        if($info){
            //音频长度转化显示
            if($info['audio_duration']>60){
                 $min = floor($info['audio_duration']/60); //分
                 $sec = $info['audio_duration']%60; //秒
                $info['audio_duration_str'] = $min."分".$sec."秒";
                }else{
                $info['audio_duration_str'] = $info['audio_duration']+"秒";
            }
            //已选择的专辑分类
            $album_ids_checekd = $med_audio_to_album_model
                ->field("cmf_med_audio_album.id")
                ->join("cmf_med_audio_album ON cmf_med_audio_album.id = cmf_med_audio_to_album.album_id")
                ->where(array("cmf_med_audio_to_album.audio_id"=>$info['id']))
                ->select();
            $ids_arr = array();
            foreach($album_ids_checekd as $key=>$value){
                $ids_arr[] = $value['id'];
            }
            $info["album_ids_checekd"] = implode(",",$ids_arr);
            $info["album_list"] = $audio_album_model->field("id,album_name")->select();

            $difficulty_list = $audio_difficulty_model->select();
            $this->assign("difficulty_list",$difficulty_list);
            $this->assign("info",$info);
        }else{
            $this->error("编辑错误!");
        }
        $this->display("audio_info_index");
    }

    /**
     * 删除
     * 音频上传
     */
    public function audio_info_del(){
        //参数
        $audio_id = I('id');
        $audio_model = M('med_audio');
        $med_audio_to_album_model = M('med_audio_to_album');
        if(empty($audio_id)){
            $this->error("参数错误");
        }else{
            try{
                $audio_model->startTrans();
                $info = $audio_model->field("audio_cover,audio_url")->where(array('id'=>$audio_id))->find();

                //删除数据库信息
                $rs = $audio_model->where(array('id'=>$audio_id))->delete();

                //删除图片和音频
               //$audio_url = iconv('utf-8', 'gbk', $info['audio_url']);

                if($rs&&unlink(".". $info['audio_url'])){
                    //如果封面存在则将他删除
                    if($info['audio_cover']){
                        unlink(".".$info['audio_cover']);
                    }
                    $med_audio_to_album_model->where(array('audio_id'=>$audio_id))->delete();
                    $audio_model->commit();
                    $return  = array('code'=>0,'message'=>'删除成功','data'=>$rs);
                }else{
                    $audio_model->rollback();
                    $return  = array('code'=>-1,'message'=>'删除失败','data'=>$rs);
                }
            }catch(Exception $e){
               $audio_model->rollback();
                    $return  = array('code'=>-1,'message'=>'删除失败','data'=>$rs);
            }
            $this->ajaxReturn($return);
        }
    }


    /**
     * 普通首页布局
     * 列表
     */
    public function layout_normal_list(){
        //参数
        $loft_name = I('loft_name');

        $loft_item_model = M('med_loft_item');
        $album_model = M('med_audio_album');
        $difficulty_model = M('med_audio_difficulty');

        if($loft_name){
            $where['cmf_med_loft.loft_title'] = array('like','%'.$loft_name.'%');
        }

        $loft_list = $loft_item_model
                         ->field('cmf_med_loft_item.id,cmf_med_loft_item.loft_id,cmf_med_loft.loft_type,cmf_med_loft.loft_title,cmf_med_loft.loft_sort,cmf_med_loft.loft_display,cmf_med_loft_item.album_ids,cmf_med_loft_item.adv_img,cmf_med_loft_item.difficulty_ids')
                         ->join("cmf_med_loft ON cmf_med_loft.id = cmf_med_loft_item.loft_id","left")
                         ->where($where)
                         ->order('loft_sort')
                         ->select();

        foreach($loft_list as $key=>$value){
            $item_info = $loft_item_model->where(array('loft_id'=>$value['loft_id']))->find();

            if($item_info['album_ids']){
                $album_ids = unserialize($item_info['album_ids']);

                $album_list = array();
                foreach($album_ids as $k=>$v){
                    $album_list[$k] = $album_model->field("album_name,album_cover")->where(array('id'=>$v['album_id']))->find();

                }

                $loft_list[$key]['album_list'] = $album_list;

            }else if($item_info['difficulty_ids']){
                $difficulty_ids = unserialize($item_info['difficulty_ids']);
                $difficulty_list = array();
                foreach($difficulty_ids as $k=>$v){
                    $difficulty_list[$k] = $difficulty_model->field("difficulty_degree,difficulty_cover")->where(array('id'=>$v))->find();
                }
                $loft_list[$key]['difficulty_list'] = $difficulty_list;
            }

        }
        //var_dump($loft_list);
        $this->assign('loft_name',$loft_name);
        $this->assign('loft_list',$loft_list);
        $this->display();
    }

    /**
     * 布局板块项添加
     * 页
     */
    public function layout_normal_add(){
        $loft_model = M('med_loft');
        $album_model = M('med_audio_album');
        $difficulty_model = M('med_audio_difficulty');

        $loft_list = $loft_model->field('loft_type,loft_title')->select();
        $album_list = $album_model->select();

        $difficulty_list = $difficulty_model->select();

        $this->assign("difficulty_list",$difficulty_list);
        $this->assign('album_list',$album_list);
        $this->assign("loft_list",$loft_list);
        $this->display();
    }

    /**
     * 普通首页布局
     * 列表
     */
    public function layout_normal_add_post(){
        //参数
        $loft_type = I('loft_type');
        $album_ids = I('album_ids');
        $difficulty_ids = I('difficulty_ids');
        $photos_url = I('photos_url');
        $adv_url = trim(I('adv_url'));
        $adv_title = trim(I('adv_title'));
        $photos_url_edit = I('photos_url_edit');
        $edit_id  = I('edit_id');

        $loft_model = M('med_loft');
        $loft_item_model = M('med_loft_item');


        $adv_img = "";
        if($loft_type=="adv"){
            if($photos_url||$photos_url_edit){
                if($photos_url[0]){
                    $adv_img = C('IMG_UPLOAD_PATH').$photos_url[0];
                }else{
                    $adv_img = '';
                }

                if($photos_url_edit){
                    $adv_img = $photos_url_edit;
                }
            }else{
                $this->error('参数错误');
            }
        }else{
            if(empty($loft_type)||(empty($album_ids)&&empty($difficulty_ids))){
                $this->error('参数错误');
            }
            if($loft_type=="home1"&&count($album_ids)!=4){
                $this->error('必须选择4个专辑!');
            }
        }

        $loft_info = $loft_model->where(array('loft_type'=>$loft_type))->find();

        //将专辑排序
        if($album_ids){
            $album_list_sort = array();
            foreach($album_ids as $key=>$value){
                $album_list_sort[$key]['album_id'] = $value;
                $sort = I('album_sort_'.$value);
                if(is_numeric($sort)){
                    $album_list_sort[$key]['album_sort'] = $sort;
                }else{
                    $this->error('框中必须填数字!');
                }
            }
            $album_list_sort = $this->multi_array_sort($album_list_sort,"album_sort","SORT_ASC");
            $album_ids = serialize($album_list_sort);
        }else{
            $album_ids = '';
        }

        if($difficulty_ids){
            $difficulty_ids = serialize($difficulty_ids);
        }else{
            $difficulty_ids = '';
        }

        //编辑
        if($edit_id){
            $item_info = $loft_item_model->where(array('id'=>$edit_id))->count();
            if(!$item_info){
                $this->error("编辑错误");
            }
            $save = array(
                'album_ids'=>$album_ids,
                'difficulty_ids'=>$difficulty_ids,
                'adv_img'=>$adv_img,
                'adv_url'=>$adv_url,
                'adv_title'=>$adv_title
            );

            $rs = $loft_item_model->where(array('id'=>$edit_id))->save($save);

            if($rs){
                $this->success("版块子项编辑成功!");
            }else{
                $this->error("版块子项编辑失败，没有选项改变!");
            }

        }else{//新增
            if($loft_info['loft_type']!='adv'){
                if($loft_item_model->where(array('loft_id'=>$loft_info['id']))->count()){
                    $this->error('添加的版块已存在,请勿重复添加');
                }
            }
            $add = array(
                'loft_id'=>$loft_info['id'],
                'album_ids'=>$album_ids,
                'difficulty_ids'=>$difficulty_ids,
                'adv_img'=>$adv_img,
                'adv_url'=>$adv_url,
                'adv_title'=>$adv_title
            );
            $rs = $loft_item_model->add($add);
            if($rs){
                $this->success("版块子项添加成功!");
            }else{
                $this->error("版块子项添加失败!");
            }


        }


    }
    /**
     * 普通首页布局
     * 编辑
     */
    public function layout_normal_edit(){
        $edit_id = I("edit_id");
        $loft_model = M('med_loft');
        $loft_item_model =  M('med_loft_item');
        $album_model = M('med_audio_album');
        $difficulty_model = M('med_audio_difficulty');

        //返回的数据库字段
        $field = "
                cmf_med_loft.loft_type,
                cmf_med_loft.class_type,

                cmf_med_loft_item.id,
                cmf_med_loft_item.album_ids,
                cmf_med_loft_item.adv_img,
                cmf_med_loft_item.difficulty_ids,
                cmf_med_loft_item.adv_url,
                cmf_med_loft_item.adv_title
        ";

        $info = $loft_item_model
                            ->field($field)
                             ->join("cmf_med_loft ON cmf_med_loft.id = cmf_med_loft_item.loft_id","left")
                             ->where(array('cmf_med_loft_item.id'=>$edit_id))
                             ->find();

        if($info['album_ids']){
            $album_list_checked = unserialize($info['album_ids']);
            $ids_arr = array();
            foreach($album_list_checked as $key=>$value){
                $ids_arr[] = $value['album_id'];
            }
            $info["album_ids_checekd"] = implode(",",$ids_arr);

            $album_sort = array();
            foreach($album_list_checked as $key=>$value){
                $album_id = $value['album_id'];
                $album_sort[$album_id] = $value['album_sort'];
            }
        }else if($info['difficulty_ids']){
            $difficulty_list_checked = unserialize($info['difficulty_ids']);
            $info["difficulty_ids_checekd"] = implode(",",$difficulty_list_checked);
        }



        $loft_list = $loft_model->field('loft_type,loft_title')->select();
        $album_list = $album_model->select();
        $difficulty_list = $difficulty_model->select();

        $this->assign('album_list',$album_list);
        $this->assign('difficulty_list',$difficulty_list);
        $this->assign('album_sort',$album_sort);
        $this->assign("loft_list",$loft_list);
        $this->assign("info",$info);

        $this->display("layout_normal_add");
    }


    /**
     * 个性化算法设置
     * 页面显示
     */
    public function layout_special_setting(){
        $setting_model = M("med_special_setting");
        $setting_info = $setting_model->find();
        $this->assign('info',$setting_info);
        $this->display();
    }

    /**
     * 个性化算法设置
     * 播放数*click_percent+分享次数*share_percent
     */
    public function layout_special_setting_post(){
        $click_percent = I("click_percent");
        $share_percent = I("share_percent");
        $setting_model = M("med_special_setting");
        if($click_percent+$share_percent!=100){
             $this->error("播放数和分享数的权重比加起来要等于100");
        }
        $setting_info = $setting_model->find();
        $add = array(
            'click_percent'=>$click_percent,
            'share_percent'=>$share_percent,
            'createtime'=>time()
        );
        if($setting_info){
            $rs = $setting_model->where(array('id'=>$setting_info['id']))->save($add);
        }else{
            $rs = $setting_model->add($add);
        }
        if($rs){
           $this->success("操作成功!");
        }else{
            $this->error("操作失败!");
        }
    }


    /**
     * 新增
     * 难度系数
     * 页面显示
     */
    public function audio_difficulty_add(){
        $this->display();
    }

    /**
     * 列表
     * 难度系数
     */
    public function audio_difficulty_list(){
        //参数
        $difficulty_degree = I('difficulty_degree');
        $difficulty_model = M('med_audio_difficulty');

        if($difficulty_degree){
            $where['difficulty_degree'] = $difficulty_degree;
        }

        //分页
        $count = $difficulty_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $difficulty_model->where($where)->limit($page->firstRow,$page->listRows)->order('createtime desc')->select();

        $this->assign('list',$list);
        $this->assign("page", $page->show('Admin'));
        $this->assign("difficulty_degree",$difficulty_degree);
        $this->display();
    }

    /**
     * 新增提交
     * 编辑提交
     * 难度系数
     */
    public function audio_difficulty_post(){
        //参数
        $difficulty_degree = trim(I('difficulty_degree'));
        $difficulty_desc = trim(I('difficulty_desc'));
        $photos_url = I('photos_url');
        $difficulty_cover_edit = I('difficulty_cover');
        $difficulty_model = M('med_audio_difficulty');

        $edit_id = I('edit_id');
        if(!is_numeric($difficulty_degree)||$difficulty_degree<=0){
            $this->error("难度系数必须为大于0的数字!");
        }else{
            if(!$edit_id){
                if($difficulty_model->where(array('difficulty_degree'=>$difficulty_degree))->count()){
                    $this->error("该难度系数已存在!");
                }
            }
        }

        if($photos_url){
            $difficulty_cover = C('IMG_UPLOAD_PATH').$photos_url[0];
        }else{
            $difficulty_cover = '';
        }

        if($difficulty_cover_edit){
            $difficulty_cover = $difficulty_cover_edit;
        }



        if(IS_POST){
            if(empty($difficulty_degree)){
                $this->error("难度系数不能为空!");
            }if(empty($difficulty_cover)){
                $this->error("封面不能为空!");
            }

            $add = array(
                'difficulty_degree'=>$difficulty_degree,
                'difficulty_desc'=>$difficulty_desc,
                'difficulty_cover'=>$difficulty_cover,
                'createtime'=>time()
            );

            //编辑
            if($edit_id){
                $rs = $difficulty_model->where(array('id'=>$edit_id))->save($add);
                if($rs){
                    $this->success("难度系数编辑成功!");
                }else{
                    $this->error("难度系数编辑失败!");
                }
            }else{//添加
                $rs = $difficulty_model->add($add);
                if($rs){
                    $this->success("难度系数添加成功!");
                }else{
                    $this->error("难度系数添加失败!");
                }
            }
        }else{
            $this->error("提交失败!");
        }
    }

    /**
     * 编辑
     * 难度系数
     * 页面显示
     */
    public function audio_difficulty_edit(){
        //参数
        $id = I('id');
        $info = M('med_audio_difficulty')->where(array('id'=>$id))->find();
        if($info){
            $this->assign("info",$info);
        }else{
            $this->error("编辑错误!");
        }
        $this->display("audio_difficulty_add");
    }

    /**
     * 删除
     * 专辑分类
     */
    public function audio_difficulty_del(){
        //参数
        $difficulty_id = I('id');
        $audio_difficulty_model = M('med_audio_difficulty');

        if(empty($difficulty_id)){
            $return  = array('code'=>-1,'message'=>'参数错误');
        }else{
            $difficulty_info = $audio_difficulty_model->field("difficulty_cover,difficulty_like_count")->where(array('id'=>$difficulty_id))->find();
            //判断难度系数是否有关注,有则不能删除
            if($difficulty_info['difficulty_like_count']>0){
                $return  = array('code'=>-3,'message'=>'删除的难度系数有'.$difficulty_info['difficulty_like_count'].'个人关注,不允许删除!');
            }else{
                //删除数据库信息
                $rs = $audio_difficulty_model->where(array('id'=>$difficulty_id))->delete();
                //删除图片
                if($rs){
                    if($difficulty_info['difficulty_cover']){
                        unlink(".".$difficulty_info['difficulty_cover']);
                    }
                    $return  = array('code'=>0,'message'=>'删除成功','data'=>$rs);
                }else{
                    $return  = array('code'=>-1,'message'=>'删除失败');
                }
            }
        }
        $this->ajaxReturn($return);
    }

    /**
     * 删除
     * 小程序首页版块
     */
    public function loft_item_del(){
        $loft_item_id = I("id");
        $loft_item_model = M("med_loft_item");

        if(empty($loft_item_id)){
            $return  = array('code'=>-1,'message'=>'参数错误');
        }else{
            //判断adv不能少于1个
            if($loft_item_model
                ->join("cmf_med_loft ON cmf_med_loft.id = cmf_med_loft_item.loft_id")
                ->where(array("cmf_med_loft.loft_type"=>"adv"))->count()==1){
                $return  = array('code'=>-1,'message'=>'轮播图项不能少于1个,不允许删除!');
            }else{
                if($loft_item_model->where(array('id'=>$loft_item_id))->count()){
                    $rs = $loft_item_model->where(array('id'=>$loft_item_id))->delete();
                    if($rs){
                        $return  = array('code'=>0,'message'=>'删除成功','data'=>$rs);
                    }else{
                        $return  = array('code'=>-1,'message'=>'删除失败');
                    }
                }else{
                    $return  = array('code'=>-1,'message'=>'删除的内容不存在!');
                }
            }
        }
        $this->ajaxReturn($return);
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





}