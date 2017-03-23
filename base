<?php

namespace Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use My\Cache\FetchArray;

class Base extends Model
{
    use FetchArray;

    public function as_array($list = [], $filter = []) {
        if (in_array("_theme", $filter)) {
            $cat_list = array_column($list, "category_id");
            $catModel = new Category();
            $cat_list = $catModel->as_array($cat_list);
            foreach ($list as $key => $val) {
                $list[$key]['category'] =  $cat_list[$val['category_id']];
            }
        }
        return $list;
    }

    public static function byId($id, $filter = []) {
        $self = new static();
        $res = $self->byIds([$id], $self);
        $res = $self->as_array($res, $filter);
        return array_pop($res);
    }

    public static function byIdModel($id) {
        $self = new static();
        $res = $self->byIds([$id], $self);
        $res = array_pop($res);
        return $self->create($res);
    }

    public function save() {
        if ($res = parent::save()) {
            $cache_key = $this->table . ":" . $this->primaryKey;
            $data = serialize($this->toArray());
            $res = Cache::put($cache_key, $data);
        }
        return $res;
    }

}
