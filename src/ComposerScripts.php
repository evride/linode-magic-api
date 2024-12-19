<?php
namespace evride;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class ComposerScripts {

	public static function postInstall(){
		if(file_exists(dirname(__FILE__) . '/Linode.openapi.yaml')){
			$contents = file_get_contents(dirname(__FILE__) . '/Linode.openapi.yaml');
			self::processContent($contents);
		}else{
			self::guzzleFile();
		}
	}
	protected static function guzzleFile(){
		require_once('./vendor/guzzlehttp/guzzle/src/functions_include.php');
		require_once('./vendor/guzzlehttp/promises/src/functions_include.php');
		require_once('./vendor/guzzlehttp/psr7/src/functions_include.php');

		$client = new GuzzleClient;
		$promise = $client->getAsync('https://developers.linode.com/api/docs/v4/openapi.yaml');


		$promise->then( function (ResponseInterface $res){
				if($res->getStatusCode() === 200){
					$body = $res->getBody();
					$contents = $body->getContents();
					self::cacheContents($contents);
					self::processContent($contents);
				}
				
			},function (RequestException $e){
		        echo $e->getMessage() . "\n";
		        echo $e->getRequest()->getMethod();

			});
		$promise->wait();
		
	}
	protected static function cacheContents($contents){		
		file_put_contents(dirname(__FILE__) . '/Linode.openapi.yaml', $contents);
	}
	protected static function processContentOld($contents){
		$linodeInfo = self::parseYaml($contents);
		if(!array_key_exists('servers', $linodeInfo)){
			exit(1);
		}
		$apiObj = [];
		$apiObj['base_uri'] = $linodeInfo['servers'][0]['url'];
		$apiObj['paths'] = [];


		$http_methods = ['get', 'post', 'put', 'delete'];
		$lastPath = "";
		foreach($linodeInfo['paths'] as $pathKey => $pathObj){
			$branch = [];
			$parameters = null;

			$pathArr = explode('/', substr($pathKey, 1)); 
			$paramLastSubpath = false;
			for($i = 0; $i < count($pathArr); $i++){
				$subResName = $pathArr[$i];
				$subPathIsParam = false;
				if(preg_match("/{(?<ParameterName>\w+)}/", $subResName, $matchedParam)){
					$subPathIsParam = 
					$subResName = '__parameters__';
					if($parameters === null){
						$parameters = [];
					}
					$parameters[] = $subResName;
				}else{
					
				}
				if( !array_key_exists($subResName, $routeArr) ){
					$routeArr[$subResName] = [];
				}
				$routeArr = &$routeArr[$subResName];
			}
			$routeArr['methods'] = [];
			print_r($pathObj);
			foreach($http_methods as $index=>$key){
				if( array_key_exists($key, $pathObj ) ){
					$routeArr['methods'][] = $key;
				}
			}

		}
		file_put_contents(dirname(__FILE__) . '/LinodeApiData.inc', serialize($apiObj));
	}
	protected static function processContent($contents){
		$linodeInfo = self::parseYaml($contents);
		if(!array_key_exists('servers', $linodeInfo)){
			exit(1);
		}
		$apiObj = [];
		$apiObj['base_uri'] = $linodeInfo['servers'][0]['url'];
		$apiObj['paths'] = [];


		$http_methods = ['get', 'post', 'put', 'delete'];
		$lastPath = "";
		foreach($linodeInfo['paths'] as $pathKey => $pathObj){
			$routeArr = &$apiObj['paths'];
			$branch = [];
			$parameters = null;

			$pathArr = explode('/', substr($pathKey, 1)); 
			$paramLastSubpath = false;
			for($i = 0; $i < count($pathArr); $i++){
				$subResName = $pathArr[$i];
				$subPathIsParam = false;
				if(preg_match("/{(?<ParameterName>\w+)}/", $subResName, $matchedParam)){
					
				}else{
					
				}
				if( !array_key_exists($subResName, $routeArr) ){
					$routeArr[$subResName] = [];
				}
				$routeArr = &$routeArr[$subResName];
			}
			//$routeArr['methods'] = [];
			//print_r($pathObj);
			foreach($http_methods as $index=>$key){
				if( array_key_exists($key, $pathObj ) ){
					$routeArr['__methods__'][] = $key;
				}
			}

		}
		file_put_contents(dirname(__FILE__) . '/LinodeApiData.inc', serialize($apiObj));
	}
	public static function test(){		
		$obj = unserialize(file_get_contents(dirname(__FILE__) . '/LinodeApiData.inc'));
		print_r($obj);
	}
	protected static function cleanYamlContent($contents){
		// cleanup yaml content : caused bug in Yaml processor
		$cleaned = "";
    	$line = strtok($contents, PHP_EOL);
    	$fixInline = "";
		while($line !== false){
			if($fixInline === "" && preg_match("/\s+[A-Za-z\-]+:\s?{/", $line) && !preg_match("/}$/", $line)){
				$fixInline = $line;
			}else if($fixInline !== ""){
				$fixInline .= $line;
				if(preg_match("/}$/", $line)){
					$bracketStart = stripos($fixInline, "{" );
					$fixed = substr($fixInline, 0, $bracketStart) .  preg_replace("/\s+/", " ", substr($fixInline, $bracketStart));
					$fixInline = "";
					$cleaned .= $fixed . PHP_EOL;
				}
			}else{
				$cleaned .= $line . PHP_EOL;
			}

			$line = strtok(PHP_EOL);
		}
		return $cleaned;
	}
	protected static function parseYaml($contents){
		try {
		    $parsed = Yaml::parse(self::cleanYamlContent($contents), Yaml::PARSE_CUSTOM_TAGS | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
		} catch (ParseException $exception) {
		    printf('Unable to parse the YAML string: %s', $exception->getMessage());
		}
		return $parsed;
	}
}