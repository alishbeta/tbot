<?
namespace app\libraries;

use app\models\MarketHistory;
use GuzzleHttp\Client;
use React\EventLoop\Factory;
use React\Socket\Connector;

class Bitmex extends Market
{
    const URL = 'https://www.bitmex.com/api/v1/trade?symbol=.xbt&count=5&reverse=false&startTime=';
    public static $price = "";
                //'https://www.bitmex.com/api/v1/trade?symbol=.xbt&count=5&reverse=false&startTime=2018-10-08T12%3A51%3A46.000Z'
    //2018-10-08T12%3A43%3A02.000Z
                                                                                                         

    public static function getPairInfo ($date = false){
        $client = new Client();
        //print_r(self::URL . urlencode($date) );
        return json_decode($client->get(self::URL . urlencode($date) )->getBody(), 1);
    }

    public static function getPairInfoWebSocket ($date = false){
        $loop = Factory::create();
        $reactConnector = new Connector($loop, [
            'dns' => '8.8.8.8',
            'timeout' => 10
        ]);
        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        $price = "";
        $connector('wss://www.bitmex.com/realtime?subscribe=instrument:XBTUSD')
            ->then(function(\Ratchet\Client\WebSocket $conn) {
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $msg) use ($conn) {
                    $data = json_decode($msg, 1);
                    if ($data['data'][0]['lastPrice']){
                        self::$price = $data['data'][0]['lastPrice'];
                        $conn->close();
                    }
                    //$conn->close();
                });

                $conn->on('close', function($code = null, $reason = null) {
                    //echo "Connection closed ({$code} - {$reason})\n";

                });

                $conn->send("help");

            }, function(\Exception $e) use ($loop) {
                echo "Could not connect: {$e->getMessage()}\n";
                $loop->stop();
            });

        $loop->run();

        return self::$price;
    }


}
