<?php

/**
 * Контроллер для управления документами моряка.
 *
 * Доступ к контроллеру только для агентов, никто другой редактировать документы моряка в данном интерфейсе не может.
 *
 * Есть два вида документов: сертификаты (к ним также относятся паспорт моряка и загран паспорт) и дипломы.
 * В данном интерфейсе редактируются все типы документов.
 */
class DocumentManagerController extends Controller
{
    /**
     * @inheritdoc
     */
    public function accessRules()
    {
        return array_merge(array(
            array(
                'allow',
                'roles' => array('roleAgent'),
                'message' => Yii::t('SeamanModule._site', 'Доступ закрыт'),
            ),
            array('deny',
                'users' => array('*'),
            ),
        ), parent::accessRules());
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        // подгрузить неймспейсы все необходимые дополнительные модули
        Yii::app()->getModule('planning');
        Yii::app()->getModule('checklists');

        parent::init();
    }

    /**
     * Получить типы сертификатов для создания новых сертификатов
     *
     * @param ModSeaman $seaman
     * @param integer|null $contractId Идентификатор контракта, если сертификат создается в рамках модуля планирования
     *
     * @return Certificate[]
     */
    protected function getCertTypesByContract(ModSeaman $seaman, $contractId = null)
    {
        /** @var CntContract $contract */
        $contract = null;
        if ($contractId) {
            $contract = CntContract::model()->findByPk($contractId);
        }

        // если есть контракт - получить все сертификаты, необходимые для контракта и отсеченные по чеклистам
        if ($contract) {
            // для новых сертификатов нужно подготовить массив сертификатов для выпадающего списка
            $availIds = ModSeamanSertificate::getCertificatesAvailableForSeaman($seaman->id, $contract->employer_id);

            // получить чеклист, чтобы удалить из availIds данные о сертификатах, которые и так есть уже на странице планирования
            /** @var VacShip $ship */
            $ship = VacShip::model()->findByPk($contract->vessel);
            if ($ship) {
                $availIds = array_diff($availIds, ModSeamanSertificate::getSeamanCertificatesByChecklist($seaman->id, $ship->checklist->id));
            }

            // получить сертификаты
            return !empty($availIds) ? Certificate::model()->findAllByPk($availIds) : array();
        }

        // если нет контракта - получить абсолютно все сертификаты
        return Certificate::model()->findAll();
    }

    /**
     * Запрос на удаление диплома моряка.
     *
     * Ответ в виде JSON.
     *
     * @param integer $seamanId Идентификатор моряка
     * @param integer $competencyId Идентификатор диплома
     *
     * @throws CHttpException
     */
    public function actionCompetencyRemove($seamanId, $competencyId)
    {
        if (!Yii::app()->request->getIsPostRequest()) {
            throw new CHttpException(403);
        }

        $competency = ModSeamanCompetency::model()->find('id = :competencyId and seaman_id = :seamanId', array(
            ':seamanId' => $seamanId,
            ':competencyId' => $competencyId,
        ));

        /** @var DocumentApi $documentApi */
        $documentApi = Yii::app()->getModule('seaman')->documentApi;

        $result = array(
            'success' => $documentApi->removeCompetency($competency),
        );

        print CJSON::encode($result);
        Yii::app()->end();
    }

    /**
     * Запрос на удаление сертификата моряка.
     *
     * Ответ в виде JSON.
     *
     * @param integer $seamanId Идентификатор моряка
     * @param integer $certId Идентификатор сертификата
     *
     * @throws CHttpException
     */
    public function actionCertRemove($seamanId, $certId)
    {
        if (!Yii::app()->request->getIsPostRequest()) {
            throw new CHttpException(403);
        }

        /** @var ModSeamanSertificate $cert */
        $cert = ModSeamanSertificate::model()->find('id = :certId and seaman_id = :seamanId', array(
            ':seamanId' => $seamanId,
            ':certId' => $certId,
        ));

        /** @var DocumentApi $documentApi */
        $documentApi = Yii::app()->getModule('seaman')->documentApi;
        
        $result = array(
            'success' => $documentApi->removeCertificate($cert),
        );

        print CJSON::encode($result);
        Yii::app()->end();
    }

