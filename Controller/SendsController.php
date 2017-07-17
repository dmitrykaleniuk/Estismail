<?php

App::uses('AppController', 'Controller');

class SendsController extends AppController
{
    public $uses = array(
        'Send',
        'SendsServer',
        'User',
        'Maket',
        'Letter',
        'Lisst',
        'EmailsLisst',
        'EmailLog',
        'UserSettingField',
        'UserSetting',
        'Server',
        'Event'

    );


    protected $_params = array(
        'index' => array(
            'required' => array(),
            'optional' => array('limit', 'page', 'fields', 'filter', 'sort_field', 'sort_direction')
        ),
        'add' => array(
            'required' => array('maket_id', 'letter_title', 'included_lists'),
            'optional' => array('sender_email_id', 'date', 'excluded_lists')
        ),
        'view' => array(
            'required' => array(),
            'optional' => array('fields')
        ),
        'delete' => array(
            'required' => array(),
            'optional' => array()
        )
    );

    public $paginate = array(
        'Send' => array(
            'limit' => 10,
            'page' => 1,
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
        $this->paginate['Send']['conditions']['Send.user_id'] = $this->Auth->user('id');

        $limit = $this->_setlimit($this->request->data['limit']);
        $this->paginate['Send']['limit'] = $limit;

        $this->request->data['page'] *= 1;
        if ($this->request->data['page'] < 1) {
            $this->request->data['page'] = 1;
        }
        $this->paginate['Send']['page'] = $this->request->data['page'];


        $fields_default = array(
            1 => 'id',
            2 => 'letter_title',
            3 => 'maket_id',
            4 => 'count',
            5 => 'date',
            6 => 'status',
            7 => 'sender_email_id',
            8 => 'included_lists',
            9 => 'excluded_lists'
        );

        $sort_fields_default = array(
            1 => 'date'

        );
        if ($this->request->data['sort_direction'] == -1) {
            $this->request->data['sort_direction'] = 'DESC';
        } else {
            $this->request->data['sort_direction'] = 'ASC';
        }
        $sort_key = array_search($this->request->data['sort_field'], $sort_fields_default);
        $conditions_decode = json_decode($this->request->data['filter'], true);

        if ($conditions_decode['maket_id']) {
            $this->paginate['Send']['conditions']['Send.maket_id'] = $conditions_decode['maket_id'];
        }
        if ($conditions_decode['count']) {
            $this->paginate['Send']['conditions']['Send.count'] = $conditions_decode['count'];
        }
        if ($conditions_decode['date']['start_date']) {
            $this->paginate['Send']['conditions']['Send.data >='] = date('Y-m-d H:i:s.u', $conditions_decode['date']['start_date']);
        }
        if ($conditions_decode['date']['end_date']) {
            $this->paginate['Send']['conditions']['Send.data <='] = date('Y-m-d H:i:s.u', $conditions_decode['date']['end_date']);
        }
        if ($conditions_decode['status'] === 0 || $conditions_decode['status'] === 1 || $conditions_decode['status'] === 2 || $conditions_decode['status'] === 3 || $conditions_decode['status'] === 4) {
            if ($conditions_decode['status'] === 0){
                $this->paginate['Send']['conditions']['Send.status'] = $conditions_decode['status'];
            }
            if ($conditions_decode['status'] === 1){
                $this->paginate['Send']['conditions']['Send.statstatus'] = 0;
                $this->paginate['Send']['conditions']['Send.statcount'] = 2;
                $this->paginate['Send']['conditions']['Send.status'] = $conditions_decode['status'];
            }
            if ($conditions_decode['status'] === 2){
                $this->paginate['Send']['conditions']['Send.statstatus'] = 1;
                $this->paginate['Send']['conditions']['Send.statcount'] = 2;
                $this->paginate['Send']['conditions']['Send.status'] = 1;
            }
            if ($conditions_decode['status'] === 3){
                $this->paginate['Send']['conditions']['Send.statstatus'] = 1;
                $this->paginate['Send']['conditions']['Send.statcount !='] = 2;
                $this->paginate['Send']['conditions']['Send.status'] = 1;
            }
            if ($conditions_decode['status'] === 4){
                $this->paginate['Send']['conditions']['Send.statstatus'] = 0;
                $this->paginate['Send']['conditions']['Send.statcount <='] = 1;
                $this->paginate['Send']['conditions']['Send.status'] = 1;
            }

        }
        if ($sort_key) {
            if ($sort_fields_default[$sort_key] == 'date') {
                $sort_fields_default[$sort_key] = 'data';
            }
            $this->paginate['Send']['order']['Send.' . $sort_fields_default[$sort_key]] = $this->request->data['sort_direction'];
        } else {
            $this->paginate['Send']['order']['Send.id'] = $this->request->data['sort_direction'];

        }

        $fields = json_decode($this->request->data['fields'], true);

        $fields_after_filter = array_intersect($fields_default, $fields);
        if (empty($fields_after_filter)) {
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'letter_title';
        }


        $key = array_search('letter_title', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'letter_id';
        };
        $key = array_search('date', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'data';
        };
        $key = array_search('sender_email_id', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'semail';
        };
        $key = array_search('status', $fields_after_filter);
        if ($key) {
            $fields_after_filter[] = 'statstatus';
            $fields_after_filter[] = 'statcount';
        }
        $key = array_search('included_lists', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'included_lissts';
        }
        $key = array_search('excluded_lists', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'excluded_lissts';
        }
        $this->Paginator->settings = $this->paginate;
        $send = $this->Paginator->paginate();

        if ($send) {

            $letters = $this->Letter->find('all', array(
                'fields' => array('Letter.id', 'Letter.title'),
                'conditions' => array(
                    'Letter.user_id' => $this->Auth->user('id'),
                    'Letter.id' => Set::combine($send, '{n}.Send.letter_id', '{n}.Send.letter_id')

                ),
                'order' => array(
                    'Letter.id' => 'asc'
                ),
            ));

            function filter($array)
            {
                return $array * 1;
            }

            $letter_titles = Set::combine($letters, '{n}.Letter.id', '{n}.Letter.title');
            $send_ids = Set::combine($send, '{n}.Send.letter_id', '{n}.Send');
            foreach ($send_ids as $key => $value) {

                if (array_search('id', $fields_after_filter)) {
                    $sends[$key]['Send']['id'] = $value['id'];
                }
                if (array_search('maket_id', $fields_after_filter)) {
                    $sends[$key]['Send']['maket_id'] = $value['maket_id'];
                }
                if (array_search('count', $fields_after_filter)) {
                    $sends[$key]['Send']['count'] = $value['count'];
                }
                if (array_search('data', $fields_after_filter)) {
                    $sends[$key]['Send']['date'] = strtotime($value['data']);
                }
                if (array_search('included_lissts', $fields_after_filter)) {
                    $sends[$key]['Send']['included_lists'] = array_map('filter', json_decode($value['included_lissts'], true));
                }
                if (array_search('excluded_lissts', $fields_after_filter)) {
                    $sends[$key]['Send']['excluded_lists'] = array_map('filter', json_decode($value['excluded_lissts'], true));
                }
                if (array_search('status', $fields_after_filter)) {
                    if ($value['status']) {
                        $sends[$key]['Send']['status'] = 4;
                    }
                    if ($sends[$key]['Send']['status'] == 4) {
                        if ($value['statstatus']) {
                            (($value['statcount'] == 2) ? $sends[$key]['Send']['status'] = 2 : $sends[$key]['Send']['status'] = 3);
                        } else {
                            if ($value['statcount'] <= 1) {
                                $sends[$key]['Send']['status'] = 4;
                            } else {
                                $sends[$key]['Send']['status'] = 1;
                            }
                        }
                    } else {
                        $sends[$key]['Send']['status'] = 0;
                    }
                }
                if (array_search('semail', $fields_after_filter)) {
                    $sender_email_id = $this->UserSetting->find('first', array(
                        'fields' => array('UserSetting.id'),
                        'conditions' => array(
                            'UserSetting.user_id' => $this->Auth->user('id'),
                            'UserSettingField.id' => 2,
                            'UserSetting.value LIKE ' => '%"email":"' . $value['semail'] . '"%'
                        )
                    ));
                    $sends[$key]['Send']['sender_email_id'] = $sender_email_id['UserSetting']['id'];
                }
                if (array_search('letter_id', $fields_after_filter)) {
                    $sends[$key]['Send']['letter_title'] = $letter_titles[$key];
                }
            }
            $sends = Set::classicExtract($sends, '{n}.Send');
            $return = array(
                'sends' => $sends
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
            1 => 'id',
            2 => 'letter_title',
            3 => 'maket_id',
            4 => 'count',
            5 => 'date',
            6 => 'status',
            7 => 'sender_email_id',
            8 => 'included_lists',
            9 => 'excluded_lists'
        );
        $fields = json_decode($this->request->data['fields'], true);
        $fields_after_filter = array_intersect($fields_default, $fields);

        if (empty($fields_after_filter)) {
            if (!empty($fields)) {
                $this->_bad_request('Invalid fields. Wrong parameters of string fields', 20520);
            }
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'letter_id';
        }
        $key = array_search('letter_title', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'letter_id';
        };
        $key = array_search('date', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'data';
        };
        $key = array_search('sender_email_id', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'semail';
        };
        $key = array_search('status', $fields_after_filter);
        if ($key) {
            $fields_after_filter[] = 'statstatus';
            $fields_after_filter[] = 'statcount';
        }
        $key = array_search('included_lists', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'included_lissts';
        }
        $key = array_search('excluded_lists', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'excluded_lissts';
        }

        $send = $this->Send->find('first', array(
            'fields' => $fields_after_filter,
            'conditions' => array(
                'Send.user_id' => $this->Auth->user('id'),
                'Send.id' => $id
            )
        ));

        if ($send) {
            $send = Set::classicExtract($send, 'Send');

            function filter($array)
            {
                return $array * 1;
            }

            if (array_search('letter_id', $fields_after_filter)) {
                $letter = $this->Letter->find('all', array(
                    'fields' => array('Letter.title'),
                    'conditions' => array(
                        'Letter.user_id' => $this->Auth->user('id'),
                        'Letter.id' => $send['letter_id']

                    ),
                    'recursive' => -1
                ));
                $send['letter_title'] = $letter[0]['Letter']['title'];
                unset($send['letter_id']);
            }
            if (array_search('semail', $fields_after_filter)) {
                $sender_email_id = $this->UserSetting->find('first', array(
                    'fields' => array('UserSetting.id'),
                    'conditions' => array(
                        'UserSetting.user_id' => $this->Auth->user('id'),
                        'UserSettingField.id' => 2,
                        'UserSetting.value LIKE ' => '%"email":"' . $send['semail'] . '"%'
                    )
                ));
                $send['sender_email_id'] = $sender_email_id['UserSetting']['id'];
                unset($send['semail']);
            }
            if (array_search('status', $fields_after_filter)) {
                if ($send['status']) {
                    $send['status'] = 4;
                }
                if ($send['status'] == 4) {
                    if ($send['statstatus']) {
                        (($send['statcount'] == 2) ? $send['status'] = 2 : $send['status'] = 3);
                    } else {
                        if ($send['statcount'] <= 1) {
                            $send['status'] = 4;
                        } else {
                            $send['status'] = 1;
                        }
                    }
                } else {
                    $send['status'] = 0;
                }
                unset($send['statstatus']);
                unset($send['statcount']);
            }
            if (array_search('data', $fields_after_filter)) {
                $send['date'] = strtotime($send['data']);
                unset($send['data']);
            }
            if (array_search('included_lissts', $fields_after_filter)) {
                $send['included_lists'] = array_map('filter', json_decode($send['included_lissts'], true));
                unset($send['included_lissts']);
            }
            if (array_search('excluded_lissts', $fields_after_filter)) {
                $send['excluded_lists'] = array_map('filter', json_decode($send['excluded_lissts'], true));
                unset($send['excluded_lissts']);
            }
            $this->_return($send);
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
        $user_data = $this->Auth->user();
        ini_set('memory_limit', '4096M');
        set_time_limit(0);

        // senders
        if (!empty($this->request->data['sender_email_id'])) {
            $sender_email_id = $this->request->data['sender_email_id'] * 1;
            $sender = $this->UserSetting->find('first', array(
                'fields' => array('UserSetting.value'),
                'conditions' => array(
                    'UserSetting.user_id' => $this->Auth->user('id'),
                    'UserSettingField.id' => 2,
                    'UserSetting.id' => $sender_email_id
                )
            ));
            if ($sender) {
                $sender_data = json_decode($sender['UserSetting']['value'], true);
                if ($sender_data['approved']) {
                    $user_data['default_email'] = $sender_data['email'];
                    $user_data['default_name'] = $sender_data['name'];
                } else {
                    $this->_bad_request('Invalid sender_email_id. Specified sender_email_id did not found', 20540);
                }
            } else {
                $this->_bad_request('Invalid sender_email_id. Specified sender_email_id did not found', 20540);
            }
        } else {
            $sender = $this->UserSetting->find('first', array(
                'fields' => array('UserSetting.value'),
                'conditions' => array(
                    'UserSetting.user_id' => $this->Auth->user('id'),
                    'UserSettingField.id' => 2,
                    'UserSetting.value LIKE ' => '%"default":1%'
                )
            ));
            if ($sender) {
                $sender_data = json_decode($sender['UserSetting']['value'], true);

                $user_data['default_email'] = $sender_data['email'];
                $user_data['default_name'] = $sender_data['name'];

            } else {
                $this->_bad_request('You do not have default email. Please add new sender_email', 20541);
            }

        }

        $maket = array();
        $this->request->data['maket_id'] *= 1;
        $maket = $this->Maket->find('first', array(
            'conditions' => array(
                'Maket.id' => $this->request->data['maket_id'],
                'Maket.user_id' => $this->Auth->user('id')
            )
        ));
        if (empty($maket)) {
            $this->_bad_request('Invalid maket_id. Specified maket_id did not found', 20542);
        }

        //check letter title
        $this->request->data['letter_title'] = trim($this->request->data['letter_title']);
        if (empty($this->request->data['letter_title'])) {
            $this->_bad_request('Invalid letter_title. Letter_title can not be empty', 20543);
        }

        $all_lissts = $this->Lisst->find('list', array(
            'fields' => array('Lisst.id', 'Lisst.sub_link'),
            'conditions' => array(
                'Lisst.user_id' => $this->Auth->user('id'),
                'Lisst.status' => 1
            )
        ));

        $this->request->data['included_lissts'] = json_decode($this->request->data['included_lists'], true);
        unset($this->request->data['included_lists']);
        //check after this one
        if (in_array(0, $this->request->data['included_lissts'])) {
            $this->request->data['included_lissts'] = array_keys($all_lissts);
        }

        $this->request->data['excluded_lissts'] = json_decode($this->request->data['excluded_lists'], true);
        unset($this->request->data['excluded_lists']);
        if (empty($this->request->data['excluded_lissts'])) {
            $this->request->data['excluded_lissts'] = array();
        }

        $this->request->data['included_lissts'] = array_intersect($this->request->data['included_lissts'], array_keys($all_lissts));
        $this->request->data['excluded_lissts'] = array_intersect($this->request->data['excluded_lissts'], array_keys($all_lissts));

//		check included lissts
        if (empty($this->request->data['included_lissts'])) { // empty included lissts
            $this->_bad_request('Empty included lists', 20544);
        }

        $emails = $this->_emails_full_from_lissts($this->request->data['included_lissts'], $this->request->data['excluded_lissts']);

        $emails_count = count($emails);
        if ($emails_count <= 0) {
            $this->_bad_request('No active emails in selected lists', 20545);
        }

        $delay = false; // TRUE - send immediately
        if (!empty($this->request->data['date']) && strtotime($this->request->data['date']) > 0 && time() < strtotime($this->request->data['date'])) {
            $delay = date('Y-m-d H:i:s', strtotime($this->request->data['date']));
        } else {
            $delay = date('Y-m-d H:i:s', strtotime('now'));
        }

        $servers = array();

        $servers = $this->Server->find('all', array(
            'conditions' => array(
                'Server.servers_group_id' => $user_data['servers_group_id'],
                'Server.user_id' => 1
            ),
            'recursive' => -1
        ));
        if (empty($servers)) {
            $servers = $this->Server->find('all', array(
                'conditions' => array(
                    'Server.servers_group_id' => null,
                    'Server.user_id' => 1
                ),
                'recursive' => -1
            ));
        }
        $servers = Set::combine($servers, '{n}.Server.id', '{n}.Server');


        if (empty($servers)) {
            $this->_bad_request('Processing error. Please contact with support service of Estismail support@estismail.com', 20546);
        }


//		save letter
        $maket['Maket']['body'] = $this->_prepare_letter_body($maket['Maket']['body']);
        $letter = array(
            'title' => $this->request->data['letter_title'],
            'body' => $maket['Maket']['body'],
            'footer' => $this->personal_footer($user_data, !$this->request->data['old']),
            'user_id' => $this->Auth->user('id'),
            'name' => $user_data['default_name'],
            'color' => (!empty($this->request->data['color'])) ? $this->request->data['color'] : 'rgb(255,255,255)',
            'login' => strtolower(trim($user_data['login']))
        );


        if ($this->Auth->user('id') == 3071) {
            $letter['footer'] = '';
        }

        if (!$this->Letter->save($letter)) { // error while saving letter
            $this->_bad_request('Some error with saving letter. Please try again', 20547);
        };
        $letter_id = $this->Letter->getLastInsertId();


        // hash unique for this send group
        $this->Send->id = null;
        $sending_stat = array();
        foreach ($servers as $k => $v) {
            $sending_stat[$k] = array();
        }

        $send = array(
            'lisst_id' => 0,
            'maket_id' => $maket['Maket']['id'],
            'user_id' => $this->Auth->user('id'),
            'count' => $emails_count,
            'status' => 0,
            'data' => $delay,
            'statdate' => date('Y-m-d G:i:s', strtotime($delay) + 60 * 500),
            'servers' => implode(',', array_keys($servers)),
            'letter_id' => $letter_id,
            'semail' => $user_data['default_email'],
            'personal' => 1,
            'send_hash' => sha1($maket['Maket']['id'] . time()),
            'sending_stat' => json_encode($sending_stat),
            'common' => 1,
            'included_lissts' => json_encode($this->request->data['included_lissts']),
            'excluded_lissts' => json_encode($this->request->data['excluded_lissts'])
        );

        if (!$this->Send->save($send)) { // can't save send
            $this->_bad_request('Can not save send. Please try again', 20548);
        }
        $send_id = $this->Send->getLastInsertId();

        $this->_created($send_id);

    }

    /**
     * editing element by it's ID
     * response status codes:
     *
     *      405 - not allowed
     * @param $id - add of requested element
     */
    public function edit()
    {
        $this->_not_allowed();
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
        $send = $this->Send->find('first', array(
            'conditions' => array(
                'Send.id' => $id,
                'Send.user_id' => $this->Auth->user('id'),
            ),
            'recursive' => -1
        ));

        if ($send) {
            if ($send['Send']['status'] == 1) {
                $this->_bad_request('Send in progress now or already sent and it can not be removed', 20580);
            }

            $this->Send->deleteAll(array(
                    'Send.id' => $send['Send']['id'],
                    'Send.user_id' => $this->Auth->user('id')
                )
            );
            $this->_empty_response();
        } else {
            $this->_not_found();
        }


    }

    /*
     * UTILITIES SECTION
     */

    private function _emails_full_from_lissts($include, $exclude, $user_id = null)
    {

        $user_id = $this->Auth->user('id');

        $plus = array_diff($include, $exclude);
        $minus = array_diff($exclude, $include);

        $data = array();

        if (!empty($plus) && !empty($user_id)) {

            $query = '
					SELECT e.id, e.email, el.lisst_id, e.name, e.ip, e.date, e.sub_link FROM emails_lissts_' . $user_id . ' AS el
						LEFT JOIN emails_' . $user_id . ' AS e
							ON e.id = el.email_id
						WHERE el.lisst_id IN (' . implode(',', $plus) . ')
							AND el.user_id = ' . $user_id . '
							AND e.user_id = ' . $user_id . '
							AND e.blocked = 0
							AND el.status = 0
						';

            if (!empty($minus)) {
                $query .= '
						AND email_id NOT IN (
							SELECT email_id FROM emails_lissts_' . $user_id . '
								WHERE lisst_id IN (' . implode(',', $minus) . ')
								AND user_id = ' . $user_id . '
						)
				';
            }


            try {
                $data = $this->EmailsLisst->query($query);
                $data = Set::combine($data, '{n}.0.id', '{n}.0');
            } catch (Exception $ex) {
                $this->_user_log(1, __LINE__);
            }

        }

        return $data;

    }

    private function _prepare_letter_body($body)
    {

        $body = $this->_clear_from_tags(array('title', 'body', 'head', 'html', '\!doctype'), $body);

        $body = str_replace("\n", '', $body);

        $body = preg_replace('%<div[^>]*editor-toolbar.*?</div></td>%i', '</div></td>', $body);

        $body = preg_replace('%contenteditable="true"%im', '', $body);

        $body = preg_replace_callback('%\{estis_event=(\d+)\}%i', function ($matches) {
            $link = '';
            if (!empty($matches[1])) {
                $event = $this->Event->find('first', array(
                    'conditions' => array(
                        'Event.id' => $matches[1] * 1
                    )
                ));
                if ($event) {
                    $link = '{estis_event=' . ($matches[1] * 1) . '&login=' . strtolower(trim($event['User']['login'])) . '}';
                }
            }
            return $link;
        }, $body);

        $body = preg_replace('%(?:\'\'){2,}%', '\'\'', $body);

        $body = preg_replace('%&amp;%', '&', $body);

        return $body;

    }


    public function personal_footer($user_data, $centered = false)
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
        $sender_site = !empty($user_data['default_site'])
            ? ', ' . $user_data['default_site'] . '.'
            : '.';

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
