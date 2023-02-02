<?php

class CategoriesController extends Controller
{
	public function accessRules()
	{
		return array(
			array('allow',
				'users' => array('*'),
				'actions' => array('show', 'position', 'seaman')
			),
			array('deny', 'users' => array('?'))
		);
	}


	public function actionShow()
	{
		$this->render('show');
	}

	public function actionPosition($id=0, $page = 1, $accessFull = 0)
	{
		$pageSize = 10;

		Yii::import('SeamanCard');
		$card = new SeamanCard();
		if($id!=0)
		{ // переход из списка должностей - убиваем фильтр
			$card->clearFilter();
			$card->positionId = $id;
		}

		if($accessFull == 1)
		{ // переход из меню - принудительное отображение только тех моряков, к которым есть доступ
			$_POST['SeamanCard']['accessMode'] = SeamanCard::ACCESS_YES;
			$card->clearFilter();
			$card->positionId = 0;
			$card->processPost();
		}

		if(isset($_POST['cmd']))
		{
			if($_POST['cmd'] == 'submit')
			{
				$card->processPost();
				$page = 1;
			}
			elseif($_POST['cmd'] == 'clear')
			{
				$card->clearFilter();
				$page = 1;
			}
		}

		$pages = new CPagination($card->getCount());
		$pages->pageSize = $pageSize;
		$pages->currentPage = $page - 1;

		$list = $card->getAll($pages->offset, $pages->limit);

		$dp = new CArrayDataProvider($list);

		$this->render('position', array(
			'dp' => $dp,
			'positionId' => $id,
			'pages' => $pages,
			'card' => $card
		));
	}

	public function actionDownloadPdf($seamanId)
	{
		$pdfFilesPath = Yii::app()->params['paths']['pdfFilesPath'];

		/** @var ModSeaman $seaman */
		$seaman = ModSeaman::model()->findByPk($seamanId);
		if($seaman && (Yii::app()->authManager->hasAccessToSeaman($seaman->id) || Yii::app()->authManager->isAnketaOwner($seaman->id)))
		{
			$outFilename = preg_replace("/[^a-z0-9-]/i", "_", $seaman->PI_familija).'_-_CV_'.Yii::app()->user->id;

			$fullPath = $pdfFilesPath.DIRECTORY_SEPARATOR.$outFilename.'.pdf';
			if(file_exists($fullPath))
			{
				$headers = array(
					"Content-Disposition: attachment; filename=".preg_replace("/[^a-z0-9-]/i", "_", $seaman->PI_familija).'_-_CV_'.$seaman->id.'.pdf',
					"Content-Transfer-Encoding: binary",
					"Content-type: application/pdf",
					"Cache-Control: must-revalidate, post-check=0, pre-check=0",
					"Pragma: public");
				foreach ($headers as $header) header($header);
				readfile($fullPath);
			}
		}

		// зачистка папки
		$files = scandir($pdfFilesPath);
		foreach ($files as $filename)
		{
			if(preg_match('|\.pdf$|', $filename))
			{
				$stat = stat($pdfFilesPath.DIRECTORY_SEPARATOR.$filename);
				if(time() - $stat['ctime'] > 300)
					unlink($pdfFilesPath.DIRECTORY_SEPARATOR.$filename);
			}
		}

		Yii::app()->end();
	}

