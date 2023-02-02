<?php

/** @var SeamanDocumentsListWidget $this */
/** @var ModSeaman $seaman */
/** @var ModSeamanCompetency[] $competencies */
/** @var ModSeamanSertificate[] $certificates */
/** @var CntContract|null $contract */
/** @var Checklist|null $checklist */
/** @var string|null $position */
$this->widget('ext.widgets.uikit.uikit');
if(Yii::app()->authManager->isAnketaOwner($seaman->id) || Yii::app()->authManager->hasAccessToSeaman($seaman->id)){
            }else{
    foreach($certificates as $key => $value){
        $value['nomer'] = '';
    }
}
?>
<?php if (Yii::app()->authManager->isMembership()):?>
    <?php $this->renderChecklistInfo(); ?>
<?php endif;?>

<table class="table table-condensed js-table-condensed documents table-condensed--small">
    <tbody id="competenciesTable">
        <tr>
            <th>
                <span class="chevron-up js-open-detail"></span>
                <?= Yii::t('SeamanModule/document', 'Рабочие дипломы') ?>
                <?php if ($contract && Yii::app()->authManager->isMembership()):?>
                    <?= CHtml::link('<i class="icon-plus"></i>', '#', array(
                        'class' => 'btn btn-mini js-toggle-seaman-competency-form',
                        'data-seaman-id' => $contract->seaman_id,
                        'title' => Yii::t('SeamanModule/document', 'Добавить рабочий диплом'),
                        'data-google-tracking' => 'Страница контракта|Добавление рабочего диплома|документы|' . $contract->getNumericalStatus()
                    )) ?>
                <?php endif;?>
            </th>
            <th><?= Yii::t('SeamanModule/document', 'Номер') ?></th>
            <th><?= Yii::t('SeamanModule/document', 'Выдан') ?></th>
            <th><?= Yii::t('SeamanModule/document', 'Истекает') ?></th>
            <th><?= Yii::t('SeamanModule/document', 'Страна') ?></th>
            <th><?= Yii::t('SeamanModule/document', 'Файлы') ?></th>
        </tr>
        <?php foreach ($competencies as $competency):?>
            <?php $this->renderCompetency($competency) ?>
        <?php endforeach;?>
        <tr>
            <th colspan="6" class="highlight_absent js-toggle-no-competencies"<?php if (count($competencies) != 0):?> style="display: none;"<?php endif;?>>
                <?= Yii::t('SeamanModule/document', 'В анкете нет ни одного рабочего диплома') ?>
            </th>
        </tr>
    </tbody>
    <tbody id="documentsTable">
        <tr>
            <th>
                <span class="chevron-up js-open-detail"></span>
                <?= Yii::t('SeamanModule/document', 'Документы') ?>
            </th>
            <th><?= Yii::t('SeamanModule/document', 'Номер') ?></th>
            <th><?= Yii::t('SeamanModule/document', 'Выдан') ?></th>
            <th><?= Yii::t('SeamanModule/document', 'Истекает') ?></th>
            <th><?= Yii::t('SeamanModule/document', 'Страна') ?></th>
            <th><?= Yii::t('SeamanModule/document', 'Файлы') ?></th>
        </tr>
        <?php foreach ($certificates as $certificate):?>
            <?php if ($certificate->isPrimaryDoc()):?>
                <?php $this->renderCertificate($certificate); ?>
            <?php endif;?>
        <?php endforeach;?>
    </tbody>
    <tbody id="certificatesTable">
        <tr>
            <th colspan="2">
                <span class="chevron-up js-open-detail"></span>
                <?= Yii::t('SeamanModule/document', 'Сертификаты') ?>

                <?php if ($contract && Yii::app()->authManager->isMembership()):?>
                    <?= CHtml::link('<i class="icon-plus"></i>', '#', array(
                        'class'=>"btn btn-mini js-toggle-seaman-cert-form",
                        'data-seaman-id' => $contract->seaman_id,
                        'data-contract-id' => $contract->Id,
                        'title' => Yii::t('SeamanModule/document', 'Добавить сертификат'),
                        'data-google-tracking' => 'Страница контракта|Добавление сертификата|документы|'.$contract->getNumericalStatus()
                    )) ?>
                <?php endif;?>
            </th>
            <th><?= Yii::t('SeamanModule/document', 'Выдан') ?></th>
            <th><?= Yii::t('SeamanModule/document', 'Истекает') ?></th>
            <th></th>
            <th><?= Yii::t('SeamanModule/document', 'Файлы') ?></th>
        </tr>
        <?php foreach ($certificates as $certificate):?>
            <?php if (!$certificate->isPrimaryDoc() && in_array($certificate->sert_id, $this->requiredCertsIds)):?>
                <?php $this->renderCertificate($certificate); ?>
            <?php endif;?>
        <?php endforeach;?>
    </tbody>
    <tbody id="othersTable" class="js-hide-td">
        <tr>
            <th colspan="2">
                <span class="chevron-up js-open-detail open"></span>
                <?= Yii::t('SeamanModule/document', 'Другие документы') ?>

                <?php if ($contract && Yii::app()->authManager->isMembership()):?>
                    <?= CHtml::link('<i class="icon-plus"></i>', '#', array(
                        'class'=>"btn btn-mini js-toggle-seaman-cert-form",
                        'data-seaman-id' => $contract->seaman_id,
                        'data-contract-id' => $contract->Id,
                        'title' => Yii::t('SeamanModule/document', 'Добавить сертификат'),
                        'data-google-tracking' => 'Страница контракта|Добавление сертификата|документы|'.$contract->getNumericalStatus()
                    )) ?>
                <?php endif;?>
            </th>
            <th><?= Yii::t('SeamanModule/document', 'Выдан') ?></th>
            <th><?= Yii::t('SeamanModule/document', 'Истекает') ?></th>
            <th></th>
            <th><?= Yii::t('SeamanModule/document', 'Файлы') ?></th>
        </tr>
        <?php foreach ($certificates as $certificate):?>
            <?php if (!$certificate->isPrimaryDoc() && !in_array($certificate->sert_id, $this->requiredCertsIds)):?>
                <?php $this->renderCertificate($certificate); ?>
            <?php endif;?>
        <?php endforeach;?>
    </tbody>
</table>
