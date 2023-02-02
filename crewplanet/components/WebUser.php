<?php

/**
 * @property int $type
 * @property string $displayName
 * @property string $groupUsername ник пользователя
 * @property string $fullUsername
 * @property int $usersNumber   количество юзеров в аккаунте
 * @property int $parent_id
 * @property string $role
 * @property User $ar связанный объект
 */
class WebUser extends CWebUser
{
    const SESSION_AGENT_BLOCKED_BC_HE_DID_NOT_PAY = '__agent_blocked_because_he_did_not_pay';

    private $parent_id = 0;
    private $role = '';

    /** @var $userActiveRecord User */
    private $userActiveRecord = null;

    /**
     * @param IUserIdentity $identity
     * @param int $duration
     *
     * @return bool|void
     */
    public function login($identity, $duration = 0)
    {
        if (parent::login($identity, $duration)) {
            $this->id = $this->userActiveRecord->id;
            Yii::app()->language = $this->userActiveRecord->getSetting('language', 'en');

            if ($duration == 0) { // эмулируем поведение фреймворка, но с поправкой - создаем куку автологина, но живущую до момента закрытия броузера
                $cookie = $this->createIdentityCookie($this->getStateKeyPrefix());
                $cookie->expire = 0; // финт ушами
                $data = array(
                    $this->getId(),
                    $this->getName(),
                    $duration,
                    $this->saveIdentityStates(),
                );
                $cookie->value = Yii::app()->getSecurityManager()->hashData(serialize($data));
                Yii::app()->getRequest()->getCookies()->add($cookie->name, $cookie);
            }

            return true;
        }

        return false;
    }

    public function beforeLogout()
    {
        if ($this->userActiveRecord) {
            // убиваем контрольный код
            $this->userActiveRecord->secret_code = '';
            $this->userActiveRecord->update(array('secret_code'));
        }

        return parent::beforeLogout();
    }

    protected function beforeLogin($id, $states, $fromCookie)
    { // проверяем, можно ли юзеру логиниться
        if (parent::beforeLogin($id, $states, $fromCookie)) {
            if ($fromCookie)
                $this->userActiveRecord = User::model()->findByAttributes(array(
                    'id' => $id,
                    'secret_code' => Yii::app()->request->cookies['secretCode']->value
                ));
            else
                $this->userActiveRecord = User::model()->findByPk($id);

            return $this->_isReallyActive();
        }

        return false;
    }

    public function afterLogout()
    {
        Yii::app()->session->remove('current_user');
    }

