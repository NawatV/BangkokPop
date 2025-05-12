<html>
    <head>
        <meta charset="UTF-8">
        <title> Update BDay for SimPhitlok </title>
    </head>
    <body>

        <?php
//-------------- Initiation (same)-----------------------------------------------------
        /** PHPExcel */
        require_once 'Classes/PHPExcel.php';

        /** PHPExcel_IOFactory - Reader */
        include 'Classes/PHPExcel/IOFactory.php';


        $inputFileName = "6_birthday.xlsx";
        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($inputFileName);

        $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);  //select sheet
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();

        $headingsArray = $objWorksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true, true);
        $headingsArray = $headingsArray[1];

        $r = -1;
        $namedDataArray = array();
        for ($row = 2; $row <= $highestRow; ++$row) {
            $dataRow = $objWorksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, true, true);
            if ((isset($dataRow[$row]['A'])) && ($dataRow[$row]['A'] > '')) {
                ++$r;
                foreach ($headingsArray as $columnKey => $columnHeading) {
                    $namedDataArray[$r][$columnHeading] = $dataRow[$row][$columnKey];
                }
            }
        }
        ?>

        <?php
//----------------- Add BDay, BYear (similar)-------------------------------------------------------
        $time_start = microtime(true);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        //*** Connect to MySQL Database ***//
        $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้");//changed
        $objDB = mysql_select_db("SimPhit") or die("เลือกฐานข้อมูลไม่ได้");
        $table = 'People';
        mysql_query("SET NAMES UTF8");
        mysql_query("SET character_set_results=UTF8");
        mysql_query("SET character_set_client=UTF8");
        mysql_query("SET character_set_connection=UTF8");

        $i = 0;
        $age = array();
        $year = array();
        $day = array();

        foreach ($namedDataArray as $result) {     //เอาค่าจาก excel มาใส่ตัวแปร
            $age[$i][0] = $result["age"];
            $year[$i][1] = $result["year"];
            $day[$i][2] = $result["day"];
            $i++;
        }

//    for( $i= 0;$i<=101;$i++){
//            echo $age[$i][0]. " " .$year[$i][1]. " " .$day[$i][2]. "<br>";
//    }
//$ran = 0;

//------------- Update (similar) ---------------------------------------      
        for ($i = 0; $i <= 101; $i++) {
            $addyear = "UPDATE `" . $table . "` SET `BYear` = '" . $year[$i][1] . "' WHERE `Age` = '" . $age[$i][0] . "'";
            mysql_query($addyear) or die(mysql_error());
            //echo  $i. "update year successful <br>";

            $addday = "UPDATE `" . $table . "` SET `BDay` =(RAND()* '" . $day[$i][2] . "'-1)+1 WHERE `BYear` = '" . $year[$i][1] . "'";
            mysql_query($addday) or die(mysql_error());
        }
        echo "update year successful <br>";

        mysql_close($objConnect);

        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $hours = (int) ($time / 60 / 60);
        $minutes = (int) ($time / 60) - $hours * 60;
        $seconds = (int) $time - $hours * 60 * 60 - $minutes * 60;
        echo "Process Time: $hours hours/ $minutes minutes/ $seconds seconds. <br>";
        ?>
    </body>
</html>
