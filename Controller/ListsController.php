<?php

App::uses('AppController', 'Controller');

class ListsController extends AppController
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
        'UserSetting'
    );

    protected $_params = array(
        'index' => array(
            'required' => array(),
            'optional' => array('limit', 'page', 'fields', 'filter', 'sort_field', 'sort_direction')
        ),
        'add' => array(
            'required' => array('title', 'subscribe_link'),
            'optional' => array('about', 'subscribe_page', 'activate_page', 'subscribe_maket', 'subscribe_maket_title', 'activate_maket', 'activate_maket_title', 'use_personal_makets', 'sender_email_id')
        ),
        'view' => array(
            'required' => array(),
            'optional' => array('fields')
        ),
        'edit' => array(
            'required' => array(),
            'optional' => array('title', 'about', 'status', 'subscribe_page', 'activate_page', 'subscribe_link', 'subscribe_maket', 'subscribe_maket_title', 'activate_maket', 'activate_maket_title', 'use_personal_makets', 'sender_email_id')
        ),
        'delete' => array(
            'required' => array(),
            'optional' => array()
        )
    );

    public $paginate = array(
        'Lisst' => array(
            'limit' => 10,
            'page' => 1
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
        $this->paginate['Lisst']['conditions']['Lisst.user_id'] = $this->Auth->user('id');

        $limit = $this->_setlimit($this->request->data['limit']);
        $this->paginate['Lisst']['limit'] = $limit;

        $this->request->data['page'] *= 1;
        if ($this->request->data['page'] < 1) {
            $this->request->data['page'] = 1;
        }
        $this->paginate['Lisst']['page'] = $this->request->data['page'];


        $fields_default = array(
            'id',
            'title',
            'about',
            'status',
            'subscribe_page',
            'activate_page',
            'repeat_date',
            'subscribe_link',
            'subscribe_maket',
            'subscribe_maket_title',
            'activate_maket',
            'activate_maket_title',
            'use_personal_makets',
            'sender_email_id'
        );


        $sort_fields_default = array(
            1 => 'title',
            2 => 'status',
            3 => 'sender_email_id'

        );
        if ($this->request->data['sort_direction'] == -1) {
            $this->request->data['sort_direction'] = 'DESC';
        } else {
            $this->request->data['sort_direction'] = 'ASC';
        }
        $sort_key = array_search($this->request->data['sort_field'], $sort_fields_default);
        $conditions_decode = json_decode($this->request->data['filter'], true);

        if ($conditions_decode['title']) {
            $this->paginate['Lisst']['conditions']['Lisst.title'] = $conditions_decode['title'];
        }
        if ($conditions_decode['status']) {
            $this->paginate['Lisst']['conditions']['Lisst.status'] = $conditions_decode['status'];
        }
        if ($sort_key) {
            if ($sort_fields_default[$sort_key] == 'sender_email_id') {
                $sort_fields_default[$sort_key] = 'user_setting_id';
            }
            $this->paginate['Lisst']['order']['Lisst.' . $sort_fields_default[$sort_key]] = $this->request->data['sort_direction'];
        } else {
            $this->paginate['Lisst']['order']['Lisst.id'] =  $this->request->data['sort_direction'];

        }

        $fields = json_decode($this->request->data['fields'], true);

        $fields_after_filter = array_intersect($fields_default, $fields);
        if (empty($fields_after_filter)) {
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'title';
        }
        $key = array_search('subscribe_page', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'sub_page';
        };
        $key = array_search('activate_page', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'act_page';
        };
        $key = array_search('subscribe_link', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'sub_link';
        };
        $key = array_search('subscribe_maket', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'sub_maket';
        };
        $key = array_search('subscribe_maket_title', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'sub_maket_title';
        };
        $key = array_search('activate_maket', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'act_maket';
        };
        $key = array_search('activate_maket_title', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'act_maket_title';
        };
        $key = array_search('sender_email_id', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'user_setting_id';
        };

        $this->paginate['Lisst']['fields'] = $fields_after_filter;
        $this->Paginator->settings = $this->paginate;
        $lissts = $this->Paginator->paginate();
        if ($lissts) {
            $lissts = Set::classicExtract($lissts, '{n}.Lisst');
            foreach ($lissts as $key => $value) {
                $keyexist_email = array_key_exists('email', $value);
                if ($keyexist_email) {
                    $emails[$key]['email'] = trim($value['email']);
                }
                $keyexist_repeat_date = array_key_exists('repeat_date', $value);
                if ($keyexist_repeat_date) {
                    $lissts[$key]['repeat_date'] = strtotime($value['repeat_date']);
                }
                $keyexist_sub_link = array_key_exists('sub_link', $value);
                if ($keyexist_sub_link) {
                    $lissts[$key]['subscribe_link'] = $value['sub_link'];
                    unset($lissts[$key]['sub_link']);
                }
                $keyexist_sub_page = array_key_exists('sub_page', $value);
                if ($keyexist_sub_page) {
                    $lissts[$key]['subscribe_page'] = $value['sub_page'];
                    if (empty($lissts[$key]['subscribe_page'])) {
                        $lissts[$key]['subscribe_page'] =  'https://mailer.estismail.com/subscribeme';
                    }
                    unset($lissts[$key]['sub_page']);
                }
                $keyexist_act_page = array_key_exists('act_page', $value);
                if ($keyexist_act_page) {
                    $lissts[$key]['activate_page'] = $value['act_page'];
                    if (empty($lissts[$key]['activate_page'])) {
                        $lissts[$key]['activate_page'] =  'https://mailer.estismail.com/subscriberactivate';
                    }
                    unset($lissts[$key]['act_page']);
                }
                $keyexist_sub_maket = array_key_exists('sub_maket', $value);
                if ($keyexist_sub_maket) {
                    $lissts[$key]['subscribe_maket'] = $value['sub_maket'];
                    unset($lissts[$key]['sub_maket']);
                }
                $keyexist_sub_maket_title = array_key_exists('sub_maket_title', $value);
                if ($keyexist_sub_maket_title) {
                    $lissts[$key]['subscribe_maket_title'] = $value['sub_maket_title'];
                    unset($lissts[$key]['sub_maket_title']);
                }
                $keyexist_act_maket = array_key_exists('act_maket', $value);
                if ($keyexist_act_maket) {
                    $lissts[$key]['activate_maket'] = $value['act_maket'];
                    unset($lissts[$key]['act_maket']);
                }
                $keyexist_act_maket_title = array_key_exists('act_maket_title', $value);
                if ($keyexist_act_maket_title) {
                    $lissts[$key]['activate_maket_title'] = $value['act_maket_title'];
                    unset($lissts[$key]['act_maket_title']);
                }
                $keyexist_user_setting_id = array_key_exists('user_setting_id', $value);
                if ($keyexist_user_setting_id) {
                    $lissts[$key]['sender_email_id'] = $value['user_setting_id'];
                    unset($lissts[$key]['user_setting_id']);
                }
            }

            $return = array(
                'lists' => $lissts
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
            'title',
            'about',
            'status',
            'subscribe_page',
            'activate_page',
            'repeat_date',
            'subscribe_link',
            'subscribe_maket',
            'subscribe_maket_title',
            'activate_maket',
            'activate_maket_title',
            'use_personal_makets',
            'sender_email_id'
        );
        $fields = json_decode($this->request->data['fields'], true);
        $fields_after_filter = array_intersect($fields_default, $fields);

        if (empty($fields_after_filter)) {
            if (!empty($fields)) {
                $this->_bad_request('Invalid fields. Wrong parameters of string fields', 20320);
            }
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'title';
        }

        $key = array_search('subscribe_page', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'sub_page';
        };
        $key = array_search('activate_page', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'act_page';
        };
        $key = array_search('subscribe_link', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'sub_link';
        };
        $key = array_search('subscribe_maket', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'sub_maket';
        };
        $key = array_search('subscribe_maket_title', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'sub_maket_title';
        };
        $key = array_search('activate_maket', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'act_maket';
        };
        $key = array_search('activate_maket_title', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'act_maket_title';
        };
        $key = array_search('sender_email_id', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'user_setting_id';
        };

        $lisst = $this->Lisst->find('first', array(
            'fields' => $fields_after_filter,
            'conditions' => array(
                'Lisst.user_id' => $this->Auth->user('id'),
                'Lisst.id' => $id
            ),
            'recursive' => -1
        ));
        if ($lisst) {
            $lisst = Set::classicExtract($lisst, 'Lisst');
            if ($lisst['repeat_date']) {
                $lisst['repeat_date'] = strtotime($lisst['repeat_date']);
            }
            if (array_search('sub_link', $fields_after_filter)) {
                $lisst['subscribe_link'] = $lisst['sub_link'];
                unset($lisst['sub_link']);
            }
            if (array_search('sub_page', $fields_after_filter)) {
                $lisst['subscribe_page'] = $lisst['sub_page'];
                if (empty($lisst['subscribe_page'])) {
                    $lisst['subscribe_page'] =  'https://mailer.estismail.com/subscribeme';
                }
                unset($lisst['sub_page']);
            }
            if (array_search('act_page', $fields_after_filter)) {
                $lisst['activate_page'] = $lisst['act_page'];
                if (empty($lisst['activate_page'])) {
                    $lisst['activate_page'] =  'https://mailer.estismail.com/subscriberactivate';
                }
                unset($lisst['act_page']);
            }
            if (array_search('sub_maket', $fields_after_filter)) {
                $lisst['subscribe_maket'] = $lisst['sub_maket'];
                unset($lisst['sub_maket']);
            }
            if (array_search('sub_maket_title', $fields_after_filter)) {
                $lisst['subscribe_maket_title'] = $lisst['sub_maket_title'];
                unset($lisst['sub_maket_title']);
            }
            if (array_search('act_maket', $fields_after_filter)) {
                $lisst['activate_maket'] = $lisst['act_maket'];
                unset($lisst['act_maket']);
            }
            if (array_search('act_maket_title', $fields_after_filter)) {
                $lisst['activate_maket_title'] = $lisst['act_maket_title'];
                unset($lisst['act_maket_title']);
            }
            if (array_search('user_setting_id', $fields_after_filter)) {
                $lisst['sender_email_id'] = $lisst['user_setting_id'];
                unset($lisst['user_setting_id']);
            }

            $this->_return($lisst);
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
        $this->request->data['user_id'] = $this->Auth->user('id');

        $this->request->data['title'] = trim($this->request->data['title']);
        if (empty($this->request->data['title'])) {
            $this->_bad_request('Invalid title. Title can not be empty', 20340);
        }
        $this->request->data['sub_link'] = $this->request->data['subscribe_link'];
        unset($this->request->data['subscribe_link']);
        $check_url = preg_match('/\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $this->request->data['sub_link']);
        if ($check_url == false) {
            $this->_bad_request('Invalid subscribe_link. URL must be existing', 20341);
        }

        $this->request->data['sender_email_id'] *= 1;
        $this->request->data['user_setting_id'] = $this->request->data['sender_email_id'];
        unset($this->request->data['sender_email_id']);
        if (!empty($this->request->data['user_setting_id'])) {
            $user_setting = $this->UserSetting->find('first', array(
                'conditions' => array(
                    'UserSetting.user_id' => $this->Auth->user('id'),
                    'UserSetting.user_setting_field_id' => 2,
                    'UserSetting.id' => $this->request->data['user_setting_id']
                )
            ));
            if (empty($user_setting)) {
                unset($this->request->data['user_setting_id']);
            }

        } else {
            unset($this->request->data['user_setting_id']);
        }


        if (is_numeric($this->request->data['use_personal_makets'])) {
            if ($this->request->data['use_personal_makets'] != 1 && $this->request->data['use_personal_makets'] != 0) {
                unset($this->request->data['use_personal_makets']);
            }
        } else {
            unset($this->request->data['use_personal_makets']);
        }
        $this->request->data['sub_page'] = $this->request->data['subscribe_page'];
        unset($this->request->data['subscribe_page']);
        $check_url = preg_match('/\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $this->request->data['sub_page']);
        if ($check_url == false) {
            unset($this->request->data['sub_page']);
        }
        $this->request->data['act_page'] = $this->request->data['activate_page'];
        unset($this->request->data['activate_page']);
        $check_url = preg_match('/\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $this->request->data['act_page']);
        if ($check_url == false) {
            unset($this->request->data['act_page']);
        }

        if (isset($this->request->data['subscribe_maket'])) {
            $this->request->data['sub_maket'] = $this->_clear_from_tags(array('title', 'body', 'head', 'html', '\!doctype'), $this->request->data['subscribe_maket']);
            unset($this->request->data['subscribe_maket']);
        }
        if (isset($this->request->data['subscribe_maket_title'])) {
            $this->request->data['sub_maket_title'] = $this->request->data['subscribe_maket_title'];
            unset($this->request->data['subscribe_maket_title']);
        }
        if ($this->request->data['activate_maket']) {
            $this->request->data['act_maket'] = $this->_clear_from_tags(array('title', 'body', 'head', 'html', '\!doctype'), $this->request->data['activate_maket']);
            unset($this->request->data['activate_maket']);
        }
        if (isset($this->request->data['activate_maket_title'])) {
            $this->request->data['act_maket_title'] = $this->request->data['activate_maket_title'];
            unset($this->request->data['activate_maket_title']);
        }
        if ($this->request->data['use_personal_makets'] == 1) {
            if (empty($this->request->data['act_maket_title']) || empty($this->request->data['act_maket']) || empty($this->request->data['sub_maket_title']) || empty($this->request->data['sub_maket'])) {
                $this->_bad_request('You can use personal makets, only when activate_maket_title, activate_maket, subscribe_maket_title and subscribe_maket are not empty', 20342);
            }
        }
        $this->Lisst->save($this->request->data);
        $lisst_id = $this->Lisst->getLastInsertId();
        $this->_created($lisst_id);

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

        $lisst = $this->Lisst->find('first',
            array(
                'conditions' => array(
                    'Lisst.user_id' => $this->Auth->user('id'),
                    'Lisst.id' => $id
                ),
                'recursive' => -1
            )
        );

        if ($lisst) {
            if (isset($this->request->data['title'])) {
                $this->request->data['title'] = trim($this->request->data['title']);
                if (empty($this->request->data['title'])) {
                    $this->_bad_request('Invalid title. Title can not be empty', 20360);
                }
            }
            if (isset($this->request->data['subscribe_link'])) {
                $this->request->data['sub_link'] = $this->request->data['subscribe_link'];
                unset($this->request->data['subscribe_link']);
                $check_url = preg_match('/\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $this->request->data['sub_link']);
                if ($check_url == false) {
                    $this->_bad_request('Invalid subscribe_link. URL must be existing', 20361);
                }
            }
            if (($this->request->data['sender_email_id']) != "") {
                $this->request->data['sender_email_id'] *= 1;
                $this->request->data['user_setting_id'] = $this->request->data['sender_email_id'];
                unset($this->request->data['sender_email_id']);
                if (!empty($this->request->data['user_setting_id'])) {
                    $user_setting = $this->UserSetting->find('first', array(
                        'conditions' => array(
                            'UserSetting.user_id' => $this->Auth->user('id'),
                            'UserSetting.user_setting_field_id' => 2,
                            'UserSetting.id' => $this->request->data['user_setting_id']
                        )
                    ));
                    if (empty($user_setting)) {
                        unset($this->request->data['user_setting_id']);
                    }

                } else {
                    unset($this->request->data['user_setting_id']);
                }
            }
            if (is_numeric($this->request->data['use_personal_makets'])) {
                if ($this->request->data['use_personal_makets'] != 1 && $this->request->data['use_personal_makets'] != 0) {
                    unset($this->request->data['use_personal_makets']);
                }
            } else {
                unset($this->request->data['use_personal_makets']);
            }

            if ($this->request->data['subscribe_page'] !== "") {
                $this->request->data['sub_page'] = $this->request->data['subscribe_page'];
                unset($this->request->data['subscribe_page']);
                $check_url = preg_match('/\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $this->request->data['sub_page']);
                if ($check_url == false) {
                    unset($this->request->data['sub_page']);
                }
            }
            if ($this->request->data['activate_page'] !== "") {
                $this->request->data['act_page'] = $this->request->data['activate_page'];
                unset($this->request->data['activate_page']);
                $check_url = preg_match('/\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $this->request->data['act_page']);
                if ($check_url == false) {
                    unset($this->request->data['act_page']);
                }
            }
            $this->request->data['status'] *= 1;
            if ($this->request->data['status'] == 1) {
                $result = $this->emaillisstactivate($id);
                if ($result == false) {
                    unset($this->request->data['status']);
                }
            } else {
                unset($this->request->data['status']);
            }


            if (isset($this->request->data['subscribe_maket'])) {
                $this->request->data['sub_maket'] = $this->_clear_from_tags(array('title', 'body', 'head', 'html', '\!doctype'), $this->request->data['subscribe_maket']);
                unset($this->request->data['subscribe_maket']);
            }
            if (isset($this->request->data['subscribe_maket_title'])) {
                $this->request->data['sub_maket_title'] = $this->request->data['subscribe_maket_title'];
                unset($this->request->data['subscribe_maket_title']);
            }
            if (isset($this->request->data['activate_maket'])) {
                $this->request->data['act_maket'] = $this->_clear_from_tags(array('title', 'body', 'head', 'html', '\!doctype'), $this->request->data['activate_maket']);
                unset($this->request->data['activate_maket']);
            }
            if (isset($this->request->data['activate_maket_title'])) {
                $this->request->data['act_maket_title'] = $this->request->data['activate_maket_title'];
                unset($this->request->data['activate_maket_title']);
            }
            if ($this->request->data['use_personal_makets'] == 1) {
                if (((empty($this->request->data['act_maket_title']) && empty($lisst['Lisst']['act_maket_title'])) == true) || ((empty($this->request->data['act_maket']) && empty($lisst['Lisst']['act_maket'])) == true) || ((empty($this->request->data['sub_maket_title']) && empty($lisst['Lisst']['sub_maket_title'])) == true) || ((empty($this->request->data['sub_maket']) && empty($lisst['Lisst']['sub_maket'])) == true)) {
                    $this->_bad_request('You can use personal makets, only when activate_maket_title, activate_maket, subscribe_maket_title and subscribe_maket are not empty', 20362);
                }
            }

            $this->Lisst->id = $id;
            $this->Lisst->save($this->request->data);

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

	    $this->_not_allowed();
	    return;

        $id *= 1;
        $this->_filter_inputs();
        $lisst = $this->Lisst->find('first',
            array(
                'conditions' => array(
                    'Lisst.user_id' => $this->Auth->user('id'),
                    'Lisst.id' => $id
                ),
                'recursive' => -1
            )
        );

        if ($lisst) {

            // delete emails from this lisst
            $this->emaildelete(null, $id);

            // delete ProCampaignInput
            $this->ProCampaignInput->deleteAll(array(
                'ProCampaignInput.pro_campaign_input_type_id' => 1,
                'ProCampaignInput.input_id' => $id
            ));

            // delete lisst
            $this->Lisst->delete($id);

            $this->_empty_response();

        } else {
            $this->_not_found();
        }

    }

    /*
     * UTILITIES SECTION
     */

    private function emaillisstactivate($id)
    {
        $id *= 1;
        $lisst = $this->Lisst->find('first', array(
            'conditions' => array(
                'Lisst.user_id' => $this->Auth->user('id'),
                'Lisst.id' => $id,
                'NOT' => array(
                    'Lisst.status' => 1
                )
            )
        ));

        if ($lisst) {
            $this->Lisst->id = $id;
            if (!empty($lisst['Lisst']['sub_link'])) { // can't activate without sub_link
                if ($lisst['Lisst']['status'] == 0) { // simple block, activate if enough subscribers limit

                    $subscribers_limit = $this->_gettariffvalue('emails_limit');

                    $active_lissts = $this->Lisst->find('list', array(
                        'fields' => array('Lisst.id', 'Lisst.id'),
                        'conditions' => array(
                            'Lisst.user_id' => $this->Auth->user('id'),
                            'Lisst.status' => 1
                        )
                    ));
                    $active_lissts[$id] = $id;

                    $distinct_emails = 0;
                    $query = 'SELECT COUNT(DISTINCT(email_id)) FROM emails_lissts_' . $this->Auth->user('id') . '
									WHERE user_id = ' . $this->Auth->user('id') . '
									AND lisst_id IN (' . implode(',', $active_lissts) . ')';
                    try {
                        $distinct_emails = $this->EmailsLisst->query($query);
                        $distinct_emails = $distinct_emails[0][0]['count'];
                    } catch (Exception $ex) {
                        $this->_user_log(1, __LINE__ . ': ' . $ex->getMessage());
                        $distinct_emails = 0;
                    }

                    if ($distinct_emails <= $subscribers_limit) {
                        return true;
                        /*if ($this->Lisst->saveField('status', 1)) {
                            $this->pushNotify('Группа активирована.');
                        }*/
                    } else {
                        return false;
                    }

                } else { // block by admin of under moderation
                    return false;
                }
            } else {
                return false;
            }
        }
        return false;

    }


    private function _clear_from_tags($tags, $text)
    {
        $pattern = '%</?(?:' . implode('|', $tags) . ')[^>]*>%is';
        $text = preg_replace($pattern, '', $text);
        if (preg_match($pattern, $text)) {
            return $this->_clear_from_tags($tags, $text);
        } else {
            return $text;
        }
    }
    /*
     * UTILITIES SECTION END
     */
}
