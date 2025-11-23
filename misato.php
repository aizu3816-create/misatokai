<?php
$id  = htmlspecialchars($_POST['id']);

if ($id == 'select') {
	$sql = htmlspecialchars($_POST['sql']);
	$db  = htmlspecialchars($_POST['db']);
	$ary = array();
	$db = new SQLite3($db);
	$rs = $db->query($sql);
	while ($row = $rs->fetchArray()) {
		array_push($ary, $row);
	}
	$db->close();
	echo json_encode($ary);
	return;
}

if ($id == 'exec') {
	$sql = htmlspecialchars($_POST['sql']);
	$db  = htmlspecialchars($_POST['db']);
	$db = new SQLite3($db);
	$rc = $db->exec($sql);
	echo $rc;
	$db->close();
	return;
}
// ============================================================================ dbのバックアップ（管理者画面
if ($id == 'backup') {
	date_default_timezone_set('Asia/Tokyo');
	$today = date( "Y-m-d H:i:s" );
	$mmdd = substr( $today, 5, 2 ) . substr( $today, 8, 2 );
	$fnm = "misato_" . $mmdd . ".db";
	copy ( 'misato.db', $fnm );
	echo $fnm;
}
// ============================================================================ yourPswd（管理者画面）
if ($id == 'yourPswd') {
	echo "zxcv";
}
// ============================================================================ メール送信（お問い合わせ）
if ($id == 'otoiawase') {
	$to  = "misatokai@ueno.ciao.jp";
	$cc  = "";
	$bcc = "k_ueno@ab.auone-net.jp";
	$name  = htmlspecialchars($_POST['name']);
	$maddr = htmlspecialchars($_POST['maddr']);
	$subject  = "お問い合わせメール";
	$msg  = htmlspecialchars($_POST['msg']);
	$hdr = "From: " . $maddr;
	$hdr .= "\nBcc: " . $bcc;
	$hdr .= "\n";
	$returnPath = "-f misatokai@ueno.ciao.jp";
	mb_language("Japanese");
	mb_internal_encoding("UTF-8");
	$rc = mb_send_mail($to, $subject, $msg, $hdr, $returnPath);
	echo $rc;

	date_default_timezone_set('Asia/Tokyo');
	$date = new DateTimeImmutable();
	$today = $date->format("Y/m/d H:i:s.u");

	$sql = "insert into sendMail (日付,発信元,送信先,氏名,タイトル,Bcc,本文) values(";
	$sql .=  "'" . $today . "'";
	$sql .= ",'" . $maddr . "'";
	$sql .= ",'" . $to . "'";
	$sql .= ",'" . $name . "'";
	$sql .= ",'" . $subject . "'";
	$sql .= ",'" . $bcc . "'";
	$sql .= ",'" . $msg . "'";
	$sql .= ");";
	$db = new SQLite3('misato.db');
	$db->exec($sql);
	$db->close();

	if ( $rc == 1 ) {
		$reply = "お問い合わせを送信しました(会津美里会)";
		$replyMsg = $name . "様\n\nアイス美里会宛てのメールを送信しました。\n　送信日時：" . substr($today,0,16);
		$s = mb_convert_kana($msg, 'KVRN');
		if ( mb_strlen($s) > 100 ) {
			$s = mb_substr($s,0,100) . "...<以下省略>";
		}
		$s .= "\n\nこのメールは会津美里会ホームページから自動返信しております。";
		$s .= "\n送信専用メールアドレスのため、このメールに返信されてもお受けできません。";
		$replyMsg .= "\n　送信内容：" . $s;
		$replyHdr = "From: " . "misatokai@ueno.ciao.jp" . "\n";
		$rc = mb_send_mail($maddr, $reply, $replyMsg, $replyHdr);

		$sql = "insert into sendMail (日付,発信元,送信先,氏名,タイトル,本文) values(";
		$sql .=  "'" . $today . "'";
		$sql .= ",'" . "misatokai@ueno.ciao.jp" . "'";
		$sql .= ",'" . $maddr . "'";
		$sql .= ",'" . "会津美里会H/P" . "'";
		$sql .= ",'" . $reply . "'";
		$sql .= ",'" . $replyMsg . "'";
		$sql .= ");";
		$db = new SQLite3('misato.db');
		$db->exec($sql);
		$db->close();
	}
}
// ============================================================================ メール送信（再送信）
if ($id == 'sendMail2') {
	$from= htmlspecialchars($_POST['from']);
	$to  = htmlspecialchars($_POST['to']);
	$cc  = htmlspecialchars($_POST['cc']);
	$bcc = htmlspecialchars($_POST['bcc']);
	$name  = htmlspecialchars($_POST['name']);
	$subject  = htmlspecialchars($_POST['ttl']);
	$msg  = htmlspecialchars($_POST['msg']);
	$hdr = "From: " . $from;
	if ( strlen($cc) > 0 )  {
		$hdr .= "\n";
		$hdr .= "Cc: "  . $cc;
	}
	if ( strlen($bcc) > 0 ) {
		$hdr .= "\n";
		$hdr .= "Bcc: " . $bcc;
	}
	$hdr .= "\n";
	mb_language("Japanese");
	mb_internal_encoding("UTF-8");
	$rc = mb_send_mail($to, $subject, $msg, $hdr);
	echo $rc;

	$sql = "insert into sendMail (日付,発信元,送信先,氏名,タイトル,本文,Cc,Bcc) values(";
	date_default_timezone_set('Asia/Tokyo');
	$date = new DateTimeImmutable();
	$sql .=  "'" . $date->format("Y/m/d H:i:s.u") . "'";
	$sql .= ",'" . $from . "'";
	$sql .= ",'" . $to . "'";
	$sql .= ",'" . $name . "'";
	$sql .= ",'" . $subject . "'";
	$sql .= ",'" . $msg . "'";
	$sql .= ",'" . $cc . "'";
	$sql .= ",'" . $bcc . "'";
	$sql .= ");";
	$db = new SQLite3('misato.db');
	$db->exec($sql);
	$db->close();
}
// ============================================================================ 投稿データを追加書き込み
if ($id == 'toukouAdd') {
	$sql = htmlspecialchars($_POST['sql']);
	$db = new SQLite3('misato.db');
	$rc = $db->exec($sql);
	$sid = $db->lastInsertRowID();
	$fpass = "./toukouImg/img" . $sid . ".jpg";
	$fpass = "img" . $sid . ".jpg";
	echo $fpass;
	$db->close();
	return;
}

?>
