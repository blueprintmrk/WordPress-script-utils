#!/usr/bin/php -q
<?php
/* Copy from wp-config.php */

/** The name of the database for WordPress */
define('DB_NAME', '');

/** MySQL database username */
define('DB_USER', '');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', '');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', '');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/* Done copying.
   YOU CAN STOP EDITIONG HERE.
   Although, if you feel brave, go on.
*/


mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die('Could not connect: '.mysql_error());
mysql_set_charset(DB_CHARSET) or die('Could not set charset: '.mysql_error());
mysql_select_db(DB_NAME) or die('Could not select database: '.mysql_error());

$db_collate = (!defined('DB_COLLATE') || '' == DB_COLLATE ? 'utf8_unicode_ci' : DB_COLLATE);

function mysql_get_results($query, $die_on_error = true) {
  $result = mysql_query($query);
  if ($result === FALSE) {
    echo "Error on query: $query\n";
    echo "\t".mysql_error();
    echo "\n";
    if ($die_on_error) exit(1);
  }
  if ($result === TRUE) return true;
  $_ret = array();
  while( $row = mysql_fetch_assoc($result) ) {
    $_ret[] = $row;
  }
  return $_ret;
}

$statements = array('ALTER DATABASE '.DB_NAME.' CHARACTER SET utf8 COLLATE '.$db_collate);
$statements[] = 'USE `'.DB_NAME.'`';

$tables = array();
$results = mysql_get_results('SHOW TABLE STATUS');
foreach ($results as $row) {
  $tables[] = $row['Name'];
  if (!preg_match('/utf8_/',$row['Collation'])) {
    $statements[] = 'ALTER TABLE `'.$row['Name'].'` DEFAULT CHARACTER SET utf8 COLLATE '.$db_collate;
  }
}

$_types = array(
  'VARCHAR' => 'VARBINARY',
  'LONGTEXT' => 'LONGBLOB',
  'TINYTEXT' => 'TINYBLOB',
  'CHAR' => 'BINARY',
  'TEXT' => 'BLOB',  
);

foreach ($tables as $table) {
  $columns = mysql_get_results('SHOW FULL COLUMNS FROM `'.$table.'`');
  $indexes = mysql_get_results('SHOW INDEX FROM `'.$table.'`');
  $fulltext = array();
  foreach ($indexes as $index) {
    if ($index['Index_type'] != 'FULLTEXT') continue;
    if (!isset($fulltext[$index['Key_name']])) $fulltext[$index['Key_name']] = array();
    $fulltext[$index['Key_name']][$index['Seq_in_index']] = $index['Column_name'];
  }

  $_fulltext = array();

  foreach ($columns as $column) {
    if (!preg_match('/utf8_/',$column['Collation'])) {
      foreach ($fulltext as $index_name => $index) {
        if (in_array($column['Field'],$index)) {
          $statements[] = "ALTER TABLE `$table` DROP INDEX `$index_name`";
          $_fulltext[] = "ALTER TABLE `$table` ADD FULLTEXT `$index_name` (".join(', ',array_map(create_function('$s','return "`$s`";'),$index)).")";
          unset($fulltext[$index_name]);
        }
      }
    }
  }

  foreach ($columns as $column) {
    if ($column['Collation'] == '') continue;
    if (!preg_match('/utf8_/',$column['Collation'])) {
      $c = '';
      $type = strtoupper($column['Type']);
      if (preg_match('/^(ENUM|SET)/',$type)) {
        $null = ($column['Null'] == 'NO' ? 'NOT NULL' : 'NULL');
        $default = ($column['Default'] ? 'DEFAULT \''.mysql_real_escape_string($column['Default']).'\'' : '');
        $statements[] = trim("ALTER TABLE `$table` CHANGE $column[Field] $column[Field] $type CHARACTER SET utf8 $null $default");
      } else {
        $btype = str_replace(array_keys($_types),$_types,$type);
        if ($type != $btype) {
          $statements[] = "ALTER TABLE `$table` CHANGE `$column[Field]` `$column[Field]` $btype";
          $statements[] = "ALTER TABLE `$table` CHANGE `$column[Field]` `$column[Field]` $type CHARACTER SET utf8 COLLATE $db_collate";
        } else {
          fprintf(STDERR,"WARNING: No binary equivalent for $type. Data scrambling is likely to occur.\n");
          $statements[] = "ALTER TABLE `$table` CHANGE `$column[Field]` `$column[Field]` $type CHARACTER SET utf8 COLLATE $db_collate";
        }

      }
    }
  }

  foreach ($_fulltext as $index) $statements[] = $index;  
}

echo join(";\n",$statements);
echo ";\n";
