<?php

App::uses('AppController', 'Controller');

class SenderemailsController extends AppController
{
    public $uses = array(
        'UserSetting',
        'UserSettingField',
        'EmailLog',
        'ProCampaignSchema',
        'ProCampaign',
        'Sender'

    );
    private $sender_emails_limit = 10;
    private $dmarc_restricted_domains = array(
        'mail.ru',
        'bk.ru',
        'mail.ua',
        'list.ru',
        'inbox.ru'
    );
    private $non_pers_domains
        = array(
            'yandex.ru',
            'ya.ru',
            'mail.ru',
            'inbox.ru',
            'bk.ru',
            'list.ru',
            'i.ua',
            'ukr.net',
            'gmail.com',
            'yahoo.com',
            'hotmail.com',
            'bigmir.ru',
            'e-mail.ru',
            'pochta.ru',
            'rambler.ru',
            'hotmail.ru',
            'gawab.com',
            'pookmail.com',
            'tut.by',
            'a.ua',
            'e-mail.ua',
            'ua.fm'
        );

    protected $_params = array(
        'index' => array(
            'required' => array(),
            'optional' => array('fields', 'filter', 'sort_field', 'sort_direction')
        ),
        'add' => array(
            'required' => array('name', 'email'),
            'optional' => array('')
        ),
        'view' => array(
            'required' => array(),
            'optional' => array('fields')
        ),
        'edit' => array(
            'required' => array(),
            'optional' => array('name', 'approved', 'activation_code', 'default')
        ),
        'delete' => array(
            'required' => array(),
            'optional' => array()
        )
    );

    public $paginate = array(
        'UserSetting' => array(
            'recursive' => -1
        )
    );

    /**
     * returning representation of all elements
     * response status codes:
     *      200 - element found
     *      204 -  success but empty response
     * @param $id - add of requested element
     */
    public function index()
    {
        $this->_filter_inputs();
        $this->paginate['UserSetting']['conditions']['UserSetting.user_id'] = $this->Auth->user('id');
        $this->paginate['UserSetting']['conditions']['UserSetting.user_setting_field_id'] = 2;
        $fields_default = array(
            'id',
            'email',
            'name',
            'approved',
            'default',
            'last_approved'
        );
        $sort_fields_default = array(
            1 => 'email',
            2 => 'approved'

        );

        if ($this->request->data['sort_direction'] == -1) {
            $this->request->data['sort_direction'] = 'DESC';
        } else {
            $this->request->data['sort_direction'] = 'ASC';
        }

        $conditions_decode = json_decode($this->request->data['filter'], true);

        if ($conditions_decode['email']) {
            $this->paginate['UserSetting']['conditions']['UserSetting.value LIKE'] = '%"email":"' . $conditions_decode['email'] . '"%';
        }
        if ($conditions_decode['approved'] === 0 || $conditions_decode['approved'] === 1) {
            $this->paginate['UserSetting']['conditions']['UserSetting.value LIKE'] .= '%"approved":' . $conditions_decode['approved'] . '%';
        }
        if ($conditions_decode['default'] === 0 || $conditions_decode['default'] === 1) {
            $this->paginate['UserSetting']['conditions']['UserSetting.value LIKE'] .= '%"default":' . $conditions_decode['default'] . '%';
        }

        $sort_key = array_search($this->request->data['sort_field'], $sort_fields_default);
        if (!$sort_key) {
            $this->paginate['UserSetting']['order']['UserSetting.id'] = $this->request->data['sort_direction'];
        }

        $fields = json_decode($this->request->data['fields'], true);

        $fields_after_filter = array_intersect($fields_default, $fields);
        if (empty($fields_after_filter)) {
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'email';
        }

        $this->Paginator->settings = $this->paginate;
        $usersettings = $this->Paginator->paginate();


        if ($usersettings) {
            $usersettings = Set::classicExtract($usersettings, '{n}.UserSetting');


            foreach ($usersettings as $key => $value) {
                $usersettings[$key] += json_decode($value['value'], true);
                unset($usersettings[$key]['value']);
            }

            foreach ($usersettings as $k => $v) {
                foreach ($v as $key => $value) {
                    if (!in_array($key, $fields_after_filter)) {
                        unset($usersettings[$k][$key]);
                    }
                }
            }

            function email_ASC($a, $b)
            {
                return strcmp($a['email'], $b['email']);
            }

            function approved_ASC($a, $b)
            {
                return strcmp($a['approved'], $b['approved']);
            }

            function email_DESC($a, $b)
            {
                return strcmp($b['email'], $a['email']);
            }

            function approved_DESC($a, $b)
            {
                return strcmp($b['approved'], $a['approved']);
            }

            if ($sort_key) {
                if ($this->request->data['sort_direction'] == 'ASC') {
                    if ($sort_fields_default[$sort_key] == 'email') {
                        usort($usersettings, "email_ASC");
                    }
                    if ($sort_fields_default[$sort_key] == 'approved') {
                        usort($usersettings, "approved_ASC");
                    }
                } else {
                    if ($sort_fields_default[$sort_key] == 'email') {
                        usort($usersettings, "email_DESC");
                    }
                    if ($sort_fields_default[$sort_key] == 'approved') {
                        usort($usersettings, "approved_DESC");
                    }
                }
            }

            $return = array(
                'sender_emails' => $usersettings
            );
            $this->_return($return);
        } else {
            $this->_empty_response();
        }

    }

