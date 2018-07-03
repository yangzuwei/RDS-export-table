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

//$mysqli->begin_transaction();
$mysqli->query('truncate `' . $table . '`');
$first = json_decode(fgets($fd), true);
$fieldsArray = array_keys($first);

//trans the data type
$typesSql = 'show COLUMNS from `' . $table . '`';
$typeStmt = $mysqli->query($typesSql);
$fieldType = [];
$placeHolder = [];
$bindTypes = $paramType = '';


while ($types = $typeStmt->fetch_object()) {
    $fields[] = $types->Field;
    $placeHolder[] = '?';
    $fieldType[$types->Field] = $types->Type;

    if (strpos($types->Type, 'int')) {
        $paramType = 'i';
    } elseif (strpos($types->Type, 'double') || strpos($types->Type, 'decimal') || strpos($types->Type, 'float')) {
        $paramType = 'd';
    } else {
        $paramType = 's';
    }

    $bindTypes .= $paramType;

    if ($types->Null == 'YES') {
        $fieldDefault[$types->Field] = $types->Default;
    }
}

$fields = implode(',', $fields);
$placeHolder = "(" . implode(",", $placeHolder) . ")";
//var_dump($bindTypes);exit();
rewind($fd);
$i = 0;
echo "start ... \n";
$time = time();
while (!feof($fd)) {
    if ($content = fgets($fd)) {
        $content = json_decode($content, true);
        $content = transDataType($fieldType, $content, $fieldDefault);
        $insertContent = implode(',',$content);
        //var_dump($content);exit();
        $insertSql = 'insert into `' . $table . '` (' . $fields . ') values(' . $insertContent.')';
        if($mysqli->query($insertSql) === false){
            echo $insertSql."\n";
        }
    }
}

echo "done " . (time() - $time) . " \n";
fclose($fd);
$mysqli->close();


function transDataType($fieldType, $data, $fieldDefault)
{
    $shouldTransToNumber = ['int', 'double', 'decimal'];
    foreach ($data as $key => $value) {
        $condition = $tmpCondition = false;
        foreach ($shouldTransToNumber as $dataBaseType) {
            $tmpCondition = strstr($fieldType[$key], $dataBaseType);
            //var_dump($fieldType[$key], $dataBaseType,$tmpCondition);
            $condition = $condition || $tmpCondition;
        }

        if ($condition) {
            $data[$key] = $value * 1;
        } elseif ($value == '') {
            $data[$key] = $fieldDefault[$key] === NULL ? 'null' : $fieldDefault[$key];
        } else {
            $data[$key] = "'" . str_replace("'",'"',$value) . "'";
        }
    }

    return $data;

}

function refValues($arr)
{
    if (strnatcmp(phpversion(), '5.3') >= 0) //Reference is required for PHP 5.3+
    {
        $refs = array();
        foreach ($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}