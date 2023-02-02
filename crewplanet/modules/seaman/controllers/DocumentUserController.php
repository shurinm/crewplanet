<?php
Yii::import('seaman.controllers.DocumentManagerController');

/**
 * Контроллер для управления документами моряка.
 */
class DocumentUserController extends DocumentManagerController
{

    /**
     * Сертификат, который показываем по умолчанию - Medical Fitness Certificate
     */
    const DEFAULT_CERT_ID = 430;

    /**
     * @inheritdoc
     */
    public function accessRules()
    {
        return array_merge(array(
            array(
                'allow',
                'roles' => array('roleSeaman'),
                'message' => Yii::t('SeamanModule._site', 'Доступ закрыт'),
            ),
        ), parent::accessRules());
    }

    /**
     * @inheritdoc
     */
    public function filters()
    {
        return array_merge(array(
            'currentSeamanOnly'
        ), parent::filters());
    }

    /**
     * Запрещаем редактировать других моряков
     * @param CFilterChain $filterChain
     * @throws CHttpException
     */
    public function filterCurrentSeamanOnly($filterChain)
    {
        $seamanId = Yii::app()->request->getParam('seamanId');
        if (!empty($seamanId) && Yii::app()->user->id != $seamanId) {
            throw new CHttpException(403);
        } else {
            $filterChain->run();
        }
    }

    /**
     * Редиректим на страницу создания/редактирования Medical Fitness Certificate
     */
    public function actionIndex()
    {
        /** @var ModSeaman $seaman */
        $seaman = ModSeaman::model()->findByPk(Yii::app()->user->id);
        if (!($seaman instanceof ModSeaman)) {
            throw new CHttpException(404);
        }
        $cert = ModSeamanSertificate::model()->find('seaman_id = :seamanId and sert_id = :certTypeId and deleted=0', array(
            ':seamanId' => $seaman->getPrimaryKey(),
            ':certTypeId' => self::DEFAULT_CERT_ID,
        ));
        $route = array('createDocument', 'certTypeId' => self::DEFAULT_CERT_ID);
        if ($cert instanceof ModSeamanSertificate) {
            $route['certId'] = $cert->getPrimaryKey();
        }
        $this->redirect($route);
    }

    /**
     * Создание документа
     * @param int $certId
     * @param int $certTypeId
     * @param int $competencyId
     * @param int $isConventional
     * @throws CHttpException
     */
    public function actionCreateDocument($certId = null, $certTypeId = null, $competencyId = null, $isConventional = null)
    {
        /** @var ModSeaman $seaman */
        $seaman = ModSeaman::model()->findByPk(Yii::app()->user->id);
        if (!($seaman instanceof ModSeaman)) {
            throw new CHttpException(404);
        }
        /** @var CntContract $contract */
        $contract = CntContract::model()->resetScope()->find(array(
            'condition' => "seaman_id = :sId AND status = '".  CntContract::STATUS_PLANNED . "'",
            'params' => array(':sId' => $seaman->id),
            'order' => 'date_start desc'
        ));
        /** @var Checklist $checklist */
        $checklist = null;
        if ($contract instanceof CntContract && $contract->ship instanceof VacShip) {
            $checklist = Checklist::model()->resetScope()->find('id = :cid', array(':cid' => $contract->ship->checklist_id));
        }

        if (!empty($competencyId)) {
            /** @var ModSeamanCompetency $competency */
            $competency = ModSeamanCompetency::model()->find('id = :competencyId and seaman_id = :seamanId', array(
                ':competencyId' => $competencyId,
                ':seamanId' => $seaman->getPrimaryKey(),
            ));

            if (!$competency instanceof ModSeamanCompetency) {
                throw new CHttpException(404);
            }
        }

        if (!empty($certId)) {
            $cert = ModSeamanSertificate::model()->find('seaman_id = :seamanId and id = :certId', array(
                ':seamanId' => $seaman->getPrimaryKey(),
                ':certId' => $certId,
            ));
            if (!$cert instanceof ModSeamanSertificate) {
                throw new CHttpException(404);
            }
        }

        $this->render('create', array(
            'seaman' => $seaman,
            'documentTypes' => $this->getDocumentTypesDropDown($seaman),
            'contract' => $contract,
            'checklist' => $checklist,
            'certId' => $certId,
            'certTypeId' => $certTypeId,
            'competencyId' => $competencyId,
            'isConventional' => $isConventional,
        ));
    }

