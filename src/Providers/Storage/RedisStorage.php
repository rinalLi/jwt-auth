<?php
/**
 * Created by PhpStorm.
 * User: rinal
 * Date: 2018/12/13
 * Time: 10:18 AM
 */

namespace Tymon\JWTAuth\Providers\Storage;


use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Contracts\Providers\Storage;

class RedisStorage implements Storage
{
    /**
     * @var string
     */
    protected $key = 'hollo_jwt_black_list';
    protected $connection;
    private $redis;

    public function __construct()
    {
        $this->key = config('hollo.redis_black_list_key', 'hollo_jwt_black_list');
        $this->connection = config('hollo.redis_black_list_connection', 'default');
        $this->redis = Redis::connection($this->connection);
    }

    public function setKey($newKey)
    {
        $this->key = $newKey;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function setConnection($connection, $db=0)
    {
        $this->connection = $connection;
        $this->redis = Redis::connection($connection);
        $this->redis->select($db);
    }

    /**
     * Add a new item into storage.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $minutes
     * @return void
     */
    public function add($key, $value, $minutes)
    {
        $this->redis->hset($this->key, $key, Carbon::now()->timestamp + $minutes * 60);
        $this->delExpire();
    }

    public function forever($key, $value)
    {
        $this->add($key, $value, 365 * 24 * 60);
    }


    /**
     * Remove an item from storage.
     *
     * @param  string  $key
     * @return bool
     */
    public function destroy($key)
    {
        return $this->redis->hdel($this->key, $key);
    }

    public function get($key)
    {
        $t = $this->redis->hget($this->key, $key);
        return $t ? 'forever' : null;
    }

    /**
     * Remove all items associated with the tag.
     *
     * @return void
     */
    public function flush()
    {
        $this->redis->del($this->key);
    }

    private function delExpire()
    {
        $all = $this->redis->hgetall($this->key);
        $now = Carbon::now()->timestamp;
        foreach ($all as $key=>$expireTime) {
            if ($expireTime < $now) {
                $this->destroy($key);
            }
        }
    }
}