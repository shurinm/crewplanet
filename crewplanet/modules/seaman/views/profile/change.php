<?php

/**
 * @var $this  Controller
 * @var $model ModSeaman
 */

$this->pageTitle = Yii::t('seamanModule.personal_details', 'Редактирование профиля моряка ID:{id}', array('{id}' => $model->id));

$cs = Yii::app()->clientScript;
$cs->registerCoreScript('jquery');
$this->widget('ext.widgets.uikit.uikit');

//-----------------------------------------

if($model->hasErrors())
{
	echo CHtml::tag('div', array('class'=>'alert alert-danger'), CHtml::errorSummary($model));
}

/**
 * @var $form TbActiveForm
 */

$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'id' => 'profileEditForm',
	'type' => 'horizontal',
	'htmlOptions' => array()
));

?>
	<style type="text/css">
		.row-fluid{
			margin-bottom: 8px;
		}

		hr{
			margin: 6px 0;
		}

		input[type="text"]{
			width: 198px;
		}

		.select2-container{
			width: 220px;
		}

		label > .label { margin-left: 10px; }
		div > .label { margin-top: 10px; display: inline-block; }

		.popover-content p { margin: 8px 0; }
	</style>

	<div class="row-fluid">
		<div class="span3">
			<?php
			echo _drawCustomLabel(Yii::t('seamanModule.personal_details', 'Полное имя').
				CHtml::link('?', '#', array(
						'rel' => 'popover',
						'data-title' => Yii::t('seamanModule.personal_details', '[заголовок тултипа про имя]'),
						'data-content' => Yii::t('seamanModule.personal_details', '[содержимое тултипа про имя]'),
						'class' => 'label',
						'onclick' => 'return false;'
					)
				));
			?>
		</div>
		<div class="span3"><?php echo _drawTextField($model, 'PI_imja', array('placeholder' => Yii::t('seamanModule.personal_details', 'Имя'))); ?></div>
		<div class="span3"><?php echo _drawTextField($model, 'PI_familija', array('placeholder' => Yii::t('seamanModule.personal_details', 'Фамилия'))); ?></div>
		<div class="span3"></div>
	</div>

	<hr> <!-- ======================================= -->

	<div class="row-fluid">
		<div class="span3"><?php echo _drawCustomLabel(Yii::t('seamanModule.personal_details', 'Данные о рождении')); ?></div>
		<div class="span3"><?php echo _drawDropDown($model, 'PI_rozhdenije_strana', ModPredefinedData::model()->getCountriesForDropdown(), array('placeholder' => Yii::t('seamanModule.personal_details', 'Страна'))); ?></div>
		<div class="span3"><?php echo _drawTextField($model, 'PI_rozhdenije_gorod', array('placeholder' => Yii::t('seamanModule.personal_details', 'Город'))); ?></div>
		<div class="span3"><?php echo _drawTextField($model, 'PI_rozhdenije_data', array('placeholder' => Yii::t('seamanModule.personal_details', 'Дата рождения'))); ?></div>
	</div>

	<hr> <!-- ======================================= -->

	<div class="row-fluid">
		<div class="span3"><?php echo _drawCustomLabel(Yii::t('seamanModule.personal_details', 'Гражданство')); ?></div>
		<div class="span3">
			<?php echo _drawDropDown($model, 'PI_grazhdanstvo_strana', ModPredefinedData::model()->getCountriesForDropdown(), array('placeholder' => Yii::t('seamanModule.personal_details', 'Гражданство'))); ?>
		</div>
		<div class="span3">
			<?php echo _drawDropDown($model, 'PI_postojannij_zhitelj', ModPredefinedData::model()->getCountriesForDropdown(), array('placeholder' => Yii::t('seamanModule.personal_details', 'Постоянно проживает'))); ?>
		</div>
		<div class="span3">
			<?php
			echo CHtml::link('?', '#', array(
				'rel' => 'popover',
				'data-title' => Yii::t('seamanModule.personal_details', '[заголовок тултипа про вид на жительство]'),
				'data-content' => Yii::t('seamanModule.personal_details', '[содержимое тултипа про вид на жительство]'),
				'data-placement' => 'bottom',
				'class' => 'label',
				'onclick' => 'return false;'
			))
			?>
		</div>
	</div>

	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span3"><?php echo _drawTextField($model, 'PI_personalnij_kod', array('placeholder' => Yii::t('seamanModule.personal_details', 'Персональный код'))); ?></div>
		<div class="span3"></div>
		<div class="span3"></div>
	</div>

	<hr> <!-- ======================================= -->

	<div class="row-fluid">
		<div class="span3"><?php echo _drawCustomLabel(Yii::t('seamanModule.personal_details', 'Адрес проживания')); ?></div>
		<div class="span3">
			<?php echo _drawDropDown($model, 'CI_strana', ModPredefinedData::model()->getCountriesForDropdown(), array('placeholder' => Yii::t('seamanModule.personal_details', 'Страна'))); ?>
		</div>
		<div class="span3"><?php echo _drawTextField($model, 'CI_shtat', array('placeholder' => Yii::t('seamanModule.personal_details', 'Область/регион'))); ?></div>
		<div class="span3"><?php echo _drawTextField($model, 'CI_gorod', array('placeholder' => Yii::t('seamanModule.personal_details', 'Город'))); ?></div>
	</div>

	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span3"></div>
		<div class="span3"><?php echo _drawTextField($model, 'CI_indeks', array('placeholder' => Yii::t('seamanModule.personal_details', 'Почтовый индекс'))); ?></div>
		<div class="span3"><?php echo _drawTextField($model, 'CI_adres', array('placeholder' => Yii::t('seamanModule.personal_details', 'Адрес'))); ?></div>
	</div>

	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span3"><?php echo _drawTextField($model, 'CI_telefon_1', array('placeholder' => Yii::t('seamanModule.personal_details', 'Номер телефона'))); ?></div>
		<div class="span6"><?php
			echo _drawRadioGroupSimple('CI_telefon_type1', $model->CI_telefon_types{0}, array(
				0 => Yii::t('seamanModule.personal_details', 'Домашний'),
				1 => Yii::t('seamanModule.personal_details', 'Мобильный'),
				2 => Yii::t('seamanModule.personal_details', 'Другой')
			)); ?></div>
	</div>

	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span3"><?php echo _drawTextField($model, 'CI_telefon_2', array('placeholder' => Yii::t('seamanModule.personal_details', 'Номер телефона'))); ?></div>
		<div class="span6">
			<?php
			echo _drawRadioGroupSimple('CI_telefon_type2', $model->CI_telefon_types{1}, array(
				0 => Yii::t('seamanModule.personal_details', 'Домашний'),
				1 => Yii::t('seamanModule.personal_details', 'Мобильный'),
				2 => Yii::t('seamanModule.personal_details', 'Другой')
			)); ?></div>
	</div>

	<hr> <!-- ======================================= -->

	<div class="row-fluid">
		<div class="span3"><?php echo _drawCustomLabel(Yii::t('seamanModule.personal_details', 'Ближайший аэропорт')); ?></div>
		<div class="span3">
			<?php

			/** @var ModAirport $airport */
			$airport = ModAirport::model()->findByPk($model->AIR_port);
			$airCountry = $airport ? $airport->country : 0;
			echo _drawDropDownSimple('AIR_country', $airCountry, ModPredefinedData::model()->getCountriesForDropdown(),
				array(
					'id' => 'AIR_country',
					'placeholder'=>Yii::t('seamanModule.personal_details', 'Страна'),
					'onchange' => "_airportCountryChanged();"
				));
			?>
		</div>
		<div class="span3">
			<?php echo _drawDropDown($model, 'AIR_port', ModAirport::getAirportsOfCountryForDropdown($airCountry), array('placeholder' => Yii::t('seamanModule.personal_details', 'Выберите аэропорт'))); ?>
		</div>
		<div class="span3"></div>

	</div>

	<script type="text/javascript">
		function _airportCountryChanged(){
			var countryId = $('#AIR_country').val();
			jQuery.ajax({
				type: 'POST',
				url: '<?php echo $this->createAbsoluteUrl('/seaman/profile/ajax'); ?>',
				data: {cmd: 'getAirPorts', countryId: countryId },
				dataType: 'json',
				success: function(respond)
				{
					if(respond.status == 'ok')
					{
						var obj = $('#ModSeaman_AIR_port').select2("destroy");
						var div = $(obj.parents('div')[0]);
						obj.remove();
						var newObj = $(respond.html).appendTo(div);
						newObj.select2();
					}
				}
			});
		}
	</script>


	<hr> <!-- ======================================= -->

	<div class="row-fluid">
		<div class="span3"><?php echo _drawCustomLabel(Yii::t('seamanModule.personal_details', 'Ближайший родственник')); ?></div>
		<div class="span3">
			<?php
			echo _drawDropDown($model, 'RI_tip_svjazi', ModPredefinedData::model()->getRelationsForDropdown(), array('placeholder' => Yii::t('seamanModule.personal_details', 'Тип связи')));
			?>
		</div>
		<div class="span3"></div>
		<div class="span3"></div>
	</div>

	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span3"><?php echo _drawTextField($model, 'RI_imja', array('placeholder' => Yii::t('seamanModule.personal_details', 'Имя'))); ?></div>
		<div class="span3"><?php echo _drawTextField($model, 'RI_familija', array('placeholder' => Yii::t('seamanModule.personal_details', 'Фамилия'))); ?></div>
		<div class="span3"></div>
	</div>

	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span3"><?php echo _drawTextField($model, 'RI_adres', array('placeholder' => Yii::t('seamanModule.personal_details', 'Адрес'))); ?></div>
		<div class="span3"><?php echo _drawTextField($model, 'RI_telefon', array('placeholder' => Yii::t('seamanModule.personal_details', 'Телефон'))); ?></div>
		<div class="span3"></div>
	</div>

	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span3">
			<?php
				echo CHtml::link(Yii::t('seamanModule.personal_details', 'Совпадает с адресом проживания'), '#', array(
					'class' => 'btn btn-small',
					'onclick' => "$('#ModSeaman_RI_adres').val('The same');return false;"
				))
			?>
			</div>
		<div class="span3"></div>
		<div class="span3"></div>
	</div>


	<hr> <!-- ======================================= -->

	<div class="row-fluid">
		<div class="span3"><?php echo _drawCustomLabel(Yii::t('seamanModule.personal_details', 'Биометрические данные')); ?></div>
		<div class="span3">
			<?php
			$_list = array(0 => Yii::t('seamanModule.personal_details', 'Рост'));
			for ($i = 150; $i <= 205; $i++) $_list[$i] = $i.' cm';
			echo _drawDropDown($model, 'PI_rost', $_list, array('placeholder' => Yii::t('seamanModule.personal_details', 'Рост')));
			?>
		</div>
		<div class="span3">
			<?php
			$_list = array(0 => Yii::t('seamanModule.personal_details', 'Вес'));
			for ($i = 40; $i <= 195; $i++) $_list[$i] = $i.' kg';
			echo _drawDropDown($model, 'PI_ves', $_list, array('placeholder' => Yii::t('seamanModule.personal_details', 'Вес')));
			?>
		</div>
		<div class="span3"></div>
	</div>

	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span3">
			<?php
			$_list = array(
				0 => Yii::t('seamanModule.personal_details', 'Цвет волос'),
				"blonde" => Yii::t('seamanModule.personal_details', 'Блондин / русые'),
				"red" => Yii::t('seamanModule.personal_details', 'Рыжие'),
				"brown" => Yii::t('seamanModule.personal_details', 'Шатен / коричневые'),
				"brunet" => Yii::t('seamanModule.personal_details', 'Брюнет / черные'),
				"grey" => Yii::t('seamanModule.personal_details', 'Седые'),
				"bold" => Yii::t('seamanModule.personal_details', 'Нет')
			);
			echo _drawDropDown($model, 'PI_cvet_volos', $_list, array('placeholder' => Yii::t('seamanModule.personal_details', 'Цвет волос')));
			?>
		</div>
		<div class="span3">
			<?php
			$_list = array(
				0 => Yii::t('seamanModule.personal_details', 'Цвет глаз'),
				"blue" => Yii::t('seamanModule.personal_details', 'Голубые'),
				"green" => Yii::t('seamanModule.personal_details', 'Зеленые'),
				"grey" => Yii::t('seamanModule.personal_details', 'Серые'),
				"brown" => Yii::t('seamanModule.personal_details', 'Коричневые')
			);
			echo _drawDropDown($model, 'PI_cvet_glaz', $_list, array('placeholder' => Yii::t('seamanModule.personal_details', 'Цвет глаз')));
			?>
		</div>
		<div class="span3"></div>
	</div>

	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span3">
			<?php
			$_list = array(0 => Yii::t('seamanModule.personal_details', 'Размер обуви'));
			for ($i = 35; $i <= 48; $i++) $_list[$i] = $i;
			echo _drawDropDown($model, 'PI_botinki', $_list, array('placeholder' => Yii::t('seamanModule.personal_details', 'Размер обуви')));
			?>
		</div>
		<div class="span3">
			<?php
			$_list = array(0 => Yii::t('seamanModule.personal_details', 'Размер робы'));
			for ($i = 40; $i <= 195; $i++) $_list[$i] = $i;
			echo _drawDropDown($model, 'PI_roba', $_list, array('placeholder' => Yii::t('seamanModule.personal_details', 'Размер робы')));
			?>
		</div>
		<div class="span3"></div>
	</div>

	<hr> <!-- ======================================= -->

	<div class="row-fluid">
		<div class="span3"><?php echo _drawCustomLabel(Yii::t('seamanModule.personal_details', 'Образование').
			CHtml::link('?', '#', array(
					'rel' => 'popover',
					'data-title' => Yii::t('seamanModule.personal_details', '[заголовок тултипа про образование]'),
					'data-content' => Yii::t('seamanModule.personal_details', '[содержимое тултипа про образование]'),
					'class' => 'label',
					'onclick' => 'return false;'
				)
			)
			); ?></div>
		<div class="span3">
			<?php echo _drawTextField($model, 'EI_nazvanije_zavedenija', array('placeholder' => Yii::t('seamanModule.personal_details', 'Название заведения')));?>
		</div>
		<div class="span3">
			<?php echo _drawTextField($model, 'EI_god_okonchanija', array(
				'placeholder' => Yii::t('seamanModule.personal_details', 'Год окончания'),
				'maxlength' => 4
			));?>
		</div>
		<div class="span3"></div>
	</div>

	<hr> <!-- ======================================= -->

	<div class="row-fluid">
		<div class="span3"><?php echo _drawCustomLabel(Yii::t('seamanModule.personal_details', 'Знание английского')); ?></div>
		<div class="span6">
			<?php echo _drawDropDown($model, 'EI_jazik_anglijskij', ModPredefinedData::model()->getEnglishLevelsForDropdown());?>
		</div>
		<div class="span3"></div>
	</div>

	<hr> <!-- ======================================= -->

	<div class="row-fluid">
		<input class="btn btn-primary" type="submit" name="submit" value="<?php echo Yii::t('seamanModule.personal_details', 'Сохранить изменения'); ?>"/>
	</div>


