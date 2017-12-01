<?php
/*素材模块*/
namespace User\Controller;
use Common\Controller\MemberbaseController;

class SourceController extends MemberbaseController {
    protected $members_model,$role_model,$role_user_model;
    public function index(){

    }
    /**
     *素材分类
     *列表页面
     */
     public function class_list(){
         $wx_source_class = D('Common/WxSourceClass');

         $source_type = I('source_type');
         $class_name = trim(I('class_name'));

         $where['user_id'] = sp_get_current_member_id();
         if($source_type!=0){
             $where['source_type'] = $source_type;
         }
         if($class_name){
            $where['class_name'] = array('like','%'.$class_name.'%');
         }

         $count = $wx_source_class->where($where)->count();
         $page = $this->page($count, 15);

         $list = $wx_source_class->field('id,class_name,class_sort,source_type,createtime')->where($where)->order('class_sort asc,createtime asc')->limit($page->firstRow, $page->listRows)->select();

         $this->assign('list',$list);
         $this->assign('source_type',$source_type);
         $this->assign('class_name',$class_name);
         $this->assign("page", $page->show('Admin'));
         $this->display();
     }
    /**
     *素材分类
     *添加页面
     */
    public function class_add(){
        $this->display();
    }
    /**
     *素材分类
     *添加和编辑
     */
    public function class_add_post(){
        $wx_source_class = D('Common/WxSourceClass');
        $user_id = sp_get_current_member_id();
        if(IS_POST){
            if($wx_source_class->create()!=false){
                //参数
                $class_name = trim(I('class_name'));
                $class_sort = I('class_sort');
                $source_type = I('source_type');
                $class_id = I('class_id');

                if(empty($class_name)||empty($class_sort)||empty($source_type)){
                    $this->error("类别不能为空");
                }else{
                    if(!$class_id){
                        //新增
                        //检查是否存在
                        $is_exist = array(
                            'user_id'=>$user_id,
                            'class_name'=>$class_name,
                            'source_type'=>$source_type
                        );
                        $rs = $wx_source_class->field('id')->where($is_exist)->find();
                        if($rs){
                            $this->error("该分类已存在");
                        }else{
                            $add = array(
                                'class_name'=>$class_name,
                                'class_sort'=>$class_sort,
                                'source_type'=>$source_type,
                                'user_id'=>$user_id,
                                'createtime'=>NOW_TIME
                            );
                            $wx_source_class->add($add);
                            $this->success("添加成功!");
                        }
                    }else{
                        //编辑
                       $save = array(
                           'class_name'=>$class_name,
                           'class_sort'=>$class_sort,
                           'source_type'=>$source_type,
                       );
                        $rs = $wx_source_class->where(array('id'=>$class_id))->save($save);
                        if($rs){
                            $this->success("编辑成功!",U('class_list'));
                        }else{
                            $this->error("编辑失败!");
                        }
                    }
                }
            }else{
                $this->error($wx_source_class->getError());
            }
        }
    }

