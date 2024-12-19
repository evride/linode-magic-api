<?php

namespace evride;




use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response;

class LinodeService {


   	private $token = "";
   	private $client = null;
   	private $assoc = false;
   	private $routesData = null;
   	private $base_uri = '';
	public function __construct($token, $async = false, $assoc = true){
		$this->initializeRoutesData();
		$this->token = $token;
		$this->assoc = $assoc;
		$this->client =  new Client([
            'base_uri' => $this->base_uri,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$this->token,
                'Content-Type' => 'application/json',
            ],
        ]);

	}
	private function initializeRoutesData(){
		if(!$this->routesData){
			if(file_exists(dirname(__FILE__) . '/LinodeApiData.inc')){
				$temp = unserialize(file_get_contents(dirname(__FILE__) . '/LinodeApiData.inc'));

				$this->routesData = $temp['paths'];
				$this->base_uri = $temp['base_uri'];
			}

		}
	}

	public function getEndpoint($str){
		print_r($str);
		$req = new GuzzleRequest('GET', $str);
        try{
			$response = $this->client->send($req);
		} catch (RequestException $e) {
		    echo Psr7\str($e->getRequest());
		    if ($e->hasResponse()) {
		        echo Psr7\str($e->getResponse());
		    }
		}
		print_r($req);
		$body = $response->getBody();

		$contents = $body->getContents();
		return json_decode($contents, $this->assoc);
	}

	public function __call($key, $args){
		//print_r($key);
		//print_r($args);
		
	}
}
