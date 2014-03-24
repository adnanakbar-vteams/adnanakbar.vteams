<?php
function post_to_url($url, $data) {
    $fields = '';
    foreach($data as $key => $value) {
        $fields .= $key . '=' . $value . '&';
    }
    rtrim($fields, '&');

    $post = curl_init();

    curl_setopt($post, CURLOPT_URL, $url);
    curl_setopt($post, CURLOPT_POST, count($data));
    curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($post);

    curl_close($post);
    return $result;
}
$strError = '';
$strMsg = '';
if(!empty($_POST['submit']))
{
    if (is_uploaded_file($_FILES['csvfile']['tmp_name'])) {
        $csv_mimetypes = array(
            'text/csv',
            'application/csv',
            'text/comma-separated-values',
            'application/excel',
            'application/vnd.ms-excel',
            'application/vnd.msexcel'
        );
        if (in_array($_FILES['csvfile']['type'], $csv_mimetypes)) {
            // possible CSV file
            ini_set('auto_detect_line_endings',TRUE);
            $row = 1;
            $arrHeader = array();
            if (($handle = fopen($_FILES['csvfile']['tmp_name'], "r")) !== FALSE) {
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $strPostXml = '';
                    $num = count($data);
                    $row++;
                    if($row == 2)
                    {
                        $arrHeader = $data;
                    }
                    else
                    {

                        $strXmlHeader = '<medicareSupplementForm><medicareSupplementLead><formMetadata>';
                        $strXmlFooter = '<ipAddress>192.168.1.1</ipAddress><creationDate>'.date('Y-m-d').'T'.date('H:i:s').'.324Z</creationDate></formMetadata></medicareSupplementLead></medicareSupplementForm>';
                        $strPostXml .= $strXmlHeader;
                        for ($c=0; $c < $num; $c++) {
                            $strPostXml .= '<'.$arrHeader[$c].'>'.$data[$c].'</'.$arrHeader[$c].'>';
                        }
                        $strPostXml .= $strXmlFooter;
                        $data = array(
                            "xml" => $strPostXml
                        );

                        $strPosted = post_to_url("http://www.clearon.com/soap-read.php", $data);
                        //$strPosted = post_to_url("http://localhost/medicarelead/receiver.php", $data);
                        //echo $strPosted;
                        //exit();

                    }
                }
                if($row > 2)
                {
                    $intPostedRecs = $row - 2;
                    $strMsg = $intPostedRecs.' records are posted successfully.';
                }
                fclose($handle);
            }
        }
        else{
            $strError = "Please upload valid csv file to post data. ";
        }
    } else {
        $strError = "Please upload file to post data. ";
    }
}
?>
<!DOCTYPE HTML>
<html>
    <head>
        <title>Medicare Lead Form</title>
        <style type="text/css">
            body{
                width: 100%;
                height: 100%;
                vertical-align: middle;
                text-align: center;
                font-family:Arial;
                font-size:18px;
                font-weight:bold;
                color:#fff;
                text-decoration: none;
                text-rendering: optimizeLegibility;
                -webkit-font-smoothing: antialiased;
            }
            #container{
                width: 400px;
                min-height: 150px;
                margin: auto;
                border: 2px solid chocolate;
                -moz-border-radius: 15px 15px 15px 15px;
                -webkit-border-radius: 15px 15px 15px 15px;
                border-radius: 15px 15px 15px 15px;
                behavior: url(PIE.htc);
            }
            #heading{

                min-height: 30px;
                text-align: center;
                vertical-align: middle;
                padding-top: 7px;

                background-image: linear-gradient(bottom, rgb(120,86,17) 48%, rgb(51,66,66) 74%);
                background-image: -o-linear-gradient(bottom, rgb(120,86,17) 48%, rgb(51,66,66) 74%);
                background-image: -moz-linear-gradient(bottom, rgb(120,86,17) 48%, rgb(51,66,66) 74%);
                background-image: -webkit-linear-gradient(bottom, rgb(120,86,17) 48%, rgb(51,66,66) 74%);
                background-image: -ms-linear-gradient(bottom, rgb(120,86,17) 48%, rgb(51,66,66) 74%);
                background-image: -webkit-gradient(
                                        linear,
                                      left bottom,
                                      left top,
                color-stop(0.48, rgb(120,86,17)),
                color-stop(0.74, rgb(51,66,66))
                );
                -moz-border-radius: 14px 14px 0px 0px;
                -webkit-border-radius: 14px 14px 0px 0px;
                border-radius: 14px 14px 0px 0px;
                behavior: url(PIE.htc);
            }
            form{
                margin: 0px;
                text-align: left;
                padding: 10px;
            }
            input{
                margin-top:10px;
            }
            label{
                color: black;
            }
            .error{
                 background-color: red;
                 border:2px solid chocolate;
                padding: 2px;
             }
            .success{
                background-color: green;
                border:2px solid chocolate;
                padding: 2px;
            }
        </style>
    </head>
    <body>
        <div id="container">
            <div id="heading" class="background-image">CSV File Posting</div>
            <?php
            if(!empty($strError))
            {
                echo '<div class="error">'.$strError.'</div>';
            }
            if(!empty($strMsg))
            {
                echo '<div class="success">'.$strMsg.'</div>';
            }
            ?>
            <form action="" enctype="multipart/form-data" method="post">
                <div style="float: left; padding-top: 8px;"><label for="csvfile">Browse File</label></div><div style="float: left; padding-left: 2px;"><input type="file" name="csvfile" id="csvfile" style="width: 100px;"> </div>
                <div style="clear: both; padding-top: 10px;"><input type="submit" name="submit" value="Upload File"> </div>
            </form>
        </div>
    </body>
</html>