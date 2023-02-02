<?php

/**
 * Api Редактирования/удаления документов
 */
class DocumentApi extends CApplicationComponent
{

    /**
     * Удаление сертификата
     * Если пользователь не агент, помечает удаленным
     * @param ModSeamanSertificate $certificate
     * @return bool
     * @throws CDbException
     */
    public function removeCertificate(ModSeamanSertificate $certificate)
    {
        if (!Yii::app()->authManager->isAgent() && SeamanRotation::hasOpenedRotation($certificate->seaman_id)) {
            $certificate->approved = false;
            $certificate->deleted = true;
            return $certificate->save();
        } else {
            return $certificate->delete();
        }
    }

    /**
     * Удаление диплома
     * Если пользователь не агент, помечает удаленным
     * @param ModSeamanCompetency $competency
     * @return bool
     * @throws CDbException
     */
    public function removeCompetency(ModSeamanCompetency $competency)
    {
        if (!Yii::app()->authManager->isAgent() && SeamanRotation::hasOpenedRotation($competency->seaman_id)) {
            $competency->approved = false;
            $competency->deleted = true;
            return $competency->save();
        } else {
            return $competency->delete();
        }
    }

    /**
     * Сохранение сертификата
     * Проверяет существование удаленного сертификата данного типа
     * @param ModSeamanSertificate $certificate
     * @return bool
     */
    public function saveCertificate(ModSeamanSertificate &$certificate)
    {
        if ($certificate->validate() && (Yii::app()->authManager->isAgent() &&
            SeamanRotation::hasOpenedRotation($certificate->seaman_id, Yii::app()->user->parent_id) ||
                !Yii::app()->authManager->isAgent() && !SeamanRotation::hasOpenedRotation($certificate->seaman_id))) {
            // пометить сертификат как заапрувленный
            $certificate->setIsApproved();
        }
        $isNewRecord = $certificate->isNewRecord;
        if ($isNewRecord) {
            //проверяем, нет ли сертификата такого типа, помеченного как удаленный
            $currentCertificate = ModSeamanSertificate::model()->findByAttributes(array(
                'seaman_id' => $certificate->seaman_id,
                'sert_id' => $certificate->sert_id,
                'deleted' => true,
            ));
            if ($certificate->validate() && $currentCertificate instanceof ModSeamanSertificate) {
                $certificate->id = $currentCertificate->id;
                $certificate->isNewRecord = false;
            }
        }

        $temporaryFiles = ModSeamanFiles::model()->findAllTemporaryBySeamanId($certificate->seaman_id);
        if ($certificate->save()) {
            // если была новая запись, то все временные доки крепим к текущей модели
            if ($isNewRecord) {
                foreach ($temporaryFiles as $file) {
                    $file->table_name = ModSeamanFiles::TABLE_CERT;
                    $file->doc_id = $certificate->id;
                    $file->save();
                }
            } else {
                $temporaryNewFiles = ModSeamanFiles::model()->findAllTemporaryNewBySeamanId($certificate->seaman_id);
                if (!empty($temporaryNewFiles)) {
                    foreach ($temporaryNewFiles as $file) {
                        $file->table_name = ModSeamanFiles::TABLE_CERT;
                        $file->doc_id = $certificate->id;
                        $file->save();
                    }
                    if (!Yii::app()->authManager->isAgent()) {
                        $certificate->setAttributes(array(
                            'has_new_files' => true,
                        ));
                        $certificate->save(true, array('has_new_files'));
                    }
                }
            }
            return true;
        } else {
            $certificate->files = $temporaryFiles;
        }
        return false;
    }

    /**
     * Сохранение диплома
     * @param ModSeamanCompetency $competency
     * @return bool
     */
    public function saveCompetency(ModSeamanCompetency &$competency)
    {
        if ($competency->validate() && (Yii::app()->authManager->isAgent() &&
            SeamanRotation::hasOpenedRotation($competency->seaman_id, Yii::app()->user->parent_id) ||
                !Yii::app()->authManager->isAgent() && !SeamanRotation::hasOpenedRotation($competency->seaman_id))) {
            // пометить диплом как заапрувленный
            $competency->setIsApproved();
        }
        $isNewRecord = $competency->isNewRecord;

        $temporaryFiles = ModSeamanFiles::model()->findAllTemporaryBySeamanId($competency->seaman_id);
        if ($competency->save()) {
            // если была новая запись, то все временные доки крепим к текущей модели
            if ($isNewRecord) {
                foreach ($temporaryFiles as $file) {
                    $file->table_name = ModSeamanFiles::TABLE_COMPETENCY;
                    $file->doc_id = $competency->id;
                    $file->save();
                }
            } else {
                $temporaryNewFiles = ModSeamanFiles::model()->findAllTemporaryNewBySeamanId($competency->seaman_id);
                if (!empty($temporaryNewFiles)) {
                    foreach ($temporaryNewFiles as $file) {
                        $file->table_name = ModSeamanFiles::TABLE_COMPETENCY;
                        $file->doc_id = $competency->id;
                        $file->save();
                    }

                    if (!Yii::app()->authManager->isAgent()) {
                        $competency->setAttributes(array(
                            'has_new_files' => true,
                        ));
                        $competency->save(true, array('has_new_files'));
                    }
                }
            }
            return true;
        } else {
            $competency->files = $temporaryFiles;
        }
        return false;
    }
}
