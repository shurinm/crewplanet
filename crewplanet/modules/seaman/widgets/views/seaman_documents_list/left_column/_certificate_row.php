<?php

/** @var SeamanDocumentsListWidget $this */
/** @var ModSeamanSertificate $certificate */
/** @var CntContract $contract */
/** @var Checklist $checklist */


$_endDateTS = App::dbDateToTS($certificate->date2);
$textClass = '';
/** @var $days int кол-во дней до истечения */
$days = ($_endDateTS - time()) / 24 / 60 / 60;

$rowClass = '';
$status = '';
if ($certificate->getIsNewRecord() || $certificate->deleted) {
    $status = Yii::t('SeamanModule/document', 'Документ отсутствует');
    $textClass = 'text-error';
} elseif (empty($certificate->files)) {
    $status = Yii::t('SeamanModule/document', 'Сканы не загружены');
    $rowClass = 'document-img-warning';
    $textClass = 'text-success';
} elseif ($certificate->date2  != '0000-00-00' && $days < $this->days1) {
    $status = Yii::t('SeamanModule/document', 'Срок действия документа истек');
    $rowClass = 'document-clock-warning';
    $textClass = 'text-error';
} elseif ($certificate->date2  != '0000-00-00' && $days > $this->days1 && $days < $this->days2) {
    $status = Yii::t('SeamanModule/document', 'Срок действия документа истекает');
    $rowClass = 'document-clock-warning';
    $textClass = 'text-warning';
} else {
    $status = Yii::t('SeamanModule/document', 'OK');
    $textClass = 'text-success';
}
if (!in_array($certificate->sert_id, $this->requiredCertsIds)) {
    $textClass = 'text-success';
}

print CHtml::tag('a', array(
    'title' => $status,
    'class' => 'list-block__item list-block--link js-toggle-seaman-cert-form js-certificate-row js_certificate ' . $rowClass . ' ' . $textClass,
    'data-seaman-id' => $certificate->seaman_id,
    'data-cert-id' => $certificate->id,
    'data-cert-type' => $certificate->sert_id,
    'data-contract-id' => $contract ? $contract->Id : '',
    'data-google-tracking' => 'Страница контракта|Редактирование сертификата|документы',
    'href' => Yii::app()->createUrl('/seaman/documentUser/createDocument', array('certTypeId' => $certificate->sert_id, 'certId' => !$certificate->deleted ? $certificate->id : null))
), $certificate->sert->long_title);
