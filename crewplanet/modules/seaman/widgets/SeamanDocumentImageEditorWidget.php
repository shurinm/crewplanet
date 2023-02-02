<?php

/**
 * Редактирование изображений документов внутри модального окна документа
 *
 * Работает вместе с SeamanDocumentModalWidget
 */
class SeamanDocumentImageEditorWidget extends CWidget
{
    /**
     * @var string Шаблон для вывода (см. в папке views/seaman_documents)
     */
    public $template = 'default';

    /**
     * @var string Селектор контента диалогового окна
     */
    public $dialogContainerSelector = '.js-dialog-content';

    /**
     * @var integer Идентификатор моряка, с которым работаем
     */
    public $seamanId;

    /**
     * @var string Тип документа (cert|competency)
     */
    public $documentType;

    /**
     * @var integer Идентификатор документа
     */
    public $documentId;

    /**
     * @var string Заголовок виджета
     */
    public $title;

    /**
     * @var string ID контроллера для загрузки/обновления/удаления файлов
     */
    public $controllerId = 'seaman/documentManager';

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
     * Регистрация скриптов и стилей
     */
    public function registerScripts()
    {
        /** @var CClientScript $clientScript */
        $clientScript = Yii::app()->getClientScript();

        $assetsPath = $this->getAssetsPath();

        $clientScript->registerCssFile($assetsPath . '/document-manager-style/font-awesome.min.css');
        $clientScript->registerCssFile($assetsPath . '/document-manager-style/planning.css');
        $clientScript->registerCssFile($assetsPath . '/cropper/dist/cropper.min.css');
        $clientScript->registerScriptFile($assetsPath . '/cropper/dist/cropper.min.js');
        $clientScript->registerScriptFile($assetsPath . '/cropImage.js');
        $clientScript->registerScriptFile($assetsPath . '/dndFileLoad.js');

        // главный скрипт, который инициализирует все вышеперечисленные и навешивает клики
        $urls = array(
            'uploadFileUrl' => Yii::app()->createUrl($this->controllerId . '/uploadFile'),
            'removeFileUrl' => Yii::app()->createUrl($this->controllerId . '/removeFile'),
            'updateFileUrl' => Yii::app()->createUrl($this->controllerId . '/updateFile'),
        );
        $clientScript->registerScriptFile($assetsPath . '/seamanDocumentImageEditor.js');
        $clientScript->registerScript('seaman-document-image-editor-init', new CJavaScriptExpression('seamanDocumentImageEditor.init("' . $this->dialogContainerSelector . '", ' . CJSON::encode($urls) . ');'));
    }

    /**
     * Рендер виджета
     */
    public function run()
    {
        $this->registerScripts();

        // форма загрузки нового файла
        $newFileForm = new DocumentManagerUploadNewFile();
        $newFileForm->seamanId = $this->seamanId;
        $newFileForm->documentId = $this->documentId;
        $newFileForm->documentType = $this->documentType;

        // форма редактирования файлов
        // оставляем с пустым идентификатором файла, будет проставляться автоматически в seamanDocumentImageEditor.js
        $updateFileForm = new DocumentManagerFileForm();
        $updateFileForm->seamanId = $this->seamanId;

        $this->render('seaman_document_image_editor', array(
            'newFileForm' => $newFileForm,
            'title' => $this->title,
            'updateFileForm' => $updateFileForm,
        ));
    }

    /**
     * @inheritdoc
     */
    public function getViewPath($checkTheme = false)
    {
        return parent::getViewPath($checkTheme) . DIRECTORY_SEPARATOR . 'seaman_documents_image_editor' . DIRECTORY_SEPARATOR . $this->template;
    }
}