    /**
     * returning full representation of element by it's ID
     * response status codes:
     *      200 - element found
     *      400 - bad request
     *      404 - element not found
     * @param $id - add of requested element
     */
    public function view($id)
    {
        $id *= 1;;
        $this->_filter_inputs();
        $fields_default = array(
            'id',
            'email',
            'name',
            'approved',
            'default',
            'last_approved'
        );
        $fields = json_decode($this->request->data['fields'], true);
        $fields_after_filter = array_intersect($fields_default, $fields);
        if (empty($fields_after_filter)) {
            if (!empty($fields)) {
                $this->_bad_request('Invalid fields. Wrong parameters of string fields', 20420);
            }
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'email';
        }
        $usersetting = $this->UserSetting->find('first', array(
            'fields' => array(
                'UserSetting.id',
                'UserSetting.value'
            ),
            'conditions' => array(
                'UserSetting.user_id' => $this->Auth->user('id'),
                'UserSetting.user_setting_field_id' => 2,
                'UserSetting.id' => $id
            )
        ));
        if ($usersetting) {
            $usersetting = Set::classicExtract($usersetting, 'UserSetting');
            $usersetting += json_decode($usersetting['value'], true);
            unset($usersetting['value']);
            foreach ($usersetting as $k => $v) {
                if (!in_array($k, $fields_after_filter)) {
                    unset($usersetting[$k]);
                }
            }
            $this->_return($usersetting);
        } else {
            $this->_not_found();
        }

    }