	/**
	 * Генерация PDF-файла анкеты моряка.
	 * Файлы складываются в папку Yii::app()->params['paths']['pdfFilesPath']
	 * test URL http://xxx.crewplanet.eu/v2/ru/seaman/categories/preparePdf/?includePersonalDetails=1&seamanId=12930
	 *
	 * @param int $seamanId
	 * @param bool $includePersonalDetails
	 */
	public function actionPreparePdf($seamanId, $includePersonalDetails = false)
	{
		Yii::app()->language = 'en'; // #350 Скачивание анкеты PDF всегда на английском

		$seamanId = intval($seamanId);
		$summary = SeamanCard::getOne($seamanId);
		if($summary && (Yii::app()->authManager->hasAccessToSeaman($summary['id']) || Yii::app()->authManager->isAnketaOwner($summary['id'])))
		{
			$html = $this->render('seaman_card_4_pdf', array(
				'data' => $summary,
				'seamanId' => $seamanId,
				'includePersonalDetails' => $includePersonalDetails
			), true);

			//-----------------------------------------
			//Заменяем ссылки на их текст
			$html = preg_replace('%<a .*?>(.+?)</a>%si', '$1', $html);

			//-----------------------------------------
			$pdfFilesPath = Yii::app()->params['paths']['pdfFilesPath'];
			if(!file_exists($pdfFilesPath))
			{
				mkdir($pdfFilesPath, 0777);
				chmod($pdfFilesPath, 0777);
			}

			$outFilename = preg_replace("/[^a-z0-9-]/i", "_", $summary['PI_familija']).'_-_CV_'.Yii::app()->user->id;

			@set_time_limit(10000);

            /* @var $mpdf mPDF */
            $mpdf = Yii::app()->ePdf->mpdf();
            $mpdf->SetDisplayMode('fullpage');
            $mpdf->WriteHTML($html);
            $mpdf->Output($pdfFilesPath . DIRECTORY_SEPARATOR . $outFilename . '.pdf', 'F');

			echo 'ok';
		}
		else
			echo Yii::t('seamanModule.card', 'У вас нет доступа к данным выбранного моряка.');

		Yii::app()->end();
	}

	private function protectXMLlite($variable)
	{
		return is_array($variable) ? array_map(array($this, "protectXMLlite"), $variable) : str_replace('&', '&amp;', $variable);
	}


	public function actionDownloadDocuments($seamanId)
	{
		/** @var ModSeaman $seaman */
		$seaman = ModSeaman::model()->with('files')->findByPk($seamanId);
		if($seaman && (Yii::app()->authManager->hasAccessToSeaman($seaman->id) || Yii::app()->authManager->isAnketaOwner($seaman->id)))
		{
			$zipFileName = preg_replace("/[^a-z0-9-]/i", "_", $seaman->PI_familija).'_documents.zip';
			$outPath = Yii::app()->params['paths']['zipTempFolderPath'];
			$fullZipPath = $outPath.DIRECTORY_SEPARATOR.$zipFileName;

			header('Content-type: application/zip');
			header('Content-Disposition: attachment; filename='.preg_replace("/[^a-z0-9-]/i", "_", $seaman->PI_familija).'_documents_'.$seaman->id.'.zip');
			echo file_get_contents($fullZipPath);
		}
		Yii::app()->end();
	}

