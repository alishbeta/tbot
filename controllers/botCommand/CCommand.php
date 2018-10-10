<?php
/**
 * Created by PhpStorm.
 * User: Dmitriy
 * Date: 13.12.2017
 * Time: 14:57
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use app\libraries\Bittrex;
use app\libraries\Market;
use app\models\User;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;
use app\models\Modes;

class CCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'c';

    /**
     * @var string
     */
    protected $description = 'C command';

    /**
     * @var string
     */
    protected $usage = '/c';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Command execute method
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $userName = $message->getChat()->getUsername();
        $mText = $message->getText();
        $data['chat_id'] = $chat_id;
        $data['parse_mode'] =  'HTML';

        $user = User::findByUsername($userName);
        $mode = Modes::findById($user->id);

        $qData = explode(' ', $mText);
        $market = new Bittrex();

        $pInfo = $market->getPairInfo($qData[1]);
        $btcPrice = $market->getPrice('usdt-btc');
        $last = Market::exp_to_dec($pInfo['result'][0]['Last']);
        $low = Market::exp_to_dec($pInfo['result'][0]['Low']);
        $high = Market::exp_to_dec($pInfo['result'][0]['High']);

        $data['text'] = "Пара: " . $qData[1] . "\n"
            . "Объем торгов: " . round($pInfo['result'][0]['BaseVolume'], 2) . " btc\n"
            . "Последняя цена: " . $last . " btc (<b>$" . round($last * $btcPrice, 3) . "</b>)\n"
            . "Max: " . $high . " btc (<b>$" . round($high * $btcPrice, 3) . "</b>)\n"
            . "Min: " . $low . " btc (<b>$" . round($low * $btcPrice, 3) . "</b>)\n"
            . "<b>---------------------------</b>\n"
            . "Укажите в ручную на какую сумму желаете купить монету или нажмите на соотвецтвующую кнопку.";
        $data['reply_markup'] = new InlineKeyboard(
            [
                ['text' => 'Обновить', 'callback_data' => 'pair ' . $qData[1] . ' r']
            ],
            [
                ['text' => 'Купить (25% депозита)', 'callback_data' => 'buy_p ' . $qData[1] . ' 25'],
                ['text' => 'Купить (50% депозита)', 'callback_data' => 'buy_p ' . $qData[1] . ' 50'],
            ],
            [
            ['text' => 'Купить (100% депозит)', 'callback_data' => 'buy_p ' . $qData[1] . ' 100'],
                ['text' => 'Баланс аккаунта', 'callback_data' => 'buy_p ' . $qData[1] . ' 50'],
            ]
        );

        return Request::sendMessage($data);


    }

}