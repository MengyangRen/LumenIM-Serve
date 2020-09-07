<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersChatList extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users_chat_list';

    /**
     * 不能被批量赋值的属性
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 创建聊天列表记录
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID
     * @param int $type 创建类型 1:私聊  2:群聊
     * @return array
     */
    public static function addItem(int $user_id, int $receive_id, int $type)
    {
        $result = self::where('uid', $user_id)->where('type', $type)->where($type == 1 ? 'friend_id' : 'group_id', $receive_id)->first();
        if ($result) {
            $result->status = 1;
            $result->updated_at = date('Y-m-d H:i:s');
            $result->save();

            return [
                'id'=>$result->id,
                'type'=>$result->type,
                'friend_id'=>$result->friend_id,
                'group_id'=>$result->group_id,
            ];
        }

        if (!$result = self::create([
            'type' => $type,
            'uid' => $user_id,
            'status' => 1,
            'friend_id' => $type == 1 ? $receive_id : 0,
            'group_id' => $type == 2 ? $receive_id : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ])) {
            return [];
        }

        return [
            'id'=>$result->id,
            'type'=>$result->type,
            'friend_id'=>$result->friend_id,
            'group_id'=>$result->group_id,
        ];
    }

    /**
     * 聊天对话列表置顶操作
     *
     * @param int $user_id 用户ID
     * @param int $list_id 对话列表ID
     * @param bool $is_top 是否置顶（true:是 false:否）
     * @return bool
     */
    public static function topItem(int $user_id, int $list_id, $is_top = true)
    {
        return (bool)self::where('id', $list_id)->where('uid', $user_id)->update(['is_top' => $is_top ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * 删除聊天列表
     *
     * @param int $user_id 用户ID
     * @param int $id 聊天列表ID、好友ID或群聊ID
     * @param int $type ID类型 （1：聊天列表ID  2:好友ID  3:群聊ID）
     * @return bool
     */
    public static function delItem(int $user_id, int $id, $type = 1)
    {
        if ($type == 1) {
            return (bool)self::where('id', $id)->where('uid', $user_id)->update(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        } else if ($type == 2) {
            return (bool)self::where('uid', $user_id)->where('friend_id', $id)->update(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            return (bool)self::where('uid', $user_id)->where('group_id', $id)->update(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * 设置消息免打扰
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID
     * @param int $type 接收者类型（1:好友  2:群组）
     * @param int $not_disturb 是否免打扰
     * @return boolean
     */
    public static function notDisturbItem(int $user_id, int $receive_id, int $type, int $not_disturb)
    {
        $result = self::where('uid', $user_id)->where($type == 1 ? 'friend_id' : 'group_id', $receive_id)->where('status', 1)->first(['id', 'not_disturb']);
        if (!$result || $not_disturb == $result->not_disturb) {
            return false;
        }

        return (bool)self::where('id', $result->id)->update(['not_disturb' => $not_disturb]);
    }
}
