<?php

/** @var SeamanDocumentsListWidget $this */
/** @var ModSeaman $seaman */
/** @var ModSeamanCompetency[] $competencies */
/** @var ModSeamanSertificate[] $certificates */
/** @var CntContract|null $contract */
/** @var Checklist|null $checklist */
/** @var string|null $position */

$this->widget('ext.widgets.uikit.uikit');

$checklistCertificates = array();
if ($checklist instanceof Checklist) {
    $checklistCertificates = ModSeamanSertificate::getSeamanCertificatesByChecklist($seaman->id, $checklist->id);
}
$primaryCertsEmpty = true;
$certsEmpty = true;
$competencyEmpty = true;
foreach ($certificates as $certificate) {
    if ($certificate->isPrimaryDoc() && !$certificate->deleted) {
        $primaryCertsEmpty = false;
    } elseif (!$certificate->deleted) {
        $certsEmpty = false;
    }
    if (!$certsEmpty && !$primaryCertsEmpty) {
        break;
    }
}
foreach ($competencies as $competency) {
    if (!$competency->deleted) {
        $competencyEmpty = false;
        break;
    }
}
?>

<div class="sidebar-block sidebar-block__inner js-document-list-widget">
    <div class="list-block">
        <strong class="list-block__item list-block--title"><?= Yii::t('SeamanModule/document', 'Паспорта, визы') ?></strong>
        <?php if ($primaryCertsEmpty) : ?>
            <?= CHtml::link(Yii::t('SeamanModule/document', 'Добавить проездной документ'),
                Yii::app()->createUrl('/seaman/documentUser/createDocument'), array('class' => 'list-block__item')) ?>

        <?php endif; ?>
        <div id="documentsTable">
            <?php if (!$primaryCertsEmpty) : ?>
                <?php foreach ($certificates as $certificate): ?>
                    <?php if ($certificate->isPrimaryDoc()): ?>
                        <?php $this->renderCertificate($certificate); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="list-block">
        <strong class="list-block__item list-block--title"><?= Yii::t('SeamanModule/document', 'Рабочие дипломы') ?></strong>
        <?php if ($competencyEmpty) : ?>
            <?= CHtml::link(Yii::t('SeamanModule/document', 'Добавить диплом'),
                Yii::app()->createUrl('/seaman/documentUser/createDocument'), array('class' => 'list-block__item')) ?>
        <?php endif; ?>
        <div id="competenciesTable">
            <?php if (!$competencyEmpty) : ?>
                <?php foreach ($competencies as $competency):?>
                    <?php $this->renderCompetency($competency) ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="list-block">
        <strong class="list-block__item list-block--title"><?= Yii::t('SeamanModule/document', 'Сертификаты') ?></strong>
        <?php if ($certsEmpty) : ?>
            <?= CHtml::link(Yii::t('SeamanModule/document', 'Добавить сертификат'),
                Yii::app()->createUrl('/seaman/documentUser/createDocument'), array('class' => 'list-block__item')) ?>
        <?php endif; ?>
        <div id="certificatesTable">
            <?php if (!$certsEmpty) : ?>
                <?php foreach ($certificates as $certificate): ?>
                    <?php if (!$certificate->isPrimaryDoc() && ($certificate->is_required || !$certificate->getIsNewRecord() && (!$certificate->deleted || in_array($certificate->sert_id, $checklistCertificates)))) : ?>
                        <?php
                        //Выводим только неудаленные сертификаты, обязательные и те, что требуются по чек-листу
                        $this->renderCertificate($certificate);
                        ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>


    </div>
</div>
