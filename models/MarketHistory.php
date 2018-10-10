<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "market_histry".
 *
 * @property integer $id
 * @property string $name
 * @property double $price
 * @property string $time
 */
class MarketHistory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'market_histry';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'price', 'time'], 'required'],
            [['price'], 'number'],
            [['name'], 'string', 'max' => 20],
            [['time'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'price' => 'Price',
            'time' => 'Time',
        ];
    }
}
