<?php
/**
 * @copyright 2022 - JAEMACOM-GmbH <https://www.jaemacom.de>
 * @author Omid Malekzadeh Eshtajarani <omid.malekzadeh-eshtajarani@jaemacom.de>
 * @version PHP version 7.4.29 
 */
define("DEBUG", true);
define("MESSAGES", [
    "english" => [
        0   => "No message! 👍",
        1   => "Successfully converted",
        2   => "Somthing wrong with sumbit",
        3   => "File csv not set",
        4   => "File with erros",
        5   => "Invalid data type",
        6   => "File name must include just one '.'",
        7   => "File type must be csv",
        8   => "File is bigger than 5mb",
        9   => "File exists",
        10  => "File not saved",
        11  => "Read and write failed",
        12  => "Unknown error call the developer team",
        13  => "Unable to open file!",
        14  => "Unable to open file!!",
        15  => "Unable to open file!!!",
        16  => "Upload the CSV",
        17  => "Choose the CSV",
        18  => "Download",
        19  => "Convert",
        20  => "Messages display",
        21  => "Click to download",
        22  => "Click to convert"
    ],
    "german" => [
        0 => "Keine Meldung! 👍",
        1 => "Erfolgreich konvertiert",
        2 => "Etwas stimmt nicht mit Sumbit",
        3 => "Datei csv wurde nicht ausgewählt",
        4 => "Datei mit Fehlern",
        5 => "Ungültiger Datentyp",
        6 => "Dateiname muss nur ein '.' enthalten",
        7 => "Dateityp muss csv sein",
        8 => "Datei ist größer als 5 MB",
        9 => "Datei existiert",
        10 => "Datei nicht gespeichert",
        11 => "Lesen und Schreiben fehlgeschlagen",
        12 => "Unbekannter Fehler Entwicklerteam anrufen",
        13 => "Datei kann nicht geöffnet werden!",
        14 => "Datei kann nicht geöffnet werden!",
        15 => "Datei kann nicht geöffnet werden!!!",
        16  => "Lade die CSV-Datei hoch",
        17  => "Wähle die CSV-Datei aus",
        18  => "Herunterladen",
        19  => "Konvertieren",
        20  => "Meldungensfenster",
        21  => "Klicke zum Herunterladen",
        22  => "Klicke zum Konvertiren"
    ]
]);
define("UPLOAD_DIRECTORY", "./");
define("ALLOWED_MIMES", [
    "text/csv",
]);
define("FILE_LIMIT_SIZE", 5000000);
$language = "german";
$download = false; 
$download_file = "";
$message = MESSAGES[$language][0];

if (!DEBUG) {
    ini_set('html_error', 'false');
    ini_set('error_reporting','false');
    ini_set('display_startup_errors', 'false');
    ini_set('post_max_size','999m');
    error_reporting(E_USER_ERROR);
    set_error_handler(function(){});
}
function show_message($message_code, $lang) {
    $message = MESSAGES[$lang][$message_code];
}