    /**
     * adding new element
     * response status codes:
     *      201 - element created
     *      400 - bad request
     * @param $id - add of requested element
     */
    public function add()
    {
        $this->_filter_inputs();

        $name = trim($this->request->data['name']);
        if (empty($name)) {
            $this->_bad_request('Invalid name. Name can not be empty', 20440);
        }

        $email = strtolower(trim($this->request->data['email']));
        if (!$this->_checkEmail($email)) {
            $this->_bad_request('Invalid email. Check the validity of the specified email', 20441);
        }

        // get all settings of that type for this user
        $all_settings = $this->UserSetting->find('all', array(
            'conditions' => array(
                'UserSetting.user_id' => $this->Auth->user('id'),
                'UserSetting.user_setting_field_id' => 2
            )
        ));

        if ($this->Auth->user('login') == 'emdesell') {
            $this->sender_emails_limit = 2000;
        }

        // check sender emails limit
        if (count($all_settings) >= $this->sender_emails_limit) {
            if ($this->Auth->user('login') == 'emdesell') {
                $this->_bad_request('Exhausted the limit of sender emails. Maximum number: 2000', 20442);

            } else{
                $this->_bad_request('Exhausted the limit of sender emails. Maximum number: 10', 20442);
            }

        }

        // decode settings value field
        $all_settings = array_map(function ($value) {
            return $value['UserSetting'] + json_decode($value['UserSetting']['value'], true);
        }, $all_settings);
        $all_settings = Set::combine($all_settings, '{n}.email', '{n}');

        // check if such email not saved already
        if (!empty($all_settings[$email])) {
            $this->_bad_request('This email already exists', 20443);
        }

        // get email domain
        $email_domain = explode('@', $email)[1];

        // check if email domain not in list of restricted domain
        if (in_array($email_domain, $this->dmarc_restricted_domains)) {
            $this->_bad_request('Emails on domains ' . implode(', ', $this->dmarc_restricted_domains) . ' can not be used', 20444);
        }

        // for not Free tariff check if domain is personal
        if ($this->Auth->user('mailer_tariff_id') > 1 && in_array($email_domain, $this->non_pers_domains)) {
            $this->_bad_request('At this tariff email should be on a personal domain', 20445);
        }

        // prepare value field
        $setting_value = json_encode(array(
            'email' => $email,
            'name' => $name,
            'approved' => 0,
            'default' => 0,
            'last_approved' => time()
        ));

        // save new setting
        $this->_saveusersetting(2, $setting_value);
        $user_setting_id = $this->UserSetting->getLastInsertId();
        if (!in_array($email_domain, $this->non_pers_domains)) {

            // get all dkim settings
            $dkim_settings = $this->UserSetting->find('first', array(
                'conditions' => array(
                    'UserSetting.user_setting_field_id' => 13,
                    'UserSetting.value LIKE ' => '%"domain":"' . $email_domain . '"%'
                )
            ));

            if ($dkim_settings) { // dkim for this domain already exists, create new one based on this

                if ($dkim_settings['UserSetting']['user_id'] != $this->Auth->user('id')) {
                    $this->_saveusersetting(13, $dkim_settings['UserSetting']['value']);
                }

            } else { // dkim for this domain not exist, generate new one and save to db

                $dkim_data = $this->_generatedkim($email_domain, 'estismail');
                if (!empty($dkim_data)) {

                    $this->_saveusersetting(13, json_encode(array(
                        'domain' => $email_domain,
                        'public' => $dkim_data['public'],
                        'private' => $dkim_data['private']
                    )));
                }
            }
        }

        // get approve letter
        $letter = file_get_contents("/var/www/mailer.estismail.com/web/app/webroot/letters/api/letter-emailapprove.html");
        $activation = 'https://mailer.estismail.com/senderemailapprove/' . $user_setting_id . '/' . sha1($email . '2Y4GxNGzlf8GQVWj' . $user_setting_id);
        $activation_code = sha1($email . '2Y4GxNGzlf8GQVWj' . $user_setting_id);
        $letter = str_replace('666activate666', $activation, $letter);
        $letter = str_replace('666email666', $email, $letter);
        $letter = str_replace('666activation_code666', $activation_code, $letter);
        $data = array();

        $data['Sender']['id'] = null;
        $data['Sender']['title'] = __('Подтверждение емейла');
        $data['Sender']['body'] = $letter;
        $data['Sender']['email'] = $email;
        $data['Sender']['name'] = 'Estismail.com';
        $data['Sender']['status'] = '0';
        $data['Sender']['server_id'] = '0';
        $data['Sender']['semail'] = 'support@estismail.com';
        $this->Sender->save($data);

        $this->_created($user_setting_id);

    }

