<?php

/**
 * Форма редактирования скана документа
 */
class DocumentManagerFileForm extends CFormModel
{
    /**
     * @var integer Идентификатор моряка
     */
    public $seamanId;

    /**
     * @var integer Идентификатор файла, который редактируем
     */
    public $fileId;

    /**
     * @var string Закодированное в base64 обновленное изображение
     */
    public $imageData;

    /**
     * @var string Данные для кропа изображения
     */
    public $cropData;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array(
            array('seamanId, fileId', 'required'),
            array('seamanId, fileId', 'numerical', 'integerOnly' => true, 'min' => 1),
            array('imageData, cropData', 'safe'),
        );
    }

    /**
     * Получить модель моряка, если она существует
     *
     * @return ModSeaman|null
     */
    public function getSeaman()
    {
        if ($this->validate(array('seamanId'))) {
            return ModSeaman::model()->findByPk($this->seamanId);
        }

        return null;
    }

    /**
     * Получить модель файла, если он существует
     *
     * @return ModSeamanFiles|null
     */
    public function getFile()
    {
        if ($this->validate(array('seamanId', 'fileId'))) {
            return ModSeamanFiles::model()->find('seaman_id = :seamanId and id = :fileId', array(
                ':seamanId' => $this->seamanId,
                ':fileId' => $this->fileId,
            ));
        }

        return null;
    }
}
