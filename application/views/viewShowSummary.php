<?php

$message = array();
if($errorFlag == 1){
	$message['error'] = "Date parameter loss";
	echo json_encode($message);
}
if ($errorFlag == 2) {
	$message['error'] = "Date Format Error";
	echo json_encode($message);
}
if ($errorFlag == 3) {
	$message['error'] = "Result Empty";
	echo json_encode($message);
}
if ($errorFlag == 0) {
	$message['result'] = $result;
	$message['eps'] = $eps;
	echo json_encode($message);
}
/*
echo "<pre>";
echo $errorFlag;
print_r($result);
echo "</pre>";
*/
?>