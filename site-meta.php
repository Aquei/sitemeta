<?php
/*
 *
 * avoid cache
 *
 */




include_once(dirname(__file__)."/Sitemeta.php");
$result = [];
if(!array_key_exists("url", $_GET)){
	bye();
}
$url = $_GET["url"];

if(!filter_var($url, FILTER_VALIDATE_URL)){
	bye();
}
	
$u = parse_url($url);
if($u["scheme"] !== "http" && $u["scheme"] !== "https"){
	bye();
}






$html = file_get_contents($url);
//もしutf8じゃなかったら変換
mb_language("Japanese");
$encoding = mb_detect_encoding($html);
if($encoding !== "UTF-8"){
	$html = mb_convert_encoding($html, "UTF-8", "auto");
}

$site = new Sitemeta();
$site->html = $html;
//print_r ($site->getAttributes(["meta"]));

$metas = $site->getAttributes(["meta"]);
//print_r($metas);
if(array_key_exists("meta", $metas) && count($metas["meta"])){
	foreach($metas["meta"] as $mt){
		if(array_key_exists("content", $mt)){
			if(array_key_exists("name", $mt)){
				$prop = "name";
			}elseif(array_key_exists("property", $mt)){
				$prop = "property";
			}else{
				continue;
			}

			$propName = $mt[$prop];
			$result["meta"][$propName] = $mt["content"];
		}
	}
}

$title = $site->getTextNode("title");
if($title){
	$result["title"] = $title;
}

$headers = [];
$headers[] = "X-Content-Type-Options: nosniff";
$headers[] = "X-XSS-Protection: 1; mode=block";
$headers[] = "Content-Security-Policy: 'none'";
$headers[] = "Access-Control-Allow-Origin: *";
$headers[] = "Timing-Allow-Origin: *";
$headers[] = "Cache-Control: public, max-age=31536000";
$headers[] = "Content-Type: application/json";
foreach($headers as $header){
	header($header);
}

$json = json_encode($result, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
if($json){
	echo $json;
}else{
	bye("json error");
}



//echo "---------------------------------------\n";
//echo $site->getTextNode("title");

function bye($message = null){
	header("HTTP/1.1 500 Internal Server Error");
	die("bye bye!".$message);
}
