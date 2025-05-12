<html>
    <head>
        <meta charset="UTF-8">
        <title> Update Add Child Have Parent  </title>
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


        $inputFileName = "14_spacing_birth.xlsx";
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

        try {
            $time_start = microtime(true);
            $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //changed
            $objDB = mysql_select_db("SimPhit") or die("เลือกฐานข้อมูลไม่ได้");
            $table = "People";
            mysql_query("SET NAMES UTF8");
            mysql_query("SET character_set_results=UTF8");
            mysql_query("SET character_set_client=UTF8");
            mysql_query("SET character_set_connection=UTF8");

            
            $diffVal = array(
                0 => 1,
                1 => 2,
                2 => array(3, 4),
                3 => array(5, 7)
            );

            foreach ($namedDataArray as $result) {
                $perRand[0] = 0;
                $perRand[1] = floatval($result["1"]);
                $perRand[2] = floatval($result["2"]);
                $perRand[3] = floatval($result["3-4"]);
                $perRand[4] = floatval($result["5-7"]);
            }

            //count all child need update
            $sql_select = "SELECT COUNT(*) AS allch "
                    . "FROM `" . $table . "` "
                    . "WHERE `temp` < 18 ";
            $temp_sql = mysql_query($sql_select);
            $temp_assoc = mysql_fetch_assoc($temp_sql);
            $countChild = $temp_assoc['allch'];
            echo $countChild . "(countFromBase) <br>";

            //calFromStatic
            $withwho = array();
            $withWho[0] = round($namedDataArray[0]["family"] * $countChild / 100);  //family
            $withWho[1] = round($namedDataArray[0]["alone"] * $countChild / 100);   //alone
            $withWho[2] = round($namedDataArray[0]["mom"] * $countChild / 100);     //mom 
            $withWho[3] = round($namedDataArray[0]["dad"] * $countChild / 100);     //father 

            $sumAll = 0;

            foreach ($withWho as $key => $value) {
                $sumAll += $value;
                echo $key . " ," . $value . "<br>";
            }
            echo "check sum " . $sumAll . "<br>";
            mysql_free_result($temp_sql);
            
///////////////////////////////////////////Mom NOT Married section/////////////////////////////////////////////////////////
            
            //หาชายไม่สมรส
            if (TRUE) {
                $sql_dad = "SELECT `Location` , `Area` , `ID` , `temp` "
                        . "FROM `" . $table . "` "
                        . "WHERE `Gender` = 'male' "
                        . "AND `Marital_Status` != 'สมรส' "
                        . "ORDER BY `Location` , `Area` , `temp`";
                echo $sql_dad . "<br>";

                $resultD = mysql_query($sql_dad);

                $dataDad = array();
                $sumD = 0;
                while ($row = mysql_fetch_object($resultD)) {
                    if (!isset($counterD[$row->Location][$row->Area][$row->temp])) {
                        $counterD[$row->Location][$row->Area][$row->temp] = 0;
                    }
                    $dataDad[$row->Location][$row->Area][$row->temp][$counterD[$row->Location][$row->Area][$row->temp]] = $row->ID;
                    $counterD[$row->Location][$row->Area][$row->temp] ++;
                    $sumD++;
                }
                echo "ชายไม่สมรส $sumD <br>";
                unset($counterD);
                mysql_free_result($resultD);
            }

            //หาคนไม่สมรส มาเก็บใส่ array
            if (TRUE) {
                $sql = "SELECT `Location` , `Area` , `ID` , `temp` "
                        . "FROM `" . $table . "` "
                        . "WHERE `Gender` = 'Female' "
                        . "AND `Marital_Status` != 'สมรส' "
                        . "AND `Number_children` = 0 "
                        . "AND temp > 17 "
                        . "AND UNKNOWN_CHILD != 1 "
                        . "ORDER BY `Location` , `Area` , `temp`";
                echo $sql . "<br>";

                $resultS = mysql_query($sql);

                $dataSingleF = array();
                $sumS = 0;
                while ($row = mysql_fetch_object($resultS)) {
                    if (!isset($counterS[$row->Location][$row->Area][$row->temp])) {
                        $counterS[$row->Location][$row->Area][$row->temp] = 0;
                    }
                    $dataSingleF[$row->Location][$row->Area][$row->temp][$counterS[$row->Location][$row->Area][$row->temp]] = $row->ID;
                    $counterS[$row->Location][$row->Area][$row->temp] ++;
                    $sumS++;
                }
                echo "หญิงที่สถานะไม่เท่ากับสมรส $sumS คน<br>";
                mysql_free_result($resultS);
            }

            //เลือกลูกคนแรกของ ไม่สมรส
            if (TRUE) {

                $chlid_init = "SELECT `Location` , `Area` , `ID` , `temp` "
                        . "FROM `" . $table . "` "
                        . "WHERE `temp` <= 17 AND `HaveParentID` = 0 "
                        . "AND ( `FirstChild` = 0 OR `FirstChild` = -1 ) "
                        . "ORDER BY `Location` , `Area` , `temp`";
                echo $chlid_init . "<br>";

                $resultC = mysql_query($chlid_init);
                //$counter = 0;
                $dataChlid = array();
                $sum = 0;
                while ($row = mysql_fetch_object($resultC)) {
                    if (!isset($counter[$row->Location][$row->Area][$row->temp])) {
                        $counter[$row->Location][$row->Area][$row->temp] = 0;
                    }
                    $dataChlid[$row->Location][$row->Area][$row->temp][$counter[$row->Location][$row->Area][$row->temp]] = $row->ID;
                    $counter[$row->Location][$row->Area][$row->temp] ++;
                    $sum++;
                }
                echo "$sum (all Child 2nd )<br>";
                mysql_free_result($resultC);

                //All mom update first child age < 18

                $sql_marraymom = "SELECT `ID` , `Location` , `Area`, `temp` , "
                        . "`Age_At_First_Birth` , `Number_children`  , `Marital_Status` "
                        . "FROM `" . $table . "` "
                        . "WHERE `Number_children` != 0 "
                        . "AND `Gender` = 'female' "
                        . "AND `temp` - `Age_At_First_Birth` < 18 "
                        . "AND ( `Marital_Status` != 'สมรส' OR `HeadAlone` = 1 ) "
                        . "ORDER BY RAND() ";
                echo $sql_marraymom . "<br>";

                $result = mysql_query($sql_marraymom);

                $collect = 0;
                $nochild = 0;

                while ($row = mysql_fetch_object($result)) {
                    $ageChildF = $row->temp - $row->Age_At_First_Birth;

                    //random ลูกคนแรกในเขตเดียวกับแม่
                    $indexF = array_rand($dataChlid[$row->Location][$row->Area][$ageChildF]);

                    //update id แม่ให้ลูกถ้ามีลูกในอายุที่ต้องการ
                    if (isset($indexF)) {
                        $idChlid = $dataChlid[$row->Location][$row->Area][$ageChildF][$indexF];
                        $sql_Update = "INSERT INTO `" . $table . "` (ID , FirstChild) "
                                . "VALUES ( " . $idChlid . " , " . $row->ID . ") "
                                . "ON DUPLICATE KEY UPDATE FirstChild = VALUES (FirstChild) ";
                        mysql_query($sql_Update) or die(mysql_error());
                        //echo $sql_Update . "<br>";
                        unset($dataChlid[$row->Location][$row->Area][$ageChildF][$indexF]);
                    } else { //กรณีลูกคนแรกที่ถูกแม่เลือกหมด แม่จะเป็น firstchild = -1
                        $nochild++;

                        $sql_Update = "INSERT INTO `" . $table . "` (ID , FirstChild) "
                                . "VALUES ( " . $row->ID . " , '-1' ) "
                                . "ON DUPLICATE KEY UPDATE FirstChild = VALUES (FirstChild) ";
                        mysql_query($sql_Update) or die(mysql_error());
                    }
                    $collect++;
                    unset($indexF);
                    unset($idChlid);
                }

                echo "$collect/$nochild   ( Sum/Error ---> Mom NOT MARRY < 18)<br>";
                mysql_free_result($result);
            }

            //เลือกลูกคนแรกเก็บใส่array
            if (TRUE) {
                //select first child for use update first child
                $str_first = "SELECT `FirstChild` , `ID` FROM `" . $table . "` WHERE `FirstChild` > 0";
                echo $str_first . "<br>";
                $resulFirst = mysql_query($str_first) or die(mysql_error());
                $countFirstChild = 0;
                while ($saveF = mysql_fetch_object($resulFirst)) {
                    $keepFirst[$saveF->FirstChild] = $saveF->ID;
                    $countFirstChild++;
                }
                mysql_free_result($resulFirst);
                echo "First Child From Count" . $countFirstChild . "<br>";
            }

            //หาเด็กอายุต่ำกว่า 18 มาใส่array
            if (TRUE) {

                unset($dataChlid);
                unset($counter);

                $chlid_init = "SELECT `Location` , `Area` , `ID` , `temp` "
                        . "FROM `" . $table . "` "
                        . "WHERE `temp` <= 17 AND `HaveParentID` = 0 "
                        . "AND ( `FirstChild` = 0 OR `FirstChild` = -1 ) "
                        . "ORDER BY `Location` , `Area` , `temp`";
                echo $chlid_init . "<br>";

                $resultC = mysql_query($chlid_init);
                //$counter = 0;
                $dataChlid = array();
                $sum = 0;
                while ($row = mysql_fetch_object($resultC)) {
                    if (!isset($counter[$row->Location][$row->Area][$row->temp])) {
                        $counter[$row->Location][$row->Area][$row->temp] = 0;
                    }
                    $dataChlid[$row->Location][$row->Area][$row->temp][$counter[$row->Location][$row->Area][$row->temp]] = $row->ID;
                    $counter[$row->Location][$row->Area][$row->temp] ++;
                    $sum++;
                }
                echo "$sum (all Child 2nd )<br>";
                mysql_free_result($resultC);

                //select first child for use update first child
                $str_first = "SELECT `FirstChild` , `ID` FROM `" . $table . "` WHERE `FirstChild` > 0";
                echo $str_first . "<br>";
                $resulFirst = mysql_query($str_first) or die(mysql_error());
                $countFirstChild = 0;
                while ($saveF = mysql_fetch_object($resulFirst)) {
                    $keepFirst[$saveF->FirstChild] = $saveF->ID;
                    $countFirstChild++;
                }
                mysql_free_result($resulFirst);
                echo "First Child From Count" . $countFirstChild . "<br>";
            }

            //อ่านค่าของ spouse
            if (TRUE) {
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
            }

            //select mom not marry for withWho[2],[3]
            if (TRUE) {
                //free variable for memory
                unset($idMom);
                unset($locatMom);
                unset($areaMom);
                unset($ageMom);
                unset($ageFBMom);
                unset($numCMom);
                unset($statMom);

                $sql_marraymom = "SELECT `ID` , `Location` , `Area`, `temp` , "
                        . "`Age_At_First_Birth` , `Number_children`  , `Marital_Status` "
                        . "FROM `" . $table . "` "
                        . "WHERE `Number_children` != 0 "
                        . "AND `Gender` = 'female' "
                        . "AND ( `Marital_Status` != 'สมรส' OR `HeadAlone` = 1 ) "
                        . "AND `FirstChild` != -1 "
                        . "ORDER BY RAND()";
                echo $sql_marraymom . "<br>";

                $result = mysql_query($sql_marraymom)or die(mysql_error());

                $countMomM = 0;
                while ($row = mysql_fetch_object($result)) {
                    $idMom[$countMomM] = $row->ID;
                    $locatMom[$countMomM] = $row->Location;
                    $areaMom[$countMomM] = $row->Area;
                    $ageMom[$countMomM] = $row->temp;
                    $ageFBMom[$countMomM] = $row->Age_At_First_Birth;
                    $numCMom[$countMomM] = $row->Number_children;
                    $statMom[$countMomM] = $row->Marital_Status;
                    //$ageChild = $ageMom - $ageFBMom;
                    $countMomM++;
                }
                echo " หญิงไม่สมรส $countMomM คน <br><br>";
                mysql_free_result($result);


                //Loop marry mom  
                $saveLastMom = 0;
                $totalChild = 0;
                $totalNewcal = 0;
                for ($mm = 0; $mm < $countMomM; $mm++) {
                    //init value
                    $checkLess = false;
                    $checkNull = false;
                    $countNull = 0;
                    $firstLess = -1;
                    $totalQry = "";

                    $fixMaxChild = 0; ////////////////////////////////////////////////////////////
                    //initial value age of child
                    for ($a = 0; $a < 10; $a++) {
                        $ageChild[$a] = -1;
                        $idchlid[$a] = -1;
                    }

                    //find rank age
                    if (($numCMom[$mm] - 1) == 0) { //ลูกคนเดียว
                        $test = 1;
                    } else {
                        $test = $numCMom[$mm] - 1;
                    }
                    $avgRankAge = (($ageMom[$mm] - $ageFBMom[$mm]) / $test);

                    //can random 0 - 7 for diff age
                    if ($avgRankAge >= $diffVal[3][1]) {

                        for ($nc = 0; $nc < $numCMom[$mm]; $nc++) {
                            $index = null;
                            $idchlid[$nc] = -1;
                            //at first child
                            if ($nc == 0) {
                                $ageChild[$nc] = $ageMom[$mm] - $ageFBMom[$mm];

                                //less than 18
                                if ($ageChild[$nc] < 18) {
                                    $checkLess = true;
                                    $idchlid[$nc] = $keepFirst[$idMom[$mm]];
                                    $fixMaxChild++;
                                    if ($idchlid[$nc] != null) {
                                        unset($keepFirst[$idMom[$mm]]);
                                    }
                                    //$sql_body.= " ( " . $idchlid[$nc] . " , " . $idMom[$mm] . ") ,";
                                }
                                //if more than 18 go next child
                                else {
                                    continue;
                                }
                            }
                            //at other child
                            else {
                                //random diff value from before child
                                $randVal = rand(0, 10000) / 100;

                                //find case
                                for ($rank = 1; $rank < 5; $rank++) {
                                    if ($randVal >= $perRand[$rank - 1] && $randVal < $perRand[$rank]) {
                                        //cho "rank = " . $rank . " ";
                                        break;
                                    }
                                }

                                switch ($rank) {
                                    case 1:
                                        $ageChild[$nc] = $ageChild[$nc - 1] - $diffVal[0];
                                        break;
                                    case 2:
                                        //$tempCase2 = rand($diffVal[1][0], $diffVal[1][1]);
                                        $ageChild[$nc] = $ageChild[$nc - 1] - $diffVal[1];
                                        break;
                                    case 3:
                                        $tempCase3 = rand($diffVal[2][0], $diffVal[2][1]);
                                        $ageChild[$nc] = $ageChild[$nc - 1] - $tempCase3;
                                        break;
                                    case 4:
                                        $tempCase4 = rand($diffVal[3][0], $diffVal[3][1]);
                                        $ageChild[$nc] = $ageChild[$nc - 1] - $tempCase4;
                                        break;
                                    default:
                                        break;
                                }

                                //now we have valse age 
                                //if less 18 we check empty
                                //เลื่อนตามแบบ ยังไม่หลุด rank

                                if ($ageChild[$nc] < 18 && $ageChild[$nc] >= 0) {
                                    $checkLess = true;
                                    switch ($rank) {
                                        case 1:
                                            //เลื่อนไม่ได้
                                            if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]])) {
                                                $index = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]]);
                                            }
                                            break;
                                        case 2:
                                            //เลื่อนไม่ได้
                                            if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]])) {
                                                $index = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]]);
                                            }
                                            break;
                                        case 3:
                                            //เลื่อนได้
                                            if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]])) {
                                                $index = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]]);
                                            } else {
                                                for ($a = $diffVal[2][0]; $a <= $diffVal[2][1]; $a++) {
                                                    if ($a == $tempCase3) {
                                                        continue;
                                                    }
                                                    $ageChild[$nc] = $ageChild[$nc - 1] - $a;
                                                    if ($ageChild[$nc] >= 0 && $ageChild[$nc] < 18) {
                                                        if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]])) {
                                                            $index = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]]);
                                                        }
                                                    }
                                                    if ($index != NULL) {
                                                        break;
                                                    }
                                                }
                                            }
                                            break;
                                        case 4:
                                            //เลื่อนได้5,6,7
                                            if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]])) {
                                                $index = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]]);
                                            } else {
                                                for ($a = $diffVal[3][0]; $a <= $diffVal[3][1]; $a++) {
                                                    if ($a == $tempCase4) {
                                                        continue;
                                                    }
                                                    $ageChild[$nc] = $ageChild[$nc - 1] - $a;
                                                    if ($ageChild[$nc] >= 0 && $ageChild[$nc] < 18) {
                                                        if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]])) {
                                                            $index = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]]);
                                                        }
                                                    }
                                                    if ($index != NULL) {
                                                        break;
                                                    }
                                                }
                                            }
                                            break;
                                        default:
                                            break;
                                    }

                                    //กรณีเลื่อนในrank ยัง null
                                    if ($index == NULL) {
                                        $checkNull = true;
                                        $countNull++;
                                        $ageChild[$nc] = -2;
                                    } else {
                                        if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]][$index])) {
                                            //echo "test";
                                            if ($fixMaxChild < 11) {
                                                $idchlid[$nc] = $dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]][$index];
                                                unset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]][$index]);
                                                $fixMaxChild++;
                                            }
                                        }
                                        //$idchlid[$nc] = $dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]][$index];
                                        //unset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]][$index]);
                                    }
                                }
                            }
                        }

                        //now we have all age 
                        //แต่อาจจะมีอายุที่ null ต้องเลือนแบบ เอาใครก็ได้ที่อายุ น้อยกว่าลูกคนแรก(ที่น้อยกว่า18)
                        //และไม่ซ้ำใครเลย
                        //ถ้ามีอายุน้อยกว่า18
                        if ($checkLess && $ageChild[0] != -1) {
                            //check firstless at index??
                            for ($fl = 0; $fl < $numCMom[$mm]; $fl++) {
                                if ($ageChild[$fl] < 18 && $ageChild[$fl] >= 0) {
                                    $firstLess = $fl;
                                    break;
                                }
                            }

                            //วนหาว่ามีคนที่หาอายุไม่ได้ไหม
                            for ($c = 0; $c < $numCMom[$mm]; $c++) {
                                $index_final = null;
                                if ($ageChild[$c] <= -2) {  //ถ้ามี
                                    if ($firstLess != -1) { //กรณีมีลูกคนที่อายุน้อยกว่า18มาก่อน
                                        for ($d = $ageChild[$firstLess]; $d >= 0; $d--) {
                                            if (!in_array($d, $ageChild)) {//check valse in array if have return true
                                                if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d])) {
                                                    $index_final = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d]);
                                                }
                                                if ($index_final != NULL) {
                                                    if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final])) {
                                                        // "$d , $index_final";
                                                        if ($fixMaxChild < 11) {
                                                            $idchlid[$c] = $dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final];
                                                            $ageChild[$c] = $d;
                                                            unset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final]);
                                                            $fixMaxChild++;
                                                        }
                                                        break;
                                                    }
                                                    //$idchlid[$c] = $dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final];
                                                    //unset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final]);
                                                }
                                            }
                                        }
                                    } else {    //กรณีไม่มีลูกคนที่อายุน้อยกว่า 18 มาก่อน
                                        for ($d = 17; $d >= 0; $d--) {
                                            if (!in_array($d, $ageChild)) {//check valse in array if have return true
                                                if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d])) {
                                                    $index_final = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d]);
                                                }
                                                if ($index_final != NULL) {
                                                    if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final])) {
                                                        //echo "$d , $index_final";
                                                        if ($fixMaxChild < 11) {
                                                            $idchlid[$c] = $dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final];
                                                            $ageChild[$c] = $d;
                                                            unset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final]);
                                                            $fixMaxChild++;
                                                        }
                                                        break;
                                                    }
                                                    //$idchlid[$c] = $dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final];
                                                    //unset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final]);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //can random age 0 - $avgRankAge for diff age
                    else {
                        for ($nc = 0; $nc < $numCMom[$mm]; $nc++) {
                            $index = null;
                            $idchlid[$nc] = -1;
                            //at first child
                            if ($nc == 0) {
                                $ageChild[$nc] = $ageMom[$mm] - $ageFBMom[$mm];

                                //less than 18
                                if ($ageChild[$nc] < 18) {
                                    $checkLess = true;
                                    $idchlid[$nc] = $keepFirst[$idMom[$mm]];
                                    $fixMaxChild++;
                                    //$sql_body.= " ( " . $idchlid[$nc] . " , " . $idMom[$mm] . ") ,";
                                }
                                //if more than 18 go next child
                                else {
                                    continue;
                                }
                            }
                            //at other child
                            else {
                                //find rank age
                                //ปัดเศษเพื่อเอาไปใช้ในการหาอายุ
                                $avgRankAge = floor($avgRankAge);
                                $diifAge = rand(1, $avgRankAge);
                                $ageChild[$nc] = $ageChild[$nc - 1] - $diifAge;
                                //ถ้าน้อยกว่า 18
                                if ($ageChild[$nc] < 18 && $ageChild[$nc] >= 0) {
                                    $checkLess = true;
                                    if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]])) {
                                        $index = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]]);
                                    }
                                    //ถ้าเต็ม
                                    if ($index == NULL) {
                                        for ($a = 1; $a <= $avgRankAge; $a++) {
                                            //อายุนี้เช็คไปแล้วข้ามไป
                                            if ($a == ($ageChild[$nc - 1] - $diifAge)) {
                                                continue;
                                            }
                                            $ageChild[$nc] = $ageChild[$nc - 1] - $a;
                                            //ถ้าน้อยกว่า 18
                                            if ($ageChild[$nc] >= 0 && $ageChild[$nc] < 18) {
                                                if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]])) {
                                                    $index = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]]);
                                                }
                                            }
                                            if ($index != NULL) {
                                                break;
                                            }
                                        }
                                    }
                                    //กรณีเลื่อนในavgAge ยัง null
                                    if ($index == NULL) {
                                        $checkNull = true;
                                        $countNull++;
                                        $ageChild[$nc] = -2;
                                    } else {
                                        if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]][$index])) {
                                            if ($fixMaxChild < 11) {
                                                $idchlid[$nc] = $dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]][$index];
                                                unset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$ageChild[$nc]][$index]);
                                                $fixMaxChild++;
                                            }
                                        } else {
                                            $checkNull = true;
                                            $countNull++;
                                            $ageChild[$nc] = -2;
                                        }
                                    }
                                }
                            }//else ลูกคนอื่นๆ
                        }

                        //now we have all age 
                        //แต่อาจจะมีอายุที่ null ต้องเลือนแบบ เอาใครก็ได้ที่อายุ น้อยกว่าลูกคนแรก(ที่น้อยกว่า18)
                        //และไม่ซ้ำใครเลย
                        //ถ้ามีอายุน้อยกว่า18
                        if ($checkLess && $ageChild[0] != -1) {
                            //check firstless at index??
                            for ($fl = 0; $fl < $numCMom[$mm]; $fl++) {
                                if ($ageChild[$fl] < 18 && $ageChild[$fl] >= 0) {
                                    $firstLess = $fl;
                                    break;
                                }
                            }

                            //วนหาว่ามีคนที่ หาอายุไม่ได้ไหม
                            $index_final = null;
                            for ($c = 0; $c < $numCMom[$mm]; $c++) {
                                if ($ageChild[$c] <= -2) {//ถ้ามี
                                    if ($firstLess != -1) {//กรณีมีลูกคนที่อายุน้อยกว่า18มาก่อน
                                        for ($d = $ageChild[$firstLess]; $d >= 0; $d--) {
                                            if (!in_array($d, $ageChild)) {//check valse in array if have return true
                                                if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d])) {
                                                    $index_final = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d]);
                                                }
                                                if ($index_final != NULL) {
                                                    if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final])) {
                                                        if ($fixMaxChild < 11) {
                                                            $idchlid[$c] = $dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final];
                                                            $ageChild[$c] = $d;
                                                            unset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final]);
                                                            $fixMaxChild++;
                                                        }
                                                        break;
                                                    }
                                                    //$idchlid[$c] = $dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final];
                                                    //unset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final]);
                                                    //break;
                                                }
                                            }
                                        }
                                    } else {//กรณีไม่มีลูกคนที่อายุน้อยกว่า 18
                                        for ($d = 17; $d >= 0; $d--) {
                                            if (!in_array($d, $ageChild)) {//check valse in array if have return true
                                                if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d])) {
                                                    $index_final = array_rand($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d]);
                                                }
                                                if ($index_final != NULL) {
                                                    if (isset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final])) {
                                                        if ($fixMaxChild < 11) {
                                                            $idchlid[$c] = $dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final];
                                                            $ageChild[$c] = $d;
                                                            unset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final]);
                                                            $fixMaxChild++;
                                                        }
                                                        break;
                                                    }
                                                    //$idchlid[$c] = $dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final];
                                                    //unset($dataChlid[$locatMom[$mm]][$areaMom[$mm]][$d][$index_final]);
                                                    //break;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }//else avgAge < 7

                    $countLess = 0;
                    $sql_insertHead = "INSERT INTO `" . $table . "` (ID , HaveParentID) VALUES";
                    $sql_body = "";
                    //print show info
                    if (true) {
                        if ($checkLess) {
                            //echo "Withwho[2]$withWho[2] At ID | $mm|$idMom[$mm] has $ageMom[$mm] - $ageFBMom[$mm] child $numCMom[$mm] : ";
                            foreach ($ageChild as $value) {
                                //if ($value > -1) {
                                //echo $value . " ";
                                //}
                            }
                            //echo"<br>";
                        }
                    }
                    $checkLow = false;
                    //หาว่ามีอายุน้อยกว่า 18 กี่คน
                    foreach ($idchlid as $key => $value) {
                        if ($value >= 0 && $value != null) {
                            $checkLow = true;
                            //echo $value . " ";
                            $sql_body.= " ( " . $value . " , " . $idMom[$mm] . ") ,";
                            $countLess++;
                            $totalChild++;
                        }
                    }

                    //เก็บแม่ที่หาลูกไม่ได้ไปคิดหลังจบหาปกติ
                    if (!$checkLow) {
                        $dataMom[$totalNewcal] = array(
                            "MID" => $idMom[$mm],
                            "MLocate" => $locatMom[$mm],
                            "MArea" => $areaMom[$mm],
                            "MNumc" => $numCMom[$mm],
                            "AgeChild" => $ageChild,
                            "MAge" => $ageMom[$mm]
                        );
                        $totalNewcal ++;
                    }


                    //สร้าง sql
                    if ($sql_body != "") {
                        $sql_body = rtrim($sql_body, ",");
                        $sql_insertTail = "ON DUPLICATE KEY UPDATE HaveParentID = VALUES (HaveParentID)";
                        $totalQry = $sql_insertHead . $sql_body . $sql_insertTail;
                        //echo $totalQry . "<br>";
                    }

                    //เช็คว่า อยู่กับครอบครัวเต็มหรือยัง
                    if (($withWho[2] - $countLess) >= 0 && $checkLess) {
                        //update
                        mysql_query($totalQry);
                        echo $fixMaxChild . " ";
                        echo $totalQry . "<br>";

                        $withWho[2] = $withWho[2] - $countLess;
                    } else if (($withWho[2] - $countLess) < 0 && $checkLess) {
                        $saveLastMom = $mm;
                        $totalChild -= $countLess;
                        //echo "withWho[2](OnlyMOM) = $withWho[2] <br>";
                        break;
                    }
                }


                echo "ไม่มีอายุต่ำกว่า 18  $totalNewcal คน";
                echo "totalmomInCal  = $mm คน<br>";
                echo "totalmomInNOTMarry  = $countMomM คน<br>";
                echo "totalChild  = $totalChild คน<br>";

