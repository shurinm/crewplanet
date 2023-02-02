<?php

class AjaxController extends Controller
{
	public function actionCard()
	{
		$cmd = trim($_POST['cmd']);

		/** @var ModSeaman $seaman */
		$seaman = ModSeaman::model()->findByPk(intval($_POST['seamanId']));
		if(!$seaman && $cmd != 'addComment')
		{
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Пользователь не найден. Операция отменена.')));
		}
		else
		{
			switch ($cmd)
			{
				case 'addCandidate':
					$this->_addCandidate($seaman);
					break;
				case 'getVacanciesList':
					$this->_getVacanciesList($seaman);
					break;
				case 'updateTags':
					$this->_updateTags($seaman);
					break;
				case 'getTagEditor':
					$this->_getTagEditor($seaman);
					break;
				case 'getEmail':
					$this->_getEmail($seaman);
					break;
				case 'getAccess':
					$this->_getAccess($seaman);
					break;
				case 'getAccessForm':
					$this->_getAccessForm($seaman);
					break;
				case 'getNotes':
					$this->_getNotes($seaman);
					break;
				case 'deleteAllComments':
					$this->_deleteAllComments($seaman);
					break;
				case 'deleteContractComment':
					$this->_deleteContractComment();
					break;
				case 'addComment':
					$this->_addComment();
					break;
				case 'getSsComments':
					$this->_getSsComments($seaman);
					break;
				case 'getSection':
					$this->_getSection($seaman);
					break;
				case 'sendCredentials':
					$this->_sendCredentials($seaman);
					break;
				case 'showSeaman':
					$this->_showSeaman($seaman);
					break;
				case 'hideSeaman':
					$this->_hideSeaman($seaman);
					break;
				case 'deleteAvatar':
					$this->_deleteAvatar($seaman);
					break;
				case 'setEnglish':
					$this->_setEnglish($seaman);
					break;
				case 'setDor':
					$this->_setDor($seaman);
					break;

				case 'addToPlanning':
					$this->_addToPlanning($seaman);
					break;

				//-----------------------------------------
				// перегенерация карточки моряка
				case 'getCard':
					$data = SeamanCard::getOne($seaman->id);

					// пытаемся найти данные из вакансии (если вызов произошел из списка кандидатов)
					$vacancyId = intval($_POST['vacancyId']);

					/** @var VacVacancy $vacancy */
					$vacancy = VacVacancy::model()->findByPk($vacancyId);
					if($vacancy)
					{
						$vacInfo = VacCandidate::getPipelineInfo($vacancyId);
						for ($i = 0, $_c = count($vacInfo); $i < $_c; $i++)
						{
							if($vacInfo[$i]['seaman_id'] == $data['id'])
							{
								$data['_vacInfo'] = $vacInfo[$i];
								$data['_vacInfo']['_isArchived'] = $vacancy->isArchived();

								break;
							}
						}
					}

					echo json_encode(array(
						'status' => 'ok',
						'html' => $this->renderPartial('/seaman_card', array('data' => $data), true)
					));
					break;

				default:
					echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Неопознанная команда.')));
			}
		}

		Yii::app()->end();
	}

	public function accessRules()
	{
		return array(
			array('allow',
				'users' => array('*'),
				'actions' => array('card', 'upload')
			),
			array('deny', 'users' => array('?'))
		);
	}

	public function beforeAction($action)
	{ // разрешены только AJAX-запросы
		return parent::beforeAction($action) && ($action->id == 'upload' || Yii::app()->request->isAjaxRequest);
	}

