<?php

/**
 * Виджет списка документов моряка.
 */
class SeamanDocumentsListWidget extends CWidget
{
    /**
     * @var string Шаблон для вывода (см. в папке views/seaman_documents)
     */
    public $template = 'default';

    /**
     * @var ModSeaman Моряк, для которого выводим список документов
     */
    public $seaman;

    /**
     * @var Checklist Чек-лист, если заранее известен, какой (например, в модуле планирования)
     */
    public $checklist;

    /**
     * @var CntContract Контракт, в рамках которого выводится список (например, в модуле планирования)
     */
    public $contract;

    /**
     * @var integer Дни для раскраски
     */
    public $days1 = 0;

    /**
     * @var integer Дни для раскраски
     */
    public $days2 = 150;

    /**
     * @var bool Показывать кастомные сертификаты
     */
    public $showCustomCerts = true;

    /**
     * @var bool Реализуется логика зависимых сертификатов из чек-листа
     */
    public $hideChecklistDependentCerts = false;

    /**
     * @var string
     */
    public $actionsController = 'documentManager';

    /**
     * @var bool Получить в рендер вьюхи информацию о количестве запланированных контрактов
     */
    public $getPlannedContractsCount = false;

    /**
     * @var int Количество запланированных контрактов, если требуется получить
     */
    protected $plannedContractsCount = 0;

    /**
     * @var bool Моряк находится в ротации
     */
    protected $seamanRotation = false;

    /**
     * @var bool Полностью обновлять список при внесении изменений
     */
    public $updateEntireList = false;

    /**
     * @var array Идентификаторы обязательных сертификатов
     */
    public $requiredCertsIds = array();

    /**
     * @inheritdoc
     */
    public function init()
    {
        // подтянуть автолоад модуля
        Yii::app()->getModule('seaman');

        if (!Yii::app()->user->isGuest && Yii::app()->user->parent_id > 0) {
            $this->seamanRotation = SeamanRotation::hasOpenedRotation($this->seaman->id, Yii::app()->user->parent_id);
        }
        $showCheckList = Yii::app()->authManager->isCrewplanetAgent() ||
            Yii::app()->authManager->isSeaman() && $this->seaman->user->id == Yii::app()->user->id;
        // получить информацию о количестве запланированных контрактов
        if ($this->getPlannedContractsCount) {
            /** @var ChecklistsModule $checklistsModule */
            $checklistsModule = Yii::app()->getModule('checklists');
            $plannedContracts = $checklistsModule->getPlanningContracts($this->seaman->id);
            $this->plannedContractsCount = count($plannedContracts);
            // если не задан контракт - задаем его здесь
            // только для себя и агента Крюпленета
            if (!$this->contract && $showCheckList) {
                $this->contract = $plannedContracts[0];
            }
        }

        // если есть контракт, но нет чеклиста - получим его из судна
        if ($this->contract && !$this->checklist && $showCheckList) {
            $ship = $this->contract->ship;
            if ($ship instanceof VacShip) {
                $this->checklist = $ship->checklist;
            }
        } else if (!$showCheckList) {
            // для неКрюпленета и не себя не показываем ни чеклисты, ни документы, доступные по чеклистам
            $this->checklist = null;
            $this->contract = null;
        }
        // если есть чеклист, то меняем дни для раскраски
        if ($this->checklist) {
            $this->days1 = $this->checklist->days1;
            $this->days2 = $this->checklist->days2;
        }

        if ($this->contract instanceof  CntContract && $this->checklist instanceof Checklist) {
            /** @var ChecklistsModule $checkListsModule */
            $checkListsModule = Yii::app()->getModule('checklists');
            $requiredCerts = $checkListsModule->getRequiredCerts($this->contract, $this->checklist);
            $this->requiredCertsIds = array_keys($requiredCerts);
        } else {
            //Подключаем класс Advisor для определения сертификатов, необходимых моряку без чеклиста
            include_once(Yii::app()->basePath . '/../in_site/config.php');
            include_once(Yii::app()->basePath . '/../in_site/modules/libraries/advisor/advisor.class.php');
            $advisor = new Advisor();
            $requiredIds = $advisor->get_required_docs($this->seaman->id);
            
            $criteria = new CDbCriteria();
            $criteria->addInCondition('type', array('seamans book', 'passport'));
            $criteria->select = 'id';
            $mainCerts = Certificate::model()->findAll($criteria);
            foreach ($mainCerts as $mainCert) {
                if (!in_array($mainCert->id, $requiredIds)) {
                    $requiredIds[] = $mainCert->id;
                }
            }
            $this->requiredCertsIds = $requiredIds;
        }

        parent::init();
    }

    /**
     * Получить дипломы моряка
     *
     * @return ModSeamanCompetency[]
     */
    public function getCompetencies()
    {
        return $this->seaman->competencies;
    }

