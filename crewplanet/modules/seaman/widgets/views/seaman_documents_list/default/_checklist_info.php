<?php

/** @var SeamanDocumentsListWidget $this */
/** @var CntContract|null $contract */
/** @var Checklist|null $checklist */
/** @var string|null $position */
?>
<?php if ($checklist):?>
    <div class="alert">
        <small>
            <?php if ($position):?>
                <?= Yii::t('SeamanModule/document', 'Документы проверяются по чеклисту <a href="{checklist_link}">{checklist}</a> по должности', array(
                        '{checklist_link}' => Yii::app()->createAbsoluteUrl('/checklists/run/index', array(
                            'id' => $checklist->id,
                            'data-google-tracking' => 'Страница контракта|Переход на чеклист|алерт|' . $contract->getNumericalStatus()
                        )),
                        '{checklist}' => $checklist->title
                )) ?>

                <?= CHtml::link($position, '#', array(
                    'class' => 'dialogActivator',
                    'data-field' => 'ClPosition',
                    'data-google-tracking' => 'Страница контракта|Привязка должности в чеклисте|алерт|'.$contract->getNumericalStatus()
                )) ?>

                <?= Yii::t('SeamanModule/document', 'для судна <a href="{vessel_link}">{vessel}</a>', array(
                    '{vessel_link}' => Yii::app()->createAbsoluteUrl('/vacancies/ship/edit/', array(
                        'id' => $contract->vessel,
                    )),
                    '{vessel}' => $contract->vessel_name,
                )) ?>
            <?php else:?>
                <?= Yii::t('SeamanModule/document', 'Судно <a href="{vessel_link}">{vessel}</a> контролируется чеклистом <a href="{checklist_url}">{checklist}</a>, но Вы должны указать по какой должности из чеклиста проверять документы моряка.', array(
                    '{vessel}' => $contract->vessel_name,
                    '{vessel_link}' => Yii::app()->createAbsoluteUrl('/vacancies/ship/edit/', array(
                        'id' => $contract->vessel,
                    )),
                    '{checklist}' => $checklist->title,
                    '{checklist_url}' => Yii::app()->createUrl('/checklists/run/index', array(
                        'id' => $checklist->id,
                        'data-google-tracking' => 'Страница контракта|Переход на чеклист|алерт|'.$contract->getNumericalStatus()
                    ))
                )) ?>
                <br />
                <?= CHtml::link(Yii::t('SeamanModule/document', 'выбрать должность'), '#', array(
                    'data-field' => 'ClPosition',
                    'class' => 'btn btn-small dialogActivator',
                    'data-google-tracking' => 'Страница контракта|Привязка должности в чеклисте|алерт|' . $contract->getNumericalStatus()
                )) ?>
            <?php endif;?>
        </small>
    </div>
<?php else:?>
    <div class="alert alert-warning">
        <small>
            <?php echo Yii::t('SeamanModule/document', '[[текст о том, что проверка по чеклисту не ведется и проверяются только даты готовности]]', array(
                '{vessel}' => $contract->vessel_name,
                '{link}' => Yii::app()->createUrl('/checklists/default/index')
            )); ?>
        </small>
    </div>
<?php endif;?>
