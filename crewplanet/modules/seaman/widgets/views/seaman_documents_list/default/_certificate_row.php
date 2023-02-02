<?php

/** @var SeamanDocumentsListWidget $this */
/** @var ModSeamanSertificate $certificate */
/** @var CntContract $contract */
/** @var Checklist $checklist */

$docColor = array();

$colorer = $checklist ? CertColorer::instance($checklist->id) : CertColorer::instance();
$docColor = $colorer->getColorInfo($certificate);

// если документ отсутствует и он не обязателен - пропускаем
if ($certificate->getIsNewRecord() && $certificate->is_required == 0) {
    return;
}

$rowCssClass = !$certificate->isApproved() ? 'highlight_not_accepted' : '';

$downloadLink = CHtml::link('', Yii::app()->createUrl('seaman/documentManager/fileUrls', array(
    'seamanId' => $certificate->seaman_id,
    'documentType' => DocumentManagerUploadNewFile::DOCUMENT_TYPE_CERT,
    'documentId' => $certificate->id,
)), array(
    'target' => '_blank',
    'class' => 'download-all fa fa-download fa-lg'
));

// рисуем строку
if ($certificate->sert->type == 'checkbox') {
    print CHtml::openTag('tr', array(
        'class' => 'js-toggle-seaman-cert-form js-certificate-row js_certificate ' . $rowCssClass,
        'data-seaman-id' => $certificate->seaman_id,
        'data-cert-id' => $certificate->id,
        'data-cert-type' => $certificate->sert_id,
        'data-contract-id' => $contract ? $contract->Id : '',
        'data-google-tracking' => 'Страница контракта|Редактирование сертификата|документы'
    ));
    print CHtml::tag('td', array(), $certificate->sert->long_title);
    print CHtml::tag('td', array(), '');
    print CHtml::tag('td', array(
        'colspan' => 2,
        'class' => !empty($docColor[$certificate->sert_id]) ? $docColor[$certificate->sert_id]['date1class'] : ''
    ), $docColor[$certificate->sert_id]['date1Text']);
    print CHtml::tag('td', array(), '');
    print CHtml::tag('td', array(), !empty($certificate->files) ? $downloadLink : '');
    print CHtml::closeTag('tr');
} else {
    print CHtml::openTag('tr', array(
        'class' => 'js-toggle-seaman-cert-form js-certificate-row js_certificate ' . $rowCssClass,
        'data-seaman-id' => $certificate->seaman_id,
        'data-cert-id' => $certificate->id,
        'data-cert-type' => $certificate->sert_id,
        'data-contract-id' => $contract ? $contract->Id : '',
        'data-google-tracking' => 'Страница контракта|Редактирование сертификата|документы'
    ));
    print CHtml::tag('td', array(), $certificate->sert->long_title);
    # Hide document numbers from unregistered visitors

	if(Yii::app()->authManager->isAnketaOwner($cnt['seaman_id']) || Yii::app()->authManager->isAgent()) {
		$number = $certificate->nomer;
	} else {
		$number = "";
	}
    print CHtml::tag('td', array(), $number);
    print CHtml::tag('td', array(
        'class' => !empty($docColor[$certificate->sert_id]) ? $docColor[$certificate->sert_id]['date1class'] : '',
    ), $docColor[$certificate->sert_id]['date1Text']);
    print CHtml::tag('td', array(
        'class' => $docColor[$certificate->sert_id]['date2class']
    ), $docColor[$certificate->sert_id]['date2Text']);
    print CHtml::tag('td', array(), Yii::app()->getLanguage() == 'ru' ? $certificate->country->name_rus : $certificate->country->name_eng);
    print CHtml::tag('td', array(), !empty($certificate->files) ? $downloadLink : '');
    print CHtml::closeTag('tr');
}