try { 
    if (count($_POST) > 1 && isset($_POST["submit"])) show_message(2, $language);
    if (isset($_POST["submit"])) {
        if (!isset($_FILES["csv"])) show_message(3, $language);
        if ($_FILES["csv"]["error"] > 0) show_message(4, $language);
        if (!in_array($_FILES["csv"]["type"], ALLOWED_MIMES)) show_message(5, $language);
        if (count(explode(".", $_FILES["csv"]["name"])) != 2) show_message(6, $language);
        if (count(explode(".csv", $_FILES["csv"]["name"])) != 2 || strlen(explode(".csv", $_FILES["csv"]["name"])[1]) != 0) show_message(7, $language);
        if ($_FILES["csv"]["size"] > FILE_LIMIT_SIZE) show_message(8, $language);

        // not yet used
        // $time_as_file_name = (UPLOAD_DIRECTORY . ($saving_time = DateTime::createFromFormat('U.u', microtime(TRUE))->format('Y_m_d_H_i_s_u')) . ".csv");
        
        $file_name = (UPLOAD_DIRECTORY . basename($_FILES["csv"]["name"]));
        if (file_exists($file_name)) show_message(9, $language);
        if (!move_uploaded_file($_FILES["csv"]["tmp_name"], $file_name)) show_message(MESSAGES[$language][10], $language);

        $saved_file = null;
        try {
            $saved_file = fopen($file_name,"r") or show_message(MESSAGES[$language][13], $language);
            $first_line = 1;
            $column_name_line = 1;
            $first_data_line = 1;
            $last_data_line = 1;
            $last_line = count(file($file_name));
            $line_counter = 1;

            while ($inline = fgets($saved_file)) {
                if (strpos(strtolower($inline),"umsatz", 1) != false) {
                    $column_name_line =  $line_counter;
                    $first_data_line =  $column_name_line + 1;
                    break;
                } 
                $line_counter++;
            }

            while ($inline = fgets($saved_file)) {
                $line_array =  explode(";", $inline);
                if (strlen(trim(join("", $line_array))) == 0 || !preg_match('/^[0-9,]+$/',$line_array[0])) {
                    $last_data_line = $line_counter;
                    break;
                } else if ($line_counter == $last_line - 1) {
                    $last_data_line = $last_data_line == 1 ? $line_counter + 1 : $last_data_line;
                    $last_line = $line_counter + 1;
                    break;
                }
                $line_counter++;
            }

            fclose($saved_file);

            $saved_file = fopen($file_name,"r") or show_message(MESSAGES[$language][13], $language);
            $line_counter = 0;
            $head_line_array = [];
            for ($head_line_index = 0; $head_line_index < $column_name_line; $head_line_index++) {
                $head_line =  fgets($saved_file);
                array_push($head_line_array, explode(";",$head_line));
            }
    
    

            $second_row_as_array = $head_line_array[$column_name_line-1];
            $account_entries = [];
            $index_of_account_column = array_search("Kontonummer", $second_row_as_array);
            $index_of_opposite_account_column = array_search("Gegenkonto ohne BU Schluessel", $second_row_as_array);
            $index_of_belegfeld_1 = array_search("Belegfeld 1", $second_row_as_array);
            $index_of_buchungstext = array_search("Buchungstext", $second_row_as_array);
            $index_of_sales_without_a_mark = array_search("Umsatz ohne S/H Kennzeichen", $second_row_as_array);
            $begin_the_customer_paragraph = count(file($file_name));
            $customer_array = [];
            $combinated_buchungstext = [];



            for ($line_index = $first_data_line; $line_index <= $last_data_line; $line_index++) {
                $line = fgets($saved_file);
                $column_data_array = explode(";", $line);
                $combination_key_account_opposite_account = "";

                if (strlen(trim(join("",$column_data_array))) != 0) {
                    for ($column_index = 0; $column_index <= count($column_data_array); $column_index++) {
                        if ($column_index === $index_of_account_column)
                            $combination_key_account_opposite_account .= $column_data_array[$column_index].",";
                        if ($column_index === $index_of_opposite_account_column)
                            $combination_key_account_opposite_account .= $column_data_array[$column_index].",";
                        if ($column_index === $index_of_belegfeld_1)
                            $combination_key_account_opposite_account .= $column_data_array[$column_index];
                    }
                    if (!array_key_exists($combination_key_account_opposite_account, $account_entries)) {
                        $account_entries[$combination_key_account_opposite_account]["umsatz"] = 0.0;
                        $combinated_buchungstext[$combination_key_account_opposite_account]["buchungstext"] = "";
                    }
                }
            }

            for ($footer_line_index = $last_data_line; $footer_line_index < $last_line; $footer_line_index++) {
                $customer_line = fgets($saved_file);
                array_push($customer_array, explode(";",  $customer_line));
            } 

            fclose($saved_file);

            $saved_file = fopen($file_name, "r") or show_message(MESSAGES[$language][13], $language);

            for ($head_line_index = 0; $head_line_index < $column_name_line; $head_line_index++) {
                $head_line =  fgets($saved_file);
            }

            for ($line_index = $first_data_line; $line_index <= $last_data_line; $line_index++) {
                $line = fgets($saved_file);
                $column_data_array = explode(";", trim($line));
                if (strlen(trim(join("",$column_data_array))) != 0) {

                    for ($column_index = 0; $column_index < count($column_data_array); $column_index++) {
                        $combination_key = $column_data_array[$index_of_account_column].','.$column_data_array[$index_of_opposite_account_column].','.$column_data_array[$index_of_belegfeld_1];
                        if ($column_index === $index_of_sales_without_a_mark) {  
                            $combination_key = $column_data_array[$index_of_account_column].','.$column_data_array[$index_of_opposite_account_column].','.$column_data_array[$index_of_belegfeld_1];
                            $point_formatted_sales = floatval(preg_replace('/,/', '.', $column_data_array[$index_of_sales_without_a_mark]));
                            if (strlen(trim(str_replace(",","", $combination_key))) != 0) {
                                if (array_key_exists($combination_key, $account_entries))
                                    $account_entries[$combination_key]["umsatz"] = $account_entries[$combination_key]["umsatz"] + $point_formatted_sales;  
                            }
                        } else {
                            if (!empty(trim($second_row_as_array[$column_index]))) {
                                if (trim($second_row_as_array[$column_index]) == "Belegdatum") {
                                    $account_entries[$combination_key][trim($second_row_as_array[$column_index])] =  substr($column_data_array[$column_index], 0, -4);
                                } else if (trim($second_row_as_array[$column_index]) == "Buchungstext") {   
                                    if (strlen(trim(str_replace(",","", $combination_key))) != 0) {
                                        $is_empty = strlen($combinated_buchungstext[$combination_key]["buchungstext"]) == 0;
                                        $combinated_buchungstext[$combination_key]["buchungstext"] =  $is_empty ? trim($column_data_array[$column_index]) : $combinated_buchungstext[$combination_key]["buchungstext"]  .", ". trim($column_data_array[$column_index]); 
                                    }
                                } else {
                                    $account_entries[$combination_key][$second_row_as_array[$column_index]] = $column_data_array[$column_index];
                                }
                            }
                        }
                    }
                    if (strlen(trim(str_replace(",","", $combination_key))) != 0) {
                        if (array_key_exists($combination_key, $combinated_buchungstext)) {
                            $account_entries[$combination_key]["Buchungstext"] = trim($combinated_buchungstext[$combination_key]["buchungstext"]) ;
                        } 
                    }
                }  

            }

            /**
             * Schreiben neues CSV
             */
           
            $print_header = isset($_POST["print-header-lines"]) &&  $_POST["print-header-lines"] === "on" ? true : false;
            $print_column_names = isset($_POST["print-column-name"]) &&  $_POST["print-column-name"] === "on" ? true : false;
            $print_body = true;
            $print_footer = isset($_POST["print-footer-lines"]) &&  $_POST["print-footer-lines"] === "on" ? true : false;

            // print_r($_POST["head-line-text"]);

            $formatted_file = fopen($file_name, "w") or show_message(MESSAGES[$language][15], $language);

            if ($print_header)
                for ($head_line_index = 0; $head_line_index < $column_name_line - 1; $head_line_index++)
                    fwrite($formatted_file, join(";",$head_line_array[$head_line_index]));

            if ($print_column_names)
                fwrite($formatted_file, join(";",$head_line_array[$column_name_line-1]));

            $line_count = 0;
            if ($print_body)
                foreach ($account_entries as $key => $line_array) {
                    $formatted_line = "";
                    $column_count = 0;

                    if ($line_count < $last_data_line) {
                        foreach ($line_array as $column_key => $column) {
                            if ($column_key === "umsatz") {
                                $comma_formatted_sales = preg_replace('/\./', ',',strval($column));
                                $formatted_line .= $comma_formatted_sales.";";
                            } else {
                                $formatted_line .= $column.";";
                            }
                            $column_count++;
                        }
                        fwrite($formatted_file, $formatted_line."\n");
                    }
                    $line_count++;
                }

            if ($print_footer)
                for ($footer_line_index = 0; $footer_line_index < $last_line -  $last_data_line; $footer_line_index++)
                    fwrite($formatted_file, join(";",$customer_array[$footer_line_index]));

            fclose($formatted_file);

            $download_file = $file_name;
            $download = true;
            $message = MESSAGES[$language][1];
        } catch (Exception $exception) {
            show_message(MESSAGES[$language][11], $language);
        } finally {
            fclose($saved_file);
        }  
    }        
} catch (Exception $exception) {
    $message = MESSAGES[$language][12];
}
if (!DEBUG)
    restore_error_handler();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="./favicon.ico">
    <script type="text/javascript">
        "use strict";
        /**
         * Initilize when the page is loaded
         */
        window.onload = init;

        /**
         * Initialize JS-Entry-Point
         */
        function init(){
            const fileLabel = document.querySelector(".main-upload-container-form-select-file-label");
            const message = document.querySelector(".main-upload-container-message");
            const form = document.querySelector(".main-upload-container-form");
            const fileInput = document.querySelector(".main-upload-container-form-select-file");
            const headlineCheckboxContainer = document.querySelector(".main-upload-container-form-checkbox-container");
            const headlineCheckbox = document.getElementById("head-line");
            if (headlineCheckbox != null){
                const headLineTextField = document.createElement("input");
                headLineTextField.classList.add("main-upload-container-form-checkbox-container-headline-textfield");
                headLineTextField.type = "text";
                headLineTextField.name = "head-line-text";
                headLineTextField.placeholder = "schreibe deine eigene Kopfzeile (EXTF...)"
                headlineCheckbox.addEventListener("input", function(e){
                
                    if(e.target.checked){
                        headlineCheckboxContainer.appendChild(headLineTextField);
                    } else{
                        headlineCheckboxContainer.removeChild(headLineTextField);
                    }
    
                });
            }

            if (fileInput != null)
                fileInput.addEventListener("input", function(e){
                    message.classList.remove("error");
                    if (e.target.files.length == 1) {
                        const file = e.target.files[0];
                        const fileName = file.name;
                        let currentDatatype = file.type === "text/csv";
                        let currentDataName = file.name.split(".").length == 2;
                        let currentDataSize = file.size < 5000000;
                        if (currentDatatype) {
                            if (currentDataName) {
                                let downloadLink = document.querySelector(".main-upload-container-form-download-link");
                                message.textContent = "<?=MESSAGES[$language][0]?>";

                                if (currentDataSize) {
                                    const oldConvertButton = form.querySelector(".main-upload-container-form-submit-button");
                                    fileLabel.textContent = fileName;
                                    const convertButton = document.createElement("input");
                                    convertButton.classList.add("main-upload-container-form-submit-button");
                                    convertButton.type = "submit";
                                    convertButton.name = "submit";
                                    convertButton.value = "<?=MESSAGES[$language][19]?>";
                                    convertButton.title = "<?=MESSAGES[$language][22]?>";
                                    if (oldConvertButton != null) {
                                        form.removeChild(oldConvertButton);
                                    }
                                    if (downloadLink != null){
                                        form.removeChild(downloadLink);
                                    }
                                    form.appendChild(convertButton);
                                } else {
                                    message.textContent = "<?=MESSAGES[$language][8]?>";
                                }
                            } else {
                                message.textContent = "<?=MESSAGES[$language][6]?>";
                            }
                        } else {
                            message.textContent = "<?=MESSAGES[$language][5]?>";
                        }

                        if (!currentDatatype || !currentDataName || !currentDataSize){
                            message.classList.add("error");
                            const oldConvertButton = form.querySelector(".main-upload-container-form-submit-button");
                            let downloadLink = document.querySelector(".main-upload-container-form-download-link");
                            if (oldConvertButton != null) {
                                form.removeChild(oldConvertButton);
                            }
                            if (downloadLink != null){
                                form.removeChild(downloadLink);
                            }
                        }
                    }
                });
        }

    </script>
    <title>CSV-Parser in PHP</title>
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            font-size: 1.2rem;
            /* user-select:none; */
        } 

        html, body {
            width: 100vw;
            height: 100vh;
        }

        .main {
            width: 100vw;
            height: 100vh;
            background-color: white; 
            opacity: 0.8;
        }

        .main-upload-container {
            height: 100%;
            width:calc(100% / 2);
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin:0 auto;
        }

        .main-upload-container-logo-frame{
            display: flex;
            justify-content: center;
            width: 100px;
            margin: 0 auto;
            padding: 40px;
        }

        .main-upload-container-logo-frame-image {
            width: 128px;
            height: 128px;
        }

        .main-upload-container-message {
            width: 100%;
            border: 1px solid green;
            border-radius: 1rem;
            margin: 0 auto;
            padding: 30px;
            text-align: center;
            color: green;
            background-color: white;
            max-width: 500px;
        }

        .main-upload-container-message.error {
            color: red;
            border: 1px solid red;
        }

        .main-upload-container-title {
            width:fit-content;
            margin: 20px auto 30px auto;
            padding:10px;
            display: block;
            border-radius: 0 0 100% 100%;
            text-align: center;
            background-color: white;
        }

        .main-upload-container-title::after {
            content: "↓";
            display: block;
            margin-top: 20px;
            font-size: 2rem;
            color: green;
            font-weight: bold;
            animation-name: arrow;
            animation-duration: 0.7s;
            animation-iteration-count: infinite;
            position: relative;
        }

        @keyframes arrow {
            0%{
                top:-10px;
            }
            100%{
                top: 10px;
            }
        }

        .main-upload-container-form-select-file-label {
            margin: 10px auto;
            width: 80%;
            border: 1px solid #ccc;
            border-radius: 0.7rem;
            font-size: 1.2rem;
            display: block;
            padding: 15px;
            text-align: center;
            color: gray;
            cursor: pointer;
            background-color: white;
            max-width: 500px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .main-upload-container-form-select-file-label:hover,
        .main-upload-container-form-select-file-label:focus{
            border: 1px solid greenyellow;
            background-color: #adff2f;
            color:black;
        }

        .main-upload-container-form-select-file {
            display: none;
            width: 50%;
            background-color: white;
        }

        .main-upload-container-form-checkbox-container {
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            padding: 10px;
            margin: 0 auto;
            max-width: 600px;  
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        } 

        .main-upload-container-form-checkbox-container > label {
            font-size: 0.8rem;
            margin: 0 4px;
            color:gray;
        }

        .main-upload-container-form-checkbox-container > label:hover,
        .main-upload-container-form-checkbox-container > label:focus {
            cursor: pointer;
            transform: scale(1.1);
            color:black;
        }

        .main-upload-container-form-checkbox-container-headline-textfield {
            width:100%;
            margin: 30px auto 0 auto;
            border: 1px solid gray;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            padding: 5px;
        }

        .main-upload-container-form-checkbox-container-headline-textfield::-webkit-input-placeholder {
            font-size: 0.8rem;
        }
        .main-upload-container-form-checkbox-container-headline-textfield::-moz-placeholder {
            font-size: 0.8rem;
        }
        .main-upload-container-form-checkbox-container-headline-textfield:-ms-input-placeholder {
            font-size: 0.8rem;
        }
        .main-upload-container-form-checkbox-container-headline-textfield:-moz-placeholder {
            font-size: 0.8rem;
        }

        .main-upload-container-form-submit-button {
            width: 50%;
            display: block;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid white;
            border-radius: 1rem;
            color: gray;
            transition: 0.3s ease-in-out;
            max-width: 200px;
        }
        
        .main-upload-container-form-submit-button:hover,
        .main-upload-container-form-submit-button:focus {
           background-color: greenyellow;
           cursor: pointer;
           color: black;
           transition: 0.3s ease-in-out;
        }

        .main-upload-container-form-download-link {
            width: fit-content;
            display: block;
            margin: 50px auto;
            background-color: orange;
            padding: 20px;
            border: 1px solid white;
            border-radius: 1rem;
            color: black;
            transition: 0.3s ease-in-out;
            text-align: center;
            text-decoration: none;
            max-width: 200px;
        }

        .main-upload-container-form-download-link:hover,
        .main-upload-container-form-download-link:focus {
            background-color: orangered;
            transition: 0.3s ease-in-out;
            color: white;
        }
    </style>
</head>
<body>
    <div class="main">
        <div class="main-upload-container">
            <figure class="main-upload-container-logo-frame">
                <img class="main-upload-container-logo-frame-image" src="logo.png" alt="logo" title="CSV-Parser">
            </figure>
            <?php 
                if ($message === MESSAGES[$language][0] || $message ===  MESSAGES[$language][1])
                    echo '<div class="main-upload-container-message" title="'.MESSAGES[$language][20].'">'.$message.'</div>';
                else
                    echo '<div class="main-upload-container-message error" title="'.MESSAGES[$language][20].'">'.$message.'</div>';
            ?>
            <div class="main-upload-container-title" title="<?=MESSAGES[$language][16]?>">
                <?=MESSAGES[$language][16]?>
            </div>
            <form class="main-upload-container-form" method="post" action="./index.php" enctype="multipart/form-data">
                <label for="file" class="main-upload-container-form-select-file-label" title="<?=MESSAGES[$language][17]?>"><?=MESSAGES[$language][17]?></label>
                <input id="file" class="main-upload-container-form-select-file" type="file" name="csv" />

                <div class="main-upload-container-form-checkbox-container" >
                    <label for="head-line">drucke Kopfzeilen</label>
                    <input id="head-line" type="checkbox" name="print-header-lines"/>
                    <label for="column-line">drucke Splatennamen</label>
                    <input id="column-line" type="checkbox" name="print-column-name" checked/>
                    <label for="footer-line">drucke Fußzeilen</label>
                    <input id="footer-line" type="checkbox" name="print-footer-lines"/>
                </div>

                <?php 
                    if ($download) {
                        echo '<a class="main-upload-container-form-download-link" title="'.MESSAGES[$language][21].'" href="'.$download_file.'" download >'.MESSAGES[$language][18].'</a>';
                    }
                ?>
                <noscript>
                    <input class="main-upload-container-form-submit-button" type="submit" title="<?=MESSAGES[$language][22]?>" name="submit" value="<?=MESSAGES[$language][19]?>">
                </noscript>
            </form>
        </div>
    </div>
</body>
</html>