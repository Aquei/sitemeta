<?php
/*
 *
 * avoid cache
 *
 */


/*
 * FUCK
 */
$arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
);  
$no_ssl = stream_context_create($arrContextOptions);





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

$origin = "";
if(isset($_SERVER["HTTP_ORIGIN"])){
	$origin = $_SERVER["HTTP_ORIGIN"];
	$parsedUrl = parse_url($origin);
	if(!preg_match('/\.?srytk\.com$/', $parsedUrl["host"]) || $parsedUrl["host"] != "localhost"){
		bye2();
	}
}




$html = file_get_contents($url, false, $no_ssl);
//ã‚‚ã—utf8ã˜ã‚ƒãªã‹ã£ãŸã‚‰å¤‰æ›
mb_language("Japanese");
$encoding = mb_detect_encoding($html);
if($encoding !== "UTF-8"){
	$html = mb_convert_encoding($html, "UTF-8", "auto");
}

$site = new Sitemeta();
$site->html = $html;
//print_r ($site->getAttributes(["meta"]));

$metas = $site->getAttributes(["meta", "link"]);
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

if(array_key_exists("link", $metas) && count($metas["link"])){
	foreach($metas["link"] as $lk){
		//’T‚·

		//feed
		if(isset($lk["rel"]) && $lk["rel"] == "alternate" && isset($lk["href"]) && isset($lk["type"])){
			if($lk["type"] == "application/rss+xml" || $lk["type"] == "application/atom+xml"){
				$feed_item = array();
				$feed_item["type"] = $lk["type"];
				$feed_item["url"] = $lk["href"];

				if(isset($lk["title"])){
					$feed_item["title"] = $lk["title"];
				}

				$result["link"]["feed"][] = $feed_item;
				continue;
			}
		}

		//icon
		if(isset($lk["href"]) && isset($lk["rel"]) && strpos(strtolower($lk["rel"]), "icon") !== false){
			$icon_item = array();

			$icon_item["url"] = $lk["href"];
			if(isset($lk["sizes"])){
				$icon_item["sizes"] = $lk["sizes"];
			}

			$result["link"]["icon"][] = $icon_item;
			continue;
		}

		//oembed
		if(isset($lk["rel"]) && $lk["rel"] == "alternate" && isset($lk["type"]) && $lk["type"] == "application/json+oembed" && isset($lk["href"]) && preg_match('/^https?:\/\/[a-z]/', $lk["href"])){
			$oembed_item = array();
			$oembed_item["url"] = $lk["href"];
			$oembed_item["type"] = $lk["type"];
			$oembed_item["data"] = json_decode(file_get_contents($lk["href"], false, $no_ssl), true);

			$result["link"]["oembed"][] = $oembed_item;
			continue;
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
$headers[] = "Cache-Control: public, max-age=".(60*60*24*30); //30“úƒLƒƒƒbƒVƒ…
$headers[] = "Content-Type: application/json";
$headers[] = "Vary: Accept-Encoding, Origin";

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
	header("x-bye: bye");
	header("HTTP/1.1 500 Internal Server Error");
	die("bye bye!".$message);
}

function bye2($message = null){
	header("HTTP/1.1 403 Forbidden");
	die("origin error :".$message);
}
