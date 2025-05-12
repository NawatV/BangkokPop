<html>
    <head>
        <meta charset="UTF-8">
        <title> Update HH_Status = Head of Female for SimPhitlok </title>
    </head>
    <body>

        <?php        
//-------------- Initiation (same)-----------------------------------------------------
        /** PHPExcel */
        require_once 'Classes/PHPExcel.php';

        /** PHPExcel_IOFactory - Reader */
        include 'Classes/PHPExcel/IOFactory.php';


        $inputFileName = "10_head_hh.xlsx";
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

//----------------- AddHead (similar)-------------------------------------------------------
        //error_reporting(error_reporting() & ~E_NOTICE);
        $time_start = microtime(true);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        //header('Content-type: text/plain');
        //*** Connect to MySQL Database ***//
        $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //changed
        $objDB = mysql_select_db("SimPhit") or die("เลือกฐานข้อมูลไม่ได้");
        $table = "People";
        mysql_query("SET NAMES UTF8");
        mysql_query("SET character_set_results=UTF8");
        mysql_query("SET character_set_client=UTF8");
        mysql_query("SET character_set_connection=UTF8");

        $i = 0;
        $o = 0;
        $counter = 0;
        $start_age = array();
        $end_age = array();
        foreach ($namedDataArray as $result) {     //เอาค่าจาก excel มาใส่ตัวแปร
            if ($counter < 16) {
                $j = 0;
                $start_age[0][$j][$i] = $result["startAge"];
                $end_age[0][$j][$i] = $result["endAge"];
                $percent[0][$j][$i] = $result["male"];

                $start_age[1][$j][$i] = $result["startAge"];
                $end_age[1][$j][$i] = $result["endAge"];
                $percent[1][$j][$i] = $result["female"];
                $i++;
            } else if ($counter >= 16) {
                $j = 1;
                $start_age[0][$j][$o] = $result["startAge"];
                $end_age[0][$j][$o] = $result["endAge"];
                $percent[0][$j][$o] = $result["male"];

                $start_age[1][$j][$o] = $result["startAge"];
                $end_age[1][$j][$o] = $result["endAge"];
                $percent[1][$j][$o] = $result["female"];
                $o++;
            }
            //echo "Counter = $counter<br>";
            $counter++;
        }

        foreach ($namedDataArray as $result) {     //เอาค่าจาก excel มาใส่ตัวแปร
            if ($result["location"] != "") {
//                echo $result["location"] . " ";
//                echo $result["Area"] . " ";
//                echo $result["count"] . "<br>";
                $numinlocate[$result["location"]][$result["Area"]] = $result["count"];
            }
        }


        $area_todo = array("นอกเขตเทศบาล", "ในเขตเทศบาล");
        $gender_todo = array("male", "female");

        $str_todo = "SELECT `Location` FROM `" . $table . "` WHERE 1 GROUP BY `Location`";
        //echo $str_todo . "<br>";
        $resultodo = mysql_query($str_todo) or die(mysql_error());
        $locatin_todo = array();
        $todo = 0;
        while ($saveL = mysql_fetch_object($resultodo)) {
            $locatin_todo[$todo] = $saveL->Location;
            $todo++;
        }
        mysql_free_result($resultodo);

        //---------------------------Query all Location---------------------------\\
        //0 = อ.ชาติตระการ
        //1 =อ.นครไทย
        //2 =อ.บางกระทุ่ม
        //3 =อ.บางระกำ  
        //4 =อ.พรหมพิราม
        //5 =อ.วังทอง
        //6 =อ.วัดโบสถ์
        //7 =อ.เนินมะปราง
        //8 =อ.เมืองพิษณุโลก
        //อ่านค่า ของ อำเภอว่าแต่ละ อำเภอมีกี่คน
        //>>>>>>>>>>>>>>>>>>>>>>>>>>>>ทำทีละอำเภอ<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        // คำนวนหาว่าแต่ละ ละเขต แต่ละอำเภอมี ญ ช อย่างละกี่คน โดย แบ่งอัตราส่วน 
        for ($selectLocate = 0; $selectLocate < 9; $selectLocate++) {
            $outVal = $numinlocate[$locatin_todo[$selectLocate]][$area_todo[0]];
            $inVal = $numinlocate[$locatin_todo[$selectLocate]][$area_todo[1]];
            echo "<br>" . $locatin_todo[$selectLocate] . "<br>";
            echo $area_todo[0] . " : " . $outVal . "<br>";
            echo $area_todo[1] . " : " . $inVal . "<br>";
            //male
            $choice[0][0] = round((147118 / (147118 + 80936)) * $outVal);
            echo "Male out : " . $choice[0][0] . "<br>";
            $choice[0][1] = round((42051 / (42051 + 26702)) * $inVal);
            echo "Male in : " . $choice[0][1] . "<br>";
            //female
            $choice[1][0] = round((80936 / (147118 + 80936)) * $outVal);
            echo "Female out : " . $choice[1][0] . "<br>";
            $choice[1][1] = round((26702 / (42051 + 26702)) * $inVal);
            echo "Female in : " . $choice[1][1] . "<br>";
            
            //query คนที่จะต้องใช้แยกหมวดหมูตาม array เพศ เขต อายุ
            $totalInLocation = 0;
            $min = 0; //factor to random
            $max = 100;
            $num = 0;
            $num_age = array();
            $count_out = array();
            $count_in = array();

            $str_count = "SELECT `Gender`, `Area`, `Age`, count(ID) AS AllC FROM `" . $table . "` "
                    . "WHERE `Location` = '" . $locatin_todo[$selectLocate] . "' "
                    . "GROUP BY `Gender`,`Location`,`Area`,`Age`";
            //echo $str_count . "<br>";
            $resulcount = mysql_query($str_count) or die(mysql_error());
            $countPeople = array();
            while ($saveAll = mysql_fetch_object($resulcount)) {
                if ($saveAll->Gender == "male") {
                    $tempG = 0;
                } else {
                    $tempG = 1;
                }

                if ($saveAll->Area == "นอกเขตเทศบาล") {
                    $tempAr = 0;
                } else {
                    $tempAr = 1;
                }

                if ($saveAll->Age == "น้อยกว่า1") {
                    $tempAge = 0;
                } else if ($saveAll->Age == "มากกว่า100") {
                    $tempAge = 101;
                } else {
                    $tempAge = intval($saveAll->Age);
                }
                $countPeople[$tempG][$tempAr][$tempAge] = $saveAll->AllC;
                $totalInLocation += $saveAll->AllC;
                //echo $saveAll->Gender . " " . $saveAll->Area . " " . $saveAll->Age . " " . $countPeople[$tempG][$tempAr][$tempAge] . "<br>";
            }

            mysql_free_result($resulcount);
            
            //ปรับค่า ถ้า array ของอายุไหนไม่มี ให้เป็น 0 แทน null
            $sumChoice = 0;
            for ($i = 0; $i < 2; $i++) {
                for ($j = 0; $j < 2; $j++) {
                    //echo " " . $choice[$i][$j] . "<br>";
                    $sumChoice += $choice[$i][$j];
                    for ($k = 0; $k < 102; $k++) {
                        if (!isset($countPeople[$i][$j][$k])) {
                            $countPeople[$i][$j][$k] = 0;
                        }
                        //echo "Check $i/$j/$k = " . $countPeople[$i][$j][$k] . "<br>";
                    }
                }
            }
            echo "sum All People in Location : " . $locatin_todo[$selectLocate] . " = " . $sumChoice . "<br>";

//        for ($i = 0; $i < 2; $i++) {
//            for ($j = 0; $j < 2; $j++) {
//                for ($k = 0; $k < 16; $k++) {
//                    echo "$i/$j/$k " . $start_age[$i][$j][$k] . " " . $end_age[$i][$j][$k] . " " . $percent[$i][$j][$k] . "<br>";
//                }
//            }
//        }

            $testtime = 0;

            //reset ค่าให้เป็น 0 เพื่อใช่ในการคำนวน
            for ($i = 0; $i < 2; $i ++) {
                for ($j = 0; $j < 2; $j ++) {
                    for ($init = 0; $init < 102; $init++) {
                        $num_age[$i][$j][$init] = 0;
                    }
                }
            }

            for ($gen = 0; $gen < 2; $gen++) {
                for ($are = 0; $are < 2; $are++) {
                    while (true) {
                        if ($choice[$gen][$are] == 0) {
                            $sum = 0;
                            for ($init = 0; $init < 102; $init++) {
                                //echo "age $init = " . $num_age[$gen][$are][$init] . "<br>";
                                $sum += $num_age[$gen][$are][$init];
                            }
                            //echo "sum $gen/$are = $sum <br>";
                            break;
                        }

                        $randVal = rand($min * 100, $max * 100) / 100;

                        for ($rank = 0; $rank < 16; $rank++) {
                            if ($rank == 0) {
                                $percent[$gen][$are][$rank - 1] = 0;
                            }

                            if ($randVal > $percent[$gen][$are][$rank - 1] && $randVal <= $percent[$gen][$are][$rank] && $rank < 15) {   //อายุน้อยกว่า 85
                                $age = rand($start_age[$gen][$are][$rank], $end_age[$gen][$are][$rank]);
                                if ($countPeople[$gen][$are][$age] == 0) { // อายุนั้นเต็ม
                                    //echo " อายุนั้นเต็ม $gen/$are/$age  = " . $countPeople[$gen][$are][$age] . "<br>";
                                    break;
                                } else {//ไม่เต็ม
                                    //echo " อายุนั้นไม่เต็ม $gen/$are/$age  = " . $countPeople[$gen][$are][$age] . "<br>";
                                    $countPeople[$gen][$are][$age] --;
                                    $choice[$gen][$are] --;
                                    $num_age[$gen][$are][$age] ++; //นับอายุเมื่อมีการ add คน
                                    break;
                                }
                                //echo "age out = ".$age."<br>";
                            } else if ($randVal > $percent[$gen][$are][$rank - 1] && $randVal <= 100 && $rank == 15) {  //อายุมากกว่า 85
                                $age = rand($start_age[$gen][$are][$rank], 101);
                                if ($countPeople[$gen][$are][$age] == 0) { // อายุนั้นเต็ม
                                    //echo " อายุนั้นเต็ม $gen/$are/$age  = " . $countPeople[$gen][$are][$age] . "<br>";
                                    break;
                                } else { //ไม่เต็ม
                                    //echo " อายุนั้นไม่เต็ม $gen/$are/$age  = " . $countPeople[$gen][$are][$age] . "<br>";
                                    $countPeople[$gen][$are][$age] --;
                                    $choice[$gen][$are] --;
                                    $num_age[$gen][$are][$age] ++;  //นับอายุเมื่อมีการ add คน
                                    break;
                                }
                            }
                        }
                        //$testtime++;
                    }
                }//for are
            }//for gen
            //$lock_sql = "LOCK TABLES `" . $table . "` WRITE;";
            //mysql_query($lock_sql) or die(mysql_error());
            $zummm = 0;
            for ($gen = 0; $gen < 2; $gen++) {
                for ($are = 0; $are < 2; $are++) {
                    $sum = 0;
                    for ($m = 0; $m < 102; $m++) {
                        if ($m <= 100) {
                            $ageU = $m;
                        }if ($m == 101) {
                            $ageU = "มากกว่า100";
                        }
                        //ช้าตรงนี้
                        //echo "$gender $area num of age ".$age." = ".$num_age[$are][$m]."<br>";
                        $update = "UPDATE `" . $table . "` SET `HH_Status` = 'Head' "
                                . "WHERE Area = '" . $area_todo[$are] . "' "
                                . "AND Age = '" . $ageU . "' AND `Location` = '" . $locatin_todo[$selectLocate] . "' "
                                . "AND `HH_Status` = '' AND `gender` = '" . $gender_todo[$gen] . "' "
                                . "ORDER BY RAND() LIMIT " . $num_age[$gen][$are][$m] . "";
                        //echo $update . "<br>" ;
                        mysql_query($update) or die(mysql_error());
                        //echo "age $init = " . $num_age[$gen][$are][$m] . "<br>";
                        $sum += $num_age[$gen][$are][$m];
                        $zummm += $sum;
                    }//for update
                    echo $gender_todo[$gen] . " " . $area_todo[$are] . " = " . $sum . "<br>";
                }
            }
            //$unlock_sql = "UNLOCK TABLES;";
            //mysql_query($unlock_sql) or die(mysql_error());
        }
        //echo "sumAll = $sum <br>";

        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $hours = (int) ($time / 60 / 60);
        $minutes = (int) ($time / 60) - $hours * 60;
        $seconds = (int) $time - $hours * 60 * 60 - $minutes * 60;
        echo "Zumm = $zummm <br>Update HH_Status Time: $hours hours/ $minutes minutes/ $seconds seconds. <br>";

