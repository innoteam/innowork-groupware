<?php

require_once('innowork/groupware/InnoworkIntraMail.php');

$dir = SM_PATH.'class/deliver/';

if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if ($file != "." && $file != "..") require_once( $dir.$file );
        }
    closedir($dh);
    }
}

function deliverMessage($composeMessage, $draft=false) {
    global $send_to, $send_to_cc, $send_to_bcc, $mailprio, $subject, $body,
           $username, $popuser, $usernamedata, $identity, $data_dir,
           $request_mdn, $request_dr, $default_charset, $color, $useSendmail,
           $domain, $action;
    global $imapServerAddress, $imapPort, $sent_folder, $key;
    global $from_mail, $full_name;

    $rfc822_header = $composeMessage->rfc822_header;

    $abook = addressbook_init(false, true);

    $rfc822_header->to = $rfc822_header->parseAddress($send_to,true,array(), '',$domain, array(&$abook,'lookup'));
    $rfc822_header->cc = $rfc822_header->parseAddress($send_to_cc,true,array(), '',$domain, array(&$abook,'lookup'));
    $rfc822_header->bcc = $rfc822_header->parseAddress($send_to_bcc,true, array(), '',$domain, array(&$abook,'lookup'));
    $rfc822_header->priority = $mailprio;
    $rfc822_header->subject = $subject;
    $special_encoding='';
    if (strtolower($default_charset) == 'iso-2022-jp') {
        if (mb_detect_encoding($body) == 'ASCII') {
            $special_encoding = '8bit';
        } else {
            $body = mb_convert_encoding($body, 'JIS');
            $special_encoding = '7bit';
        }
    }
    $composeMessage->setBody($body);

    if (ereg("^([^@%/]+)[@%/](.+)$", $username, $usernamedata)) {
       $popuser = $usernamedata[1];
       $domain  = $usernamedata[2];
       unset($usernamedata);
    } else {
       $popuser = $username;
    }
    $reply_to = '';

    if ( strlen( $from_mail ) )
    {
    }
    else
    {
    if (isset($identity) && $identity != 'default') {
        $from_mail = getPref($data_dir, $username,'email_address' . $identity);
        $full_name = getPref($data_dir, $username,'full_name' . $identity);
        $reply_to = getPref($data_dir, $username,'reply_to' . $identity);
    } else {
        $from_mail = getPref($data_dir, $username, 'email_address');
        $full_name = getPref($data_dir, $username, 'full_name');
        $reply_to = getPref($data_dir, $username,'reply_to');
    }
    if (!$from_mail) {
       $from_mail = "$popuser@$domain";
       $full_name = '';
    }
    }
    $rfc822_header->from = $rfc822_header->parseAddress($from_mail,true);
    if ($full_name) {
        $from = $rfc822_header->from[0];
        if (!$from->host) $from->host = $domain;
        $full_name_encoded = encodeHeader($full_name);
        if ($full_name_encoded != $full_name) {
            $from_addr = $full_name_encoded .' <'.$from->mailbox.'@'.$from->host.'>';
        } else {
            $from_addr = '"'.$full_name .'" <'.$from->mailbox.'@'.$from->host.'>';
        }
        $rfc822_header->from = $rfc822_header->parseAddress($from_addr,true);
    }
    if ($reply_to) {
       $rfc822_header->reply_to = $rfc822_header->parseAddress($reply_to,true);
    }
    /* Receipt: On Read */
    if (isset($request_mdn) && $request_mdn) {
       $rfc822_header->dnt = $rfc822_header->parseAddress($from_mail,true);
    }
    /* Receipt: On Delivery */
    if (isset($request_dr) && $request_dr) {
       $rfc822_header->more_headers['Return-Receipt-To'] = $from_mail;
    }
    /* multipart messages */
    if (count($composeMessage->entities)) {
        $message_body = new Message();
        $message_body->body_part = $composeMessage->body_part;
        $composeMessage->body_part = '';
        $mime_header = new MessageHeader;
        $mime_header->type0 = 'text';
        $mime_header->type1 = 'plain';
        if ($special_encoding) {
            $mime_header->encoding = $special_encoding;
        } else {
            $mime_header->encoding = '8bit';
        }
        if ($default_charset) {
            $mime_header->parameters['charset'] = $default_charset;
        }
        $message_body->mime_header = $mime_header;
        array_unshift($composeMessage->entities, $message_body);
        $content_type = new ContentType('multipart/mixed');
    } else {
            $content_type = new ContentType('text/plain');
    }
    if ($default_charset) {
        $content_type->properties['charset']=$default_charset;
    }

    $rfc822_header->content_type = $content_type;
    $composeMessage->rfc822_header = $rfc822_header;

    if (!$useSendmail && !$draft) {
        require_once(SM_PATH . 'class/deliver/Deliver_SMTP.class.php');
        $deliver = new Deliver_SMTP();
        global $smtpServerAddress, $smtpPort, $pop_before_smtp, $smtp_auth_mech;

        if ($smtp_auth_mech == 'none') {
                $user = '';
                $pass = '';
        } else {
                global $key, $onetimepad;
                $user = $username;
                $pass = OneTimePadDecrypt($key, $onetimepad);
        }

        $authPop = (isset($pop_before_smtp) && $pop_before_smtp) ? true : false;
        $stream = $deliver->initStream($composeMessage,$domain,0,
                          $smtpServerAddress, $smtpPort, $user, $pass, $authPop);
    } elseif (!$draft) {
       require_once(SM_PATH . 'class/deliver/Deliver_SendMail.class.php');
       global $sendmail_path;
       $deliver = new Deliver_SendMail();
       $stream = $deliver->initStream($composeMessage,$sendmail_path);
    } elseif ($draft) {
       global $draft_folder;
       require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
       $imap_stream = sqimap_login($username, $key, $imapServerAddress,
                      $imapPort, 0);
       if (sqimap_mailbox_exists ($imap_stream, $draft_folder)) {
           require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
           $imap_deliver = new Deliver_IMAP();
           $length = $imap_deliver->mail($composeMessage);
           sqimap_append ($imap_stream, $draft_folder, $length);
           $imap_deliver->mail($composeMessage, $imap_stream);
               sqimap_append_done ($imap_stream, $draft_folder);
           sqimap_logout($imap_stream);
           unset ($imap_deliver);
           return $length;
        } else {
           $msg  = '<br>Error: '._("Draft folder")." $draft_folder" . ' does not exist.';
           plain_error_message($msg, $color);
           return false;
        }
    }
    $succes = false;
    if ($stream) {
        $length = $deliver->mail($composeMessage, $stream);
        $succes = $deliver->finalizeStream($stream);
    }
    if (!$succes) {
        $msg  = $deliver->dlv_msg . '<br>' .
                _("Server replied: ") . $deliver->dlv_ret_nr . ' '.
                $deliver->dlv_server_msg;
        plain_error_message($msg, $color);
    }
    /*else {
        unset ($deliver);
        $imap_stream = sqimap_login($username, $key, $imapServerAddress,
        $imapPort, 0);
        if (sqimap_mailbox_exists ($imap_stream, $sent_folder)) {
                sqimap_append ($imap_stream, $sent_folder, $length);
            require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
            $imap_deliver = new Deliver_IMAP();
            $imap_deliver->mail($composeMessage, $imap_stream);
                sqimap_append_done ($imap_stream, $sent_folder);
            unset ($imap_deliver);
        }
        global $passed_id, $mailbox, $action;
        ClearAttachments($composeMessage);
        if ($action == 'reply' || $action == 'reply_all') {
            sqimap_mailbox_select ($imap_stream, $mailbox);
            sqimap_messages_flag ($imap_stream, $passed_id, $passed_id, 'Answered', true);
        }
            sqimap_logout($imap_stream);
    }
    */
    return $succes;
}

?>
