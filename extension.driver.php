<?php

	Class extension_dump_db extends Extension{

		public function about(){
			return array('name' => 'Dump DB',
						 'version' => '1.01',
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
			
			$rows = Symphony::Database()->fetch("SHOW TABLES LIKE 'tbl_%';");
			
			$rows = array_map (create_function ('$x', 'return array_values ($x);'), $rows);
			$tables = array_map (create_function ('$x', 'return $x[0];'), $rows);
			
			foreach ($tables as $table){
				$table = str_replace(Administration::instance()->Configuration->get('tbl_prefix', 'database'), 'tbl_', $table);
				
				if($table == "tbl_cache" || $table == "tbl_sessions") { ## Grab the structure for cache and sessions
					$sql_data .= $dump->export($table, MySQLDump::STRUCTURE_ONLY);
				}
				else { ## Grab the data for everything else
					$sql_data .= $dump->export($table, MySQLDump::ALL);
				}
			}
			
		    if(FALSE !== file_put_contents(DOCROOT . '/workspace/dump.sql', $sql_data)) {
				Administration::instance()->Page->pageAlert(__('Database successfully dumped into <code>/workspace/dump.sql</code>.'), Alert::SUCCESS);
			}
			else {
				Administration::instance()->Page->pageAlert(__('An error occurred while trying to write <code>/workspace/dump.sql</code>.'), Alert::ERROR);
			}
			
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

			$div->appendChild(new XMLElement('p', __('Packages entire database into <code>/workspace/dump.sql</code>.'), array('class' => 'help')));	

			$group->appendChild($div);						
			$context['wrapper']->appendChild($group);
						
		}
	}