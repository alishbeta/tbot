<?php
/**
 * Created by PhpStorm.
 * User: Dmitriy
 * Date: 21.11.2017
 * Time: 13:18
 */

namespace app\libraries;

use GuzzleHttp\Client;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

class Market
{
    public static function exp_to_dec($float_str)
    {
        $float_str = (string)((float)($float_str));

        // if there is an E in the float string
        if(($pos = strpos(strtolower($float_str), 'e')) !== false)
        {
            // get either side of the E, e.g. 1.6E+6 => exp E+6, num 1.6
            $exp = substr($float_str, $pos+1);
            $num = substr($float_str, 0, $pos);

            // strip off num sign, if there is one, and leave it off if its + (not required)
            if((($num_sign = $num[0]) === '+') || ($num_sign === '-')) $num = substr($num, 1);
            else $num_sign = '';
            if($num_sign === '+') $num_sign = '';

            // strip off exponential sign ('+' or '-' as in 'E+6') if there is one, otherwise throw error, e.g. E+6 => '+'
            if((($exp_sign = $exp[0]) === '+') || ($exp_sign === '-')) $exp = substr($exp, 1);
            else trigger_error("Could not convert exponential notation to decimal notation: invalid float string '$float_str'", E_USER_ERROR);

            // get the number of decimal places to the right of the decimal point (or 0 if there is no dec point), e.g., 1.6 => 1
            $right_dec_places = (($dec_pos = strpos($num, '.')) === false) ? 0 : strlen(substr($num, $dec_pos+1));
            // get the number of decimal places to the left of the decimal point (or the length of the entire num if there is no dec point), e.g. 1.6 => 1
            $left_dec_places = ($dec_pos === false) ? strlen($num) : strlen(substr($num, 0, $dec_pos));

            // work out number of zeros from exp, exp sign and dec places, e.g. exp 6, exp sign +, dec places 1 => num zeros 5
            if($exp_sign === '+') $num_zeros = $exp - $right_dec_places;
            else $num_zeros = $exp - $left_dec_places;

            // build a string with $num_zeros zeros, e.g. '0' 5 times => '00000'
            $zeros = str_pad('', $num_zeros, '0');

            // strip decimal from num, e.g. 1.6 => 16
            if($dec_pos !== false) $num = str_replace('.', '', $num);

            // if positive exponent, return like 1600000
            if($exp_sign === '+') return $num_sign.$num.$zeros;
            // if negative exponent, return like 0.0000016
            else return $num_sign.'0.'.$zeros.$num;
        }
        // otherwise, assume already in decimal notation and return
        else return $float_str;
    }

    public function sendMesage($users, $msg, $inline_keyboard = false){
        $bot_api_key  = \Yii::$app->params['botApiKey'];
        $bot_username = \Yii::$app->params['botName'];
        $telegram = new Telegram($bot_api_key, $bot_username);

        foreach ($users as $user){
            $data['chat_id'] = $user->chat_id;
            $data['text'] = $msg;
            if ($inline_keyboard){
                $data['reply_markup'] = $inline_keyboard;  
            }
            $data['parse_mode'] =  'HTML';
            if ($user->chat_id != 0 /*&& $user->username == 'HarveySpecterr'*/)
                Request::sendMessage($data);
        }
    }

}