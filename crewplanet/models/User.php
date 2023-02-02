<?php

/**
 * This is the model class for table "users".
 *
 * The followings are the available columns in table 'users':
 *
 * @property integer $id
 * @property integer $parent_id
 * @property string  $email
 * @property string  $password
 * @property string  $last_ip
 * @property string  $last_hit
 * @property string  $role
 * @property integer $profile_type
 * @property integer $active
 * @property string  $full_name
 * @property string  $phone
 * @property string  $nickname
 * @property string  $secret_code
 * @property string  $settings
 *
 *----------------------------------------------------------------
 * @property ModShipowner    $shipowner
 * @property ModSeaman    $seaman
 * @property UserAccount[] $accounts
 */
class User extends CActiveRecord
{
	const PROFILE_SEAMAN = 0;
	const PROFILE_SHIPOWNER = 1;
	const PROFILE_OBSERVER = 2; // наблюдатели за группами судов (просмотр чужого планирования)

	const ROLE_SHIPOWNER_ADMIN = 'shipowner_admin'; // судовладелец-администратор
	const ROLE_SHIPOWNER_MANAGER = 'shipowner_manager'; // судовладелец-менеджер
	const ROLE_SHIPOWNER_ASSISTANT = 'shipowner_assistant'; // судовладелец-ассистент
	const ROLE_BOOKER = 'booker'; // бухгалтер

	//-----------------------------------------
	// переменные без полей в БД (вспомогательные)
	public $password2 = '';
	public $email2 = '';

	//-----------------------------------------
	private $_unpackedSettings = null;

	/**
	 * Возвращает ассоциативный массив ролей, доступных указанному провилю пользователя
	 *
	 * @param int $profile код профиля. Если null, то массив будет содержать список ролей для всех профилей
	 *
	 * @return array
	 */
	public static function getRolesArray($profile = null)
	{
		$out = array();

		if($profile == null || $profile == self::PROFILE_OBSERVER)
		{ // добавляем роли групп судов

		}

		if($profile == null || $profile == self::PROFILE_SEAMAN)
		{ // добавляем роли моряков

		}

		if($profile == null || $profile == self::PROFILE_SHIPOWNER)
		{ // добавляем роли судовладельцев
			$out[self::ROLE_SHIPOWNER_ASSISTANT] = Yii::t('modelUser', 'Ассистент');
			$out[self::ROLE_SHIPOWNER_MANAGER] = Yii::t('modelUser', 'Менеджер');
			$out[self::ROLE_BOOKER] = Yii::t('modelUser', 'Бухгалтер');
			$out[self::ROLE_SHIPOWNER_ADMIN] = Yii::t('modelUser', 'Администратор');
		}

		return $out;
	}

	public static function getRoleTitle($roleCode)
	{
		$_ = self::getRolesArray();

		return $_[$roleCode];
	}

	//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
	//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
	//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param string $className active record class name.
	 *
	 * @return User the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'users';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('email, password', 'required'),
			array('parent_id, profile_type, active', 'numerical', 'integerOnly' => true),
			array('email, full_name', 'length', 'max' => 100),
			array('email', 'email', 'message' => Yii::t('modelUser', 'В е-мейле есть ошибка. Проверьте ввод')),
			array('password, role, secret_code', 'length', 'max' => 32),
			array('last_ip', 'length', 'max' => 15),
			array('phone', 'length', 'max' => 20),
			array('nickname', 'length', 'max' => 4),
			array('settings', 'safe'),

			array('active', 'in', 'range' => array('0', '1')),
			array('profile_type', 'in', 'range' => array(self::PROFILE_SEAMAN, self::PROFILE_SHIPOWNER, self::PROFILE_OBSERVER)),



			//-----------------------------------------
			// регистрация моряка (сценарий seaman_creation)
			array('email2', 'email', 'on' => 'seaman_creation', 'message' => Yii::t('modelUser', 'В е-мейле есть ошибка. Проверьте ввод')),
			array('email2', 'required', 'on' => 'seaman_creation', 'message' => Yii::t('modelUser', 'Введите емейл еще раз для проверки')),
			array('email2, password2', 'required', 'on' => 'seaman_creation'),
			array('email2', 'compare', 'compareAttribute' => 'email', 'on' => 'seaman_creation', 'message' => Yii::t('modelUser', 'Введенные адреса электронной почты не совпадают')),

