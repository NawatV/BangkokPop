<html>
    <head>
        <meta charset="UTF-8">
        <title> Update Age At First Birth for SimPhitlok </title>
    </head>
    <body>
        <?php
//-------------- Initiation (same)-----------------------------------------------------
        /** PHPExcel */
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        require_once 'Classes/PHPExcel.php';

        /** PHPExcel_IOFactory - Reader */
        include 'Classes/PHPExcel/IOFactory.php';


        $inputFileName = "13_age_at_first_birth_remake.xlsx";
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
//--------------AddAgeAtFirstBirth------------------------------------------------------
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        try {
            error_reporting(error_reporting() & ~E_NOTICE);
            $time_start = microtime(true);
            $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //changed
            $objDB = mysql_select_db("SimPhit") or die("เลือกฐานข้อมูลไม่ได้");
            $table = "People";
            mysql_query("SET NAMES UTF8");
            mysql_query("SET character_set_results=UTF8");
            mysql_query("SET character_set_client=UTF8");
            mysql_query("SET character_set_connection=UTF8");

            foreach ($namedDataArray as $result) {
                $gender = $result["Gender"];
                $per[0] = $result["13-19"];
                $per[1] = $result["20-24"];
                $per[2] = $result["25-29"];
                $per[3] = $result["30-34"];
                $per[4] = $result["35-39"];
            }
            echo "_____>" . $gender . " " . $per[0] . " " . $per[1] . " " . $per[2] . " " . $per[3] . " " . $per[4] . "<br>";

            $time_end = microtime(true);
            $time = $time_end - $time_start;
            $hours = (int) ($time / 60 / 60);
            $minutes = (int) ($time / 60) - $hours * 60;
            $seconds = (int) $time - $hours * 60 * 60 - $minutes * 60;
            echo "Read Excel: $hours hours/ $minutes minutes/ $seconds seconds. <br>";

            $str_Qry = mysql_query("SELECT COUNT(*) AS CN FROM " . $table . " WHERE `Number_children` != '0'");  //จำนวนคนทั้งหมดจาก db
            $countAllMom = mysql_fetch_assoc($str_Qry);

            $sql_make = "ALTER TABLE " . $table . " ADD COLUMN Age_At_First_Birth INT DEFAULT 0";
            //mysql_query($sql_make) or die(mysql_error());

            echo "Count = " . $countAllMom['CN'] . "<br>";

            $sum = 0;
            $sumper = 0;

            $startAge = array(13, 20, 25, 30, 35);
            $endAge = array(19, 24, 29, 34, 39);

            for ($i = 0; $i < 5; $i++) {    //คิดจำนวนคนที่มีลูกคนแรกในแต่ละช่วง
                $firstMomCount[$i] = round($per[$i] / 100 * $countAllMom['CN'], 0, PHP_ROUND_HALF_UP);
                $sum += $firstMomCount[$i];
                $sumper += $per[$i];
                echo "1stMom $i = " . $firstMomCount[$i] . "<br>";
            }
            echo "sum = " . $sum . "<br>";

            for ($k = 0; $k < (abs($countAllMom['CN'] - $sum)); $k++) {      //ปัดเศษคนที่ขาดหรือเกิน
                if ($countAllMom['CN'] - $sum > 0) {
                    $firstMomCount[rand(0, 4)] ++;
                }
                if ($countAllMom['CN'] - $sum < 0) {
                    $firstMomCount[rand(0, 4)] --;
                }
            }

            //$findMissing = $firstMomCount;
            $sum = 0;
            for ($i = 0; $i < 5; $i++) {
                $sum += $firstMomCount[$i];
                $findMissing[$i] = $firstMomCount[$i];
            }
            echo "sum = " . $sum . "<br><br>";  //check จำนวนทั้งหมดว่าครบหรือยัง
            //--------------------สร้าง attribute เพิ่มให้เก็บค่าอายุ มากกว่า100 =101----------------------------//
            $sql_temp = "ALTER TABLE " . $table . " ADD COLUMN temp INT DEFAULT 0";
            //mysql_query($sql_temp);

            $sql_temp1 = "UPDATE " . $table . " SET `temp`= IF(`Age` != 'มากกว่า100',`Age`,101) WHERE 1";
            //mysql_query($sql_temp1);
            //-------------------------------------------------------------------------------------------//
//            $allMon = 0;
            //$allMomInRank = 0;
            $allMomMustInRank = 0;
            for ($i = 0; $i < 5; $i++) {    //ช่วงอายุ
                $allMomInRank = 0;
                $str_count = "SELECT COUNT(*) AS Count "
                        . "FROM " . $table . " WHERE `Number_children` != '0' "
                        . "AND `Age_At_First_Birth` = '0'"
                        . "AND `temp` - `Number_children` + 1 >= " . $startAge[$i] . " AND `temp` - `Number_children` + 1 <= " . $endAge[$i];
                echo $str_count . "<br>";
                $countEmptyMom = mysql_fetch_assoc(mysql_query($str_count));
                echo "Count = " . $countEmptyMom['Count'];
                $emptyMomMustInRank[$i] = $countEmptyMom['Count'];
                $allMomMustInRank += $emptyMomMustInRank[$i]; //แม่ที่มีอายุต้องอยู่ใน rank นั้นเท่านั้น

                for ($k = 0; $k <= $i; $k++) { //นับ                  
                    $allMomInRank += $firstMomCount[$k];    //แม่ที่มีอายุอื่นมาอยู่ใน rank ด้วย                    
                }

                echo " allMomInRank = " . $allMomInRank . " AllMomMust = " . $allMomMustInRank . "<br>";

                for ($l = 0; $l <= $i; $l++) {   //cal percent mom in rank
                    $pLimitMom[$l] = ($firstMomCount[$l] / $allMomInRank);    //แบ่งแม่ตามอัตราส่วนว่าจะอยู่ใน rank ไหนกี่%
                    echo "percent = " . $pLimitMom[$l];

                    $limitMom[$l] = round($pLimitMom[$l] * $emptyMomMustInRank[$i], 0, PHP_ROUND_HALF_UP);   //เอา% ไปคูณจำนวนแม่ที่ต้องอยู่ในแต่ละ rank
                    $sumLimitMom += $limitMom[$l];

                    echo "  LimitMom = " . $limitMom[$l] . "<br>";
                }

                if ($sumLimitMom > $allMomMustInRank) { //round mom ปัดจำนวนคนที่ขาดหรือเกิน
                    $limitMom[rand(0, $i)] --;
                } else if ($sumLimitMom < $allMomMustInRank) {
                    $limitMom[rand(0, $i)] ++;
                }
                
//------------- Update (similar) ---------------------------------------   
                for ($j = 0; $j <= $i; $j++) {
                    $str_sql = "UPDATE " . $table . " "
                            . "SET `Age_At_First_Birth` = IF(`temp` - `Number_children` + 1 <= " . $endAge[$j] . " , "
                            . "FLOOR(RAND() * (`temp` - `Number_children` - " . $startAge[$j] . " + 2))  + " . $startAge[$j] . " , "
                            . "FLOOR(RAND() * (" . $endAge[$j] . " - " . $startAge[$j] . " + 1)) + " . $startAge[$j] . ") "
                            . "WHERE `Number_children` != '0' "
                            . "AND `Age_At_First_Birth` = '0' "
                            . "AND `temp` - `Number_children` + 1 >= " . $startAge[$i] . " "
                            . "AND `temp` - `Number_children` + 1 <= " . $endAge[$i] . " "
                            . "ORDER BY RAND() LIMIT " . $limitMom[$j];
                    mysql_query($str_sql);
                    $firstMomCount[$j] -= $limitMom[$j];
                    echo $str_sql . "<br>";
                    echo "All Mom In Rank[$j] = " . $firstMomCount[$j] . "<br>";
                }
                echo "<br>";
            }
            //--------------------update คนที่อายุมากกว่า40-----------------------------------//
            echo "update mom's age > 40 <br>";
            for ($j = 0; $j < 5; $j++) {
                $str_sql = "UPDATE " . $table . " "
                        . "SET `Age_At_First_Birth` = "
                        . "FLOOR(RAND() * (" . $endAge[$j] . " - " . $startAge[$j] . " + 1))  + " . $startAge[$j] . " "
                        . "WHERE `Number_children` != '0' "
                        . "AND `Age_At_First_Birth` = '0' "
                        . "AND `temp`  >= " . $startAge[$j] . " "
                        . "ORDER BY RAND() LIMIT " . $firstMomCount[$j];
                mysql_query($str_sql);
                echo $str_sql . "<br>";
                //echo "All Mom In Rank = " . $firstMomCount[$j] . "<br>";
            }
            echo "-------------------------------------------------------------------<br><br>";

            $str_QryFinal = mysql_query("SELECT COUNT(*) AS CN FROM " . $table . " WHERE `Number_children` != '0' AND `Age_At_First_Birth` != 0");  //จำนวนคนทั้งหมดจาก db
            $countFinal = mysql_fetch_assoc($str_QryFinal);
            //echo "";
            if ($countFinal['CN'] != $countAllMom['CN']) {
                echo "--------------------------------------MISSING--------------------------------------<br><br>";
                for ($j = 0; $j < 5; $j++) {
                    //$firstMomCount[$i]
                    $str_QryFinalRank = mysql_query("SELECT COUNT(*)AS CN FROM `" . $table . "` "
                            . "WHERE  `Number_children` != 0 AND "
                            . "`Age_At_First_Birth`  BETWEEN " . $startAge[$j] . " AND " . $endAge[$j] . "");  //จำนวนคนทั้งหมดจาก db
                    $countFinalRank = mysql_fetch_assoc($str_QryFinalRank);

                    $missingMom[$j] = $findMissing[$j] - $countFinalRank['CN'];
                    echo "Missing = " . $missingMom[$j] . " findMissing = " . $findMissing[$j] . " countFinalRank = " . $countFinalRank['CN'] . "<br>";
                    //echo "";
                    if ($missingMom[$j] != 0) {
                        $str_sql = "UPDATE " . $table . " "
                                . "SET `Age_At_First_Birth` = "
                                . "FLOOR(RAND() * (" . $endAge[$j] . " - " . $startAge[$j] . " + 1))  + " . $startAge[$j] . " "
                                . "WHERE `Number_children` != '0' "
                                . "AND `Age_At_First_Birth` = '0' "
                                . "AND `temp`  >= " . $startAge[$j] . " "
                                . "ORDER BY RAND() LIMIT " . $missingMom[$j];
                        mysql_query($str_sql);
                        echo $str_sql . "<br>";
                    }
                }
            }

            //echo "sum = ". $firstMomCount[0] +$firstMomCount[1] +$firstMomCount[2] +$firstMomCount[3] + $firstMomCount[4]."<br>";
            //$sql_temp2 = "ALTER TABLE " . $table . " DROP COLUMN temp";
            //mysql_query($sql_temp2);

            echo "per = $sumper <br>";
            $time_end1 = microtime(true);
            $time1 = $time_end1 - $time_start;
            $hours1 = (int) ($time1 / 60 / 60);
            $minutes1 = (int) ($time1 / 60) - $hours1 * 60;
            $seconds1 = (double) $time1 - $hours1 * 60 * 60 - $minutes1 * 60;
            //writeMsg();
            echo "Time of  select Area , Age , count(Age) :  $hours1 hours/ $minutes1  minutes/ $seconds1 seconds. <br>";

            mysql_close($objConnect);
        } catch (Exception $ex) {
            echo 'exception: ', $ex->getMessage(), "<br>";
            mysql_close($objConnect);
        }
        ?>
    </body>
</html>