<?php

class SiteController extends Controller
{
	public function actions()
	{
		return array(
			'forbidden' => 'application.controllers.actions.error403',
		);
	}


	public function accessRules()
	{ // доступ открыт для всех
		return array(
			array('allow', 'users' => array('*'))
		);
	}


	public function actionIndex()
	{
		// страна
		$country = isset($_SERVER['GEOIP_COUNTRY_CODE']) ? $_SERVER['GEOIP_COUNTRY_CODE'] : 'RU';

		if(Yii::app()->user->isGuest)
		{
			if(in_array($country, array('RU', 'UA', 'LT', 'LV', 'EE')))
				$this->redirect(array('site/seaman'));
			else
				$this->redirect(array('site/crewing'));
		}

		// залогиненый юзер
		if(Yii::app()->user->getType() == User::PROFILE_SEAMAN)
			$this->redirect(array('site/seaman'));
		else
			$this->redirect(array('site/crewing'));
	}

	/**
	 * Выводит статичные страницы (расположены в /views/site/static/<lang>/... )
	 *
	 * @param string $page имя страницы
	 *
	 * @throws CHttpException
	 */
	public function actionStatic($page = 'default')
	{
		$page = str_replace(array('\\', '..', '/', ':'), '', $page);

		if($page == 'startpage_shipowner')
			$this->redirect(array('/site/crewing'), true, 301); // костыль

		if(!empty($page) && $page != '__sample' /* блокировка отображения страницы-образца */)
		{
			$path = Yii::app()->getViewPath().DIRECTORY_SEPARATOR.'site'.DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR.Yii::app()->language.DIRECTORY_SEPARATOR.$page.'.php';
			if(file_exists($path))
			{
				$this->menuActiveItem = $page;
				$this->render('static', array('page' => $page, 'path' => $path));

				return;
			}
		}

		throw new CHttpException(404, Yii::t('_site', 'msg 404'));
	}


	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if($error = Yii::app()->errorHandler->error)
		{
			if(Yii::app()->request->isAjaxRequest)
			{
				echo $error['message'];
			}
			else
			{
				//-				$error['message'] .= ', request:'.Yii::app()->request->getRequestUri();
				switch ($error['code'])
				{
					case 404:
						$this->render('//error404', $error);
						break;
					case 403:
						if(Yii::app()->user->isGuest)
							$this->redirect(Yii::app()->user->loginUrl);
						else
							$this->render('//error403', $error);
						break;
					default:
						$this->render('error', $error);
				}
			}
		}
	}

	//======================================================
	// статичные страницы
	//======================================================

	public function actionSeaman()
	{
		$this->menuActiveItem = 'seaman';
		$this->render('seaman');
	}

	public function actionCrewing()
	{
		$this->menuActiveItem = 'shipowner';
		$this->render('shipowner');
	}

	public function actionOpendoc()
	{ // todo Этот метод создан только для тестирования верстки. Должен быть удален вместе с файлом /v2/protected/views/site/_opendoc_demo.php
		$this->render('_opendoc_demo');
	}

	public function actionContact()
	{
		$model = new ContactForm;

		if(!Yii::app()->user->isGuest)
		{
			/** @var User $u */
			$u = Yii::app()->user->getAr();
			$model->company = Yii::app()->authManager->isAgent() ? $u->shipowner->firma : '';
			$model->email = $u->email;
			$model->name = $u->full_name;
			$model->phone = $u->phone;
		}

		if(isset($_POST['submit']))
		{
			$model->attributes = $_POST['ContactForm'];
			if($model->validate())
			{
				if (trim(strtolower($model->company)) <> 'acunetix'){
					//-----------------------------------------
					// мыло админу
					$msg = $this->renderPartial('email/contact_form', array('model' => $model), true);

					Yii::app()->sendEmailToSupport(
						Yii::t('_site_contact', 'Вопрос от клиента').' - '.$model->theme,
						$msg,
						'text/html',
						Yii::app()->params['salesEmail'],
						false,
						$model->attributes['email']
					);

					Yii::app()->user->setFlash('success', Yii::t('_site_contact', 'Отправлено!'));

					$model->theme = '';
					$model->message = '';
				}
			}
			else
				Yii::app()->user->setFlash('error', Yii::t('_site_contact', '<strong>Обнаружены ошибки!</strong><br/>Поля с ошибочными данными помечены оранжевым цветом.'));
		}

		$this->menuActiveItem = 'contact';
		$this->render('contact', array('model' => $model));
	}

	/**
	 * Форма создания запроса на вакансию (хез что это такое - m00nk)
	 */
	public function actionSend_crewing_inquiry()
	{
		$model = new CrewingInquiryForm;

		if(isset($_POST['submit']))
		{
			$model->attributes = $_POST['CrewingInquiryForm'];
			if($model->validate())
			{
				//-----------------------------------------
				// мыло админу
				$msg = $this->renderPartial('email/crewing_inquiry_form', array('model' => $model), true);

				Yii::app()->sendEmailToSupport(Yii::t('_site', 'Запрос на обработку вакансии'), $msg, 'text/html',
					Yii::app()->params['salesEmail']);

				Yii::app()->user->setFlash('success', Yii::t('_site', 'Ваш запрос на моряка отправлен в круинг! Мы с Вами свяжемся в ближайшее время.'));
				$model = new CrewingInquiryForm;

			}
			else
				Yii::app()->user->setFlash('error', Yii::t('_site_contact', '<strong>Обнаружены ошибки!</strong><br/>Поля с ошибочными данными помечены оранжевым цветом.'));
		}

		$this->render('crewing_inquiry', array('model' => $model));
	}

	/**
	 * Отображение страницы входа.
	 *
	 * @param string $returnUrl Не обязательный адрес страницы, на который будет перенаправлен пользователь после входа. Используется только
	 *                          для авторизации из in_site. Для авторизации из Yii следует использовать встроенный механизм.
	 */
	public function actionLogin($returnUrl = '')
	{
		if(!Yii::app()->user->isGuest)
			$this->redirect(!empty($returnUrl) ? $returnUrl : Yii::app()->user->returnUrl);

		$model = new LoginForm;
		$model->returnUrl = $returnUrl;

		if(isset($_POST['LoginForm']))
		{
			$model->attributes = $_POST['LoginForm'];
			if($model->validate())
			{
				$_identity = new InsiteUserIdentity($model->username, $model->password);
				if($_identity->authenticate())
				{ // аутентифицируем пользователя
					Yii::app()->user->login($_identity, $model->rememberMe == 1 ? intval(Yii::app()->params['cookieLifeTime']) : 0);

					if(!empty($model->returnUrl))
						Yii::app()->user->returnUrl = $model->returnUrl;
					else
					{
						/** @var User $user */
						$user = User::model()->findByAttributes(array('email' => $model->username));

						if($user->profile_type == User::PROFILE_SEAMAN)
							Yii::app()->user->returnUrl = $this->createUrl('/seaman/startPage/show'); // моряк

						if($user->profile_type == User::PROFILE_SHIPOWNER)
							Yii::app()->user->returnUrl = $this->createUrl('/shipowner/membership/welcome'); // судовладелец

						if($user->profile_type == User::PROFILE_OBSERVER)
							Yii::app()->user->returnUrl = $this->createUrl('/planning/list/observe'); // наблюдатель
					}

					// корректируем язык
					if(Yii::app()->language == 'ru')
						Yii::app()->user->returnUrl = str_replace('/en/', '/ru/', Yii::app()->user->returnUrl);
					else
						Yii::app()->user->returnUrl = str_replace('/ru/', '/en/', Yii::app()->user->returnUrl);

					$this->redirect(Yii::app()->user->returnUrl);
				}
				Yii::app()->user->setFlash('error', $_identity->errorMessage);
			}
			else
			{
				$error_messages = '';
				foreach ($model->getErrors() as $key => $value)
				{
					$error_messages .= implode('<br/>', $value).'<br/>';
				}
				Yii::app()->user->setFlash('error', '<strong>'.Yii::t('_site', 'Обнаружена ошибка').'</strong><br/>'.$error_messages);
			}
		}
		$this->render('login', array('model' => $model));
	}

	public function actionLogout($returnUrl = '/')
	{
		Yii::app()->user->logout();
		$this->redirect(urldecode($returnUrl));
	}

	/**
	 * Форма восстановления пароля
	 */
	public function actionRestorePassword()
	{
		$model = new RestorePasswordForm;
		if(isset($_POST['submit']))
		{
			$model->attributes = $_POST['RestorePasswordForm'];
			if($model->validate())
			{
				//-----------------------------------------
				// мыло клиенту
				$user = User::model()->findByAttributes(array('email' => $model->email));
				$msg = $this->renderPartial('email/password_restore', array('model' => $user), true);

				$message = new YiiMailMessage;
				$message->setBody($msg, 'text/html');
				$message->setSubject(Yii::t('_site_contact', 'Восстановление пароля'));

				$message->addTo($user->email);
				//          $message->setBcc(Yii::app()->params['supportEmail']);
				$message->from = Yii::app()->params['doNotReplyEmail'];
				Yii::app()->mail->send($message);

				Yii::app()->user->setFlash('success', Yii::t('_site', 'Пароль успешно отправлен на ваш электронный ящик.'));
				$this->refresh();
			}
			else
				Yii::app()->user->setFlash('error', $model->getError('email'));
		}
		$this->render('formRestorePassword', array('model' => $model));
	}

	/**
	 * Форма редактора профиля моряка (НЕ АНКЕТА)
	 */
	public function actionEditUserProfile()
	{
		if(Yii::app()->user->isGuest)
			$this->redirect(array('login', 'returnUrl' => $this->createUrl('site/editUserProfile')));

		$userProfile = new UserProfileForm;
		if(isset($_POST['submit']))
		{
			$userProfile->attributes = $_POST['UserProfileForm'];
			if($userProfile->validate())
			{
				/** @var User $user */
				$user = User::model()->findByPk(Yii::app()->user->id);
				if(!empty($userProfile->email))
					$user->email = $userProfile->email;

				if(!empty($userProfile->newPassword))
					$user->password = $userProfile->newPassword;

				if($user->save())
					Yii::app()->user->setFlash('success', Yii::t('_site', 'Ваши данные успешно обновлены. Не забудьте использовать новые данные при следующем входе в систему.'));
				else
					Yii::app()->user->setFlash('error', Yii::t('_site', 'Не удалось обновить Ваши данные. Обратитесь к администрации сайта для решения проблемы.'));
			}
		}

		$this->render('formUserProfile', array('model' => $userProfile));
	}

	/**
	 * Рассылка напоминаний морякам, которые давно не посещали сайт. Вызывается кроном.
	 */
	public function actionSeamanReminder()
	{
		set_time_limit(0); // эта музыка будет вечной....

		$_lng = Yii::app()->language;

		$list = Yii::app()->db->createCommand("
			SELECT
				u.id,
				u.email,
 				u.last_hit, datediff(now(),
				u.last_hit) AS absentDays,

				s.PI_familija AS lname,
				s.PI_imja AS fname

			FROM
				users AS u
					LEFT JOIN _mod_seaman AS s ON s.id = u.id

			WHERE
				u.profile_type = 0
				AND u.active = 1
				AND	datediff(now(), u.last_hit) BETWEEN 365 AND 700 /* больше года , но меньше двух лет */
				AND u.last_hit <> '0000-00-00 00:00:00' /* активированный аккаунт */
				AND (datediff(now(), s.last_reminder) > 180 OR  s.last_reminder = '0000-00-00') /* не менее полу года с момента предыдущего напоминания */
			")->queryAll();

		for ($i = 0, $_c = count($list); $i < $_c; $i++)
		{
			$_dstEmail = trim($list[$i]['email']);

			/** @var User $us */
			$us = User::model()->findByPk($list[$i]['id']);

			if(Yii::app()->isEmailValid($_dstEmail))
			{
				Yii::app()->language = $us->getSetting('language', 'en');
				$body = $this->renderPartial('email/'.Yii::app()->language.'/reminder', array('data' => $list[$i]), true);

				$message = new YiiMailMessage;
				$message->setBody($body, 'text/html');
				$message->subject = Yii::t('_site', 'Обновите свою анкету');
				$message->addTo($_dstEmail, $list[$i]['fname'].' '.$list[$i]['lname']);
				$message->from = Yii::app()->params['doNotReplyEmail'];
				Yii::app()->mail->send($message);

				Yii::app()->db->createCommand("UPDATE _mod_seaman SET last_reminder = NOW() WHERE id = ".$list[$i]['id'])->execute();
			}
		}

		Yii::app()->language = $_lng;
	}

	public function actionCleanDirs()
	{
		$list = Yii::app()->params['paths']['cleaning'];

		for ($i = 0, $_c = count($list); $i < $_c; $i++)
			Yii::app()->deleteFolder($list[$i], false, 24*60*60);
	}

	public function actionParsePosition()
	{
		if(!Yii::app()->request->isAjaxRequest) throw new CHttpException(404);

		$position = trim($_POST['position']);

		$normalizedPositionTitle = PositionParser::normalizePosition($position);
		$data = PositionParser::index_normalized_position($normalizedPositionTitle);

		echo json_encode(array(
			'normalizedPositionTitle' => $normalizedPositionTitle,
			'data' => $data
		));

		Yii::app()->end();
	}
}
