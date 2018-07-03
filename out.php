<?php
include './config/database.php';
echo "\n";
$offset = 10000;
$mysqli2 = new mysqli($DB2['hostname'], $DB2["username"], $DB2["password"], $DB2["database"]);
$mysqli2->set_charset("utf8");

$table = $argv[1];

$count = $mysqli2->query('select count(1) as num from `'.$table.'`');
$num = 1*$count->fetch_object()->num;

echo $table." total item is ".$num;
echo "\n";
$fd = fopen($table."_pro".time().".json","w+");
for($i=0;$i<$num;$i += $offset){
    $result2 = $mysqli2->query('select * from `'.$table.'` limit '.$i.','.$offset);
    while($row = $result2->fetch_array(MYSQLI_ASSOC)){
       fwrite($fd,json_encode($row)."\n");
    }
    #echo 'tmp res size is '.$result2->num_rows.' i is '.$i;
    printf("progress:[%-50s] %d%% Done \r",str_repeat("=",$i/$num*50),$i/$num*100);
    echo "\n";
    $result2->free();
    sleep(1);
}
fclose($fd);
echo "json error is ".json_last_error();
echo "\n";
$mysqli2->close();
