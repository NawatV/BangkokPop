<html>
    <head>
        <meta charset="UTF-8">
        <title> Update Marital Status of Female for SimPhitlok </title>
    </head>
    <body>

        <?php
//-------------- Initiation (same)-----------------------------------------------------
        /** PHPExcel */
        require_once 'Classes/PHPExcel.php';

        /** PHPExcel_IOFactory - Reader */
        include 'Classes/PHPExcel/IOFactory.php';


        $inputFileName = "8_marital.xlsx";
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
//----------------- AddMartialFemale (similar) -----------------------------------------------
        //-----Set up --------------------------
        $time_start = microtime(true);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        //*** Connect to MySQL Database ***//
        $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //changed
        $objDB = mysql_select_db("SimPhit") or die("เลือกฐานข้อมูลไม่ได้");
        $table = 'People';
        mysql_query("SET NAMES UTF8");
        mysql_query("SET character_set_results=UTF8");
        mysql_query("SET character_set_client=UTF8");
        mysql_query("SET character_set_connection=UTF8");

        $i = 0;
        //8.1
        $gender = array();   //เก็บค่าจาก excel 
        $area = array();
        $startage = array();
        $endage = array();
        $single = array();  //เก็บค่าจาก excel ของ location
        $married = array();
        $widowed = array();
        $divorced = array();
        $separated = array();
        $unknow = array();
        $unknowStatus = array();

        //----------- Keep the data in arrays above -------------- 8.2
        foreach ($namedDataArray as $result) {     //เอาค่าจาก excel มาใส่ตัวแปร
            $gender[$i][0] = $result["Gender"];
            $area[$i][1] = $result["Area"];
            $startage[$i][2] = $result["StartAge"];
            $endage[$i][3] = $result["EndAge"];
            $single[$i][4] = floatval($result["โสด"]);
            $married[$i][5] = floatval($result["สมรส"]);
            $widowed[$i][6] = floatval($result["หม้าย"]);
            $divorced[$i][7] = floatval($result["หย่า"]);
            $separated[$i][8] = floatval($result["แยกกันอยู่"]);
            $unknow[$i][9] = floatval($result["ไม่ทราบ"]);
            $unknowStatus[$i][10] = floatval($result["ไม่ทราบสถานภาพสมรส"]);

            $i++;
        }

        //----------- Message ---------------------------- 8.3
        $message = array();  //message ไว้ update set
        $message[4] = "โสด";
        $message[5] = "สมรส";
        $message[6] = "หม้าย";
        $message[7] = "หย่า";
        $message[8] = "แยกกันอยู่";
        $message[9] = "ไม่ทราบ";
        $message[10] = "ไม่ทราบสถานภาพสมรส";


        $groupage = array();
        $number = array();
        //------------ Keep the data in  $groupage[?] -------------------
        for ($i = 0; $i <= 31; $i++) {  //32 Periods of age in "sheet2"
            
            if ($endage[$i][3] == "มากกว่า100") {
            //-------- > 100 ----------------------------
                $number[$i] = mysql_query("SELECT count(*) as number from `" . $table . "` "
                        . "WHERE `Gender` = '" . $gender[$i][0] . "' AND `Area` = '" . $area[$i][1] . "' "
                        . "AND (`Age` >= " . $startage[$i][2] . " AND `Age` <= 100 OR `Age` = '" . $endage[$i][3] . "')");
                $groupage[$i] = mysql_fetch_assoc($number[$i]);
//                echo $i . " = " . $groupage[$i]['number'] . "<br>";           
//                echo "test : gender" . $gender[$i][0] . " area " . $area[$i][1] . " sage " . $startage[$i][2] . " end " . $endage[$i][3] . "<br>";
            } else {
            //--------- OTHER CASES ---------------------
                $number[$i] = mysql_query("SELECT count(*) as number from `" . $table . "` "
                        . "WHERE `Gender` = '" . $gender[$i][0] . "' AND `Area` = '" . $area[$i][1] . "' "
                        . "AND `Age` >= '" . $startage[$i][2] . "' AND `Age` <= '" . $endage[$i][3] . "'");
                $groupage[$i] = mysql_fetch_assoc($number[$i]);
//                echo $i . " = " . $groupage[$i]['number'] . "<br>";
//                echo "test : gender " . $gender[$i][0] . " area " . $area[$i][1] . " sage " . $startage[$i][2] . " end " . $endage[$i][3] . "<br>";
            }
        }

        //-------------- Keep the numbers in $status[?][?] --------------------
        for ($i = 0; $i <= 31; $i++) {   //32 Periods of age in "sheet2"
            //4-10 refers to the status of 8.3
            $status[$i][4] = (($single[$i][4] * $groupage[$i]['number']) / 100);
            $status[$i][5] = (($married[$i][5] * $groupage[$i]['number']) / 100);
            $status[$i][6] = (($widowed[$i][6] * $groupage[$i]['number']) / 100);
            $status[$i][7] = (($divorced[$i][7] * $groupage[$i]['number']) / 100);
            $status[$i][8] = (($separated[$i][8] * $groupage[$i]['number']) / 100);
            $status[$i][9] = (($unknow[$i][9] * $groupage[$i]['number']) / 100);
            $status[$i][10] = (($unknowStatus[$i][10] * $groupage[$i]['number']) / 100);

            $max = -1;
            $maxIndex = 0;
            $min = 999;
            $minIndex = 0;
            $sum = 0;
            $total = 0;
            //---------- 4-10 refers to the status of 8.3 -------------
            for ($j = 4; $j <= 10; $j++) {
                
                $digit[$i][$j] = ($status[$i][$j] * 100) % 100;
                
                //------- $max ----------------------
                if ($digit[$i][$j] < 50) {
                    if ($digit[$i][$j] > $max) {
                        $maxIndex = $j;
                        $max = $digit[$i][$j];
                    }
                }
                //------- $min ----------------------
                if ($digit[$i][$j] > 50) {
                    if ($digit[$i][$j] < $min) {
                        $minIndex = $j;
                        $min = $digit[$i][$j];
                    }
                }

                //----- 4-10 status OF 32 Periods of age in "sheet2" ---------
                $status[$i][$j] = round($status[$i][$j]);
                echo $status[$i][$j] . " ";
                
                $sum += $status[$i][$j];
                
            }
            
            if ($sum > intval($groupage[$i]['number'])) {
                $status[$i][$minIndex] --;
            } else if ($sum < intval($groupage[$i]['number'])) {
                $status[$i][$maxIndex] ++;
            }
            
            echo "<br>";
            //---- 4-10 refers to the status of 8.3 ----------
            for ($j = 4; $j <= 10; $j++) {
                echo $status[$i][$j] . " ";
                $total += $status[$i][$j];
            }
            echo "<br> Sum = $sum Re = $total Data = ".$groupage[$i]['number']."<br><br>";
        }
        
