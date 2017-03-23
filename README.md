# listResult
基于laravel 5.3 实现列表缓存

$obj = new ListResult(new Log(), ['where' => ['theme_id' => 1, 'has_reply' => 1]]);
$list = $obj->fetch_array(null, null, ['_theme']);
$obj->clear_cache();

names