    /**
     * editing element by it's ID
     * response status codes:
     *      204 - success but empty response
     *      400 - bad request
     *      404 - element not found
     * @param $id - add of requested element
     */
    public function edit($id)
    {
        $id *= 1;
        $this->_filter_inputs();
        $usersetting = $this->UserSetting->find('first', array(
            'conditions' => array(
                'UserSetting.user_id' => $this->Auth->user('id'),
                'UserSetting.id' => $id,
                'UserSetting.user_setting_field_id' => 2
            )
        ));
        if ($usersetting) {

            $setting_data = json_decode($usersetting['UserSetting']['value'], true);

            $this->request->data['name'] = trim($this->request->data['name']);
            if (empty($this->request->data['name'])) {
                unset($this->request->data['name']);
            } else {
                $setting_data['name'] = $this->request->data['name'];
            }

            $this->request->data['approved'] *= 1;
            if ($this->request->data['approved'] == 1) {

                // check if email not approved yet
                if ($setting_data['approved']) {
                    $this->_bad_request('Email is already approved', 20460);
                }

                $this->request->data['activation_code'] = trim($this->request->data['activation_code']);
                if (empty($this->request->data['activation_code'])) {

                    if (time() - $setting_data['last_approved'] < 60) {
                        $this->_bad_request('Send email confirmation can not occur more than once per minute', 20461);
                    }
                    // get approve letter one more time
                    $letter = file_get_contents("/var/www/mailer.estismail.com/web/app/webroot/letters/api/letter-emailapprove.html");
                    $activation = 'https://mailer.estismail.com/senderemailapprove/' . $id . '/' . sha1($setting_data['email'] . '2Y4GxNGzlf8GQVWj' . $id);
                    $activation_code = sha1($setting_data['email'] . '2Y4GxNGzlf8GQVWj' . $id);
                    $letter = str_replace('666activate666', $activation, $letter);
                    $letter = str_replace('666email666', $setting_data['email'], $letter);
                    $letter = str_replace('666activation_code666', $activation_code, $letter);

                    $data = array();

                    $data['Sender']['id'] = null;
                    $data['Sender']['title'] = __('Подтверждение емейла');
                    $data['Sender']['body'] = $letter;
                    $data['Sender']['email'] = $setting_data['email'];
                    $data['Sender']['name'] = 'Estismail.com';
                    $data['Sender']['status'] = '0';
                    $data['Sender']['server_id'] = '0';
                    $data['Sender']['semail'] = 'support@estismail.com';
                    $this->Sender->save($data);

                    $setting_data['last_approved'] = time();

                } else {
                    $code = $this->request->data['activation_code'];
                    if (sha1($setting_data['email'] . '2Y4GxNGzlf8GQVWj' . $id) != $code) {
                        $this->_bad_request('Wrong activation code', 20462);
                    }
                    // email is approved
                    $setting_data['approved'] = 1;

                    // check if there is an default email
                    $default_emails = $this->UserSetting->find('count', array(
                        'conditions' => array(
                            'UserSetting.user_id' => $this->Auth->user('id'),
                            'UserSetting.user_setting_field_id' => 2,
                            'UserSetting.value LIKE' => '%"default":1%',
                            'NOT' => array(
                                'UserSetting.id' => $id
                            )
                        )
                    ));

                    // if no default emails fount, save current as default
                    if (!$default_emails) {
                        $setting_data['default'] = 1;
                        $this->_updatedefaultemailandname($setting_data['email'], $setting_data['name']);
                    }
                }

            }

            $this->request->data['default'] *= 1;
            if ($this->request->data['default'] == 1) {

                // check if email approved
                if (!$setting_data['approved']) {
                    $this->_bad_request('Email is not approved. Before you set this email as default , you have to approve it', 20463);
                }

                // check if email not default already
                if ($setting_data['default']) {
                    $this->_bad_request('Email is already set as default', 20464);
                }

                // find previous default email
                $default_emails = $this->UserSetting->find('all', array(
                    'conditions' => array(
                        'UserSetting.user_id' => $this->Auth->user('id'),
                        'UserSetting.user_setting_field_id' => 2,
                        'UserSetting.value LIKE' => '%"default":1%',
                        'NOT' => array(
                            'UserSetting.id' => $id
                        )
                    )
                ));

                // make previous emails not default
                if (!empty($default_emails)) {
                    foreach ($default_emails as $key => $value) {

                        $def_email_value = json_decode($value['UserSetting']['value'], true);
                        $def_email_value['default'] = 0;

                        $this->UserSetting->id = $value['UserSetting']['id'];
                        $this->UserSetting->saveField('value', json_encode($def_email_value));
                        $this->UserSetting->id = null;

                    }
                }
                // set current email as default
                $setting_data['default'] = 1;

                $this->_updatedefaultemailandname($setting_data['email'], $setting_data['name']);
                $email_domain = explode('@', $setting_data['email'])[1];
                $this->_updatedefaultdkim($email_domain);
            }


            $this->UserSetting->id = $id;
            $this->UserSetting->saveField('value', json_encode($setting_data));

        } else {
            $this->_not_found();
        }

        $this->_empty_response();

    }

    /**
     * deleting element by it's ID
     * response status codes:
     *      204 - success but empty response
     *      404 - element not found
     * @param $id - add of requested element
     */
    public function delete($id)
    {
        $id *= 1;
        $this->_filter_inputs();

        // get current setting
        $setting = $this->UserSetting->find('first', array(
            'conditions' => array(
                'UserSetting.user_id' => $this->Auth->user('id'),
                'UserSetting.id' => $id,
                'UserSetting.user_setting_field_id' => 2
            )
        ));

        if (empty($setting)) {
            $this->_not_found();
        }

        $setting_data = json_decode($setting['UserSetting']['value'], true);

        // check if email not set as default
        if ($setting_data['default']) {
            $this->_bad_request('Wrong id. This email is default. You can not delete the default email', 20480);
        }

        // clear this email from pro_campaigns
        $this->ProCampaign->updateAll(array(
            'user_setting_id' => null
        ), array(
            'ProCampaign.user_id' => $this->Auth->user('id'),
            'UserSetting.id' => $id
        ));

        // get sender email on current domain
        $email_domain = explode('@', $setting_data['email'])[1];
        $another_emails = $this->UserSetting->find('count', array(
            'conditions' => array(
                'UserSetting.user_id' => $this->Auth->user('id'),
                'UserSetting.value LIKE ' => '%@' . $email_domain . '%',
                'UserSettingField.id' => 2,
                'NOT' => array(
                    'UserSetting.id' => $id
                )
            )
        ));

        // if no another email found - delete dkim settings for this domain from this user
        if (!$another_emails) {
            $this->UserSetting->deleteAll(array(
                'UserSetting.user_id' => $this->Auth->user('id'),
                'UserSetting.user_setting_field_id' => 13,
                'UserSetting.value LIKE ' => '%"domain":"' . $email_domain . '"%'
            ));
        }

        // delete
        $this->UserSetting->id = $id;
        $this->UserSetting->delete($id);

        $this->_empty_response();

    }