    /**
     * Отправить сообщение в комет-сервер, для изменения иконок в списке планирования в режиме реального времени
     *
     * @param ModSeaman $seaman
     * @param integer|null $contractId Идентификатор запланированного контракта
     */
    protected function sendMessageToComet(ModSeaman $seaman, $contractId = null)
    {
        /** @var ChecklistsModule $checklistsModule */
        $checklistsModule = Yii::app()->getModule('checklists');
        Yii::app()->getModule('planning');

        // получить контракт
        $contract = $contractId ? CntContract::model()->findByPk($contractId) : null;

        if ($contract) {
            // если не передан идентификатор контракта, то пытаемся получить последний запланированный контракт моряка
            $plannedContracts = $checklistsModule->getPlanningContracts($seaman->id);
            $contract = !empty($plannedContracts) ? $plannedContracts[0] : null;
        }

        if ($contract) {
            // отправить сообщение в комет-сервер для замены иконок в списке планирования
            Yii::app()->cometSend('planningList', 'change data', array(
                'contractId' => $contract->Id,
                'cmd' => 'listSetIcons',
                'params' => array(
                    'newValue' => PlanningModule::getIcons($contract->isPlanned() ? 1 : 2, $contract),
                )
            ), true);
        }
    }

    /**
     * Загрузка формы создания или редактирования диплома.
     *
     * @param integer $seamanId Идентификатор моряка
     * @param integer|null $competencyId Идентификатор диплома (если не передан, то открывается форма создания диплома)
     *
     * @throws CHttpException
     */
    public function actionCompetencyForm($seamanId, $competencyId = null)
    {
        Yii::import('application.modules.checklists.models.*');

        /** @var ModSeaman $seaman */
        $seaman = ModSeaman::model()->findByPk($seamanId);
        if (!$seaman) {
            throw new CHttpException(404);
        }

        $competency = new ModSeamanCompetency();

        // если передан идентификатор документа - получить документ по идентификатору и моряку
        if ($competencyId) {
            $competency = ModSeamanCompetency::model()->find('seaman_id = :seamanId and id = :id', array(
                ':seamanId' => $seamanId,
                ':id' => $competencyId,
            ));
            if (!$competency instanceof ModSeamanCompetency) {
                throw new CHttpException(404);
            }
        } else {
            $competency->seaman_id = $seaman->id;
        }

        /** @var CHttpRequest $request */
        $request = Yii::app()->getRequest();

        //Удаляем временные файлы при открытии формы редактирования/создания
        if (!$request->getIsPostRequest()) {
            $files = ModSeamanFiles::model()->findAllTemporaryBySeamanId($seamanId);
            $files = array_merge($files, ModSeamanFiles::model()->findAllTemporaryNewBySeamanId($seamanId));
            foreach ($files as $file) {
                $file->removeFile();
                $file->delete();
            }
        }

        // пометка, что документ находится в ротации
        // только при этом условии выводим информацию об измененных полях
        $needApproval = SeamanRotation::hasOpenedRotation($competency->seaman_id, Yii::app()->user->parent_id);

        $result = array(
            'mode' => 'competency',
            'competencyId' => !$competency->getIsNewRecord() ? $competency->id : null,
            'seamanId' => $seaman->id,
            'success' => false,
            'deleted' => false,
            'submitted' => false,
            'isApproved' => !$needApproval || $competency->isApproved(),
            'needToDelete' => $competency->needToDelete(),
        );

        if ($request->getIsPostRequest() && ($data = $request->getPost('ModSeamanCompetency', false)) !== false) {
            $result['submitted'] = true;
            $competency->setAttributes($data);
            $isNewRecord = $competency->getIsNewRecord();

            /** @var DocumentApi $documentApi */
            $documentApi = Yii::app()->getModule('seaman')->documentApi;
            // подтверждение удаления диплома
            if (empty($_POST['declineChanges']) && $competency->needToDelete() && !$competency->getIsNewRecord()) {
                $result['competencyId'] = $competency->id;
                $result['deleted'] = $result['success'] = $competency->delete();
            } else {

                $result['success'] = $documentApi->saveCompetency($competency);
                if ($result['success']) {
                    $result['competencyId'] = $competency->id;
                }
            }

            $this->sendMessageToComet($seaman);
        }

        // формирование заголовка окна
        if ($competency->getIsNewRecord()) {
            $result['form_title'] = Yii::t('SeamanModule/document', 'Новый диплом');
        } else {
            $result['form_title'] = $competency->position == 0 ?
                $competency->position_freeenter :
                ModPredefinedData::model()->getValue($competency->position);
        }

        // формирование тела формы
        $result['form'] = $competency->getIsNewRecord() ?
            $this->renderPartial('competency/create', array(
                'model' => $competency,
            ), true) :
            $this->renderPartial('competency/update', array(
                'model' => $competency,
                'needApproval' => $needApproval,
            ), true);

        // кнопки, которые нужно показать в модалке
        $result['buttons'] = array(
            'cancel' => true,
            'save' => true,
            'decline' => $needApproval && !$competency->isApproved() && (!$competency->isNewDocument() || $competency->needToDelete()),
            'delete' => !$competency->getIsNewRecord(),
        );

        echo CJSON::encode($result);
        Yii::app()->end();
    }

