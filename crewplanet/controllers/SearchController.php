<?php

class SearchController extends Controller
{
	/**
	 * Обработка запросов из строки поиска в главном меню
	 *
	 * @param string $query
	 *
	 * @throws CHttpException
	 */
	public function actionIndex($query)
	{
		if(!Yii::app()->user->checkAccess('allowSearchField'))
			throw new CHttpException(403);

		$query = trim(strip_tags($query));


		//Есть ли в тексте специальный префикс для запроса
		preg_match('/^(?P<type>vsl|crewing|vac):\s*?(?P<text>.+)$/m', $query, $query_parts);

		if(isset($query_parts['type']) and isset($query_parts['text']))
		{

			// поиск судна
			if($query_parts['type']=='vsl')
			{
				$this->searchVessel($query);
				return;
			}

			// поиск по названию круинга
			if($query_parts['type']=='crewing' && Yii::app()->user->checkAccess('roleCrewplanet'))
			{
				$this->searchCrewing($query_parts['text']);
				return;
			}

			// Поиск вакансий указанной компании
			if($query_parts['type']=='vac' && Yii::app()->user->checkAccess('roleCrewplanet'))
			{ // разрешено только для Crewplanet (Роман)
				$this->searchVacancies($query);
				return;
			}
		}


		//======================================================
		// далее идут различные варианты поиска моряков
		//======================================================

		$this->redirect(array('/seaman/categories/search', 'query' => $query));
	}

	/**
	 * Поиск вакансий, созданных указанным агентом
	 *
	 * @param string $query часть названия агента
	 */
	private function searchVacancies($query)
	{
		$filter = new VacFilter();
		$filter->restoreFromSession();
		$filter->company = $query = preg_replace('|^vac:\s*|', '', $query);

		$_ = $filter->attributes;
		unset($_['id'], $_['seaman_id'], $_['title'], $_['enable_notification']);

		$_gets = array();
		foreach ($_ as $k => $v) $_gets[] = urlencode('VacFilter['.$k.']').'='.urlencode($v);
		$this->redirect($this->createUrl('/vacancies/search/filter').'?'.implode('&', $_gets));
	}

	/**
	 * Поиск судна
	 *
	 * @param string $query часть названия судна
	 * @throws CHttpException
	 */
	private function searchVessel($query)
	{
		if(!Yii::app()->user->checkAccess('roleCrewplanet')) throw new CHttpException(403);

		//Готовим поисковую строку к вставке в запрос
		$condition = '';
		$query = preg_replace('|^vsl:\s*|', '', $query);
		$query = str_replace('*', '%', $query);
		$query = explode(',', $query);
		if(count($query) > 1)
		{
			foreach ($query as $value)
			{
				$condition .= 'OR vessel_name LIKE "'.trim($value).'" ';
			}
		}
		else $condition = 'vessel_name LIKE "'.$query[0].'"';
		$condition = preg_replace('|^OR |', '', $condition);

		//Ищем
		$contracts = Yii::app()->db->createCommand()
			->select('
				c.id, vessel_name, d1.name_eng AS flag, gross_tonnage, total_engine_power, d2.name_eng AS vsl_type,
				vessel_imo, s.id, s.PI_imja, s.PI_familija, d4.name_eng AS strana, d3.name_eng AS rank, position_display,
				contract_starts, contract_ends, cont_name, c.vessel_owner,cont_phone
			')
			->from('
				crewplanet._mod_seaman_contracts AS c, _mod_seaman AS s, _mod_predefined_data AS d1, _mod_predefined_data AS d2,
				_mod_predefined_data AS d3, _mod_predefined_data AS d4
			')
			->where("
				(".$condition.") AND
				c.seaman_id=s.id AND
				d1.id=c.vessel_type AND
				d2.id=c.vessel_flag AND
				d3.id=c.position AND
				d4.id=s.CI_strana"
			)
			->queryAll();

		//Готовим для cGridView
		$vessel_search_provider = new CArrayDataProvider($contracts, array(
			'keyField' => 'id',
			'sort' => array(
				'attributes' => array(
					'vessel_name', 'contract_starts', 'contract_ends'
				),
				'defaultOrder' => 'contract_starts DESC'
			),
			'pagination' => array(
				'pageSize' => 100,
			)
		));

		$this->render('vessel', array(
			'vessel_search_provider' => $vessel_search_provider,
		));
	}

	private function searchCrewing($searchtext)
	{
		//Готовим поисковую строку к вставке в запрос
		$searchtext = str_replace('*', '%', $searchtext);
		$searchtext = explode(',', $searchtext);

		$condition = '';
		if(count($searchtext) > 1)
		{
			foreach ($searchtext as $value)
			{
				$condition .= 'OR vessel_owner LIKE "'.trim($value).'" ';
			}
		}
		else $condition = 'vessel_owner LIKE "'.$searchtext[0].'"';
		$condition = preg_replace('|^OR |', '', $condition);

		//Ищем
		$contracts = Yii::app()->db->createCommand()
			->select('
				c.id, vessel_name, d1.name_eng AS flag, gross_tonnage, total_engine_power, d2.name_eng AS vsl_type,
				vessel_imo, s.id, s.PI_imja, s.PI_familija, d4.name_eng AS strana, d3.name_eng AS rank, position_display,
				contract_starts, contract_ends, cont_name, c.vessel_owner,cont_phone
			')
			->from('
				crewplanet._mod_seaman_contracts AS c, _mod_seaman AS s, _mod_predefined_data AS d1, _mod_predefined_data AS d2,
				_mod_predefined_data AS d3, _mod_predefined_data AS d4
			')
			->where("
				(".$condition.") AND
				c.seaman_id=s.id AND
				d1.id=c.vessel_type AND
				d2.id=c.vessel_flag AND
				d3.id=c.position AND
				d4.id=s.CI_strana"
			)
			->queryAll();

		//Готовим для cGridView
		$crewing_search_provider = new CArrayDataProvider($contracts, array(
			'keyField' => 'id',
			'sort' => array(
				'attributes' => array(
					'vessel_name', 'contract_starts', 'contract_ends'
				),
				'defaultOrder' => 'contract_starts DESC'
			),
			'pagination' => array(
				'pageSize' => 100,
			)
		));

		$this->render('vessel', array(
			'vessel_search_provider' => $crewing_search_provider,
		));
	}


	public function beforeAction($action)
	{
		if(!Yii::app()->user->checkAccess('allowSearchField'))
			throw new CHttpException(403);

		return parent::beforeAction($action);
	}
}