<?php

/**
 * @var $this Controller
 * @var $seaman ModSeaman
 * @var $user User
 */

/**
 * @var $form TbActiveForm
 */

$this->pageTitle = Yii::t('seamanModule.register', 'Регистрация моряка');

$assets = Yii::app()->getAssetManager()->publish($this->module->getBasePath().'/assets', false, -1, defined('YII_DEBUG'));
$cs = Yii::app()->getClientScript();

$cs->registerCssFile($assets.'/profileRegister.css');
$cs->registerScriptFile($assets.'/profileRegister.js');

$cs->registerScript('profileRegister#airports', 'profileRegister.airports = '.CJavaScript::jsonEncode(ModAirport::getAllAirportsArray()).';');

$cs->registerScriptFile($assets.'/seamanregister_inspectlet.js');

$this->widget('ext.widgets.select2.Select2', array(
	'selector' => 'select',
));

$this->widget('ext.widgets.select2.Select2', array(
	'selector' => '#ModSeaman_airPortCountry',
	'events' => array(
		'select2-selecting' => new CJavaScriptExpression('profileRegister.handler_AirportCountryChanged')
	)
));

// инициализацию скриптов проводим ПОСЛЕ создания виджетов Select2.
$cs->registerScript('profileRegister#init', 'profileRegister.init();');


//===============================================

$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'id' => 'registerForm',
	'type' => 'horizontal',
	'htmlOptions' => array(),
	'enableClientValidation' => true,
	'enableAjaxValidation' => true
));

echo CHtml::tag('div', array('class' => 'row-fluid'),
	CHtml::tag('div', array('class' => 'span4'), $user->getAttributeLabel('email')).
	CHtml::tag('div', array('class' => 'span4'), $form->textField($user, 'email', array(
			'placeholder' => $user->getAttributeLabel('email'),
			'class' => 'span12',
		))._err($form, $user, 'email')).
	CHtml::tag('div', array('class' => 'span4'), $form->textField($user, 'email2', array(
			'placeholder' => $user->getAttributeLabel('email2'),
			'class' => 'span12',
		))._err($form, $user, 'email2'))
);

echo '<hr/>';

echo CHtml::tag('div', array('class' => 'row-fluid'),
	CHtml::tag('div', array('class' => 'span4'), Yii::t('seamanModule.register', 'Пароль (не менее 5 символов)')).
	CHtml::tag('div', array('class' => 'span4'), $form->passwordField($user, 'password', array(
			'placeholder' => $user->getAttributeLabel('password'),
			'class' => 'span12',
		))._err($form, $user, 'password')).
	CHtml::tag('div', array('class' => 'span4'), $form->passwordField($user, 'password2', array(
			'placeholder' => $user->getAttributeLabel('password2'),
			'class' => 'span12',
		))._err($form, $user, 'password2'))
);

echo '<hr/>';

echo CHtml::tag('div', array('class' => 'row-fluid'),
	CHtml::tag('div', array('class' => 'span4'),
		Yii::t('seamanModule.register', 'Данные по книжке моряка (на английском)').' '.
		CHtml::link('?', '#', array(
			'class' => 'badge badge-info',
			'rel' => 'popover',
			'placong' => 'right',
			'data-title' => Yii::t('seamanModule.register', 'Имя и фамилия'),
			'data-content' => Yii::t('seamanModule.register', 'Имя и фамилия может быть использована при дальнейшем оформлении'),
			'onclick' => 'return false;'
		))
	).
	CHtml::tag('div', array('class' => 'span4'), $form->textField($seaman, 'PI_imja', array(
			'placeholder' => $seaman->getAttributeLabel('PI_imja'),
			'class' => 'span12',
		))._err($form, $seaman, 'PI_imja')).
	CHtml::tag('div', array('class' => 'span4'), $form->textField($seaman, 'PI_familija', array(
			'placeholder' => $seaman->getAttributeLabel('PI_familija'),
			'class' => 'span12',
		))._err($form, $seaman, 'PI_familija'))
);

echo '<hr/>';

echo CHtml::tag('div', array('class' => 'row-fluid'),
	CHtml::tag('div', array('class' => 'span4'), Yii::t('seamanModule.register', 'День рождения (ДД.ММ.ГГГГ)')).
	CHtml::tag('div', array('class' => 'span4'), $form->textField($seaman, 'humanBirthDate', array(
//		'placeholder' => $seaman->getAttributeLabel('humanBirthDate'),
			'class' => 'span6',
		))._err($form, $seaman, 'humanBirthDate'))
);

echo '<hr/>';

