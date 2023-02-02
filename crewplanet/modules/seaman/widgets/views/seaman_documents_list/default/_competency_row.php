<?php

/** @var SeamanDocumentsListWidget $this */
/** @var ModSeamanCompetency $competency */

$_classes = array(
    'nomer' => '',
    'date1' => '',
    'date1-e' => '',
    'date2' => '',
    'country_id' => '',
    'row' => '' // класс для всей строки
);

$_endDateTS = App::dbDateToTS($competency->data_istechenija);

/** @var $days int кол-во дней до истечения */
$days = ($_endDateTS - time()) / 24 / 60 / 60;

switch (true) {
    case $days < 0 :
        $_classes['date2'] = 'highlight_expired ';
        break;
    case $days < $this->days1 :
        $_classes['date2'] = 'highlight_warning_1 ';
        break;
    case $days < $this->days2 :
        $_classes['date2'] = 'highlight_warning_2 ';
        break;
}

if (App::dbDateToTS($competency->data_vidachi) > time()) // дата выдачи в будущем - диплом еще не выдан
    $_classes['date1'] = 'highlight_expired ';

if (App::dbDateToTS($competency->endorsment_data_vidachi) > time()) // дата выдачи приложения в будущем - приложение еще не выдано
    $_classes['date1-e'] = 'highlight_expired ';

if (!$competency->isApproved()) {
    $_classes['row'] = 'highlight_not_accepted ';
}

$downloadLink = CHtml::link('', Yii::app()->createUrl('seaman/documentManager/fileUrls', array(
    'seamanId' => $competency->seaman_id,
    'documentType' => DocumentManagerUploadNewFile::DOCUMENT_TYPE_COMPETENCY,
    'documentId' => $competency->id,
)), array(
    'target' => '_blank',
    'class' => 'download-all fa fa-download fa-lg'
));

print CHtml::openTag('tr', array(
    'class' => 'js-toggle-seaman-competency-form js-competency-row js_competency ' . $_classes['row'],
    'data-seaman-id' => $competency->seaman_id,
    'data-competency-id' => $competency->id,
    'data-google-tracking' => 'Страница контракта|Редактирование диплома|документы'
));
print CHtml::tag('td', array(), $competency->position == 0 ? $competency->position_freeenter : ModPredefinedData::model()->getValue($competency->position));
if(Yii::app()->authManager->isAnketaOwner($cnt['seaman_id']) || Yii::app()->authManager->isAgent()) {
         $number = $competency->nomer_vidachi;
     } else {
         $number = "";
}
print CHtml::tag('td', array(), $number);
print CHtml::tag('td', array('class' => $_classes['date1']), App::tsToHuman(App::dbDateToTS($competency->data_vidachi)));
print CHtml::tag('td', array(
    'rowspan' => $competency->hasEndorsment() ? 2 : 1,
    'class' => $_classes['date2']
), App::tsToHuman(App::dbDateToTS($competency->data_istechenija)));
print CHtml::tag('td', array(
    'rowspan' => $competency->hasEndorsment() ? 2 : 1,
), ModPredefinedData::model()->getValue($competency->strana_vidachi));
print CHtml::tag('td', array(
    'rowspan' => $competency->hasEndorsment() ? 2 : 1,
), !empty($competency->files) ? $downloadLink : '');
print CHtml::closeTag('tr');

if ($competency->hasEndorsment()) {
    print CHtml::openTag('tr', array(
        'class' => 'js-toggle-seaman-competency-form js-competency-row js_competency_end css_noborder '.$_classes['row'],
        'data-seaman-id' => $competency->seaman_id,
        'data-competency-id' => $competency->id,
        'data-google-tracking' => 'Страница контракта|Редактирование документа|документы'
    ));
    print CHtml::tag('td', array(), CHtml::tag('span', array('class' => 'endorsment_title'), Yii::t('SeamanModule/document', 'Приложение')));
if(Yii::app()->authManager->isAnketaOwner($cnt['seaman_id']) || Yii::app()->authManager->isAgent()) {
          $number = $competency->endorsment_nomer_vidachi;
      } else {
          $number = "";
 }
    print CHtml::tag('td', array(), $number);
    print CHtml::tag('td', array('class' => $_classes['date1-e']), App::tsToHuman(App::dbDateToTS($competency->endorsment_data_vidachi)));
    print CHtml::closeTag('tr');
}

