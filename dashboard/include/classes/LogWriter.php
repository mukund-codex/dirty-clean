<?php
	/*
	 * LogWriter V3
	 */
	class LogWriter{
		private $EOL = "
";
		private $divider = "===================";
		private $isEnabled = true;
		
		private $outputDir = "../extra/log/"; // IMPORTANT ADD / at the end
		private $outputFile = "";
		private $prefix = "";

		// function __construct(){
			// $this->isEnabled = true;
			// echo "obj created";
		// }

		function setFileName($filename){
			$this->outputFile = $filename;
		}
		function setFileDirectory($directoryName){
			$this->outputDir = $directoryName;
		}
		function getFileDirectory(){
			return $this->outputDir;
		}
		function printDiskDetails(){
			echo "Total Space: ".(disk_total_space($this->getFileDirectory())/(1024*1024*1024));
			echo "<br/>";
			echo "Empty Space: ".(disk_free_space($this->getFileDirectory())/(1024*1024*1024));
		}
		function writeToFile($string, $url = "undefined URL"){
			if($this->isEnabled){
				$data = $this->EOL;
				$data .= $this->divider;
				$data .= $this->EOL;
				$data .= 'TIME - '.time().' - '.date("r").' - '.$_SERVER['REMOTE_ADDR'];
				$data .= $this->EOL;
				// $data .= 'HTTP_REFERER - '.$_SERVER['HTTP_REFERER'];
				// $data .= $this->EOL;
				$data .= 'HTTP_USER_AGENT - '.$_SERVER['HTTP_USER_AGENT'];
				$data .= $this->EOL;
				$data .= 'API - '.$url;
				$data .= $this->EOL;
				$data .= $this->divider;
				$data .= $this->EOL;
				$data .= $this->EOL;
				$data .= $string;
				$data .= $this->EOL;

				/* write to log for FLIGHT API */
				/*$fp = fopen("", "a");
				for ($written = 0; $written < strlen($string); $written += $fwrite) {
					$fwrite = fwrite($fp, substr($string, $written));
					if ($fwrite === false) {
						return $written;
					}
				}
				fclose($fp);
				return $written; */
				$filename = $this->outputDir.$this->prefix."-".$this->outputFile;
				// check if file exists and is writable first.
				if (is_writable($filename)) {
					if (!$handle = fopen($filename, 'a')) {
						 // echo "Cannot open file ($filename)";
						 echo "Error: Cannot open log - 404";
						 exit;
					}
					// write
					if (fwrite($handle, $data) === FALSE) {
						// echo "Cannot write to file ($filename)";
						echo "Error: Cannot write to log - 403";
						exit;
					}
					// echo "Success, wrote ($string) to file ($filename)";
					fclose($handle);
				} else {
					// echo "The file $filename is not writable";
					echo "Error: Log disabled - 403";
					exit;
				}
			}
		}
		function writeToNewFilePerDay($string, $url = "undefined URL"){
			$filePrefix = date("Ymd");
			$this->prefix = $filePrefix;

			$fileName = $this->outputDir.$this->prefix."-".$this->outputFile;
			if(!is_writable($fileName)) { // if file does not exists create file
				$handle = fopen($fileName, "a");
				// chmod($fileName, 0640); // block public access to log
				chmod($fileName, 0200); // block public access to log
				if($handle){
					fclose($handle);
				} else {
					echo "Error: Log Generation Failed";
					exit;
				}
			}
			$this->writeToFile($string, $url);
		}

	}

	/* TEMPLATE CODE TO IMPLEMENT CLASS */
	/* $logWriter = new LogWriter();
	$logWriter->printDiskDetails(); // TEST
	$logWriter->setFileDirectory("../extra/log/flight/");
	if(isset($_SESSION['log_user']) && !empty($_SESSION['log_user']) && $_SESSION['log_user']=="dev0"){ // dev0
		$logWriter->setFileName('flight-dev0.txt');
	} else if(isset($_SESSION['log_user']) && !empty($_SESSION['log_user']) && $_SESSION['log_user']=="dev1"){ // dev1
		$logWriter->setFileName('flight-dev1.txt');
	} else if(isset($_SESSION['log_user']) && !empty($_SESSION['log_user']) && $_SESSION['log_user']=="design0"){ // design0
		$logWriter->setFileName('flight-design0.txt');
	} else {
		// $logWriter->setFileName('flight.txt');
		$logWriter->setFileName('flight-design0.txt');
	}
	$logWriter->writeToNewFilePerDay("{Hello:World}", "www.google.com"); */
	/* TEMPLATE CODE TO IMPLEMENT CLASS */
?>