    /**
     * Возвращает полный список документов для дропдауна
     * @param ModSeaman $seaman
     * @return array
     */
    protected function getDocumentTypesDropDown(ModSeaman $seaman)
    {
        $data = array();

        $existsIds = array();
        foreach ($seaman->certificates as $certificate) {
            if (!$certificate->deleted) {
                $existsIds[] = $certificate->sert_id;
            }
        }
        $criteria = new CDbCriteria();
        if (!empty($existsIds)) {
            $criteria->addNotInCondition('id', $existsIds);
        }
        $criteria->addCondition('is_custom = 0');
        $availCertificates = Certificate::model()->findAll($criteria);
        usort($availCertificates, function($a, $b) {
            $aSort = $a->sort_order;
            $bSort = $b->sort_order;
            if ($aSort == $bSort) {
                return strcmp($a->long_title, $b->long_title);
            }
            if ($aSort == 0) {
                return 1;
            }
            if ($bSort == 0) {
                return -1;
            }
            return ($aSort < $bSort) ? -1 : 1;
        });
        $mainTypes = array('visa', 'seamans book', 'passport');
        foreach ($availCertificates as $certificate) {
            if (in_array($certificate->type, $mainTypes)) {
                $data[Yii::t('SeamanModule/document', 'Паспорта, визы')][$certificate->id] = $certificate->long_title;
            }
        }
        $data[Yii::t('SeamanModule/document', 'Рабочие дипломы')] = array(
            'non-conventional' => Yii::t('SeamanModule/document', 'Не конвенционный'),
            'conventional' => Yii::t('SeamanModule/document', 'Конвенционный'),
        );
        foreach ($availCertificates as $certificate) {
            if (!in_array($certificate->type, $mainTypes)) {
                $data[Yii::t('SeamanModule/document', 'Сертификаты')][$certificate->id] = $certificate->long_title;
            }
        }

        return $data;
    }

    /**
     * Получить строку сертификата для таблицы документов на странице списка документов
     *
     * @param integer $seamanId Идентификатор моряка
     * @param string $template Шаблон для вывода строки
     *
     * @throws CHttpException
     */
    public function actionGetDocumentsTable($seamanId, $template = 'default')
    {
        /** @var ModSeaman $seaman */
        $seaman = ModSeaman::model()->findByPk($seamanId);
        if (!$seaman instanceof ModSeaman) {
            throw new CHttpException(404);
        }
        /** @var CntContract $contract */
        $contract = CntContract::model()->resetScope()->find(array(
            'condition' => "seaman_id = :sId AND status = '".  CntContract::STATUS_PLANNED . "'",
            'params' => array(':sId' => $seaman->id),
            'order' => 'date_start desc'
        ));
        /** @var Checklist $checklist */
        $checklist = null;
        if ($contract instanceof CntContract && $contract->ship instanceof VacShip) {
            $checklist = Checklist::model()->resetScope()->find('id = :cid', array(':cid' => $contract->ship->checklist_id));
        }

        echo CJSON::encode(array(
            'html' => $this->renderPartial('_list_widget', array(
                'contract' => $contract,
                'seaman' => $seaman,
                'checklist' => $checklist
            ), true)
        ));
        Yii::app()->end();
    }



    /**
     * Удаление скана документа.
     *
     * Обработка запроса производится через форму DocumentManagerFileForm
     *
     * @see DocumentManagerFileForm
     */
    public function actionRemoveFile()
    {
        /** @var ModSeaman $seaman */
        $seaman = ModSeaman::model()->findByPk(Yii::app()->user->id);
        if (!($seaman instanceof ModSeaman)) {
            throw new CHttpException(404);
        }
        $ids = Yii::app()->request->getPost('ids');

        $result['success'] = false;
        if (!empty($ids)) {
            $result['success'] = true;
            $criteria = new CDbCriteria();
            $criteria->addInCondition('id', $ids);
            $criteria->addCondition(array('seaman_id' => $seaman->getPrimaryKey()));
            $files = ModSeamanFiles::model()->findAll($criteria);
            foreach ($files as $file) {
                $result['success'] = $result['success'] && $this->deleteFile($file);
            }
        }

        echo CJSON::encode($result);
        Yii::app()->end();
    }

    /**
     * После сохранения делаем пометку в документе о том, что изменился список файлов
     * @inheritdoc
     */
    protected function deleteFile(ModSeamanFiles $file) {
        if ($file->table_name == ModSeamanFiles::TABLE_CERT)  {
            $doc = ModSeamanSertificate::model()->findByPk($file->doc_id);
        } elseif ($file->table_name == ModSeamanFiles::TABLE_COMPETENCY) {
            $doc = ModSeamanCompetency::model()->findByPk($file->doc_id);
        }
        $result = parent::deleteFile($file);
        if ($result && !empty($doc)) {
            $doc->setAttributes(array(
                'has_new_files' => true,
                'approved' => false,
            ));
            $doc->save();
        }
        return $result;
    }
}
