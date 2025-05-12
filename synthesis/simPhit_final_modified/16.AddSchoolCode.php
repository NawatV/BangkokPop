<html>
    <head>
        <meta charset="UTF-8">
        <title> Update School code for SimPhitlok </title>
    </head>
    <body>
        <?php
//-------------- Initiation (same)-----------------------------------------------------
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        /** PHPExcel */
        require_once 'Classes/PHPExcel.php';

        /** PHPExcel_IOFactory - Reader */
        include 'Classes/PHPExcel/IOFactory.php';


        $inputFileName = "16_SchoolCode.xlsx";
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
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        //header('Content-type: text/plain');
        try {
            //error_reporting(error_reporting() & ~E_NOTICE);
            $time_start = microtime(true);
            $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //changed
            $objDB = mysql_select_db("SimPhit") or die("เลือกฐานข้อมูลไม่ได้");
            $table = "People";
            mysql_query("SET NAMES UTF8");
            mysql_query("SET character_set_results=UTF8");
            mysql_query("SET character_set_client=UTF8");
            mysql_query("SET character_set_connection=UTF8");

            $message = array();  //message ไว้ update set
            $message[0] = "อ.1";
            $message[1] = "อ.2";
            $message[2] = "ป.1";
            $message[3] = "ป.2";
            $message[4] = "ป.3";
            $message[5] = "ป.4";
            $message[6] = "ป.5";
            $message[7] = "ป.6";
            $message[8] = "ม.1";
            $message[9] = "ม.2";
            $message[10] = "ม.3";
            $message[11] = "ม.4";
            $message[12] = "ม.5";
            $message[13] = "ม.6";
            $mGender = array("male", "female");

            foreach ($namedDataArray as $result) {
                $m = 0;
                $mClass = 0;
                $cGen = 0;

                for ($sc = 1; $sc <= 28; $sc++) {
                    if ($sc % 2 == 1) {
                        $cGen = 0;
                    } else {
                        $cGen = 1;
                    }
                    
                    $dataSchool[$result["School_Code"]][$result["Area"]][$message[$mClass]][$mGender[$cGen]] = $result["$sc"];

                    $m++;
                    if ($m == 2) {
                        $m = 0;
                        $mClass++;
                    }
                }
            }

//----------------- AddSchoolCode ----------------------------------------------------
            $chlid_init = "SELECT `Location` ,`Gender` , `ID` , `Edu_Level` "
                    . "FROM `" . $table . "` "
                    . "WHERE `Edu_Level` != '' "
                    . "ORDER BY `Location`, `Gender` , `Edu_Level`";
            echo $chlid_init . "<br>";
            
            $resultC = mysql_query($chlid_init);
            $dataChlid = array();
            $sum = 0;
            while ($row = mysql_fetch_object($resultC)) {
                if (!isset($counter[$row->Location][$row->Gender][$row->Edu_Level])) {
                    $counter[$row->Location][$row->Gender][$row->Edu_Level] = 0;
                }
                $dataChlid[$row->Location][$row->Gender][$row->Edu_Level][$counter[$row->Location][$row->Gender][$row->Edu_Level]] = $row->ID;
                $counter[$row->Location][$row->Gender][$row->Edu_Level] ++;
                $sum++;
            }
            
            echo "$sum (all Child )<br>";
            mysql_free_result($resultC);

            $m = 0;
            if (true) {
                foreach ($dataSchool as $sCode => $value) {
                    foreach ($dataSchool[$sCode] as $sLocate => $value) {
                        foreach ($dataSchool[$sCode][$sLocate] as $sClass => $value) {
                            foreach ($dataSchool[$sCode][$sLocate][$sClass] as $sGen => $value) {
                                echo " " . $sCode . " " . $sLocate . " " . $sClass . " " . $sGen . " ";
                                echo $dataSchool[$sCode][$sLocate][$sClass][$sGen] . "<br>";

                                if (true) {
                                    if ($dataSchool[$sCode][$sLocate][$sClass][$sGen] > 0) {
                                        for ($i = 0; $i < $dataSchool[$sCode][$sLocate][$sClass][$sGen]; $i++) {
                                            echo $i . " ";
                                            $index = array_rand($dataChlid[$sLocate][$sGen][$sClass]);

                                            $HID = $dataChlid[$sLocate][$sGen][$sClass][$index];

                                            $sql_Update = "INSERT INTO `" . $table . "` (ID , SchoolCode) "
                                                    . "VALUES ( " . $HID . " , " . $sCode . ") "
                                                    . "ON DUPLICATE KEY UPDATE SchoolCode = VALUES (SchoolCode) ";
                                            echo $sql_Update . "<br>";
                                            mysql_query($sql_Update) or die(mysql_error());

                                            unset($dataChlid[$sLocate][$sGen][$sClass][$index]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

//------------- Update (similar) ---------------------------------------   
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            $hours = (int) ($time / 60 / 60);
            $minutes = (int) ($time / 60) - $hours * 60;
            $seconds = (double) $time - $hours * 60 * 60 - $minutes * 60;
            echo "<br>Time: $hours hours/ $minutes minutes/ $seconds seconds. <br>";
            mysql_close($objConnect);
        } catch (Exception $ex) {
            echo 'exception: ', $ex->getMessage(), "<br>";
            mysql_close($objConnect);
        }
        ?>
    </body>
</html>