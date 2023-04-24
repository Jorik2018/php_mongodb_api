<?php

include_once("config.php");

include_once("utils.php");

function post($path,$data=array()){
	global $OAUTH_URL,$OAUTH_CLIENT_ID,$OAUTH_CLIENT_SECRET,$BASE_URL;
	$ch = curl_init($path);
	clog($path);
	curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));
	//http_build_query($data));
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_TIMEOUT,30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$return = curl_exec($ch);
	clog($return);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	http_response_code($httpcode);
	curl_close($ch);
	return json_decode($return,true);
}

function get($path,$data=array()){
	global $OAUTH_URL,$OAUTH_CLIENT_ID,$OAUTH_CLIENT_SECRET,$BASE_URL;
	clog($BASE_URL.$path);
	$ch = curl_init($BASE_URL.$path);
	
	//curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));
	//http_build_query($data));
	curl_setopt($ch, CURLOPT_POST,0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_TIMEOUT,30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	
	$return = curl_exec($ch);
	clog($return);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	http_response_code($httpcode);
	curl_close($ch);
	return json_decode($return,true);
}

function callApi($path,$data=array()){

	global $OAUTH_URL,$OAUTH_CLIENT_ID,$OAUTH_CLIENT_SECRET,$BASE_URL;

	return '{"uid":1}';
	$ch = curl_init($OAUTH_URL.$path);
	if($path=='/token'){
		$code=$data['code'];
		curl_setopt($ch, CURLOPT_POSTFIELDS,"grant_type=authorization_code&scope=profile&code=$code");
		curl_setopt($ch, CURLOPT_USERPWD,$OAUTH_CLIENT_ID.":" .$OAUTH_CLIENT_SECRET);
		curl_setopt($ch, CURLOPT_POST,1);
	}else{
		$token=getBearerToken();
		if(!$token)throw new Exception('No autorized.');
		curl_setopt($ch, CURLOPT_POST,0);
		$authorization = "Authorization: Bearer ".$token; // Prepare the authorisation token
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization )); // Inject the token into the header
	}
	//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $additionalHeaders));
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_TIMEOUT,30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$return = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	http_response_code($httpcode);
	curl_close($ch);
	return $return;
}

$url=$_SERVER['REQUEST_URI'];

$url=explode( '/', $url); 