    /**
     * Загрузка формы редактирования/создания сертификата по AJAX
     *
     * @param integer $seamanId Идентификатор моряка, документ которого необходимо редактировать
     * @param integer|null $certId Идентификатор сертификата, который требуется редактировать (null, если создается новый сертификат)
     * @param integer|null $certTypeId Идентификатор типа сертификата, который требуется создать (null, если создается рпоизвольный сертификат)
     * @param integer|null $contractId Идентификатор контракта, в рамках которого создается документ (если создается в модуле планирования)
     *
     * @throws CHttpException
     */
    public function actionCertForm($seamanId, $certId = null, $certTypeId = null, $contractId = null)
    {
        Yii::import('application.modules.checklists.models.*');

        /** @var ModSeaman $seaman */
        $seaman = ModSeaman::model()->findByPk($seamanId);
        if (!$seaman) {
            throw new CHttpException(404);
        }

        $cert =  new ModSeamanSertificate();
        if ($certTypeId) {
            $cert->sert_id = $certTypeId;
        }

        // массив сертификатов для создания нового сертификата
        /** @var Certificate[] $availCertificates */
        $availCertificates = array();

        // если передан идентификатор документа - получить документ по идентификатору и моряку
        if ($certId) {
            $cert = ModSeamanSertificate::model()->find('seaman_id = :seamanId and id = :certId', array(
                ':seamanId' => $seamanId,
                ':certId' => $certId,
            ));
            if (!$cert instanceof ModSeamanSertificate) {
                throw new CHttpException(404);
            }
        } else {
            $availCertificates = $this->getCertTypesByContract($seaman, $contractId);
        }

        // переопределить идентификатор моряка
        $cert->seaman_id = $seamanId;

        /** @var CHttpRequest $request */
        $request = Yii::app()->getRequest();

        //Удаляем временные файлы при открытии формы редактирования/создания
        if (!$request->getIsPostRequest()) {
            $files = ModSeamanFiles::model()->findAllTemporaryBySeamanId($seamanId);
            $files = array_merge($files, ModSeamanFiles::model()->findAllTemporaryNewBySeamanId($seamanId));
            foreach ($files as $file) {
                $file->removeFile();
                $file->delete();
            }
        }

        // пометка, что документ находится в ротации
        // только при этом условии выводим информацию об измененных полях
        $needApproval = SeamanRotation::hasOpenedRotation($cert->seaman_id, Yii::app()->user->parent_id);

        $result = array(
            'mode' => 'certificate',
            'certId' => !$cert->getIsNewRecord() ? $cert->id : null,
            'certTypeId' => $cert->sert_id,
            'contractId' => $contractId,
            'seamanId' => $seaman->id,
            'success' => false,
            'deleted' => false,
            'submitted' => false,
            'isApproved' => !$needApproval || $cert->isApproved(),
            'needToDelete' => $cert->needToDelete(),
        );
        if ($request->getIsPostRequest() && ($data = $request->getPost('ModSeamanSertificate', false)) !== false) {
            $result['submitted'] = true;
            $cert->setAttributes($data);
            /** @var DocumentApi $documentApi */
            $documentApi = Yii::app()->getModule('seaman')->documentApi;
            // подтверждение удаления сертификата
            if (empty($_POST['declineChanges']) && $cert->needToDelete() && !$cert->getIsNewRecord()) {
                $result['certId'] = $cert->id;
                $result['deleted'] = $result['success'] = $documentApi->removeCertificate($cert);
            } else {
                $result['success'] = $documentApi->saveCertificate($cert);
                if ($result['success']) {
                    $result['certId'] = $cert->id;
                }
            }

            $this->sendMessageToComet($seaman, $contractId);
        }



        $result['form_title'] = $cert->getIsNewRecord() && !$cert->sert_id ?
            Yii::t('SeamanModule/document', 'Новый сертификат') :
            $cert->sert->long_title;
        $result['form'] = $cert->getIsNewRecord() && !$cert->sert_id ?
            $this->renderPartial('certificate/create', array(
                'model' => $cert,
                'availCertificates' => $availCertificates,
            ), true) :
            $this->renderPartial('certificate/update', array(
                'model' => $cert,
                'needApproval' => $needApproval,
            ), true);

        // кнопки, которые нужно показать в модалке
        $result['buttons'] = array(
            'cancel' => true,
            'save' => true,
            'decline' => $needApproval && !$cert->isApproved() && (!$cert->isNewDocument() || $cert->needToDelete()),
            'delete' => !$cert->isPrimaryDoc() && !$cert->getIsNewRecord(),
        );

        print CJSON::encode($result);
        Yii::app()->end();
    }

