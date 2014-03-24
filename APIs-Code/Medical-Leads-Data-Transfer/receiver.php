<?php
$file = 'data.txt';
// Open the file to get existing content
$current = file_get_contents($file);
// Append a new person to the file
$current .= $_POST;
// Write the contents back to the file
file_put_contents($file, $current);

$fp = fopen("data.txt", "w");
fputs ($fp, $_POST['xml']);
fclose ($fp)
?>