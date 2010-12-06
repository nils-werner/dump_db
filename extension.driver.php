<?php

	Class extension_dump_db extends Extension{

		public function about(){
			return array('name' => 'Dump DB',
						 'version' => '1.05',
						 'release-date' => '2010-09-23',
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
						)
					);
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
		
		public function appendPreferences($context){
			list($hash, $path, $format) = $this->getConfig();
			
			if($hash == "")
				$hash = "<em>" . __("random-hash") . "</em>";
			
			$filename = $this->generateFilename($hash, $format, "<em>data|authors</em>");
			
			if(isset($_POST['action']['dump'])){
				$this->__dump($context);
			}
			
			if(isset($_POST['action']['restore'])){
				$this->__restore($context);
			}
			
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Dump Database')));			
			

			$div = new XMLElement('div', NULL, array('id' => 'file-actions', 'class' => 'label'));
			
			$div->appendChild(new XMLElement('p', __('After checking this box, all authordata will be overwritten if the dump is being restored.'), array('class' => 'help')));	
			
			$span = new XMLElement('span');
			$span->appendChild(new XMLElement('button', __('Save Authors'), array('name' => 'action[dump][authors]', 'type' => 'submit')));
			$span->appendChild(new XMLElement('button', __('Save Data'), array('name' => 'action[dump][data]', 'type' => 'submit')));
			$div->appendChild($span);
			
			if(Administration::instance()->Configuration->get('restore', 'dump_db') === 'yes') {
				$span = new XMLElement('span');
				$span->appendChild(new XMLElement('button', __('Restore Authors'), array('name' => 'action[restore][authors]', 'type' => 'submit')));
				$span->appendChild(new XMLElement('button', __('Restore Data'), array('name' => 'action[restore][data]', 'type' => 'submit')));
				$div->appendChild($span);
			}
			

			$div->appendChild(new XMLElement('p', __('Packages and restores your data into and from <code>%s/%s</code>.',array($path, $filename)), array('class' => 'help')));	

			$group->appendChild($div);						
			$context['wrapper']->appendChild($group);
						
		}
		
		private function __restore($context){
			if(Administration::instance()->Configuration->get('restore', 'dump_db') !== 'yes')  // make sure the user knows what he's doing
				return;
			
			list($hash, $path, $format) = $this->getConfig();
			$filename = $this->generateFilename($hash, $format);
			
			require_once(dirname(__FILE__) . '/lib/class.mysqlrestore.php');
			
			$restore = new MySQLRestore(Symphony::Database());
			
			$return = $restore->import(file_get_contents(DOCROOT . $path . '/' . $filename));
			
		    if(FALSE !== $return) {
				Administration::instance()->Page->pageAlert(__('Database successfully restored from <code>%s/%s</code> in %d queries.',array($path,$filename,$return)), Alert::SUCCESS);
			}
			else {
				Administration::instance()->Page->pageAlert(__('An error occurred while trying to import from <code>%s/%s</code>.',array($path,$filename)), Alert::ERROR);
			}
		}
		
		private function __dump($context){
			$sql_schema = $sql_data = NULL;
			
			list($hash, $path, $format) = $this->getConfig();
			
			require_once(dirname(__FILE__) . '/lib/class.mysqldump.php');
			
			$dump = new MySQLDump(Symphony::Database());
			
			$rows = Symphony::Database()->fetch("SHOW TABLES LIKE 'tbl_%';");
			$rows = array_map (create_function ('$x', 'return array_values ($x);'), $rows);
			$tables = array_map (create_function ('$x', 'return $x[0];'), $rows);
			
			$mode = NULL;
			$mode = (isset($_POST['action']['dump']['authors']))? 'authors' : 'data';
			
			$filename = $this->generateFilename($hash, $format, $mode);
			
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
			
			if(FALSE !== file_put_contents(DOCROOT . $path . '/' . $filename, $sql_data)) {
				Administration::instance()->Page->pageAlert(__('Database successfully dumped into <code>%s/%s</code>.',array($path,$filename)), Alert::SUCCESS);
			}
			else {
				Administration::instance()->Page->pageAlert(__('An error occurred while trying to write <code>%s/%s</code>.',array($path,$filename)), Alert::ERROR);
			}
			
		}
		
		private function getConfig() {
			$path = General::Sanitize(Administration::instance()->Configuration->get('path', 'dump_db'));
			$hash = General::Sanitize(Administration::instance()->Configuration->get('hash', 'dump_db'));
			$format = General::Sanitize(Administration::instance()->Configuration->get('format', 'dump_db'));
			
			if($format == "")
				$format = '%1$s-%2$s.sql';
			
			if($path == "")
				$path = "/workspace";
			
			if($hash == "") {
				$hash = md5(microtime());
				Administration::instance()->Configuration->set('hash', $hash ,'dump_db');
				Administration::instance()->saveConfig();
			}
			
			return array($hash, $path, $format);
		}
		
		private function generateFilename($hash, $format, $mode) {
			return sprintf($format,$mode,$hash,date("YmdHi"));
		}
	}