    /**
     * Переключить значение сертификата типа "чекбокс"
     *
     * @param integer $seamanId Идентификатор моряка
     * @param integer $certId Идентификатор сертификата
     *
     * @throws CHttpException
     */
    public function actionCertToggleBool($seamanId, $certId)
    {
        /** @var ModSeamanSertificate $certificate */
        $certificate = ModSeamanSertificate::model()->find('seaman_id = :seamanId and id = :certId', array(
            ':seamanId' => $seamanId,
            ':certId' => $certId,
        ));
        if (!$certificate instanceof ModSeamanSertificate || !$certificate->sert->type == 'checkbox') {
            throw new CHttpException(404);
        }

        $certificate->bool_value = $certificate->bool_value == 0 ? 1 : 0;
        $success = $certificate->save();

        $result = array(
            'seamanId' => $certificate->seaman_id,
            'certId' => $certificate->id,
            'certTypeId' => $certificate->sert_id,
            'boolValue' => $certificate->bool_value,
            'success' => $success,
        );

        echo CJSON::encode($result);
        Yii::app()->end();
    }

    /**
     * Получить строку диплома для таблицы документов на странице списка документов
     *
     * @param integer $competencyId Идентификатор диплома
     * @param integer $seamanId Идентификатор моряка
     * @param string $template Шаблон для вывода строки
     *
     * @throws CHttpException
     */
    public function actionGetCompetencyRow($competencyId, $seamanId, $template = 'default')
    {
        /** @var ModSeamanCompetency $competency */
        $competency = ModSeamanCompetency::model()->find('id = :competencyId and seaman_id = :seamanId', array(
            ':competencyId' => $competencyId,
            ':seamanId' => $seamanId,
        ));

        if (!$competency instanceof ModSeamanCompetency) {
            throw new CHttpException(404);
        }

        $result = array();

        /** @var SeamanDocumentsListWidget $seamanDocumentsList */
        $seamanDocumentsList = $this->createWidget('application.modules.seaman.widgets.SeamanDocumentsListWidget', array(
            'template' => $template,
            'seaman' => ModSeaman::model()->findByPk($competency->seaman_id),
        ));

        $result[] = array(
            'competencyId' => $competency->id,
            'seamanId' => $competency->seaman_id,
            'html' => $seamanDocumentsList->renderCompetency($competency, true)
        );

        echo CJSON::encode($result);
        Yii::app()->end();
    }

