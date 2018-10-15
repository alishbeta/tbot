<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "modes".
 *
 * @property integer $id
 * @property string $name
 * @property integer $user_id
 * @property integer $pair
 * @property integer $amount
 */
class Modes extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'modes';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'user_id'], 'required'],
            [['user_id'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['pair'], 'string', 'max' => 10],
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
            'user_id' => 'User ID',
            'pair' => 'Pair',
            'amount' => 'Amount',
        ];
    }

    public function setMode($data = []){
        $this->attributes = $data;
        $this->save();
    }

    public static function findById($id){
        return self::findOne(['user_id' => $id]);
    }

    public static function findMode($id, $pair = false){
        return self::findOne(['user_id' => $id]);
    }
}
