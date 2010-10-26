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
			
			$filename = $this->generateFilename($hash, $format);
			
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
			
			$label = new XMLElement('label', NULL);
			$checkbox = new XMLElement('input', NULL, array('name' => 'settings[dump_db][users]', 'type' => 'checkbox', 'value' => 'yes', 'checked' => 'checked'));
			$label->setValue($checkbox->generate() . ' ' .__('Save author information'));
			$div->appendChild($label);
			
			$div->appendChild(new XMLElement('p', __('Unchecking this box will prevent your dump from dumping any author data.'), array('class' => 'help')));	
			
			$span = new XMLElement('span');
			$span->appendChild(new XMLElement('button', __('Dump'), array('name' => 'action[dump]', 'type' => 'submit')));
			if(Administration::instance()->Configuration->get('restore', 'dump_db') === 'yes') {
				$span->appendChild(new XMLElement('button', __('Restore'), array('name' => 'action[restore]', 'type' => 'submit')));
			}
			
			$div->appendChild($span);

			$div->appendChild(new XMLElement('p', __('Packages and restores your database into and from <code>%s/%s</code>.',array($path, $filename)), array('class' => 'help')));	

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
			$filename = $this->generateFilename($hash, $format);
			
			require_once(dirname(__FILE__) . '/lib/class.mysqldump.php');
			
			$dump = new MySQLDump(Symphony::Database());
			
			$rows = Symphony::Database()->fetch("SHOW TABLES LIKE 'tbl_%';");
			
			$rows = array_map (create_function ('$x', 'return array_values ($x);'), $rows);
			$tables = array_map (create_function ('$x', 'return $x[0];'), $rows);
			
			$users = ($_POST['settings']['dump_db']['users'] == 'yes')? true : false;
			
			foreach ($tables as $table){
				$table = str_replace(Administration::instance()->Configuration->get('tbl_prefix', 'database'), 'tbl_', $table);
				
				if(!$users && ($table == "tbl_authors" || $table == "tbl_forgotpass")) {
					continue;
				}
				
				if($table == "tbl_cache" || $table == "tbl_sessions") { ## Grab the structure for cache and sessions
					$sql_data .= $dump->export($table, MySQLDump::STRUCTURE_ONLY);
				}
				else { ## Grab the data for everything else
					$sql_data .= $dump->export($table, MySQLDump::ALL);
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
				$format = 'dump-%1$s.sql';
			
			if($path == "")
				$path = "/workspace";
			
			if($hash == "") {
				$hash = md5(microtime());
				Administration::instance()->Configuration->set('hash', $hash ,'dump_db');
				Administration::instance()->saveConfig();
			}
			
			return array($hash, $path, $format);
		}
		
		private function generateFilename($hash, $format) {
			return sprintf($format,$hash,date("YmdHi"));
		}
	}