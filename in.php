<?php

include './config/database.php';

$mysqli = new mysqli($DB1['hostname'], $DB1["username"], $DB1["password"], $DB1["database"]);
$mysqli->set_charset("utf8");

if ($mysqli->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}

//read data
$file = $argv[1];
$table = $argv[2];
$insertData = [];

$fd = fopen($file, 'r');

$mysqli->begin_transaction();
$mysqli->query('truncate `' . $table . '`');
$first = json_decode(fgets($fd), true);;
$fields = implode(',', array_keys($first));

rewind($fd);

while (!feof($fd)) {
    if ($content = fgets($fd)) {
        $insertData[] = "('" . implode("','", json_decode($content, true)) . "')";
    }
    if (count($insertData) >= 10000) {
        $datas = implode(',', $insertData);
        $mysqli->query('insert into `' . $table . '`(' . $fields . ') values' . $datas);
        unset($insertData);
    }
}

if (!empty($insertData)) {
    $datas = implode(',', $insertData);
    $insertSql = 'insert into `' . $table . '` (' . $fields . ') values' . $datas;
    $mysqli->query($insertSql);
}

$mysqli->commit();
echo "done \n";
fclose($fd);
$mysqli->close();
