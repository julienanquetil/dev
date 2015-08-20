<?php
include 'PHPMailer.php';
try {
	$bdd = new PDO('mysql:host=localhost;dbname=akeneo_pim;charset=utf8', 'akeneo_pim', 'akeneo_pim');
}
catch (Exception $e){
	die('Erreur : ' . $e->getMessage());
}
$date = date('Y-M-d');

$reponse = $bdd->query('
SELECT * FROM `akeneo_batch_job_execution`
LEFT JOIN `akeneo_batch_job_instance` on `akeneo_batch_job_execution`.`job_instance_id` = `akeneo_batch_job_instance`.`id`
WHERE `start_time` LIKE "%'.$date.'%"');

$return = '<table>';
$return .= '<tr>';
$return .=  '<td>Job</td>';
$return .=  '<td>Start Time</td>';
$return .=  '<td>End Time</td>';
$return .=  '<td>Status</td>';
$return .=  '</tr>';

// On affiche chaque entrée une à une
while ($donnees = $reponse->fetch()){
	$return .=  '<tr>';
	$return .=  '<td>'.$donnees['label'].'</td>';
	$return .=  '<td>'.$donnees['start_time'].'</td>';
	$return .=  '<td>'.$donnees['end_time'].'</td>';
	//@TODO add color depend of status
	$return .=  '<td>'.$donnees['exit_code'].'</td>';
	$return .=  '</tr>';
}
$return .=  '</table>';
$reponse->closeCursor(); // Termine le traitement de la requête

// envoie du report par mail
$mail->SetFrom('pim@henryschein.fr','report PIM');
$mail->AddAddress('julien.anquetil@gmail.com');
$mail->Subject = 'Report PIM';
$mail->MsgHTML($return);
$mail->IsHTML(true);
$mail->CharSet = "utf-8";
if (!$mail->Send()) {
	echo "Mailer Error: " . $mail->ErrorInfo;
}