//                $sql_Update = "INSERT INTO `" . $table . "` (ID , FirstChild) "
//                        . "VALUES ( " . $value . " , '0' ) "
//                        . "ON DUPLICATE KEY UPDATE FirstChild = VALUES (FirstChild) ";
//                //echo $sql_Update . "<br>";
                //mysql_query($sql_Update) or die(mysql_error());

                $countSingleChild = 0;

                $row = 0;
                $indexD = null;

                foreach ($namedDataArray as $result) {     //เอาค่าจาก excel มาใส่ตัวแปร
                    $startage[$row][2] = $result["StartAge"];
                    if ($result["EndAge"] == "มากกว่า100") {
                        $endage[$row][3] = 101;
                    } else {
                        $endage[$row][3] = $result["EndAge"];
                    }
                    $dif[$row][4] = floatval($result["dif1"]);
                    $dif[$row][5] = $result["dif2"];
                    $dif[$row][6] = $result["dif3"];
                    $dif[$row][7] = $result["dif4"];
                    $dif[$row][8] = $result["dif5"];
                    $dif[$row][9] = $result["dif6"];
                    $dif[$row][10] = $result["dif7"];
                    $dif[$row][11] = $result["dif8"];
                    $row++;
                }

                //เก็บแม่ที่หาลูกไม่ได้มาคิด
                if (true) {
                    foreach ($dataMom as $key => $value) {
                        $checkSMOM = false;
                        $idChlidSMO = array();
                        $ageChildSMOM = array();
                        $countCSMOM = 0;

                        $sql_insertHead = "INSERT INTO `" . $table . "` (ID , HaveParentID) VALUES";
                        $sql_body = "";

                        //echo $key . ": " . $value["MID"] . " " . $value["MLocate"] . " " . $value["MArea"] . " " . $value["MNumc"] . " ";

                        foreach ($value["AgeChild"] as $numC => $ageC) {
                            $indexS = null;

                            if ($ageC >= 0) {
                                $agrChildS = $ageC - rand(5, 10);
                                if ($agrChildS < 18) {
                                    if (isset($dataChlid[$value["MLocate"]][$value["MArea"]][$agrChildS])) {
                                        $indexS = array_rand($dataChlid[$value["MLocate"]][$value["MArea"]][$agrChildS]);
                                        if ($indexS != null) {
                                            if ($countCSMOM < 11) {
                                                $checkSMOM = true;
                                                //echo $ageC . " ";
                                                $ageChildSMOM[$countCSMOM] = $agrChildS;
                                                $idChlidSMOM[$countCSMOM] = $dataChlid[$value["MLocate"]][$value["MArea"]][$agrChildS][$indexS];
                                                //echo "(" . $dataChlid[$value["MLocate"]][$value["MArea"]][$agrChildS][$indexS] . ") ,";

                                                unset($dataChlid[$value["MLocate"]][$value["MArea"]][$agrChildS][$indexS]);
                                                $countCSMOM++;
                                                $countSingleChild++;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($withWho[2] > 0 && $checkSMOM) {
                            echo $withWho[2] . ": " . $value["MID"] . " " . $value["MLocate"] . " " . $value["MArea"] . " " . $value["MNumc"] . " ";

                            $min = $ageChildSMOM[0] + 13;
                            $max = $min + 30;
                            $ageSMOM = rand($min, $max);

                            //เลื่อนถ้าแม่โสด หมด
                            While (!isset($dataSingleF[$value["MLocate"]][$value["MArea"]][$ageSMOM])) {
                                $ageSMOM++;
                            }

                            $indexSMOM = array_rand($dataSingleF[$value["MLocate"]][$value["MArea"]][$ageSMOM]);
                            echo "ID MOM : " . $dataSingleF[$value["MLocate"]][$value["MArea"]][$ageSMOM][$indexSMOM];
                            $idSMOM = $dataSingleF[$value["MLocate"]][$value["MArea"]][$ageSMOM][$indexSMOM];

                            foreach ($ageChildSMOM as $numCS => $valAge) {
                                $sql_body.= " ( " . $idChlidSMOM[$numCS] . " , $idSMOM ) ,";
                                echo "( $valAge , " . $idChlidSMOM[$numCS] . ") ";
                            }
                            echo "<br>";
                            if ($sql_body != "") {
                                $sql_body = rtrim($sql_body, ",");
                                $sql_insertTail = "ON DUPLICATE KEY UPDATE HaveParentID = VALUES (HaveParentID)";
                                $totalQry = $sql_insertHead . $sql_body . $sql_insertTail;
                                echo $countCSMOM . "x " . $totalQry . "<br>";
                                mysql_query($totalQry);
                                //echo "WithWh[2]$withWho[2] <br>";
                                $withWho[2] -= $countCSMOM;
                            }
                            //$withWho[2] -= $countCSMOM;
                            //echo "<br>";
                        } else if ($withWho[2] <= 0 && $checkSMOM) {
                            //waitting to find Dad
//                        $row = 0;
                            $indexD = null;
//
//                        foreach ($namedDataArray as $result) {     //เอาค่าจาก excel มาใส่ตัวแปร
//                            $startage[$row][2] = $result["StartAge"];
//                            if ($result["EndAge"] == "มากกว่า100") {
//                                $endage[$row][3] = 101;
//                            } else {
//                                $endage[$row][3] = $result["EndAge"];
//                            }
//                            $dif[$row][4] = floatval($result["dif1"]);
//                            $dif[$row][5] = $result["dif2"];
//                            $dif[$row][6] = $result["dif3"];
//                            $dif[$row][7] = $result["dif4"];
//                            $dif[$row][8] = $result["dif5"];
//                            $dif[$row][9] = $result["dif6"];
//                            $dif[$row][10] = $result["dif7"];
//                            $dif[$row][11] = $result["dif8"];
//                            $row++;
//                        }
                            //check read excel
                            if (false) {
                                for ($i = 0; $i <= 6; $i++) {
                                    echo $startage[$i][2] . " ";
                                    echo $endage[$i][3] . " ";
                                    echo $dif[$i][4] . " ";
                                    echo $dif[$i][5] . " ";
                                    echo $dif[$i][6] . " ";
                                    echo $dif[$i][7] . " ";
                                    echo $dif[$i][8] . " ";
                                    echo $dif[$i][9] . " ";
                                    echo $dif[$i][10] . " ";
                                    echo $dif[$i][11] . "<br>";
                                }
                            }

                            //check ตามค่า random ของแต่ละอายุของแม่ จะได้น้ำหนักความต่างอายุพ่อไม่เท่ากัน
                            $random = rand(0, 10000) / 100;
                            for ($c = 0; $c < 7; $c++) {
                                if (intval($value["MAge"]) >= $startage[$c][2] && intval($value["MAge"]) <= $endage[$c][3]) {
                                    if ($random >= 0.00 && $random <= $dif[$c][4]) {
                                        $tempDiffDad = rand(5, 7);
                                        $ageDad = $value["MAge"] - $tempDiffDad;
                                        $dadRank = 1;
                                    } else if ($random > $dif[$c][4] && $random <= $dif[$c][5]) {
                                        $tempDiffDad = rand(3, 4);
                                        $ageDad = $value["MAge"] - $tempDiffDad;
                                        $dadRank = 2;
                                    } else if ($random > $dif[$c][5] && $random <= $dif[$c][6]) {
                                        $tempDiffDad = rand(1, 2);
                                        $ageDad = $value["MAge"] - $tempDiffDad;
                                        $dadRank = 3;
                                    } else if ($random > $dif[$c][6] && $random <= $dif[$c][7]) {
                                        $tempDiffDad = 0;
                                        $ageDad = $value["MAge"];
                                        $dadRank = 4;
                                    } else if ($random > $dif[$c][7] && $random <= $dif[$c][8]) {
                                        $tempDiffDad = rand(1, 2);
                                        $ageDad = $value["MAge"] + $tempDiffDad;
                                        $dadRank = 5;
                                    } else if ($random > $dif[$c][8] && $random <= $dif[$c][9]) {
                                        $tempDiffDad = rand(3, 4);
                                        $ageDad = $value["MAge"] + $tempDiffDad;
                                        $dadRank = 6;
                                    } else if ($random > $dif[$c][9] && $random <= $dif[$c][10]) {
                                        $tempDiffDad = rand(5, 9);
                                        $ageDad = $value["MAge"] + $tempDiffDad;
                                        $dadRank = 7;
                                    } else if ($random > $dif[$c][10] && $random <= $dif[$c][11]) {
                                        $tempDiffDad = rand(10, 15);
                                        $ageDad = $value["MAge"] + $tempDiffDad;
                                        $dadRank = 8;
                                    }
                                    //check over age
                                    if ($ageDad > 101) {
                                        $ageDad = 101;
                                    }
                                    //$break;
                                }
                            }

                            switch ($dadRank) {
                                case 1:
                                    if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                        $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                    }
                                    if ($indexD == null) {
                                        for ($e = 5; $e <= 7; $e++) {
                                            $ageDad = $value["MAge"] - $e;
                                            if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                                $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                            }
                                            if ($indexD != null) {
                                                break;
                                            }
                                        }
                                    }
                                    break;
                                case 2:
                                    if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                        $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                    }
                                    if ($indexD == null) {
                                        for ($e = 3; $e <= 4; $e++) {
                                            $ageDad = $value["MAge"] - $e;
                                            if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                                $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                            }
                                            if ($indexD != null) {
                                                break;
                                            }
                                        }
                                    }
                                    break;
                                case 3:
                                    if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                        $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                    }
                                    if ($indexD == null) {
                                        for ($e = 1; $e <= 2; $e++) {
                                            $ageDad = $value["MAge"] - $e;
                                            if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                                $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                            }
                                            if ($indexD != null) {
                                                break;
                                            }
                                        }
                                    }
                                    break;
                                case 4:
                                    //No rank
                                    break;
                                case 5:
                                    if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                        $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                    }
                                    if ($indexD == null) {
                                        for ($e = 1; $e <= 2; $e++) {
                                            $ageDad = $value["MAge"] + $e;
                                            if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                                $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                            }
                                            if ($indexD != null) {
                                                break;
                                            }
                                        }
                                    }
                                    break;
                                case 6:
                                    if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                        $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                    }
                                    if ($indexD == null) {
                                        for ($e = 3; $e <= 4; $e++) {
                                            $ageDad = $value["MAge"] + $e;
                                            if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                                $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                            }
                                            if ($indexD != null) {
                                                break;
                                            }
                                        }
                                    }
                                    break;
                                case 7:
                                    if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                        $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                    }
                                    if ($indexD == null) {
                                        for ($e = 5; $e <= 9; $e++) {
                                            $ageDad = $value["MAge"] + $e;
                                            if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                                $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                            }
                                            if ($indexD != null) {
                                                break;
                                            }
                                        }
                                    }
                                    break;
                                case 8:
                                    if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                        $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                    }
                                    if ($indexD == null) {
                                        for ($e = 10; $e <= 15; $e++) {
                                            $ageDad = $value["MAge"] + $e;
                                            if (isset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad])) {
                                                $indexD = array_rand($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad]);
                                            }
                                            if ($indexD != null) {
                                                break;
                                            }
                                        }
                                    }
                                    break;

                                default:
                                    break;
                            }

                            if ($indexD != NULL && $indexD != 0) {
                                $sql_insertHead = "INSERT INTO `" . $table . "` (ID , HaveParentID) VALUES";
                                $sql_body = "";

                                $haveDad = true;
                                $idDad = $dataDad[$value["MLocate"]][$value["MArea"]][$ageDad][$indexD];
                                if ($idDad == NULL) {
                                    echo "";
                                }
                                //ลบมันออกจากarrayเพื่อ random ครั้งหน้าจะไม่เจออีก
                                echo $withWho[3] . " AgeDad = $ageDad , ID $idDad <br>";
                                unset($dataDad[$value["MLocate"]][$value["MArea"]][$ageDad][$indexD]);

                                foreach ($ageChildSMOM as $numCS => $valAge) {
                                    if ($withWho[3] > 0) {
                                        $sql_body.= " ( " . $idChlidSMOM[$numCS] . " , $idDad ) ,";
                                        //echo "( $valAge , " . $idChlidSMOM[$numCS] . ") ";
                                        $withWho[3] --;
                                    }
                                }
                                echo "withWho[3] $withWho[3] ";
                                if ($sql_body != "") {
                                    $sql_body = rtrim($sql_body, ",");
                                    $sql_insertTail = "ON DUPLICATE KEY UPDATE HaveParentID = VALUES (HaveParentID)";
                                    $totalQry = $sql_insertHead . $sql_body . $sql_insertTail;
                                    echo $countCSMOM . "D " . $totalQry . "<br>";
                                    mysql_query($totalQry);
                                    //$withWho[3] -= $countCSMOM;
                                }
                            }

                            //$withWho[3] --;
//
                            if ($withWho[3] <= 0) {
                                break;
                            }
                        }
                    }
                    echo "มีลูกเพิ่มอีก $countSingleChild คน<br>";
                }
            }

//---------------------Update (similar) ---------------------------------------
            $strUpHHStatus = "UPDATE `" . $table . "` "
                    . "SET HH_STATUS = 'Child' "
                    . "WHERE HH_STATUS != 'Head' "
                    . "AND HH_STATUS != 'Spouse' "
                    . "AND HaveParentID != 0 ";
            echo $strUpHHStatus . "<br>";
            mysql_query($strUpHHStatus) or die(mysql_error());

            $strUpHHStatus = "UPDATE `" . $table . "` "
                    . "SET HH_STATUS = 'Other' "
                    . "WHERE HH_STATUS = '' ";
            echo $strUpHHStatus . "<br>";
            mysql_query($strUpHHStatus) or die(mysql_error());

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
        ?>
    </body>
</html>