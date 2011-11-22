<?php
class funcoes {
	public static function GetTranslationTable() {
		static $translation_table;
		if (is_null($translation_table)) {
			$translation_table = get_html_translation_table(HTML_ENTITIES);
			$translation_table[ chr(10) ] = "&#10;";
			$translation_table[ chr(13) ] = "&#13;";
		}
		return $translation_table;
	}
	public static function getXmlDocType($recordSet = 'recordset') {
		$translation_table = funcoes::GetTranslationTable();
		$retorno = "" .
			"<!DOCTYPE $recordSet [\n";
		foreach($translation_table as $key => $value) {
			if (strpos($value, '#')) continue;
			$key = ord($key);
			$retorno.="\t<!ENTITY ".substr($value, 1, strlen($value) - 2) .' "&#'.$key.';">'."\n";
		}
		$retorno.= "" .
			"]>\n";
		return $retorno;
	}
	public static function getXmlHeader($recordSet = 'recordset') {
		return '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n" .
			funcoes::getXmlDocType($recordSet);
	}
	public static function SubstituirTags($string){
		$translation_table = funcoes::GetTranslationTable();
		unset($translation_table['&']);
		return strtr($string, $translation_table);
	}
	public static function SubstituirEComercial($string){
		$posE = 0;
		do{
			$posE = strpos($string, '&', $posE);
			if($posE === false)
				break;
			$posPto = strpos($string, ';', $posE+1);
			if($posPto === false){
				$string = substr_replace($string, '&amp;', $posE, 1);
				$posE += 5;
				continue;
			}
			if(!in_array(substr($string, $posE, $posPto-$posE+1), funcoes::GetTranslationTable() )) {
				$string = substr_replace($string, '&amp;', $posE, 1);
				$posE += 5;
			} else $posE++;
		} while($posE !== false);
		return $string;
	}
	public static function parseRegistroToXml($registro, $estilo = 1, $record_name = 'record'){
		$retorno = array();
		$preformatter = array(
			'<br>' => '<br />',
			chr(10) => '',
			chr(13) => '',
			chr(147) => '"',
			chr(148) => '"',
		);
		if (is_array($registro))
		foreach ($registro as $chave => $valor) {
			if ( ($valor == '') || (is_null($valor)) || is_array($valor)) {
				continue;
			}
			foreach($preformatter as $key => $value) {
				$valor = str_ireplace($key, $value, $valor);
			}
			switch ($estilo) {
				case 1:
					$valor = funcoes::SubstituirEComercial($valor);
					$valor = funcoes::SubstituirTags($valor);
					$retorno[] = "$chave=\"". $valor .'"';
					break;
				case 2:
					$retorno[] = "<$chave>". rawurlencode($valor) ."</$chave>";
					break;
			}
		}

		switch ($estilo) {
			case 1: return "\n\t\t<$record_name \n\t\t\t".implode("\n\t\t\t", $retorno)." />"; break;
			case 2: return implode("\n\t\t\t", $retorno); break;
		}
		return '';
	}
}