<?php

/**
 * @var int $certId
 * @var int $competencyId
 * @var int $certTypeId
 * @var string $competencyType
 * @var array $documentTypes
 */

print CHtml::openTag('div', array('class' => 'alert alert-info js-document-form-success-block', 'style' => 'display: none;'));
print Yii::t('SeamanModule/documents', 'Документ успешно создан!');
echo '<br>';
print Yii::t('SeamanModule/documents', 'Вы можете увидеть его в левой колонке. Перейдите в {documents} чтобы увидеть полный список.', array(
    '{documents}' => CHtml::link(Yii::t('SeamanModule/documents', 'Документы'), Yii::app()->createUrl('/seaman/documentUser/index'))
));
echo '<br>';
print CHtml::link(Yii::t('SeamanModule/documents', 'Добавить новый документ'), Yii::app()->createUrl('/seaman/documentUser/createDocument'));
print CHtml::closeTag('div'); ?>
<div class="pull-right btn-group in-document">
    <?php

    if (empty($certId) && empty($competencyId)) {
        print CHtml::button(Yii::t('SeamanModule/documents', 'Отмена'), array(
            'class' => 'btn js-back-button',
            'onclick' => new CJavaScriptExpression('seamanDocumentForm.cancel()'),
        ));
    }

    if (!empty($certId) || !empty($competencyId)) {
        print CHtml::link('<i class="icon-trash"></i> ' . Yii::t('SeamanModule/documents', 'Удалить'), '#', array(
            'class' => 'js-delete-button btn pull-right',
            'style' => 'display: none;',
            'onclick' => new CJavaScriptExpression('seamanDocumentForm.delete()'),
        ));
    }
    ?>
</div>
<div class="js-document-form-container">
    <?php if (empty($certId) && empty($competencyId)): ?>
        <h1><?= Yii::t('SeamanModule/documents', 'Создание документа') ?></h1>
    <?php endif; ?>

    <div class="step step--first" style="<?= !empty($certId) || !empty($competencyId) ? 'display: none;' : '' ?>">
        <h3><strong><?= Yii::t('SeamanModule/documents', 'Шаг 1.') ?></strong> <?= Yii::t('SeamanModule/documents', 'Выберите тип документа') ?></h3>
        <?php

        $dropDownValue = !empty($certTypeId) ? $certTypeId : '';
        if (empty($dropDownValue) && !empty($competencyType)) {
            $dropDownValue = $competencyType;
        }

        print CHtml::dropDownList('certificates', $dropDownValue, $documentTypes, array(
            'class' => 'js-document-types',
            'style' => 'width: 448px',
            'prompt' => '',
            'id' => 'document-type-select'
        ));
        $this->widget('ext.widgets.select2.Select2', array(
            'selector' => '#document-type-select',
        ));
        ?>
    </div>
    <div id="document-form-container" class="js-information-container"></div>
    <div class="wrap-btn-finish">
        <?php
        print CHtml::button(Yii::t('SeamanModule/documents', 'Сохранить изменения'), array(
            'class' => 'js-save-button btn btn-primary',
            'style' => 'display: none;',
            'onclick' => new CJavaScriptExpression('seamanDocumentForm.save()'),
        ));
        ?>
    </div>

</div>
