<?php
Yii::import('zii.widgets.jui.*');

/**
 * Виджет враппера модального окна для редактирования и создания документов
 */
class SeamanDocumentModalWidget extends CJuiDialog
{
    /**
     * @var array Опции по умолчанию
     */
    public $options = array(
        'title' => '',
        'autoOpen' => false,
        'modal' => true,
        'resizable' => false,
        'width' => 500,
        'position' => array(
            '', 150,
        ),
    );

    /**
     * @inheritdoc
     */
    public function init()
    {
        // подтянуть автолоад модуля
        Yii::app()->getModule('seaman');

        if (empty($this->options['buttons'])) {
            // сгенерировать кнопки
            $this->options['buttons'] = array(
                array(
                    'text' => Yii::t('SeamanModule/document', 'Сохранить изменения'),
                    'click' => new CJavaScriptExpression('seamanDocumentModal.save'),
                    'class' => 'js-save-button btn btn-success',
                ),
                array(
                    'text' => Yii::t('SeamanModule/document', 'Отменить изменения'),
                    'click' => new CJavaScriptExpression('seamanDocumentModal.decline'),
                    'class' => 'js-decline-button btn',
                ),
                array(
                    'text' => Yii::t('SeamanModule/document', 'Удалить'),
                    'click' => new CJavaScriptExpression('seamanDocumentModal.delete'),
                    'class' => 'js-delete-button btn',
                ),
                array(
                    'text' => Yii::t('SeamanModule/document', 'Отмена'),
                    'click' => new CJavaScriptExpression('seamanDocumentModal.cancel'),
                    'class' => 'js-cancel-button btn',
                ),
            );
        }
        parent::init();
        $this->registerScripts();
        print CHtml::tag('div', array(
            'class' => 'js-dialog-content wrap-dialog-content',
        ));
    }

    /**
     * Закрываем .js-dialog-content
     * @inheritdoc
     */
    public function run()
    {
        print CHtml::closeTag('div');
        parent::run();
    }

    /**
     * Получить путь до assets
     *
     * @return string
     */
    protected function getAssetsPath()
    {
        $seamanModuleBasePath = Yii::app()->getModule('seaman')->getBasePath();
        return Yii::app()->assetManager->publish($seamanModuleBasePath . '/assets', false, -1, defined('YII_DEBUG'));
    }

    /**
     * Зарегистрировать скрипты для работы виджета
     */
    public function registerScripts()
    {
        /** @var CClientScript $clientScript */
        $clientScript = Yii::app()->getClientScript();
        // модальное окно работает на jquery.uikit
        $this->widget('ext.widgets.uikit.uikit');
        // подключить главный скрипт модального окна
        $clientScript->registerScriptFile($this->getAssetsPath() . '/seamanDocumentModal.js');
        // установить урлы, требуемые для работы модалки
        $urls = array(
            'certificateUrl' => Yii::app()->createUrl('seaman/documentManager/certForm'),
            'deleteCertificateUrl' => Yii::app()->createUrl('seaman/documentManager/certRemove'),
            'competencyUrl' => Yii::app()->createUrl('seaman/documentManager/competencyForm'),
            'deleteCompetencyUrl' => Yii::app()->createUrl('seaman/documentManager/competencyRemove'),
            'toggleCheckboxCertUrl' => Yii::app()->createUrl('seaman/documentManager/certToggleBool'),
        );
        // установить типы сертификатов по ID
        $certTypesById = array();
        $certificates = Certificate::model()->findAll();
        foreach ($certificates as $certificate) {
            /** @var Certificate $certificate */
            $certTypesById[$certificate->id] = $certificate->type;
        }
        $clientScript->registerScript('seaman-document-modal-widget-urls', new CJavaScriptExpression('seamanDocumentModal.urls = ' . CJSON::encode($urls) . ';'));
        $clientScript->registerScript('seaman-document-modal-widget-cert-types', new CJavaScriptExpression('seamanDocumentModal.certTypesById = ' . CJSON::encode($certTypesById) . ';'));
        $clientScript->registerScript('seaman-document-modal-widget-init', new CJavaScriptExpression('seamanDocumentModal.init("' . $this->id . '");'));

        // этот плагин нужен для субмита форм с файлами по AJAX
        $clientScript->registerScriptFile('/themes/crewplanet/js/jquery.form.js');

        // данный виджет зависит от редактора изображений, который подгружается по AJAX
        // скрипты редактора изображений регистрируем заранее
        /** @var SeamanDocumentImageEditorWidget $seamanDocumentsImage */
        $seamanDocumentsImage = $this->createWidget('application.modules.seaman.widgets.SeamanDocumentImageEditorWidget', array(
            'dialogContainerSelector' => '#' . $this->id,
        ));
        $seamanDocumentsImage->registerScripts();
    }
}
