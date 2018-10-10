<?php

namespace app\libraries;

use app\models\MarketHistory;
use GuzzleHttp\Client;

class Bittrex extends Market
{
    const API_KEY = '71be83d637d04504944ba20411b21b3f';
    const API_SECRET = '175eff1ecae6486fa5c2ac9b17434bcb';
    const URL = 'https://bittrex.com/api/v1.1';

    public static function getSign($uri){
        return hash_hmac('sha512', $uri, static::API_SECRET);
    }

    public static function getMarketSummaries()
    {
        $client = new Client();
        return json_decode($client->get(self::URL . '/public/getmarketsummaries')->getBody(), 1);
    }

    public function getPairInfo($pair)
    {
        $client = new Client();
        return json_decode($client->get(self::URL . '/public/getmarketsummary?market=' . $pair)->getBody(), 1);

    }

    public function getPrice($pair)
    {
        $market = MarketHistory::findOne(['name' => $pair]);
        return self::exp_to_dec($market->price);
    }

    public static function setOrder($pair, $quantity, $price)
    {
        $data = [
            'apikey' => self::API_KEY,
            'market' => $pair,
            'quantity'=> $quantity,
            'rate' => $price,
            'nonce' => time(),
        ];
        $uri = self::URL . '/market/buylimit?' . http_build_query($data);
        $client = new \yii\httpclient\Client();
        $response = $client->createRequest()
            ->setUrl($uri)
            ->addHeaders(['apisign' => self::getSign($uri)])
            ->send();

        return $response->data;
    }

    public static function getBalance($pair)
    {
        $data = [
            'apikey' => self::API_KEY,
            'currency' => $pair,
            'nonce' => time(),
        ];
        $uri = self::URL . '/account/getbalances?' . http_build_query($data);
        $client = new \yii\httpclient\Client();
        $response = $client->createRequest()
            ->setUrl($uri)
            ->addHeaders(['apisign' => self::getSign($uri)])
            ->send();

        return $response->data;
    }



}