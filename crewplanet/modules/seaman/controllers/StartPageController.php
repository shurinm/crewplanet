<?php

class StartPageController extends Controller
{
	public function accessRules()
	{
		return array(
			array('allow',
				'users' => array('*'),
				'actions' => array('dummy')
			),
			array('deny', 'users' => array('?'))
		);
	}

	public function actionShow()
	{
		if(!Yii::app()->authManager->isSeaman())
			throw new CHttpException(403);

		$model = new StartPageForm();
		$model->initBySeaman(Yii::app()->user->id);

		if(isset($_POST['submit']))
		{
			$model->attributes = $_POST['StartPageForm'];
			if($model->validate())
			{
				/** @var ModSeaman $seaman */
				$seaman = ModSeaman::model()->findByPk(Yii::app()->user->id);
				$seaman->estimated_ready = App::tsToDbDate(App::humanToTS($model->dor));
				$seaman->estimated_salary = $model->salary.'-'.$model->salaryCurrency;
				$seaman->save(true, array('estimated_ready', 'estimated_salary'));

				Yii::app()->user->setFlash('success', Yii::t('seamanModule.startPage', 'Данные успешно сохранены.'));
				$this->refresh();
			}
			else
				Yii::app()->user->setFlash('error', Yii::t('seamanModule.startPage', 'Обнаружены ошибки. Операция отменена.'));
		}

		$this->render('start_page', array('model'=>$model));
	}

}