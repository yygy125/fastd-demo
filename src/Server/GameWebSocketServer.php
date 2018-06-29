<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace Server;

use Controller\MemberController;
use Controller\RoomController;
use FastD\Servitization\OnWorkerStart;
use FastD\Swoole\Server\WebSocket;
use swoole_server;
use swoole_websocket_frame;

/**
 * Class WebSocketServer.
 */
class GameWebSocketServer extends WebSocket
{
    use OnWorkerStart;

    /**
     * @param swoole_server          $server
     * @param swoole_websocket_frame $frame
     *
     * @return int|mixed
     *
     * @throws \Exception
     * @throws \FastD\Packet\Exceptions\PacketException
     */
    public function doMessage(swoole_server $server, swoole_websocket_frame $frame)
    {
//        $server->push($frame->fd, '1111111', 2);
        try{

            $format = 'slength/sprotocol/A*data';
            $request = unpack($format,$frame->data);

            if($request['length']+2 != strlen($request['data'])){
                $format = 'slength/sprotocol/a*data';
                $request = unpack($format,$frame->data);
            }
//            $request = $this->data($frame->fd);

            $route = protocol2route($request['protocol']);
            if(!isset($route['object'])){
                throw new \Exception("协议{$request['protocol']}不存在 !");
            }
            $request['fd'] = fd2sid($frame->fd);
//
            $bean = new $route['object'];
            echo "协议:{$request['protocol']}=>{$route['object']}@{$route['method']}\n";

            $format = isset($route['format']) ? 'sverify/'.trim($route['format'],'/') : 'sverify';
            echo "format:{$format}\n";
            $request['data'] = bin2array($format, $request['data']);
//var_dump($request);
            $Response = call_user_func(array($bean,$route['method']), $request);
            if(is_object($Response) && $Response->valid()){
                foreach ($Response as $message) {
                    $fd = sid2fd($message->fd());
                    $server->push($fd, (string) $message, WEBSOCKET_OPCODE_BINARY);
                    unset($message);
                }
            }
        }catch (\Exception $e){
            var_dump($e);
//            file_put_contents("/tmp/test.log",$e->getMessage()."\n",FILE_APPEND);
//            file_put_contents("/tmp/test.log",$e->getLine()."\n",FILE_APPEND);
//            var_dump($e->getMessage());
//            var_dump($e->getCode());
//            var_dump($e->getLine());
        }
        return 0;
    }

