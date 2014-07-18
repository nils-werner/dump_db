<?php

	Class extension_dump_db extends Extension{

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
			$this->path = General::Sanitize(Symphony::Configuration()->get('path', 'dump_db'));
			$this->format = General::Sanitize(Symphony::Configuration()->get('format', 'dump_db'));
			
			if($this->format == "")
				$this->format = '%1$s.sql';
			
			if($this->path == "")
				$this->path = "/workspace";
		}
		
		public function install(){
			return true;
		}
		
		public function uninstall(){
				Symphony::Configuration()->remove('dump_db');            
				Administration::instance()->saveConfig();
		}
		
		public function initaliseAdminPageHead($context) {
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/dump_db/assets/dump_db.preferences.js', 3134);
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
			$downloadMode = $this->__downloadMode();
			$filesWriteable = $this->__filesWriteable();
			
		    if (count($filesWriteable) < 2 && !$downloadMode && !$this->__filesNewer()) {
		        Administration::instance()->Page->pageAlert(__('At least one of the database-dump files is not writeable. You will not be able to save your database.'), Alert::ERROR);
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
			
			$disabled = (count($filesWriteable) < 2 && !$downloadMode ? array('disabled' => 'disabled') : array());
			
			$span = new XMLElement('span', NULL, array('class' => 'frame'));
			$span->appendChild(new XMLElement('button', __('Save Authors'), array_merge(array('name' => 'action[dump][authors]', 'type' => 'submit'), $disabled)));
			$span->appendChild(new XMLElement('button', __('Save Data'), array_merge(array('name' => 'action[dump][data]', 'type' => 'submit'), $disabled)));
			$div->appendChild($span);
			
			
			if($downloadMode)
				$div->appendChild(new XMLElement('p', __('Dumping is set to <code>%s</code>. Your dump will be downloaded and won\'t touch local dumps on the server.',array(Symphony::Configuration()->get('dump', 'dump_db'))), array('class' => 'help')));
			
			$disabled = (Symphony::Configuration()->get('restore', 'dump_db') === 'yes' ? array() : array('disabled' => 'disabled'));
			
			$span = new XMLElement('span', NULL, array('class' => 'frame'));
			$span->appendChild(new XMLElement('button', __('Restore Authors'), array_merge(array('name' => 'action[restore][authors]', 'type' => 'submit'), $disabled)));
			$span->appendChild(new XMLElement('button', __('Restore Data'), array_merge(array('name' => 'action[restore][data]', 'type' => 'submit'), $disabled)));
			$div->appendChild($span);
			
			if(Symphony::Configuration()->get('restore', 'dump_db') !== 'yes') {
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
				$return = NULL;
			
			return $return;
		}
		
		private function __downloadMode() {
			return in_array(Symphony::Configuration()->get('dump', 'dump_db'), array('text','download'));
		}
		
		private function __filesNewer() {	
			$return = array();
					
			$last_sync = strtotime(Symphony::Configuration()->get('last_sync', 'dump_db'));
			
			if($last_sync === FALSE)
				return FALSE;
			
			foreach(array("data", "authors") AS $mode) {
				$filename = DOCROOT . $this->path . '/' . $this->generateFilename($mode);
				
				if(file_exists($filename) && $last_sync < filemtime($filename)) { // file exists and is newer than $last_sync
					$return[] = $mode;
				}
			}
				
			if($return == array())
				$return = NULL;

			return $return;
		}
		
		private function __restore($context){
			if(Symphony::Configuration()->get('restore', 'dump_db') !== 'yes')  // make sure the user knows what he's doing
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
				Symphony::Configuration()->set('last_sync', date('c') ,'dump_db');
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
				$table = str_replace(Symphony::Configuration()->get('tbl_prefix', 'database'), 'tbl_', $table);
				
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
						case 'tbl_search_index':
						case 'tbl_search_index_entry_keywords':
						case 'tbl_search_index_keywords':
						case 'tbl_search_index_logs':
							$sql_data .= $dump->export($table, MySQLDump::STRUCTURE_ONLY);
							break;
						default:
							$sql_data .= $dump->export($table, MySQLDump::ALL);
					}
				}
				
			}
			
			if(Symphony::Configuration()->get('dump', 'dump_db') === 'download') {
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

				header("Content-Type: application/octet-stream");
				header("Content-Transfer-Encoding: binary");
				header("Content-Disposition: attachment; filename=" . $mode . ".sql");
				echo $sql_data;
				die();
			}
			elseif(Symphony::Configuration()->get('dump', 'dump_db') === 'text') {
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

				header("Content-Type: text/plain; charset=UTF-8");
				echo $sql_data;
				die();
			}
			else {
				if(FALSE !== @file_put_contents(DOCROOT . $this->path . '/' . $filename, $sql_data)) {
					Administration::instance()->Page->pageAlert(__('%s successfully dumped into <code>%s/%s</code>.',array(__(ucfirst($mode)),$this->path,$filename)), Alert::SUCCESS);
					Symphony::Configuration()->set('last_sync', date('c') ,'dump_db');
					Symphony::Configuration()->write();
				}
				else {
					Administration::instance()->Page->pageAlert(__('An error occurred while trying to write <code>%s/%s</code>.',array($this->path,$filename)), Alert::ERROR);
				}
			}
			
		}
		
		private function generateFilename($mode) {
			return sprintf($this->format, $mode);
		}
	}