    /*
     * UTILITIES SECTION
     */

    private function _checkEmail($email)
    {

        preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD',
            $email,
            $rez
        );

        return !empty($rez[0]);
    }


    private function _saveusersetting($user_setting_field_id, $value, $user_id = null)
    {

        $user_setting_field_id *= 1;


        $user_id = $this->Auth->user('id');


        $user_setting_field = $this->UserSettingField->findById($user_setting_field_id);

        if ($user_setting_field) { // user setting exist

            if ($user_setting_field['UserSettingField']['unique']) { // field is unique

                $user_setting = $this->UserSetting->find('first', array(
                    'conditions' => array(
                        'UserSetting.user_id' => $user_id,
                        'UserSetting.user_setting_field_id' => $user_setting_field_id
                    )
                ));

                if ($user_setting) { // setting already exist

                    if ($user_setting['UserSetting']['value'] != $value) { // new value for existing setting

                        $this->UserSetting->id = $user_setting['UserSetting']['id'];
                        $this->UserSetting->saveField('value', $value);

                    }

                } else { // setting not exist, save new one

                    $this->UserSetting->id = null;
                    $this->UserSetting->save(array(
                        'user_id' => $user_id,
                        'user_setting_field_id' => $user_setting_field_id,
                        'value' => $value
                    ));
                    $this->UserSetting->id = null;

                }

            } else { // not unique setting, save new one

                $this->UserSetting->id = null;
                $this->UserSetting->save(array(
                    'user_id' => $user_id,
                    'user_setting_field_id' => $user_setting_field_id,
                    'value' => $value
                ));
                $this->UserSetting->id = null;

            }

        }

    }

    private function _generatedkim($domain, $selector = "default")
    {
        $this->autoRender = false;

        $ch = curl_init();
        $url = 'https://www.port25.com/dkim-wizard/';
        $fields = array(
            'domain' => $domain,
            'selector' => $selector,
            'keysize' => '1024'
        );
        $fields_string = "";
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $result = curl_exec($ch);
        curl_close($ch);

        $public = [];
        preg_match(
            "/(\-\-\-\-\-BEGIN PUBLIC KEY\-\-\-\-\-([\sa-zA-Z0-9\+\/]*)\-\-\-\-\-END PUBLIC KEY\-\-\-\-\-)/",
            $result,
            $public
        );
        $public = str_replace("\n", "", $public[2]);
//		$public = $public[0];

        $private = [];
        preg_match(
            "/(\-\-\-\-\-BEGIN RSA PRIVATE KEY\-\-\-\-\-([\s=a-zA-Z0-9\+\/]*)\-\-\-\-\-END RSA PRIVATE KEY\-\-\-\-\-)/",
            $result,
            $private
        );

        $private = $private[0];


        //это строка, которую пользователь должен внести в свой домен. можешь ее сразу выдать, и потом при запросе
//		$dns1 = $selector . '._domainkey IN TXT "v=DKIM1; k=rsa; p='.$z.'"';

        return array(
            'public' => $public,
            'private' => $private
        );
    }

    private function _updatedefaultemailandname($default_email, $default_name, $user_id = null)
    {

        $this->User->id = $this->Auth->user('id');
        $this->User->saveField('default_email', $default_email);
        $this->User->saveField('default_name', $default_name);
    }

    private function _updatedefaultdkim($domain, $user_id = null)
    {

        $user_id = $this->Auth->user('id');

        // get all dkim settings
        $dkim_settings = $this->UserSetting->find('first', array(
            'conditions' => array(
                'UserSetting.user_setting_field_id' => 13,
                'UserSetting.value LIKE ' => '%"domain":"' . $domain . '"%',
                'UserSetting.user_id' => $user_id
            )
        ));

        if ($dkim_settings) { // dkim setting exist

            $setting_data = json_decode($dkim_settings['UserSetting']['value'], true);
            $this->User->save(array(
                'id' => $user_id,
                'domen' => $domain,
                'public_key' => $setting_data['public'],
                'private_key' => $setting_data['private']
            ));

        }

    }
    /*
     * UTILITIES SECTION END
     */
}
