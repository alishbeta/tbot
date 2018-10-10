<?php
/**
 * Created by PhpStorm.
 * User: Dmitriy
 * Date: 18.11.2017
 * Time: 12:05
 */

namespace app\controllers;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use yii\web\Controller;

class BotController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent :: beforeAction($action);
    }

    public function actionHook(){
        try {
            $commands_paths = [
                __DIR__ . '/botCommand',
            ];

            $bot_api_key  = \Yii::$app->params['botApiKey'];
            $bot_username = \Yii::$app->params['botName'];
            // Create Telegram API object
            $telegram = new Telegram($bot_api_key, $bot_username);
            $telegram->addCommandsPaths($commands_paths);

            $telegram->handle();
        } catch (TelegramException $e) {
            // log telegram errors
            // echo $e->getMessage();
            \Yii::error($e->getMessage());
        }
    }

}