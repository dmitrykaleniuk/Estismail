<?php

class Warehouse
{

    private $baseUrl = 'https://warehouse.checkbounce.org';
    private $prefix = '/v1';
    private $path = array(
	    'input_batch' => '/input/batch',
        'output_batch' => '/output/batch'
    );
    private $apiKey;

    private $limitEmails = 10000;

    private $exception_codes = array(
        400 => 'Bad request',
        401 => 'Unauthorized',
        404 => 'Not found'
    );

    public function __construct($api_key)
    {

        $this->apiKey = $api_key;

    }

    public function addemails($emails)
    {

        $all_emails = array();

        $count = count($emails);
        if ($count > $this->limitEmails) {
            throw new Exception('Exceeded the limit of emails:'.$this->limitEmails);
        }

        if (empty($emails)) {
            throw new Exception('Array of emails is empty');
        }

        foreach ($emails as $key => $value) {
            $all_emails[$value['reason']][$key] = urlencode($value['email']);
        }

        $all_emails = array_map(function ($value) {
            return implode(';', $value);
        }, $all_emails);

        $all_emails = implode('::', array_map(
            function ($v, $k) {
                return $k.':'.$v;
            },
            $all_emails,
            array_keys($all_emails)
        ));
        $params = 'api_key=' . $this->apiKey . '&data=' . $all_emails;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $this->prefix . $this->path['input_batch']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info['http_code'] != 200) {
            $this->_exceptions($info['http_code']);
        }

        return true;

    }


    public function checkemails($emails) {
        $all_emails = array();

        $count = count($emails);
        if ($count > $this->limitEmails) {
            throw new Exception('Exceeded the limit of emails:'.$this->limitEmails);
        }

        if (empty($emails)) {
            throw new Exception('Array of emails is empty');
        }

        foreach ($emails as $key => $value) {
            $key = substr($key, -11, 11);
            $all_emails[$key] = urlencode($value);
        }
        $all_emails = implode(';', array_map(
            function ($v, $k) {
                return $k.'::'.$v;
            },
            $all_emails,
            array_keys($all_emails)
        ));

        $params = 'api_key=' . $this->apiKey . '&data=' . $all_emails;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $this->prefix . $this->path['output_batch']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info['http_code'] != 200) {
            $this->_exceptions($info['http_code']);
        }

        return $output;


    }

    private function _exceptions($http_code)
    {

        if (array_key_exists($http_code, $this->exception_codes)) {
            throw new Exception($this->exception_codes[$http_code]);
        } else {
            throw new Exception('Exception');
        }

    }

}