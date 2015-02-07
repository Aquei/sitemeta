<?php
class Sitemeta{
	/*
	 *
	 * const
	 *
	 */

	const VERSION = "0.0.1";


	/*
	 *
	 * class vars
	 *
	 */


	/*
	 *
	 * instance vars
	 *
	 */

	private $html;

	function __construct(){
		return $this;
	}

	function __set($name, $value){

		switch($name){
			case "url":
				break;

			case "html":
				$this->html = (string) $value;
				break;

		}

		return $this;
	}

	function getAttributes($tags = array()){
		if(!$this->html){
			throw new Exception("need html");
		}
		//タグで分割する
		$result = array();
		$temp;

		//全てのタグと要素を取得
		preg_match_all('/<([^\/!][^\s\t>]+)\s*([^>]*?)\/?>/i', $this->html, $temp, PREG_SET_ORDER);


		//タグを配列に
		if(!is_array($tags)){
			$tags = array($tags);
		}


		//タグを小文字に
		$toSmallLetter = function($str){
			return strtolower($str);
		};

		$tags = array_map($toSmallLetter, $tags);


		//指定されたtagのみ抜き出す
		foreach($temp as $elem){
			$tag = strtolower($elem[1]);
			if(in_array($tag, $tags)){
				$attributes = [];
				//属性をパース
				$attrs = trim($elem[2]);
				preg_match_all('/(?<prop>[a-z1-9A-Z-]+)=(?<quote>[\"\'])(?<val>[^\"\']+)\k<quote>/', $attrs, $match, PREG_SET_ORDER);
				
				foreach($match as $m){
					//$attributes[] = ["property"=>$m["prop"], "value"=>$m["val"]];
					$attributes[$m["prop"]] = $m["val"];
				}

				//$result[] = array("tag"=>$tag, "attr"=>$attributes);
				$result[$tag][] = $attributes;
			}
		}

		return $result;

	}

	function getTextNode($tag){
		if(!$this->html){
			throw new Exception("need html");
		}
		//htmlのタイトルを返す
		if(strpos($this->html, '<'.$tag) !== false){
			$bool = preg_match('/<'.$tag.'[^>]*?>([^<]+)<\/'.$tag.'>/i', $this->html, $match);

			if($bool){
				return $match[1];
			}else{
				return false;
			}
		}
	}
}