<?php


$this->endWidget();

$this->widget('ext.widgets.select2.Select2', array(
	'selector' => 'select'
));

//======================================================
//
//======================================================

function _resolveName($model, $attribute)
{
	return get_class($model).'['.$attribute.']';
}

function _resolveId($model, $attribute)
{
	return get_class($model).'_'.$attribute;
}

function _drawCustomLabel($customText = '')
{
	return CHtml::label($customText, '');
}

function _drawLabel(CModel $model, $attribute, $customText = '')
{
	if(empty($customText)) $customText = $model->getAttributeLabel($attribute);

	return CHtml::label($customText, _resolveId($model, $attribute), array('class' => $model->hasErrors($attribute) ? ' error' : ''));
}

function _drawTextField(CModel $model, $attribute, $htmlOptions = array())
{
	if(!array_key_exists('id', $htmlOptions)) $htmlOptions['id'] = _resolveId($model, $attribute);
	$htmlOptions['class'] = array_key_exists('class', $htmlOptions) ? $htmlOptions['class'] : '';
	$htmlOptions['class'] .= $model->hasErrors($attribute) ? ' error' : '';

	return CHtml::textField(_resolveName($model, $attribute), $model->$attribute, $htmlOptions);
}

function _drawDropDown(CModel $model, $attribute, $arr = array(), $htmlOptions = array())
{
	if(!array_key_exists('id', $htmlOptions)) $htmlOptions['id'] = _resolveId($model, $attribute);
	$htmlOptions['class'] = array_key_exists('class', $htmlOptions) ? $htmlOptions['class'] : '';
	$htmlOptions['class'] .= $model->hasErrors($attribute) ? ' error' : '';

	return CHtml::dropDownList(_resolveName($model, $attribute), $model->$attribute, $arr, $htmlOptions);
}

