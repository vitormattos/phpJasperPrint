<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'libs/funcoes.class.php';

//$db = new SQLite3('arquivos/banco.sqlite');

//$result = $db->query('SELECT * FROM teste') or die('Ocorreu um erro!');

$retorno = '';
/*while ($row = $result->fetchArray(SQLITE3_ASSOC))
	$retorno.= funcoes::parseRegistroToXml($row)."\n";*/

$xml = funcoes::getXmlHeader('relatorio')."<relatorio>$retorno</relatorio>";
$xml = file_get_contents('arquivos/banco.xml');

define('JAVA_HOSTS', '127.0.0.1:9080');
require_once 'libs/JasperReports/JasperReports.class.php';

$jasperReport = new JasperReports();
$erros = $jasperReport->getErrors();
if($erros) {
	var_dump($erros);
	return;
}


$jasperReport->setJasperReport(dirname(__FILE__) . '/arquivos/report1.jasper');
$jasperReport->setXML($xml, '/relatorio/record');
$jasperReport->setParameter('IMAGEM_01', dirname(__FILE__) . '/img/jasper.gif');
$jasperReport->setParameter('IMAGEM_02', dirname(__FILE__) . '/img/php.png');
$jasperReport->setParameter('IMAGEM_03', dirname(__FILE__) . '/img/charge_01.jpg');
$jasperReport->download('teste.pdf');
$erros = $jasperReport->getErrors();
if($erros) {
	var_dump($erros);
}
