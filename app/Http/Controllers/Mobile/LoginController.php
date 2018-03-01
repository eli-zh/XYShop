<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use Illuminate\Http\Request;
use Validator;

class LoginController extends Controller
{
    // 登陆
    public function getLogin()
    {
        // 存下来源页面
        $backurl = url()->previous() == '' || url()->previous() == config('app.url').'/login' ? url('/') : url()->previous();
        session()->put('backurl',$backurl);
        if (!session()->has('member')) {
            // 判断是不是微信
            if (app('com')->is_weixin()) {
                $wechat = app('wechat.official_account');
                $oauth = $wechat->oauth->withRedirectUrl(config('app.url').'/wxlogin');
                return $oauth->redirect();
            }
            else
            {
                $pos_id = 'home';
                return view(cache('config')['theme'].'.login',compact('pos_id'));
            }
        }
        else
        {
            return redirect($backurl);
        }
    }
    // 登陆
    public function postLogin(Request $req)
    {
        try {
            $validator = Validator::make($req->input(), [
              'phone' => 'required|numeric|digits_between:10,12',
              'passwd' => 'required|min:6|max:15',
            ]);
            $attrs = array(
              'phone' => '手机号',
              'passwd' => '新密码',
            );
            $validator->setAttributeNames($attrs);
            if ($validator->fails()) {
                // 如果有错误，提示第一条
                return back()->with('message',$validator->errors()->all()[0]);
            }
            // 看这个用户在不在数据库，不在，添加并登录，在直接登录
            $user = User::where('phone',$req->phone)->first();
            if (is_null($user)) {
                return back()->with('message','没有找到当前用户，请确认手机号正确！');
            }
            else
            {
                if (decrypt($user->password) != $req->passwd) {
                    return back()->with('message','密码不正确！');
                }
                if ($user->status == 0) {
                    return back()->with('message','用户被禁用，请联系管理员！');
                }
                User::where('id',$user->id)->update(['last_ip'=>$req->ip(),'last_time'=>date('Y-m-d H:i:s')]);
                session()->put('member',(object)['id'=>$user->id,'openid'=>$user->openid]);
            }
            return redirect($backurl);
        } catch (\Exception $e) {
            return back()->with('message','登陆失败，请稍后再试！');
        }
    }
    // 微信直接登陆
    public function getWxLogin(Request $req)
    {
        $backurl = session('backurl') == '' || session('backurl') == url('login') ? url('/') : session('backurl');
        try {
          $wechat = app('wechat.official_account');
          $oauth = $wechat->oauth;
          // 获取 OAuth 授权结果用户信息
          $wxuser = $oauth->user();
          // 看这个用户在不在数据库，不在，添加并登录，在直接登录
          $user = User::where('openid',$wxuser->id)->first();
          if (is_null($user)) {
            $sex = $wxuser->sex == '' ? 0 : $wxuser->sex;
            $res = User::create(['openid'=>$wxuser->id,'nickname'=>$wxuser->name,'sex'=>$sex,'thumb'=>$wxuser->avatar,'status'=>1,'last_ip'=>$req->ip(),'last_time'=>date('Y-m-d H:i:s')]);
            session()->put('member',(object)['id'=>$res->id,'openid'=>$res->openid]);
            // 弹出填写手机号功能
            session()->flash('nophone',1);
          }
          else
          {
            if ($user->status == 0) {
                $message = '用户被禁用，请联系管理员！';
                return view('errors.404',compact('message'));
            }
            User::where('openid',$wxuser->id)->update(['thumb'=>$wxuser->avatar,'last_ip'=>$req->ip(),'last_time'=>date('Y-m-d H:i:s')]);
            session()->put('member',(object)['id'=>$user->id,'openid'=>$user->openid]);
            if ($user->phone == '') {
              // 弹出填写手机号功能
              session()->flash('nophone',1);
            }
          }
          return redirect($backurl);
        } catch (\Exception $e) {
          return redirect($backurl);
        }
    }
    // 退出登录
    public function getLogout()
    {
        session()->put('member',null);
        return redirect(url('login'))->with('message','退出登录成功！');
    }
}