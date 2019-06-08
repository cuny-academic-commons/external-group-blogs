<?php
/**
 * Class bp-geb-debug is a debugging module. 
 * To activate, define('_BP_GEB_DEBUG_ON_', TRUE); before the class is loaded.
 *
 * @author lordmatt
 */
class bp_geb_debug {
		protected $debug=array();
		protected $debugfile = '';		
		
		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->debugfile = _BP_GEB_FILE_;// set this be defining it before the plugin is loaded
			$this->debug('['.gmdate( "Y-m-d H:i:s" ).'] Constructed bp_geb_debug');
		}
		
		public function __destruct() {
			if(!_BP_GEB_DEBUG_ON_){
				return;
			}
			$this->log_all();
		}
		/**
		 * Forces the class to log to alt file.
		 * @param string $file
		 */
		public function force_alt_log($file){
			$this->debugfile=$file;
		}
		/**
		 * 
		 * @param string $message
		 * @param mixed $var
		 */
		public function debug($message,$var=''){			
			if(_BP_GEB_DEBUG_ON_){
				$this->debug[]=array($message,$var);
				if(count($this->debug)>9){
					$this->log();
				}
			}
		}
		
		/**
		 * Flush to log file
		 */
		public function log_all(){
			if(_BP_GEB_DEBUG_ON_){
				$this->log();
			}
			return;
		}
		
		protected function log(){
			if(is_writable ($this->debugfile)){
				$my_file = 'file.txt';
				@$handle = fopen($this->debugfile, 'a');
				if($handle===FALSE){
					return;
				}
				foreach($this->debug as $k=>$log){
					$data = "[{$k}] ".$log[0] . ' : ' . print_r($log[1],TRUE) . "\n";
					fwrite($handle, $data);
				}
				$this->debug=array();
				fclose($handle);
			}
		}
}

$bp_geb_debug = new bp_geb_debug();
$bp_geb_debug->debug('Logging started: '.time(),'',TRUE);
# ALT: error_log("Starting\n", 3, _BP_GEB_FILE_);