<html>
    <head>
        <meta charset="UTF-8">
        <title> Update Gender for SimPhitlok </title>
    </head>
    <body>
        <?php
//-------------- Initiation -----------------------------------------------------
        //---- Set up --------
        /** PHPExcel */
        require_once 'Classes/PHPExcel.php';

        /** PHPExcel_IOFactory - Reader */
        include 'Classes/PHPExcel/IOFactory.php';

        $inputFileName = "2_gender.xlsx";								//1 
        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);  //2
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);  //3
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($inputFileName);			    //4
        //ActiveSheet is a sheet currently selected to show in the program.
        $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);			//5
        $highestRow = $objWorksheet->getHighestRow();						//6
        $highestColumn = $objWorksheet->getHighestColumn();					//6

        //rangeToArray(string $pRange, mixed $nullValue, boolean $calculateFormulas, boolean $formatData, boolean $returnCellRef) : array
        //http://hitautodestruct.github.io/PHPExcelAPIDocs/classes/PHPExcel_Worksheet.html

        /*for the better understanding
        	$headingsArray is an array that keeps only all headings of the table.
        	$namedDataArray keep the data in each cell.
			$columnKey is a word on the top of each col.
     		$columnHeading = male, female used in 2.2
        */


        $headingsArray = $objWorksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true, true);														   
        //A1 = first col. in Excel  									    //6
        $headingsArray = $headingsArray[1];									//6


        //------ Keep data in $namedDataArray ------	2.1
        $r = -1;
        $namedDataArray = array();
        for ($row = 2; $row <= $highestRow; ++$row) {
            $dataRow = $objWorksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, true, true);  //=an array 2D that keeps all Data.

            if ((isset($dataRow[$row]['A'])) && ($dataRow[$row]['A'] > '')) { 
                ++$r;			   	//isset($var) determines if a variable is set and is not NULL.
                //http://php.net/manual/en/control-structures.foreach.php
                foreach ($headingsArray as $columnKey => $columnHeading) {
                    $namedDataArray[$r][$columnHeading] = $dataRow[$row][$columnKey];
                }				  //keeps the data in each cell into $namedDataArray[?][?] 
            }
        }
        ?>

        <?php
//----------------- AddGender ---------------------------------------------------------------
        //---- Set up --------
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        $time_start = microtime(true);
        //*** Connect to MySQL Database ***//
        $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //Changed
        $objDB = mysql_select_db("SimPhit") or die("เลือกฐานข้อมูลไม่ได้");
        $table = 'People';
        mysql_query("SET NAMES UTF8");
        mysql_query("SET character_set_results=UTF8");
        mysql_query("SET character_set_client=UTF8");
        mysql_query("SET character_set_connection=UTF8");

        //http://php.net/manual/en/control-structures.foreach.php
        //----- Cal & Keep the data in $male, $female ------  2.2
        foreach ($namedDataArray as $result) {
            $number = 835555;   //จำนวนคนทั้งหมดของจังหวัด
            $PercentMale = floatval($result["male"]);	//floatval: Get float value of a variable
            $PercentFemale = floatval($result["female"]);
            //echo gettype($PercentMale);
            $male = round(($PercentMale * $number) / 100);	//round: <.5 is down, >=.5 is up
            $female = round(($PercentFemale * $number) / 100);
            //echo $number."<br>";
            echo "male = $male<br>";
            echo "female = $female<br>";
        }

        $StartMale = 1;

        //---- Add Male ------
        for ($i = $StartMale; $i <= $male; $i++) {
            $strSQL = "";
            $strSQL .= "INSERT INTO `" . $table . "`";	//"x.=1" is "x +=1" is "x=x+1" 
            $strSQL .= "(Gender) ";
            $strSQL .= "VALUES ";
            $strSQL .= "('male')";
            mysql_query($strSQL) or die(mysql_error());
            //echo "Row $i Inserted...<br>";
        }
        echo "success male = $male <br>";

        //---- Add Female ------
        for ($i = $male + $StartMale; $i <= $male + $female; $i++) {
            $strSQL = "";
            $strSQL .= "INSERT INTO `" . $table . "` ";
            $strSQL .= "(Gender) ";
            $strSQL .= "VALUES ";
            $strSQL .= "('female')";
            mysql_query($strSQL) or die(mysql_error());
            //echo "Row $i Inserted...<br>";
        }
        echo "success female = $female <br>";
       
//------------ Config -----------------------------------
		//Optimize a table       
        $config = "OPTIMIZE TABLE ".$table;
        echo $config . "<br>";
        mysql_query($config);

        mysql_close($objConnect);

        //About the time
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $hours = (int) ($time / 60 / 60);
        $minutes = (int) ($time / 60) - $hours * 60;
        $seconds = (int) $time - $hours * 60 * 60 - $minutes * 60;
        echo "Process Time: $hours hours/ $minutes minutes/ $seconds seconds. <br>";
        ?>
    </body>
</html>