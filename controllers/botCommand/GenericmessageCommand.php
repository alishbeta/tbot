<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use app\models\Modes;
use app\models\Setings;
use app\models\User;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;


class GenericmessageCommand extends SystemCommand
{
    protected $name = 'genericmessage';
    protected $description = 'Handle generic message';
    protected $version = '1.1.0';

    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text = $message->getText();
        $userName = $message->getChat()->getUsername();
        $user = User::findByUsername($userName);
        $mode = Modes::findMode($user->id);

        $data['chat_id'] = $chat_id;
        $data['text'] = "Мне не понятна эта команда...";

        $keyboards[] = new Keyboard(
            ['Установить разницу...']
        );
        $keyboard = $keyboards[0]
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(false)
            ->setSelective(false);
        $data['reply_markup'] = $keyboard;

        switch ($text) {
            case 'Установить разницу...':
                (new Modes())->setMode(['name' => 'setings_interval', 'user_id' => $user->id]);
                $data['text'] = "Укажите интервал времени в минутах.";
                break;
        }

        if (!empty($mode))
            switch ($mode->name) {
                case 'setings_interval':
                    if (is_numeric($text)){
                        $model = new Setings();
                        $model->name = 'interval';
                        $model->user_id = $user->id;
                        $model->value = $text;
                        $model->save();
                    }

                    break;
            }

        return Request::sendMessage($data);
    }
}