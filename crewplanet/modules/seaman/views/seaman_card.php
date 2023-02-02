<?php

/** @var $this Controller */

$cs = Yii::app()->clientScript;


$cs->registerCoreScript('jquery');
$cs->registerCoreScript('jquery.ui');

// скрипты и стили общие для карточек
$cs->registerCssFile(Yii::app()->theme->baseUrl.'/css/new_design/pages/candidate_card.css');
$cs->registerScriptFile(Yii::app()->theme->baseUrl.'/js/candidate_card.js', CClientScript::POS_HEAD);
$cs->registerScript('_candidateCard_ajaxUrl', 'candidateCard.ajaxUrl = "'.$this->createAbsoluteUrl('/vacancies/candidate/ajax').'";', CClientScript::POS_HEAD);

//===============================================
// комет
$cs->registerScriptFile('/comet/node_modules/socket.io/node_modules/socket.io-client/dist/socket.io.js');
$cs->registerScriptFile('/comet/client.js');

if(Yii::app()->authManager->isAgent() && Yii::app()->user->getUsersNumber() > 1)
{ // комет запускаем только для агентов у которых более одного юзера
	$cs->registerScript('_candidateCard_cometHost', 'candidateCard.cometHost = "'.Yii::app()->params['cometHost'].'";', CClientScript::POS_HEAD);
	$cs->registerScript('_candidateCard_cometMyLabel', 'candidateCard.cometMyLabel = "'.Yii::app()->user->getGroupUsername().'";', CClientScript::POS_HEAD);
	$cs->registerScript('_candidateCard_cometAgencyId', 'candidateCard.cometAgencyId = '.(Yii::app()->user->parent_id ? Yii::app()->user->parent_id : 0).';', CClientScript::POS_HEAD);
	$cs->registerScript('_candidateCard_cometRoom', 'candidateCard.cometRoom = "candidateCard";', CClientScript::POS_HEAD);
}


// скрипты и стили модуля
$assets = Yii::app()->getAssetManager()->publish(Yii::app()->getModule('seaman')->getBasePath().'/assets', false, -1, defined('YII_DEBUG'));
$cs->registerCssFile($assets.'/seaman_card.css');
$cs->registerCssFile($assets.'/access_dlg.css');
$cs->registerScriptFile($assets.'/seaman_card.js');

