<?php

namespace app\models;

/**
 * This is the model class for table "service".
 *
 * @property integer $id
 * @property string $name
 * @property string $api_key
 * @property integer $user_id
 * @property array $users
 */
class Service extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'service';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'api_key', 'user_id'], 'required'],
            [['api_key'], 'string'],
            [['user_id'], 'integer'],
            [['name'], 'string', 'max' => 50],
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
            'api_key' => 'Api Key',
            'user_id' => 'User ID',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['user_id' => 'id']);
    }


}