    /**
     * Получить строку сертификата для таблицы документов на странице списка документов
     *
     * @param integer $seamanId Идентификатор моряка
     * @param integer $certId Идентификатор документа
     * @param integer $contractId Идентификатор контракта
     * @param string $template Шаблон для вывода строки
     * @param integer $certTypeId Тип документа
     *
     * @throws CHttpException
     */
    public function actionGetCertRow($seamanId, $certId = null, $contractId = null, $template = 'default', $certTypeId = null)
    {
        /** @var ModSeaman $seaman */
        $seaman = ModSeaman::model()->findByPk($seamanId);
        if (!$seaman instanceof ModSeaman) {
            throw new CHttpException(404);
        }

        if (!empty($certId)) {
            /** @var ModSeamanSertificate $certificate */
            $certificate = ModSeamanSertificate::model()->find('id = :certId and seaman_id = :seamanId', array(
                ':certId' => $certId,
                ':seamanId' => $seamanId,
            ));
            if (!$certificate instanceof ModSeamanSertificate) {
                throw new CHttpException(404);
            }
        } elseif (!empty($certTypeId)) {
            $certificate = new ModSeamanSertificate();
            $certificate->sert_id = $certTypeId;
            $certificate->seaman_id = $seamanId;
            $certificate->date1 = '0000-00-00';
            $certificate->date2 = '0000-00-00';
            $certificate->bool_value = 0;
            $certificate->nomer = '';
            $certificate->country_id = 0;
            $certificate->is_required = 1;
        } else {
            throw new CHttpException(404);
        }
        /** @var CntContract $contract */
        $contract = CntContract::model()->find('id = :contractId and seaman_id = :seamanId', array(
            ':contractId' => $contractId,
            ':seamanId' => $seamanId,
        ));

        // виджет для рендера строк
        /** @var SeamanDocumentsListWidget $seamanDocuments */
        $seamanDocuments = $this->createWidget('application.modules.seaman.widgets.SeamanDocumentsListWidget', array(
            'template' => $template,
            'seaman' => $seaman,
            'contract' => $contract,
        ));

        $result = array();

        $result[] = array(
            'certTypeId' => $certificate->sert_id,
            'certId' => $certId,
            'contractId' => $contract->Id,
            'seamanId' => $contract->seaman_id,
            'isPrimaryDoc' => $certificate->isPrimaryDoc(),
            'isRequired' => in_array($certificate->sert_id, $seamanDocuments->requiredCertsIds),
            'html' => $seamanDocuments->renderCertificate($certificate, true),
        );

        // если есть зависимый документ по чеклисту - также его подтянуть
        $checklist = $seamanDocuments->checklist;
        if ($checklist) {
            $colorer = CertColorer::instance($checklist->id);
            $changes = $colorer->getColorInfo($certificate->attributes);
            if (count($changes) > 1) {
                // найдены связанные документы
                foreach ($changes as $certTypeId => $relatedCert) {
                    if ($certTypeId == $certificate->sert_id) {
                        continue;
                    }
                    /** @var ModSeamanSertificate $secDoc */
                    $secDoc = ModSeamanSertificate::model()->findAllByAttributes(array(
                        'seaman_id' => $contract->seaman_id,
                        'sert_id' => $relatedCert['sert_id']
                    ));
                    if (!$secDoc instanceof ModSeamanSertificate) {
                        continue;
                    }
                    $result[] = array(
                        'certTypeId' => $secDoc->sert_id,
                        'certId' => $secDoc->id,
                        'contractId' => $contract->Id,
                        'seamanId' => $contract->seaman_id,
                        'isPrimaryDoc' => $secDoc->isPrimaryDoc(),
                        'html' => $seamanDocuments->renderCertificate($secDoc, true),
                    );
                }
            }
        }

        echo CJSON::encode($result);
        Yii::app()->end();
    }