//-----------------------------------------
// аякс и ты и ды
$cs->registerScript('seamanCard_ajaxUrl', 'seamanCard.ajaxUrl = "'.$this->createAbsoluteUrl('/seaman/ajax/card').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_uploadUrl', 'seamanCard.uploadUrl = "'.$this->createAbsoluteUrl('/seaman/ajax/upload').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_swfUrl', 'seamanCard.swfUrl = "'.Yii::app()->theme->baseUrl.'/js/plupload/plupload.flash.swf";', CClientScript::POS_HEAD);

//-----------------------------------------
// диалог добавления комментария
$cs->registerScript('seamanCard_sSlabelRank', 'seamanCard.sSlabelRank = "'.Yii::t('seamanModule.card', 'Общая оценка этого контракта').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_sSlabelFrom', 'seamanCard.sSlabelFrom = "'.Yii::t('seamanModule.card', 'Кто дал оценку? (напр. имя, должность)').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_sSlabelComment', 'seamanCard.sSlabelComment = "'.Yii::t('seamanModule.card', 'Комментарий к этому контракту').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_sSlabelFile', 'seamanCard.sSlabelFile = "'.Yii::t('seamanModule.card', 'Файл \"Evaluation report\"').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_sStitle', 'seamanCard.sStitle = "'.Yii::t('seamanModule.card', 'Оценка контракта').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_sSerrorRank', 'seamanCard.sSerrorRank = "'.Yii::t('seamanModule.card', 'Вам нужно выбрать оценку.').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_sSerrorFrom', 'seamanCard.sSerrorFrom = "'.Yii::t('seamanModule.card', 'Вы должны указать человека, давшего оценку.').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_sSdeleteAllComments', 'seamanCard.sSdeleteAllComments = "'.Yii::t('seamanModule.card', 'Удалить все комментарии.').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_sSdeleteAllCommentsConfirm', 'seamanCard.sSdeleteAllCommentsConfirm = "'.Yii::t('seamanModule.card', 'Вы действительно хотите удалить все комментарии к выбранному контракту?').'";', CClientScript::POS_HEAD);

//-----------------------------------------
// диалог удаления комментария
$cs->registerScript('seamanCard_sS_deleteCommentConfirmText', 'seamanCard.sS_deleteCommentConfirmText = "'.Yii::t('seamanModule.card', 'Вы действительно хотите удалить выбранный комментарий?').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_sS_deleteCommentConfirmTitle', 'seamanCard.sS_deleteCommentConfirmTitle = "'.Yii::t('seamanModule.card', 'Удаление комментария').'";', CClientScript::POS_HEAD);

//-----------------------------------------
// диалог удаления аватара
$cs->registerScript('seamanCard_sC_deleteAvatarConfirmText', 'seamanCard.sC_deleteAvatarConfirmText = "'.Yii::t('seamanModule.card', 'Вы действительно хотите удалить аватар моряка?').'";', CClientScript::POS_HEAD);
$cs->registerScript('seamanCard_sC_deleteAvatarConfirmTitle', 'seamanCard.sC_deleteAvatarConfirmTitle = "'.Yii::t('seamanModule.card', 'Удаление аватара').'";', CClientScript::POS_HEAD);

//-----------------------------------------
// закачка драг-н-дропом
$cs->registerScriptFile(Yii::app()->theme->baseUrl.'/js/plupload/plupload.full.js');
$cs->registerScriptFile(Yii::app()->theme->baseUrl.'/js/jquery.form.js');

//-----------------------------------------
$this->widget('ext.widgets.uikit.uikit');

?>
	<div
		data-type="seaman"
		data-id="<?php echo $data['id']; ?>"
		data-vac-id="<?php echo isset($vacancyId) ? $vacancyId : '0'; ?>"
		data-candidate-id="<?php echo intval(@$data['_vacInfo']['id']); ?>"
		class="candidate_card row-fluid
	<?php
		// дополнительные классы
		echo $data['visible_to_others'] == 0 ? 'css_hidden' : '';

		if(Yii::app()->authManager->hasAccessToSeaman($data['id']))
			echo ' js_accessFull';
		elseif(Yii::app()->user->checkAccess('roleAgent'))
			echo ' js_accessAgent';
		elseif(Yii::app()->authManager->isAnketaOwner($data['id']))
			echo ' js_accessOwner';

		if(!Yii::app()->user->checkAccess('seamanCard_allowSeeFooter', array('seamanId' => $data['id'])))
			echo ' css_rounded_corners';
		?>">
	<ul class="nav nav-tabs">
		<li data-type="summary" class="active"><a href="#"><?php echo Yii::t('seamanModule.card', 'Основное'); ?></a></li>
		<li data-type="details"><a href="#"><?php echo Yii::t('seamanModule.card', 'Личные данные'); ?></a></li>
		<li data-type="sea_services"><a href="#"><?php echo Yii::t('seamanModule.card', 'Опыт в море'); ?></a></li>
		<li data-type="documents"><a href="#"><?php echo Yii::t('seamanModule.card', 'Документы'); ?></a></li>
		<?php
		/*
		 <li data-type="vacancies"><a href="#"><?php echo Yii::t('seamanModule.card', 'Вакансии'); ?></a></li>
		 */
		?>
	</ul>

	<div class="box">

	<?php
	//======================================================
	// заголовок
	//======================================================
	?>

	<div class="title">
		<div class="span6 css_name">
			<span class="person_id" title="<?php echo Yii::t('seamanModule.card', 'ID моряка'); ?>">ID <?php echo $data['id']; ?> </span>
			<?php

			$seamans_age = ModSeaman::getAge($data['PI_rozhdenije_data']);

			if(Yii::app()->authManager->isAgent() || Yii::app()->authManager->isAnketaOwner($data['id']))
				echo $data['PI_imja'].' '.$data['PI_familija'];
			else
				echo '<span title="'.Yii::t('seamanModule.card', 'имя моряка доступно только зарегистрированным работодателям').'">'.Yii::t('seamanModule.card', 'Имя скрыто').'</span>';

			echo ', <span title="'.Yii::t('seamanModule.card', 'возраст').'">'.$seamans_age.'</span>';
			if(Yii::app()->user->checkAccess('roleCrewplanet'))
				echo '<span class="hidden_indicator" title="'.Yii::t('seamanModule.card', 'Виден только работникам Crewplanet').'">'.Yii::t('seamanModule.card', 'спрятан').'</span>';
			?>
		</div>

		<div class="span6">
			<?php

			// активатор меню (шестерня) показываем только НЕ морякам
			if(!Yii::app()->authManager->isSeaman())
			{
				echo CHtml::link('', '#', array('class' => 'js_css_menu_activator pull-right'));

				// значок доступа
				if(!Yii::app()->authManager->hasAccessToSeaman($data['id']) && Yii::app()->authManager->isAgent())
				{
					echo CHtml::link('', '#', array(
						'class' => 'css_access_lock_closed link_get_access pull-right',
						'title' => Yii::t('seamanModule.card', 'Доступ закрыт'),
						'rel' => 'tooltip'
					));
				}
			}
			?>

			<div class="js_css_comet_users pull-right"></div>

			<?php

			if(!Yii::app()->authManager->isSeaman())
			{
				$_menuItems = array();

				if(Yii::app()->user->isGuest)
				{
					$_menuItems[] = array('label' => Yii::t('seamanModule.card', 'Это меню доступно только зарегистрированным работодателям.'));
					$_menuItems[] = array('label' => Yii::t('seamanModule.card', 'Войти в систему'), 'url' => array('/site/login'));
					$_menuItems[] = array('label' => Yii::t('seamanModule.card', 'Зарегистрироваться'), 'url' => array('/shipowner/profile/register'));
				}
				else
				{


					$_menuItems[] = array('label' => '<image src="'.Yii::app()->theme->baseUrl.'/img/iconset/icon-pdf.png"> '.Yii::t('seamanModule.card', 'Скачать анкету в PDF'), 'url' => '#',
						'linkOptions' => array(
							'class' => 'js_sC_downloader',
							'data-prepare-url' => $this->createUrl('/seaman/categories/preparePdf', array('includePersonalDetails' => 1)),
							'data-redirect-url' => $this->createUrl('/seaman/categories/downloadPdf', array('dummy' => 0)),
							'data-download-message' => Yii::t('seamanModule.card', 'Идет подготовка данных, подождите... <br/>Это может занять до 60 секунд.')
						),
					);

					$_menuItems[] = array('label' => '<image src="'.Yii::app()->theme->baseUrl.'/img/iconset/icon-pdf.png"> '.Yii::t('seamanModule.card', 'Скачать анкету без контактных деталей'), 'url' => '#',
						'linkOptions' => array(
							'class' => 'js_sC_downloader',
							'data-prepare-url' => $this->createUrl('/seaman/categories/preparePdf', array('dummy' => 0)),
							'data-redirect-url' => $this->createUrl('/seaman/categories/downloadPdf', array('dummy' => 0)),
							'data-download-message' => Yii::t('seamanModule.card', 'Идет подготовка данных, подождите... <br/>Это может занять до 60 секунд.')
						),
					);
				}

				if(Yii::app()->user->checkAccess('roleAgent'))
				{

					$_menuItems[] = array('label' => '<i class="icon-download-alt"></i> '.Yii::t('seamanModule.card', 'Скачать документы в ZIP'), 'url' => '#',
						'linkOptions' => array(
							'class' => 'js_sC_downloader',
							'data-prepare-url' => $this->createUrl('/seaman/categories/prepareDocuments', array('dummy' => 0)),
							'data-redirect-url' => $this->createUrl('/seaman/categories/downloadDocuments', array('dummy' => 0)),
							'data-download-message' => Yii::t('seamanModule.card', 'Идет подготовка данных, подождите...')
						),
					);

					if (file_exists(User::getAvatarPath($data['id']))) {
                        $_menuItems[] = array('label' => '<i class="icon-download-alt"></i> '.Yii::t('seamanModule.card', 'Скачать фото'),
                            'url' => User::getAvatarUrl($data['id']),
                            'linkOptions' => array(
                                'target' => '_blank',
                            ),
                        );
                    }

					$_menuItems[] = array('label' => '<hr>');

					$_menuItems[] = array('label' => '<i class="icon-plus"></i> '.Yii::t('seamanModule.card', 'Добавить в вакансию'), 'url'=>'#', 'linkOptions'=>array(
						'class' => 'js_sC_add2vacancy'
					));

					$_menuItems[] = array('label' => '<i class="icon-time"></i> '.Yii::t('seamanModule.card', 'Запланировать на судно'), 'url'=>'#', 'linkOptions'=>array(
						'class' => 'js_sC_add2planning'
					));

					$_menuItems[] = array('label' => '<hr>');

					$_menuItems[] = array(
						'label' => '<i class="icon-warning-sign"></i> '.Yii::t('seamanModule.card', 'Выслать напоминание пароля'),
						'url' => '#',
						'visible' => Yii::app()->user->checkAccess('allowSendPassword', array('seamanId'=>$data['id'])),
						'linkOptions' => array('class' => 'js_sC_send_password'),
					);


					$_menuItems[] = array('label' => '<i class="icon-envelope"></i> '.Yii::t('seamanModule.card', 'Написать е-мейл'), 'url' => '#',
						'linkOptions' => array('class' => 'js_sC_composeEmail'),
					);

				}

				if(Yii::app()->user->checkAccess('roleCrewplanet'))
				{
					$_menuItems[] = array('label' => '<hr>');

					$_menuItems[] = array('label' => '<i class="icon-eye-close"></i> '.Yii::t('seamanModule.card', 'Спрятать моряка от всех'), 'url' => '#',
						'visible' => Yii::app()->user->checkAccess('allowChangeVisibility'),
						'linkOptions' => array('class' => 'js_sC_hide_seaman'),
						'itemOptions' => array('class' => 'css_hide_seaman'),
					);

					$_menuItems[] = array('label' => '<i class="icon-eye-open"></i> '.Yii::t('seamanModule.card', 'Показать моряка'), 'url' => '#',
						'visible' => Yii::app()->user->checkAccess('allowChangeVisibility'),
						'linkOptions' => array('class' => 'js_sC_show_seaman'),
						'itemOptions' => array('class' => 'css_show_seaman'),
					);

					$_menuItems[] = array('label' => '<i class="icon-edit"></i> '.Yii::t('seamanModule.card', 'Изменить данные моряка'),
						'url' => array('/seaman/profile/change', 'sId'=>$data['id']),
//						'visible' => Yii::app()->user->checkAccess('allowChangeVisibility'),
//						'linkOptions' => array('class' => 'js_sC_show_seaman'),
//						'itemOptions' => array('class' => 'css_show_seaman'),
					);

					//-----------------------------------------
					$_menuItems[] = array('label' => '<hr>');
					$_menuItems[] = array(
						'label' => 'L/P: '.$data['_login'].' / '.$data['_password']
					);
				}

				$this->widget('zii.widgets.CMenu', array('htmlOptions' => array('class' => 'card_menu'), 'items' => $_menuItems, 'encodeLabel' => false));
			}
			?>
		</div>
		<div class="clearfix"></div>
		<div class="drag_and_drop_handler"></div>
	</div>

	<?php
	//======================================================
	// контент
	//======================================================
	?>

	<div class="row-fluid">
		<!-- BEGIN OF SUMMARY SECTION -->
		<div class="js_section js_section_summary" data-loaded="1">
			<?php $this->renderPartial('application.modules.seaman.views._seaman_card.summary', array('data' => $data)); ?>
			<div class="clearfix"></div>
		</div>
		<!-- END OF SUMMARY SECTION -->

		<!-- BEGIN OF DETAILS SECTION -->
		<div class="js_section js_section_details" data-loaded="0">
			<div style="margin:10px;text-align:center;"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/working.gif"/></div>
			<div class="clearfix"></div>
		</div>
		<!-- END OF DETAILS SECTION -->

		<!-- BEGIN OF SEA SERVICE SECTION -->
		<div class="js_section js_section_sea_services" data-loaded="0">
			<div style="margin:10px;text-align:center;"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/working.gif"/></div>
			<div class="clearfix"></div>
		</div>
		<!-- END OF SEA SERVICE SECTION -->

		<!-- BEGIN OF DOCUMENTS SECTION -->
		<div class="js_section js_section_documents" data-loaded="0">
			<div style="margin:10px;text-align:center;"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/working.gif"/></div>
			<div class="clearfix"></div>
		</div>
		<!-- END OF DOCUMENTS SECTION -->

		<!-- BEGIN OF VACANCIES SECTION -->
		<div class="js_section js_section_vacancies" data-loaded="0">
			<div style="margin:10px;text-align:center;"><img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/working.gif"/></div>
			<div class="clearfix"></div>
		</div>
		<!-- END OF VACANCIES SECTION -->

	</div>
	<?php
	//======================================================
	// футер
	//======================================================

	if(Yii::app()->user->checkAccess('seamanCard_allowSeeFooter'))
	{
		?>
		<div class="css_footer">
			<div class="row-fluid js_footer span5">
				<div class="wrapper">
					<div class="container">
						<?php
						if($data['_tags']['overWeight'] !== false)
							echo '<span class="label label_1 css_tag_overWeight" title="'.$data['_tags']['overWeight'].'" rel="tooltip">'.Yii::t('seamanModule.card', 'Излишний вес?').'</span> ';
						if($data['_tags']['worked'] !== false)
							echo '<span class="label label_1 css_tag_worked">'.Yii::t('seamanModule.card', 'Работал').'</span> ';

						// оффшорные теги
						$_ = array(
							492 => 'HUET',
							493 => 'BOSIET',
							489 => 'DP Induction',
							490 => 'DP Advanced',
							515 => 'DP Limited',
							491 => 'DP Unlimited'
						);
						for ($i = 0, $_c = count($data['_tags']['offshore']); $i < $_c; $i++)
							echo '<span class="label label_2">'.$_[$data['_tags']['offshore'][$i]].'</span> ';

						if($data['_tags']['offshoreWorked'])
							echo '<span class="label label_2">Offshore</span> ';

						?>
						<span class="js_tags_area">
							<?php
							// зона для пользовательских тегов
							for ($i = 0, $_c = count($data['_tags']['_private']); $i < $_c; $i++)
								echo '<span class="label label_3">'.$data['_tags']['_private'][$i].'</span> ';
							?>
						</span>

						&nbsp;

						<?php
						echo CHtml::link('', '#', array(
							'class' => 'css_edit_tags '.(Yii::app()->authManager->hasAccessToSeaman($data['id']) ? 'js_edit_tags' : 'link_get_access'),
							'title' => Yii::t('seamanModule.card', 'Редактировать ярлыки'),
						));
						?>
					</div>

					<div class="cover"></div>
				</div>
			</div>

			<?php
			//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
			// статусы рассмотрения
			if(isset($data['_vacInfo']))
			{
				$vi = $data['_vacInfo'];
				?>
				<div class="js_css_pipe_status pull-right <?php echo $data['_vacInfo']['_isArchived'] ? 'css_archive_mode': ''; ?>">
					<?php

					// маркер забытого показываем только если вакансия еще активна и моряк еще не отклонен
					if(!$data['_vacInfo']['_isArchived'] && $vi['status'] > 0)
					{ //
						if(time() - App::dbDatetimeToTS($vi['_lastChanged']) > Yii::app()->params['vacancyWaitingTime'] * 60 * 60 * 24)
							echo CHtml::tag('span', array(
								'class' => 'css_warning js_status_warning',
								'title' => Yii::t('seamanModule.card', 'Вы более {n} дней не меняли статус рассмотрения моряка', Yii::app()->params['vacancyWaitingTime'])
							), '', true);
					}

					echo CHtml::link(
						CHtml::tag('span', array('class'=>'text'), VacCandidate::getStatusTitle($vi['status']).'<span class="activator_pipe_menu"></span>','', true),
						'#',
						array(
							'class'=>'css_status '.($data['_vacInfo']['_isArchived'] ? 'js_css_card_doNothing': 'js_card_linkChangeStatus'),
							'title' => $data['_vacInfo']['_isArchived'] ?
									Yii::t('seamanModule.card', 'Результат рассмотрения кандидата') :
									Yii::t('seamanModule.card', 'Изменить статус рассмотрения кандидата')
						)
					);

					echo CHtml::link(
						CHtml::image('/themes/crewplanet/img/new/vac-status-question.png'),
						'#',
						array(
							'class' => 'marker  quality_'.$vi['quality'].' '.($data['_vacInfo']['_isArchived'] ? 'js_css_card_doNothing': 'js_card_changeQuality'),
							'title' => Yii::t('seamanModule.card', 'Оценка кандидата')
						)
					);

					?>

					<ul class="js_css_quality_menu">
						<li class="text_only">
							<span>
							<?php echo Yii::t('seamanModule.card', 'НЕ видно моряку'); ?>
							</span>
						</li>
						<li>
							<a class="js_candidate_quality" data-type="good" href="#"><span class="quality quality_good"></span><span class="text"><?php echo Yii::t('seamanModule.card', 'хороший кандат'); ?></span></a>
						</li>
						<li>
							<a class="js_candidate_quality" data-type="average" href="#"><span class="quality quality_average"></span><span class="text"><?php echo Yii::t('seamanModule.card', 'средний кандидат'); ?></span></a>
						</li>
						<li>
							<a class="js_candidate_quality" data-type="bad" href="#"><span class="quality quality_bad"></span><span class="text"><?php echo Yii::t('seamanModule.card', 'плохой кандидат'); ?></span></a>
						</li>
						<li>
							<a class="js_candidate_quality" data-type="none" href="#"><span class="quality quality_none"></span><span class="text"><?php echo Yii::t('seamanModule.card', 'неопределенный'); ?></span></a>
						</li>
					</ul>

					<?php
					$_s = VacCandidate::getStatusesForDropdown(false, true);

					//Так выглядит меню на карточке, когда развернуто
					$this->widget('zii.widgets.CMenu', array(
						'htmlOptions' => array('class' => 'js_css_status_menu'),
						'items' => array(
							array('label' => Yii::t('seamanModule.card', 'Статус рассмотрения кандидата Видно моряку')),
							array(
								'label' => Yii::t('modelVacCandidate', 'Новая анкета'),
								'url' => '#',
								'linkOptions' => array('class' => 'js_card_statusMainLevel'),
								'items' => array(
									array('label' => Yii::t('modelVacCandidate', 'не прочитана'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_110, 'class' => 'js_card_changeStatus'), 'url' => '#'),
									array('label' => Yii::t('modelVacCandidate', 'прочитана, ожидает решения'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_120, 'class' => 'js_card_changeStatus'), 'url' => '#'),
									array('label' => Yii::t('modelVacCandidate', 'прочитана, претендент отклонен'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_n110, 'class' => 'js_card_changeStatus'), 'url' => '#'),
									//-									array('label' => Yii::t('modelVacCandidate', 'прочитана, претендент отклонен (по старому пайп-лайну)'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_n190, 'class' => 'js_card_changeStatus'), 'url' => '#'),
								)
							),

							array(
								'label' => Yii::t('modelVacCandidate', 'Интервью'),
								'url' => '#',
								'linkOptions' => array('class' => 'js_card_statusMainLevel'),
								'items' => array(
									array('label' => Yii::t('modelVacCandidate', 'ожидает интервью'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_210, 'class' => 'js_card_changeStatus'), 'url' => '#'),
									array('label' => Yii::t('modelVacCandidate', 'интервью состоялось, претендент отклонен'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_n210, 'class' => 'js_card_changeStatus'), 'url' => '#'),
								)),

							array(
								'label' => Yii::t('modelVacCandidate', 'Принятие решения'),
								'url' => '#',
								'linkOptions' => array('class' => 'js_card_statusMainLevel'),
								'items' => array(
									array('label' => Yii::t('modelVacCandidate', 'мы размышляем над анкетой'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_310, 'class' => 'js_card_changeStatus'), 'url' => '#'),
									array('label' => Yii::t('modelVacCandidate', 'мы ожидаем решения от моряка'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_320, 'class' => 'js_card_changeStatus'), 'url' => '#'),
									array('label' => Yii::t('modelVacCandidate', 'и мы, и моряк размышляем'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_330, 'class' => 'js_card_changeStatus'), 'url' => '#'),
									array('label' => Yii::t('modelVacCandidate', 'не договорились, претендент отклонен'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_n310, 'class' => 'js_card_changeStatus'), 'url' => '#'),
								)),

							array(
								'label' => Yii::t('modelVacCandidate', 'Утверждение'),
								'url' => '#',
								'linkOptions' => array('class' => 'js_card_statusMainLevel'),
								'items' => array(
									array('label' => Yii::t('modelVacCandidate', 'ожидает представления'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_410, 'class' => 'js_card_changeStatus'), 'url' => '#'),
									array('label' => Yii::t('modelVacCandidate', 'представлен, ожидаем решения'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_420, 'class' => 'js_card_changeStatus'), 'url' => '#'),
									array('label' => Yii::t('modelVacCandidate', 'представлен и утвержден'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_430, 'class' => 'js_card_changeStatus'), 'url' => '#'),
									//-								array('label' => Yii::t('modelVacCandidate', 'потенциальный кандидат (из старого пайп-лайна)'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_490, 'class' => 'js_card_changeStatus'), 'url' => '#'),
									array('label' => Yii::t('modelVacCandidate', 'представлен, но отклонен'), 'linkOptions' => array('data-status' => VacCandidate::STATUS_n410, 'class' => 'js_card_changeStatus'), 'url' => '#'),
								)),
						)
					));
					?>
				</div>
			<?php
			}
			?>

			<div class="clearfix"></div>
		</div>
	<?php
	}
	?>
	</div>

	</div>

<?php
// просто заглушка чтобы подключить виджет
$this->widget('ext.widgets.select2.Select2', array('selector' => '#dummy___'));
