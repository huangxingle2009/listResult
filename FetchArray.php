<?php
namespace My\Cache;

use Illuminate\Support\Facades\Cache;

trait FetchArray {

    public function byIds($list = [], $model)
    {
        if (empty($list))
            return null;
        if (isset($list[0][$this->primaryKey]))
            $ids = array_column($list, $this->primaryKey);
        else
            $ids = $list;

        $arr_keys = [];
        if ($ids) {
            foreach ($ids as $id) {
                $key = $this->table . ":" . $id;
                array_push($arr_keys, $key);
            }
        } else {
            return null;
        }

        $arr_values = Cache::many($arr_keys);
        $list = array_map(function ($v) {
            return unserialize($v);
        }, $arr_values);
        $list = array_values($list);
        $expire_temp = [];
        if ($list) {
            foreach ($list as $key => $val) {
                if (!$val) {
                    $cache_key = $this->table . ":" . $ids[$key];
                    $expire_temp[$cache_key] = $ids[$key];
                }
            }
        }
        // 8 => 13
        if (!empty($expire_temp)) {
            $res = $model->select('*')->whereIn($this->primaryKey, $expire_temp)->get()->toArray();
            $cache = [];
            foreach ($res as $val) {
                $cache[$this->table . ":" . $val[$this->primaryKey]] = serialize($val);
            }
            Cache::putMany($cache, 3);

            if ($list) {
                foreach ($list as $key => $val) {
                    if (!$val) {
                        $id = $ids[$key];
                        foreach ($res as $sub) {
                            if ($sub['id'] == $id) {
                                $list[$key] = $sub;
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $list;
    }

}
