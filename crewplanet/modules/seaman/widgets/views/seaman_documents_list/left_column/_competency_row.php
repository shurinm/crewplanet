<?php

/** @var SeamanDocumentsListWidget $this */
/** @var ModSeamanCompetency $competency */
// если документ удален - пропускаем
if ($competency->deleted) {
    return;
}
$_endDateTS = App::dbDateToTS($competency->data_istechenija);

/** @var $days int кол-во дней до истечения */
$days = ($_endDateTS - time()) / 24 / 60 / 60;

$status = '';
if (empty($competency->files)) {
    $status = Yii::t('SeamanModule/document', 'Сканы не загружены');
    $rowClass = 'document-img-warning';
    $textClass = 'text-success';
} elseif ($days < $this->days1) {
    $status = Yii::t('SeamanModule/document', 'Срок действия документа истек');
    $rowClass = 'document-clock-warning';
    $textClass = 'text-error';
} elseif ($days > $this->days1 && $days < $this->days2) {
    $status = Yii::t('SeamanModule/document', 'Срок действия документа истекает');
    $rowClass = 'document-clock-warning';
    $textClass = 'text-warning';
} else {
    $status = Yii::t('SeamanModule/document', 'OK');
    $textClass = 'text-success';
}

print CHtml::tag('a', array(
    'class' => 'list-block__item list-block--link js-toggle-seaman-competency-form js-competency-row js_competency ' . $rowClass . ' ' . $textClass,
    'data-seaman-id' => $competency->seaman_id,
    'data-competency-id' => $competency->id,
    'data-google-tracking' => 'Страница контракта|Редактирование диплома|документы',
    'href' => Yii::app()->createUrl('/seaman/documentUser/createDocument', array('isConventional' => $competency->conventional, 'competencyId' => $competency->id))
), $competency->position == 0 ? $competency->position_freeenter : ModPredefinedData::model()->getValue($competency->position));