echo CHtml::tag('div', array('class' => 'row-fluid'),
	CHtml::tag('div', array('class' => 'span4'), Yii::t('seamanModule.register', 'Как с Вами связаться?<div><small>Всегда указывайте международный код. Например +7 812 1234567</small></div>')).
	CHtml::tag('div', array('class' => 'span8'),
		CHtml::tag('div', array('class' => 'row-fluid with_margin_bottom'),
			CHtml::tag('div', array('class' => 'span6'),
				$form->textField($seaman, 'CI_telefon_1', array(
					'placeholder' => $seaman->getAttributeLabel('CI_telefon_1'),
					'class' => 'span12',
				))._err($form, $seaman, 'CI_telefon_1')
			).
			CHtml::tag('div', array('class' => 'span6'),
				CHtml::tag('span', array('class' => 'phone_span'),
					CHtml::tag('input', array(
							'id' => 'rg_phone_primary_type_0',
							'name' => 'ModSeaman[rg_phone_primary_type]',
							'value' => 1,
							'type' => 'radio',
							'checked' => $seaman->rg_phone_primary_type == 1 ? 'checked' : ''
						)
					).
					CHtml::label(Yii::t('seamanModule.register', 'мобильный'), 'rg_phone_primary_type_0').

					CHtml::tag('input', array(
							'id' => 'rg_phone_primary_type_1',
							'name' => 'ModSeaman[rg_phone_primary_type]',
							'value' => 0,
							'type' => 'radio',
							'checked' => $seaman->rg_phone_primary_type == 0 ? 'checked' : ''
						)
					).
					CHtml::label(Yii::t('seamanModule.register', 'домашний'), 'rg_phone_primary_type_1').

					CHtml::tag('input', array(
							'id' => 'rg_phone_primary_type_2',
							'name' => 'ModSeaman[rg_phone_primary_type]',
							'value' => 2,
							'type' => 'radio',
							'checked' => $seaman->rg_phone_primary_type == 2 ? 'checked' : ''
						)
					).
					CHtml::label(Yii::t('seamanModule.register', 'другой'), 'rg_phone_primary_type_2')
				)
			)
		).
		CHtml::tag('div', array('class' => 'row-fluid'),
			CHtml::tag('div', array('class' => 'span6'),
				$form->textField($seaman, 'CI_telefon_2', array(
					'placeholder' => $seaman->getAttributeLabel('CI_telefon_2'),
					'class' => 'span12',
				))
			).
			CHtml::tag('div', array('class' => 'span6'),
				CHtml::tag('span', array('class' => 'phone_span'),
					CHtml::tag('input', array(
							'id' => 'rg_phone_secondary_type_0',
							'name' => 'ModSeaman[rg_phone_secondary_type]',
							'value' => 1,
							'type' => 'radio',
							'checked' => $seaman->rg_phone_secondary_type == 1 ? 'checked' : ''
						)
					).
					CHtml::label(Yii::t('seamanModule.register', 'мобильный'), 'rg_phone_secondary_type_0').

					CHtml::tag('input', array(
							'id' => 'rg_phone_secondary_type_1',
							'name' => 'ModSeaman[rg_phone_secondary_type]',
							'value' => 0,
							'type' => 'radio',
							'checked' => $seaman->rg_phone_secondary_type == 0 ? 'checked' : ''
						)
					).
					CHtml::label(Yii::t('seamanModule.register', 'домашний'), 'rg_phone_secondary_type_1').

					CHtml::tag('input', array(
							'id' => 'rg_phone_secondary_type_2',
							'name' => 'ModSeaman[rg_phone_secondary_type]',
							'value' => 2,
							'type' => 'radio',
							'checked' => $seaman->rg_phone_secondary_type == 2 ? 'checked' : ''
						)
					).
					CHtml::label(Yii::t('seamanModule.register', 'другой'), 'rg_phone_secondary_type_2')
				)
			)
		)
	)
);

echo '<hr/>';

$countries = ModPredefinedData::model()->getCountriesForDropdown(null, false, false);
$countriesWithNot = ModPredefinedData::model()->getCountriesForDropdown();

