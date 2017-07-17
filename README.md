<<<<<<< HEAD
# Estismail
=======
# Estismail-API

Example API call

<?php

require_once('/var/www/v1.estismail.com/web/app/Vendor/estisapiclient.php');

class ClientController extends AppController
{
    public function test()
    {
        Configure::write('debug', 2);
        $this->autoRender = false;
        $object = new EstisAPIclient('6fac749e554fb00c4b026c61da6bb45a03ee0613'); // your API key
      
        //
        //Emails
        $result = $object->sendrequest('/mailer/emails/','get',array('limit' => 10, 'page' => 1, 'fields' => json_encode(array('id', 'name', 'email', 'city', 'phone', 'date', 'status', 'ip', 'skype', 'subscribe_link')),'list_id' => '134', 'sort_field' => 'email', 'sort_direction' => 1));

        

        debug($result);

    }
}
>>>>>>> 831e0fdeaa8c388f5fab9f3ef26335a89db0fda2
