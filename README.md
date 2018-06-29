# 页面缓存插件使用教程
1.安装
  正式包 composer require fastd/cache-provider （暂时不能用 by.2018-06-29）
  开发包 composer require "fastd/cache-provider:dev-master"
 
2.安装完成之后，修改配置文件 config/app.php 找到 services增加 \FastD\CacheProvider\CacheServiceProvider::class，如下示例
  ```
    'services' => [
        \FastD\ServiceProvider\RouteServiceProvider::class,
        \FastD\ServiceProvider\LoggerServiceProvider::class,
        \FastD\ServiceProvider\DatabaseServiceProvider::class,
        \FastD\ServiceProvider\CacheServiceProvider::class,
        \FastD\ServiceProvider\MoltenServiceProvider::class,
        \ServiceProvider\HelloServiceProvider::class,
        \FastD\Viewer\Viewer::class,
        \FastD\CacheProvider\CacheServiceProvider::class,//新增的在此，上面的与此无关
    ],
    
  ```
  3.设置页面缓存时长【此步可选，不设置默认60秒】
    config/config.php 修改或增加 common.cache.lifetime ，代码示例如下
 ```
  return [
    'common' => [
      'cache' => [
        'lifetime' => 60
       ],
    ],
  ];
 ```
 注：只针对GET请求正常响应码为200的页面有缓存；
 完成；
