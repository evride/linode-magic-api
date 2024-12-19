<?php
namespace evride;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Yaml\Yaml;

class ComposerScripts {

	public static function postInstall(){
		require_once('./vendor/guzzlehttp/guzzle/src/functions_include.php');
		require_once('./vendor/guzzlehttp/promises/src/functions_include.php');
		require_once('./vendor/guzzlehttp/psr7/src/functions_include.php');


		$client = new GuzzleClient;
		$promise = $client->getAsync('https://developers.linode.com/api/docs/v4/openapi.yaml');


		$promise->then( function (ResponseInterface $res){
				if($res->getStatusCode() === 200){

					$body = $res->getBody();
					$contents = $body->getContents();
					self::processContent($contents);
				}
				
			},function (RequestException $e){
		        echo $e->getMessage() . "\n";
		        echo $e->getRequest()->getMethod();

			});
		$promise->wait();
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
		foreach($linodeInfo['paths'] as $pathKey => $pathObj){
			$pathObj = explode('/', substr($pathKey, 1));
			$routeArr = &$apiObj['paths'];
			for($i = 0; $i < count($pathObj); $i++){
				$subResName = $pathObj[$i];
				if( !array_key_exists($subResName, $routeArr) ){
					if(preg_match("/{\w+}/", $subResName)){
						$subResName = '__parameter__';
						$routeArr['__parameter__'] = [
								'type' => 'parameter', 
								'name' => $pathObj[$i],
								'methods' => []
							];
					}else{
						$routeArr[$subResName] = [
								'type' => 'path', 
								'name' => $subResName,
								'methods' => []
							];

					}
				}
				$routeArr = &$routeArr[$subResName];
			}
			foreach($http_methods as $key){
				if( array_key_exists($key, $pathObj ) ){
					$routeArr['methods'][] = $key;
				}
			}
		}
		file_put_contents(dirname(__FILE__) . '/LinodeApiData.inc', serialize($apiObj));
	}
	protected static function cleanYamlContent($contents){
		// cleanup yaml content : caused bug in Yaml processor
		$cleanStart = stripos($contents, 'x-logo');
		$cleanEnd = stripos($contents, '}', $cleanStart);

		if( $cleanStart >= 1 && $cleanEnd >= 1){
			return substr_replace($contents, '', $cleanStart, $cleanEnd - $cleanStart + 1);
		}else{
			return $contents;
		}
	}
	protected static function parseYaml($contents){
		return Yaml::parse(self::cleanYamlContent($contents));
	}
}