    /**
     *素材分类
     *删除
     */
    public function class_del_post(){
        $ids = I('ids');
        $wx_source_class = D('Common/WxSourceClass');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $wx_source_class->where(array('id'=>$value))->delete();
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
     *素材分类
     *编辑
     */
    public function class_edit(){
        //参数
        $id = I('id');
        $wx_source_class = D('Common/WxSourceClass');
        $class_info = $wx_source_class->field('id,class_name,class_sort,source_type')->where(array('id'=>$id))->find();
        if(!$class_info){
            $this->error("编辑出错");
        }
        $this->assign('class_info',$class_info);
        $this->display('class_add');
    }


    /**
     *朋友圈图文素材
     *列表页面
     */
    public function momentsimgtxt_list(){
        $momentsimgtxt_model = D('Common/WxSourceMomentsImgtxt');

        //参数
        $title = I('title');
        $content = I('content');
        $choose_source = I('choose_source');

        $where['cmf_wx_source_moments_imgtxt.user_id'] = sp_get_current_member_id();
        if($title){
            $where['cmf_wx_source_moments_imgtxt.title'] = array('like','%'.$title.'%');
        }
        if($content){
            $where['cmf_wx_source_moments_imgtxt.content'] = array('like','%'.$content.'%');
        }

        $count = $momentsimgtxt_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $momentsimgtxt_model->field('cmf_wx_source_moments_imgtxt.id,cmf_wx_source_moments_imgtxt.title,cmf_wx_source_moments_imgtxt.content,cmf_wx_source_moments_imgtxt.comments,cmf_wx_source_moments_imgtxt.createtime,cmf_wx_source_moments_imgtxt.source_class_id,cmf_wx_source_class.class_name')->join('cmf_wx_source_class ON cmf_wx_source_class.id = cmf_wx_source_moments_imgtxt.source_class_id')->where($where)->order('cmf_wx_source_moments_imgtxt.createtime asc')->limit($page->firstRow, $page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('title',$title);
        $this->assign('content',$content);
        $this->assign('choose_source',$choose_source);
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     *朋友圈图文素材
     *添加页面
     */
    public function momentsimgtxt_add(){
        $wx_source_class = D('Common/WxSourceClass');
        $user_id = sp_get_current_member_id();
        $class_list = $wx_source_class->where(array('source_type'=>1,'user_id'=>$user_id))->select();
        $this->assign("class_list",$class_list);
        $this->display();
    }

    /**
     *朋友圈图文素材
     *添加提交
     */
    public function momentsimgtxt_add_post(){
        $momentsimgtxt_model = D('Common/WxSourceMomentsImgtxt');
        $user_id = sp_get_current_member_id();
        if(IS_POST){
            if($momentsimgtxt_model->create()!=false){
                $title = I('title');
                $content = I('content');
                $comments = I('comments');
                $source_class = I('source_class');
                $photos_urls = I('photos_url');//图片地址
                $photos_alts = I('photos_alt');//图片名字
                $comments_imgtxt_id = I('comments_imgtxt_id');
                if(!$photos_urls){
                   $this->error("图片不能为空");
                }else{
                    //新增
                    if(!$comments_imgtxt_id){
                        $add = array(
                            'title'=>$title,
                            'content'=>$content,
                            'comments'=>$comments,
                            'photo_urls'=>serialize(json_encode($photos_urls)),
                            'photo_alts'=>serialize(json_encode($photos_alts)),
                            'source_class_id'=>$source_class,
                            'user_id'=>$user_id,
                            'createtime'=>NOW_TIME
                        );
                        $rs = $momentsimgtxt_model->add($add);
                        if(!$rs){
                            $this->error("添加失败");
                        }else{
                            $this->success("添加成功",U('momentsimgtxt_list'));
                        }
                    }else{
                        //编辑
                        $save = array(
                            'title'=>$title,
                            'content'=>$content,
                            'comments'=>$comments,
                            'source_class_id'=>$source_class,
                            'photo_urls'=>serialize(json_encode($photos_urls)),
                            'photo_alts'=>serialize(json_encode($photos_alts)),
                        );
                        $rs = $momentsimgtxt_model->where(array('id'=>$comments_imgtxt_id))->save($save);
                        if(!$rs){
                            $this->error("编辑失败");
                        }else{
                            $this->success("编辑成功",U('momentsimgtxt_list'));
                        }
                    }
                }

            }else{
                $this->error($momentsimgtxt_model->getError());
            }
        }
    }

    /**
     *朋友圈图文素材
     *编辑
     */
    public function momentsimgtxt_edit(){
        //参数
        $id = I('id');
        $momentsimgtxt_model = D('Common/WxSourceMomentsImgtxt');
        $wx_source_class = D('Common/WxSourceClass');
        $info = $momentsimgtxt_model->field('id,title,content,comments,photo_urls,photo_alts,source_class_id')->where(array('id'=>$id))->find();
        $info['photo_urls'] = json_decode(unserialize($info['photo_urls']));
        $info['photo_alts'] = json_decode(unserialize($info['photo_alts']));
        if(!$info){
            $this->error("编辑出错");
        }
        $user_id = sp_get_current_member_id();
        $class_list = $wx_source_class->where(array('source_type'=>1,'user_id'=>$user_id))->select();
        $this->assign('info',$info);
        $this->assign('class_list',$class_list);
        $this->display('momentsimgtxt_add');
    }

    /**
     *朋友圈图文素材
     *删除
     */
    public function momentsimgtxt_del_post(){
        $ids = I('ids');
        $momentsimgtxt_model = D('Common/WxSourceMomentsImgtxt');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $momentsimgtxt_model->where(array('id'=>$value))->delete();
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
     *图片素材
     *列表页面
     */
    public function image_list(){
        $image_model = D('Common/WxSourceImage');
        $wx_source_class = D('Common/WxSourceClass');
        $user_id = sp_get_current_member_id();
        //参数
        $title = I('title');
        $source_class_id = I('source_class');

        $where['cmf_wx_source_image.user_id'] = sp_get_current_member_id();
        if($title){
            $where['cmf_wx_source_image.title'] = array('like','%'.$title.'%');
        }
        if($source_class_id!=0){
            $where['cmf_wx_source_image.source_class_id'] = $source_class_id;
        }

        $count = $image_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $image_model->field('cmf_wx_source_image.id,cmf_wx_source_image.title,cmf_wx_source_image.source_class_id,cmf_wx_source_image.createtime,cmf_wx_source_class.class_name')->join('cmf_wx_source_class ON cmf_wx_source_class.id = cmf_wx_source_image.source_class_id')->where($where)->order('cmf_wx_source_image.createtime asc')->limit($page->firstRow, $page->listRows)->select();

        //分类下拉框
        $class_list = $wx_source_class->where(array('source_type'=>3,'user_id'=>$user_id))->select();

        $this->assign('list',$list);
        $this->assign('title',$title);
        $this->assign('source_class_id',$source_class_id);
        $this->assign("class_list",$class_list);
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     *图片素材
     *添加页面
     */
    public function image_add(){
        $wx_source_class = D('Common/WxSourceClass');
        $user_id = sp_get_current_member_id();
        $class_list = $wx_source_class->where(array('source_type'=>3,'user_id'=>$user_id))->select();
        $this->assign("class_list",$class_list);
        $this->display();
    }


    /**
     *图片素材
     *添加提交
     */
    public function image_add_post(){
        $image_model = D('Common/WxSourceImage');
        $user_id = sp_get_current_member_id();
        if(IS_POST){
            if($image_model->create()!=false){
                $title = I('title');
                $source_class = I('source_class');
                $photos_urls = I('photos_url');//图片地址
                $photos_alts = I('photos_alt');//图片名字
                $image_id = I('image_id');
                if(!$photos_urls){
                    $this->error("图片不能为空");
                }else{
                    //新增
                    if(!$image_id){
                        $add = array(
                            'title'=>$title,
                            'source_class_id'=>$source_class,
                            'photo_urls'=>serialize(json_encode($photos_urls)),
                            'photo_alts'=>serialize(json_encode($photos_alts)),
                            'user_id'=>$user_id,
                            'createtime'=>NOW_TIME
                        );
                        $rs = $image_model->add($add);
                        if(!$rs){
                            $this->error("添加失败");
                        }else{
                            $this->success("添加成功",U('image_list'));
                        }
                    }else{
                        //编辑
                        $save = array(
                            'title'=>$title,
                            'source_class_id'=>$source_class,
                            'photo_urls'=>serialize(json_encode($photos_urls)),
                            'photo_alts'=>serialize(json_encode($photos_alts)),
                        );
                        $rs = $image_model->where(array('id'=>$image_id))->save($save);
                        if(!$rs){
                            $this->error("编辑失败");
                        }else{
                            $this->success("编辑成功",U('image_list'));
                        }
                    }
                }

            }else{
                $this->error($image_model->getError());
            }
        }
    }


    /**
     *图片素材
     *编辑
     */
    public function image_edit(){
        //参数
        $id = I('id');
        $image_model = D('Common/WxSourceImage');
        $wx_source_class = D('Common/WxSourceClass');
        $info = $image_model->field('id,title,photo_urls,photo_alts,source_class_id')->where(array('id'=>$id))->find();
        $info['photo_urls'] = json_decode(unserialize($info['photo_urls']));
        $info['photo_alts'] = json_decode(unserialize($info['photo_alts']));
        if(!$info){
            $this->error("编辑出错");
        }
        $user_id = sp_get_current_member_id();
        $class_list = $wx_source_class->where(array('source_type'=>3,'user_id'=>$user_id))->select();
        $this->assign('info',$info);
        $this->assign('class_list',$class_list);
        $this->display('image_add');
    }

    /**
     *图片素材
     *删除
     */
    public function image_del_post(){
        $ids = I('ids');
        $image_model = D('Common/WxSourceImage');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $image_model->where(array('id'=>$value))->delete();
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
     *打招呼素材
     *列表页面
     */
    public function greet_list(){
        $greet_model = D('Common/WxSourceGreet');
        //参数
        $title = I('title');
        $content = I('content');

        $where['user_id'] = sp_get_current_member_id();
        if($title){
            $where['title'] = array('like','%'.$title.'%');
        }
        if($content){
            $where['content'] = array('like','%'.$content.'%');
        }

        $count = $greet_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $greet_model->field('id,title,content,createtime')->where($where)->order('createtime asc')->limit($page->firstRow, $page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('title',$title);
        $this->assign('content',$content);
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     *打招呼素材
     *添加页面
     */
    public function greet_add(){
        $this->display();
    }

    /**
     *打招呼素材
     *添加提交
     */
    public function greet_add_post(){
        $greet_model = D('Common/WxSourceGreet');
        $user_id = sp_get_current_member_id();
        if(IS_POST){
            if($greet_model->create()!=false){
                $title = I('title');
                $content = I('content');
                $greet_id = I('greet_id');
                //新增
                if(!$greet_id){
                    $add = array(
                        'title'=>$title,
                        'content'=>$content,
                        'user_id'=>$user_id,
                        'createtime'=>NOW_TIME
                    );
                    $rs = $greet_model->add($add);
                    if(!$rs){
                        $this->error("添加失败");
                    }else{
                        $this->success("添加成功",U('greet_list'));
                    }
                }else{
                        //编辑
                        $save = array(
                            'title'=>$title,
                            'content'=>$content,
                        );
                        $rs = $greet_model->where(array('id'=>$greet_id))->save($save);
                        if(!$rs){
                            $this->error("编辑失败");
                        }else{
                            $this->success("编辑成功",U('greet_list'));
                        }
                }
            }else{
                $this->error($greet_model->getError());
            }
        }
    }

    /**
     *打招呼素材
     *编辑
     */
    public function greet_edit(){
        //参数
        $id = I('id');
        $greet_model = D('Common/WxSourceGreet');
        $info = $greet_model->field('id,title,content')->where(array('id'=>$id))->find();
        if(!$info){
            $this->error("编辑出错");
        }
        $user_id = sp_get_current_member_id();
        $this->assign('info',$info);
        $this->display('greet_add');
    }

    /**
     *打招呼素材
     *删除
     */
    public function greet_del_post(){
        $ids = I('ids');
        $greet_model = D('Common/WxSourceGreet');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $greet_model->where(array('id'=>$value))->delete();
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
     *朋友圈评论素材
     *列表页面
     */
    public function moments_comments_list(){
        $moments_comments_model = M('WxSourceMomentsComments');
        $comments_item_model = M('WxSourceCommentsItem');
        //参数
        $title = I('title');
        $content = I('content');
        $choose_source = I('choose_source');

        $where['user_id'] = sp_get_current_member_id();
        if($title){
            $where['title'] = array('like','%'.$title.'%');
        }
        //查询评论内容
        if($content){
            $where_item['comments'] = array('like','%'.$content.'%');
            $where_item['user_id'] = sp_get_current_member_id();
            $item_list = $comments_item_model->field('comments_id')->where($where_item)->group('comments_id')->select();
            $len = count($item_list);
            $in_str = '';
            foreach($item_list as $key=>$value){
                 if($key!=($len-1)){
                     $in_str .= $value['comments_id'].',';
                 }else{
                     $in_str .= $value['comments_id'];
                 }
            }
            if($len>1){
                $where['id'] = array('in',$in_str);
            }else if($len==1){
                $where['id'] = array('eq',$item_list[0]['comments_id']);
            }else if($len==0){
                $where['id'] = array('eq',0);
            }
        }

        $count = $moments_comments_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $moments_comments_model->field('id,title,createtime')->where($where)->order('createtime asc')->limit($page->firstRow, $page->listRows)->select();


        foreach($list as $key => $value){
            $comments = $comments_item_model->where(array('comments_id'=>$value['id']))->select();
            foreach($comments as $k=>$v){
                $sort = $v['comments_sort'];
                $list[$key]['comments_str'] .= '评论'.$sort.':'.$v['comments'].'；';
            }
        }

        $this->assign('list',$list);
        $this->assign('title',$title);
        $this->assign('choose_source',$choose_source);
        $this->assign('content',$content);
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     *朋友圈评论素材
     *添加页面
     */
    public function moments_comments_add(){
        $this->display();
    }

    /**
     *朋友圈评论素材
     *添加提交
     */
    public function moments_comments_add_post(){
        $title = I('title');
        $comments = I('comments');
        $edit_id = I('edit_id');
        $user_id = sp_get_current_member_id();
        $moments_comments_model = M('WxSourceMomentsComments');
        $comments_item_model = M('WxSourceCommentsItem');
        if(empty($comments)||empty($title)){
           $this->error("标题或内容不能为空");
        }
        if(IS_POST){
            if(!$edit_id){
                //新增
                $add = array(
                    'title'=>$title,
                    'user_id'=>$user_id,
                    'createtime'=>NOW_TIME
                );
                $rs = $moments_comments_model->add($add);
                $last_id = $moments_comments_model->getLastInsID();
                if(!$rs){
                    $this->error("添加失败");
                }else{
                    foreach($comments as $key=>$value){
                        $add_item[] =  array(
                            'comments'=>$value,
                            'comments_id'=>$last_id,
                            'comments_sort'=>$key+1,
                            'createtime'=>NOW_TIME,
                            'user_id'=>$user_id
                        );
                    }
                    $item_rs = $comments_item_model->addAll($add_item);
                    if($item_rs){
                        $this->success("添加成功",U('moments_comments_list'));
                    }else{
                        $this->error("添加失败");
                    }
                }
            }else{
                //编辑
                $save = array(
                    'title'=>$title,
                );
                $moments_comments_model->where(array('id'=>$edit_id))->save($save);
                foreach($comments as $key=>$value){
                        $add_item[] =  array(
                            'comments'=>$value,
                            'comments_id'=>$edit_id,
                            'comments_sort'=>$key+1,
                            'createtime'=>NOW_TIME,
                            'user_id'=>$user_id
                        );
                    }
                $comments_item_model->where(array('comments_id'=>$edit_id))->delete();
                $item_rs = $comments_item_model->addAll($add_item);
                if($item_rs){
                    $this->success("编辑成功",U('moments_comments_list'));
                }else{
                    $this->error("编辑失败");
                }

            }
        }
    }

    /**
     *朋友圈评论素材
     *编辑
     */
    public function moments_comments_edit(){
        //参数
        $id = I('id');
        $moments_comments_model = M('WxSourceMomentsComments');
        $comments_item_model = M('WxSourceCommentsItem');
        $user_id = sp_get_current_member_id();
        $info = $moments_comments_model->field('id,title')->where(array('id'=>$id,'user_id'=>$user_id))->find();
        $item_list = $comments_item_model->field('comments,comments_id,comments_sort')->where(array('comments_id'=>$id,'user_id'=>$user_id))->select();
        if(!$info){
            $this->error("编辑出错");
        }
        $info['comments_list'] = $item_list;
        $this->assign('info',$info);
        $this->display('moments_comments_add');
    }

    /**
     *朋友圈评论素材
     *删除
     */
    public function moments_comments_del_post(){
        $ids = I('ids');
        $moments_comments_model = M('WxSourceMomentsComments');
        $comments_item_model = M('WxSourceCommentsItem');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $moments_comments_model->where(array('id'=>$value))->delete();
            $comments_item_model->where(array('comments_id'=>$value))->delete();
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
     *分享网址素材
     *列表页面
     */
    public function shareurl_list(){
        $shareurl_model = D('Common/WxSourceShareurl');
        //参数
        $title = I('title');
        $shareurl = I('shareurl');
        $choose_source = I('choose_source');

        $where['user_id'] = sp_get_current_member_id();
        if($title){
            $where['title'] = array('like','%'.$title.'%');
        }
        if($shareurl){
            $where['shareurl'] = array('like','%'.$shareurl.'%');
        }

        $count = $shareurl_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $shareurl_model->field('id,title,url,createtime')->where($where)->order('createtime asc')->limit($page->firstRow, $page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('title',$title);
        $this->assign('choose_source',$choose_source);
        $this->assign('shareurl',$shareurl);
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     *分享网址素材
     *添加页面
     */
    public function shareurl_add(){
        $this->display();
    }

    /**
     *分享网址素材
     *添加提交
     */
    public function shareurl_add_post(){
        $shareurl_model = D('Common/WxSourceShareurl');
        $user_id = sp_get_current_member_id();
        if(IS_POST){
            if($shareurl_model->create()!=false){
                $title = I('title');
                $shareurl = I('shareurl');
                $edit_id = I('edit_id');
                //新增
                if(!$edit_id){
                    $add = array(
                        'title'=>$title,
                        'url'=>$shareurl,
                        'user_id'=>$user_id,
                        'createtime'=>NOW_TIME
                    );
                    $rs = $shareurl_model->add($add);
                    if(!$rs){
                        $this->error("添加失败");
                    }else{
                        $this->success("添加成功",U('shareurl_list'));
                    }
                }else{
                    //编辑
                    $save = array(
                        'title'=>$title,
                        'url'=>$shareurl,
                    );
                    $rs = $shareurl_model->where(array('id'=>$edit_id))->save($save);
                    if(!$rs){
                        $this->error("编辑失败");
                    }else{
                        $this->success("编辑成功",U('shareurl_list'));
                    }
                }
            }else{
                $this->error($shareurl_model->getError());
            }
        }
    }

    /**
     *分享网址素材
     *编辑
     */
    public function shareurl_edit(){
        //参数
        $id = I('id');
        $shareurl_model = D('Common/WxSourceShareurl');
        $info = $shareurl_model->field('id,title,url')->where(array('id'=>$id))->find();
        if(!$info){
            $this->error("编辑出错");
        }
        $user_id = sp_get_current_member_id();
        $this->assign('info',$info);
        $this->display('shareurl_add');
    }

    /**
     *分享网址素材
     *删除
     */
    public function shareurl_del_post(){
        $ids = I('ids');
        $shareurl_model = D('Common/WxSourceShareurl');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $shareurl_model->where(array('id'=>$value))->delete();
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
     *好友发消息素材
     *列表页面
     */
    public function friend_msg_list(){
        $friend_msg_model = D('Common/WxSourceFriendMsg');
        //参数
        $title = I('title');
        $content = I('content');

        $where['user_id'] = sp_get_current_member_id();
        if($title){
            $where['title'] = array('like','%'.$title.'%');
        }
        if($content){
            $where['content'] = array('like','%'.$content.'%');
        }

        $count = $friend_msg_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $friend_msg_model->field('id,title,content,createtime')->where($where)->order('createtime asc')->limit($page->firstRow, $page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('title',$title);
        $this->assign('content',$content);
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     *好友发消息素材
     *添加页面
     */
    public function friend_msg_add(){
        $this->display();
    }

    /**
     *好友发消息素材
     *添加提交
     */
    public function friend_msg_add_post(){
        $friend_msg_model = D('Common/WxSourceFriendMsg');
        $user_id = sp_get_current_member_id();
        if(IS_POST){
            if($friend_msg_model->create()!=false){
                $title = I('title');
                $content = I('content');
                $edit_id = I('edit_id');
                if(!$content){
                   $this->error("内容不能为空");
                }
                //新增
                if(!$edit_id){
                    $add = array(
                        'title'=>$title,
                        'content'=>$content,
                        'user_id'=>$user_id,
                        'createtime'=>NOW_TIME
                    );
                    $rs = $friend_msg_model->add($add);
                    if(!$rs){
                        $this->error("添加失败");
                    }else{
                        $this->success("添加成功",U('friend_msg_list'));
                    }
                }else{
                    //编辑
                    $save = array(
                        'title'=>$title,
                        'content'=>$content,
                    );
                    $rs = $friend_msg_model->where(array('id'=>$edit_id))->save($save);
                    if(!$rs){
                        $this->error("编辑失败");
                    }else{
                        $this->success("编辑成功",U('friend_msg_list'));
                    }
                }
            }else{
                $this->error($friend_msg_model->getError());
            }
        }
    }

    /**
     *好友发消息素材
     *编辑
     */
    public function friend_msg_edit(){
        //参数
        $id = I('id');
        $friend_msg_model = D('Common/WxSourceFriendMsg');
        $info = $friend_msg_model->field('id,title,content')->where(array('id'=>$id))->find();
        if(!$info){
            $this->error("编辑出错");
        }
        $this->assign('info',$info);
        $this->display('friend_msg_add');
    }

    /**
     *好友发消息素材
     *删除
     */
    public function friend_msg_del_post(){
        $ids = I('ids');
        $friend_msg_model = D('Common/WxSourceFriendMsg');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $friend_msg_model->where(array('id'=>$value))->delete();
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
     *加好友素材
     *列表页面
     */
    public function friend_list(){
        $friend_model = D('Common/WxSourceFriend');
        //参数
        $title = I('title');
        $province = I('province');
        $city = I('city');

        $where['user_id'] = sp_get_current_member_id();
        if($title){
            $where['title'] = array('like','%'.$title.'%');
        }
        if($province){
            $where['province'] = array('like','%'.$province.'%');
        }
        if($city){
            $where['city'] = array('like','%'.$city.'%');
        }

        $count = $friend_model->where($where)->count();
        $page = $this->page($count, 15);

        $list = $friend_model->field('id,title,province,city,add_source_type,quantity,createtime')->where($where)->order('createtime asc')->limit($page->firstRow, $page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('title',$title);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign("page", $page->show('Admin'));
        $this->display();

    }

    /**
     *加好友素材
     *添加页面
     */
    public function friend_add(){
        $this->display();
    }

    /**
     *加好友素材
     *添加提交
     */
    public function friend_add_post(){
        $friend_model = D('Common/WxSourceFriend');
        $user_id = sp_get_current_member_id();
        if(IS_POST){
            if($friend_model->create()!=false){
                $title = I('title');
                $add_type = I('add_type');
                $province = I('province');
                $city = I('city');
                $quantity = I('quantity');
                $edit_id = I('edit_id');
                //新增
                if(!$edit_id){
                    $add = array(
                        'title'=>$title,
                        'add_type'=>$add_type,
                        'province'=>$province,
                        'city'=>$city,
                        'quantity'=>$quantity,
                        'user_id'=>$user_id,
                        'createtime'=>NOW_TIME
                    );
                    $rs = $friend_model->add($add);
                    if(!$rs){
                        $this->error("添加失败");
                    }else{
                        $this->success("添加成功",U('friend_list'));
                    }
                }else{
                    //编辑
                    $save = array(
                        'title'=>$title,
                        'add_type'=>$add_type,
                        'province'=>$province,
                        'city'=>$city,
                        'quantity'=>$quantity,
                    );
                    $rs = $friend_model->where(array('id'=>$edit_id))->save($save);
                    if(!$rs){
                        $this->error("编辑失败");
                    }else{
                        $this->success("编辑成功",U('friend_list'));
                    }
                }
            }else{
                $this->error($friend_model->getError());
            }
        }
    }

    /**
     *加好友素材
     *编辑
     */
    public function friend_edit(){
        //参数
        $id = I('id');
        $friend_model = D('Common/WxSourceFriend');
        $info = $friend_model->where(array('id'=>$id))->find();
        if(!$info){
            $this->error("编辑出错");
        }
        $this->assign('info',$info);
        $this->display('friend_add');
    }

    /**
     *加好友素材
     *删除
     */
    public function friend_del_post(){
        $ids = I('ids');
        $friend_model = D('Common/WxSourceFriend');
        $rs = 0;
        foreach($ids as $key=>$value){
            $del_rs = $friend_model->where(array('id'=>$value))->delete();
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
     *选择素材页面
     */
     public function choose_source_list(){
         //参数
         /*$source_model = I("source_model");//素材类型
         $title = I("title");
         $content = I("content");
         $user_id = sp_get_current_member_id();

         $model = M($source_model);
         $where['title'] = array('like','%'.$title.'%');
         $where['content'] = array('like','%'.$content.'%');
         $where['user_id'] = $user_id;
         $list = $model->where($where)->select();

         $this->assign('list',$list);
         $this->assign('title',$title);
         $this->assign('content',$content);*/
         $this->display();
     }
    /**
     *选择素材页面
     * 提交
     */
    public function choose_source_post(){

    }

}