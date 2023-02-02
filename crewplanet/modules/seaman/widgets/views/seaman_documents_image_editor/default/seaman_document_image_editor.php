<?php

/** @var SeamanDocumentImageEditorWidget $this */
/** @var DocumentManagerFileForm $updateFileForm */
/** @var DocumentManagerUploadNewFile $newFileForm */
?>
<div style="display:none;">
    <?php
    /** @var CActiveForm $form */
    $form = $this->beginWidget('CActiveForm', array(
        'method' => 'post',
        'action' => '#', // с открытым URL, это может быть либо удаление, либо редактирование файла
        'htmlOptions' => array(
            'class' => 'js-file-form',
        )
    ));
    print $form->hiddenField($updateFileForm, 'seamanId');
    print $form->hiddenField($updateFileForm, 'imageData', array(
        'class' => 'js-file-image-data'
    ));
    print $form->hiddenField($updateFileForm, 'cropData', array(
        'class' => 'js-file-crop-data'
    ));
    print $form->hiddenField($updateFileForm, 'fileId', array(
        'class' => 'js-file-id',
    ));
    $this->endWidget();
    ?>
</div>
<div class="column-image column-image--modal js-state-dnd">
    <?php if (!empty($title)) : ?>
        <div>
            <?= CHtml::tag('h2', array(), $title) ?>
        </div>
    <?php endif; ?>

    <div class="column-image__title" style="display: none;">upload</div>
    <div class="column-image__subtitle">Please, follow three simple rules:</div>
    <ol class="column-image__ol">
        <li>English language document.</li>
        <li>Easy to read.</li>
        <li>One document per image.</li>
    </ol>
    <?php
    /** @var CActiveForm $form */
    $form = $this->beginWidget('CActiveForm', array(
        'method' => 'post',
        'action' => '#',
        'htmlOptions' => array(
            'enctype' => 'multipart/form-data',
            'class' => 'js-upload-new-file-form',
        )
    ));
    ?>
        <?= $form->hiddenField($newFileForm, 'seamanId') ?>
        <?= $form->hiddenField($newFileForm, 'documentType') ?>
        <?= $form->hiddenField($newFileForm, 'documentId') ?>

        <div class="load-place js-drag-n-drop-place">

            <img src="/themes/crewplanet/img/iconset/icon-upload-big.png" class="upload-icon"/>
            <div class="load-place__title">Drag & Drop images here <br> to upload it</div>
            <div class="load-place__or">or</div>
            <button class="btn load-place__btn js-drag-n-drop-file">Browse...
                <a class="load-place__loader js-file-link-loader" style="display:none;" href="#"><span><i class="loader fa fa-spinner" aria-hidden="true"></i></span></a>
            </button>
            <?= $form->fileField($newFileForm, 'files[]', array(
                'class' => 'hide-element',
                'multiple' => 'multiple',
            )) ?>
        </div>
    <?php $this->endWidget(); ?>
</div>
<div class="column-image column-image--modal js-state-crop">
    <div class="column-image__title js-crop-place-title" style="display: none;"></div>
    <div class="column-image__image">
        <img src="" class="js-crop-place-image"/>
    </div>
    <div class="btn-toolbar crop-place__buttons">

        <div class="btn-default-group btn-default-step1 js-group-btn-step-one">
            <button class="btn btn-success js-crop-edit">Edit photo</button>
            <button class="btn btn-default js-crop-delete">Delete</button>
        </div>
        <div class="wrap-group-btns clearfix js-wrap-group-btns">
            <div class="pull-left">
                <div class="btn-group crop-place__small-btn">
                    <button class="btn btn-default js-crop-move"><span class="fa fa-arrows"></span></button>
                    <button class="btn btn-default js-crop-crop"><span class="fa fa-crop"></span></button>
                    <button class="btn btn-default js-crop-zoom-in"><span class="fa fa-search-plus"></span></button>
                    <button class="btn btn-default js-crop-zoom-out"><span class="fa fa-search-minus"></span></button>
                    <button class="btn btn-default js-crop-rotate-left"><span class="fa fa-rotate-left"></span></button>
                    <button class="btn btn-default js-crop-rotate-right"><span class="fa fa-rotate-right"></span></button>
                </div>
            </div>

            <div class="btn-default-group pull-right wrap-group-control">
                <button class="btn btn-success js-crop-save">Save</button>
                <button class="btn js-crop-restore">Restore</button>
                <button class="btn js-crop-cancel">Cancel</button>
            </div>
        </div>
    </div>
</div>
