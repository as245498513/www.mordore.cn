<?php
/**
 * Created by PhpStorm.
 * User: CMJ
 * Date: 2017/9/5
 * Time: 11:40
 */
namespace Api\Controller;
use Think\Controller;

class MedcheckController extends Controller{

    /**
     * 检查微信用户是否已经授权
     * @interface 3.1
     * @return array returnMsg
     */
    public function wx_user_is_auth(){
        //参数
        $wechat_id = I('wechat_id');
        if(empty($wechat_id)){
            $this->returnMsg(-2);
        }

        $wx_users_model = M('med_wx_users');

        $where['wx_wechat_id'] = $wechat_id;
        $user_id = $wx_users_model->field('id')->where($where)->find();

        if($user_id['id']){
            $rs = $wx_users_model->where(array('id'=>$user_id['id']))->save(array('last_login_time'=>time()));
            if($rs){
                $this->returnMsg(0,"已授权");
            }else{
                $this->returnMsg(-3,"数据库错误");
            }

        }else{
            $this->returnMsg(-1,"未授权");
        }

    }

    /**
     * 微信用户授权
     * @interface 3.2
     * @return array returnMsg
     */
    public function wx_user_auth(){
        //参数
        $wechat_id = I('wechat_id');
        $open_id = I('open_id');
        $nickname = I('nickname');

        $wx_users_model = M('med_wx_users');

        if(empty($wechat_id)||empty($nickname)||empty($open_id)){
            $this->returnMsg(-2);
        }

        $user_id = $wx_users_model->field('id')->where(array('wx_wechat_id'=>$wechat_id))->find();
        if($user_id){
            $this->returnMsg(-1,"请勿重复授权");
        }else{
            $now_time = time();
            $add = array(
                'wx_wechat_id'=>$wechat_id,
                'wx_openid'=>$open_id,
                'wx_nickname'=>$nickname,
                'last_login_time'=>$now_time,
                'createtime'=>$now_time
            );
            $rs = $wx_users_model->add($add);
            if($rs){
                $this->returnMsg(0,"授权成功");
            }else{
                $this->returnMsg(-1,"授权失败");
            }
        }

    }

    /**
     * 小程序登录
     * @interface 3.3
     * @return array returnMsg
     */
    public function wx_login(){
        $js_code = I('js_code'); //登录时获取的 code
        $grant_type = I('grant_type'); //填写为 authorization_code
        $wx_nickname = I('wx_nickname');

        $appid = C('APP_ID');     //小程序唯一标识
        $secret = C('SECRET');   //小程序的 app secret

        $now_time = time();

        $wx_user_model = M('med_wx_users');

        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$secret."&js_code=".$js_code."&grant_type=".$grant_type;

        //获取微信用户的session_key(判断用户登录状态)和openid
        $wx_session = json_decode($this->_getRequest($url),true) ;
        if($wx_session['errcode']){
            $this->ajaxReturn($wx_session);
        }else{
            //判断用户是否已存在
            $user_info = $wx_user_model->where(array('wx_wechat_openid'=>$wx_session['openid']))->find();
            $param = array('session_key'=>$wx_session['session_key'],'time'=>$now_time);
            $token = $this->encryptSession($param);
            if(!$user_info){
                $add = array(
                    'wx_nickname'=>$wx_nickname,
                    'wx_wechat_openid'=>$wx_session['openid'],
                    'createtime'=>$now_time,
                    'last_login_time'=>$now_time,
                    'token'=>$token,
                    'session_key'=>$wx_session['session_key'],
                    'overdue_time'=>$now_time+30*60 //过期时间为30分钟
                );
                $rs = $wx_user_model->add($add);
                $return_data = array('token'=>$token,'openid'=>$wx_session['openid']);
                if($rs){
                    $this->returnMsg(0,"登录授权成功",$return_data);
                }else{
                    $this->returnMsg(-1,"登录授权失败");
                }
            }else{
                $save = array(
                    'last_login_time'=>$now_time,
                    'token'=>$token,
                    'session_key'=>$wx_session['session_key'],
                    'overdue_time'=>$now_time+30*60 //过期时间为30分钟
                );
                $rs = $wx_user_model->where(array('wx_wechat_openid'=>$wx_session['openid']))->save($save);
                $return_data = array('token'=>$token,'openid'=>$wx_session['openid']);
                if($rs){
                    $this->returnMsg(0,"登录授权成功",$return_data);
                }else{
                    $this->returnMsg(-1,"登录授权失败");
                }
            }
        }
    }


    /**
     * GET 请求
     */
    private static function _getRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

    /**
     * 加密微信session
     */
    protected function encryptSession($param) {
        // 参数为空不参与签名
        $param = array_filter($param);
        ksort($param);
        $str  = http_build_query($param);
        $str  = $str.'&secret='.C('SECRET');
        $sign = strtoupper(md5($str));
        return $sign;
    }

    public function returnMsg($code,$message=null,$data=null){
        //常用
        switch ($code) {
            case -2:
                $d_message = "参数错误";
                break;
            case -1:
                $d_message = "失败";
                break;
            case 0:
                $d_message = "成功";
                break;

            default:
                # code...
                break;
        }
        $message = $message ? $message : $d_message;

        $return = array(
            'code'=>$code,
            'message'=>$message,
            'data'=>$data,
        );
        $this->ajaxReturn($return);
    }




}