	/**
	 * Загрузка аватара
	 *
	 * @param int $sId идентификатор моряка
	 *
	 * @throws CHttpException
	 */
	public function actionUpload($sId)
	{
		$sId = intval($sId);
		$user = User::model()->findByPk($sId);
		if(!$user) throw new CHttpException(404);

		if(!Yii::app()->user->checkAccess('allowChangeAvatar', array('seamanId' => $user->id)))
		{
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У вас недостаточно прав для выполнения этого действия.')));
		}
		else
		{
			$res = User::uploadAvatar('file', $sId);
			if($res['status'] == 'ok')
				echo json_encode(array(
					'status' => 'ok',
					'html' => CHtml::image(User::getAvatarUrl($user->id).'?time='.time()).CHtml::link(Yii::t('seamanModule.ajax', 'Удалить'), '#', array('class' => 'avatar_delete_link'))
				));
			else
				echo json_encode(array('status' => $res['message']));
		}

		Yii::app()->end();
	}

	//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
	//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
	//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

	/**
	 * Добавляет моряка в кандидаты на вакансию
	 *
	 * @param ModSeaman $seaman
	 */
	private function _addCandidate($seaman)
	{
		$vacancy = VacVacancy::model()->findByPk($_POST['vacId']);
		$status = intval($_POST['status']);
		if($vacancy && $status > 0)
		{
			$cand = VacCandidate::model()->findByAttributes(array('vacancy_id' => $vacancy->id, 'seaman_id' => $seaman->id));
			if(!$cand)
			{
				$cand = new VacCandidate();
				$cand->vacancy_id = $vacancy->id;
				$cand->seaman_id = $seaman->id;
				$cand->status = $status;
				$cand->quality = VacCandidate::QUALITY_NONE;
				$cand->enable_notify = 0;
				$cand->is_free = 0;

				if($cand->save())
				{
					echo json_encode(array(
						'status' => 'ok',
						'message' => Yii::t('seamanModule.ajax', 'Кандидат успешно добавлен.'),
						'title' => Yii::t('seamanModule.ajax', 'Отчет'),
					));
				}
				else
					echo json_encode(array('status' => Yii::t('seamanModule.ajax', '[[отказ претенденту - ошибка создания записи в таблице vac_candidates]]')));
			}
			else
				echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Нельзя дважды претендовать на одну и ту же вакансию.')));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Неверные параметры запроса.')));
	}

	/**
	 * Перенаправляем юзера на страницу добавления моряка в планирование
	 *
	 * @param ModSeaman $seaman
	 */
	private function _addToPlanning(ModSeaman $seaman)
	{
		echo json_encode(array(
			'status' => 'ok',
			'location' => $this->createAbsoluteUrl('/planning/contract/add', array('seamanId'=>$seaman->id))
		));
	}

	/**
	 * Генерирует форму выбора вакансии для включения моряка в кандидаты
	 *
	 * @param ModSeaman $seaman
	 */
	private function _getVacanciesList($seaman)
	{
		// получаем все активные вакансии судовладельца, с указанием на какие из них уже претендует моряк
		$vacs = Yii::app()->db->createCommand("
			SELECT
				v.*, vc.status,
				IF(v.position_freeenter IS NOT NULL AND v.position_freeenter <>'', v.position_freeenter, pd1.name_eng) AS _positionTitle,
				pd2.name_eng AS _vesselType,
				if(v.vessel_name IS NOT NULL AND v.vessel_name <> '', v.vessel_name, vs.vessel_name) AS _vesselTitle,
				vg.title AS _groupTitle

			FROM
				vac_vacancies AS v
					LEFT JOIN vac_candidates AS vc ON vc.vacancy_id = v.id AND vc.seaman_id = :seamanId

					LEFT JOIN _mod_predefined_data AS pd1 ON v.position1 = pd1.id
					LEFT JOIN _mod_predefined_data AS pd2 ON v.vessel_type = pd2.id
					LEFT JOIN vac_ships AS vs ON v.ship_id = vs.id
					LEFT JOIN vac_ship_groups AS vg ON vs.group_id = vg.id

			WHERE
				v.archived = '0000-00-00 00:00:00'
				/* AND v.join_date_current > NOW() */
				AND v.shipowner_id = :agentId
			ORDER BY v.join_date_current DESC
			")->queryAll(true, array(
			':agentId' => Yii::app()->user->parent_id,
			':seamanId' => $seaman->id
		));

		echo json_encode(array(
			'status' => 'ok',
			'html' => $this->renderPartial('vacancies_list', array('vacs' => $vacs), true),
			'title' => Yii::t('seamanModule.ajax', 'Добавление моряка в кандидаты')
		));
	}


	/**
	 * устанавливает дату готовности
	 *
	 * @param ModSeaman $seaman
	 */
	private function _setDor($seaman)
	{
		if(Yii::app()->user->checkAccess('seamanCard_allowChangeDor', array('seamanId' => $seaman->id)))
		{
			$newDate = trim($_POST['newDate']);
			$newDate = App::humanToTS($newDate);

			// добавляем заметку
			$note = new ModSeamenNotes();

			$note->note_type = ModSeamenNotes::TYPE_GENERAL;
			$note->note = '';
			$note->estimated_readiness_date = $newDate;
			$note->public = 0;
			$note->seaman_id = $seaman->id;

			if($note->save())
				echo json_encode(array('status' => 'ok', 'value' => App::tsToHuman($newDate)));
			else
				echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Не удалось сохранить данные о дате готовности.')));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У вас недостаточно прав для выполнения этого действия.')));
	}

	/**
	 * устанавливает уровень английского
	 *
	 * @param $seaman
	 */
	private function _setEnglish($seaman)
	{
		if(Yii::app()->user->checkAccess('seamanCard_allowEditEnglishLevel', array('seamanId' => $seaman->id)))
		{
			$level = intval($_POST['level']);

			if(Yii::app()->user->checkAccess('roleAgent'))
			{ // агент меняет уровень в заметках
				$note = new ModSeamenNotes();

				$note->note_type = ModSeamenNotes::TYPE_GENERAL;
				$note->note = '';
				$note->english_level = $level;
				$note->public = 0;
				$note->seaman_id = $seaman->id;

				if($note->save())
				{
					$_engTitle = Yii::t('seamanModule.card', 'Проверено').' ('.App::tsToHuman(time()).')';
					if(Yii::app()->user->getUsersNumber() > 1) $_engTitle .= ' - '.Yii::app()->user->getGroupUsername();


					echo json_encode(array(
						'status' => 'ok',
						'value' => ModPredefinedData::model()->getValue($level),
						'showMark' => true,
						'markTitle' => $_engTitle
					));
				}
				else
					echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Не удалось сохранить данные об уровне английского.')));
			}
			else
			{ // моряк меняет свой уровень в анкете
				$seaman->EI_jazik_anglijskij = $level;
				$seaman->save(true, array('EI_jazik_anglijskij'));
				echo json_encode(array(
					'status' => 'ok',
					'value' => ModPredefinedData::model()->getValue($level),
					'showMark' => false,
					'markTitle' => ''
				));
			}
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У вас недостаточно прав для выполнения этого действия.')));
	}

	/**
	 * удаляет аватар моряка
	 *
	 * @param $seaman
	 */
	private function _deleteAvatar($seaman)
	{
		if(Yii::app()->user->checkAccess('allowChangeAvatar', array('seamanId' => $seaman->id)))
		{
			$filePath = User::getAvatarPath($seaman->id);
			if(file_exists($filePath)) unlink($filePath);

			echo json_encode(array(
				'status' => 'ok',
				'url' => 'http://'.Yii::app()->request->serverName.'/images/seaman_unknown_upload_'.Yii::app()->language.'.gif'
			));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У вас недостаточно прав для выполнения этого действия.')));
	}

	/**
	 * прячет моряка
	 *
	 * @param $seaman
	 */
	private function _hideSeaman($seaman)
	{
		if(Yii::app()->user->checkAccess('allowChangeVisibility', array('seamanId' => $seaman->id)))
		{
			$seaman->visible_to_others = 0;
			$seaman->save(true, array('visible_to_others'));

			echo json_encode(array('status' => 'ok'));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У вас недостаточно прав для выполнения этого действия.')));
	}

	/**
	 * включает показ ранее спрятанного моряка
	 *
	 * @param $seaman
	 */
	private function _showSeaman($seaman)
	{
		if(Yii::app()->user->checkAccess('allowChangeVisibility', array('seamanId' => $seaman->id)))
		{
			$seaman->visible_to_others = 1;
			$seaman->save(true, array('visible_to_others'));

			echo json_encode(array('status' => 'ok'));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У вас недостаточно прав для выполнения этого действия.')));
	}

	/**
	 * отправляет моряку данные для входа в систему
	 *
	 * @param $seaman
	 */
	private function _sendCredentials($seaman)
	{
		if(Yii::app()->user->checkAccess('allowSendPassword', array('seamanId' => $seaman->id)))
		{
			$user = User::model()->findByPk(intval($_POST['seamanId']));
			if($user)
			{
				//-----------------------------------------
				// мыло клиенту
				$msg = $this->renderPartial('emails/password_restore', array('model' => $user), true);
				$message = new YiiMailMessage;
				$message->setBody($msg, 'text/html');
				$message->setSubject(Yii::t('seamanModule.ajax', 'Восстановление пароля'));
				$message->addTo($user->email);
				$message->from = Yii::app()->params['doNotReplyEmail'];
				Yii::app()->mail->send($message);

				echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Данные для входа отправлены пользователю.')));
			}
			else
				echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Пользователь не найден.')));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У вас недостаточно прав для выполнения этого действия.')));
	}

	/**
	 * отправляет контент закладок карточки моряка
	 *
	 * @param $seaman
	 *
	 * @throws CHttpException
	 */
	private function _getSection($seaman)
	{
		$section = trim($_POST['section']);
		if(!in_array($section, array('sea_services', 'documents', 'details' /*, 'vacancies' */)))
			throw new CHttpException(404);

		echo json_encode(array(
			'status' => 'ok',
			'html' => $this->renderPartial('/_seaman_card/'.$section, array('seaman' => $seaman), true)
		));
	}

	/**
	 * отправляет список коментариев к контракту
	 *
	 * @param $seaman
	 */
	private function _getSsComments($seaman)
	{
		/** @var ModSeamanContract $contract */
		$contract = ModSeamanContract::model()->findByPk($_POST['contractId']);
		if($contract)
		{
			echo json_encode(array(
				'status' => 'ok',
				'html' => $this->renderPartial('/_seaman_card/sea_services/comments', array('seaman' => $seaman, 'contract' => $contract), true)
			));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Контракт не найден. Операция отменена.')));
	}

	/**
	 * добавляет комментарий и оценку к контракту
	 */
	private function _addComment()
	{
		/** @var ModSeamanContract $contract */
		$contract = ModSeamanContract::model()->findByPk($_POST['f_contract']);
		if($contract)
		{
			if(Yii::app()->authManager->hasAccessToSeaman($contract->seaman_id))
			{
				$rate = Rate::model()->findByAttributes(array('contract_id' => $contract->id, 'shipowner_id' => Yii::app()->user->parent_id));
				if(!$rate)
				{
					$rate = new Rate;
					$rate->contract_id = $contract->id;
					$rate->shipowner_id = Yii::app()->user->parent_id;
				}

				$rate->rate = intval($_POST['f_rank']);

				if($rate->save())
				{
					if(trim($_POST['f_text']) != '' || (is_array($_FILES['f_file']) && $_FILES['f_file']['error'] == 0 && $_FILES['f_file']['size'] > 0))
					{
						$comment = new RateComment();
						$comment->rate_id = $rate->id;
						$comment->from = trim($_POST['f_from']);
						$comment->comment = trim($_POST['f_text']);

						if(empty($comment->comment) || !empty($comment->from))
						{
							if(is_array($_FILES['f_file']))
							{ // загружаем файл
								if($comment->save())
								{
									$res = $comment->uploadFile('f_file');
									if($res === true)
									{
										echo json_encode(array(
											'status' => 'ok',
											'html' => $this->renderPartial('/_seaman_card/sea_services/comments', array('contract' => $contract), true),
										));
									}
									else
										echo json_encode(array('status' => $res));
								}
								else
									echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Не удалось сохранить комментарий.')));
							}
							else
							{
								if($comment->save())
								{
									echo json_encode(array(
										'status' => 'ok',
										'html' => $this->renderPartial('/_seaman_card/sea_services/comments', array('contract' => $contract), true)
									));
								}
								else
									echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Не удалось сохранить комментарий.')));
							}
						}
						else
							echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Вы должны указать человека, давшего оценку.')));
					}
					else
					{ // файл не загружали и комментарий не добавляли - только сигналим, что оценка сохранена
						echo json_encode(array('status' => 'ok', 'html' => ''));
					}
				}
				else
					echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Не удалось сохранить оценку.')));
			}
			else
				echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У вас нет доступа к данным выбранного моряка.')));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Контракт не найден. Операция отменена.')));
	}

	/**
	 * удаляет комментарий к контракту
	 */
	private function _deleteContractComment()
	{
		/** @var RateComment $comment */
		$comment = RateComment::model()->findByPk($_POST['commentId']);
		if($comment)
		{
			if(Yii::app()->authManager->hasAccessToSeaman($comment->rate->contract->seaman_id))
			{
				$comment->delete();
				echo json_encode(array(
					'status' => 'ok',
				));
			}
			else
				echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У Вас нет прав для выполнения удаления комментария.')));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Комментарий не найден.')));
	}

	/**
	 * Удаляем все комменты с контракта
	 *
	 * @param $seaman
	 */
	private function _deleteAllComments($seaman)
	{
		if(Yii::app()->authManager->hasAccessToSeaman($seaman->id))
		{
			/** @var Rate $rate */
			$rate = Rate::model()->findByAttributes(array('contract_id' => $_POST['contractId']));
			if($rate && $rate->rate != 0)
			{
				$comment = new RateComment;
				$comment->rate_id = $rate->id;
				$comment->from = Yii::t('seamanModule.ajax', 'Система');
				$comment->comment = Yii::t('seamanModule.ajax', 'Оценка ({rate}) и комментарии были удалены пользователем {userName}', array(
					'{rate}' => $rate->rate,
					'{userName}' => Yii::app()->user->getFullUsername()
				));
				$comment->save();

				$rate->rate = 0; // удаляем оценку
				$rate->save();

				RateComment::model()->updateAll(array('deleted' => 1), 'rate_id='.$rate->id);

				echo json_encode(array('status' => 'ok'));
			}
			else
				echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У выбранного контракта еще нет оценок и комментариев.')));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У Вас нет прав для удаления комментариев.')));
	}

	/**
	 * показывает заметки (notes) на карточке моряка
	 *
	 * @param $seaman
	 */
	private function _getNotes($seaman)
	{
		$notes = ModSeamenNotes::getNotes($seaman->id, true, false, 5);
		echo json_encode(array(
			'status' => 'ok',
			'html' => $this->renderPartial('/_seaman_card/summary/notes', array('notes' => $notes), true)
		));
	}

	/**
	 * отправляет форму получения доступа к моряку
	 *
	 * @param $seaman
	 */
	private function _getAccessForm($seaman)
	{
		if(!Yii::app()->authManager->hasAccessToSeaman($seaman->id))
		{ // предлагаем купить доступ
			echo json_encode(array(
				'status' => 'ok',
				'html' => $this->renderPartial('access_form', array(
					'seaman' => $seaman,
					'vacancyId' => intval($_POST['vacancyId']),
					'forceShowTempBtn' => $_POST['forceTempBtn']
				), true),
				'title' => Yii::t('seamanModule.ajax', 'Требуется профессиональный доступ к анкете')
			));

		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У Вас уже есть доступ к данным выбранного моряка. Перезагрузите страницу, чтобы увидеть скрытые данные.')));
	}

	/**
	 * получение доступа к моряку
	 *
	 * @param $seaman
	 */
	private function _getAccess($seaman)
	{
		/** @var ModShipowner $agent */
		$agent = ModShipowner::model()->findByPk(Yii::app()->user->parent_id);

		switch (trim($_POST['accessType']))
		{
			case 'full' :
				if(!Yii::app()->authManager->hasAccessToSeaman($seaman->id))
				{
					if($agent->membership_current != App::MEMBERSHIP_FREE)
					{
						$res = $agent->getAccessToSeaman($seaman->id);
						if($res === true)
							echo json_encode(array(
								'status' => 'ok',
								'message' => Yii::t('seamanModule.ajax', 'Теперь Вы можете видеть личные детали, оставлять комментарии, использовать ярлыки и другие профессиональные функции.'),
								'title' => Yii::t('seamanModule.ajax', 'Доступ к моряку получен'),
							));
						else
							echo json_encode(array(
								'status' => $res,
								'title' => Yii::t('seamanModule.ajax', 'Внимание'),
								'hideButtons' => true
							));
					}
					else
						echo json_encode(array(
							'title' => Yii::t('seamanModule.ajax', 'Внимание'),
							'status' => Yii::t('seamanModule.ajax', '<p>Данная операция недоступна на бесплатном абонементе.</p><p>Для выполнения этой операции Вам необходимо <a href="{url}">получить платный абонемент</a>.</p>', array(
								'{url}' => $this->createUrl('/site/static', array('page' => 'crewing_software'))
							)),
						));
				}
				else
					echo json_encode(array(
						'status' => Yii::t('seamanModule.ajax',
							'У Вас уже есть постоянный доступ к данному моряку. Перезагрузите страницу, чтобы увидеть скрытые данные.')));

				break;

			default:
				echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'Неверный тип доступа. Операция отменена.')));
		}
	}

	/**
	 * отправляет мыло моряка
	 *
	 * @param $seaman
	 */
	private function _getEmail($seaman)
	{
		if(Yii::app()->authManager->hasAccessToSeaman($seaman->id) || Yii::app()->authManager->isAnketaOwner($seaman->id))
		{
			echo json_encode(array(
				'status' => 'ok',
				'email' => $seaman->user->email
			));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У вас нет доступа к данным выбранного моряка. Операция отменена.')));
	}

	/**
	 * отправляет форму редактора тегов
	 *
	 * @param $seaman
	 */
	private function _getTagEditor($seaman)
	{
		if(Yii::app()->authManager->hasAccessToSeaman($seaman->id))
		{
			echo json_encode(array(
				'status' => 'ok',
				'html' => $this->renderPartial('tag_editor', array('seaman' => $seaman), true),
				'tags' => Yii::app()->db->createCommand("SELECT tag FROM tags WHERE shipowner_id = :shipownerId ORDER BY tag")->queryColumn(array(':shipownerId' => Yii::app()->user->parent_id))
			));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У вас нет доступа.')));
	}

	/**
	 * обновление списка тегов моряка
	 *
	 * @param $seaman
	 */
	private function _updateTags($seaman)
	{
		if(Yii::app()->authManager->hasAccessToSeaman($seaman->id))
		{
			/** @var ModSeaman $seaman */
			$seaman->setTags($_POST['tags']);

			$tags = $seaman->getTags();
			$html = '';
			for ($i = 0, $_c = count($tags); $i < $_c; $i++) $html .= '<span class="label label_3">'.$tags[$i].'</span> ';

			echo json_encode(array(
				'status' => 'ok',
				'html' => $html,
			));
		}
		else
			echo json_encode(array('status' => Yii::t('seamanModule.ajax', 'У вас нет доступа.')));
	}
}