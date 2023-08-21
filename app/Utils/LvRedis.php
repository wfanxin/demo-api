<?php
namespace App\Utils;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Predis\Cluster\Hash\CRC16;
use Predis\Configuration\Options;
use Predis\Configuration\OptionsInterface;

class  LvRedis
{
    protected $options;

    public function __construct()
    {
    }

    public static function mget($keys = [])
    {
        if ( empty ($keys) ) {
            return false;
        }

        $options = config("database.redis.options");
        $clustersIpList = config("database.redis.clusters_ip_list");
        if (empty($options['cluster'])) {
            return Redis::mget($keys);
        }

        $self = new self();
        ///获取当前集群的哈希槽信息
        $slotsInfo = $self->_getSlotsInfo();

        ///计算待操作的key对应是在那个槽值里
        $crc16 = new CRC16();
        foreach ($keys as $key) {
            $keySlot = $crc16->hash($key) % 16384;

            array_walk($slotsInfo, function($slotValue, $slotKey) use ($keySlot, $key, &$pipeList) {
                $slotRange = explode("|", $slotKey);

                if ($keySlot >= $slotRange[0] && $keySlot <= $slotRange[1]) {
                    $pipeList[$slotKey][] = $key;
                }
            });
        }

        ///执行pipeline
        $result = [];
        foreach ($pipeList as $key => $val) {
            $num = count($val);
            if ($num <=0) {
                continue;
            }

            $curPort = $slotsInfo[$key]['1'];
            $curIp = $clustersIpList[$curPort];

            $response = Redis::getClientFor($curIp.":".$curPort)->pipeline(function($pipe) use ($val) {
                if ( ! empty($val) ) {
                    foreach ($val as $k) {
                        $pipe->get($k);
                    }
                }
            });

            $i = 0;
            foreach ($val as $redisKey) {
                $result[$redisKey] = $response[$i];
                $i++;
            }
        }

        ///
        return $result;
    }

    /**
     * 获取当前集群的哈希槽信息
     * @param string $index
     * @return array
     */
    private function _getSlotsInfo($index='default') {
        $i = 0;
        retry_get: {
            try {
                $runPort = [];
                //获取redis集群哈希槽信息
                $options = config("database.redis.options");
                $parameters = config("database.redis.clusters.{$index}");
                $count = count($parameters);

                $slotsInfoTmp = Redis::getClientFor($parameters[$i]['host'].":".$parameters[$i]['port'])->executeRaw(["cluster", "slots"]);
                if (empty($slotsInfoTmp)) {
                    throw new \Exception("it have not valid slots,please check the redis cluster config", 50005);
                }
                $slotsInfo = [];
                foreach ($slotsInfoTmp as $val) {
                    $slotsRange = sprintf("%s|%s", $val[0], $val[1]);
                    $slotsInfo[$slotsRange] = $val[2];
                    $new[$val[2][1]] = [
                        'ip' => $val[2][0],
                        'port' => $val[2][1]
                    ];
                    $runPort[] = $val[2][1];
                }

                ///刷新配置文件
                foreach ($parameters as $k => $val) {
                    $oldPort[] = $val['port'];
                }
                $newPort = array_diff($runPort, $oldPort);
                sort($newPort);

                $i = 0;
                foreach ($parameters as $k => $val) {
                    $oldPort[] = $val['port'];

                    if ( ! in_array($val['port'], $runPort) ) {
                        $node = sprintf("REDIS_PORT_NODE%s", $k+1);
                        \App\Facades\ConfigFacade::update($node, $newPort[$i]);
                        $i++;
                    }
                }

                ///
                return $slotsInfo;
            } catch (\Exception $exception) {
                if (config('app.debug')) {
                    echo "[ERROR] => ";
                    echo $exception->getFile()." line ".$exception->getLine()."<br/>";
                    echo $exception->getMessage()."<br/>";
                } else {
                    Log::error(null, [
                        $exception->getFile(),
                        $exception->getLine(),
                        $exception->getMessage()
                    ]);
                }

                if ($exception->getCode() == 50005) {
                    echo json_encode([
                        'code' => 505,
                        'message' => 'server invalid'
                    ]);
                    exit;
                }

                if ($i < $count) {
                    $i++;
                    goto retry_get;
                } else {
                    echo json_encode([
                        'code' => 505,
                        'message' => "database config redis invalid"
                    ]);
                    exit;
                }
            }
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return Redis::$name(...$arguments);
    }
}