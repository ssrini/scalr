<?php
	$args = getopt('u:p:h:d:s:');
	if (!($args['u'] && $args['d'])) {
		// Print usage message
		print <<<T
Usage: php -q shitgen.php OPTION
  -u     mysql user*
  -p     mysql password
  -h     mysql host
  -d     mysql database*
  -s     size of database ex: 5Gb, 500Mb. Default 1Gb

T;
		die();
	}

	if (!$args['h']) {
		$args['h'] = 'localhost';
	}
	if ($args['s']) {
		$unit = strtolower(substr($args['s'], -2));
		$size = (int)$args['s'];
		if ($unit == 'mb') {
			$size = $size * 1024 * 1024;
		} else if ($unit == 'gb') {
			$size = $size * 1024 * 1024 * 1024;
		} else {
			die(sprintf("Invalid -s option. %s\n", $args['s']));
		}
	} else {
		$size = 1024 * 1024 * 1024; // 1Gb
	}

	$max_table_size = 1024 * 1024 * 1024; // 512Mb
	$min_blob_size = 1024 * 1024; // 1MB
	$max_blob_size = 1024 * 1024 * 10; // 10Mb

	print "ShitGen v0.2\n";
	print "Size: " . number_format($size/1024, 2) . "Kb.\n";
	print "Blob min: " . number_format($min_blob_size/1024, 2) . "Kb, max: " .  number_format($max_blob_size/1024, 2) . "Kb.\n";
	print "Max table size: " . number_format($max_table_size/1024, 2) . "Kb.\n";
	print "===============\n\n";

	mysql_connect($args['h'], $args['u'], $args['p']) or die(mysql_error() . "\n");
	mysql_select_db($args['d']) or die(mysql_error() . "\n");
	
	$sql_clean = "DROP TABLE IF EXISTS `{table}`";
	$sql_create = "CREATE TABLE `{table}` ( `id` INTEGER  NOT NULL AUTO_INCREMENT, `trash` MEDIUMBLOB  NOT NULL, PRIMARY KEY (`id`)) ENGINE = MyISAM";
	$sql_ins = "INSERT INTO `{table}` SET trash = '{blob}'";

	$table_num = 0;
	$new_table = true;
	$table_size = 0;
	
	$blob_size_total = 0;

	$shit_bottle = @fopen("/dev/random", "r");
	
	while ($blob_size_total < $size) {
		if ($new_table) {
			$table_num++;
			$table = "table{$table_num}";
			mysql_query(str_replace("{table}", $table, $sql_clean)) or die (mysql_error() . "\n");
			mysql_query(str_replace("{table}", $table, $sql_create)) or die (mysql_error() . "\n");
			$new_table = false;
			$table_size = 0;
			print "== Created new table {$table}\n";
		}

		$blob_size = mt_rand($min_blob_size, $max_blob_size);
		
		$blob = fread($shit_bottle, $blob_size);
		
		$blob_size_total += $blob_size;
		$table_size += $blob_size;
		if ($table_size >= $max_table_size) {
			// New table in next iteration
			$new_table = true;
		}

		// Insert generated data
		print "Generated " . number_format($blob_size/1024, 2) . "Kb blob.\t Total: " . number_format($blob_size_total/1024, 2) . "Kb\n";
		mysql_query(str_replace(
			array("{table}", "{blob}"), 
			array($table, addslashes($blob)), 
			$sql_ins
		)) or die(mysql_error() . "\n");
	}
	
	@fclose($shit_bottle);
?>