echo CHtml::tag('div', array('class' => 'row-fluid'),
	CHtml::tag('div', array('class' => 'span4'),
		$seaman->getAttributeLabel('PI_grazhdanstvo_strana').' '.
		CHtml::link('?', '#', array(
			'class' => 'badge badge-info',
			'rel' => 'popover',
			'placing' => 'right',
			'data-content' => Yii::t('seamanModule.register', 'Вид на жительство выдается, если, например,<br/><br/>1. у Вас нет гражданства<br/>2. Вы - иностранец, проживающий в чужой стране<br/><br/>В остальных случаях вид на жительство указывать не надо.'),
			'data-title' => Yii::t('seamanModule.register', 'Данные о гражданстве'),
			'onclick' => 'return false;'
		))
	).
	CHtml::tag('div', array('class' => 'span4'),
		$form->dropDownList($seaman, 'PI_grazhdanstvo_strana', $countriesWithNot,
			array(
				'class' => 'span12',
				'placeholder' => $seaman->getAttributeLabel('PI_grazhdanstvo_strana'),
				'empty' => '' // чтобы заработали плейсхолдеры
			)
		)._err($form, $seaman, 'PI_grazhdanstvo_strana')
	).
	CHtml::tag('div', array('class' => 'span4'),
		$form->dropDownList($seaman, 'PI_postojannij_zhitelj', $countriesWithNot,
			array(
				'class' => 'span12',
				'placeholder' => $seaman->getAttributeLabel('PI_postojannij_zhitelj'),
				'empty' => '' // чтобы заработали плейсхолдеры
			)
		)._err($form, $seaman, 'PI_postojannij_zhitelj')
	)
);

//======================================================
// Аэропорт
//======================================================

echo '<hr/>';

$airPorts = ModAirport::getAirportsOfCountryForDropdown($seaman->airPortCountry);

echo CHtml::tag('div', array('class' => 'row-fluid'),
	CHtml::tag('div', array('class' => 'span4'), Yii::t('seamanModule.register', 'Ближайший аэропорт')).
	CHtml::tag('div', array('class' => 'span4'),
		$form->dropDownList($seaman, 'airPortCountry', $countries,
			array(
				'class' => 'span12',
				'placeholder' => $seaman->getAttributeLabel('airPortCountry'),
				'empty' => '' // чтобы заработали плейсхолдеры
			)
		)._err($form, $seaman, 'airPortCountry')
	).
	CHtml::tag('div', array('class' => 'span4', 'id'=>'airport_span'),
		$form->dropDownList($seaman, 'AIR_port', $airPorts,
			array(
				'class' => 'span12',
				'style' => $seaman->airPortCountry > 0 ? '': 'display: none',
				'placeholder' => $seaman->getAttributeLabel('AIR_port'),
				'empty' => '' // чтобы заработали плейсхолдеры
			))._err($form, $seaman, 'AIR_port')
	)
);



//======================================================
// Язык
//======================================================


echo '<hr/>';

$langLevel = array(
	"13" => Yii::t('seamanModule.register', "Знаю отдельные слова, опыта в общении по работе нет или мало"),
	"27" => Yii::t('seamanModule.register', "Улавливаю суть вопроса, говорю простыми предложениями"),
	"28" => Yii::t('seamanModule.register', "Понимаю вопросы, легко изъясняюсь, делаю немного ошибок"),
	"29" => Yii::t('seamanModule.register', "Все понимаю, даже специальные темы, поддерживаю разговор"),
	"30" => Yii::t('seamanModule.register', "Абослютно свободно общаюсь, различаю и понимаю акценты")
);


echo CHtml::tag('div', array('class' => 'row-fluid'),
	CHtml::tag('div', array('class' => 'span4'), Yii::t('seamanModule.register', 'Уровень знания английского')).
	CHtml::tag('div', array('class' => 'span8'),
		$form->dropDownList($seaman, 'EI_jazik_anglijskij', $langLevel,
			array(
				'class' => 'span12',
				'placeholder' => $seaman->getAttributeLabel('EI_jazik_anglijskij'),
				'empty' => '' // чтобы заработали плейсхолдеры
			)
		)._err($form, $seaman, 'EI_jazik_anglijskij')
	)
);

echo '<hr/>';

echo CHtml::tag('div', array('class' => 'form-actions'),
	CHtml::submitButton(Yii::t('seamanModule.register', 'Зарегистрироваться и перейти к анкете'), array(
		'class' => 'btn btn-primary'
	))
);


$this->endWidget();

//======================================================
// Дополнительные функции
//======================================================

/**
 * @param CActiveForm $form
 * @param CActiveRecord $model
 * @param string $field
 * @return string
 */
function _err($form, $model, $field)
{
	$form->error($model, $field); // НЕ ВЫВОДИМ РЕЗУЛЬТАТ, а только вызываем метод, чтобы форма зарегистрировала обрабатываемое аяксом поле

	return CHtml::tag('p', array(
		'class' => 'error help-block',
		'id'=>CHtml::activeId($model,$field).'_em_',
		'style' => $model->hasErrors($field) ? '' : 'display:none;'
	), $model->getError($field));
}

//======================================================
// виджеты
//======================================================