//------- UPDATE HH_ID IF HH_Status = HEAD (similar) ---------------------------------
        $sql = "SELECT `ID` "
                . "FROM `" . $table . "` "
                . "WHERE `HH_Status` = 'Head' "
                . "ORDER BY `ID` ";
        echo $sql . "<br>";

        $result = mysql_query($sql);
        $HHID = 1;
        while ($row = mysql_fetch_object($result)) {
            $sql_Update = "INSERT INTO `" . $table . "` (ID , HH_ID) "
                    . "VALUES ( " . $row->ID . " , " . $HHID . ") "
                    . "ON DUPLICATE KEY UPDATE HH_ID = VALUES (HH_ID) ";
            echo $sql_Update . "<br>";
            mysql_query($sql_Update) or die(mysql_error());
            $HHID++;
        }
        
        mysql_close($objConnect);

        $time_end1 = microtime(true);
        $time1 = $time_end1 - $time_start;
        $hours1 = (int) ($time1 / 60 / 60);
        $minutes1 = (int) ($time1 / 60) - $hours1 * 60;
        $seconds1 = (int) $time1 - $hours1 * 60 * 60 - $minutes1 * 60;
        echo "Update HH_ID Time: $hours1 hours/ $minutes1 minutes/ $seconds1 seconds. <br>";
        ?>
    </body>
</html>