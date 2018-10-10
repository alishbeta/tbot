<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use app\libraries\Bittrex;
use app\libraries\Bitmex;
use app\libraries\Market;
use app\models\Modes;
use app\models\User;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;

/**
 * Callback query command
 */
class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var callable[]
     */
    protected static $callbacks = [];

    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Reply to callback query';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Add a new callback handler for callback queries.
     *
     * @param $callback
     */
    public static function addCallbackHandler($callback)
    {
        self::$callbacks[] = $callback;
    }

    /**
     * Command execute method
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $callback_query = $this->getUpdate()->getCallbackQuery();
        $user_id = $callback_query->getFrom()->getId();
        $query_id = $callback_query->getId();
        $query_data = $callback_query->getData();
        $message = $callback_query->getMessage();
        $message_id = $message->getMessageId();
        $chat_id = $message->getChat()->getId();
        $userName = $message->getChat()->getUsername();
        $user = User::findByUsername($userName);


        // Call all registered callbacks.
        foreach (self::$callbacks as $callback) {
            $callback($this->getUpdate()->getCallbackQuery());
        }

        $data = [                                  // Set up the new message data
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'parse_mode' => 'HTML',// Set Chat ID to send the message to
        ];
        $queryData = explode(' ', $query_data);
        $mode = Modes::findMode($user->id, $queryData[1]);

        switch ($queryData[0]) {
            case 'pair':
                if (empty($mode))
                    (new Modes())->setMode(['name' => 'buy', 'user_id' => $user->id, 'pair' => $queryData[1]]);
                $market = new Bittrex();
                $pInfo = $market->getPairInfo($queryData[1]);
                $btcPrice = $market->getPrice('usdt-btc');
                $last = Market::exp_to_dec($pInfo['result'][0]['Last']);
                $low = Market::exp_to_dec($pInfo['result'][0]['Low']);
                $high = Market::exp_to_dec($pInfo['result'][0]['High']);

                $data['text'] = "Пара: " . $queryData[1] . "\n"
                    . "Объем торгов: ". $btcPrice. "---" . round($pInfo['result'][0]['BaseVolume'], 2) . " btc\n"
                    . "Последняя цена: " . $last . " btc (<b>$" . round($last * $btcPrice, 3) . "</b>)\n"
                    . "Max: " . $high . " btc (<b>$" . round($high * $btcPrice, 3) . "</b>)\n"
                    . "Min: " . $low . " btc (<b>$" . round($low * $btcPrice, 3) . "</b>)\n"
                    . "<b>---------------------------</b>\n"
                    . "Укажите в ручную на какую сумму желаете купить монету или нажмите на соотвецтвующую кнопку.";
                $data['reply_markup'] = new InlineKeyboard(
                    [
                        ['text' => 'Обновить', 'callback_data' => 'pair ' . $queryData[1] . ' r']
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
                if (isset($queryData[2]) && $queryData[2] == 'r')
                    return Request::editMessageText($data);
                break;
            case 'buy_p':

                //\Yii::info(Bittrex::getBalance($queryData[1]), 'debug');

                $balance = array_values(array_filter(Bittrex::getBalance($queryData[1])['result'], function ($array) {
                    return $array['Currency'] == 'BTC';
                }));
                $balance = round(Market::exp_to_dec($balance[0]['Available']), 5);

                if ($balance < 0.0005){
                    $data['text'] = 'Не достаточно средств для покупки';
                    return Request::sendMessage($data);
                }

                $amount = round($balance * $queryData[2] / 100, 5);
                $btcPrice = (new Bittrex())->getPrice('usdt-btc');
                $amountUsd = $btcPrice * $amount;
                $balanceUsd = $btcPrice * $balance;

                $data['text'] = "Пара: ".$queryData[1]."\n"
                    . "Ордер на " . $queryData[2] . "% от депозита.\n"
                    . "Доступный депозит: " . $balance . " BTC (<b>$ ".$balanceUsd."</b>)\n"
                    . "Ордер на сумму: " . $amount . " BTC (<b>$ ".$amountUsd."</b>)\n"
                    . "Для отмены нажмите 'Назад'\n";
                $data['reply_markup'] = new InlineKeyboard(
                    [
                        ['text' => 'Купить', 'callback_data' => 'buy ' . $queryData[1] .' '. $queryData[2]],
                    ],
                    [
                        ['text' => 'Купить (+1%)', 'callback_data' => 'buy ' . $queryData[1] .' '. $queryData[2] . ' 1'],
                        ['text' => 'Купить (+2%)', 'callback_data' => 'buy ' . $queryData[1] .' '. $queryData[2] . ' 2']
                    ],
                    [
                        ['text' => 'Назад', 'callback_data' => 'pair ' . $queryData[1] . ' r'],
                    ]
                );
                return Request::editMessageText($data);
                break;
            case 'buy':
                $bitrex = new Bittrex();
                $pInfo = $bitrex->getPairInfo($queryData[1]);
                $low = Market::exp_to_dec($pInfo['result'][0]['Low']);
                $high = Market::exp_to_dec($pInfo['result'][0]['High']);
                $balance = array_values(array_filter(Bittrex::getBalance($queryData[1])['result'], function ($array) {
                    return $array['Currency'] == 'BTC';
                }));
                $balance = round(Market::exp_to_dec($balance[0]['Available']), 5);
                $amount = round($balance * $queryData[2] / 100, 5);
                $btcPrice = $bitrex->getPrice('usdt-btc');
                $pairPrice = Market::exp_to_dec($pInfo['result'][0]['Last']);
                $buyPrice = Market::exp_to_dec($pairPrice + ($pairPrice * $queryData[3] / 100));
                $amountUsd = $btcPrice * $amount;
                $balanceUsd = $btcPrice * $balance;
                $quantity = $amount / $buyPrice;


                $order = Bittrex::setOrder($queryData[1], $quantity, $buyPrice);

                \Yii::error($order);

                $data['text'] = "Ордер № 457889 размешен.\n"
                    . "Последняя цена: ".$order." btc\n"
                    . "Цена в ордере: ".$buyPrice." btc (".$queryData[3]."%)\n"
                    . "Матен в ордере: ".round($quantity, 3)." монет\n"
                    . "\n";
                $data['reply_markup'] = new InlineKeyboard(
                    [
                        ['text' => 'Обновить', 'callback_data' => 'active_orders']
                    ]
                );
                return Request::editMessageText($data);
                break;
            case 'trade':
                $dateNowB = (new \DateTime())->modify('-3 hour -1 minutes')->format('Y-m-d\TH:i:s');
                $dateNow = (new \DateTime())->modify('-1 minutes')->format('Y-m-d H:i:s');
                $dataB = Bitmex::getPairInfo($dateNowB);
                $data['text'] = "Время: <b>" . $dateNow . "</b> \nКурс XBT: <b>" . $dataB[0]['price'] . "</b>" ;

                $keyboardData[0]['text'] = "Обновить курс";
                $keyboardData[0]['callback_data'] = 'trade';
                $inline_keyboard = new InlineKeyboard(
                    $keyboardData
                );
                $data['reply_markup'] = $inline_keyboard;

                return Request::editMessageText($data);
                break;

        }


        return Request::sendMessage($data);
    }
}
