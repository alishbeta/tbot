<?php
/**
 * Created by PhpStorm.
 * User: Dmitriy
 * Date: 18.11.2017
 * Time: 13:01
 */
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
class StartCommand extends UserCommand
{
    public function execute()
    {
        $message = $this->getMessage();            // Get Message object

        $chat_id = $message->getChat()->getId();   // Get the current Chat ID

        $data = [                                  // Set up the new message data
            'chat_id' => $chat_id,                 // Set Chat ID to send the message to
            'text'    => 'This is just a Test...', // Set message to send
        ];
        \Yii::info($chat_id);
        return Request::sendMessage($data);        // Send message!
    }

}