    /**
     * Вызывается после успешной авторизации - удобное место для заполнения сессии нужными данными
     *
     * @param bool $formCookie
     */
    protected function afterLogin($formCookie)
    {
        //-----------------------------------------
        // дополняем сессию данными профиля
        if ($this->userActiveRecord->profile_type == User::PROFILE_SEAMAN) { // моряк
            $userFull = Yii::app()->db->createCommand('
            SELECT
                u.*, s.*, u.id
              FROM
                users AS u
                LEFT OUTER JOIN _mod_seaman AS s ON s.id = u.id
              WHERE u.id=:uId')->queryRow(true, array(':uId' => $this->userActiveRecord->id));
        }

        if ($this->userActiveRecord->profile_type == User::PROFILE_SHIPOWNER) { // агент
            $userFull = Yii::app()->db->createCommand('
            SELECT
                u.*, s.*, u.id
              FROM
                users AS u
                LEFT OUTER JOIN _mod_shipowner AS s ON s.id = u.parent_id
              WHERE u.id=:uId')->queryRow(true, array(':uId' => $this->userActiveRecord->id));
        }

        if ($this->userActiveRecord->profile_type == User::PROFILE_OBSERVER) { // наблюдатель
            $userFull = Yii::app()->db->createCommand('
            SELECT u.* FROM users AS u WHERE u.id=:uId')->queryRow(true, array(':uId' => $this->userActiveRecord->id));
        }

        Yii::app()->session->add('current_user', $userFull);

        //===============================================
        $this->parent_id = $userFull['parent_id'];
        $this->role = $userFull['role'];

        //Судовладелец или моряк
        $this->setState('profile_type', $userFull['profile_type']);
        $this->setState('group_username', $userFull['nickname']);

        //Для показа, под кем залогинен
        if ($userFull['profile_type'] == User::PROFILE_SHIPOWNER) {
            $this->setState('display_name', $userFull['firma'] . (!empty($userFull['nickname']) ? " > " . $userFull['nickname'] : ""));
            $this->setState('full_user_name', $this->userActiveRecord->full_name);

            $this->setState('users_number', User::model()->count('parent_id=' . $this->parent_id));

            //-----------------------------------------
            // проверка на пропущенную смену ТП
            Abonement::checkAgentMembership($this->userActiveRecord->parent_id);

            /** @var ModShipowner $agent */
            $agent = ModShipowner::model()->with('users')->findByPk($this->userActiveRecord->parent_id);

            // проверка на долги и установить соответствующие флаги в сессии
            /** @var AgentPaymentChecker $agentPaymentChecker */
            $agentPaymentChecker = Yii::app()->agentPaymentChecker;
            if ($agentPaymentChecker->checkCriticallyUnpaidInvoice($agent)) {
                Yii::app()->session->add(self::SESSION_AGENT_BLOCKED_BC_HE_DID_NOT_PAY, 1);
            } else {
                Yii::app()->session->remove(self::SESSION_AGENT_BLOCKED_BC_HE_DID_NOT_PAY);
            }

            //-----------------------------------------
            // проверка на самый первый вход в систему
            if (!$agent->getSetting('first_login_done', false)) { // отправляем мыло админам о первом входе
                Yii::app()->sendEmailToSupport(
                    '[REPORT] Агент ' . CHtml::encode($agent->firma) . ' впервые вошел в свой аккаунт',
                    "<p>Агент " . CHtml::encode($agent->firma) . '(' . $agent->id . ") впервые вошел в свой аккаунт.</p>

					<br/>

					<p>Данные агента:</p>
					<p>Компания: " . CHtml::encode($agent->firma) . "</p>

					<p>Контактное лицо: " . CHtml::encode($agent->users[0]->full_name) . "</p>
					<p>" . $agent->users[0]->phone . "'>" . $agent->users[0]->phone . "</p>

					<p><a href='mailto:" . $agent->users[0]->email . "'>" . $agent->users[0]->email . "</a></p>

					<p>Был ли получен триал при регистрации?: " . ($agent->membership_current == App::MEMBERSHIP_TRIAL ? 'да' : 'нет') . "</p>

					",
                    null,
                    Yii::app()->params['salesEmail']
                );

                $agent->setSetting('first_login_done', 1);
            }
        }

        if ($userFull['profile_type'] == User::PROFILE_SEAMAN) {
            $this->setState('users_number', 1);

            $this->setState('full_user_name', $userFull['PI_imja'] . ' ' . $userFull['PI_familija']);

            if (!empty($userFull['full_name']))
                $this->setState('display_name', $userFull['full_name']);
            else
                $this->setState('display_name', $userFull['PI_imja'] . ' ' . $userFull['PI_familija']);
        }

        if ($userFull['profile_type'] == User::PROFILE_OBSERVER) {
            $this->setState('users_number', 1);

            $this->setState('full_user_name', $userFull['full_name']);

            if (!empty($userFull['full_name']))
                $this->setState('display_name', $userFull['full_name']);
            else
                $this->setState('display_name', $userFull['nickname']);
        }

        // обновляем данные в записи
        if ($this->userActiveRecord) {
            $this->userActiveRecord->last_hit = date('Y-m-d H:i:s');
            $this->userActiveRecord->secret_code = md5('crew-SuPeR-secret' . time());
            $this->userActiveRecord->last_ip = $_SERVER['REMOTE_ADDR'];
            $this->userActiveRecord->update(array('secret_code', 'last_hit', 'last_ip'));

            if ($this->allowAutoLogin)
                Yii::app()->request->cookies['secretCode'] = new CHttpCookie('secretCode', $this->userActiveRecord->secret_code, array(
                    'expire' => time() + @intval(Yii::app()->params['cookieLifeTime'])
                ));
        }
    }

    /**
     * Вызывается при каждом хите - удобное место для обновления кук
     */
    protected function updateAuthStatus()
    {
        if (!$this->userActiveRecord)
            $this->userActiveRecord = User::model()->findByPk($this->id);

        parent::updateAuthStatus();

    }

    /**
     * Проверяет, активен ли еще пользователь по всем связанным таблицам
     *
     * @return bool
     */
    private function _isReallyActive()
    {
        if ($this->userActiveRecord && $this->userActiveRecord->active == 1) {
            if ($this->userActiveRecord->profile_type == User::PROFILE_SHIPOWNER) { // судовладелец - проверяем не заблокирован ли аккаунт компании
                /** @var ModShipowner $shipowner */
                $shipowner = ModShipowner::model()->findByPk($this->userActiveRecord->parent_id);


                return $shipowner && $shipowner->active == 1;
            }

            return true;
        }

        return false;
    }

    /**
     * Числовой код типа профиля. Используйте User::PROFILE_XXX
     *
     * @return int
     */
    public function getType()
    {
        return $this->getState('profile_type');
    }

    /**
     * Имя пользователя (для судовладельцев - "фирма > ник")
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->getState('display_name');
    }

    /**
     * ник пользователя
     *
     * @return string
     */
    public function getGroupUsername()
    {
        return $this->getState('group_username');
    }

    /**
     * Полное имя пользователя (имя + фамилия для всех типов пользователей)
     *
     * @return string
     */
    public function getFullUsername()
    {
        return $this->getState('full_user_name');
    }

    /**
     * Возвращает количество юзеров в аккаунте.
     * Для моряков всегда == 1, аккаунты судовладельцев могут иметь значения более 1
     *
     * @return int
     */
    public function getUsersNumber()
    {
        return $this->getState('users_number');
    }

    /**
     * Идентификатор агента (_mod_shipowner.id)
     *
     * @return int
     */
    public function getParent_id()
    {
        return intval($this->userActiveRecord->parent_id);
    }

    public function getRole()
    {
        return $this->userActiveRecord->role;
    }

    /**
     * Чтобы не писать в бизнес-правилах проверку на авторизованность
     *
     * @param string $operation
     * @param array $params
     * @param bool $allowCaching
     *
     * @return bool
     */
    public function checkAccess($operation, $params = array(), $allowCaching = true)
    {
        return $this->isGuest ? false : parent::checkAccess($operation, $params, $allowCaching);
    }

    /**
     * @return User
     */
    public function getAr()
    {
        return $this->userActiveRecord;
    }

    /**
     * Получение значения параметра юзера
     *
     * @param string $key
     * @param null $default значение по-умолчанию
     *
     * @return null
     */
    public function getSetting($key, $default = null)
    {
        return $this->userActiveRecord->getSetting($key, $default);
    }

    /**
     * Установка параметра юзера
     *
     * @param string $key
     * @param string $value
     */
    public function setSetting($key, $value)
    {
        $this->userActiveRecord->setSetting($key, $value);
    }

    /**
     * Метод проверяет, не вызван ли данный хит с сайта морехода и пытается авторизовать пользователя, который авторизован сейчас на мореходе.
     * На данный момент метод используется только для страниц, генерируемых crewplanet, но располагаемых на мореходе (morehod.ru/jobs/...)
     *
     * Если текущий пользователь не гость, а на мореходе он не авторизован, то метод делает logout.
     */
    public function loginFromMorehodAccount()
    {
        if (Yii::app()->isMorehod()) { // поведение только если заход с морехода
            $morUser = new MorehodUser();

            if ($morUser->data) { // только если он авторизован на мореходе
                /** @var UserAccount $userAcc */
                $userAcc = UserAccount::model()->with('user')->find(array(
                    'condition' => "service_name = :serName AND service_id = :servId",
                    'params' => array(
                        ':serName' => UserAccount::SERVICE_MOREHOD_FORUM,
                        ':servId' => $morUser->data['user_id']
                    )
                ));

                if ($userAcc) { // пользователь найден
                    if (Yii::app()->user->isGuest || Yii::app()->user->id != $userAcc->user_id) { // и он не является текущим залогиненным - аутентифицируем его
                        $_identity = new InsiteUserIdentity($userAcc->user->email, $userAcc->user->password);
                        if ($_identity->authenticate()) $this->login($_identity);
                    }
                } else { // юзер крю не найден или
                    if (!Yii::app()->user->isGuest)
                        $this->logout();
                }
            } else { // мореходного юзера нет (не залогинен)
                if (!Yii::app()->user->isGuest)
                    $this->logout();
            }
        }
    }

}

