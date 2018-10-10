<?
namespace app\libraries;

use app\models\MarketHistory;
use GuzzleHttp\Client;

class Bitmex extends Market
{
    const URL = 'https://www.bitmex.com/api/v1/trade?symbol=.xbt&count=5&reverse=false&startTime=';
                //'https://www.bitmex.com/api/v1/trade?symbol=.xbt&count=5&reverse=false&startTime=2018-10-08T12%3A51%3A46.000Z'
    //2018-10-08T12%3A43%3A02.000Z
                                                                                                         

    public static function getPairInfo ($date = false){
        $client = new Client();
        //print_r(self::URL . urlencode($date) );
        return json_decode($client->get(self::URL . urlencode($date) )->getBody(), 1);
    }


}
