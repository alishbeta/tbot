<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use app\models\Modes;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

/**
 * Start command
 */
class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Start command';

    /**
     * @var string
     */
    protected $usage = '/start';

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
        $data['chat_id'] = $chat_id;

        $user = \app\models\User::findByUsername($userName);
        $mode = Modes::findById($user->id);

        if ($user->status == \app\models\User::STATUS_BOT_LOGIN){
            $data['text'] = 'Вы уже авторизированы.';
        }

        if (empty($mode))
            (new Modes())->setMode(['name' => 'login', 'user_id' => $user->id]);

        $data['text'] = 'Введите пароль...';

        return Request::sendMessage($data);


    }
}
