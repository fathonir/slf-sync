<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class Feeder Webservice versi 2.2, mengikuti panduan
 */
class Feederws
{
    private $url;

    function __construct($params)
    {
        $this->url = $params['url'];
    }

    /**
     * @param $data mixed JSON Object dari fungsi json_encode
     * @return string Hasil dari eksekusi webservice
     */
    public function runWS($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}