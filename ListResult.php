<?php
/**
 *      列表缓存
 *      $obj = new ListResult(new Log(), ['where' => ['theme_id' => 1, 'has_reply' => 1]]);
 *      $list = $obj->fetch_array(null, null, ['_theme']);
 *      $obj->clear_cache();
 */
namespace My\Cache;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ListResult {
    use FetchArray;
    private $cache_on;
    private $where = [];
    private $order = [];
    private $cache_key;
    private $list;
    private $table;
    private $model;
    private $primaryKey;
    private $max_num = 300;

    public function __construct(Model $model, $where = [], $order = [], $cache_on = true)
    {
        $this->cache_on = $cache_on;
        $this->where = $where;
        $this->order = $order;
        $this->model = $model;
        $this->table = $model->getTable();
        $this->primaryKey = $pk = $model->getKeyName();
        $arr_order_key = [];
        foreach ($order as $key => $val) {
            list($key) = $val;
            array_push($arr_order_key, $key);
        }
        if (!in_array($pk, $arr_order_key)) {
            $this->order = $arr_order_key = array_merge($arr_order_key, array($pk));
        }
    }

    private function _cache_key()
    {
        ksort($this->where);
        foreach ($this->where as &$where) {
            is_array($where) && ksort($where);
        }
        ksort($this->order);
        $map = array_merge($this->where, $this->order);
        $this->cache_key = substr(md5(serialize($map)), 8 - 8);
    }

    public function fetch_array($start = null, $per_page = null, $filter = [], $fields = ['*']) {
        $model = clone $this->model;
        if (!$this->cache_on || $start + $per_page > $this->max_num) {
            $this->_set_total();
            foreach($this->where as $key => $item) {
                $this->model = $this->model->{$key}($item);
            }
            $this->list = $this->model->select($fields)->offset($start)->limit($this->max_num)->get()->toArray();
        } else {
            $this->_cache_key();
            if (!$this->list = Cache::get($this->cache_key)) {
                $this->_set_total();
                foreach($this->where as $key => $item) {
                    $this->model = $this->model->{$key}($item);
                }
                $this->list = $this->model->select($this->order)->limit($this->max_num)->get()->toArray();
                Cache::put($this->cache_key, serialize($this->list), 60);
            } else {
                $this->list = unserialize($this->list);
            }
            $this->list = array_slice($this->list, $start, $per_page);
            $this->list = $this->byIds($this->list, $this->model);
        }
        return empty($this->list) ? null : $model->as_array($this->list, $filter);
    }

    private function _set_total() {
        if (!$this->get_total()) {
            $alias = clone $this->model;
            foreach($this->where as $key => $item) {
                $alias = $alias->{$key}($item);
            }
            $total = $alias->count();
            Cache::put($this->cache_key . "_total", $total, 60);
        }
    }


    public function clear_cache() {
        return Cache::forget($this->cache_key);
    }

    public function get_total()
    {
        return Cache::get($this->cache_key . "_total");
    }

}
