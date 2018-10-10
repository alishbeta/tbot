<?php

namespace app\controllers;

use app\libraries\Bittrex;
use app\libraries\Bitmex;
use app\libraries\Market;
use app\models\ContactForm;
use app\models\LoginForm;
use app\models\MarketHistory;
use app\models\SignupForm;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Ratchet\Client;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionBit()
    {
        $dateNow = (new \DateTime())->format('Y-m-d H:i:s');
        $dateGMT = (new \DateTime())->modify('-3 hour -1 minutes')->format('Y-m-d\TH:i:s');

        $user = User::find()->all();

        Client\connect("wss://www.bitmex.com/realtime")->then(function ($conn){
            $conn->on('message', function ($msg) use ($conn){
                echo "Received: {$msg}\n";
                $conn->close();
            });

            $conn->send('ping');
        }, function ($e){
            echo "Could not connect: {$e->getMessage()}\n";
        });

        //$data = Bitmex::getPairInfo($dateGMT);
        
//        $priceChange = $data[4]['price'] - $data[0]['price'];
//        print_r($dateGMT);
//        print_r($data);
        
//        print_r( $data[4]['price'] . "<br>");
//        print_r(round($priceChange, 2) . "<br>");

        // $users = User::find()->all();

        // $bot_api_key  = \Yii::$app->params['botApiKey'];
        // $bot_username = \Yii::$app->params['botName'];
        // $telegram = new Telegram($bot_api_key, $bot_username);

        // $data['chat_id'] = $user->chat_id;
        // $data['text'] = 'sgdfgdfg';

        // foreach ($users as $user){
        //     $data['chat_id'] = $user->chat_id;
        //     Request::sendMessage($data);
        // }




        return '';
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }
}
