<?php

App::uses('AppController', 'Controller');

class FormsController extends AppController
{
    public $uses = array(
        'ProForm',
        'ProFormGallery',
        'ProFormBase'
    );

    protected $_params = array(
        'index' => array(
            'required' => array(),
            'optional' => array('limit', 'page', 'fields', 'filter', 'sort_field', 'sort_direction')
        ),
        'add' => array(
            'required' => array(),
            'optional' => array()
        ),
        'view' => array(
            'required' => array(),
            'optional' => array('fields')
        ),
        'edit' => array(
            'required' => array(),
            'optional' => array()
        ),
        'delete' => array(
            'required' => array(),
            'optional' => array()
        )
    );

    public $paginate = array(
        'ProForm' => array(
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
        $this->paginate['ProForm']['conditions']['ProForm.user_id'] = $this->Auth->user('id');


        $limit = $this->_setlimit($this->request->data['limit']);
        $this->paginate['ProForm']['limit'] = $limit;

        $this->request->data['page'] *= 1;
        if ($this->request->data['page'] < 1) {
            $this->request->data['page'] = 1;
        }
        $this->paginate['ProForm']['page'] = $this->request->data['page'];

        $fields_default = array(
            'id',
            'title',
            'list_id',
            'body'
        );

        $sort_fields_default = array(
            1 => 'title',
            2 => 'id'

        );
        if ($this->request->data['sort_direction'] == -1) {
            $this->request->data['sort_direction'] = 'DESC';
        } else {
            $this->request->data['sort_direction'] = 'ASC';
        }
        $sort_key = array_search($this->request->data['sort_field'], $sort_fields_default);
        $conditions_decode = json_decode($this->request->data['filter'], true);

        if ($conditions_decode['title']) {
            $this->paginate['ProForm']['conditions']['ProForm.title'] = $conditions_decode['title'];
        }
        if ($sort_key) {
            $this->paginate['ProForm']['order']['ProForm.' . $sort_fields_default[$sort_key]] = $this->request->data['sort_direction'];
        } else {
            $this->paginate['ProForm']['order']['ProForm.id'] =  $this->request->data['sort_direction'];

        }

        $fields = json_decode($this->request->data['fields'], true);

        $fields_after_filter = array_intersect($fields_default, $fields);
        if (empty($fields_after_filter)) {
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'title';
        }

        $key = array_search('list_id', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'lisst_id';
        };

        $key = array_search('body', $fields_after_filter);
        if ($key) {
            $fields_after_filter[] = 'ProFormGallery.css';
            $fields_after_filter[] = 'ProFormGallery.pro_form_base_id';
        };


        $this->paginate['ProForm']['fields'] = $fields_after_filter;
        $this->Paginator->settings = $this->paginate;
        $forms = $this->Paginator->paginate();
        if ($forms) {
            foreach ($forms as $k => $v) {
                $keyexist_body_id = array_key_exists('body', $v['ProForm']);
                if ($keyexist_body_id) {
                    $forms[$k]['ProForm']['body'] = '<style type="text/css">'.$v['ProFormGallery']['ProFormBase']['css'].' '.$v['ProFormGallery']['css'].'</style>'.$v['ProForm']['body'];
                }

            }

            $forms = Set::classicExtract($forms, '{n}.ProForm');
            foreach ($forms as $key => $value) {
                $keyexist_lisst_id = array_key_exists('lisst_id', $value);
                if ($keyexist_lisst_id) {
                    $forms[$key]['list_id'] = trim($value['lisst_id']);
                    unset($forms[$key]['lisst_id']);
                }
            }
            $return = array(
                'forms' => $forms
            );
            $this->_return($return);
        } else {
            $this->_empty_response();
        }

    }

    /**
     * viewing element by it's ID
     * response status codes:
     *      405 - not allowed
     * @param $id - add of requested element
     */
    public function view($id)
    {
        $id *= 1;;
        $this->_filter_inputs();
        $fields_default = array(
            'id',
            'title',
            'body',
            'list_id'
        );
        $fields = json_decode($this->request->data['fields'], true);
        $fields_after_filter = array_intersect($fields_default, $fields);

        if (empty($fields_after_filter)) {
            if (!empty($fields)) {
                $this->_bad_request('Invalid fields. Wrong parameters of string fields', 20720);
            }
            $fields_after_filter[] = 'id';
            $fields_after_filter[] = 'title';
        }
        $key = array_search('list_id', $fields_after_filter);
        if ($key) {
            $fields_after_filter[$key] = 'lisst_id';
        };

        $key = array_search('body', $fields_after_filter);
        if ($key) {
            $fields_after_filter[] = 'ProFormGallery.css';
            $fields_after_filter[] = 'ProFormGallery.pro_form_base_id';
        };

        $form = $this->ProForm->find('first', array(
            'fields' => $fields_after_filter,
            'conditions' => array(
                'ProForm.user_id' => $this->Auth->user('id'),
                'ProForm.id' => $id
            ),
            'recursive'  => 2
        ));

        if ($form) {

            $keyexist_lisst_id = array_key_exists('body', $form['ProForm']);
            if ($keyexist_lisst_id) {
                $form['ProForm']['body'] = '<style type="text/css">'.$form['ProFormGallery']['ProFormBase']['css'].' '.$form['ProFormGallery']['css'].'</style>'.$form['ProForm']['body'];
            }

            $form = Set::classicExtract($form, 'ProForm');

            $keyexist_lisst_id = array_key_exists('lisst_id', $form);
            if ($keyexist_lisst_id) {
                $form['list_id'] = trim($form['lisst_id']);
                unset($form['lisst_id']);
            }

            $this->_return($form);
        } else {
            $this->_not_found();
        }
    }

    /**
     * adding element by it's ID
     * response status codes:
     *      405 - not allowed
     * @param $id - add of requested element
     */
    public function add()
    {
        $this->_not_allowed();
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
     *      405 - not allowed
     * @param $id - add of requested element
     */
    public function delete()
    {
        $this->_not_allowed();
    }


}