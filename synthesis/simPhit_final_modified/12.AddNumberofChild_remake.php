<html>
    <head>
        <meta charset="UTF-8">
        <title> Update Number of children for SimPhitlok </title>
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


        $inputFileName = "12_no_children_remake.xlsx";
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

        //header('Content-type: text/plain');
        try {
            //error_reporting(error_reporting() & ~E_NOTICE);
            $time_start = microtime(true);
            //*** Connect to MySQL Database ***//
            $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //changed
            $objDB = mysql_select_db("SimPhit") or die("เลือกฐานข้อมูลไม่ได้");
            $table = 'People';
            mysql_query("SET NAMES UTF8");
            mysql_query("SET character_set_results=UTF8");
            mysql_query("SET character_set_client=UTF8");
            mysql_query("SET character_set_connection=UTF8");

            $perAge = array();

            foreach ($namedDataArray as $result) {     //เอาค่าจาก excel มาใส่ตัวแปร     
                $perAge[$result["Area"]][0][$result["NoOfChildren"]] = $result["13-14"];
                $perAge[$result["Area"]][1][$result["NoOfChildren"]] = $result["15-19"];
                $perAge[$result["Area"]][2][$result["NoOfChildren"]] = $result["20-24"];
                $perAge[$result["Area"]][3][$result["NoOfChildren"]] = $result["25-29"];
                $perAge[$result["Area"]][4][$result["NoOfChildren"]] = $result["30-34"];
                $perAge[$result["Area"]][5][$result["NoOfChildren"]] = $result["35-39"];
                $perAge[$result["Area"]][6][$result["NoOfChildren"]] = $result["40-44"];
                $perAge[$result["Area"]][7][$result["NoOfChildren"]] = $result["45-49"];
                $perAge[$result["Area"]][8][$result["NoOfChildren"]] = $result["50-54"];
                $perAge[$result["Area"]][9][$result["NoOfChildren"]] = $result["55-59"];
                $perAge[$result["Area"]][10][$result["NoOfChildren"]] = $result["60-64"];
                $perAge[$result["Area"]][11][$result["NoOfChildren"]] = $result["65-69"];
                $perAge[$result["Area"]][12][$result["NoOfChildren"]] = $result["70up"];
            }

            //print_r($perAge);
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            $hours = (int) ($time / 60 / 60);
            $minutes = (int) ($time / 60) - $hours * 60;
            $seconds = (double) $time - $hours * 60 * 60 - $minutes * 60;
            echo "Read Excel: $hours hours/ $minutes minutes/ $seconds seconds. <br>";


            $ageStart = array(13, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70);
            $age_end = array(14, 19, 24, 29, 34, 39, 44, 49, 54, 59, 64, 69, 101);

            $sql_temp = "ALTER TABLE " . $table . " ADD COLUMN UNKNOWN_CHILD INT NOT NULL DEFAULT 0";
            //mysql_query($sql_temp);

            for ($aBug = 0; $aBug <= 1; $aBug++) {
                if ($aBug == 0) {
                    $keyArea = "นอกเขตเทศบาล";
                } else {
                    $keyArea = "ในเขตเทศบาล";
                }
                for ($j = 0; $j <= 12; $j++) {//rank age
                    $sql = "SELECT count(*) as number FROM `" . $table . "` "
                            . "WHERE Gender = 'female' AND Area =  '" . $keyArea . "' "
                            . "AND temp >= '" . $ageStart[$j] . "' AND temp <= '" . $age_end[$j] . "' "
                            . "AND `Marital_Status` != 'โสด' "
                            . "AND `Marital_Status` != 'ไม่ทราบสถานภาพสมรส' "
                            . "AND `Marital_Status` != '' ";
                    echo $sql . "<br>";
                    $number = mysql_query($sql);  //จำนวนคนทั้งหมดจาก db
                    $Qnumber[$keyArea][$j] = mysql_fetch_assoc($number);
                    mysql_free_result($number);

                    echo "Count " . $keyArea . "/" . $ageStart[$j] . "/" . $age_end[$j] . " = " . $Qnumber[$keyArea][$j]['number'] . "<br>";

                    //Cal percent
                    $checkLose = 0;
                    $tempMax = 0;
                    $keepIndex = 0;
                    for ($l = 0; $l <= 11; $l++) {//num of child
                        if ($tempMax < $perAge[$keyArea][$j][$l]) {
                            $tempMax = $perAge[$keyArea][$j][$l];
                            $keepIndex = $l;
                        }

                        $calNumP[$keyArea][$j][$l] = percentCalc($perAge[$keyArea][$j][$l], intval($Qnumber[$keyArea][$j]['number']));
                        echo "Per$l = " . round($perAge[$keyArea][$j][$l], 3) . "%" . $calNumP[$keyArea][$j][$l] . "<br>";

                        $checkLose += $calNumP[$keyArea][$j][$l];
                    }
                    echo "All = " . $Qnumber[$keyArea][$j]['number'] . "<br>";
                    echo "check Lose  = $checkLose<br>";

                    //ปัดเลข
                    if ($checkLose < intval($Qnumber[$keyArea][$j]['number'])) {
                        $calNumP[$keyArea][$j][$keepIndex] ++;
                    } else if ($checkLose > intval($Qnumber[$keyArea][$j]['number'])) {
                        $calNumP[$keyArea][$j][$keepIndex] --;
                    }

                    for ($m = 11; $m > 0; $m--) {
                        if ($calNumP[$keyArea][$j][$m] > 0) {
                            if ($j < 2 && ($m + 12) > $ageStart[$j] && $m < 11) {
                                $tempSAge = $m + 12;
                            } else {
                                $tempSAge = $ageStart[$j];
                            }

                            $str_sql = "UPDATE `" . $table . "` SET `Number_children` = '$m' "
                                    . "WHERE  `Gender` = 'female' "
                                    . "AND `Area` = '" . $keyArea . "' "
                                    . "AND temp >= " . $tempSAge . " "
                                    . "AND temp <= " . $age_end[$j] . " "
                                    . "AND `Marital_Status` != 'โสด' "
                                    . "AND `Marital_Status` != 'ไม่ทราบสถานภาพสมรส' "
                                    . "AND `Marital_Status` != '' "
                                    . "AND `Number_children` = '0' "
                                    . "ORDER BY RAND() LIMIT " . $calNumP[$keyArea][$j][$m];  //จำนวนคนทั้งหมดจาก db
                            echo $str_sql . "<br><br>";

                            mysql_query($str_sql) or die(mysql_error());
                        }
                    }
                }
            }
            $str_sql1 = "UPDATE `" . $table . "` SET UNKNOWN_CHILD = 1 , Number_children = 0 "
                    . "WHERE Number_children = 11 ";
            echo $str_sql1 . "<br>";
            mysql_query($str_sql1) or die(mysql_error());

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

        function percentCalc($percent, $all) {
            $decimal = $percent * .01;
            $unrounded = $decimal * $all;
            $final = round($unrounded);

            return $final;
        }
        ?>
    </body>
</html>