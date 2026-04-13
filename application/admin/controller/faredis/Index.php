<?php

namespace app\admin\controller\faredis;

use app\common\controller\Backend;

class Index extends Backend
{

    protected $handler = null;

    public function _initialize($options = [])
    {
        parent::_initialize();

        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('未安装redis扩展');
        }
        $options = get_addon_config('faredis');
        $this->handler = new \Redis;
        try {
            $this->handler->connect($options['host'], $options['port'], 0);
            if ('' != $options['password']) {
                $this->handler->auth($options['password']);
            }
        } catch (\RedisException $e) {
            $this->error('连接失败,请检查配置文件或Redis服务是否开启', url('faredis/index'), null, 3);
        }
    }


    public function index()
    {
        if ($this->request->isAjax()) {
            $root = array('text' => 'Redis', 'id' => -1, 'type' => 'root', 'state' => array('opened' => true));
            $nums = get_addon_config('faredis')['dbnums'];
            for ($i = 1; $i <= $nums; $i++) {
                $this->handler->select($i - 1);
                $count = $this->handler->dbSize();
                $root['children'][] = array('text' => 'DB' . ($i - 1) . '(' . $count . ')', 'id' => $i, 'type' => 'db');
            }
            return json($root);
        }
        // add test data
        // for ($i = 1; $i < 20; $i++) {
        //     $this->handler->select(0);
        //     $this->handler->set('k' . $i, $i);
        // }
        return $this->view->fetch();
    }

    public function reloadDb($db)
    {
        $this->handler->select($db - 1);
        $node = array('text' => 'DB' . ($db - 1) . '(' . $this->handler->dbSize() . ')', 'id' => $db, 'type' => 'db', 'parent' => -1);
        return json($node);
    }

    public function flushDb($db)
    {
        $this->handler->select($db - 1);
        $this->handler->flushDb();
        $this->success();
    }

    public function keys($db)
    {
        $this->handler->select($db);
        $keys = $this->handler->keys('*');
        $data = [];
        foreach ($keys as $k => $v) {
            $data[] = array('text' => $v, 'id' => $db . '_' . $v, 'type' => 'key');
        }
        return json($data);
    }

    public function getValue($db, $key)
    {
        $this->handler->select($db);
        $type = $this->handler->type($key);
        $ttl = $this->handler->ttl($key);
        $value = null;
        // 1 string 2 set   3 list 4 zset  5 hash
        switch ($type) {
            case 1:
                $value = $this->handler->get($key);
                break;
            case 2:
                $value = $this->handler->SMEMBERS($key);
                break;
            case 3:
                $value = $this->handler->lrange($key, 0, -1);
                break;
            case 4:
                $value = $this->handler->zrange($key, 0, -1, true);
                break;
            case 5:
                $value = $this->handler->hGetAll($key);
                break;
            default:
                # code...
                break;
        }
        return json(array('db' => $db, 'key' => $key, 'type' => $type, 'value' => $value, 'ttl' => $ttl));
    }

    public function delKey($db, $key)
    {
        $this->handler->select($db);
        $r = $this->handler->del($key);
        $r == 1 ? $this->success() : $this->error();
    }

    public function rename($db, $key)
    {
        $this->handler->select($db);
        $newkey = input('newkey', $key);
        $r = $this->handler->rename($key, $newkey);
        $r == 1 ? $this->success() : $this->error();
    }

    public function ttl($db, $key)
    {
        $this->handler->select($db);
        $newttl = input('ttl');
        if ($newttl == -1)
            $this->handler->persist($key);
        else
            $this->handler->expire($key, $newttl);
        $this->success();
    }

    public function updateValue()
    {
        $db = input('db');
        $key = input('key');
        $value = input('value');
        $json = json_decode($value);
        if (!is_null($json)) {
            $value = json_encode($json);
        }
        $this->handler->select($db);
        $type = $this->handler->type($key);
        // 1 string 2 set   3 list 4 zset  5 hash
        switch ($type) {
            case 1:
                $this->handler->set($key, $value);
                break;
            case 2:
                $old = input('oldvalue');
                $this->handler->sRem($key, $old);
                $this->handler->sadd($key, $value);
                break;
            case 3:
                $list_idx = input('oldvalue');
                $this->handler->lSet($key, $list_idx, $value);
                break;
            case 4:
                $zset_key = input('zset-key'); //rem
                $zset_score = input('zset-score'); //add + value
                $this->handler->zrem($key, $zset_key);
                $this->handler->zadd($key, $zset_score, $value);
                break;
            case 5:
                $hash_key = input('hash-key');
                $this->handler->hSet($key, $hash_key, $value);
                break;
            default:
                # code...
                break;
        }
        $this->success('操作成功');
    }

    public function addValue()
    {
        $db = input('db');
        $key = input('key');
        $value = input('value');
        $json = json_decode($value);
        if (!is_null($json)) {
            $value = json_encode($json);
        }
        $this->handler->select($db);
        $type = $this->handler->type($key);
        // 1 string 2 set   3 list 4 zset  5 hash
        switch ($type) {
            case 2:
                $this->handler->sadd($key, $value);
                break;
            case 3:
                $this->handler->rpush($key, $value);
                break;
            case 4:
                $zset_score = input('zset_score', 0);
                $this->handler->zadd($key, $zset_score, $value);
                break;
            case 5:
                $hash_key = input('hash_key');
                $this->handler->hset($key, $hash_key, $value);
                break;
            default:
                # code...
                break;
        }
        $this->success('操作成功');
    }

    public function delValue()
    {
        $db = input('db');
        $key = input('key');
        $value = input('value');
        $json = json_decode($value);
        if (!is_null($json)) {
            $value = json_encode($json);
        }
        $this->handler->select($db);
        $type = $this->handler->type($key);
        // 1 string 2 set   3 list 4 zset  5 hash
        switch ($type) {
            case 2:
                $this->handler->srem($key, $value);
                break;
            case 3:
                $this->handler->lrem($key, $value);
                break;
            case 4:
                $this->handler->zrem($key, $value);
                break;
            case 5:
                $this->handler->hdel($key, $value);
                break;
            default:
                # code...
                break;
        }
        $this->success('操作成功');
    }

    public function newKey()
    {
        $db = input('newdb', 1);
        --$db;
        $key = input('newkey');
        $value = input('newvalue');
        $json = json_decode($value);
        if (!is_null($json)) {
            $value = json_encode($json);
        }
        $this->handler->select($db);
        $type = input('newType', 'string');
        // 1 string 2 set   3 list 4 zset  5 hash
        switch ($type) {
            case 'string':
                $this->handler->set($key, $value);
                break;
            case 'set':
                $this->handler->sadd($key, $value);
                break;
            case 'list':
                $this->handler->rpush($key, $value);
                break;
            case 'zset':
                $zset_score = input('newscore', 0);
                $this->handler->zadd($key, $zset_score, $value);
                break;
            case 'hash':
                $hash_key = input('newfield');
                $this->handler->hset($key, $hash_key, $value);
                break;
            default:
                # code...
                break;
        }
        $this->success('操作成功');
    }
}
