<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // 用户表
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users';

    // 不可以批量赋值的字段，为空则表示都可以
    protected $guarded = [];

    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
    protected $hidden = [];
    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = true;

    // 增加会员等级的属性
    public function getGroupnameAttribute()
    {
        $points = $this->attributes['points'];
        try {
            $groups = collect(cache('group'))->sortByDesc('points');
            $groupname = $groups->where('points','<=',$points)->first()['name'];
        } catch (\Exception $e) {
            $groupname = '普通用户';
        }
        return $groupname;
    }

    // 关联商品评价
    public function good_comment()
    {
        return $this->hasMany('\App\Models\Home\Good\GoodComment','user_id','id');
    }

    // 属性值
    public function return_good()
    {
        return $this->hasMany('\App\Models\Home\Good\ReturnGood','user_id','id');
    }

    // 属性值
    public function card()
    {
        return $this->hasMany('\App\Models\User\Card','user_id','id');
    }
    // 订单
    public function order()
    {
        return $this->hasMany('\App\Models\Home\Good\Order','user_id','id');
    }

    // 关联消费记录
    public function consume()
    {
        return $this->hasMany('\App\Models\User\Consume','user_id','id');
    }
    // 充值记录
    public function recharge()
    {
        return $this->hasMany('\App\Models\User\Recharge','user_id','id');
    }
    // 砍价活动
    public function bargain()
    {
        return $this->hasMany('\App\Models\Promotion\BargainOrder','user_id','id');
    }
}
