<?php

App::uses('AppController', 'Controller');

class CampaignsController extends AppController
{
    public $uses = array(
        'ProCampaign',
        'User',
        'UserSetting',
        'EmailLog',
        'ProCampaignSchema',
        'ProCampaignEmail',
        'ProCampaignPointer',
        'ProCampaignInput'

    );

    protected $_params = array(
        'index' => array(
            'required' => array(),
            'optional' => array('limit', 'page')
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
        'ProCampaign' => array(
            'fields' => array('ProCampaign.id', 'ProCampaign.title'),
            'limit' => 10,
            'page' => 1,
            'recursive' => 0,
            'order' => array(
                'ProCampaign.id' => 'asc'
            )
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
        $this->paginate['ProCampaign']['conditions']['ProCampaign.user_id'] = $this->Auth->user('id');


        $limit = $this->_setlimit($this->request->data['limit']);
        $this->paginate['ProCampaign']['limit'] = $limit;

        $this->request->data['page'] *= 1;
        if ($this->request->data['page'] < 1) {
            $this->request->data['page'] = 1;
        }
        $this->paginate['ProCampaign']['page'] = $this->request->data['page'];

        $this->Paginator->settings = $this->paginate;
        $campaigns = $this->Paginator->paginate();
        if ($campaigns) {
            $campaigns = Set::classicExtract($campaigns, '{n}.ProCampaign');
            $return = array(
                'camapigns' => $campaigns
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
            'status'
        );
        $fields = json_decode($this->request->data['fields'], true);
        $fields_after_filter = array_intersect($fields_default, $fields);
        if (empty($fields_after_filter)) {
            $this->_bad_request('Invalid arguments');
        }

        $campaign = $this->ProCampaign->find('first', array(
            'fields' => $fields_after_filter,
            'conditions' => array(
                'ProCampaign.user_id' => $this->Auth->user('id'),
                'ProCampaign.id' => $id
            )
        ));
        if ($campaign) {
            $campaign = Set::classicExtract($campaign, 'ProCampaign');
            $this->_return(array('campaign' => $campaign));
        } else {
            $this->_not_found();
        }

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

        $campaign = $this->ProCampaign->find('first', array(
            'conditions' => array(
                'ProCampaign.user_id' => $this->Auth->user('id'),
                'ProCampaign.id' => $id
            )
        ));

        if ($campaign) {

            $this->ProCampaignEmail->deleteAll(array(
                'ProCampaignEmail.pro_campaign_id' => $id
            ));

            $this->ProCampaignInput->deleteAll(array(
                'ProCampaignInput.pro_campaign_id' => $id
            ));

            $this->ProCampaignPointer->deleteAll(array(
                'ProCampaignPointer.pro_campaign_id' => $id
            ));

            $this->ProCampaignSchema->deleteAll(array(
                'ProCampaignSchema.pro_campaign_id' => $id
            ));

            $this->ProCampaign->delete($id);

            $this->_empty_response();

        } else {

            $this->_not_found();
        }
    }
}