    /**
     * Получить сертификаты моряка
     *
     * @return ModSeamanSertificate[]
     */
    public function getCertificates()
    {
        if ($this->contract && $this->checklist) {
            // получить список сертификатов моряка, требуемых по чеклисту
            /** @var ChecklistsModule $checkListsModule */
            $checkListsModule = Yii::app()->getModule('checklists');
            $certs = $checkListsModule->getSeamanCertificatesByChecklist($this->checklist, $this->contract, $this->hideChecklistDependentCerts);
        } else {
            // если не указан контракт - заполняем все документы, которые есть у моряка
            $certs = $this->seaman->certificates;
            //получаем список документов, необходимых всем морякам без чеклистов
            $requiredIds = $this->requiredCertsIds;
            foreach ($certs as $cert) {
                if (($key = array_search($cert->sert_id, $requiredIds)) !== false) {
                    unset($requiredIds[$key]);
                }
            }
            if (!empty($requiredIds)) {
                $criteria = new CDbCriteria();
                $criteria->addInCondition('id', $requiredIds);
                $requiredCerts = Certificate::model()->findAll($criteria);
                foreach ($requiredCerts as $requiredCert) {
                    $doc = new ModSeamanSertificate();
                    $doc->sert_id = $requiredCert->id;
                    $doc->seaman_id = $this->seaman->id;
                    $doc->date1 = '0000-00-00';
                    $doc->date2 = '0000-00-00';
                    $doc->bool_value = 0;
                    $doc->nomer = '';
                    $doc->country_id = 0;
                    $doc->is_required = 1;
                    $certs[] = $doc;
                }
            }
            @usort($certs, function($a, $b) {
                $aSort = $a->sert->sort_order;
                $bSort = $b->sert->sort_order;
                if ($aSort == $bSort) {
                    return strcmp($a->sert->long_title, $b->sert->long_title);
                }
                if ($aSort == 0) {
                    return 1;
                }
                if ($bSort == 0) {
                    return -1;
                }
                return ($aSort < $bSort) ? -1 : 1;
            });
        }
        if (!$this->showCustomCerts) {
            foreach ($certs as $key => $cert) {
                if ($cert->sert->is_custom) {
                    unset($certs[$key]);
                }
            }
        }


        return $certs;
    }

    /**
     * Получить должность моряка по контракту
     *
     * @return null|string
     */
    public function getPosition()
    {
        /** @var ChecklistsModule $checkListsModule */
        $checkListsModule = Yii::app()->getModule('checklists');
        if ($this->contract && $this->checklist) {
            return $checkListsModule->getPositionByContract($this->checklist, $this->contract);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getViewPath($checkTheme = false)
    {
        return parent::getViewPath($checkTheme) . DIRECTORY_SEPARATOR . 'seaman_documents_list' . DIRECTORY_SEPARATOR . $this->template;
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
     * Зарегистрировать скрипты
     */
    public function registerScripts()
    {
        /** @var CClientScript $clientScript */
        $clientScript = Yii::app()->getClientScript();
        $clientScript->registerScriptFile($this->getAssetsPath() . '/seamanDocumentsList.js');
        Yii::app()->clientScript->registerCssFile(Yii::app()->theme->baseUrl . "/css/new_design/pages/checklists/document_highlight.css");
        // url для получения строки сертификата
        $certRowUrl = Yii::app()->createAbsoluteUrl('seaman/' . $this->actionsController . '/getCertRow');
        // url для получения строки диплома
        $competencyRowUrl = Yii::app()->createAbsoluteUrl('seaman/' . $this->actionsController . '/getCompetencyRow');
        //url для получения всей таблицы
        $updateTableUrl = $this->updateEntireList ? Yii::app()->createAbsoluteUrl('seaman/' . $this->actionsController . '/getDocumentsTable') : null;
        $clientScript->registerScript('seaman-documents-list',
            new CJavaScriptExpression('seamanDocumentsList.init("' . $certRowUrl . '", "' . $competencyRowUrl . '", "' . $this->template . '", "' . $updateTableUrl . '");'));
    }

    /**
     * Рендер индексовой страницы (весь список)
     */
    public function renderIndex()
    {
        $this->render('index', array(
            'seaman' => $this->seaman,
            'competencies' => $this->getCompetencies(),
            'certificates' => $this->getCertificates(),
            'contract' => $this->contract,
            'checklist' => $this->checklist,
            'position' => $this->getPosition(),
        ));
    }

    /**
     * Рендер строки сертификата
     *
     * @param ModSeamanSertificate $certificate
     * @param boolean $returnResult Вернуть результат
     *
     * @return string
     * @throws CException
     */
    public function renderCertificate(ModSeamanSertificate $certificate, $returnResult = false)
    {
        if (!$this->seamanRotation && Yii::app()->authManager->isAgent()) {
            // считаем документ заапрувленным, если моряк находится не в ротации
            $certificate->setIsApproved();
        }

        return $this->render('_certificate_row', array(
            'certificate' => $certificate,
            'contract' => $this->contract,
            'checklist' => $this->checklist,
            'seaman' => $this->seaman,
            'required' => !$certificate->isNewRecord && in_array($certificate->sert_id, $this->requiredCertsIds)
        ), $returnResult);
    }

    /**
     * Рендер строки диплома
     *
     * @param ModSeamanCompetency $competency
     * @param bool $returnResult Вернуть результат
     *
     * @return string
     */
    public function renderCompetency(ModSeamanCompetency $competency, $returnResult = false)
    {
        if (!$this->seamanRotation && Yii::app()->authManager->isAgent()) {
            // считаем документ заапрувленным, если моряк находится не в ротации
            $competency->setIsApproved();
        }

        return $this->render('_competency_row', array(
            'competency' => $competency,
            'seaman' => $this->seaman,
        ), $returnResult);
    }

    /**
     * Рендер блока информации о чеклисте
     *
     * @param bool $returnResult Вернуть результат
     *
     * @return string
     */
    public function renderChecklistInfo($returnResult = false)
    {
        return $this->render('_checklist_info', array(
            'contract' => $this->contract,
            'checklist' => $this->checklist,
            'position' => $this->position,
        ), $returnResult);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerScripts();
        $this->renderIndex();
    }
}