    /**
     * Создать модель файла с привязкой к документу и моряку на основе формы загрузки файлов
     *
     * @param CUploadedFile $uploadedFile
     * @param DocumentManagerUploadNewFile $form
     * @param ModSeaman $seaman
     *
     * @return ModSeamanFiles|null
     */
    protected function prepareImageFile(CUploadedFile $uploadedFile, DocumentManagerUploadNewFile $form, ModSeaman $seaman)
    {
        // создать модель
        $fileModel = new ModSeamanFiles();
        $fileModel->created = time();
        $fileModel->seaman_id = $form->seamanId;
        $fileModel->original_filename = basename($uploadedFile->name);
        if (!empty($form->documentId) && !empty($form->documentType)) {
            $fileModel->table_name = ModSeamanFiles::TABLE_TEMPORARY_NEW;
            $fileModel->doc_id = $form->documentId;
        } else {
            // загружен временный файл для нового документа, который еще не был создан
            $fileModel->table_name = ModSeamanFiles::TABLE_TEMPORARY;
            $fileModel->doc_id = $seaman->id;
        }
        $fileModel->doc_sub_id = 0;
        // генерация рандомного относительного пути
        $newFileName = ModSeamanFiles::generateNewFilePath($seaman->id, $uploadedFile->name);
        // на основе названия генерируем абсолютный путь, куда будет файл перемещен
        $newFileAbsolutePath = ModSeamanFiles::getAbsolutePath($newFileName);
        $fileModel->filename = $newFileName;
        if ($fileModel->save()) {
            $uploadedFile->saveAs($newFileAbsolutePath);
            return $fileModel;
        }

        return null;
    }

    /**
     * Создать массив файлов с привязкой к документу и моряку из PDF-файла.
     *
     * Функция разбивает PDF на картинки по листам.
     *
     * @param CUploadedFile $uploadedFile
     * @param DocumentManagerUploadNewFile $form
     * @param ModSeaman $seaman
     *
     * @return ModSeamanFiles[]
     */
    protected function preparePdfFile(CUploadedFile $uploadedFile, DocumentManagerUploadNewFile $form, ModSeaman $seaman)
    {
        $result = array();

        $imagick = new Imagick();

        // настройки качества выдаваемого изображения
        $imagick->setResolution(200, 200);
        $imagick->readImage($uploadedFile->tempName);
        $imagick->setCompression(Imagick::COMPRESSION_JPEG);
        $imagick->setCompressionQuality(100);

        // количество страниц в PDF
        $pagesCount = $imagick->getNumberImages();

        if ($pagesCount < 1) {
            return $result;
        }

        // каждую страницу сохраняем как отдельный файл
        for ($x = 0; $x < $pagesCount; $x++) {
            // установить текущую страницу
            $imagick->setIteratorIndex($x);

            $fileModel = new ModSeamanFiles();
            $fileModel->created = time();
            $fileModel->seaman_id = $form->seamanId;
            $fileModel->original_filename = basename($uploadedFile->name) . ' (' . (string) ($x + 1) . ')';
            if (!empty($form->documentId) && !empty($form->documentType)) {
                $fileModel->table_name = ModSeamanFiles::TABLE_TEMPORARY_NEW;
                $fileModel->doc_id = $form->documentId;
            } else {
                // загружен временный файл для нового документа, который еще не был создан
                $fileModel->table_name = ModSeamanFiles::TABLE_TEMPORARY;
                $fileModel->doc_id = $seaman->id;
            }
            $fileModel->doc_sub_id = 0;
            // генерация рандомного относительного пути
            $newFileName = ModSeamanFiles::generateNewFilePath($seaman->id, $uploadedFile->name . $x, 'jpg');
            // на основе названия генерируем абсолютный путь, куда будет файл перемещен
            $newFileAbsolutePath = ModSeamanFiles::getAbsolutePath($newFileName);
            $fileModel->filename = $newFileName;
            if ($fileModel->save()) {
                // модель сохранена, теперь надо сохранить изображение
                $imagick->writeImage($newFileAbsolutePath);
                $result[] = $fileModel;
            }
        }

        return $result;
    }