//------------- Update (similar) < Fill attributes > ---------------------------------------   
        $lock_sql = "LOCK TABLES `" . $table . "` WRITE;";
        //mysql_query($lock_sql) or die(mysql_error());

        for ($i = 0; $i <= 31; $i++) {
            for ($j = 4; $j <= 10; $j++) {
                if ($endage[$i][3] == "มากกว่า100") {
                    $addmar = "UPDATE `" . $table . "` SET `Marital_Status` = '" . $message[$j] . "' WHERE `Gender` = '" . $gender[$i][0] . "' AND `Area` = '" . $area[$i][1] . "' AND `Age` >= '" . $startage[$i][2] . "' AND (`Age` >= " . $startage[$i][2] . "  OR Age = '" . $endage[$i][3] . "') AND `Marital_Status` = ''  ORDER BY RAND() LIMIT " . $status[$i][$j] . "";
                    mysql_query($addmar) or die(mysql_error());
                    echo "$i$j update successful <br>";
                } else {
                    $addmar = "UPDATE `" . $table . "` SET `Marital_Status` = '" . $message[$j] . "' WHERE `Gender` = '" . $gender[$i][0] . "' AND `Area` = '" . $area[$i][1] . "' AND `Age` >= '" . $startage[$i][2] . "' AND `Age` <= '" . $endage[$i][3] . "' AND `Marital_Status` = '' ORDER BY RAND() LIMIT " . $status[$i][$j] . "";
                    mysql_query($addmar) or die(mysql_error());
                    echo "$i$j update successful <br>";
                }
            }
        }
        $unlock_sql = "UNLOCK TABLES;";
        //mysql_query($unlock_sql) or die(mysql_error());
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