function _drawDropDownSimple($name, $value, $arr = array(), $htmlOptions = array())
{
	if(!array_key_exists('id', $htmlOptions)) $htmlOptions['id'] = $name;

	$htmlOptions['class'] = array_key_exists('class', $htmlOptions) ? $htmlOptions['class'] : '';

	return CHtml::dropDownList($name, $value, $arr, $htmlOptions);
}

function _drawRadioGroup(CModel $model, $attribute, $arr = array(), $htmlOptions = array())
{
	$name = _resolveName($model, $attribute);

	$htmlOptions['id'] = _resolveId($model, $attribute);
	$htmlOptions['class'] = array_key_exists('class', $htmlOptions) ? $htmlOptions['class'] : '';
	$htmlOptions['class'] .= $model->hasErrors($attribute) ? ' error' : '';

	$out = '';

	foreach ($arr as $k => $v)
	{
		$out .= CHtml::tag('input', array(
			'type' => 'radio',
			'name' => $name,
			'value' => $k,
			'id' => $htmlOptions['id'].'_'.$k,
			'checked' => $k == $model->$attribute ? 'checked': ''
		));

		$out .= CHtml::tag('label', array('for' => $htmlOptions['id'].'_'.$k), $v);
	}

	return $out;
}

function _drawRadioGroupSimple($name, $value, $arr = array(), $htmlOptions = array())
{
	if(!array_key_exists('id', $htmlOptions)) $htmlOptions['id'] = $name;
	$htmlOptions['class'] = array_key_exists('class', $htmlOptions) ? $htmlOptions['class'] : '';

	$out = '';

	foreach ($arr as $k => $v)
	{
		$out .= CHtml::tag('input', array(
			'type' => 'radio',
			'name' => $name,
			'value' => $k,
			'id' => $htmlOptions['id'].'_'.$k,
			'checked' => $k == $value ? 'checked': ''
		));

		$out .= CHtml::tag('label', array('for' => $htmlOptions['id'].'_'.$k), $v);
	}

	return $out;
}
