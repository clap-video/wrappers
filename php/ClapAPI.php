<?php

class ClapAPI{

    protected $api = "https://api.clap.video/api/";
    protected $maxPhotos = 5;
    protected $headers = null;
    protected $expires_in = null;
    protected $accessToken = null;

    protected $clientId = "";
    protected $clientSecret = "";

    public function __construct($clientId, $clientSecret, $api = null){
        $this->clientId = $clientId;
        $this->client_secret = $clientSecret;

        $this->api = $api ?? $this->api;

        $this->generateAccessToken();
    }

    protected function generateUrl($endpoint){
        return "{$this->api}{$endpoint}";
    }

    protected function makeRequest($method, $url, $data = null, $headers = null, $error = null){
        $url = $this->generateUrl($url);

        $headers = array('Content-Type: application/json', $this->headers ?? '');

        $data_json = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if($method == "POST")
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);


        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $result;
    }

    protected function generateAccessToken(){
        $req = $this->makeRequest(
            "POST", "oauth",
            ["client_id" => $this->clientId, "client_secret" => $this->client_secret],
            "Error while generating Access Token"
        );

        $this->accessToken = isset($req["access_token"]) ? $req["access_token"] : null;
        $this->expiresIn = isset($req["expires_in"]) ? $req["expires_in"] : null;
        $this->headers = "Authorization: Access-Token {$this->accessToken}";
    }

    public function getProject($id){
        return $this->makeRequest("GET", "video/projects/{$id}");
    }

    public function createProject($options){
        $json_fields = $options['json_fields'] ?? [];

        if(isset($options['webhook_infos'])){
            $json_fields['webhook_infos'] = $options['webhook_infos'];
            unset($options['webhook_infos']);
        }

        if(isset($options['realty'])){
            $json_fields['realty'] = $options['realty'];
            unset($options['realty']);
        }

        $photos_list = $options['photos'] ?? [];

        if($photos_list){
            if(!isset($json_fields['realty'])){
                $json_fields['realty'] = [];
            }

            foreach($photos_list as $i=>$photo){
                $json_fields['realty']['photo'.($i+1)] = $photo;

                if(($i+1) >= $this->maxPhotos){
                    break;
                }
            }
            unset($options['photos']);
        }

        $options['json_fields'] = $json_fields;

        return $this->makeRequest("POST", "video/projects", $options);
    }

    public function getUserToken($userId){
        $req = $this->makeRequest("POST", "users/{$userId}/get-auth-token");

        return isset($req['token']) ? $req['token'] : null;
    }
}
?>
