<?php

App::uses('AppController', 'Controller');

class MaketsController extends AppController
{
    public $uses = array(
        'Maket',
        'EmailLog',
        'ProCampaignSchema'

    );

    protected $_params = array(
        'index' => array(
            'required' => array(),
            'optional' => array('limit', 'page', 'fields', 'filter', 'sort_field', 'sort_direction')
        ),
        'add' => array(
            'required' => array('title'),
            'optional' => array('body')
        ),
        'view' => array(
            'required' => array(),
            'optional' => array('fields')
        ),
        'edit' => array(
            'required' => array(),
            'optional' => array('title', 'body')
        ),
        'delete' => array(
            'required' => array(),
            'optional' => array()
        )
    );

    public $paginate = array(
        'Maket' => array(
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
        $this->paginate['Maket']['conditions']['Maket.user_id'] = $this->Auth->user('id');


        $limit = $this->_setlimit($this->request->data['limit']);
        $this->paginate['Maket']['limit'] = $limit;

        $this->request->data['page'] *= 1;
        if ($this->request->data['page'] < 1) {
            $this->request->data['page'] = 1;
        }
        $this->paginate['Maket']['page'] = $this->request->data['page'];

        $fields_default = array(
            'id',
            'title',
            'body'
        );

        $sort_fields_default = array(
            1 => 'title'

        );
        if ($this->request->data['sort_direction'] == -1) {
            $this->request->data['sort_direction'] = 'DESC';
        } else {
            $this->request->data['sort_direction'] = 'ASC';
        }
        $sort_key = array_search($this->request->data['sort_field'], $sort_fields_default);
        $conditions_decode = json_decode($this->request->data['filter'], true);

        if ($conditions_decode['title']) {
            $this->paginate['Maket']['conditions']['Maket.title'] = $conditions_decode['title'];
        }
        if ($sort_key) {
            $this->paginate['Maket']['order']['Maket.' . $sort_fields_default[$sort_key]] = $this->request->data['sort_direction'];
        } else {
            $this->paginate['Maket']['order']['Maket.id'] =  $this->request->data['sort_direction'];

        }

        $fields = json_decode($this->request->data['fields'], true);

        $fields_after_filter = array_intersect($fields_default, $fields);
        if (empty($fields_after_filter)) {
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'title';
        }
        $this->paginate['Maket']['fields'] = $fields_after_filter;
        $this->Paginator->settings = $this->paginate;
        $makets = $this->Paginator->paginate();
        if ($makets) {
            $makets = Set::classicExtract($makets, '{n}.Maket');
            $return = array(
                'makets' => $makets
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
            'body'
        );
        $fields = json_decode($this->request->data['fields'], true);
        $fields_after_filter = array_intersect($fields_default, $fields);

        if (empty($fields_after_filter)) {
            if (!empty($fields)) {
                $this->_bad_request('Invalid fields. Wrong parameters of string fields', 20220);
            }
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'title';
        }

        $maket = $this->Maket->find('first', array(
            'fields' => $fields_after_filter,
            'conditions' => array(
                'Maket.user_id' => $this->Auth->user('id'),
                'Maket.id' => $id
            ),
            'recursive' => -1
        ));
        if ($maket) {
            $maket = Set::classicExtract($maket, 'Maket');
            $this->_return($maket);
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
        //Configure::write('debug', 2);
        $this->request->data['title'] = trim($this->request->data['title']);
        if (empty($this->request->data['title'])) {
            $this->_bad_request('Invalid title. Title can not be empty', 20240);
        }

        if (!empty($this->request->data['body'])) {
            $body = $this->_clearhtml($this->request->data['body']);
            $this->request->data['body'] = $body;
        } else {
            unset($this->request->data['body']);
        }

        $this->request->data['bad_redirect'] = $this->_check_redirects($this->request->data['body']);
        $this->Maket->save($this->request->data);
        $maket_id = $this->Maket->getLastInsertId();
        $this->_created($maket_id);

    }

    /**
     * editing element by it's ID
     * response status codes:
     *      204 - success but empty response
     *      404 - element not found
     * @param $id - add of requested element
     */
    public function edit($id)
    {
        $id *= 1;
        $this->_filter_inputs();
        $maket = $this->Maket->find('first',
            array(
                'conditions' => array(
                    'Maket.user_id' => $this->Auth->user('id'),
                    'Maket.id' => $id
                ),
                'recursive' => -1
            )
        );
        if ($maket) {
            $this->request->data['title'] = trim($this->request->data['title']);
            if (empty($this->request->data['title'])) {
                $this->_bad_request('Invalid title. Title can not be empty', 20260);
            }

            if (!empty($this->request->data['body'])) {
                $body = $this->_clearhtml($this->request->data['body']);
                $this->request->data['body'] = $body;
            }

            if ($maket['Maket']['color'] != 'rgb(255, 255, 255)') {
                $this->request->data['color'] = 'rgb(255, 255, 255)';
            }

            if ($maket['Maket']['old'] == 0) {
                $this->request->data['old'] = 1;
            }
            $this->request->data['bad_redirect'] = $this->_check_redirects($this->request->data['body']);

            $this->Maket->id = $id;
            $this->Maket->save($this->request->data);

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
        $maket = $this->Maket->find('first',
            array(
                'conditions' => array(
                    'Maket.user_id' => $this->Auth->user('id'),
                    'Maket.id' => $id
                ),
                'recursive' => -1
            )
        );

        if ($maket) {
            $this->ProCampaignSchema->updateAll(array(
                'maket_id' => null
            ), array(
                'ProCampaignSchema.user_id' => $this->Auth->user('id'),
                'ProCampaignSchema.maket_id' => $id
            ));
            $this->Maket->deleteAll(array('Maket.id' => $id, 'Maket.user_id' => $this->Auth->user('id')));
            $this->_empty_response();

        } else {
            $this->_not_found();
        }

    }



}
