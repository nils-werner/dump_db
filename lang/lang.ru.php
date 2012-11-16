<?php

	$about = array(
		'name' => 'Русский',
		'author' => array(
			'name' => 'Александр Бирюков',
			'email' => 'info@alexbirukov.ru',
			'website' => 'http://alexbirukov.ru'
		),
		'release-date' => '2012-08-06'
	);

	/**
	 * Dump DB
	 */
	$dictionary = array(

		' and ' => 
		' и ',

		'The database file for your %s is newer than your last sync. ' => 
		'Вы никогда не создавали файл данных для %s. ',

		'The database files for both your %s is newer than your last sync. ' => 
		'Вы никогда не создавали файл данных для %s. ',

		'It\'s recommended to <a href="%s">sync your database now.</a>' => 
		'Рекомендуется создать <a href="%s">резервную копию БД</a> прямо сейчас.',

		'At least one of the database-dump files is not writeable. You will not be able to save your database.' => 
		'Один из файлов данных не может быть перезаписан. Сохранение данных невозможно.',

		'Dump Database' => 
		'Дамп БД',

		'Save Authors' => 
		'Сохранить авторов',

		'Save Data' => 
		'Сохранить данные',

		'Dumping is set to <code>%s</code>. Your dump will be downloaded and won\'t touch local dumps on the server.' => 
		'Сохранение данных установлено в значение <code>%s</code>. Вам будет предложено скачать дамп данных.',

		'Restore Authors' => 
		'Восстановить авторов',

		'Restore Data' => 
		'Восстановить данные',

		'Restoring needs to be enabled in <code>/manifest/config.php</code>.' => 
		'Восстановление необходимо включить в файле <code>/manifest/config.php</code>.',

		'%s successfully restored from <code>%s/%s</code> in %d queries.' => 
		'%s успешно восстановлены из <code>%s/%s</code> за %d запросов.',

		'An error occurred while trying to import from <code>%s/%s</code>.' => 
		'Произошла ошибка импорта данных из <code>%s/%s</code>.',

		'%s successfully dumped into <code>%s/%s</code>.' => 
		'%s успешно сохранены в <code>%s/%s</code>.',

		'An error occurred while trying to write <code>%s/%s</code>.' => 
		'Произошла ошибка записи <code>%s/%s</code>.',

		'Do you really want to overwrite your database with the contents from the file?' => 
		'Вы действительно хотите перезаписать данные в БД содержимым из файла?',

	);
