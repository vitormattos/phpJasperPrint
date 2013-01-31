<?php
/**
 * ATENCAO: necessita instalacao do jdk
 *
 */
require_once dirname(__FILE__) . '/Java.inc';
class JasperReports {
	public $LIB_PATH = '';
	public $binary_java_path = '/usr/bin/java';
	public $JAVA_BRIDGE_PATH = '';
	public $JAVA_BRIDGE_LOG_FILE = '';
	public $TMP_PATH = '/tmp/';
	private $LIB_FILES = array();
	private $jasperReportFile = '';
	private $errors = array();
	private $parameters = array();
	private $xmlDataSource = '';
	private $xmlRecordPath = '';
	private $xmlEncoding = 'UTF-8';

	public function __construct() {
		$this->JAVA_BRIDGE_PATH = dirname(__FILE__) . '/../JavaBridge/lib/';
		$this->JAVA_BRIDGE_LOG_FILE = dirname(__FILE__) . '/../JavaBridge/JavaBridge.log';
		if(!$this->LIB_PATH)
			$this->LIB_PATH = dirname(__FILE__);
		$this->loadLibList();
		$this->startVirtualMachine();
		try {
			$this->parameters = new Java('java.util.HashMap');
		} catch (Exception $e) {
			return $this->setError($e->getMessage());
		}
		$this->setVirtualize();
		$this->jasperReportFile = '';
		$this->xmlDataSource = '';
		$this->xmlRecordPath = '';
	}
	/**
	 * Carrega todo o conteúdo do diretório LIB_PATH informado nas configurações
	 * da classe. Esta carga é feita apenas uma vez.
	 *
	 */
	private function loadLibList() {
		if(count($this->LIB_FILES)) return;
		$handle = opendir($this->LIB_PATH);
		if( !$handle ) return $this->setError('Lib path incorreto.');
		while (false !== ($file = readdir($handle))) {
			if(substr($file, -4,4) != '.jar') $file .= '.jar';
			$file = $this->LIB_PATH . DIRECTORY_SEPARATOR . $file;
			if(!is_file($file)) continue;
			if (!in_array($file, $this->LIB_FILES)) {
				$this->LIB_FILES[] = $file;
			}
		}
		closedir($handle);
	}
	private function startVirtualMachine() {
		if (!count($this->LIB_FILES))
			return $this->setError('Lib files not found.');
		if(!class_exists('Java'))
			return $this->setError('PHP/Java Bridge não localizado.');
		try {
			__javaproxy_Client_getClient();
		} catch(Exception $e){
			$host = java_Protocol::getHost();
			pclose(popen(
				$this->binary_java_path . ' '.
				'-Djava.library.path=' . $this->JAVA_BRIDGE_PATH . ' '.
				'-Djava.class.path=' .
					$this->JAVA_BRIDGE_PATH . 'JavaBridge.jar:'.
					implode(':', $this->LIB_FILES).' '.
				'-Djava.awt.headless=true '.
				'-Dphp.java.bridge.base=' . $this->JAVA_BRIDGE_PATH . ' '.
				'php.java.bridge.Standalone SERVLET:' . $host[2] . ' 5 '.
					$this->JAVA_BRIDGE_LOG_FILE . ' '.
					'2>&1 &', 'r'
			));
			// Aguarda 1 segundo para garantir que a VM levantou
			sleep(1);
			try {
				__javaproxy_Client_getClient();
			} catch(Exception $e){
				$this->setError('Erro ao iniciar JavaBridge');
			}
		}
	}
	public function setParameter($parameter, $value) {
		if ( is_null($value) ) return;
		if ( is_string($value) && strlen($value) > 0 )
			$this->parameters->put("$parameter", "$value");
		if ( is_numeric($value) )
			$this->parameters->put("$parameter", new Java('java.lang.Integer', $value));
	}
	public function setJasperReport($jasperReport) {
		if ( !file_exists($jasperReport) ) {
			return $this->setError("Arquivo [$jasperReport] nao encontrado.");
		}
		$array = explode('.', $jasperReport);
		$extension = end($array);
		$accept = array('jrxml', 'jasper');
		if(!in_array($extension, $accept) || !file_exists($jasperReport) ) {
			return $this->setError(
				'Arquivo [$jasperReport] inválido. ' .
				'Informe um arquivo jasper ou jrxml.'
			);
		}
		if($extension == 'jrxml') {
			try {
				$compileManager = new JavaClass(
					"net.sf.jasperreports.engine.JasperCompileManager"
				);
				$tmp_name = tempnam($this->TMP_PATH, 'tmp');
				$compileManager->compileReport(
					$jasperReport,
					$tmp_name
				);
				$jasperReport = $tmp_name;
			} catch(Exception $e) {
				return $this->setError(
					"Erro ao compilar o arquivo [$jasperReport]:\n" . $e->getMessage()
				);
			}
		}
		$this->jasperReportFile = $jasperReport;
	}
	public function setXML($xml, $recordPath, $encoding = 'UTF-8') {
		$this->xmlDataSource = $xml;
		$this->xmlRecordPath = $recordPath;
		$this->xmlEncoding = $encoding;
	}
	private function getDataSource() {
		try {
			if ( strlen($this->xmlDataSource) > 0 ) {
				$String = new Java('java.lang.String', $this->xmlDataSource);
				$ByteArrayInputStream = new Java(
					'java.io.ByteArrayInputStream',
					$String->getBytes($this->xmlEncoding)
				);
				if (strlen($this->xmlRecordPath) > 0) {
					$dataSource = new Java(
						'net.sf.jasperreports.engine.data.JRXmlDataSource',
						$ByteArrayInputStream,
						new Java('java.lang.String', $this->xmlRecordPath)
					);
				} else {
					$dataSource = new Java(
						'net.sf.jasperreports.engine.data.JRXmlDataSource',
						$ByteArrayInputStream
					);
				}
			} else {
				$dataSource = new Java('net.sf.jasperreports.engine.JREmptyDataSource');
			}
		} catch (Exception $e) {
			return $this->setError($e->getMessage());
		}
		return $dataSource;
	}
	private function setError($message) {
		$this->errors[] = '<strong>ERRO gerando relatório:</strong> ' . $message;
	}
	public function getErrors(){
		return $this->errors;
	}
	private function stream($stream, $fileName = '', $contentType = '') {
		if (!strlen($stream)) return $this->setError('Stream vazio.');
		if (headers_sent()) return $this->setError('Cabeçalho já enviado.');
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: ' . $contentType);
		if (strlen($fileName) > 0) {
			header("Content-Disposition: attachment; filename=\"$fileName\"");
		} else {
			header('Content-Disposition: attachment');
		}
		header('Content-Length: ' . strlen($stream));
		header("Content-Transfer-Encoding: binary\n");
		if (is_object($stream)) {
			echo java_cast($stream, 'string');
		} else {
			echo $stream;
		}
	}
	/**
	 * Afim de possibilitar a geração de relatórios relativamente grandes, o
	 * JasperReports dispõe de um recurso chamado "virtualização". Ao gerar
	 * um relatório utilizando virtualização, o JasperReports busca gerenciar
	 * melhor a memória RAM utilizada para geração do mesmo, tentando assim,
	 * eliminar um grande incômodo chamado "OutOfMemoryException: Java heap space"
	 */
	private function setVirtualize() {
		$swapFile = new Java(
			'net.sf.jasperreports.engine.util.JRSwapFile', $this->TMP_PATH, 512, 512
		);
		$virtualizer = new Java(
			'net.sf.jasperreports.engine.fill.JRSwapFileVirtualizer', 10, $swapFile, true
		);
		$this->parameters->put('REPORT_VIRTUALIZER', $virtualizer);
	}
	private function getJasperPrint() {
		if (count($this->errors)) return;
		$data_source = $this->getDataSource();
		if (count($this->errors)) return;
		try {
			$JasperFillManager = new JavaClass(
				'net.sf.jasperreports.engine.JasperFillManager'
			);
			$jasperPrint = $JasperFillManager->fillReport(
				$this->jasperReportFile, $this->parameters, $data_source
			);
		} catch (Exception $e) {
			return $this->setError($e->getMessage());
		}
		return $jasperPrint;
	}
	public function download($fileName = null, $contentType = null) {
		$jasperPrint = $this->getJasperPrint();
		if (!$jasperPrint) return NULL;
		$JasperExportManager = new JavaClass(
			'net.sf.jasperreports.engine.JasperExportManager'
		);
		$array = explode('.', $fileName);
		$extension = end($array);
		$extension = $extension?:'pdf';
		$contentType = $contentType?:'application/'.$extension;
		if(!$fileName) $fileName = md5($stream) . '.' . $extension;
		try {
			$method = 'exportReportTo' . ucwords($extension) . 'File';
			$tmp_name = tempnam($this->TMP_PATH, 'tmp');
			$JasperExportManager->$method($jasperPrint, $tmp_name);
			$stream = file_get_contents($tmp_name);
		} catch (Exception $e) {
			return $this->setError($e->getMessage());
		}
		$this->stream($stream, $fileName, $contentType);
	}
}
