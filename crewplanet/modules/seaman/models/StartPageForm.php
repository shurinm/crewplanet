<?php

class StartPageForm extends CFormModel
{
	public $dor;
	public $salary;
	public $salaryCurrency;

	public function initBySeaman($seamanId)
	{
		/** @var ModSeaman $seaman */
		$seaman = ModSeaman::model()->findByPk($seamanId);
		if($seaman)
		{
			$_ = App::dbDateToTS($seaman->estimated_ready);
			$this->dor = $_ !== false ? App::tsToHuman($_) : ''; // чтобы пустая дата не выдавалась как 01.01.1970

			$this->salary = intval($seaman->estimated_salary);
			$this->salaryCurrency = substr($seaman->estimated_salary, -3);
		}
	}

	public function rules()
	{
		return array(
			array('dor', 'required', 'message'=>Yii::t('seamanModule.startPage', 'Введите значение')),

			array('salaryCurrency', 'in', 'range' => array(
				App::CURRENCY_EUR,
				App::CURRENCY_USD,
			)),

			array('salary', 'numerical', 'integerOnly' => true, 'min'=>0),
			array('dor', '_validateDor'),
		);
	}

	public function _validateDor($a, $b)
	{
		$ts = App::humanToTS($this->dor);
		if($ts === false)
			$this->addError('dor', Yii::t('seamanModule.startPage', 'Введите дату в формате ДД.ММ.ГГГГ'));
		else
			if($ts < time())
				$this->addError('dor', Yii::t('seamanModule.startPage', 'Дата готовности не может быть в прошлом.'));
			else
				$this->dor = App::tsToHuman($ts);
	}

	public function attributeLabels()
	{
		return array(
			'dor' => Yii::t('seamanModule.startPage', 'Дата готовности к контракту примерно'),
			'salary' => Yii::t('seamanModule.startPage', 'Желаемая зарплата не менее'),
			'salaryCurrency' => Yii::t('seamanModule.startPage', 'Валюта'),
		);
	}
}