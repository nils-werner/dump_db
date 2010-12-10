<?php

	Class extension_dump_db extends Extension{

		public function about(){
			return array('name' => 'Dump DB',
						 'version' => '1.07',
						 'release-date' => '2010-12-10',
						 'author' => array('name' => 'Nils Werner',
										   'website' => 'http://www.phoque.de',
										   'email' => 'nils.werner@gmail.com')
				 		);
		}
		
		public function getSubscribedDelegates(){
			return array(
						array(
							'page' => '/system/preferences/',
							'delegate' => 'AddCustomPreferenceFieldsets',
							'callback' => 'appendPreferences'
						),
						array(
							'page'		=> '/backend/',
							'delegate'	=> 'InitaliseAdminPageHead',
							'callback'	=> 'initaliseAdminPageHead'
						),
						array(
							'page' => '/backend/',
							'delegate' => 'AppendPageAlert',
							'callback' => 'appendAlert'
						),
					);
		}
		
		public function __construct() {
			$this->path = General::Sanitize(Administration::instance()->Configuration->get('path', 'dump_db'));
			$this->hash = General::Sanitize(Administration::instance()->Configuration->get('hash', 'dump_db'));
			$this->format = General::Sanitize(Administration::instance()->Configuration->get('format', 'dump_db'));
			
			if($this->format == "")
				$this->format = '%1$s-%2$s.sql';
			
			if($this->path == "")
				$this->path = "/workspace";
			
			if($this->hash == "") {
				$this->hash = md5(microtime());
				Administration::instance()->Configuration->set('hash', $this->hash ,'dump_db');
				Administration::instance()->saveConfig();
			}
			
		}
		
		public function install(){
			return true;
		}
		
		public function uninstall(){
				Administration::instance()->Configuration->remove('dump_db');            
				Administration::instance()->saveConfig();
		}
		
		public function initaliseAdminPageHead($context) {
			$page = $context['parent']->Page;
			
			$page->addScriptToHead(URL . '/extensions/dump_db/assets/script.js', 3134);
		}
		
		public function appendAlert($context){
			
			if(!is_null($context['alert'])) return;
			
		    if ($this->__filesNewer()) {
				$files = implode(__(" and "), array_map('__',array_map('ucfirst',$this->__filesNewer())));
			
		        if(count($this->__filesNewer()) == 1)
		        	$message = __('The database file for your %s is newer than your last sync. ',array($files));
		        else
		        	$message = __('The database files for both your %s is newer than your last sync. ',array($files));
		        	
		        	
		        $message .= __('It\'s recommended to <a href="%s">sync your database now.</a>', array(URL . '/symphony/system/preferences/#dump-actions'));
		        
       			Administration::instance()->Page->pageAlert($message);
		    }
		    
		    

		}
		
		public function appendPreferences($context){		
			$filesWriteable = $this->__filesWriteable();
			
		    if (!$filesWriteable) {
		        Administration::instance()->Page->pageAlert(__('One of the database-dump files is not writeable. You will not be able to save your database.'), AdministrationPage::PAGE_ALERT_ERROR);
		    }
			
			if(isset($_POST['action']['dump'])){
				$this->__dump($context);
			}
			
			if(isset($_POST['action']['restore'])){
				$this->__restore($context);
			}
			
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Dump Database')));			
			

			$div = new XMLElement('div', NULL, array('id' => 'dump-actions', 'class' => 'label'));	
			
			$disabled = ($filesWriteable ? array() : array('disabled' => 'disabled'));
			
			$span = new XMLElement('span');
			$span->appendChild(new XMLElement('button', __('Save Authors'), array_merge(array('name' => 'action[dump][authors]', 'type' => 'submit'), $disabled)));
			$span->appendChild(new XMLElement('button', __('Save Data'), array_merge(array('name' => 'action[dump][data]', 'type' => 'submit'), $disabled)));
			$div->appendChild($span);
			
			$disabled = (Administration::instance()->Configuration->get('restore', 'dump_db') === 'yes' ? array() : array('disabled' => 'disabled'));
			
			$span = new XMLElement('span');
			$span->appendChild(new XMLElement('button', __('Restore Authors'), array_merge(array('name' => 'action[restore][authors]', 'type' => 'submit'), $disabled)));
			$span->appendChild(new XMLElement('button', __('Restore Data'), array_merge(array('name' => 'action[restore][data]', 'type' => 'submit'), $disabled)));
			$div->appendChild($span);
			
			if(Administration::instance()->Configuration->get('restore', 'dump_db') !== 'yes') {
				$div->appendChild(new XMLElement('p', __('Restoring needs to be enabled in <code>/manifest/config.php</code>.',array($this->path, $filename)), array('class' => 'help')));
			}

			$group->appendChild($div);						
			$context['wrapper']->appendChild($group);
						
		}
		
		private function __filesWriteable() {
			$return = array();
			
			foreach(array("data", "authors") AS $mode) {
				$filename = DOCROOT . $this->path . '/' . $this->generateFilename($mode);
				
				if(!file_exists($filename) || is_writable($filename)) { // file doesn't exist or is writeable
					$return[] = $mode;
				}
			}
			
			if($return == array())
				$return = FALSE;
			
			return $return;
		}
		
		private function __filesNewer() {	
			$return = array();
					
			$last_sync = strtotime(Administration::instance()->Configuration->get('last_sync', 'dump_db'));
			
			if($last_sync === FALSE)
				return FALSE;
			
			foreach(array("data", "authors") AS $mode) {
				$filename = DOCROOT . $this->path . '/' . $this->generateFilename($mode);
				
				if(file_exists($filename) && $last_sync < filemtime($filename)) { // file exists and is newer than $last_sync
					$return[] = $mode;
				}
			}
				
			if($return == array())
				$return = FALSE;

			return $return;
		}
		
		private function __restore($context){
			if(Administration::instance()->Configuration->get('restore', 'dump_db') !== 'yes')  // make sure the user knows what he's doing
				return;
			
			require_once(dirname(__FILE__) . '/lib/class.mysqlrestore.php');
			
			$restore = new MySQLRestore(Symphony::Database());
			
			$mode = NULL;
			$mode = (isset($_POST['action']['restore']['authors']))? 'authors' : 'data';
			if($mode == NULL) return;
			
			$filename = $this->generateFilename($mode);
			
			$return = $restore->import(file_get_contents(DOCROOT . $this->path . '/' . $filename));
			
			if(FALSE !== $return) {
				Administration::instance()->Page->pageAlert(__('%s successfully restored from <code>%s/%s</code> in %d queries.',array(__(ucfirst($mode)),$this->path,$filename,$return)), Alert::SUCCESS);
				Administration::instance()->Configuration->set('last_sync', date('c') ,'dump_db');
				Administration::instance()->saveConfig();
			}
			else {
				Administration::instance()->Page->pageAlert(__('An error occurred while trying to import from <code>%s/%s</code>.',array($this->path,$filename)), Alert::ERROR);
			}
		}
		
		private function __dump($context){
			$sql_schema = $sql_data = NULL;
			
			require_once(dirname(__FILE__) . '/lib/class.mysqldump.php');
			
			$dump = new MySQLDump(Symphony::Database());
			
			$rows = Symphony::Database()->fetch("SHOW TABLES LIKE 'tbl_%';");
			$rows = array_map (create_function ('$x', 'return array_values ($x);'), $rows);
			$tables = array_map (create_function ('$x', 'return $x[0];'), $rows);
			
			$mode = NULL;
			$mode = (isset($_POST['action']['dump']['authors']))? 'authors' : 'data';
			if($mode == NULL) return;
			
			$filename = $this->generateFilename($mode);
			
			foreach ($tables as $table){
				$table = str_replace(Administration::instance()->Configuration->get('tbl_prefix', 'database'), 'tbl_', $table);
				
				if($mode == 'authors') {
					switch($table) {
						case 'tbl_authors':
						case 'tbl_forgotpass':
							$sql_data .= $dump->export($table, MySQLDump::ALL);
							break;
						case 'tbl_sessions':
							$sql_data .= $dump->export($table, MySQLDump::STRUCTURE_ONLY);	
							break;
						default: // ignore everything but the authors
							break;
					}
				}
				elseif($mode == 'data') {
					switch($table) {
						case 'tbl_authors': // ignore authors
						case 'tbl_forgotpass':
						case 'tbl_sessions':
							break;
						case 'tbl_cache':
							$sql_data .= $dump->export($table, MySQLDump::STRUCTURE_ONLY);
							break;
						default:
							$sql_data .= $dump->export($table, MySQLDump::ALL);
					}
				}
				
			}
			
			if(FALSE !== @file_put_contents(DOCROOT . $this->path . '/' . $filename, $sql_data)) {
				Administration::instance()->Page->pageAlert(__('%s successfully dumped into <code>%s/%s</code>.',array(__(ucfirst($mode)),$this->path,$filename)), Alert::SUCCESS);
				Administration::instance()->Configuration->set('last_sync', date('c') ,'dump_db');
				Administration::instance()->saveConfig();
			}
			else {
				Administration::instance()->Page->pageAlert(__('An error occurred while trying to write <code>%s/%s</code>.',array($this->path,$filename)), Alert::ERROR);
			}
			
		}
		
		private function generateFilename($mode) {
			return sprintf($this->format, $mode, $this->hash,date("YmdHi"));
		}
	}
