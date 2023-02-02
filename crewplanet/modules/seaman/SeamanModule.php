<?php

class SeamanModule extends CWebModule
{
	public function init()
	{
		$this->setImport(array(
			'seaman.models.*',
		));
        $this->setComponents(array(
            'documentApi' => array(
                'class' => 'seaman.components.DocumentApi',
            ),
        ));
	}

	public function beforeControllerAction($controller, $action)
	{
		if(parent::beforeControllerAction($controller, $action))
		{
			// включаем бустер для всех, кроме генератора PDF
			if($controller->id != 'categories' || $action->id != 'preparePdf') Yii::app()->getComponent('bootstrap');

			// задаем внешний вид
			$controller->layout = '//layouts/new_design_opendoc';

			return true;
		}
		else
			return false;
	}
}
