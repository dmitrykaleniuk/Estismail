<?php

App::uses('AppController', 'Controller');

class EmailsController extends AppController
{

    public $uses = array(
        'Lisst',
        'Campaign',
        'CampaignsEmail',
        'Email',
        'EmailLog',
        'EmailsLisst',
        'EmailField',
        'LisstFieldSchema',
        'ProCampaignInput',
        'ProCampaignEmail',
        'ProCampaignPointer',
        'UserSetting',
        'Form',
        'Server',
        'SendsServer',
        'Queue',
        'Letter',
        'UserSettingField',
        'BlacklistedEmail'
    );

    protected $_params = array(
        'index' => array(
            'required' => array(),
            'optional' => array('limit', 'page', 'fields', 'list_id', 'filter', 'sort_field', 'sort_direction')
        ),
        'add' => array(
            'required' => array('email', 'list_id'),
            'optional' => array('name', 'city', 'phone', 'ip', 'skype', 'subscribe_link', 'form_id', 'activation_letter')
        ),
        'view' => array(
            'required' => array(),
            'optional' => array('fields', 'list_id')
        ),
        'edit' => array(
            'required' => array(),
            'optional' => array('name', 'city', 'phone', 'skype')
        ),
        'delete' => array(
            'required' => array(),
            'optional' => array('list_id')
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
        // Configure::write('debug', 2);
        $this->_filter_inputs();
        $limit = $this->_setlimit($this->request->data['limit']);


        $this->request->data['page'] *= 1;
        if ($this->request->data['page'] < 1) {
            $this->request->data['page'] = 1;
        }
        $number = $this->request->data['page'];

        if (!is_numeric($this->request->data['list_id'])) {
            unset($this->request->data['list_id']);
        }

        $fields_default = array(
            'id',
            'name',
            'email',
            'city',
            'phone',
            'date',
            'status',
            'ip',
            'skype',
            'subscribe_link'
        );

        $sort_fields_default = array(
            1 => 'email',
            2 => 'date',
            3 => 'status'
        );

        $fields = json_decode($this->request->data['fields'], true);

        $fields_after_filter = array_intersect($fields_default, $fields);
        if (empty($fields_after_filter)) {
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'email';
        }
        $key = array_search('subscribe_link', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'sub_link';
        }

        if ($this->request->data['sort_direction'] == -1) {
            $this->request->data['sort_direction'] = 'DESC';
        } else {
            $this->request->data['sort_direction'] = 'ASC';
        }
        $sort_key = array_search($this->request->data['sort_field'], $sort_fields_default);
        $conditions_decode = json_decode($this->request->data['filter'], true);

        if (empty($this->request->data['list_id'])) {
            $source_changed = false;
            try {
                $this->Email->setSource('emails_' . $this->Auth->user('id'));
                $source_changed = true;
            } catch (Exception $ex) {
                $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
                $source_changed = false;
            }
            $key = array_search('status', $fields_after_filter);
            if ($key) {
                $fields_after_filter[$key] = 'blocked';
            }

            $conditions['Email.user_id'] = $this->Auth->user('id');
            if ($conditions_decode['email']) {
                $conditions['Email.email'] = $conditions_decode['email'];
            }
            if ($conditions_decode['status'] === 0 || $conditions_decode['status'] === 1 || $conditions_decode['status'] === 2 || $conditions_decode['status'] === 3) {
                $conditions['Email.blocked'] = $conditions_decode['status'];
            }
            if ($conditions_decode['date']['start_date']) {
                $conditions['Email.date >='] = date('Y-m-d H:i:s.u', $conditions_decode['date']['start_date']);
            }
            if ($conditions_decode['date']['end_date']) {
                $conditions['Email.date <='] = date('Y-m-d H:i:s.u', $conditions_decode['date']['end_date']);
            }

            if ($sort_key) {
                if ($sort_fields_default[$sort_key] == 'status') {
                    $sort_fields_default[$sort_key] = 'blocked';
                }
                $sort['Email.' . $sort_fields_default[$sort_key]] = $this->request->data['sort_direction'];
            } else {
                $sort['Email.id'] = $this->request->data['sort_direction'];
            }

            if ($source_changed) {
                $emails = $this->Email->find('all', array(
                    'fields' => $fields_after_filter,
                    'conditions' => $conditions,
                    'recursive' => -1,
                    'limit' => $limit,
                    'page' => $number,
                    'order' => $sort
                ));

            } else {
                $emails = $this->Email->find('all', array(
                    'fields' => $fields_after_filter,
                    'conditions' => $conditions,
                    'recursive' => -1,
                    'limit' => $limit,
                    'page' => $number,
                    'order' => $sort
                ));
            }

        } else {
            $number = $limit * ($number - 1);

            function filter($array)
            {
                if ($array == 'status' || $array == 'date') {
                    return ('el.' . $array);
                } else {
                    return ('e.' . $array);
                }
            }

            $conditions_list_id = '';
            if ($conditions_decode['email']) {
                $conditions_list_id .= ' AND e.email = \'' . $conditions_decode['email'] . '\'';
            }
            if ($conditions_decode['status'] === 0 || $conditions_decode['status'] === 1 || $conditions_decode['status'] === 2 || $conditions_decode['status'] === 3) {
                $conditions_list_id .= ' AND el.status = \'' . $conditions_decode['status'] . '\'';
            }
            if ($conditions_decode['date']['start_date']) {
                $conditions_list_id .= ' AND el.date >= \'' . date('Y-m-d H:i:s.u', $conditions_decode['date']['start_date']) . '\'';
            }
            if ($conditions_decode['date']['end_date']) {
                $conditions_list_id .= ' AND el.date <= \'' . date('Y-m-d H:i:s.u', $conditions_decode['date']['end_date']) . '\'';
            }

            $fields_after_filter = array_map("filter", $fields_after_filter);
            $fields_after_filter = implode(',', $fields_after_filter);

            if ($sort_key) {
                $sort_fields_default = array_map("filter", $sort_fields_default);
                $sort = $sort_fields_default[$sort_key] . ' ' . $this->request->data['sort_direction'];
            } else {
                $sort = 'e.id ' . $this->request->data['sort_direction'];
            }

            $query = 'SELECT ' . $fields_after_filter . ' FROM emails_lissts_' . $this->Auth->user('id') . ' AS el
						LEFT JOIN emails_' . $this->Auth->user('id') . ' AS e
							ON el.email_id = e.id
						WHERE el.lisst_id = ' . $this->request->data['list_id'] . $conditions_list_id
                . ' order by ' . $sort
                . ' LIMIT ' . $limit
                . ' OFFSET ' . $number;
            try {
                $emails = $this->EmailsLisst->query($query);
            } catch (Exception $ex) {
                $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
                $emails = array();
            }
        }

        if ($emails) {

            if (!empty($this->request->data['list_id'])) {
                $emails = Set::classicExtract($emails, '{n}.0');

            } else {
                $emails = Set::classicExtract($emails, '{n}.Email');
            }
            foreach ($emails as $key => $value) {
                $keyexist_email = array_key_exists('email', $value);
                if ($keyexist_email) {
                    $emails[$key]['email'] = trim($value['email']);
                }
                $keyexist_sub_link = array_key_exists('sub_link', $value);
                if ($keyexist_sub_link) {
                    $emails[$key]['subscribe_link'] = $value['sub_link'];
                    unset($emails[$key]['sub_link']);
                }
                $keyexist_blocked = array_key_exists('blocked', $value);
                if ($keyexist_blocked) {
                    $emails[$key]['status'] = $value['blocked'];
                    unset($emails[$key]['blocked']);
                }
                $keyexist_date = array_key_exists('date', $value);
                if ($keyexist_date) {
                    $emails[$key]['date'] = strtotime($emails[$key]['date']);
                }
            }


            $return = array(
                'emails' => $emails
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
        //Configure::write('debug', 2);
        $id *= 1;
        $this->_filter_inputs();

        $emails_lisst = false;
        $fields_default = array(
            'id',
            'name',
            'email',
            'city',
            'phone',
            'date',
            'status',
            'ip',
            'skype',
            'subscribe_link'
        );
        $fields = json_decode($this->request->data['fields'], true);
        $fields_after_filter = array_intersect($fields_default, $fields);
        if (empty($fields_after_filter)) {
            if (!empty($fields)) {
                $this->_bad_request('Invalid fields. Wrong parameters of string fields', 20120);
            }
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'email';
        }

        $source_changed = false;
        try {
            $this->Email->setSource('emails_' . $this->Auth->user('id'));
            $source_changed = true;
        } catch (Exception $ex) {
            $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
            $source_changed = false;
        }

        $key = array_search('status', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'blocked';
        }
        $key = array_search('subscribe_link', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'sub_link';
        }

        if ($source_changed) {
            $email = $this->Email->find('first', array(
                'fields' => $fields_after_filter,
                'conditions' => array(
                    'Email.id' => $id
                ),
                'recursive' => -1
            ));

        } else {
            $email = $this->Email->find('first', array(
                'fields' => $fields_after_filter,
                'conditions' => array(
                    'Email.user_id' => $this->Auth->user('id'),
                    'Email.id' => $id
                ),
                'recursive' => -1
            ));
        }

        if (!is_numeric($this->request->data['list_id']) || $this->request->data['list_id'] <= 0) {
            unset($this->request->data['list_id']);
        }
        if (!empty($this->request->data['list_id'])) {
            $source_changed = false;
            try {
                $this->EmailsLisst->setSource('emails_lissts_' . $this->Auth->user('id'));
                $source_changed = true;
            } catch (Exception $ex) {
                $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
                $source_changed = false;
            }
            if ($source_changed) {
                $emails_lisst = $this->EmailsLisst->find('first', array(
                    'fields' => array('status', 'date'),
                    'conditions' => array(
                        'EmailsLisst.lisst_id' => $this->request->data['list_id'],
                        'EmailsLisst.email_id' => $id
                    ),
                    'recursive' => -1
                ));
            } else {
                $emails_lisst = $this->EmailsLisst->find('first', array(
                    'fields' => array('status', 'date'),
                    'conditions' => array(
                        'EmailsLisst.user_id' => $this->Auth->user('id'),
                        'EmailsLisst.lisst_id' => $this->request->data['list_id'],
                        'EmailsLisst.email_id' => $id
                    ),
                    'recursive' => -1
                ));

            }
        }

        if ($email) {
            $email = Set::classicExtract($email, 'Email');

            $keyexist = array_key_exists('email', $email);
            if ($keyexist) {
                $email['email'] = trim($email['email']);

            }
            $keyexist = array_key_exists('blocked', $email);
            if ($keyexist) {
                $email['status'] = $email['blocked'];
                unset($email['blocked']);
            }
            $keyexist = array_key_exists('sub_link', $email);
            if ($keyexist) {
                $email['subscribe_link'] = $email['sub_link'];
                unset($email['sub_link']);
            }
            $keyexist_date1 = array_key_exists('date', $email);
            $keyexist_date2 = array_key_exists('date', $emails_lisst);
            if ($keyexist_date1 || $keyexist_date2) {
                if ($emails_lisst) {
                    $email['date'] = strtotime($emails_lisst['EmailsLisst']['date']);
                    $email['status'] = $emails_lisst['EmailsLisst']['status'];
                } else {
                    $email['date'] = strtotime($email['date']);
                }
            }
            $this->_return($email);
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
        $user_id = $this->Auth->user('id');

        $email = strtolower(trim($this->request->data['email']));
        if (!$this->_checkEmail($email)) {
            $this->_bad_request('Invalid email. Check the validity of the specified email', 20140);
        }

        if (is_numeric($this->request->data['activation_letter'])) {
            if ($this->request->data['activation_letter'] != 1 && $this->request->data['activation_letter'] != 0) {
                $this->request->data['activation_letter'] = 1;
            }
        } else {
            $this->request->data['activation_letter'] = 1;
        }
        $activation_letter = $this->request->data['activation_letter'];

        $form_id = $this->request->data['form_id'] * 1;
        $form = $this->Form->find("first", array(
            "conditions" => array(
                "Form.id" => $form_id,
                "Form.user_id" => $user_id
            ),
            'recursive' => -1
        ));
        if (!empty($form)) {
            $send_flag = $form['Form']['send'];
            $lisst_id = $form['Form']['lissts'];
        } else {
            $send_flag = $activation_letter;
            $lisst_id = $this->request->data['list_id'] * 1;
        }

        $lisst = $this->Lisst->find('first', array(
            'conditions' => array(
                'Lisst.user_id' => $user_id,
                'Lisst.id' => $lisst_id
            ),
            'recursive' => -1
        ));

        if (empty($lisst)) {
            $this->_bad_request('Invalid list_id. List with specified id does not exist', 20141);
        }


        $sub_link_default = '';
        $check_url = preg_match('/\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $this->request->data['subscribe_link']);
        if ($check_url == true) {
            $sub_link_default = $this->request->data['subscribe_link'];
        }
        $sub_link = ($sub_link_default) ? $sub_link_default : $lisst['Lisst']['sub_link'];


        $name = '';
        if (!(trim($this->request->data['name']) == false)) {
            $name = $this->request->data['name'];
        }

        $phone = '';
        if (!(trim($this->request->data['phone']) == false)) {
            $phone = $this->request->data['phone'];
        }

        $city = '';
        if (!(trim($this->request->data['city']) == false)) {
            $city = $this->request->data['city'];
        }

        $skype = '';
        if (!(trim($this->request->data['skype']) == false)) {
            $skype = $this->request->data['skype'];
        }

        $ip = '';
        if (filter_var($this->request->data['ip'], FILTER_VALIDATE_IP)) {
            $ip = $this->request->data['ip'];
        }

		ini_set('memory_limit', '256M');

        $query = 'SELECT e.id, e.email, el.lisst_id, e.name, e.ip, e.date, e.sub_link, e.blocked, el.status FROM emails_lissts_' . $user_id . ' AS el
                                     LEFT JOIN emails_' . $user_id . ' AS e
                                         ON el.email_id = e.id
                                     WHERE el.lisst_id = ' . $lisst_id . '
                                     AND e.email LIKE \'' . $email . '%\'
                                     LIMIT 1';

        $email_exist = array();
        try {
            $email_exist = $this->EmailsLisst->query($query);
            $email_exist['Email'] = $email_exist[0][0];
            unset($email_exist[0]);
        } catch (Exception $ex) {
            $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
        }

        $email_id = 0;
        $exist = 0;
        $blocked = 0;
        if ($email_exist['Email']) {
            $exist = true;
            $blocked = ($email_exist['Email']['blocked']) ?: $email_exist['Email']['status'];
        }

        if (!$exist || $blocked == 1) {
            $email_info = array();


            $sub_limits = $this->_subscriberslimit($user_id);



            if ($sub_limits['total']['number'] + 1 > $sub_limits['limit']['number']) {
                $this->Lisst->id = $lisst_id;
                $this->Lisst->saveField('status', 0);
                $this->Lisst->id = null;
            }

            $email_info['name'] = $name;
            $email_info['email'] = $email;
            $email_info['user_id'] = $user_id;
            $email_info['lisst_id'] = $lisst_id;
            $email_info['phone'] = $phone;
            $email_info['city'] = $city;
            $email_info['skype'] = $skype;
            $email_info['blocked'] = ($send_flag) ? 3 : 0;
            $email_info['ip'] = $ip;
            $email_info['sub_link'] = $sub_link;


            $db_changed = false;
            try {
                $this->Email->setSource('emails_' . $user_id);
                $db_changed = true;
            } catch (Exception $ex) {
                $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
            }
            if ($db_changed) {
                $this->Email->save($email_info);
            } else {
                $email_info['user_id'] = $user_id;
                $this->Email->save($email_info);
            }

            $query = 'SELECT e.id FROM emails_lissts_' . $user_id . ' AS el
                                     LEFT JOIN emails_' . $user_id . ' AS e
                                         ON el.email_id = e.id
                                     WHERE el.lisst_id = ' . $lisst_id . '
                                     AND e.email LIKE \'' . $email . '%\'
                                     LIMIT 1';
            $last_email_id = array();

            try {
                $last_email_id = $this->EmailsLisst->query($query);
                $email_id = $last_email_id[0][0]['id'];
            } catch (Exception $ex) {
                $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
            }
            $email_log = array();

            $email_log['email_id'] = $email_id;
            $email_log['user_id'] = $user_id;
            $email_log['lisst_id'] = $lisst_id;
            $email_log['ip'] = $ip;
            $email_log['email_log_action_id'] = ($blocked) ? 23 : 1;
            $email_log['data'] = serialize(array('referer' => $sub_link));

            try {
                $this->EmailLog->setSource('email_logs_' . $user_id);
            } catch (Exception $ex) {
                $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
            }

            $this->EmailLog->save($email_log);

            // check blacklist
            $bl_email = $this->BlacklistedEmail->find('first', array(
                'conditions' => array(
                    'BlacklistedEmail.email' => $email,
                    'BlacklistedEmail.user_id' => $user_id,
                )
            ));
            if ($bl_email) {

                $this->BlacklistedEmail->id = $bl_email['BlacklistedEmail']['id'];
                $this->BlacklistedEmail->saveField('email_id', $email_id);

                if ($db_changed) {
                    $this->Email->id = $email_id;
                    $this->Email->saveField('blocked', 1);
                } else {
                    $this->Email->updateAll(array(
                        'Email.blocked' => 1
                    ), array(
                        'Email.id' => $email_id,
                        'Email.user_id' => $user_id
                    ));
                    //email_id, user_id, blocked
                }
                $this->EmailLog->save(array(
                        'email_id' => $email_id,
                        'lisst_id' => 0,
                        'user_id' => $user_id,
                        'email_log_action_id' => 27,
                        'data' => json_encode(array(
                            'action' => 'add_email_api'
                        ))
                    )
                );
                // save unblock log


                $this->_created($email_id);
                return;
            }

            if (!$send_flag) {

                $pro_campaigns = $this->ProCampaignInput->find('all', array(
                    'conditions' => array(
                        'ProCampaign.user_id' => $user_id,
                        'ProCampaignInput.pro_campaign_input_type_id' => 1,
                        'ProCampaignInput.input_id' => $lisst_id,
                        'ProCampaignInput.status' => 1
                    )
                ));

                foreach ($pro_campaigns as $camp_key => $camp_value) {
                    $exist = $this->ProCampaignEmail->find('count', array(
                        'conditions' => array(
                            'ProCampaignEmail.pro_campaign_id' => $camp_value['ProCampaign']['id'],
                            'ProCampaignEmail.user_id' => $user_id,
                            'ProCampaignEmail.pro_campaign_input_id' => $camp_value['ProCampaignInput']['id'],
                            'ProCampaignEmail.email_id' => $email_id
                        )
                    ));
                    if (!$exist) {

                        $this->ProCampaignEmail->save(array(
                            'pro_campaign_id' => $camp_value['ProCampaign']['id'],
                            'user_id' => $user_id,
                            'pro_campaign_input_id' => $camp_value['ProCampaignInput']['id'],
                            'email_id' => $email_id,
                            'lisst_id' => $lisst_id
                        ));
                    }
                }
            }
        } else {
            $this->_bad_request('This email is already subscribed', 20142);
        }

        if ($send_flag) {
            $user_data = $this->Auth->user();

            $hash = sha1(trim($lisst['Lisst']['id']) . trim($email) . "40tovzsb");
            $url = "mailer.estismail.com/subscriberactivate/" . $lisst['Lisst']['id'] . "/" . $email . "/" . $hash;

            $letter = '';
            if (!empty($lisst['Lisst']['sub_maket'])
                && $lisst['Lisst']['use_personal_makets']
                && strstr($lisst['Lisst']['sub_maket'], '{activation_link}')
            ) {
                $letter = $lisst['Lisst']['sub_maket'] . '666footer666';
            } else {
                $letter = file_get_contents("/var/www/mailer.estismail.com/web/app/webroot/letters/letter-link-podpiska.html");
                //$url = 'http://' . $url;
            }
            $check_http_exist = preg_match('/\b(http|https):\/\/{activation_link}/', $letter);
            if ($check_http_exist == false) {
                $url = 'http://' . $url;
            }

            //$text = 'Вы только что подписались на рассылку ';
            $text = '';
            $text .= $lisst['Lisst']['title'];

            $setting_data = json_decode($lisst['UserSetting']['value'], true);
            if ($setting_data['approved']) {
                $user_data['default_name'] = $setting_data['name'];
                $user_data['default_email'] = $setting_data['email'];
            }

            $footer = $this->personal_footer($user_data);
            $letter = str_replace("666text666", $text, $letter);
            $letter = str_replace("666link666", $url, $letter);
            $letter = str_replace("666footer666", $footer, $letter);
            $letter = str_replace("{activation_link}", $url, $letter);
            $letter = str_replace("{lisst_name}", $lisst['Lisst']['title'], $letter);

            $let['title'] = '';
            if (!empty($lisst['Lisst']['sub_maket_title']) && $lisst['Lisst']['use_personal_makets']) {
                $lisst['Lisst']['sub_maket_title'] = str_replace("{lisst_name}", $lisst['Lisst']['title'], $lisst['Lisst']['sub_maket_title']);
                $let['title'] = $lisst['Lisst']['sub_maket_title'];
            } else {
                $let['title'] = "Подтвердите подписку на " . $lisst['Lisst']['title'];
            }

            $let['body'] = $letter;
            $let['user_id'] = $user_id;
            $let['name'] = (!empty($user_data['default_name'])) ? $user_data['default_name'] : $user_data['name'] . ' ' . $user_data['lastname'];
	        $let['login'] = trim(strtolower($user_data['login']));
            $this->Letter->save($let);
            $letter_id = $this->Letter->getLastInsertId();

            $data['Queue']['letter_id'] = $letter_id;
            $data['Queue']['email'] = $email;
            $data['Queue']['email_id'] = $email_id;
            $data['Queue']['send_id'] = 0;
            $data['Queue']['semail'] = (!empty($user_data['default_email'])) ? $user_data['default_email'] : $user_data['email'];
            $data['Queue']['title'] = "Подтвердите подписку на " . $lisst['Lisst']['title'];
            $data['Queue']['name'] = (!empty($user_data['default_name'])) ? $user_data['default_name'] : $user_data['name'] . ' ' . $user_data['lastname'];
            $data['Queue']['status'] = 0;
            $data['Queue']['lisst_id'] = $email_info['lisst_id'];
            $data['Queue']['email_name'] = $email_info['name'];
            $data['Queue']['email_ip'] = $email_info['ip'];
            $data['Queue']['email_date'] = date('Y-m-d G:i');
            $data['Queue']['sub_link'] = $sub_link;

            $server = $this->Server->find('all', array(
                'conditions' => array(
                    'Server.servers_group_id' => $user_data['servers_group_id'],
                    'Server.user_id' => 1
                ),
                'recursive' => -1
            ));

            if (empty($server)) {
                $server = $this->Server->find('all', array(
                    'conditions' => array(
                        'Server.servers_group_id' => null,
                        'Server.user_id' => 1
                    ),
                    'recursive' => -1
                ));
            }
            $server = $server[time() % count($server)];
            $data['Queue']['server_id'] = $server['Server']['id'];

            try {
                $this->Queue->setSource('queues_0');
            } catch (Exception $ex) {
                $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
            }
            $this->Queue->save($data['Queue']);


            $this->SendsServer->save(array(
                'send_id' => 0,
                'server_id' => $server['Server']['id'] * 1
            ));
        }


        $this->_created($email_id);

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
        $user_id = $this->Auth->user('id');

        $db_changed = false;
        try {
            $this->Email->setSource('emails_' . $user_id);
            $db_changed = true;
        } catch (Exception $ex) {
            $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
        }
        if ($db_changed) {
            $email = $this->Email->find('first',
                array(
                    'conditions' => array(
                        'Email.id' => $id
                    ),
                    'recursive' => -1
                )
            );
        } else {
            $email = $this->Email->find('first',
                array(
                    'conditions' => array(
                        'Email.user_id' => $user_id,
                        'Email.id' => $id
                    ),
                    'recursive' => -1
                )
            );
        }
        if ($email) {

            if (isset($this->request->data['name'])) {
                $name = '';
                if (trim($this->request->data['name']) != '') {
                    $name = $this->request->data['name'];
                }
                $email_info['name'] = $name;
            }


            if (isset($this->request->data['phone'])) {
                $phone = '';
                if (trim($this->request->data['phone']) != '') {
                    $phone = $this->request->data['phone'];
                }
                $email_info['phone'] = $phone;
            }


            if (isset($this->request->data['city'])) {
                $city = '';
                if (trim($this->request->data['city']) != '') {
                    $city = $this->request->data['city'];
                }
                $email_info['city'] = $city;

            }


            if (isset($this->request->data['skype'])) {
                $skype = '';
                if (trim($this->request->data['skype']) != '') {
                    $skype = trim($this->request->data['skype']);
                }
                $email_info['skype'] = $skype;
            }


            if (empty($email_info)) {
                $this->_bad_request('Arguments are empty', 20160);
            }

            $this->Email->id = $id;

            if ($db_changed) {
                $this->Email->save($email_info);
            } else {
                $email_info['user_id'] = $user_id;
                $this->Email->save($email_info);
            };

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

        //$this->_not_allowed();
        //return;

        $id *= 1;
        $this->_filter_inputs();
        $list_id = $this->request->data['list_id'];
        if ($list_id) {
            $db_changed = false;
            try {
                $this->EmailsLisst->setSource('emails_lissts' . $this->Auth->user('id'));
                $db_changed = true;
            } catch (Exception $ex) {
                $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
            }
            if ($db_changed) {
                $email = $this->EmailsLisst->find('first',
                    array(
                        'conditions' => array(
                            'EmailsLisst.email_id' => $id,
                            'EmailsLisst.lisst_id' => $list_id
                        ),
                        'recursive' => -1
                    )
                );

            } else {
                $email = $this->EmailsLisst->find('first',
                    array(
                        'conditions' => array(
                            'EmailsLisst.user_id' => $this->Auth->user('id'),
                            'EmailsLisst.email_id' => $id,
                            'EmailsLisst.lisst_id' => $list_id
                        ),
                        'recursive' => -1
                    )
                );
            }

        } else {
            $list_id = NULL;
            $db_changed = false;
            try {
                $this->Email->setSource('emails_' . $this->Auth->user('id'));
                $db_changed = true;
            } catch (Exception $ex) {
                $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
            }
            if ($db_changed) {
                $email = $this->Email->find('first',
                    array(
                        'conditions' => array(
                            'Email.id' => $id
                        ),
                        'recursive' => -1
                    )
                );

            } else {
                $email = $this->Email->find('first',
                    array(
                        'conditions' => array(
                            'Email.user_id' => $this->Auth->user('id'),
                            'Email.id' => $id
                        ),
                        'recursive' => -1
                    )
                );
            }

        }
        if ($email) {

            // delete emails from this lisst
            $this->emaildelete($id, $list_id);

            $this->_empty_response();

        } else {
            $this->_not_found();
        }

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

    //	duplicates function in my.estismail.com app controller, if you made some changes here - don't forget to do the same there
    private function _subscriberslimit($user_id = null, $emails_limit = null)
    {
        if (!$user_id) {
            $user_id = $this->Auth->user('id');
        }

        $ret = array(
            'total' => array(
                'percent' => 0,
                'number' => 0
            ),
            'limit' => array(
                'percent' => 0,
                'number' => 0
            ),
            'left' => array(
                'percent' => 0,
                'number' => 0
            )
        );

	    $emails_count = 0;

	    $query = '
			SELECT COUNT(DISTINCT(email_id)) AS c FROM emails_lissts_'.$user_id.'
				WHERE status IN (0, 3)
					AND lisst_id IN (
						SELECT id FROM lissts
							WHERE user_id = '.$user_id.'
								AND status = 1
					)
					AND email_id NOT IN (
						SELECT id FROM emails_'.$user_id.'
							WHERE blocked = 1
					)
		';

	    try {
		    $emails_count = $this->Email->query($query);
		    $emails_count = $emails_count[0][0]['c'];
	    } catch(Exception $ex) {

	    }

	    $ret['total']['number'] = $emails_count;

	    if (empty($emails_limit)) {
		    $emails_limit = ($this->_gettariffvalue('emails_limit', $user_id)) ? : 0;
	    }

        $ret['limit']['number'] = $emails_limit;
        $ret['limit']['percent'] = 100;

        $emails_percent = number_format((($emails_count / $emails_limit) * 100), 2);
        $emails_percent = ($emails_percent <= 100) ? $emails_percent : 100;
        $ret['total']['percent'] = $emails_percent * 1;

        $ret['left']['number'] = $ret['limit']['number'] - $ret['total']['number'];
        $ret['left']['percent'] = $ret['limit']['percent'] - $ret['total']['percent'];

        return $ret;
    }


    private function personal_footer($user_data, $centered = false)
    {
        if ($user_data['id'] == 2 || $user_data['id'] == 24) {
            return '';
        }

        $sender_name = !empty($user_data['default_name'])
            ? $user_data['default_name']
            : $user_data['name'] . ' ' . $user_data['lastname'];
        $sender_email = !empty($user_data['default_email'])
            ? $user_data['default_email']
            : $user_data['email'];

        $sender_site = '';

        $footer = '
			<div ' . (($centered) ? 'align="center";' : '') . ' style="padding-bottom: 20px;">
				<p style="margin: 0px;color:#777777;">
					<small>Вы получили это письмо, так как подписали емейл {user_email} на рассылки<br />от ' . $sender_name . ' на {subscribe_link}</small>
				</p>
				<p style="margin: 0px;color:#777777;">
					<small>' . $sender_name . ', ' . $sender_email . $sender_site . '</small>
				</p>
				<p style="margin: 0px;color:#777777;">
					<small>IP-адрес: {email_ip}, Время подписки: {email_date}.</small>
				</p>
				<p style="margin: 0px;color:#777777;">
					<small>Если Вы не желаете больше получать письма,
					<br />то можете отписаться по ссылке: <b>{unsubscribe_link}</b></small>
				</p>
			</div>
		';


        return $footer;
    }

    /*
     * UTILITIES SECTION END
     */
}
