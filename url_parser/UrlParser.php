<?php

class UrlParser
{
    private $links = array();
    private $redirect_count = 0;
    public function checkurl($url)
    {

        $url = trim($url);

        if($this->redirect_count == 10) {
            return $this->links;
        }
        if($this->redirect_count == 0) {
            $this->links[] = $url;
        }

        $matches = array();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if(!empty($info['redirect_url']))
        {
            $this->links[] =  $info['redirect_url'];
            $this->redirect_count += 1;
            $this->checkurl($info['redirect_url']);
        }

        $templates = array(
            '/(?:window|document)?\.?location\.?(?:replace|href|reload|assign)?\([\'"]{1}(https?:\/\/[^\'"]+)[\'"]{1}\)/',
            '/location[^=]*=[^\'"]*[\'"]{1}(https?:\/\/[^\'"]+)[\'"]{1}/',
            '/\(location\)\.(?:attr|prop)+\([^\'"]*[\'"]{1}[^href]*href[^\'"]*[\'"]{1}[^,]*,[^\'"]*[\'"]{1}[^http]*(https?:\/\/[^\'"]+)[\'"]{1}\)/',
            '/jq\(window\)\.(?:attr|prop)+\([^\'"]*[\'"]{1}[^location]*location[^\'"]*[\'"]{1}[^,]*,[^\'"]*[\'"]{1}[^http]*(https?:\/\/[^\'"]+)[\'"]{1}\)/',
            '/content[^=]*=[\'"]{1}[0-9]{1,100};[^http]*(https?:\/\/[^\'"]+)/',
            '/iframe[^>]*src[^=]*=[^\'"]*[\'"]{1}(https?:\/\/[^\'"]+)[\'"]{1}/',
            '/Refresh:[^0-9]*[0-9]{1,100}[^:]*;[^url]*url[^=]*=[^http]*[^http]*(https?:\/\/[^\n]+)[^\n]/'
            );

        preg_match('/iframe[^>]*src[^=]*=[^\'"]*[\'"]{1}(https?:\/\/(?:player.vimeo|www.youtube|youtu.be)+[^\'"]+)[\'"]{1}/', $result, $rez);
        if($rez){
            return $this->links;
        }
        foreach ($templates as $key => $preg_value) {

            preg_match($preg_value, $result, $matches);
            if ($matches) {
                $this->links[] = $matches[1];
                $this->redirect_count += 1;
                $this->checkurl($matches[1]);
            }
        }

        return $this->links;
    }

}