			// сценарий shipowner_creation, shipowner_edition
			array('email, password, password2, full_name, phone, nickname, role', 'required', 'on' => 'shipowner_creation, shipowner_edition'),

			// сценарий shipowner_creation, shipowner_edition, seaman_creation
			array('password', 'length', 'min' => 5),
			array('email', 'email', 'on' => 'seaman_creation, shipowner_creation, shipowner_edition'),
			array('email', 'unique', 'on' => 'seaman_creation, shipowner_creation, shipowner_edition'),
			array('password2', 'compare', 'compareAttribute' => 'password', 'on' => 'seaman_creation, shipowner_creation, shipowner_edition', 'message' => Yii::t('modelUser', 'Введенные пароли не совпадают')),

			// сценарий search
			array('id, parent_id, email, password, last_ip, active, last_hit, role, profile_type, full_name, phone, nickname', 'safe', 'on' => 'search'),
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
			'shipowner' => array(self::BELONGS_TO, 'ModShipowner', 'parent_id'),
			'seaman' => array(self::BELONGS_TO, 'ModSeaman', 'id'),
			'accounts' => array(self::HAS_MANY, 'UserAccount', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'parent_id' => Yii::t('modelUser', 'Родитель'),
			'email' => Yii::t('modelUser', 'Email = Логин'),
			'email2' => Yii::t('modelUser', 'Email еще раз'),
			'password' => Yii::t('modelUser', 'Пароль'),
			'password2' => Yii::t('modelUser', 'Пароль еще раз'),
			'last_ip' => Yii::t('modelUser', 'Последний IP'),
			'last_hit' => Yii::t('modelUser', 'Последний хит'),
			'role' => Yii::t('modelUser', 'Роль'),
			'profile_type' => Yii::t('modelUser', 'Тип профиля'),
			'active' => Yii::t('modelUser', 'Активен'),
			'full_name' => Yii::t('modelUser', 'Полное имя'),
			'phone' => Yii::t('modelUser', 'Телефон'),
			'nickname' => Yii::t('modelUser', 'Псевдоним'),
			'secret_code' => '',
			'settings' => Yii::t('modelUser', 'Настройки'),
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('parent_id', $this->parent_id);
		$criteria->compare('email', $this->email, true);
		$criteria->compare('password', $this->password, true);
		$criteria->compare('last_ip', $this->last_ip, true);
		$criteria->compare('last_hit', $this->last_hit, true);
		$criteria->compare('role', $this->role, true);
		$criteria->compare('profile_type', $this->profile_type);
		$criteria->compare('active', $this->active);
		$criteria->compare('full_name', $this->full_name, true);
		$criteria->compare('phone', $this->phone, true);
		$criteria->compare('nickname', $this->nickname, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	public function beforeValidate()
	{
		if(empty($this->last_hit))
		{
			if($this->parent_id == 0)
				$this->last_hit = date('Y-m-d H:i:s'); // морякам сразу проставляем дату последнего входа
			else
				$this->last_hit = '0000-00-00 00:00:00';; // агентам НЕ проставляем дату последнего входа, т.к. по ней определяется факт активации аккаунта и начисление бонусов
		}

		return parent::beforeValidate();
	}

	public function afterFind()
	{
		$this->password2 = $this->password;

		parent::afterFind();
	}

	/**
	 * Получение значения параметра юзера
	 *
	 * @param string $key
	 * @param null   $default значение по-умолчанию
	 *
	 * @return null
	 */
	public function getSetting($key, $default = null)
	{
		$this->_unpackSettings();
		if(isset($this->_unpackedSettings[$key]))
			return $this->_unpackedSettings[$key];

		return $default;
	}

	/**
	 * Установка параметра юзера
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setSetting($key, $value)
	{
		$this->_unpackSettings();
		$this->_unpackedSettings[$key] = $value;
		if(!$this->isNewRecord)
		{
			$this->settings = serialize($this->_unpackedSettings);
			$this->save(false, array('settings'));
		}
	}

	private function _unpackSettings()
	{
		if(!$this->_unpackedSettings)
		{
			if(!empty($this->settings))
				$this->_unpackedSettings = unserialize($this->settings);
			if($this->_unpackedSettings === false)
				$this->_unpackedSettings = array();
		}
	}


	/**
	 * Загружает аватар пользователя. Делает автоматическую обрезку
	 *
	 * @param string $name   имя поля в массиве $_FILES
	 * @param int    $userId идентификатор пользователя
	 *
	 * @return array массив данных о загрузке
	 */
	public static function uploadAvatar($name, $userId)
	{
		$out = array(
			'status' => 'error',
			'message' => Yii::t('modelUser', 'Ошибка загрузки файла.'),
			'filename' => '',
			'fileUrl' => '',
			'filePath' => ''
		);

		$avatarsFolder = Yii::app()->params['avatars']['folderPath'];
		$subfolder = substr('00'.intval($userId / 1000), -2);
		$filename = self::getAvatarFilename($userId);

		/** @var CUploadedFile $uploadedFile */
		$uploadedFile = CUploadedFile::getInstanceByName($name);
		if($uploadedFile && $uploadedFile->getError() == 0)
		{
			if(in_array($uploadedFile->getType(), array('image/png', 'image/jpg', 'image/gif', 'image/jpeg')))
			{
				@mkdir($avatarsFolder, 0777);
				@chmod($avatarsFolder, 0777);

				@mkdir($avatarsFolder.DIRECTORY_SEPARATOR.$subfolder, 0777);
				@chmod($avatarsFolder.DIRECTORY_SEPARATOR.$subfolder, 0777);

				/** @var MImageHandler $ih */
				$ih = Yii::app()->imageHandler;
				try
				{
					$ih->load($uploadedFile->tempName);
					$imgW = $ih->getWidth();
					$imgH = $ih->getHeight();

					if($imgW / $imgH > Yii::app()->params['avatars']['width'] / Yii::app()->params['avatars']['height'])
					{
						$h = $imgH;
						$w = intval($imgH * Yii::app()->params['avatars']['width'] / Yii::app()->params['avatars']['height']);
					}
					else
					{
						$w = $imgW;
						$h = intval($imgW * Yii::app()->params['avatars']['height'] / Yii::app()->params['avatars']['width']);
					}

					$sx = intval(($imgW - $w) / 2);
					$sy = intval(($imgH - $h) / 2);

					$ih->crop($w, $h, $sx, $sy)->resize(Yii::app()->params['avatars']['width'], Yii::app()->params['avatars']['height'], true);
					$ih->save(self::getAvatarPath($userId), MDriverGD::IMG_JPEG, 60);

					$out = array(
						'status' => 'ok',
						'message' => '',
						'filename' => $filename,
						'fileUrl' => self::getAvatarUrl($userId),
						'filePath' => self::getAvatarPath($userId)
					);
				} catch (Exception $e)
				{
					$out['message'] = Yii::t('modelUser', 'Файл имеет недопустимый тип. Разрешены только png, gif и jpg.');
				}
			}
			else
				$out['message'] = Yii::t('modelUser', 'Файл имеет недопустимый тип. Разрешены только png, gif и jpg.');
		}

		return $out;
	}

	/**
	 * Генерирует имя файла аватара для указанного юзера
	 *
	 * @param int $userId идентификатор пользователя
	 *
	 * @return string
	 */
	public static function getAvatarFilename($userId)
	{
		return md5(Yii::app()->params['md5salt'].$userId.Yii::app()->params['md5salt']).'.jpg';
	}

	/**
	 * Возвращает абсолютный УРЛ аватара пользователя
	 *
	 * @param int  $userId          идентификатор пользователя
	 * @param bool $checkFileExists если true - проводится проверка на существование аватара и если его нет возвращает УРЛ к дефолтовой картинке.
	 *                              false - никаких проверок не проводится и всегда возвращает УРЛ автара, даже если он не существует
	 *
	 * @return string
	 */
	public static function getAvatarUrl($userId, $checkFileExists = false)
	{
		if(!$checkFileExists || file_exists(self::getAvatarPath($userId)))
			return 'http://'.Yii::app()->request->serverName.Yii::app()->params['avatars']['folderUrl'].'/'.substr('00'.intval($userId / 1000), -2).'/'.self::getAvatarFilename($userId);
		else
			return 'http://'.Yii::app()->request->serverName.'/images/seaman_unknown.gif';
	}

	/**
	 * Возвращает полный путь к файлу аватара пользователя
	 *
	 * @param int $userId идентификатор пользователя
	 *
	 * @return string
	 */
	public static function getAvatarPath($userId)
	{
		return Yii::app()->params['avatars']['folderPath'].DIRECTORY_SEPARATOR.substr('00'.intval($userId / 1000), -2).DIRECTORY_SEPARATOR.self::getAvatarFilename($userId);
	}
}
