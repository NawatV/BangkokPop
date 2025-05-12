<html>
    <head>
        <meta charset="UTF-8">
        <title> Update People By Size for SimPhitlok </title>
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


        $inputFileName = "15_hh_size.xlsx";
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
            $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //changed
            $table = "People";
            mysql_query("SET NAMES UTF8");
            mysql_query("SET character_set_results=UTF8");
            mysql_query("SET character_set_client=UTF8");
            mysql_query("SET character_set_connection=UTF8");

            foreach ($namedDataArray as $result) {

                $per[$result["location"]][$result["area"]][1] = $result["1"];
                $per[$result["location"]][$result["area"]][2] = $result["2"];
                $per[$result["location"]][$result["area"]][3] = $result["3"];
                $per[$result["location"]][$result["area"]][4] = $result["4"];
                $per[$result["location"]][$result["area"]][5] = $result["5"];
                $per[$result["location"]][$result["area"]][6] = $result["6"];
                $per[$result["location"]][$result["area"]][7] = $result["7"];
                $per[$result["location"]][$result["area"]][8] = $result["8"];
                $per[$result["location"]][$result["area"]][9] = $result["9"];
                $per[$result["location"]][$result["area"]][10] = $result["10"];
            }

            $sql_workLocation = "SELECT  `Location` FROM  `" . $table . "` GROUP BY `Location` ";
            echo $sql_workLocation . "<br>";
            $resulcount = mysql_query($sql_workLocation) or die(mysql_error());
            $worklocation = array();
            $loca = 0;
            while ($saveAll = mysql_fetch_object($resulcount)) {
                $worklocation[$loca] = $saveAll->Location;
                $loca++;
            }
            mysql_free_result($resulcount);
            unset($loca);

            //$worklocation = array("อ.ชาติตระการ", "อ.นครไทย");
            foreach ($worklocation as $keylocation => $valuelocation) {

                $strLocation = $valuelocation;

                //Cal per to numbers of size
                if (true) {
                    //---------------------------Query all Head---------------------------\\
                    $str_count = "SELECT  `Location` , `Area` , COUNT(`ID`) AS AllHead "
                            . "FROM  `" . $table . "` "
                            . "WHERE  `HH_Status` =  'Head' "
                            . "AND `Location` = '" . $strLocation . "' "
                            . "GROUP BY  `Location` ,  `Area` ";
                    echo $str_count . "<br>";
                    $resulcount = mysql_query($str_count) or die(mysql_error());
                    $countHH = array();
                    while ($saveAll = mysql_fetch_object($resulcount)) {
                        $countHH[$saveAll->Location][$saveAll->Area] = $saveAll->AllHead;
                        //echo $saveAll->Location." ".$saveAll->Area." ".$countHH[$saveAll->Location][$saveAll->Area]."<br>";
                    }
                    mysql_free_result($resulcount);


                    //---------------------------Count all People---------------------------\\
                    $str_count = "SELECT  `Location` , `Area` , COUNT(`ID`) AS AllPP "
                            . "FROM  `" . $table . "` "
                            . "WHERE `Location` = '" . $strLocation . "' "
                            . "GROUP BY  `Location` ,  `Area` ";
                    echo $str_count . "<br>";
                    $resulcountPP = mysql_query($str_count) or die(mysql_error());
                    $countAPP = array();
                    while ($saveAll = mysql_fetch_object($resulcountPP)) {
                        $countAPP[$saveAll->Location][$saveAll->Area] = $saveAll->AllPP;
                        //echo $saveAll->Location." ".$saveAll->Area." ".$countHH[$saveAll->Location][$saveAll->Area]."<br>";
                    }
                    mysql_free_result($resulcountPP);



                    foreach ($countHH as $location => $value) {
                        foreach ($countHH[$location] as $area => $allHHSize) {
                            //$toRound = 0;
                            //echo "all = $allHHSize $location $area ";
                            $allExcel = 0;
                            $allExP = 0;
                            for ($hhSize = 1; $hhSize <= 10; $hhSize++) {
                                $sizeFromCal[$location][$area][$hhSize] = round($per[$location][$area][$hhSize] * $allHHSize / 100);
                                $allExcel += $sizeFromCal[$location][$area][$hhSize];
                                $allExP += $sizeFromCal[$location][$area][$hhSize] * $hhSize;
                            }

                            echo " $location $area ครัวเรือน Data/Excel  = $allHHSize/$allExcel Diff =" . ($allExcel - $allHHSize)
                            . " คนทั้งหมด " . $countAPP[$location][$area] . "/" . $allExP . "Diff = " . ($countAPP[$location][$area] - $allExP) . "คน";


                            if ($allHHSize < $allExcel) {
                                if ($countAPP[$location][$area] < $allExP) {
                                    $indexRuond = (($allExP - $countAPP[$location][$area]) / ($allExcel - $allHHSize));
                                    echo " ปัด[$indexRuond]--";
                                    $sizeFromCal[$location][$area][$indexRuond] --;
                                } else if ($countAPP[$location][$area] >= $allExP) {
                                    echo " ปัด[1]--";
                                    for ($x = 0; $x < ($allExcel - $allHHSize ); $x++) {
                                        $sizeFromCal[$location][$area][1] --;
                                    }
                                }
                            } else if ($allHHSize > $allExcel) {
                                echo " ปัด[1] " . ($allHHSize - $allExcel) . "++";
                                for ($x = 0; $x < ($allHHSize - $allExcel); $x++) {
                                    $sizeFromCal[$location][$area][1] ++;
                                }
                            } else {
                                if ($countAPP[$location][$area] < $allExP) {
                                    //Reduce Size
                                    echo "Reduce Size " . ($allExP - $countAPP[$location][$area]);

                                    for ($x = 0; $x < ($allExP - $countAPP[$location][$area]); $x++) {
                                        $sizeFromCal[$location][$area][1] ++;
                                        $sizeFromCal[$location][$area][2] --;
                                    }
                                }
                            }

                            echo " <br>";
                        }
                    }

                    unset($location);
                    unset($area);
                }

                //Find Data Spouse
                if (true) {
                    $sql = "SELECT ID , HH_ID "
                            . "FROM `" . $table . "` "
                            . "WHERE HH_Status = 'Spouse' "
                            . "AND `Location` = '" . $strLocation . "' ";
                    echo $sql . "<br>";
                    $sumSP = 0;
                    $resultSP = mysql_query($sql) or die(mysql_error());
                    $dataSpouse = array();
                    $dataSpToSp = array();
                    while ($saveAll = mysql_fetch_object($resultSP)) {
                        $dataSpouse[$saveAll->HH_ID] = $saveAll->ID;
                        $dataSpToSp[$saveAll->ID] = $saveAll->HH_ID;
                        $sumSP++;
                    }
                    echo "Sum Spouse $sumSP <br>";
                    mysql_free_result($resultSP);
                }

                //Find UID TO HHID
                if (true) {
                    $sql = "SELECT `ID` , `HH_ID` "
                            . "FROM `" . $table . "` "
                            . "WHERE `HH_Status` = 'Head' "
                            . "AND `Location` = '" . $strLocation . "' ";
                    echo $sql . "<br>";
                    $sumHead = 0;
                    $resultH = mysql_query($sql) or die(mysql_error());
                    $dataHead = array();
                    while ($saveAll = mysql_fetch_object($resultH)) {
                        $dataHead[$saveAll->ID] = $saveAll->HH_ID;
                        $sumHead++;
                    }
                    echo "Sum Head $sumHead <br>";
                    mysql_free_result($resultH);
                }

                //Find NumOfChild
                if (true) {
                    $sql = "SELECT `Number_children` , `ID` "
                            . "FROM `" . $table . "` "
                            . "WHERE Number_children != 0 "
                            . "AND `Location` = '" . $strLocation . "' ";
                    echo $sql . "<br>";
                    $sumHPNum = 0;
                    $resultNHP = mysql_query($sql) or die(mysql_error());
                    $dataHPNum = array();
                    while ($saveAll = mysql_fetch_object($resultNHP)) {
                        $dataHPNum[$saveAll->ID] = $saveAll->Number_children;
                        $sumHPNum++;
                    }
                    echo "Sum HavePent $sumHPNum <br>";
                    mysql_free_result($resultNHP);
                }

                //Find Parent For check
                if (true) {
                    $sql = "SELECT HaveParentID , Marital_Status , ID "
                            . "FROM `" . $table . "` "
                            . "WHERE HaveParentID != 0 "
                            . "AND `Location` = '" . $strLocation . "' ";
                    echo $sql . "<br>";
                    $sumHP = 0;
                    $resultHP = mysql_query($sql) or die(mysql_error());
                    $dataHaveParent = array();
                    while ($saveAll = mysql_fetch_object($resultHP)) {

                        if (!isset($counter[$saveAll->HaveParentID])) {
                            $counter[$saveAll->HaveParentID] = 0;
                        }

                        $dataHaveParent[$saveAll->HaveParentID][$counter[$saveAll->HaveParentID]] = $saveAll->ID;

                        $counter[$saveAll->HaveParentID] ++;
                        $sumHP++;
                    }
                    echo "Sum HavePent $sumHP <br>";
                    mysql_free_result($resultHP);
                    unset($counter);
                }

                //Find MinSize for Check
                if (true) {
                    $sql = "SELECT `Location` , `Area` , `HH_ID` ,`ID` ,`Marital_Status` "
                            . "FROM `" . $table . "` "
                            . "WHERE `HH_Status` = 'Head' "
                            . "AND `Location` = '" . $strLocation . "' "
                            //. "AND `Area` = 'ในเขตเทศบาล' "
                            . "ORDER BY `ID` ";
                    echo $sql . "<br>";
                    $countChattakran = 0;
                    $resultHH = mysql_query($sql) or die(mysql_error());
                    $dataHH = array();
                    $totalMinSize = 0;
                    $totalMinSizeAr = 0;
                    $checkPick = 0;
                    $realChild = 0;

                    while ($saveAll = mysql_fetch_object($resultHH)) {
                        $HHLoc = $saveAll->Location;
                        $HHArea = $saveAll->Area;
                        $HHID = $saveAll->HH_ID;
                        $HHUID = $saveAll->ID;
                        $HHMar = $saveAll->Marital_Status;
                        $countChattakran++;

                        $realChild = 0;
                        $whoParent = NULL;

                        if (!isset($counterMinSize[$HHID])) {
                            $counterMinSize[$HHID] = 0;
                            $minSize = 0;
                        }

                        $dataHH[$HHID][$counterMinSize[$HHID]] = $HHUID;
                        $counterMinSize[$HHID] ++;
                        $minSize++;

                        //ถ้าตัวเองมีลูก
                        if (isset($dataHaveParent[$HHUID])) {
                            foreach ($dataHaveParent[$HHUID] as $key => $valueHH) {
                                if (!array_key_exists($valueHH, $dataHead) && !array_key_exists($valueHH, $dataSpToSp)) {
                                    $dataHH[$HHID][$counterMinSize[$HHID]] = $valueHH;
                                    $counterMinSize[$HHID] ++;
                                    $minSize++;
                                    $realChild++;
                                    $whoParent = $HHUID;
                                }
                            }
                        }

                        //ถ้ามี Spouse
                        if (array_key_exists($HHID, $dataSpouse)) {

                            $dataHH[$HHID][$counterMinSize[$HHID]] = $dataSpouse[$HHID];
                            $counterMinSize[$HHID] ++;
                            $minSize++; //add spouse

                            if (array_key_exists($dataSpouse[$HHID], $dataHaveParent)) {

                                foreach ($dataHaveParent[$dataSpouse[$HHID]] as $key => $valueHH) {
                                    if (!array_key_exists($valueHH, $dataHead) && !array_key_exists($valueHH, $dataSpToSp)) {
                                        $dataHH[$HHID][$counterMinSize[$HHID]] = $valueHH;
                                        $counterMinSize[$HHID] ++;
                                        $minSize++;
                                        $realChild++;
                                        $whoParent = $dataSpouse[$HHID];
                                    }
                                }
                            }
                        }

                        if (!isset($counter[$HHLoc][$HHArea][$minSize])) {
                            $counter[$HHLoc][$HHArea][$minSize] = 0;
                        }

                        $dataMinSize[$HHLoc][$HHArea][$minSize][$counter[$HHLoc][$HHArea][$minSize]] = $HHID;
                        if ($whoParent != NULL && isset($dataHPNum[$whoParent])) {
                            if ($realChild < $dataHPNum[$whoParent]) {
                                if (!isset($dataForSwap[$HHLoc][$HHArea][$HHMar][$minSize][0])) {
                                    $dataForSwap[$HHLoc][$HHArea][$HHMar][$minSize][0] = array($whoParent, $realChild);
                                } else {
                                    array_push($dataForSwap[$HHLoc][$HHArea][$HHMar][$minSize], array($whoParent, $realChild));
                                }
                            }
                            $dataForSwapParent[$HHID] = array("who" => $whoParent, "sta" => $HHMar);
                        }
                        $counter[$HHLoc][$HHArea][$minSize] ++;

                        $totalMinSize+=$minSize;
                        $totalMinSizeAr += $counterMinSize[$HHID];
                    }

                    mysql_free_result($resultHH);
                    unset($counter);
                    echo "totalminsize $totalMinSize<br>";
                }

                //update คนที่ถูกเลือกไปแล้ว for Check
                if (true) {
                    foreach ($dataHH as $keyHH => $value) {
                        foreach ($dataHH[$keyHH] as $keyNum => $valueID) {
                            $sql_Update = "INSERT INTO `" . $table . "` (ID , Picked) "
                                    . "VALUES ( " . $valueID . " , " . $keyHH . " ) "
                                    . "ON DUPLICATE KEY UPDATE Picked = VALUES (Picked) ";
                            mysql_query($sql_Update) or die(mysql_error());
                        }
                    }
                }

                //Find Another people for Check
                if (true) {
                    $sql = "SELECT `Location` , `Area` , `ID` FROM `" . $table . "` "
                            . "WHERE Picked = 0 "
                            . "AND `Location` = '" . $strLocation . "'";
                    echo $sql . "<br>";
                    $sumPeople = 0;
                    $resultAP = mysql_query($sql) or die(mysql_error());
                    $dataPeople = array();
                    while ($saveAll = mysql_fetch_object($resultAP)) {

                        if (!isset($counter[$saveAll->Location][$saveAll->Area])) {
                            $counter[$saveAll->Location][$saveAll->Area] = 0;
                        }

                        $dataPeople[$saveAll->Location][$saveAll->Area][$counter[$saveAll->Location][$saveAll->Area]] = $saveAll->ID;
                        $counter[$saveAll->Location][$saveAll->Area] ++;
                        $sumPeople++;
                    }
                    echo "Sum People $sumPeople <br>";
                    mysql_free_result($resultAP);
                    unset($counter);
                }

                //Fix Data More than Excel
                if (true) {
                    foreach ($dataMinSize as $sizeLoc => $value) {
                        foreach ($dataMinSize[$sizeLoc] as $sizeArea => $value) {

                            // <editor-fold defaultstate="collapsed" desc="ปัดเลข"> 
                            $sumExcel = 0;
                            $sumExcelA = 0;

                            //จากexcel
                            echo "DataPP = " . count($dataPeople[$sizeLoc][$sizeArea]) . "<br>";
                            echo "$sizeLoc $sizeArea <br>Excel ";

                            for ($inSize = 10; $inSize >= 1; $inSize--) {
                                echo $inSize . " " . $sizeFromCal[$sizeLoc][$sizeArea][$inSize] . ", ";

                                $sumExcel += ($sizeFromCal[$sizeLoc][$sizeArea][$inSize] * $inSize);
                                $sumExcelA += $sizeFromCal[$sizeLoc][$sizeArea][$inSize];
                            }

                            echo " Sum Excel $sumExcelA:$sumExcel คน <br> Data ";
                            $test = 0;

                            krsort($dataMinSize[$sizeLoc][$sizeArea]); //เรียงจากมากไปน้อย เพื่อทำ บ้านขนาดใหญ่ก่อน

                            foreach ($dataMinSize[$sizeLoc][$sizeArea] as $key => $value) {
                                echo $key . " " . count($dataMinSize[$sizeLoc][$sizeArea][$key]) . ", ";
                                $test += (count($dataMinSize[$sizeLoc][$sizeArea][$key]) * $key);
                            }
                            $allPP = $test + count($dataPeople[$sizeLoc][$sizeArea]);
                            echo "allPP = $allPP <br>";

                            $deltaPP = $allPP - $sumExcel;
                            echo "Diff PPvsExcel = $deltaPP <br>";
                            for ($ppSize = 1; $ppSize <= 9; $ppSize++) {
                                if ($deltaPP > 1) {
                                    $tempRoundPP = round($deltaPP * $per[$sizeLoc][$sizeArea][$ppSize] / 100);
                                    echo $tempRoundPP . " ";

                                    for ($rpp = 0; $rpp < $tempRoundPP; $rpp++) {
                                        $sizeFromCal[$sizeLoc][$sizeArea][$ppSize] --;
                                        $sizeFromCal[$sizeLoc][$sizeArea][$ppSize + 1] ++;
                                    }
                                } else if ($deltaPP == 1 && $sizeFromCal[$sizeLoc][$sizeArea][10] == 0) {
                                    $sizeFromCal[$sizeLoc][$sizeArea][1] --;
                                    $sizeFromCal[$sizeLoc][$sizeArea][2] ++;
                                }
                            }

                            echo "<br> Affter Cal Excel <br>";
                            $sumRPPA = 0;
                            $sumRPP = 0;
                            for ($inSize = 10; $inSize >= 1; $inSize--) {
                                echo $inSize . " " . $sizeFromCal[$sizeLoc][$sizeArea][$inSize] . ", ";

                                $sumRPPA += ($sizeFromCal[$sizeLoc][$sizeArea][$inSize] * $inSize);
                                $sumRPP += $sizeFromCal[$sizeLoc][$sizeArea][$inSize];
                            }
                            echo "allPP = $sumRPP:$sumRPPA <br>";

                            // </editor-fold>

                            for ($inSize = 10; $inSize >= 1; $inSize--) {
                                if ($inSize == 10) {
                                    echo "=====" . $inSize . " " . $sizeFromCal[$sizeLoc][$sizeArea][$inSize] . "=====<br>";

                                    $sumMoreTen = 0;
                                    $arrayMoreTen = array();
                                    foreach ($dataMinSize[$sizeLoc][$sizeArea] as $keySize => $value) {
                                        if ($keySize < 10) {
                                            break;
                                        }
                                        $sumMoreTen += count($dataMinSize[$sizeLoc][$sizeArea][$keySize]);
                                    }

                                    if ($sumMoreTen > $sizeFromCal[$sizeLoc][$sizeArea][$inSize]) {
                                        $diffMoreTen = $sumMoreTen - $sizeFromCal[$sizeLoc][$sizeArea][$inSize];
                                        echo "Diff =  $diffMoreTen <br>";

                                        foreach ($dataMinSize[$sizeLoc][$sizeArea] as $keySize => $value) {
                                            if ($keySize < 10) {
                                                break;
                                            }

                                            foreach ($dataMinSize[$sizeLoc][$sizeArea][$keySize] as $keyWho => $valueWho) {
                                                echo " $keySize $valueWho <br>";
                                                if (!isset($arrayMoreTen[0])) {
                                                    $arrayMoreTen[0] = array("who" => $valueWho, "size" => $keySize);
                                                } else {
                                                    array_push($arrayMoreTen, array("who" => $valueWho, "size" => $keySize));
                                                }
                                            }
                                        }

                                        echo "<br> diff BTW size ";
                                        $diffAva = array();
                                        for ($n = 10; $n >= 1; $n--) {

                                            $diffBTWSize = $sizeFromCal[$sizeLoc][$sizeArea][$n] - count($dataMinSize[$sizeLoc][$sizeArea][$n]);
                                            echo $n . " " . $diffBTWSize . " ";
                                            if ($diffBTWSize > 0) {
                                                if (!isset($diffAva[0])) {
                                                    $diffAva[0] = array("size" => $n, "ava" => $diffBTWSize);
                                                } else {
                                                    array_push($diffAva, array("size" => $n, "ava" => $diffBTWSize));
                                                }
                                            }
                                        }

                                        echo "<br>";
                                        echo "To Move <br>";
                                        for ($m = 0; $m < $diffMoreTen; $m++) {
                                            $toMoveHaveParnt = array_pop($arrayMoreTen);
                                            echo $dataHH[$toMoveHaveParnt["who"]][0] . ":"
                                            . $dataForSwapParent[$toMoveHaveParnt["who"]]["who"] . ":"
                                            . $dataForSwapParent[$toMoveHaveParnt["who"]]["sta"] . " ,"
                                            . "Count = " . count($dataHH[$toMoveHaveParnt["who"]]);
                                            $countToMove = count($dataHH[$toMoveHaveParnt["who"]]);
                                            echo $toMoveHaveParnt["size"] . " can change to ";
                                            foreach ($diffAva as $keyAva => $value) {
                                                if ($diffAva[$keyAva]["ava"] > 0) {
                                                    echo $diffAva[$keyAva]["size"] . "," . $diffAva[$keyAva]["ava"] . " ";

                                                    $diffMoveAva = $toMoveHaveParnt["size"] - $diffAva[$keyAva]["size"];

                                                    echo "<br>Diff Ava = " . $diffMoveAva . " Size Next = " . $diffAva[$keyAva]["size"] . "," . $diffAva[$keyAva]["ava"];
                                                    echo "<br>Kick<br>";
                                                    for ($e = 1; $e <= $diffMoveAva; $e++) {
                                                        echo $dataHH[$toMoveHaveParnt["who"]][$countToMove - $e] . " ";

                                                        $whoToKick = $dataHH[$toMoveHaveParnt["who"]][$countToMove - $e];

                                                        $sql = "SELECT temp FROM `" . $table . "` WHERE ID = " . $dataHH[$toMoveHaveParnt["who"]][$countToMove - $e];
                                                        //echo $sql . "<br>";
                                                        $resultTK = mysql_query($sql) or die(mysql_error());
                                                        while ($saveAll = mysql_fetch_object($resultTK)) {
                                                            echo "Age = " . $saveAll->temp . " ";
                                                        }
                                                        mysql_free_result($resultTK);
                                                        ///////////////กำลังจะswapให้ size ถัดไปจากเป้าหมาย
                                                        echo " Select Size To Swap :" . $diffAva[$keyAva + 1]["size"] . " ava = " . $diffAva[$keyAva + 1]["ava"];
                                                        echo " Kick TO " . $diffAva[max(array_keys($diffAva))]["size"] . " ava = " . $diffAva[max(array_keys($diffAva))]["ava"] . " ";

                                                        $indexSize = $diffAva[max(array_keys($diffAva))]["size"];
                                                        $indexSta = $dataForSwapParent[$toMoveHaveParnt["who"]]["sta"];
                                                        krsort($dataForSwap[$sizeLoc][$sizeArea][$indexSta]);

                                                        $indexRandSwap = array_rand($dataForSwap[$sizeLoc][$sizeArea][$indexSta][$indexSize]);
                                                        $kickToWho = $dataForSwap[$sizeLoc][$sizeArea][$indexSta][$indexSize][$indexRandSwap][0];
                                                        echo "Kick To Who " . $kickToWho;
                                                        echo " ";

                                                        $sql = "SELECT temp FROM `" . $table . "` WHERE ID = " . $kickToWho;

                                                        //echo $sql . "<br>";
                                                        $resultKT = mysql_query($sql) or die(mysql_error());
                                                        while ($saveAll = mysql_fetch_object($resultKT)) {
                                                            echo "Age = " . $saveAll->temp . "<br>";
                                                        }
                                                        mysql_free_result($resultKT);

                                                        $sql_Update = "INSERT INTO `" . $table . "` (ID , HaveParentID ) "
                                                                . "VALUES ( " . $whoToKick . " , " . $kickToWho . " ) "
                                                                . "ON DUPLICATE KEY UPDATE HaveParentID  = VALUES (HaveParentID ) ";
                                                        mysql_query($sql_Update);

                                                        $diffAva[max(array_keys($diffAva))]["ava"] --;
                                                    }
                                                    $diffAva[$keyAva]["ava"] --;
                                                    break;
                                                }
                                            }
                                            echo "<br>";
                                        }
                                        echo "<br>";
                                    }
                                } else {
                                    
                                }
                            }
                        }
                    }
                }

                if (true) {
                    $sql_Update = "UPDATE `" . $table . "` SET Picked =0 "
                            . "AND `Location` = '" . $strLocation . "'";
                    mysql_query($sql_Update);
                    unset($dataHaveParent);
                    unset($counterMinSize);
                    unset($dataHH);
                    unset($dataMinSize);
                    unset($dataPeople);
                }

                //Find Parent Again
                if (true) {
                    $sql = "SELECT HaveParentID , Marital_Status , ID FROM `" . $table . "` WHERE HaveParentID != 0";
                    echo $sql . "<br>";
                    $sumHP = 0;
                    $resultHP = mysql_query($sql) or die(mysql_error());
                    $dataHaveParent = array();
                    while ($saveAll = mysql_fetch_object($resultHP)) {

                        if (!isset($counter[$saveAll->HaveParentID])) {
                            $counter[$saveAll->HaveParentID] = 0;
                        }

                        $dataHaveParent[$saveAll->HaveParentID][$counter[$saveAll->HaveParentID]] = $saveAll->ID;

                        $counter[$saveAll->HaveParentID] ++;
                        $sumHP++;
                    }
                    echo "Sum HavePent $sumHP <br>";
                    mysql_free_result($resultHP);
                    unset($counter);
                }

                //cal Data min Size Again
                if (true) {
                    $sql = "SELECT `Location` , `Area` , `HH_ID` ,`ID` ,`Marital_Status` "
                            . "FROM `" . $table . "` "
                            . "WHERE `HH_Status` = 'Head' "
                            . "AND `Location` = '" . $strLocation . "' "
                            //. "AND `Area` = 'ในเขตเทศบาล' "
                            . "ORDER BY `ID` ";
                    echo $sql . "<br>";
                    $countChattakran = 0;
                    $resultHH = mysql_query($sql) or die(mysql_error());
                    $dataHH = array();
                    $counterMinSize = array();
                    $totalMinSize = 0;
                    $totalMinSizeAr = 0;
                    $checkPick = 0;
                    $realChild = 0;

                    while ($saveAll = mysql_fetch_object($resultHH)) {
                        $HHLoc = $saveAll->Location;
                        $HHArea = $saveAll->Area;
                        $HHID = $saveAll->HH_ID;
                        $HHUID = $saveAll->ID;
                        $HHMar = $saveAll->Marital_Status;
                        $countChattakran++;

                        $realChild = 0;


                        if (!isset($counterMinSize[$HHID])) {
                            $counterMinSize[$HHID] = 0;
                            $minSize = 0;
                        }

                        $dataHH[$HHID][$counterMinSize[$HHID]] = $HHUID;
                        $counterMinSize[$HHID] ++;
                        $minSize++;

                        //ถ้าตัวเองมีลูก
                        if (isset($dataHaveParent[$HHUID])) {
                            foreach ($dataHaveParent[$HHUID] as $key => $valueHH) {
                                if (!array_key_exists($valueHH, $dataHead) && !array_key_exists($valueHH, $dataSpToSp)) {
                                    $dataHH[$HHID][$counterMinSize[$HHID]] = $valueHH;
                                    $counterMinSize[$HHID] ++;
                                    $minSize++;
                                }
                            }
                        }

                        //ถ้ามี Spouse
                        if (array_key_exists($HHID, $dataSpouse)) {

                            $dataHH[$HHID][$counterMinSize[$HHID]] = $dataSpouse[$HHID];
                            $counterMinSize[$HHID] ++;
                            $minSize++; //add spouse

                            if (array_key_exists($dataSpouse[$HHID], $dataHaveParent)) {

                                foreach ($dataHaveParent[$dataSpouse[$HHID]] as $key => $valueHH) {
                                    if (!array_key_exists($valueHH, $dataHead) && !array_key_exists($valueHH, $dataSpToSp)) {
                                        $dataHH[$HHID][$counterMinSize[$HHID]] = $valueHH;
                                        $counterMinSize[$HHID] ++;
                                        $minSize++;
                                    }
                                }
                            }
                        }

                        if (!isset($counter[$HHLoc][$HHArea][$minSize])) {
                            $counter[$HHLoc][$HHArea][$minSize] = 0;
                        }

                        $dataMinSize[$HHLoc][$HHArea][$minSize][$counter[$HHLoc][$HHArea][$minSize]] = $HHID;
                        $counter[$HHLoc][$HHArea][$minSize] ++;
                        $totalMinSize+=$minSize;
                        $totalMinSizeAr += $counterMinSize[$HHID];
                    }

                    mysql_free_result($resultHH);
                    unset($counter);
                }

                //update คนที่ถูกเลือกไปแล้ว NEW
                if (true) {
                    foreach ($dataHH as $keyHH => $value) {
                        foreach ($dataHH[$keyHH] as $keyNum => $valueID) {
                            $sql_Update = "INSERT INTO `" . $table . "` (ID , Picked) "
                                    . "VALUES ( " . $valueID . " , " . $keyHH . " ) "
                                    . "ON DUPLICATE KEY UPDATE Picked = VALUES (Picked) ";
                            mysql_query($sql_Update) or die(mysql_error());
                        }
                    }
                }

                //Find Another people New
                if (true) {
                    $sql = "SELECT `Location` , `Area` , `ID` FROM `" . $table . "` "
                            . "WHERE Picked = 0 "
                            . "AND `Location` = '" . $strLocation . "'";
                    echo $sql . "<br>";
                    $sumPeople = 0;
                    $resultAP = mysql_query($sql) or die(mysql_error());
                    $dataPeople = array();
                    while ($saveAll = mysql_fetch_object($resultAP)) {

                        if (!isset($counter[$saveAll->Location][$saveAll->Area])) {
                            $counter[$saveAll->Location][$saveAll->Area] = 0;
                        }

                        $dataPeople[$saveAll->Location][$saveAll->Area][$counter[$saveAll->Location][$saveAll->Area]] = $saveAll->ID;
                        $counter[$saveAll->Location][$saveAll->Area] ++;
                        $sumPeople++;
                    }
                    echo "Sum People $sumPeople <br>";
                    mysql_free_result($resultAP);
                    unset($counter);
                }

                //เลือก size
                $saveToTen = array();
                If (true) {
                    foreach ($dataMinSize as $sizeLoc => $value) {
                        foreach ($dataMinSize[$sizeLoc] as $sizeArea => $value) {
                            $needMorePP = 0;
                            $tempCheck = 0;
                            $keepPP = 0;
                            $tempTotal = 0;
                            $sumExcel = 0;
                            $sumExcelA = 0;

                            //make sure update HOUSENUM on all DataMinSize
                            $sql_Update = "UPDATE `" . $table . "` SET HOUSENUM = Picked "
                                    . "WHERE Picked != 0 "
                                    . "AND Location = '" . $sizeLoc . "' "
                                    . "AND Area = '" . $sizeArea . "'";
                            echo $sql_Update . "<br>";
                            mysql_query($sql_Update) or die(mysql_error());

                            // <editor-fold defaultstate="collapsed" desc="ปัริ้น"> 
                            //จากexcel
                            echo "DataPP = " . count($dataPeople[$sizeLoc][$sizeArea]) . "<br>";
                            echo "$sizeLoc $sizeArea <br>Excel ";

                            for ($inSize = 1; $inSize <= 10; $inSize++) {
                                echo $inSize . " " . $sizeFromCal[$sizeLoc][$sizeArea][$inSize] . ", ";

                                $sumExcel += ($sizeFromCal[$sizeLoc][$sizeArea][$inSize] * $inSize);
                                $sumExcelA += $sizeFromCal[$sizeLoc][$sizeArea][$inSize];
                            }

                            echo " Sum Excel $sumExcelA:$sumExcel คน <br> Data ";
                            $test = 0;
                            foreach ($dataMinSize[$sizeLoc][$sizeArea] as $key => $value) {
                                echo $key . " " . count($dataMinSize[$sizeLoc][$sizeArea][$key]) . ", ";
                                $test += (count($dataMinSize[$sizeLoc][$sizeArea][$key]) * $key);
                            }
                            $allPP = $test + count($dataPeople[$sizeLoc][$sizeArea]);
                            echo "allPP = $allPP <br>";

                            $deltaPP = $allPP - $sumExcel;
                            echo "Diff PPvsExcel = $deltaPP <br>";

                            // </editor-fold>

                            $testSort = array();
                            $i = 0;

                            krsort($dataMinSize[$sizeLoc][$sizeArea]); //เรียงจากมากไปน้อย เพื่อทำ บ้านขนาดใหญ่ก่อน
                            $countMinSize = 0;

                            foreach ($dataMinSize[$sizeLoc][$sizeArea] as $sizeOnData => $value) {
                                echo "<br>=================[" . $sizeOnData . ","
                                . "" . count($dataMinSize[$sizeLoc][$sizeArea][$sizeOnData])
                                . "]================= <br>";
                                //$maxSize = 9;
                                if ($sizeOnData <= 9) {

                                    $inCal = count($dataMinSize[$sizeLoc][$sizeArea][$sizeOnData]);

                                    for ($r = 0; $r < $inCal; $r++) {
                                        echo "$r HHID " . $dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$r] . " ";
                                        $tempHHID = $dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$r];
                                        $randomSize = rand($sizeOnData, 9);

                                        if ($sizeFromCal[$sizeLoc][$sizeArea][$randomSize] == 0) {
                                            $arrayTemp = array();
                                            $isFound = false;
                                            for ($i = $sizeOnData; $i <= 9; $i++) {
                                                if ($sizeFromCal[$sizeLoc][$sizeArea][$i] > 0) {
                                                    $isFound = true;
                                                    array_push($arrayTemp, $i);
                                                }
                                            }

                                            if ($isFound) {
                                                $randomSize = $arrayTemp[array_rand($arrayTemp)];
                                            } else {
                                                $randomSize = 0;
                                            }
                                            //$sizeFromCal[$sizeLoc][$sizeArea][$finalSize]--;
                                        }


                                        if ($randomSize > 0) {
                                            $tempTotal += $randomSize;
                                            $tempCheck += $sizeOnData;

                                            echo "random = " . $randomSize . " ava  = " . $sizeFromCal[$sizeLoc][$sizeArea][$randomSize] . " "
                                            . "SizeOnData = " . $sizeOnData . " ";
                                            $sizeFromCal[$sizeLoc][$sizeArea][$randomSize] --;
                                            echo "ลูก,คู่สมรส ดังนี้ ";
                                            foreach ($dataHH[$tempHHID] as $key => $vFromHH) {
                                                echo $vFromHH . " ";

                                                $sql_Update = "INSERT INTO `" . $table . "` (ID , HOUSENUM) "
                                                        . "VALUES ( " . $vFromHH . " , " . $tempHHID . " ) "
                                                        . "ON DUPLICATE KEY UPDATE HOUSENUM = VALUES (HOUSENUM) ";
                                                // echo "$sql_Update , $sized ,$numSized ,$tempHHID <br>";
                                                mysql_query($sql_Update);
                                            }
                                            echo " ";
                                            $diff = $randomSize - $sizeOnData;
                                            if ($diff > 0) {
                                                echo "ต้องการคนเพิ่ม $diff คน ";
                                                $tempP = NULL;
                                                for ($f = 0; $f < $diff; $f++) {
                                                    //$tempP = null;
                                                    if (isset($dataPeople[$sizeLoc][$sizeArea])) {
                                                        $tempP = array_rand($dataPeople[$sizeLoc][$sizeArea]);
                                                        if (isset($dataPeople[$sizeLoc][$sizeArea][$tempP])) {
                                                            echo $dataPeople[$sizeLoc][$sizeArea][$tempP] . " ";

                                                            $sql_Update = "INSERT INTO `" . $table . "` (ID , HOUSENUM) "
                                                                    . "VALUES ( " . $dataPeople[$sizeLoc][$sizeArea][$tempP] . " , " . $tempHHID . " ) "
                                                                    . "ON DUPLICATE KEY UPDATE HOUSENUM = VALUES (HOUSENUM) ";
                                                            //echo "$sql_Update , $sized ,$numSized ,$tempHHID <br>";
                                                            mysql_query($sql_Update);


                                                            unset($dataPeople[$sizeLoc][$sizeArea][$tempP]);
                                                            $keepPP++;
                                                        } else {

                                                            echo "ChangeTo = $sizeOnData ";
                                                        }
                                                    } else {

                                                        $needMorePP ++;
                                                        echo " NeedMore!! ";
                                                    }
                                                    //echo "ต้องการคนเพิ่ม $diff [$needMorePP][$keepPP] ";
                                                }
                                                echo "[$needMorePP][$keepPP]<br> ";
                                            }
                                            if ($diff == 0) {
                                                echo "พอดี<br>";
                                            }

                                            echo "<br>";
                                        } else if ($randomSize == 0) {
                                            $noStop = false;
                                            for ($inSize = 1; $inSize <= 9; $inSize++) {
                                                echo "[" . $inSize . "," . $sizeFromCal[$sizeLoc][$sizeArea][$inSize] . "] ";
                                                if ($sizeFromCal[$sizeLoc][$sizeArea][$inSize] != 0) {
                                                    $noStop = true;
                                                    break;
                                                }
                                            }

                                            if ($sizeFromCal[$sizeLoc][$sizeArea][10] == 0 && $noStop) {

                                                echo "ลูก,คู่สมรส ดังนี้3 ";
                                                foreach ($dataHH[$dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$r]] as $key => $vFromHH) {
                                                    echo $vFromHH . " ";
                                                    $sql_Update = "INSERT INTO `" . $table . "` (ID , HOUSENUM) "
                                                            . "VALUES ( " . $vFromHH . " , " . $tempHHID . " ) "
                                                            . "ON DUPLICATE KEY UPDATE HOUSENUM = VALUES (HOUSENUM) ";
                                                    //echo "$sql_Update , $sized ,$numSized ,$tempHHID <br>";
                                                    mysql_query($sql_Update);
                                                }
                                                echo "<br>";
                                            }

                                            if (!$noStop) {
                                                echo "SumEXcel(1-9) = $sumExcel คน";
                                                echo "<br>people from Data HavePR $tempCheck คน<br>";
                                                echo "people from Data PP $keepPP คน<br>";
                                                echo "Sum" . ($tempCheck + $keepPP ) . "คน<br>";
                                                echo "Must have(1-9) = $tempTotal คน<br>";
                                                echo "NeedMore = $needMorePP คน<br>";
                                                echo "DataPP = " . count($dataPeople[$sizeLoc][$sizeArea]) . "<br>";
                                                echo "SaveToTen = " . ($inCal - $r) . " ครอบครัว <br>";
                                                $saveHH = $inCal - $r;
                                                $saveHHID = array();
                                                $indexSaveHHID = 0;
                                                for ($s = $r; $s < $inCal; $s++) {

                                                    //$arrayToten[]
                                                    //echo "HHID size $sizeOnData " . $dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$s] . "<br>";
                                                    $saveHHID[$indexSaveHHID] = $dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$s];
                                                    $indexSaveHHID++;
                                                    foreach ($dataHH[$dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$s]] as $key => $vFromHH) {
                                                        echo "Head TO Ten $vFromHH  <br>";

                                                        $sql_Update = "INSERT INTO `" . $table . "` (ID , HOUSENUM) "
                                                                . "VALUES ( " . $vFromHH . " , " . $dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$s] . " ) "
                                                                . "ON DUPLICATE KEY UPDATE HOUSENUM = VALUES (HOUSENUM) ";
                                                        // echo "$sql_Update , $sized ,$numSized ,$tempHHID <br>";
                                                        mysql_query($sql_Update);
                                                    }
                                                    echo " ";
                                                }

                                                if (isset($saveMoreTen[$sizeLoc][$sizeArea])) {
                                                    foreach ($saveMoreTen[$sizeLoc][$sizeArea] as $key => $value) {
                                                        echo "HHID size " . $value["HHSIZE"] . " " . $value["HHID"] . " ";
                                                        echo "ลูก,คู่สมรส ดังนี้ ";
                                                        foreach ($dataHH[$value["HHID"]] as $key => $vFromHH) {
                                                            echo $vFromHH . " ";
                                                            $sql_Update = "INSERT INTO `" . $table . "` (ID , HOUSENUM) "
                                                                    . "VALUES ( " . $vFromHH . " , " . $value["HHID"] . " ) "
                                                                    . "ON DUPLICATE KEY UPDATE HOUSENUM = VALUES (HOUSENUM) ";
                                                            //echo "$sql_Update , $sized ,$numSized ,$tempHHID <br>";
                                                            mysql_query($sql_Update);
                                                        }
                                                        echo "<br>";
                                                    }
                                                }
                                                $savePP = count($dataPeople[$sizeLoc][$sizeArea]);


                                                echo "เท่ากับ $savePP คน<br>";

                                                for ($t = 0; $t < $savePP; $t++) {
                                                    if ($t % $saveHH == 0) {
                                                        echo "<br>";
                                                    }

                                                    if (isset($dataPeople[$sizeLoc][$sizeArea])) {
                                                        $tempPS = array_rand($dataPeople[$sizeLoc][$sizeArea]);
                                                        echo $dataPeople[$sizeLoc][$sizeArea][$tempPS] . " ";
                                                        $sql_Update = "INSERT INTO `" . $table . "` (ID , HOUSENUM) "
                                                                . "VALUES ( " . $dataPeople[$sizeLoc][$sizeArea][$tempPS]
                                                                . " , " . $saveHHID[$t % $saveHH] . " ) "
                                                                . "ON DUPLICATE KEY UPDATE HOUSENUM = VALUES (HOUSENUM) ";
                                                        //echo "$sql_Update , $sized ,$numSized ,$tempHHID <br>";
                                                        mysql_query($sql_Update);

                                                        unset($dataPeople[$sizeLoc][$sizeArea][$tempPS]);
                                                    }
                                                }
                                                break;
                                            }
                                        }
                                    }////////////////////////////////incal
                                } else {

                                    $inCal = count($dataMinSize[$sizeLoc][$sizeArea][$sizeOnData]);
                                    $inTen = $sizeFromCal[$sizeLoc][$sizeArea][10];
                                    echo "10up " . $inCal . "ครัวเรือน  Size10 ON Excel $inTen <br>";

                                    for ($st = 0; $st < $inCal; $st++) {
                                        if ($inTen != 0) {
                                            if (!isset($saveMoreTen[$sizeLoc][$sizeArea])) {
                                                $saveMoreTen[$sizeLoc][$sizeArea][0] = array(
                                                    "HHID" => $dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$st],
                                                    "HHSIZE" => $sizeOnData
                                                );

                                                echo "ลูก,คู่สมรส ดังนี้1 ";
                                                foreach ($dataHH[$dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$st]] as $key => $vFromHH) {
                                                    echo $vFromHH . " ";
                                                    $sql_Update = "INSERT INTO `" . $table . "` (ID , HOUSENUM) "
                                                            . "VALUES ( " . $vFromHH . " , " . $dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$st] . " ) "
                                                            . "ON DUPLICATE KEY UPDATE HOUSENUM = VALUES (HOUSENUM) ";
                                                    //echo "$sql_Update , $sized ,$numSized ,$tempHHID <br>";
                                                    mysql_query($sql_Update);
                                                }
                                                echo "<br>";
                                                continue;
                                            }

                                            array_push($saveMoreTen[$sizeLoc][$sizeArea], array(
                                                "HHID" => $dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$st],
                                                "HHSIZE" => $sizeOnData
                                                    )
                                            );

                                            echo "ลูก,คู่สมรส ดังนี้2 ";
                                            foreach ($dataHH[$dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$st]] as $key => $vFromHH) {
                                                echo $vFromHH . " ";
                                                $sql_Update = "INSERT INTO `" . $table . "` (ID , HOUSENUM) "
                                                        . "VALUES ( " . $vFromHH . " , " . $dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$st] . " ) "
                                                        . "ON DUPLICATE KEY UPDATE HOUSENUM = VALUES (HOUSENUM) ";
                                                //echo "$sql_Update , $sized ,$numSized ,$tempHHID <br>";
                                                mysql_query($sql_Update);
                                            }
                                            echo "<br>";
                                        } else {
                                            echo "ลูก,คู่สมรส ดังนี้4 ";
                                            foreach ($dataHH[$dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$st]] as $key => $vFromHH) {
                                                echo $vFromHH . " ";
                                                $sql_Update = "INSERT INTO `" . $table . "` (ID , HOUSENUM) "
                                                        . "VALUES ( " . $vFromHH . " , " . $dataMinSize[$sizeLoc][$sizeArea][$sizeOnData][$st] . " ) "
                                                        . "ON DUPLICATE KEY UPDATE HOUSENUM = VALUES (HOUSENUM) ";
                                                //echo "$sql_Update , $sized ,$numSized ,$tempHHID <br>";
                                                mysql_query($sql_Update);
                                            }
                                            echo "<br>";
                                        }
                                    }

                                    //echo "HHID size " . $value["HHSIZE"] . " " . $value["HHID"] . " ";
                                }
                            }
                            echo "<br> จำนวนของแค่ละsizeที่เหลือ(Excel)  = ";
                            for ($inSize = 1; $inSize <= 10; $inSize++) {
                                echo "[" . $inSize . "," . $sizeFromCal[$sizeLoc][$sizeArea][$inSize] . "] ";
                            }
                            echo "<br>";


                            // break;
                        }
                        //break;
                    }
                }
            }

            $arrHead = array();
            $str = "SELECT ID , HOUSENUM "
                    . "FROM `" . $table . "` "
                    . "WHERE HOUSENUM != 0 "
                    . "AND HH_status = 'Head' ";
            echo $str . "<br>";
            $resultHead = mysql_query($str) or die(mysql_error());
            while ($saveAllHead = mysql_fetch_object($resultHead)) {
                $arrHead[$saveAllHead->HOUSENUM] = $saveAllHead->ID;
            }
            mysql_free_result($resultHead);

            for ($i = 1; $i <= 9; $i++) {
                $str = "SELECT Location ,Area , HOUSENUM "
                        . "FROM `" . $table . "` "
                        . "WHERE HOUSENUM != 0 "
                        . "GROUP BY HOUSENUM "
                        . "HAVING COUNT(*) = $i "
                        . "ORDER BY ID";
                echo $str . "<br>";
                $result = mysql_query($str) or die(mysql_error());
                while ($saveAll = mysql_fetch_object($result)) {
                    //Up Size
                    $sql_Update = "INSERT INTO `" . $table . "` (ID , HH_SIZE) "
                            . "VALUES ( " . $arrHead[$saveAll->HOUSENUM] . " , " . $i . ") "
                            . "ON DUPLICATE KEY UPDATE HH_SIZE = VALUES (HH_SIZE) ";
                    echo $sql_Update . "<br>";
                    mysql_query($sql_Update) or die(mysql_error());
                }
                mysql_free_result($result);
            }

            $str = "SELECT Location ,Area , HOUSENUM "
                    . "FROM `" . $table . "` "
                    . "WHERE HOUSENUM != 0 "
                    . "GROUP BY HOUSENUM "
                    . "HAVING COUNT(*) > 9 "
                    . "ORDER BY ID";
            echo $str . "<br>";
            $result = mysql_query($str) or die(mysql_error());
            while ($saveAll = mysql_fetch_object($result)) {
                //Up Size
                $sql_Update = "INSERT INTO `" . $table . "` (ID , HH_SIZE) "
                        . "VALUES ( " . $arrHead[$saveAll->HOUSENUM] . " , 10 ) "
                        . "ON DUPLICATE KEY UPDATE HH_SIZE = VALUES (HH_SIZE) ";
                echo $sql_Update . "<br>";
                mysql_query($sql_Update) or die(mysql_error());
            }
            mysql_free_result($result);


            $strUpSize = "UPDATE `" . $table . "` "
                    . "SET HH_Size = '1' "
                    . "WHERE HOUSENUM = '0' "
                    . "AND HH_STATUS = 'Head' ";
            echo $strUpSize . "<br>";
            mysql_query($strUpSize) or die(mysql_error());

            $strUpHHID = "UPDATE `" . $table . "` "
                    . "SET HH_ID = HOUSENUM "
                    . "WHERE HOUSENUM != '0' "
                    . "AND HH_ID = '0' ";
            echo $strUpHHID . "<br>";
            mysql_query($strUpHHID) or die(mysql_error());
            
            
            
            ////////////////////////////HOT-FIX/////////////////////////////////
            //FIX-STATUS: 'OTHER'
             $str = "SELECT ID , HOUSENUM , HaveParentID ,HH_Status "
                    . "FROM `" . $table . "` "
                    . "ORDER BY HOUSENUM";
            echo $str . "<br>";
            $tempHavePID = array();
            $markChild = array();
            $markParent = array();

            $result = mysql_query($str) or die(mysql_error());
            while ($saveAll = mysql_fetch_object($result)) {
                if ($saveAll->HH_Status == 'Head' || $saveAll->HH_Status == 'Spouse') {
                    if (!isset($markParent[$saveAll->HOUSENUM])) {
                        $markParent[$saveAll->HOUSENUM][0] = $saveAll->ID;
                    } else {
                        array_push($markParent[$saveAll->HOUSENUM], $saveAll->ID);
                    }
                } else if ($saveAll->HH_Status == 'Child') {
                    if (!isset($markChild[$saveAll->HOUSENUM])) {
                        $markChild[$saveAll->HOUSENUM][0] = $saveAll->ID;
                    } else {
                        array_push($markChild[$saveAll->HOUSENUM], $saveAll->ID);
                    }

                    $tempHPID[$saveAll->ID] = array("HNUM" => $saveAll->HOUSENUM, "HPID" => $saveAll->HaveParentID);
                }
            }
            mysql_free_result($result);

            foreach ($tempHPID as $idChild => $objChild) {
                $checkOther = false;
                foreach ($markParent[$objChild["HNUM"]] as $numP => $idParent) {
                    if ($idParent == $objChild["HPID"]) {
                        $checkOther = true;
                    }
                }

                if ($checkOther) {
                    $sql_Update = "INSERT INTO `" . $table . "` (ID , HH_Status) "
                            . "VALUES ( " . $idChild . " , 'Other' ) "
                            . "ON DUPLICATE KEY UPDATE HH_Status = VALUES (HH_Status) ";
                    echo " $sql_Update<br>";
                    mysql_query($sql_Update);
                }
            }
            

            $time_end = microtime(true);
            $time = $time_end - $time_start;
            $hours = (int) ($time / 60 / 60);
            $minutes = (int) ($time / 60) - $hours * 60;
            $seconds = (int) $time - $hours * 60 * 60 - $minutes * 60;
            echo "<br>Time: $hours hours/ $minutes minutes/ $seconds seconds. <br>";


            mysql_close($objConnect);
        } catch (Exception $ex) {
            echo 'exception: ', $ex->getMessage(), "<br>";
            mysql_close($objConnect);
        }
        ?>
    </body>
</html>