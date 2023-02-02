<?php

/**
 * Виджет формы создания/редактирования документов моряка
 */
class SeamanDocumentFormWidget extends CWidget
{
    /**
     * @var ModSeaman Моряк для которого создаются документы
     */
    public $seaman;

    /**
     * @var array Типы документов
     */
    public $documentTypes;

    /**
     * @var int Идентификатор сертификата
     */
    public $certId;

    /**
     * @var int Идентификатор типа сертификата
     */
    public $certTypeId;

    /**
     * @var int Иднетификатор диплома
     */
    public $competencyId;

    /**
     * @var boolean Конвенционный/не конвенционный диплом
     */
    public $isConventional;

    /**
     * @var string
     */
    protected $competencyType;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!is_null($this->isConventional)) {
            $this->competencyType = $this->isConventional ? 'conventional' : 'non-conventional';
        }
        $this->registerScripts();
    }

    /**
     *
     * @inheritdoc
     */
    public function run()
    {
        $this->render('seaman_document_form/index', array(
            'certId' => $this->certId,
            'competencyId' => $this->competencyId,
            'certTypeId' => $this->certTypeId,
            'competencyType' => $this->competencyType,
            'documentTypes' => $this->documentTypes
        ));
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

        $clientScript->registerScriptFile($this->getAssetsPath() . '/seamanDocumentForm.js');
        $urls = array(
            'certificateUrl' => Yii::app()->createUrl('seaman/documentUser/certForm'),
            'deleteCertificateUrl' => Yii::app()->createUrl('seaman/documentUser/certRemove'),
            'competencyUrl' => Yii::app()->createUrl('seaman/documentUser/competencyForm'),
            'deleteCompetencyUrl' => Yii::app()->createUrl('seaman/documentUser/competencyRemove'),
            'toggleCheckboxCertUrl' => Yii::app()->createUrl('seaman/documentUser/certToggleBool'),
            'documentListUrl' => Yii::app()->createUrl('seaman/documentUser/index'),
        );
        // установить типы сертификатов по ID
        $certTypesById = array();
        $certificates = Certificate::model()->findAll();
        foreach ($certificates as $certificate) {
            /** @var Certificate $certificate */
            $certTypesById[$certificate->id] = $certificate->type;
        }
        $clientScript->registerScript('seaman-document-form-widget-urls', new CJavaScriptExpression('seamanDocumentForm.urls = ' . CJSON::encode($urls) . ';'));
        $clientScript->registerScript('seaman-document-form-widget-cert-types', new CJavaScriptExpression('seamanDocumentForm.certTypesById = ' . CJSON::encode($certTypesById) . ';'));
        $clientScript->registerScript('seaman-document-form-widget-exists-id', new CJavaScriptExpression(
            'seamanDocumentForm.certId = ' . CJSON::encode($this->certId) . ';' .
            'seamanDocumentForm.certTypeId = ' . CJSON::encode($this->certTypeId) . ';' .
            'seamanDocumentForm.competencyType = ' . CJSON::encode($this->competencyType) . ';' .
            'seamanDocumentForm.competencyId = ' . CJSON::encode($this->competencyId) . ';'));
        $clientScript->registerScript('seaman-document-form',
            new CJavaScriptExpression('seamanDocumentForm.init("document-form-container", ' . $this->seaman->id .');')
        );
        /** @var SeamanDocumentImageEditorWidget $seamanDocumentsImage */
        $seamanDocumentsImage = $this->createWidget('application.modules.seaman.widgets.SeamanDocumentImageEditorWidget', array(
            'dialogContainerSelector' => '#document-form-container',
            'controllerId' => 'seaman/documentUser',
        ));
        $seamanDocumentsImage->registerScripts();
        $this->widget('ext.widgets.uikit.uikit');
        // этот плагин нужен для субмита форм с файлами по AJAX
        $clientScript->registerScriptFile('/themes/crewplanet/js/jquery.form.js');
    }
}
