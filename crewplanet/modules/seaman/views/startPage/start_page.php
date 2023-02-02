<?php

/**
 * @var $this  Controller
 * @var $form  TbActiveForm
 * @var $model StartPageForm
 */

$cs = Yii::app()->clientScript;

$cs->registerCoreScript('jquery');
$cs->registerCoreScript('jquery.ui');

$assets = Yii::app()->getAssetManager()->publish(Yii::app()->getModule('seaman')->getBasePath().'/assets', false, -1, defined('YII_DEBUG'));
$cs->registerCssFile($assets.'/start_page.css');

$this->pageTitle = Yii::t('seamanModule.startPage', 'Карточка моряка');
?>

<div class="intro">
	<p><?php echo Yii::t('seamanModule.startPage', '[[вводный текст на стартовой моряка]]', array('{edit_cv_url}' => Yii::app()->getInsiteUrl('seafarers/registration'))); ?></p>
</div>

<?php

// загружаем модуль, т.к. без него никуда...
Yii::app()->getModule('vacancies');


$card = new SeamanCard();
$seaman = $card->getOne(Yii::app()->user->id);

// рендерим карточку
$this->renderPartial('application.modules.seaman.views.seaman_card', array('data' => $seaman, 'vacancyId' => 0));

//===============================================
?>

<div class="row-fluid">
	<div class="span8 well">
		<h4><?php echo Yii::t('seamanModule.startPage', 'Вы претендовали на вакансии'); ?></h4>
		<?php

		// поднимаем 6 последних "претендований" моряка
		/** @var VacCandidate[] $candidates */
		$candidates = VacCandidate::model()->with('vacancy')->findAll(array(
			'condition' => 'seaman_id='.Yii::app()->user->id,
			'limit' => 5,
			'order' => 't.created desc'
		));

		if(count($candidates) > 0)
		{
			?>
			<table class="table table-bordered">
				<?php
				for ($i = 0, $_c = count($candidates); $i < $_c; $i++)
				{
					$cand = $candidates[$i];

					?>
					<tr>
						<td>
							<?php
							echo CHtml::link(
								$cand->vacancy->getTitle(true),
								array('/vacancies/search/card', 'vacId' => $cand->vacancy_id),
								array('target' => '_blank')
							);
							echo '<br/>'.$cand->vacancy->getSalaryTextOfThisVacancy();

						?>
						</td>
						<td><?php echo VacCandidate::getStatusForVacancyCard($cand->status); ?><br/>
							<?php
							if($cand->vacancy->isArchived())
							echo '<div class="css_closed_vacancy">'.Yii::t('seamanModule.startPage', 'закрыта').'</div>';
							?>
						</td>
					</tr>
				<?php
				}
				?>
			</table>
			<p>
				<a href="<?php echo $this->createUrl('/vacancies/search'); ?>"><?php echo Yii::t('seamanModule.startPage', 'Перейти к поиску вакансий'); ?></a>
			</p>
		<?php
		}
		else
		{
			?>
			<div class="alert alert-info">
				<p><?php echo Yii::t('seamanModule.startPage', 'На данный момент Вы не претендуете ни на одну вакансию.'); ?></p>

				<p>
					<a href="<?php echo $this->createUrl('/vacancies/search'); ?>"><?php echo Yii::t('seamanModule.startPage', 'Перейти к поиску вакансий'); ?></a>
				</p>
			</div>
		<?php
		}

		?>
	</div>

	<div class="span4 well">
		<h4><?php echo Yii::t('seamanModule.startPage', 'Ваш будущий контракт'); ?></h4>

		<?php
		$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array('id' => 'StartPageForm'));

		echo $form->textFieldRow($model, 'dor');
		echo $form->textFieldRow($model, 'salary', array('style' => 'width: 116px;'));
		echo $form->dropDownList($model, 'salaryCurrency', App::getCurrenciesForDropdown(), array('style' => 'width: 80px;margin-left: 10px'));

		echo '<div class="clearfix"></div>';

		echo CHtml::button(Yii::t('seamanModule.startPage', 'Сохранить'), array(
			'class' => 'btn btn-primary',
			'id' => 'submit_btn',
			'name'=>'submit',
			'type' => 'submit'

		));

		$this->endWidget();
		?>

	</div>

	<div class="clearfix"></div>
</div>