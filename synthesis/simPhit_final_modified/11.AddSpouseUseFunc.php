<html>
    <head>
        <meta charset="UTF-8">
        <title> Update Spouse of Head in SimPhitlok  </title>
    </head>
    <body>

        <?php
//-------------- Initiation (same)-----------------------------------------------------
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        // <editor-fold defaultstate="collapsed" desc="อ่าน Excel"> 
        /** PHPExcel */
        require_once 'Classes/PHPExcel.php';

        /** PHPExcel_IOFactory - Reader */
        include 'Classes/PHPExcel/IOFactory.php';


        $inputFileName = "11_spouse_Remake.xlsx";
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
        // </editor-fold>

        try {

            // <editor-fold defaultstate="collapsed" desc="CreateConnection"> 
            $time_start = microtime(true);
            $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //changed
            $objDB = mysql_select_db("SimPhit") or die("เลือกฐานข้อมูลไม่ได้");
            $table = "People";
            mysql_query("SET NAMES UTF8");
            mysql_query("SET character_set_results=UTF8");
            mysql_query("SET character_set_client=UTF8");
            mysql_query("SET character_set_connection=UTF8");
            // </editor-fold>
            // <editor-fold defaultstate="collapsed" desc="เก็บค่า Excel"> 
            $row = 0;
            $hh_status = array();   //เก็บค่าจาก excel 
            $marital_status = array();
            $startage = array();
            $endage = array();
            $dif = array();

            foreach ($namedDataArray as $result) {     //เอาค่าจาก excel มาใส่ตัวแปร
                $dif[$row][1] = $result["dif1"];
                $dif[$row][2] = $result["dif2"];
                $dif[$row][3] = $result["dif3"];
                $dif[$row][4] = $result["dif4"];
                $dif[$row][5] = $result["dif5"];
                $dif[$row][6] = $result["dif6"];
                $dif[$row][7] = $result["dif7"];
                $row++;
            }
            // </editor-fold>
            // <editor-fold defaultstate="collapsed" desc="เก็บค่า HHID"> 
            $sqlH = "SELECT `ID` , `HH_ID` "
                    . "FROM `" . $table . "` "
                    . "WHERE `HH_Status` = 'Head' ";
            echo $sqlH . "<br>";
            $resultHH = mysql_query($sqlH);
            while ($row = mysql_fetch_object($resultHH)) {
                $HHID[$row->ID] = $row->HH_ID;
            }
            mysql_free_result($resultHH);
            // </editor-fold>

            $sql_temp = "ALTER TABLE " . $table . " ADD COLUMN SPOUSE_ID INT NOT NULL DEFAULT 0";
            //mysql_query($sql_temp);


            $sqlS = "SELECT `Gender`, `Location`, `Area`, `temp` , `ID` ,`HH_Status`"
                    . "FROM `" . $table . "` "
                    . "WHERE `Gender` = 'male' "
                    . "AND `Marital_Status` = 'สมรส' "
                    . "ORDER BY RAND()";
            echo $sqlS . "<br>";
            $checkSumS = 0;
            $resultS = mysql_query($sqlS);
            while ($row = mysql_fetch_object($resultS)) {
                $tempFac = 0;
                if ($row->HH_Status == "Head") {
                    $tempFac = 1;
                }
                if (!isset($dataS[$tempFac][$row->Location][$row->Area][$row->temp])) {
                    $dataS[$tempFac][$row->Location][$row->Area][$row->temp][0] = $row->ID;
                } else {
                    array_push($dataS[$tempFac][$row->Location][$row->Area][$row->temp], $row->ID);
                }
                $checkSumS++;
            }
            mysql_free_result($resultS);
            echo "Count All Male Married = $checkSumS <br>";

            $startAge = array(15, 20, 25, 30, 35, 40, 45);
            $endAge = array(19, 24, 29, 34, 39, 44, 49);
            $sum = 0;
            for ($i = 0; $i < 7; $i++) {
                $sql = "SELECT ID "
                        . "FROM `" . $table . "` "
                        . "WHERE `Gender` = 'female'"
                        . "AND `Marital_Status` = 'สมรส' "
                        . "AND temp >= " . $startAge[$i] . " "
                        . "AND temp <= " . $endAge[$i] . " ";
                //echo $sql . "<br>";
                $resultC = mysql_query($sql);
                $num_rows = mysql_num_rows($resultC);
                $sum+=$num_rows;
                $sumDiff = 0;
                echo "<br>Count All Female Married $startAge[$i]  - $endAge[$i] = $num_rows <br>";
                echo "Per Diff in Rank = ";
                for ($j = 1; $j <= 7; $j++) {
                    $numOnRank[$i][$j] = round($dif[$i][$j] * $num_rows / 100);
                    $sumDiff += $numOnRank[$i][$j];
                    echo $numOnRank[$i][$j] . " ";
                }
                echo " = $sumDiff <br>";
            }
            echo " Sum = $sum <br>";
            $sql = "SELECT `Gender`,`Location`,`Area`,`temp`,`HH_ID` ,`ID` ,`HH_Status` "
                    . "FROM `" . $table . "` "
                    . "WHERE `Gender` = 'female' "
                    . "AND `Marital_Status` = 'สมรส' "
                    . "AND temp >= 15 "
                    . "AND temp <= 49 "
                    . "ORDER BY RAND()"; //HH_Status DESC , RAND()";

            echo $sql . "<br>";
            $checkSumF = 0;
            $ortherDif = 0;
            $ortherDifff = 0;

            $backUpToRE = array();
            $resultH = mysql_query($sql);
            while ($row = mysql_fetch_object($resultH)) {
                $ageF = $row->temp;
                $locationF = $row->Location;
                $areaF = $row->Area;
                $statF = $row->HH_Status;
                $idFemale = $row->ID;
                $hhidFemale = $row->HH_ID;
                $isHead = FALSE;
                $numSpouse = -1;
                //$idSpouse = -1;
                //หาช่วงอายุ
                $rankF = FindRank($ageF);

                if ($row->HH_Status == "Head") {
                    $facS = 0;
                    $strT = "Head";
                    echo "Is$strT rankF$rankF AgeF$ageF  facS$facS ";
                    $idSpouse = FindSpouse($rankF, $ageF, $facS, $locationF, $areaF);
                } else {
                    $strT = "None";
                    $headOrNot = array(0, 1);
                    $facS = array_rand($headOrNot);
                    echo "Is$strT rankF$rankF AgeF$ageF  facS$headOrNot[$facS] ";
                    $idSpouse = FindSpouse($rankF, $ageF, $headOrNot[$facS], $locationF, $areaF);
                    unset($headOrNot[$facS]);
                    
//                    $facS = 1;
//                    echo "Is$strT rankF$rankF AgeF$ageF  facS$facS ";
//                    $idSpouse = FindSpouse($rankF, $ageF, $facS, $locationF, $areaF);

                    if ($idSpouse == 0) {
                        $facS = array_rand($headOrNot);
                        echo "<br>Is$strT rankF$rankF AgeF$ageF  facS$headOrNot[$facS] ";
                        $idSpouse = FindSpouse($rankF, $ageF, $headOrNot[$facS], $locationF, $areaF);
                    }
                }

                if ($idSpouse == 0) {
                    if (!isset($backUpToRE[0])) {
                        $backUpToRE[0] = array($ageF, $strT, $locationF, $areaF, $row->ID);
                    } else {
                        array_push($backUpToRE, array($ageF, $strT, $locationF, $areaF, $row->ID));
                    }
                    echo " $strT $facS ID=0<br>";
                    $ortherDifff++;
                } else {
                    $sql_InsertF = "INSERT INTO `" . $table . "` (ID , SPOUSE_ID) VALUES "
                            . "( " . $idFemale . " , " . $idSpouse . ") "
                            . "ON DUPLICATE KEY UPDATE SPOUSE_ID = VALUES (SPOUSE_ID)";
                    //echo $sql_Insert . "<br>";
                    mysql_query($sql_InsertF) or die(mysql_error());

                    $sql_InsertM = "INSERT INTO `" . $table . "` (ID , SPOUSE_ID) VALUES "
                            . "( " . $idSpouse . " , " . $idFemale . ") "
                            . "ON DUPLICATE KEY UPDATE SPOUSE_ID = VALUES (SPOUSE_ID)";
                    //echo $sql_Insert . "<br>";
                    mysql_query($sql_InsertM) or die(mysql_error());

                    if ($strT == "Head") {
                        $sql_Insert = "INSERT INTO `" . $table . "` (ID , HH_ID , HH_Status) "
                                . "VALUES ( " . $idSpouse . " , " . $hhidFemale . " ,'Spouse') "
                                . "ON DUPLICATE KEY UPDATE "
                                . "HH_ID = VALUES (HH_ID) , HH_Status = VALUES (HH_Status)";
                        //echo $sql_Insert . "<br>";
                        mysql_query($sql_Insert) or die(mysql_error());
                    } else if ($facS == 1) {
                        $sql_Insert = "INSERT INTO `" . $table . "` (ID , HH_ID , HH_Status) "
                                . "VALUES ( " . $idFemale . " , " . $HHID[$idSpouse] . " ,'Spouse') "
                                . "ON DUPLICATE KEY UPDATE "
                                . "HH_ID = VALUES (HH_ID) , HH_Status = VALUES (HH_Status)";
                        //echo $sql_Insert . "<br>";
                        mysql_query($sql_Insert) or die(mysql_error());
                    }

                    echo " $strT $facS ID=$idSpouse<br>";
                }
                $checkSumF++;
            }
            mysql_free_result($resultH);

            $sql_Update = "UPDATE `" . $table . "` "
                    . "SET `HeadAlone` = 1 "
                    . "WHERE `HH_Status` = 'Head' "
                    . "AND `SPOUSE_ID` = 0 "
                    . "AND `Marital_Status` = 'สมรส' ";
            echo $sql_Update . "<br>";
            mysql_query($sql_Update) or die(mysql_error());


            echo " Total Female in cal $checkSumF  <br><br>";

            // <editor-fold defaultstate="collapsed" desc="Out of Input"> 
            if (FALSE) {
                $testtttttt = 0;
                foreach ($backUpToRE as $key => $valueWho) {
                    $reID = 0;
                    if ($valueWho[1] == "Head") {
                        $facSta = 0;
                        $reID = ReFindSpouse($valueWho[0], $facSta, $valueWho[2], $valueWho[3]); //beta test
                        //$reID = ReFindSpouseFree($facSta, $valueWho[2], $valueWho[3]); //beta test
                    } else {
                        $randomSta = array(0, 1);
                        $facSta = array_rand($randomSta);
                        $reID = ReFindSpouse($valueWho[0], $facSta, $valueWho[2], $valueWho[3]); //beta test
                        //$reID = ReFindSpouseFree($facSta, $valueWho[2], $valueWho[3]); //beta test
                        if ($reID == 0) {
                            unset($randomSta[$facSta]);
                            $facSta = array_rand($randomSta);
                            $reID = ReFindSpouse($valueWho[0], $facSta, $valueWho[2], $valueWho[3]); //beta test
                            //$reID = ReFindSpouseFree($facSta, $valueWho[2], $valueWho[3]); //beta test
                        }
                    }

                    if ($reID != 0) {
                        echo "Re Diff $valueWho[0], $facSta, $valueWho[2], $valueWho[3] REID = $reID<br>";
                    } else {
                        echo "Re Diff $valueWho[0], $facSta, $valueWho[2], $valueWho[3] REID = 0<br>";
                    }

                    $testtttttt++;
                }
                echo "orther Dif result / all = $ortherDif / $ortherDifff<br>";
                echo "$testtttttt<br>";

                echo "<br>";
                echo "<br>";
                $sum = 0;

                foreach ($dataS as $keyH => $valueH) {
                    foreach ($valueH as $keyL => $valueL) {
                        foreach ($valueL as $keyA => $valueA) {
                            ksort($valueA);
                            $sum = 0;
                            foreach ($valueA as $keyAge => $valueAge) {
                                echo "Count Male $keyH $keyL $keyA $keyAge = " . count($valueAge) . "<br>";
                                $sum += count($valueAge);
                            }
                            echo "Count Male $keyH $keyL = $sum <br>";
                        }
                    }
                }
            }
            // </editor-fold>
            // <editor-fold defaultstate="collapsed" desc="Test BUG"> 
//----------------------------------------Check BUG ZONE----------------------------------------------------//
//            $sum = 0;
//            $sumD = 0;
//            foreach ($backUpToRE as $keyST => $valueST) {
//                foreach ($valueST as $keyL => $valueL) {
//                    foreach ($valueL as $keyA => $valueA) {
//                        ksort($valueA);
//                        $sum = 0;
//                        foreach ($valueA as $keyAge => $valueAge) {
//                            echo "Count Female $keyST $keyL $keyA $keyAge = " . count($valueAge) . "<br>";
//                            $rr = FindRank($keyAge);
//
//                            foreach ($numOnRank[$rr] as $keyD => $valueD) {
//                                echo "Count Rank/Dif $rr/$keyD = $valueD <br>";
//                            }
//
//                            echo "Data Male age -7 to +9<br>";
//
//                            for ($h = 0; $h < 2; $h++) {
//                                for ($i = -7; $i < 9; $i++) {
//                                    if ($h == 1) {
//                                        $strH = "Head";
//                                    } else {
//                                        $strH = "None";
//                                    }
//                                    if (isset($dataS[$h][$keyL][$keyA][($keyAge + $i)])) {
//                                        echo "Data Male $strH (ห่าง$i) = " . count($dataS[$h][$keyL][$keyA][($keyAge + $i)]) . "<br>";
//                                    }
//                                }
//                            }
//                        }
//                        //echo "Count Female $keyST $keyL = $sum <br>";
//                    }
//                }
//            }
//            echo "<br>";
//            echo "<br>";
//            $sum = 0;
//            foreach ($dataS as $keyH => $valueH) {
//                //$dataS[$isHead][$loca][$area][$arrSpouseAge[$randWho]]
//                foreach ($valueH as $keyL => $valueL) {
//                    foreach ($valueL as $keyA => $valueA) {
//                        ksort($valueA);
//                        $sum = 0;
//                        foreach ($valueA as $keyAge => $valueAge) {
//                            echo "Count Male $keyH $keyL $keyA $keyAge = " . count($valueAge) . "<br>";
//                            $sum += count($valueAge);
//                        }
//                        echo "Count Male $keyH $keyL = $sum <br>";
//                    }
//                }
//            }
//
//            echo "<br>";
//            echo "<br>";
//            $sum = 0;
//            foreach ($numOnRank as $keyR => $valueR) {
//                foreach ($valueR as $keyD => $valueD) {
//                    echo "Count Rank/Dif $keyR/$keyD = $valueD <br>";
//                    $sum += $valueD;
//                }
//            }
//            echo "Count INPUT Rank\Dif $sum <br>";
            // </editor-fold>

            $time_end1 = microtime(true);
            $time1 = $time_end1 - $time_start;
            $hours1 = (int) ($time1 / 60 / 60);
            $minutes1 = (int) ($time1 / 60) - $hours1 * 60;
            $seconds1 = (double) $time1 - $hours1 * 60 * 60 - $minutes1 * 60;
            echo "Time:  $hours1 hours/ $minutes1  minutes/ $seconds1 seconds. <br>";
        } catch (Exception $ex) {
            echo 'exception: ', $ex->getMessage(), "<br>";
            mysql_close($objConnect);
        }

        // <editor-fold defaultstate="collapsed" desc="Function Area"> 
        function FindRank($ageD) {
            $rankD = 0;
            global $startAge, $endAge;
            for ($i = 0; $i < 7; $i ++) {
                if ($ageD >= $startAge[$i] && $ageD <= $endAge[$i]) {
                    $rankD = $i;
                    break;
                }
            }
            return $rankD;
        }

        function FindSpoueAtAge($arrAge, $isHead, $age, $loca, $area) {
            global $dataS;
            $arrResult = array();
            for ($i = 0; $i < count($arrAge); $i++) {
                $temp = $age + $arrAge[$i];
                if (isset($dataS[$isHead][$loca][$area][$temp])) {
                    $count = count($dataS[$isHead][$loca][$area][$temp]);
                    if ($count > 0) {
                        array_push($arrResult, $temp);
                    }
                }
            }
            return $arrResult;
        }

        function currentDiff($rank) {
            global $numOnRank;
            $arrResult = array();
            for ($i = 1; $i <= 7; $i++) {
                if (isset($numOnRank[$rank][$i])) {
                    array_push($arrResult, $i);
                }
            }
            return $arrResult;
        }

        function FindArrayOfDiff($numDiff) {
            switch ($numDiff) {
                case 1:
                    $ageDiff = array(-5, -6, -7);
                    break;
                case 2:
                    $ageDiff = array(-3, -4);
                    break;
                case 3:
                    $ageDiff = array(-1, -2);
                    break;
                case 4:
                    $ageDiff = array(0);
                    break;
                case 5:
                    $ageDiff = array(1, 2);
                    break;
                case 6:
                    $ageDiff = array(3, 4);
                    break;
                case 7:
                    //$ageDiff = array(5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
                    $ageDiff = array(5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15);
                    break;
                default:
                    $ageDiff = array();
                    break;
            }
            return $ageDiff;
        }

        function FindSpouse($rank, $age, $isHead, $loca, $area) {
            $canDiff = currentDiff($rank);

            $total = count($canDiff);
            if ($total == 0) {
                echo "";
            }
            global $dataS, $numOnRank;
            for ($i = 0; $i < $total; $i++) {
                $index = array_rand($canDiff);
                $doDiff = $canDiff[$index];
                $arrDiffAge = FindArrayOfDiff($doDiff);
                $arrSpouseAge = FindSpoueAtAge($arrDiffAge, $isHead, $age, $loca, $area);
                if ($arrSpouseAge != array()) {
                    $randWho = array_rand($arrSpouseAge);
                    break;
                } else {
                    unset($canDiff[$index]);
                    continue;
                }
            }

            if (isset($randWho)) {
                $whoId = array_rand($dataS[$isHead][$loca][$area][$arrSpouseAge[$randWho]]);
                if (!isset($dataS[$isHead][$loca][$area][$arrSpouseAge[$randWho]][$whoId])) {
                    echo"";
                    //$arrSpouseAge = FindSpoueAtAge($arrDiffAge, $isHead, $age, $loca, $area);
                }
                $id = $dataS[$isHead][$loca][$area][$arrSpouseAge[$randWho]][$whoId];
                $numOnRank[$rank][$doDiff] --;
                echo " " . $loca . " " . $area;
                echo " ageS" . $arrSpouseAge[$randWho];
                echo " Current" . count($dataS[$isHead][$loca][$area][$arrSpouseAge[$randWho]]);
                echo " r/d $rank/$doDiff C" . $numOnRank[$rank][$doDiff];
                unset($dataS[$isHead][$loca][$area][$arrSpouseAge[$randWho]][$whoId]);

                if (count($dataS[$isHead][$loca][$area][$arrSpouseAge[$randWho]]) == 0) {
                    unset($dataS[$isHead][$loca][$area][$arrSpouseAge[$randWho]]);
                }

                if ($numOnRank[$rank][$doDiff] == 0) {
                    unset($numOnRank[$rank][$doDiff]);
                }

                return $id;
            } else {
                echo " " . $loca . " " . $area;
                echo " ageS0";
                echo " Current" . count($dataS[$isHead][$loca][$area]);
                echo " r/d $rank/?$doDiff C" . $numOnRank[$rank][$doDiff];
                //$testRe = FindSpouse($rank, $age, $isHead, $loca, $area);
                return 0;
            }
        }

        function ReFindSpouse($age, $isHead, $loca, $area) {
            global $dataS, $ortherDif;
            $id = 0;
            $result = array();
            for ($i = -15; $i < 15; $i++) {
                if (isset($dataS[$isHead][$loca][$area][($age + $i)]) && count($dataS[$isHead][$loca][$area][($age + $i)]) > 0) {
                    array_push($result, ($age + $i));
                } else {
                    unset($dataS[$isHead][$loca][$area][($age + $i)]);
                }
            }

            if ($result != array()) {
                $whoAge = array_rand($result);
                $who = array_rand($dataS[$isHead][$loca][$area][$result[$whoAge]]);
                $id = $dataS[$isHead][$loca][$area][$result[$whoAge]][$who];
                unset($dataS[$isHead][$loca][$area][$result[$whoAge]][$who]);
            }
            if ($id != 0) {
                $ortherDif++;
                return $id;
            } else {
                return 0;
            }
        }

        function ReFindSpouseFree($isHead, $loca, $area) {
            global $dataS, $ortherDif;
            $id = 0;
            $whoAge = array_rand($dataS[$isHead][$loca][$area]);

            if (isset($dataS[$isHead][$loca][$area][$whoAge])) {

                $who = array_rand($dataS[$isHead][$loca][$area][$whoAge]);
                $id = $dataS[$isHead][$loca][$area][$whoAge][$who];
                unset($dataS[$isHead][$loca][$area][$whoAge][$who]);

                if (count($dataS[$isHead][$loca][$area][$whoAge]) == 0) {
                    unset($dataS[$isHead][$loca][$area][$whoAge]);
                }
            }

            if ($id != 0) {
                $ortherDif++;
                return $id;
            } else {
                return 0;
            }
        }

        // </editor-fold>
        ?>
    </body>
</html>