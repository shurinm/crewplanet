<?php

/**
 * Форма загрузки новых изображений в редактор документов
 */
class DocumentManagerUploadNewFile extends CFormModel
{
    /**
     * Тип документа - сертификат
     */
    const DOCUMENT_TYPE_CERT = 'cert';

    /**
     * Тип документа - диплом
     */
    const DOCUMENT_TYPE_COMPETENCY = 'competency';

    /**
     * @var integer Идентификатор моряка, для которого меняем документы
     */
    public $seamanId;

    /**
     * @var string Тип документа (cert|competency)
     */
    public $documentType;

    /**
     * @var integer Идентификатор документа (null, если для нового документа)
     */
    public $documentId;

    /**
     * @var CUploadedFile Загружаемые файлы
     */
    public $files;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array(
            array('seamanId, documentType', 'required'),
            array('seamanId', 'numerical', 'integerOnly' => true, 'min' => 1),
            array('documentId', 'numerical', 'integerOnly' => true),
            array('documentType', 'in', 'range' => array(self::DOCUMENT_TYPE_CERT, self::DOCUMENT_TYPE_COMPETENCY)),
            array('files', 'file',
                'allowEmpty' => false,
                'types' => array('gif', 'jpeg', 'jpg', 'png', 'pdf'),
                'mimeTypes' => array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'),
                'maxFiles' => 24,
            )
        );
    }
}
