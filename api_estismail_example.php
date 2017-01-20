<?php

class EstisAPIclient
{
    private $header;
    private $apigateway = 'https://v1.estismail.com';
    private $headerid = 'X-Estis-Auth: ';

    public function __construct($header)
    {
        $this->header = $header;

    }


    public function sendrequest($url, $method, $params)
    {
        $result = false;
        if (!is_array($params)) {
            $params = array();
        }

        switch ($method) {
            case 'get':
                $result = $this->sendgetrequest($url, $params);
                break;
            case 'post':
                $result = $this->sendpostrequest($url, $params);
                break;
            case 'put':
                $result = $this->sendputrequest($url, $params);
                break;
            case 'delete':
                $result = $this->senddeleterequest($url, $params);
                break;
        }
        return json_decode($result, true);
        //return $result;

    }


    public function sendgetrequest($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apigateway . $url.'?'.http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = array($this->headerid . $this->header);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;

    }

    public function sendpostrequest($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apigateway . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $headers = array($this->headerid . $this->header);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;

    }

    public function sendputrequest($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apigateway . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // note the PUT here
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $headers = array($this->headerid . $this->header);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function senddeleterequest($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apigateway . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $headers = array($this->headerid . $this->header);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

}