    /**
     * Загрузка скана документа.
     *
     * Обработка запроса производится через форму DocumentManagerUploadNewFile.
     *
     * @see DocumentManagerUploadNewFile
     */
    public function actionUploadFile()
    {
        // JSON-результат
        $result = array();

        $form = new DocumentManagerUploadNewFile();

        if (isset($_POST['DocumentManagerUploadNewFile'])) {
            $form->setAttributes($_POST['DocumentManagerUploadNewFile']);
        }
        $form->files = CUploadedFile::getInstances($form, 'files');

        // форма не прошла валидацию - генерируем пустой массив
        if (!$form->validate()) {
            echo CJSON::encode($result);
            Yii::app()->end();
        }

        // поиск моряка
        $seaman = ModSeaman::model()->findByPk($form->seamanId);
        if (!$seaman instanceof ModSeaman) {
            throw new CHttpException(403);
        }

        foreach ($form->files as $uploadedFile) {
            /** @var CUploadedFile $uploadedFile */
            if (in_array($uploadedFile->type, array('image/jpeg', 'image/jpg', 'image/png', 'image/gif'))) {
                // загрузка изображения
                $fileModel = $this->prepareImageFile($uploadedFile, $form, $seaman);
                if ($fileModel) {
                    $result[] = array(
                        'id' => $fileModel->id,
                        'url' => $fileModel->getUrl(),
                        'html' => $this->renderPartial('file_row', array(
                            'file' => $fileModel,
                        ), true),
                    );
                }
            } elseif ($uploadedFile->type == 'application/pdf') {
                // загрузка PDF, дробим на кучу маленьких файлов
                $fileModels = $this->preparePdfFile($uploadedFile, $form, $seaman);
                foreach ($fileModels as $fileModel) {
                    $result[] = array(
                        'id' => $fileModel->id,
                        'url' => $fileModel->getUrl(),
                        'html' => $this->renderPartial('file_row', array(
                            'file' => $fileModel,
                        ), true),
                    );
                }
            }
        }

        echo CJSON::encode($result);

        Yii::app()->end();
    }

    /**
     * Обновить файл через редактор.
     *
     * Обработка запроса производится через форму DocumentManagerUploadNewFile.
     *
     * В файле сохраняем лог изменений в JS-плагине jquery.cropper.
     *
     * @see DocumentManagerUploadNewFile
     */
    public function actionUpdateFile()
    {
        $result = array(
            'fileId' => null,
            'originalFileUrl' => null,
            'updatedFileUrl' => null,
            'success' => false,
        );

        $model = new DocumentManagerFileForm();

        if (isset($_POST['DocumentManagerFileForm'])) {
            $model->setAttributes($_POST['DocumentManagerFileForm']);
        }

        if (!$model->validate()) {
            echo CJSON::encode($result);
            Yii::app()->end();
        }

        /** @var ModSeamanFiles $file */
        $file = $model->getFile();

        // если пришел base64 закропленного изображения - получаем его
        if ($model->imageData) {
            // определить тип файла
            $matches = array();
            preg_match('#^data:image/([^\;]+)\;#i', $model->imageData, $matches);
            $fileExtension = !empty($matches[1]) ? $matches[1] : null;
            if (!is_null($fileExtension)) {
                // удалить предыдущую копию
                $file->removeUpdatedFile();
                // отрезаем все, что находится до запятой
                $imageData = preg_replace('#^([^\,]+,)#i', '', $model->imageData);
                // генерация нового названия файла
                $relativeFilePath = ModSeamanFiles::generateNewFilePath($model->seamanId, $file->getAbsoluteFilePath(), $fileExtension);
                $fileAbsolutePath = ModSeamanFiles::getAbsolutePath($relativeFilePath);

                // пушим внутрь файла, предварительно декодировав данные
                file_put_contents($fileAbsolutePath, base64_decode($imageData));

                $file->updated_filename = $relativeFilePath;
            }
        }

        $file->crop_data = $model->cropData;

        // сохранить файл
        $file->save();

        $result['fileId'] = $model->fileId;
        $result['originalFileUrl'] = $file->getUrl();
        $result['updatedFileUrl'] = $file->getUpdatedUrl();
        $result['success'] = true;

        echo CJSON::encode($result);
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
        $ids = Yii::app()->request->getPost('ids');

        $result['success'] = false;
        if (!empty($ids)) {
            $result['success'] = true;
            $criteria = new CDbCriteria();
            $criteria->addInCondition('id', $ids);
            $files = ModSeamanFiles::model()->findAll($criteria);
            foreach ($files as $file) {
                $result['success'] = $result['success'] && $this->deleteFile($file);
            }
        }

        echo CJSON::encode($result);
        Yii::app()->end();
    }

