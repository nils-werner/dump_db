<?php

	Class extension_dump_db extends Extension{

		public function about(){
			return array('name' => 'Dump DB',
						 'version' => '1.00',
						 'release-date' => '2010-03-13',
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

					);
		}
		
		public function install(){
			return true;
		}
		
		
		private function __dump(){
			$sql_schema = $sql_data = NULL;
			
			require_once(dirname(__FILE__) . '/lib/class.mysqldump.php');
			
			$dump = new MySQLDump(Symphony::Database());
			
			## Grab the data
			$sql_data  = $dump->export('tbl_%', MySQLDump::ALL);
			
			$sql_data = str_replace('`' . Administration::instance()->Configuration->get('tbl_prefix', 'database'), '`tbl_', $sql_data);
			
			header('Content-type: text/plain');	
			header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header("Content-Disposition: Attachment; filename=install.sql");
			
		    echo $sql_data;
			exit();
			
		}

		public function __SavePreferences($context){
			$this->__dump();
		}
		
		public function appendPreferences($context){
			
			if(isset($_POST['action']['dump'])){
				$this->__SavePreferences($context);
			}
			
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Dump Database')));			
			

			$div = new XMLElement('div', NULL, array('id' => 'file-actions', 'class' => 'label'));			
			$span = new XMLElement('span');
			
			$span->appendChild(new XMLElement('button', __('Dump'), array('name' => 'action[dump]', 'type' => 'submit')));	
			
			$div->appendChild($span);

			$div->appendChild(new XMLElement('p', __('Packages entire database into a <code>.sql</code> file for download.'), array('class' => 'help')));	

			$group->appendChild($div);						
			$context['wrapper']->appendChild($group);
						
		}
	}