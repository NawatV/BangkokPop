<html>
    <head>
        <meta charset="UTF-8">
        <title> Update Age for SimPhitlok </title>
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


        $inputFileName = "5_age.xlsx";
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
//----------------- AddAge (similar)-------------------------------------------------------
        //--- Set up -----
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
        //5.1
        $gender = array();  //เลือก string ไว้ query หาจำนวน
        $PercentLo = array();  //เก็บค่าจาก excel ของ location
        $area = array();
        $PercentAge = array();

        //----- Keep the data in $PercentLo[?][?]/Age[?][?], $gender[?][?], $area[?][?]  ------  5.2
        foreach ($namedDataArray as $result) {     //เอาค่าจาก excel มาใส่ตัวแปร
            $gender[$i][0] = $result["Gender"];
            $location[$i][1] = $result["Location"];
            $area[$i][2] = $result["Area"];
            $PercentAge[$i][3] = $result["น้อยกว่า1"];
            for ($j = 1; $j <= 100; $j++) {
                $PercentAge[$i][$j + 3] = $result["$j"];
            }
            $PercentAge[$i][104] = $result["มากกว่า100"];
            $i++;
        }

//    for($i=0;$i<=35;$i++){
//        echo $gender[$i][0]." ".$location[$i][1]." ".$area[$i][2]." ".$PercentAge[$i][3]. " ";
//        for($j =1;$j<=103;$j++){
//		echo $PercentAge[$i][$j+3]." ";
//	}
//        echo $PercentAge[$i][104]."<br>";
//    }
//    
//    

        $number = mysql_query("SELECT count(*) as number from `" . $table . "`");  //จำนวนคนทั้งหมดจาก db
        $Qnumber = mysql_fetch_assoc($number);
        echo "number = " . $Qnumber['number'] . "<br>";

        //----- Cal & Keep the data in $age[?][?][?][?]  ------  5.3
        $m = 0;
        $n = 3;
        for ($i = 0; $i <= 1; $i++) {
            for ($j = 1; $j <= 9; $j++) {
                for ($k = 0; $k <= 1; $k++) {
                    for ($l = 0; $l <= 101; $l++) {
                        $age[$i][$j][$k][$l] = round(($PercentAge[$m][$n] * $Qnumber['number']) / 100);
                        $n++;
                    }
                    $n = 3;
                    $m++;
                }
            }
        }

        //---- Keep locations in arrays (=$headingsArray) -------
        $gender[0] = "male";
        $gender[1] = "female";
        $array_location[1] = "อ.เมืองพิษณุโลก";
        $array_location[2] = "อ.นครไทย";
        $array_location[3] = "อ.ชาติตระการ";
        $array_location[4] = "อ.บางระกำ";
        $array_location[5] = "อ.บางกระทุ่ม";
        $array_location[6] = "อ.พรหมพิราม";
        $array_location[7] = "อ.วัดโบสถ์";
        $array_location[8] = "อ.วังทอง";
        $array_location[9] = "อ.เนินมะปราง";
        $area[0] = "นอกเขตเทศบาล";
        $area[1] = "ในเขตเทศบาล";

        //----- Add "0-101" in $age_text[] --------
        $age_text[0] = "น้อยกว่า1";
        for ($i = 1; $i <= 100; $i++) {
            $age_text[$i] = $i;
        }
        $age_text[101] = "มากกว่า100";
        
        //----------------- Keep the data in DB &Find $minMale, $minFemale --------------------
        //--- Set up ----
        $minMale = 1;
        echo "minMale = 1 <br>";

        $tempFM = mysql_query("SELECT MIN(`ID`) AS MINFM FROM `" . $table . "` WHERE  `Gender` = 'female'");  //จำนวนคนทั้งหมดจาก db
        $resultFM = mysql_fetch_assoc($tempFM);
        echo "minFemale = " . $resultFM['MINFM'] . "<br>";

        $minFemale = intval($resultFM['MINFM']);

        //Keep & Find
        for ($i = 0; $i <= 1; $i++) {
            for ($j = 1; $j <= 9; $j++) {                //location: 9 locations 
                for ($k = 0; $k <= 1; $k++) {            //area: Area in, Area out
                    for ($l = 0; $l <= 101; $l++) {      //age: "0-101" 
                        if ($i == 0) {
                        //----------------- Keep & Find $minMale ------------------
                            echo "$gender[$i] $array_location[$j] $area[$k] age $l ID $minMale - " . ($minMale + $age[$i][$j][$k][$l] - 1 ) . "<br>";

                            for ($index = $minMale; $index <= ($minMale + $age[$i][$j][$k][$l] - 1 ); $index++) {
                                $sql_Update = "INSERT INTO `" . $table . "` (ID , Age) "
                                        . "VALUES ( " . $index . " , '" . $age_text[$l] . "') "
                                        . "ON DUPLICATE KEY UPDATE Age = VALUES (Age) ";
                                //echo $sql_Update . "<br>";
                                mysql_query($sql_Update) or die(mysql_error());
                            }

                            $minMale += $age[$i][$j][$k][$l];
                        //---------------------------------------------
                        } else {
                        //----------------- Keep& Find $minFemale ------------------
                            echo "$gender[$i] $array_location[$j] $area[$k] age $l ID $minFemale - " . ($minFemale + $age[$i][$j][$k][$l] - 1 ) . "<br>";

                            for ($index = $minFemale; $index <= ($minFemale + $age[$i][$j][$k][$l] - 1 ); $index++) {
                                $sql_Update = "INSERT INTO `" . $table . "` (ID , Age) "
                                        . "VALUES ( " . $index . " , '" . $age_text[$l] . "') "
                                        . "ON DUPLICATE KEY UPDATE Age = VALUES (Age) ";
                                //echo $sql_Update . "<br>";
                                mysql_query($sql_Update) or die(mysql_error());
                            }

                            $minFemale += $age[$i][$j][$k][$l];
                        //----------------------------------------------
                        }
                    }
                    //echo "success " . $area[$k] . " ";
                }
                //echo $array_location[$j] . "<br> ";
            }
        }

//------------- Update (similar) ---------------------------------------
        //FOR 11
        $sql_temp1 = "UPDATE " . $table . " SET `temp`= IF(`Age` != 'มากกว่า100',`Age`,101) WHERE 1";
        mysql_query($sql_temp1);
        
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