    /**
     * Удаляет прикрепленный к документу файл
     * @param ModSeamanFiles $file
     * @return bool
     * @throws CDbException
     */
    protected function deleteFile(ModSeamanFiles $file) {
        // удалить файл на диске
        $file->removeFile();

        // и саму модель
        return $file->delete();
    }

    /**
     * Возвращает пути и названия файлов сканов
     *
     * @param integer $seamanId Идентификатор моряка
     * @param string $documentType Тип документа (cert|competency)
     * @param integer $documentId Идентификатор документа
     *
     * @throws CHttpException
     */
    public function actionFileUrls($seamanId, $documentType, $documentId)
    {
        // получить все файлы, которые требуются для скачивания
        /** @var ModSeamanFiles[] $models */
        $models = ModSeamanFiles::model()->findAll('seaman_id = :seamanId and table_name = :tableName and doc_id = :documentId', array(
            ':seamanId' => $seamanId,
            ':tableName' => $documentType == DocumentManagerUploadNewFile::DOCUMENT_TYPE_CERT ?
                '_mod_seaman_sertificates' :
                '_mod_seaman_competency',
            ':documentId' => $documentId,
        ));

        $files = array();
        foreach ($models as $model) {
            $files[] = array(
                'url' => $model->updated_filename ? $model->getUpdatedUrl() : $model->getUrl(),
                'path' => $model->updated_filename ? $model->getAbsoluteUpdatedFilePath() : $model->getAbsoluteFilePath(),
                'original_name' => $model->getOriginalFileName(),
                'extension' => $model->updated_filename ? $model->getUpdatedExtension() : $model->getExtension(),
            );
        }

        if (empty($files)) {
            // без файлов работать не можем
            throw new CHttpException(404);
        }

        // название файлов основывается на названии документа
        if ($documentType == DocumentManagerUploadNewFile::DOCUMENT_TYPE_CERT) {
            $cert = ModSeamanSertificate::model()->findByPk($documentId);
            if (!($cert instanceof ModSeamanSertificate)) {
                throw new CHttpException(404);
            }
            $documentPrefix = $cert->sert->long_title;
        } else {
            $document = ModSeamanCompetency::model()->findByPk($documentId);
            if (!($document instanceof ModSeamanCompetency)) {
                throw new CHttpException(404);
            }
            $documentPrefix = $document->position == 0 ? $document->position_freeenter : ModPredefinedData::model()->getValue($document->position);
        }
        // нормализуем префикс
        $documentPrefix = preg_replace('/[^0-9A-z]+/', '_', $documentPrefix);

        // склеиваем все изображения в один pdf
        /* @var $mPDF mpdf */
        $mPDF = Yii::app()->ePdf->mpdf('', 'A4', 0, 'Arial', 0, 0, 0, 0, 0, 0, 'P');
        $mPDF->SetDisplayMode('fullpage');

        foreach ($files as $file) {
            $html='<img src="' . $file['path'] . '"/>';
            $mPDF->AddPage();
            $mPDF->WriteHTML($html);
        }

        $bytes = $mPDF->Output(null, 'S');
        return Yii::app()->getRequest()->sendFile($documentPrefix . '.pdf', $bytes);
    }
}
