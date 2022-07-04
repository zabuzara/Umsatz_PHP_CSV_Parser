<?php
/**
 * @author Omid Malekzadeh Eshtajarani <omid.malekzadeh-eshtajarani@jaemacom.de>
 * @copyright 2022 - JAEMACOM-GmbH <https://www.jaemacom.de>
 * @version PHP version 7.4.29 
 */
define("DEBUG", false);
define("MESSAGES", [
    "english" => [
        0   => "No message is a good message! 👍",
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
    ],
    "german" => [
        0 => "Keine Nachricht ist eine gute Nachricht! 👍",
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
    if (count($_POST) > 1 && isset($_POST["submit"])) show_message(2, $languag);
    if (isset($_POST["submit"])) {
        if (!isset($_FILES["csv"])) show_message(3, $languag);
        if ($_FILES["csv"]["error"] > 0) show_message(4, $languag);
        if (!in_array($_FILES["csv"]["type"], ALLOWED_MIMES)) show_message(5, $languag);
        if (count(explode(".", $_FILES["csv"]["name"])) != 2) show_message(6, $languag);
        if (count(explode(".csv", $_FILES["csv"]["name"])) != 2 || strlen(explode(".csv", $_FILES["csv"]["name"])[1]) != 0) show_message(7, $languag);
        if ($_FILES["csv"]["size"] > FILE_LIMIT_SIZE) show_message(8, $languag);

        // not yet used
        // $time_as_file_name = (UPLOAD_DIRECTORY . ($saving_time = DateTime::createFromFormat('U.u', microtime(TRUE))->format('Y_m_d_H_i_s_u')) . ".csv");
        
        $file_name = (UPLOAD_DIRECTORY . basename($_FILES["csv"]["name"]));
        if (file_exists($file_name)) show_message(9, $languag);
        if (!move_uploaded_file($_FILES["csv"]["tmp_name"], $file_name)) show_message(MESSAGES[$language][10], $languag);

        $saved_file = null;
        try {
            $saved_file = fopen($file_name,"r") or show_message(MESSAGES[$language][13], $languag);
            $line_counter = 0;
            $first_row_as_array = explode(";", fgets($saved_file));
            $second_row_as_array = explode(";", fgets($saved_file));
            $account_entries = [];
            $index_of_account_column = array_search("Kontonummer", $second_row_as_array);
            $index_of_opposite_account_column = array_search("Gegenkonto ohne BU Schluessel", $second_row_as_array);
            $index_of_sales_without_a_mark = array_search("Umsatz ohne S/H Kennzeichen", $second_row_as_array);
            $begin_the_customer_paragraph = count(file($file_name));
            $customer_array = [];

            for ($line_index = 0; $line_index < count(file($file_name)); $line_index++) {
                $line = fgets($saved_file);
                $column_data_array = explode(";", $line);
                $combination_key_account_opposite_account = "";
                if (!in_array("****************** Customer *******************", $column_data_array) && $line_index < $begin_the_customer_paragraph) {
                    for ($column_index = 0; $column_index <= count($column_data_array); $column_index++) {
                        if ($column_index === $index_of_account_column)
                            $combination_key_account_opposite_account .= $column_data_array[$column_index].",";
                        if ($column_index === $index_of_opposite_account_column)
                            $combination_key_account_opposite_account .= $column_data_array[$column_index];
                    }
                    if (!array_key_exists($combination_key_account_opposite_account, $account_entries))
                        $account_entries[$combination_key_account_opposite_account]["umsatz"] = 0.0 ;
                } else {
                    $begin_the_customer_paragraph = $line_index;
                    array_push($customer_array, $line);
                }
            }
            fclose($saved_file);

            $saved_file = fopen($file_name,"r") or show_message(MESSAGES[$language][13], $languag);
            for ($line_index = 0; $line_index < count(file($file_name)); $line_index++) {
                $line = fgets($saved_file);
                $column_data_array = explode(";", $line);
                if (strlen(trim(join("",$column_data_array))) != 0 && !in_array("****************** Customer *******************", $column_data_array) && $line_index > 1) {
                    for ($column_index = 0; $column_index <= count($column_data_array); $column_index++) {
                        if ($column_index === $index_of_sales_without_a_mark) {  
                            $point_formatted_sales = floatval(preg_replace('/,/', '.',$column_data_array[$index_of_sales_without_a_mark]));
                            $account_entries[$column_data_array[$index_of_account_column].','.$column_data_array[$index_of_opposite_account_column]]["umsatz"] = $account_entries[$column_data_array[$index_of_account_column].','.$column_data_array[$index_of_opposite_account_column]]["umsatz"] + $point_formatted_sales;
                        } else {
                            if (!empty($second_row_as_array[$column_index]))
                                $account_entries[$column_data_array[$index_of_account_column].','.$column_data_array[$index_of_opposite_account_column]][$second_row_as_array[$column_index]] = $column_data_array[$column_index];
                        }
                    }
                }
            }

            $formatted_file = fopen($file_name,"w") or show_message(MESSAGES[$language][15], $languag);
            $output_first_line = join(";",$first_row_as_array);
            fwrite($formatted_file, $output_first_line);
            $output_second_line = join(";",$second_row_as_array);
            fwrite($formatted_file, $output_second_line);
            $line_count = 0;
            foreach ($account_entries as $key => $line_array) {
                $formatted_line = "";
                $column_count = 0;
                if ($line_count < count($account_entries) - 1) {
                    foreach ($line_array as $column_key => $column) {
                        if ($column_key === "umsatz") {
                            $comma_formatted_sales = preg_replace('/\./', ',',strval($column));
                            $formatted_line .= $comma_formatted_sales.";";
                        } else {
                            if ($column_count < count($line_array) - 1)
                                $formatted_line .= $column.";";
                            else
                                $formatted_line .= $column;
                        }
                        $column_count++;
                    }
                }
                fwrite($formatted_file, $formatted_line);
                $line_count++;
            }
            fwrite($formatted_file,";;;;;;;;;;;;;;;;;;;;;\n");
            foreach ($customer_array as $line) {
                fwrite($formatted_file, $line);
            }
            fclose($formatted_file);

            $download_file = $file_name;
            $download = true;
            $message = MESSAGES[$language][1];
        } catch (Exception $exception) {
            show_message(MESSAGES[$language][11], $languag);
        } finally {
            fclose($saved_file);
        }  
    }        
} catch (Exception $exception) {
    $message = MESSAGES[$language][12];
} finally { 
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
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
            const fileLabel = document.querySelector(".main-upload-container-select-file-label");
            const message = document.querySelector(".main-upload-container-message");
            const form = document.querySelector(".main-upload-container-form");
            const fileInput = document.querySelector(".main-upload-container-select-file");
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
                                convertButton.value = "Convert";
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
            user-select:none;
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

        .main-upload-container-select-file-label {
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

        .main-upload-container-select-file-label:hover,
        .main-upload-container-select-file-label:focus{
            border: 1px solid greenyellow;
            background-color: #adff2f;
            color:black;
        }

        .main-upload-container-select-file {
            display: none;
            width: 50%;
            background-color: white;
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
                <img class="main-upload-container-logo-frame-image" src="logo.png" alt="logo">
            </figure>
            <?php 
                if ($message === MESSAGES[$language][0] || $message ===  MESSAGES[$language][1])
                    echo '<div class="main-upload-container-message">'.$message.'</div>';
                else
                    echo '<div class="main-upload-container-message error">'.$message.'</div>';
            ?>
            <div class="main-upload-container-title">
                <?=MESSAGES[$language][16]?>
            </div>
            <form class="main-upload-container-form" method="post" action="./index.php" enctype="multipart/form-data">
                <label for="file" class="main-upload-container-select-file-label"><?=MESSAGES[$language][17]?></label>
                <input id="file" class="main-upload-container-select-file" type="file" name="csv" />
                <?php 
                    if ($download) {
                        echo '<a class="main-upload-container-form-download-link" href="'.$download_file.'" download >'.MESSAGES[$language][18].'</a>';
                    }
                ?>
                <noscript>
                    <input class="main-upload-container-form-submit-button" type="submit" name="submit" value="<?=MESSAGES[$language][19]?>">
                </noscript>
            </form>
        </div>
    </div>
</body>
</html>