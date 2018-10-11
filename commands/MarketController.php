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
use React\EventLoop\Factory;
use React\Socket\Connector;
use yii\console\Controller;
use Ratchet\Client;


class MarketController extends Controller
{
    public function actionDelAll()
    {
        MarketHistory::deleteAll();
    }
    public function actionMarketSave()
    {
//        $date = (new \DateTime())->add(new \DateInterval("PT2H"));
//
//        $data = Bittrex::getMarketSummaries();
//        foreach ($data['result'] as $res) {
//            if (strpos($res['MarketName'], 'ETH-') === false)
//                    $arrData[] = [$res['MarketName'], Market::exp_to_dec($res['Last']), $date->format('Y-m-d H:i:s')];
//        }
//        \Yii::$app->db->createCommand()->batchInsert('market_histry', ['name', 'price', 'time'], $arrData)->execute();

        $dateNow = (new \DateTime())->format('Y-m-d H:i:s');
        $price = Bitmex::getPairInfoWebSocket();

        $arrData[] = ["XBT", $price, $dateNow];
        \Yii::$app->db->createCommand()->batchInsert('market_histry', ['name', 'price', 'time'], $arrData)->execute();
    }

    public function actionWbTest(){
        print_r(Bitmex::getPairInfoWebSocket());
    }

    public function actionFiveMinutesBitmex()
    {
        $dateNow = (new \DateTime())->format('Y-m-d H:i:s');
        $dateGMT = (new \DateTime())->modify('-5 minutes')->format('Y-m-d H:i:s');

        $users = User::find()->all();
        //$data = Bitmex::getPairInfo($dateGMT);
        $price = Bitmex::getPairInfoWebSocket();

        $arrData[] = ["XBT", $price, $dateNow];
        \Yii::$app->db->createCommand()->batchInsert('market_histry', ['name', 'price', 'time'], $arrData)->execute();

        $data = MarketHistory::find()
            ->where(['between', 'time', $dateGMT, $dateNow])
            ->asArray()
            ->all();



        
        $priceChange = round($data[0]['price'] - $price, 2);

        //$msg = "Цена без изменений \n";
        //$msg .= "Цена сейчас: <b>" .  $data[4]['price'] . "</b>\n";
        //$msg .= "Цена 5 минут назад: <b>" .  $data[0]['price'] . "</b>";

        if ($priceChange >= 30){
            $msg = "Цена XBT выросла на <b>" . $priceChange ."</b>\n";
            $msg .= "Цена сеqчас: " .  $data[4]['timestamp']. "\n";
            $msg .= "Цена 5 минут назад: " .  $data[0]['price'];
        }elseif($priceChange <= -30){
            $msg = "Цена XBT упала на <b>" . $priceChange * -1 . "</b>\n";
            $msg .= "Цена сечас: " .  $data[4]['price'] . "\n";
            $msg .= "Цена 5 минут назад: " .  $data[0]['price'];
        }

        print_r($priceChange);

        if (isset($msg)){
            $keyboardData[0]['text'] = "Текущий курс";
            $keyboardData[0]['callback_data'] = 'trade';
    
            $inline_keyboard = new InlineKeyboard(
                $keyboardData
            );
        }


        (new Market())->sendMesage($users, $msg, $inline_keyboard);
        
        //print_r($data[0]['price'] . '<br>');
        //print_r( $data[4]['price'] . '<br>');
        //print_r($users[0]->chat_id);
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