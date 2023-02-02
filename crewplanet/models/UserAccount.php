<?php

/**
 * This is the model class for table "user_accounts".
 *
 * The followings are the available columns in table 'user_accounts':
 * @property integer $id
 * @property integer $user_id
 * @property string $service_name
 * @property string $service_id
 * @property string $service_url
 *
 * The followings are the available model relations:
 * @property User $user
 */
class UserAccount extends CActiveRecord
{
	const SERVICE_MOREHOD_FORUM = 'morehod_forum';
	const SERVICE_GOOGLE = 'google';
	const SERVICE_FACEBOOK = 'facebook';


	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserAccount the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'user_accounts';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id', 'required'),
			array('user_id', 'numerical', 'integerOnly'=>true),
			array('service_name, service_id', 'length', 'max'=>100),
			array('service_url', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, service_name, service_id, service_url', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_id' => 'User',
			'service_name' => 'Service Name',
			'service_id' => 'Service',
			'service_url' => 'Service Url',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('service_name',$this->service_name,true);
		$criteria->compare('service_id',$this->service_id,true);
		$criteria->compare('service_url',$this->service_url,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}