	public function actionPrepareDocuments($seamanId)
	{
		/** @var ModSeaman $seaman */
		$seaman = ModSeaman::model()->with('files')->findByPk($seamanId);
		if($seaman && (Yii::app()->authManager->hasAccessToSeaman($seaman->id) || Yii::app()->authManager->isAnketaOwner($seaman->id)))
		{
			$zipFileName = preg_replace("/[^a-z0-9-]/i", "_", $seaman->PI_familija).'_documents.zip';
			$outPath = Yii::app()->params['paths']['zipTempFolderPath'];
			$fullZipPath = $outPath.DIRECTORY_SEPARATOR.$zipFileName;

			$zipArchive = new ZipArchive();

			if($zipArchive->open($fullZipPath, ZIPARCHIVE::OVERWRITE) === true)
			{
				$fileList = array();
				$files = $seaman->files;
				for ($i = 0, $_c = count($files); $i < $_c; $i++)
				{
					/** @var ModSeamanFiles $file */
					$file = $files[$i];
					if(file_exists(Yii::getRootPath().DIRECTORY_SEPARATOR.$file->filename))
					{
						preg_match('|_([^_]*)$|', $file->table_name, $type);

						switch ($type[1])
						{
							//-----------------------------------------
							case 'passports' :
								$_ = Yii::app()->db->createCommand("
									SELECT
										ms.*, c.title, mp.name_eng AS countryName
									FROM
										_mod_seaman_sertificates AS ms
										LEFT JOIN _mod_predefined_data AS mp ON ms.country_id = mp.id
										LEFT JOIN cl_certificates AS c ON ms.sert_id = c.id
									WHERE ms.id = :docId
								")->queryRow(true, array(':docId' => $file->doc_id));

								if($_)
								{
									$fileList[$_['countryName'].$_['title']][] = array(
										'fileName' => $file->filename,
										'country' => (($_['sert_id'] == 132) ? null : $_['countryName']),
										'type' => $_['title']
									);
								}
								break;

							//-----------------------------------------
							case 'contracts' :
								$fileList['contracts'][] = array(
									'fileName' => $file->filename,
									'type' => 'sea_service',
									'country' => null);
								break;

							//-----------------------------------------
							case 'sertificates' :
								$_ = Yii::app()->db->createCommand("
									SELECT c.title
									FROM
										_mod_seaman_sertificates AS ms
										LEFT JOIN cl_certificates AS c ON ms.sert_id = c.id
									WHERE ms.id =  :docId
								")->queryRow(true, array(':docId' => $file->doc_id));

								if($_)
								{
									$fileList[$_['title']][] = array(
										'fileName' => $file->filename,
										'type' => $_['title'],
										'country' => null);
								}
								break;

							//-----------------------------------------
							case 'competency' :

								$_ = Yii::app()->db->createCommand("
									SELECT
										ms.*, mp.name_eng AS country ,
										mpp.name_eng AS endorsment_country
									FROM
										_mod_seaman_competency AS ms
										LEFT JOIN _mod_predefined_data AS mp ON ms.strana_vidachi = mp.id
										LEFT JOIN _mod_predefined_data AS mpp ON ms.endorsment_strana_vidachi = mpp.id
									WHERE ms.id = :docId
								")->queryRow(true, array(':docId' => $file->doc_id));

								if($_)
								{
									$country = (($result['country']) ? $result['country'] : $result['endorsment_country']);
									$fileList[$country][] = array(
										'fileName' => $file->filename,
										'type' => 'coc_'.$country,
										'country' => null);
								}
								break;
						}
					}
				}

				if(count($fileList) > 0)
				{
					foreach ($fileList as $type)
					{
						$isCountable = false;
						if(count($type) > 1) $isCountable = true;
						$i = 0;
						foreach ($type as $f)
						{
							if(file_exists(Yii::getRootPath().DIRECTORY_SEPARATOR.$f['fileName']))
							{
								$i++;
								$parts = explode('.', $f['fileName']);
								$extention = end($parts);
								$fileName = $f['type'].(($f['country']) ? "_".$f['country'] : '').(($isCountable) ? '_'.$i : '').".".$extention;

								$zipArchive->addFile(Yii::getRootPath().DIRECTORY_SEPARATOR.$f['fileName'], App::fixFileName($fileName));
							}
						}
					}

					$zipArchive->close();
					echo 'ok';
				}
				else
					echo Yii::t('seamanModule.card', 'У моряка нет загруженных файлов.');
			}
			else
				echo Yii::t('seamanModule.card', 'Не удалось создать ZIP архив.');

		}
		else
			echo Yii::t('seamanModule.card', 'У вас нет доступа к данным выбранного моряка.');
		Yii::app()->end();
	}

	/**
	 * Показ карточки моряка (для не-хозяина анкеты), т.е. страница, которая отображается по crewplanet.eu/123456
	 *
	 * @param int $seamanId идентификатор моряка
	 *
	 * @throws CHttpException
	 */
	public function actionSeaman($seamanId)
	{
		$seaman = ModSeaman::model()->findByPk($seamanId);
		if(!$seaman) throw new CHttpException(404);

		$this->render('seaman', array('seaman' => $seaman));
	}

	/**
	 * Обработчик поиска через поле в главном меню
	 *
	 * @param string $query
	 */
	public function actionSearch($query)
	{
		$query = trim($query);

		if(!Yii::app()->user->checkAccess('allowSearchField'))
			throw new CHttpException(403);

		$maxResults = 100; // ОБЯЗАТЕЛЬНО ЦЕЛОЕ НАТУРАЛЬНОЕ ЧИСЛО!!!
		/** @var array $ids массив идентификаторов моряков, которых нужно отобрзить в результатах */
		$ids = array();
		$message = '';
		$error = '';

		if(preg_match('/^(tel:|\+|00)/', $query))
		{ // по номеру телефона, если начинается с tel: , + или 00
			$phone = preg_replace('/^tel:(\s*)?/', '', $query);
			// удаляем все, кроме цифр и 00 в начале
			$normalizePhoneChunk = preg_replace('/\D/', '', preg_replace('/^00/', '', $phone));
			$message = Yii::t('seamanModule.card', 'Ищем по номеру телефона {phone}', array('{phone}' => $phone));
			$ids = Yii::app()->db->createCommand("
				SELECT DISTINCT u.id
				FROM
					users as u
				LEFT JOIN _mod_seaman AS s ON s.id = u.id
				WHERE
					(replace(s.CI_telefon_1, ' ', '') LIKE :phone OR replace(s.CI_telefon_2, ' ', '') LIKE :phone)
					AND u.profile_type = 0
				ORDER BY u.last_hit DESC
				LIMIT 0, " . $maxResults)->queryColumn(array(':phone' => '%' . $normalizePhoneChunk . '%'));
		}
		elseif(preg_match('/^\d{1,6}$/', $query))
		{ // поиск моряка по ID
			$message = Yii::t('seamanModule.card', 'Ищем анкету с ID {id}', array('{id}' => intval($query)));
			$ids = array(intval($query));
		}
		elseif(preg_match('/\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b/', $query) && Yii::app()->user->checkAccess('roleCrewplanet'))
		{ //Если был введен IP адрес. Поиск моряка по IP
			$message = Yii::t('seamanModule.card', 'Ищем по IP {ip}', array('{ip}' => $query));
			$ids = Yii::app()->db->createCommand("
				SELECT id
				from users
				WHERE last_ip=:ip AND profile_type = 0
				ORDER BY last_hit DESC
				LIMIT 0, ".$maxResults)->queryColumn(array(':ip' => $query));
		}
		elseif(VacShip::isImo($query))
		{ // по IMO-номеру
			$message = Yii::t('seamanModule.card', 'Ищем по IMO {imo}', array('{imo}' => intval($query)));
			$ids = Yii::app()->db->createCommand("
				SELECT DISTINCT u.id
				FROM
					users as u,
					_mod_seaman_contracts as c
				WHERE
					c.vessel_imo=:imo
					AND c.seaman_id = u.id
					AND u.profile_type = 0
				ORDER BY u.last_hit DESC
				LIMIT 0, ".$maxResults)->queryColumn(array(':imo' => $query));
		}
		elseif(preg_match('|.@.|', $query))
		{ // по мылу
			$message = Yii::t('seamanModule.card', 'Ищем по адресу электронной почты {email}', array('{email}' => $query));
			$ids = Yii::app()->db->createCommand("
				SELECT id
				FROM users
				WHERE email LIKE :email AND profile_type = 0
				ORDER BY last_hit DESC
				LIMIT 0, ".$maxResults)->queryColumn(array(':email' => '%'.$query.'%'));
		}
		else
		{ //Обычный текстовой поиск
			$sc = SeamanCard::getIdsByText($query, $maxResults);
			$ids = $sc['ids'];
			$message = $sc['message'];
			$error = $sc['error'];
		}

		//-----------------------------------------
		// собираем данные и строим карточки

		$this->render('search', array('ids' => $ids, 'message' => $message, 'error' => $error));
	}

	/**
	 * Отправляет юзеру файл, прикрепленный к комментарию контракта (оценка работы).
	 *
	 * @param int $commentId
	 * @throws CHttpException
	 */
	public function actionGetCommentFile($commentId)
	{
		/** @var $comment RateComment */
		$comment = RateComment::model()->with('rate', 'rate.contract')->findByPk($commentId);
		$filePath = $comment->getCommentFilePath();

		if(!$comment || !file_exists($filePath)) throw new CHttpException(404);

		if(!Yii::app()->authManager->hasAccessToSeaman($comment->rate->contract->seaman_id))
			throw new CHttpException(403);

		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.$comment->filename);
		echo file_get_contents($filePath);
		Yii::app()->end();
	}
}