if(!isset($url[1])||$url[1]!='token'){
	$token=getBearerToken();
}
$collection=str_replace("-", "_",$url[1]);
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	$writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 100);
	$bulk = new MongoDB\Driver\BulkWrite;
	$user=getCurrentUser($token);
	$ids=explode(',',$url[count($url)-1]);
	foreach ($ids as $id) {
		$id = new \MongoDB\BSON\ObjectId($id);
		$data=array('canceled'=>1);
		$data['deleted_uid'] =$user['uid'];
		$data['deleted_directory'] =$user['directory'];
		$data['deleted_user'] =$user['user'];
		$data['deleted'] =floor(microtime(true));
		$bulk->update(['_id' => $id],['$set'=>$data]);
	}
	$result=$manager->executeBulkWrite("db.$collection", $bulk,$writeConcern);
	die(json_encode($result));
}else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$json = file_get_contents('php://input');
	try{
		$data = (array)json_decode($json, false, 512, JSON_THROW_ON_ERROR);
	}catch(Exception $e){
		die(json_encode(['error' => $e->getMessage()]));
	}
	if($url[1]=='token'){
		if(isset($data['client'])&&$data['client']=='disabled'){
			$OAUTH_CLIENT_ID=$OAUTH_CLIENT_ID_DISABLED;
			$OAUTH_CLIENT_SECRET=$OAUTH_CLIENT_SECRET_DISABLED;
		}
		die(callApi('/'.$url[1],$data));
	}
	$user=getCurrentUser($token);
	$id=$data['_id']?? null;
	unset($data['_id']);
	$data['uid'] =$user['uid'];
	$bulk = new MongoDB\Driver\BulkWrite;
	if($id){
		$id=(array)$id;
		$id=new MongoDB\BSON\ObjectId($id['$oid']);
		$data['updated_uid'] =$user['uid'];
		if(isset($user['directory']))
			$data['inserted_directory'] =$user['directory'];
		if(isset($user['company']))
			$data['inserted_company'] =$user['company'];
		$data['updated_user'] =$user['user'];
		$data['updated'] =floor(microtime(true));
		$bulk->update(['_id' => $id],['$set'=>$data]);
	}else{
		$data['_id'] =new MongoDB\BSON\ObjectId;
		$data['updated_uid'] =$user['uid'];
		if(isset($user['directory']))
			$data['updated_directory'] =$user['directory'];
		if(isset($user['company']))
			$data['updated_company'] =$user['company'];
		$data['updated_user'] =$user['user'];
		$data['updated'] =floor(microtime(true));
		$bulk->insert($data);
	}
	$writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 100); 
	$r=$manager->executeBulkWrite("db.$collection", $bulk,$writeConcern);
	die(json_encode($data));
	if ($writeConcernError = $r->getWriteConcernError()) {
		printf("%s (%d): %s\n", $writeConcernError->getMessage(), $writeConcernError->getCode(), var_export($writeConcernError->getInfo(), true));
	}
}else{
	$user=getCurrentUser($token);
	$data=array();
	if(count($url)==4&&$url[2]=='code'){
		$filter = ['code' =>$url[3]];
		$query = new MongoDB\Driver\Query($filter);
		$cursor = $manager->executeQuery("db.$collection", $query);
		foreach ($cursor as $document) {
			die(json_encode($document));
		}
		$data=array();
		$result=post("http://web.regionancash.gob.pe/api/reniec/",['dni'=>$url[3]]);
		if(isset($result['datosPersona'])){
			$people=$result['datosPersona'];
			$data=array('fullName'=>$people['apPrimer'].' '.$people['apSegundo'].' '
				.$people['prenombres'],'address'=>$people['direccion']);
		}
		//Find previous record
		$result=get('/api/grds/disabled/0/0?numDoc='.$url[3]);
		if(array_key_exists(0, $result['data'])){
			$data=array_merge($data,$result['data'][0]);
		}

		$data['search']=$url[3];
		die(json_encode($data));
	}else if(count($url)==3){
		$id = new \MongoDB\BSON\ObjectId($url[2]);
		$filter = ['_id' =>$id];
		$query = new MongoDB\Driver\Query($filter);
		$cursor = $manager->executeQuery("db.$collection", $query);
		foreach ($cursor as $document) {
			die(json_encode($document));
		}
	}else{
		$filter = ['canceled' => ['$ne' => 1]];

	
		if($user['uid']!=1){
			//averiguar si es super_admin segun los permisos de la db
			$filter['uid'] =$user['uid'];
		}
		$code=isset($_GET["code"])?$_GET["code"]:null;
		if($code)$filter['code'] = new \MongoDB\BSON\Regex(preg_quote($code), 'i');
		
		$fullName=isset($_GET["fullName"])?$_GET["fullName"]:null;
		if($fullName){
			if($fullName)$filter['fullName'] = new \MongoDB\BSON\Regex(preg_quote($fullName), 'i');
			/*$filter['$or']=[
				['names' => new \MongoDB\BSON\Regex(preg_quote($fullName), 'i')],
				['surnames' => new \MongoDB\BSON\Regex(preg_quote($fullName), 'i')]
			];*/
		}

		$address=isset($_GET["address"])?$_GET["address"]:null;
		if($address)$filter['address'] = new \MongoDB\BSON\Regex(preg_quote($address), 'i');	
		
		
		$age=isset($_GET["age"])?$_GET["age"]:null;
		

		$aggregate=[
			['$addFields' => ['fullName' => ['$concat' => ['$names', '$surnames']]]], 
			['$match' => $filter]
		];

		

		
		/*$query = new MongoDB\Driver\Query($filter,['skip' => $url[2],'limit'=>$url[3]]); 



		$agg=[
			['$addFields' => ['fullName' => ['$concat' => ['$names', '$surnames']]]], 
			['$match' => $filter]
		]



		$cmd = new MongoDB\Driver\Command(['count' => $collection,'query' => $filter]);
		$all = $manager->executeCommand('db', $cmd);
		$all=(array)$all->toArray()[0];
		$all['size']=$all['n'];
		$cursor = $manager->executeQuery("db.$collection", $query);*/
		$cursor = $manager->executeCommand('db',new MongoDB\Driver\Command([
			'aggregate' => $collection,
			'pipeline' => array_merge($aggregate,[['$count'=>'count']]),
			'cursor' => new stdClass
		]));

		$all=[];
		foreach ($cursor as $document) {
			$all['size']=$document->count;
		}

		$cursor = $manager->executeCommand('db',new MongoDB\Driver\Command([
				'aggregate' => $collection,
				'pipeline' => array_merge($aggregate,[['$skip' => intval($url[2])],['$limit' =>intval($url[3])]]),
			'cursor' => new stdClass
		]));
		foreach ($cursor as $document) {
			$data[]=$document;
		}
		$all['data']=$data;
		echo json_encode($all);
	}
	//echo json_encode($data);
}
?>