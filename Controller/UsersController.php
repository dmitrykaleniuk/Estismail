<?php

App::uses('AppController', 'Controller');

class UsersController extends AppController
{
    public $uses = array(
        'User'
    );

    protected $_params = array(
        'index' => array(
            'required' => array(),
            'optional' => array('fields')
        ),
        'add' => array(
            'required' => array(),
            'optional' => array()
        ),
        'view' => array(
            'required' => array(),
            'optional' => array()
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
        'User' => array(
            'limit' => 1
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
        $this->paginate['User']['conditions']['User.id'] = $this->Auth->user('id');
        $fields_default = array(
            'login',
            'email',
            'name',
            'lastname'
        );

        $fields = json_decode($this->request->data['fields'], true);

        $fields_after_filter = array_intersect($fields_default, $fields);
        if (empty($fields_after_filter)) {
            $fields_after_filter[] = 'login';
        }
        $this->paginate['User']['fields'] = $fields_after_filter;
        $this->Paginator->settings = $this->paginate;
        $user = $this->Paginator->paginate();
        if ($user) {
            $user = Set::classicExtract($user, '{n}.User');
            $return = array(
                'user' => $user[0]
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
    public function view()
    {
        $this->_not_allowed();
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