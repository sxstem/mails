# mails

#GET
$mail = new \Sxstem\Mails\Imap(username, passwork, [mailbox]);  //The mailbox defaults to inbox
$result = $mail->getBoxes(); //get folder
//$result = $mail->getUid();  //before this,you can use $mail->setBeginDate(),$mail->setEndDate() to set bigin date, end date
//$result = $mail->getHeader($uid); //Not passing the uid means getting the headers for all messages under the current mailbox
//$result = $mail->getMsgnoByUid($uid);
//$result = $mail->getBody($msgno);
//$result = $mail->setFolder($mailbox);  //reconnect imap
$mail->imapClose();

#Mark
$mail = new \Sxstem\Mails\Mark(username, passwork, [mailbox]);
$result = $mail->set_seen($uid);
//$result = $mail->clear_seen($uid);
//$result = $mail->set_flagged($uid);
//$result = $mail->clear_flagged($uid);
//$result = $mail->set_deleted($uid);
//$result = $mail->set_answered($uid);
//$result = $mail->move_mail($uid, $mailbox);
$mail->imapClose();

#SMTP
$mail = new \Sxstem\Mails\Smtp(username, passwork);
$result = $mail->send($subject, $to, $cc, $mails_body, $in_reply_to, $references, $attach);
