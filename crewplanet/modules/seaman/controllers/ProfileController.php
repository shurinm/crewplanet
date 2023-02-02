<?php

class ProfileController extends Controller
{
	public function accessRules()
	{
		return array(
			array('allow',
				'users' => array('*'),
				'actions' => array('register')
			),
			array('deny', 'users' => array('?'))
		);
	}

	/**
	 * Редактирование персональных данных моряка агентом (пока доступно только менеджерам Crewplanet)
	 *
	 * @param int $sId идентификатор моряка
	 */
	public function actionChange($sId)
	{
		if(!Yii::app()->authManager->isCrewplanetAgent()) throw new CHttpException(403);

		/** @var ModSeaman $model */
		$model = ModSeaman::model()->findByPk($sId);
		if(!$model) throw new CHttpException(404);

		if(isset($_POST['ModSeaman']))
		{
			$_POST['ModSeaman']['CI_telefon_types'] = $_POST['CI_telefon_type1'].$_POST['CI_telefon_type2'];

			$model->attributes = $_POST['ModSeaman'];

			// костыль
			$model->humanBirthDate = $model->PI_rozhdenije_data;

			if($model->save(true, array(
				'PI_imja', 'PI_familija',
				'PI_rozhdenije_strana', 'PI_rozhdenije_gorod', 'PI_rozhdenije_data',
				'PI_grazhdanstvo_strana', 'PI_postojannij_zhitelj', 'PI_personalnij_kod',
				'CI_strana', 'CI_shtat', 'CI_gorod', 'CI_indeks', 'CI_adres', 'CI_telefon_1', 'CI_telefon_2', 'CI_telefon_types',
				'AIR_port',
				'RI_tip_svjazi', 'RI_imja', 'RI_familija', 'RI_adres', 'RI_telefon',
				'PI_rost', 'PI_ves', 'PI_cvet_volos', 'PI_cvet_glaz', 'PI_botinki', 'PI_roba',
				'EI_nazvanije_zavedenija', 'EI_god_okonchanija',
				'EI_jazik_anglijskij'
			))
			)
			{

				Yii::app()->user->setFlash('success', Yii::t('seamanModule', 'Данные успешно сохранены'));
			}
		}


		//-----------------------------------------
		// костыль
		$model->PI_rozhdenije_data = App::tsToHuman(App::dbDateToTS($model->PI_rozhdenije_data));

		$this->render('change', array('model' => $model));
	}


	public function actionAjax()
	{
		if(!Yii::app()->request->isAjaxRequest) throw new CHttpException(404);

		$countryId = intval($_POST['countryId']);
		$airports = ModAirport::getAirportsOfCountryForDropdown($countryId);

		$htmlOptions['id'] = 'ModSeaman_AIR_port';
		$htmlOptions['class'] = '';

		echo json_encode(array(
			'status' => 'ok',
			'html' => CHtml::dropDownList('ModSeaman[AIR_port]', 0, $airports, $htmlOptions)
		));

		Yii::app()->end();
	}

	public function actionRegister()
	{
		if(!Yii::app()->user->isGuest)
			throw new CHttpException(404);

		$seaman = new ModSeaman();
		$user = new User();

		$user->scenario = 'seaman_creation';
		$seaman->scenario = 'create';

		if(isset($_POST['ajax']) && $_POST['ajax']==='registerForm')
	    {
		    $_1 = CActiveForm::validate($user);
		    $_2 = CActiveForm::validate($seaman);
	        echo json_encode(array_merge((Array)json_decode($_1), (Array)json_decode($_2)));
	        Yii::app()->end();
	    }

		if(isset($_POST['User']))
		{
			$user->attributes = $_POST['User'];
			$seaman->attributes = $_POST['ModSeaman'];

            // заполнить UTM-метки для моряка, если они есть в куках
            /* @var $utmCollector AdvCampaignUTMCollector */
            $utmCollector = Yii::app()->utmCollector;
            if ($utmCollector->hasCookiePrimaryUTMData(Yii::app()->request)) {
                $request = Yii::app()->request;
                $seaman->setAttribute('UTM_source', $utmCollector->getUTMData($request, 'utm_source'));
                $seaman->setAttribute('UTM_medium', $utmCollector->getUTMData($request, 'utm_medium'));
                $seaman->setAttribute('UTM_campaign', $utmCollector->getUTMData($request, 'utm_campaign'));
                $seaman->setAttribute('UTM_term', $utmCollector->getUTMData($request, 'utm_term'));
                $seaman->setAttribute('UTM_content', $utmCollector->getUTMData($request, 'utm_content'));
            }

			// используем дополнительные переменные, чтобы в любом случае обрабатывались ОБЕ валидации
			$_1 = $user->validate();
			$_2 = $seaman->validate();
			if($_1 && $_2)
			{
				$user->profile_type = User::PROFILE_SEAMAN;
				$user->full_name = $seaman->PI_imja.' '.$seaman->PI_familija;
				$user->phone = $seaman->CI_telefon_1;
				$user->active = 1;

				if($user->save())
				{
					$seaman->id = $user->id;
					if($seaman->save())
					{
						// если моряк с морехода, то сразу линкуем аккаунты
						if(isset($_GET['from']) && $_GET['from'] == 'morehod' && intval($_GET['from_id'])>0)
						{ // пришел с морехода - линкуем аккаунты
							$acc = new UserAccount();
							$acc->user_id = $user->id;
							$acc->service_name = UserAccount::SERVICE_MOREHOD_FORUM;
							$acc->service_id = intval($_GET['from_id']);
							$acc->save();
						}

						// авторизуем пользователя
						$_identity = new InsiteUserIdentity($user->email, $user->password);
						$_identity->authenticate();
						Yii::app()->user->login($_identity);

						$this->redirect(Yii::app()->getInsiteUrl('seafarers/registration').'?newSeaman=1');
					}
					else
					{
						Yii::app()->sendEmailToSupport('[ERROR] Не удалось зарегистрировать нового моряка',
							"При регистрации нового моряка возникла ошибка создания записи в _mod_seaman.\n\nДамп ошибок:\n".print_r($seaman->errors, true).
							"\n\nДамп данных:\n".print_r($seaman->attributes, true).
							"\n\nДамп данных юзера (запись была удалена):\n".print_r($user->attributes, true),
							'text/plain'
						);
						Yii::app()->user->setFlash('error', Yii::t('seamanModule.register', 'При сохранении данных возникла ошибка. Письмо с указанием причин отправлено в администрацию сайта. Мы свяжемся с Вами как только устраним проблему.'));

						// удаляем только что созданную запись в users
						$user->delete();
					}
				}
				else
				{
					Yii::app()->sendEmailToSupport('[ERROR] Не удалось зарегистрировать нового моряка',
						"При регистрации нового моряка возникла ошибка создания записи в users.\n\nДамп ошибок:\n".print_r($user->errors, true).
						"\n\nДамп данных:\n".print_r($user->attributes, true).
						"\n\nДамп данных моряка:\n".print_r($seaman->attributes, true),
						'text/plain'
					);
					Yii::app()->user->setFlash('error', Yii::t('seamanModule.register', 'При сохранении данных возникла ошибка. Письмо с указанием причин отправлено в администрацию сайта. Мы свяжемся с Вами как только устраним проблему.'));
				}
			}
		}

		$this->render('register', array('seaman' => $seaman, 'user' => $user));
	}

	public function actionDoneRegistration()
	{

	}
}
