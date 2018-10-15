<?php
/**
 * Created by PhpStorm.
 * User: Dmitriy
 * Date: 21.11.2017
 * Time: 0:18
 */

namespace app\commands;

use app\libraries\Bittrex;
use app\libraries\Bitmex;
use app\libraries\Market;
use app\models\MarketHistory;
use app\models\User;
use Longman\TelegramBot\Entities\InlineKeyboard;
use yii\console\Controller;
use React\EventLoop\Factory;
use React\Socket\Connector;
use yii\db\Exception;


class MarketController extends Controller
{
    public function actionDelAll()
    {
        MarketHistory::deleteAll();
    }
    public function actionBitmexDemon()
    {
        $loop = Factory::create();
        $reactConnector = new Connector($loop, [
            'dns' => '8.8.8.8',
            'timeout' => 10
        ]);
        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        $connector('wss://www.bitmex.com/realtime?subscribe=instrument:XBTUSD')
            ->then(function(\Ratchet\Client\WebSocket $conn) {
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $msg) use ($conn) {
                    $data = json_decode($msg, 1);

                    if ($data['data'][0]['lastPrice']){
                        $dateNow = (new \DateTime())->format('Y-m-d H:i:s');

                        echo $dateNow . "\n";

                    if (\Yii::$app->cache->get('lastSave') === false) {
                        \Yii::$app->cache->set('lastSave', $dateNow);
                    }

                        $lastSave = \Yii::$app->cache->get('lastSave');
                        $lastSaveDate = strtotime($lastSave);
                        $diff = strtotime($dateNow) - $lastSaveDate;

                        echo $diff . "\n";

                        if ($diff > 20) {
                            \Yii::$app->cache->set('lastSave', $dateNow);
                            $price = $data['data'][0]['lastPrice'];

                            $arrData[] = ["XBT", $price, $dateNow];
                            try {
                                \Yii::$app->db->createCommand()->batchInsert('market_histry', ['name', 'price', 'time'], $arrData)->execute();
                            } catch (Exception $e) {
                                $conn->close();
                            }
                        }

                }
                });

                $conn->on('close', function($code = null, $reason = null) {
                    echo "Connection closed ({$code} - {$reason})\n";
                });
            }, function(\Exception $e) use ($loop) {
                echo "Could not connect: {$e->getMessage()}\n";
                $loop->stop();
            });
        $loop->run();
    }

    public function actionWbTest(){
        $dateNowB = (new \DateTime())->modify('-1 minutes')->format('Y-m-d H:i:s');
        $dateNow = (new \DateTime())->format('Y-m-d H:i:s');

        $datas = MarketHistory::find()
            ->where(['between', 'time', $dateNowB, $dateNow])
            ->asArray()
            ->all();
        $data['text'] = "Время: <b>" . $dateNow . "</b> \nКурс XBT: <b>" . end($datas)['price'] . "</b>" ;

        print_r($data['text']);
    }

    public function actionFiveMinutesBitmex()
    {
        date_default_timezone_set('Europe/Kiev');
        $dateNow = (new \DateTime())->format('Y-m-d H:i:s');
        $dateGMT = (new \DateTime())->modify('-5 minutes')->format('Y-m-d H:i:s');

        $users = User::find()->all();
        //$data = Bitmex::getPairInfo($dateGMT);
        //$price = Bitmex::getPairInfoWebSocket();

        $data = MarketHistory::find()
            ->where(['between', 'time', $dateGMT, $dateNow])
            ->asArray()
            ->all();


        $priceChange = round(end($data)['price'] - $data[0]['price'], 2);

        //print_r($priceChange);

        if ($priceChange >= 15){
            $msg = "Цена XBT выросла на <b>" . $priceChange ."</b>\n";
            $msg .= "Цена сейчас: " .  end($data)['price']. "\n";
            $msg .= "Цена 5 минут назад: " .  $data[0]['price'];
        }elseif($priceChange <= -15){
            $msg = "Цена XBT упала на <b>" . $priceChange * -1 . "</b>\n";
            $msg .= "Цена сейчас: " .  end($data)['price'] . "\n";
            $msg .= "Цена 5 минут назад: " .  $data[0]['price'];
        }

        //print_r($priceChange);

        if (isset($msg)){
            $keyboardData[0]['text'] = "Текущий курс";
            $keyboardData[0]['callback_data'] = 'trade';
    
            $inline_keyboard = new InlineKeyboard(
                $keyboardData
            );
        }
        (new Market())->sendMesage($users, $msg, $inline_keyboard);
    }

    public function actionFiveMinutes()
    {

        $date1 = (new \DateTime())->add(new \DateInterval("PT2H"))->format('Y-m-d H:i:s');
        $date2 = (new \DateTime())->add(new \DateInterval("PT2H"))->modify('-1 hour')->format('Y-m-d H:i:s');

        $users = User::find()->all();

        $data = MarketHistory::find()
            ->where(['between', 'time', $date2, $date1])
            ->all();


        foreach ($data as $val) {
            $dataArr[$val->name][] = $val->price;
        }

        //print_r($dataArr);
        $msgUp = ''; $msgDwn = ''; $i = 0;
        $count = count($dataArr['BTC-ETH']);

        foreach ($dataArr as $k => $val) {
            //$val2 = (isset($val[59])) ? $val[59] : $val[20];
            $persent = round(($val[$count-1] - $val[0]) / $val[0] * 100, 2);

            //print_r($dataArr);

            if ($persent >= 15) {
                $msgUp .= 'Рост ' . $k . ': ' . $persent . "%\n";
                $keyboardData[$i]['text'] = $k;
                $keyboardData[$i]['callback_data'] = 'pair ' . $k;
                $i++;
            }
            /*if ($persent <= -15) {
                if ($k != 'BTC-BTS')
                $msgDwn .= 'Падение ' . $k . ': ' . $persent . "%\n";

            }*/
        }


        $msg = $date1 . "\n"; $msg .= $msgUp ."\n". $msgDwn;

        $inline_keyboard = new InlineKeyboard(
                $keyboardData
        );

        if ($msgUp != "" || $msgDwn != "")
            (new Market())->sendMesage($users, $msg, $inline_keyboard);
    }

}