    public function doClose(swoole_server $server, $fd, $fromId){
        $fd = fd2sid($fd);
        echo $fd . "关闭链接\n";
        $Room = new RoomController();
        $Member = new MemberController();
        $Room->close($fd);
        $Member->logout($fd);
    }

   
}
/*

function bin2array($format, &$data){
    $formats = explode('/', $format);
    $entity = [];
    $index = 0;
    foreach ($formats as $v) {
        $type = $v[0];
        $key = substr($v, 1);
        switch ($type) {
            case 'A':
                $str_length = unpack('s', substr($data, $index, 2))[1];
                $index += 2;
                $str = unpack("A$str_length", substr($data, $index, $str_length))[1];
                $entity[$key] = $str;
                $index += $str_length;
                break;
            case 's':
                $int16 = unpack('s', substr($data, $index, 2))[1];
                $entity[$key] = $int16;
                $index += 2;
                break;
            case 'l':
                $int32 = unpack('l', substr($data, $index, 4))[1];
                $entity[$key] = $int32;
                $index += 4;
                break;
            case 'c':
                $char = unpack('c', substr($data, $index, 1))[1];
                $entity[$key] = $char;
                $index += 4;
                break;
        }
    }
    return $entity;
}



function protocol2route($sprotocol){
    $sprotocols = config()->get('protocol_map', []);
    return isset($sprotocols[$sprotocol]) ? $sprotocols[$sprotocol] : $sprotocols['default'];
}

function route2protocol($path){
    return array_search($path, config()->get('protocol_map'));
}

function array2json(array $arr){
    return json_encode($arr, JSON_UNESCAPED_UNICODE);
}

function json2array($str, $toArray = true){
    return json_decode($str, $toArray);
}

function fd2sid($fd){
    return \Library\Constants::FD_PREFIX . $fd;
}

function sid2fd($sid){
    return substr($sid, strlen(\Library\Constants::FD_PREFIX));
}

function notify($Response){
    if($Response->valid()){
        foreach ($Response as $message) {
            $fd = sid2fd($message->fd());
            server()->getSwoole()->push($fd, (string) $message, WEBSOCKET_OPCODE_BINARY);
            unset($message);
        }
    }
}

return [
    'protocol_map' => [//协议号映射路由
        'default' => '/',
        '10' => [//登陆
            'object' => \Controller\MemberController::class,
            'method' => 'login',
            'format' => '/lappid/lgameid/Aopenid/Aopenkey'
        ],
        '11' => [//房间信息
            'object' =>\Controller\RoomController::class,
            'method' => 'create',
        ],
        '12' => [//开始游戏
            'object' => \Controller\GameController::class,
            'method' => 'start',
            'format' => '/lmapId',
        ],
        '15' => [//心跳
            'object' =>\Controller\MemberController::class,
            'method' => 'heartbeat',
        ],
        '17' => [//房间成员准备状态
            'object' =>\Controller\RoomController::class,
            'method' => 'ready',
            'format' => '/cready',
        ],
        '18' => [//进入房间
            'object' => \Controller\RoomController::class,
            'method' => 'enter',
            'format' => '/AroomId',
        ],
        '19' => [
            'object' => \Controller\RoomController::class,
            'method' => 'out',
        ],
        '20' => [
            'object' => \Controller\MatchesController::class,
            'method' => 'caseAction',
            'format' => '/cstatus/lmapId',
        ],
        '21' => [
            'object' => \Controller\RoomController::class,
            'method' => 'item',
            'format' => '/AotherId/lpropId',
        ],
        '101' => [
            'object' => \Controller\RoomController::class,
            'method' => 'integral',
            'format' => '/Aopenid/lintegral/lcomplet',
        ],
        '100' => [
            'object' => \Controller\RoomController::class,
            'method' => 'clearing',
            'format' => '/AroomId',
        ],
    ],
    'protocols' => [//协议
        'enter' => '18',
        'ready' => 17,
        'members' => 16,
        'login' => 10,
        'room_info' => 11,
        'heartbeat' => 15,
        'start' => 12,
        'match' => 20,
        'item' => 21,
        'integral' => 101,
        'clearing' => 100,
        'out' => 19,
        'msg' => '22',
    ],
];

namespace Library;

class Message
{
    private $fd;
    private $protocol;
    private $data = '';

    public function __construct($fd, $protocol)
    {
        $this->fd = $fd;
        $this->protocol = $protocol;
        $this->data = '';
    }

    public function __toString()
    {
        $length = strlen($this->data);
        return pack('s',$length) . pack('s',$this->protocol) . pack("A{$length}", $this->data);
    }

    public function with($type, $value)
    {
        $this->$type($value);
        return $this;
    }

    public function fd()
    {
        return $this->fd;
    }

    public function protocol()
    {
        return $this->protocol;
    }

    private function int16($value){
         $this->data .= pack('s', $value);
        return true;
    }

    private function string($value){
        $length = strlen($value);
         $this->data .= pack('s', $length).pack("A$length", $value);
        return true;
    }

    private function int32($value){
         $this->data .= pack('l', $value);
        return $this;
    }
}

namespace Library;
class Response extends \ArrayIterator{

    private $data = [];
    private $index = 0;

    public function withMessage($Message){
        if(is_array($Message)){
            foreach ($Message as $msg){
                $this->data[] = $msg;
            }
        }else{
            $this->data[] = $Message;
        }
        return $this;
    }

    //1 重置迭代器
    public function rewind()
    {
        $this->index = 0;
    }

    //2 验证迭代器是否有数据
    public function valid()
    {
        return $this->index < count($this->data);
    }

    //3 获取当前内容
    public function current()
    {
        return $this->data[$this->index];
    }

    //4 移动key到下一个
    public function next()
    {
        return $this->index++;
    }

    //5 迭代器位置key
    public function key()
    {
        return $this->index;
    }
}
*/
