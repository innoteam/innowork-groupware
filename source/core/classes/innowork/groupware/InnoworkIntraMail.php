<?php
require_once('innowork/core/InnoworkItem.php');
require_once('innomatic/dataaccess/DataAccess.php');
require_once('innomatic/logging/Logger.php');

define( 'INNOWORKINTRAMAIL_FOLDERTYPE_INBOX',  '1' );
define( 'INNOWORKINTRAMAIL_FOLDERTYPE_OUTBOX', '2' );
define( 'INNOWORKINTRAMAIL_FOLDERTYPE_TRASH',  '3' );
define( 'INNOWORKINTRAMAIL_FOLDERTYPE_USER',   '4' );

define( 'SM_PATH', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().'core/applications/squirrelmaillib/squirrelmail/' );

class InnoworkIntraMail extends InnoworkItem {
    var $mTable = 'innowork_email_messages';
    var $mNewDispatcher = 'view';
    var $mNewEvent = 'newmail';
    var $mNoAcl = true;
    var $mNoLog = true;
    var $mNoTrash = false;
    var $_mCreationAcl = InnoworkAcl::TYPE_PRIVATE;
    var $mConvertible = true;
    const ITEM_TYPE = 'intramail';

    function InnoworkIntraMail(
        $rrootDb,
        $rdomainDA,
        $mailId = 0
        )
    {
        parent::__construct(
            $rrootDb,
            $rdomainDA,
            InnoworkIntraMail::ITEM_TYPE,
            $mailId);

        $this->mKeys['subject'] = 'text';
        $this->mKeys['date'] = 'timestamp';
        $this->mKeys['fromuser'] = 'text';
        $this->mKeys['projectid'] = 'table:innowork_projects:name:integer';
        $this->mKeys['customerid'] = 'table:innowork_directory_companies:companyname:integer';
        $this->mKeys['content'] = 'text';
        $this->mKeys['mailread'] = 'boolean';
        $this->mKeys['headers'] = 'text';

        $this->mSearchResultKeys[] = 'subject';
        $this->mSearchResultKeys[] = 'date';
        $this->mSearchResultKeys[] = 'fromuser';
        $this->mSearchResultKeys[] = 'projectid';
        $this->mSearchResultKeys[] = 'mailread';

        $this->mViewableSearchResultKeys[] = 'subject';
        $this->mViewableSearchResultKeys[] = 'date';
        $this->mViewableSearchResultKeys[] = 'fromuser';
        $this->mViewableSearchResultKeys[] = 'projectid';

        $this->mSearchOrderBy = 'date,id';
        $this->mShowDispatcher = 'view';
        $this->mShowEvent = 'default';

        $this->mGenericFields['companyid'] = 'customerid';
        //$this->mGenericFields['projectid'] = 'projectid';
        $this->mGenericFields['title'] = 'subject';
        $this->mGenericFields['content'] = 'body';
        $this->mGenericFields['binarycontent'] = '';
    }

    function doCreate(
        $params,
        $userId
        )
    {
        $result = false;

        if ( count( $params ) )
        {
            

            $item_id = $this->mrDomainDA->getNextSequenceValue( $this->mTable.'_id_seq' );

            $key_pre = $value_pre = $keys = $values = '';

            if ( !isset($params['foldertype'] ) ) $params['foldertype'] = INNOWORKINTRAMAIL_FOLDERTYPE_OUTBOX;
            if ( !isset($params['mailread'] ) ) $params['mailread'] = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse;

            if (
                !isset($params['subject'] )
                or !strlen( $params['subject'] )
                )
            {
                require_once('innomatic/locale/LocaleCountry.php'); 
                $locale = new LocaleCatalog(
                    'innowork-groupware::intramail_misc',
                	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
                    );

                $params['subject'] = $locale->getStr( 'no_subject.label' );
            }

            while ( list( $key, $val ) = each( $params ) )
            {
                $key_pre = ',';
                $value_pre = ',';

                switch ( $key )
                {
                case 'subject':
                case 'content':
                case 'touser':
                case 'mailread':
                case 'headers':
                case 'fromuser':
                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$this->mrDomainDA->formatText( $val );
                    break;

                case 'date':
                    $val = $this->mrDomainDA->getTimestampFromDateArray( $val );
                    unset( $date_array );

                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$this->mrDomainDA->formatText( $val );
                    break;

                case 'projectid':
                case 'foldertype':
                case 'folderid':
                case 'customerid':
                    if ( !strlen( $key ) ) $key = 0;
                    $keys .= $key_pre.$key;
                    $values .= $value_pre.(int)$val;
                    break;

                default:
                    break;
                }
            }

            if ( strlen( $values ) )
            {
                if ( $this->mrDomainDA->Execute( 'INSERT INTO '.$this->mTable.' '.
                                               '(id,ownerid'.$keys.') '.
                                               'VALUES ('.$item_id.','.
                                               $userId.
                                               $values.')' ) )
                {
                    $result = $item_id;
                }
            }
        }

        //$this->_mCreationAcl = InnoworkAcl::TYPE_PRIVATE;

        return $result;
    }

    function doEdit(
        $params
        )
    {
        $result = false;

        if ( $this->mItemId )
        {
            if ( count( $params ) )
            {
                $start = 1;
                $update_str = '';

                if (
                    isset($params['subject'] )
                    and !strlen( $params['subject'] )
                    )
                {
                    require_once('innomatic/locale/LocaleCatalog.php');
                    $locale = new LocaleCatalog(
                        'innowork-groupware::intramail_misc',
                    	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
                        );

                    $params['subject'] = $locale->getStr( 'no_subject.label' );
                }

                while ( list( $field, $value ) = each( $params ) )
                {
                    if ( $field != 'id' )
                    {
                        switch ( $field )
                        {
                        case 'subject':
                        case 'touser':
                        case 'content':
                        case 'mailread':
                        case 'headers':
                        case 'fromuser':
                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$this->mrDomainDA->formatText( $value );
                            $start = 0;
                            break;

                        case 'date':
                            $value = $this->mrDomainDA->getTimestampFromDateArray( $value );
                            unset( $date_array );

                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$this->mrDomainDA->formatText( $value );
                            $start = 0;
                            break;

                        case 'projectid':
                        case 'foldertype':
                        case 'folderid':
                        case 'customerid':
                            if ( !strlen( $value ) ) $value = 0;
                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$value;
                            $start = 0;
                            break;

                        default:
                            break;
                        }
                    }
                }

                $query = &$this->mrDomainDA->Execute(
                    'UPDATE '.$this->mTable.' '.
                    'SET '.$update_str.' '.
                    'WHERE id='.$this->mItemId );

                if ( $query ) $result = TRUE;
            }
        }

        return $result;
    }

    function doRemove(
        $userId
        )
    {
        $result = FALSE;

        $result = $this->mrDomainDA->Execute(
            'DELETE FROM '.$this->mTable.' '.
            'WHERE id='.$this->mItemId
            );

        return $result;
    }

    function doGetSummary()
    {
        $result = false;

        $mailbox_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->Execute(
            'SELECT id,subject '.
            'FROM innowork_email_messages '.
            'WHERE ownerid='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId().' '.
            'AND foldertype='.INNOWORKINTRAMAIL_FOLDERTYPE_INBOX.' '.
            'AND mailread='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText( \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse ).' '.
        	'ORDER BY date DESC '.
        	'LIMIT 5'
            );

        if ( $mailbox_query->getNumberRows() )
        {
            $xml_def =
'<vertgroup>
  <children>';

            while ( !$mailbox_query->eof )
            {
                if ( strlen( $mailbox_query->getFields( 'subject' ) ) > 25 ) $subject = substr( $mailbox_query->getFields( 'subject' ), 0, 22 ).'...';
                else $subject = $mailbox_query->getFields( 'subject' );

                $xml_def .=
'<horizgroup><children>
<label>
  <args>
    <label>- </label>
    <compact>true</compact>
  </args>
</label>
<link>
  <args>
    <label type="encoded">'.urlencode( $subject ).'</label>
    <link type="encoded">'.urlencode(
        WuiEventsCall::buildEventsCallString( 'innoworkintramail', array(
                array(
                    'view',
                    'default',
                    array( 'mailid' => $mailbox_query->getFields( 'id' ) )
                )
            ) )
        ).'</link>
    <compact>true</compact>
  </args>
</link>
</children>
</horizgroup>';

                $mailbox_query->moveNext();
            }

            $xml_def .=
'  </children>
</vertgroup>';

            $result = &$xml_def;
        }

        return $result;
    }

    function Send(
        $toUsers
        )
    {
        $result = false;

        if ( $this->mItemId )
        {
            $this->Edit(
                array(
                    'touser' => $toUsers
                    )
                );

            $mail_data = $this->getItem();

            $users = explode( ',', $toUsers );

            foreach ( $users as $userId )
            {
                $user_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->Execute(
                    'SELECT username,email '.
                    'FROM domain_users '.
                    'WHERE id='.$this->mOwnerId
                    );

                $from_user = $user_query->getFields( 'username' );

                $to_user_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->Execute(
                    'SELECT id '.
                    'FROM domain_users '.
                    'WHERE username='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess()->formatText( $userId )
                    );

                if ( $to_user_query->getNumberRows() )
                {
                    $tmp_mail = new InnoworkIntraMail(
                        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
                        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
                        );

                    $tmp_mail->Receive(
                        $from_user,
                        $userId,
                        $this->mrDomainDA->getDateArrayFromTimestamp( $mail_data['date'] ),
                        $mail_data['subject'],
                        $mail_data['content']
                        );
                }
                else
                {
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'class/deliver/Deliver.class.php');
require_once(SM_PATH . 'functions/addressbook.php');
require_once('innowork/groupware/InnoworkIntraMailDeliver.php');
require_once('innomatic/domain/user/UserSettings.php');

  $composeMessage = new Message();
  $rfc822_header = new Rfc822Header();
  $composeMessage->rfc822_header = $rfc822_header;

    global $send_to, $send_to_cc, $send_to_bcc, $mailprio, $subject, $body,
           $username, $popuser, $usernamedata, $identity, $data_dir,
           $request_mdn, $request_dr, $default_charset, $color, $useSendmail,
           $domain, $action;
    global $imapServerAddress, $imapPort, $sent_folder, $key;
    global $from_mail, $full_name;

    $user_data = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserData();
                    $send_to = $userId;
                    $send_to = 'alex.pagnoni@innoteam.it';
                    $subject = $mail_data['subject'];
                    $body = $mail_data['content'];
                    //$domain = 'innoteam.it';
                    $data_dir = SM_PATH.'data/';
                    $popuser = 'alex';
                    $domain = 'innoteam.it';
                    $from_mail = $user_data['email'];
                    $full_name = $user_data['fname'].
                        ' '.
                        $user_data['lname'];

    $sets = new UserSettings(
    	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
    	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());
    $smtpServerAddress = $sets->getKey( 'innoworkgroupware-mailsmtpserver');
    $smtpPort = $sets->getKey( 'innoworkgroupware-mailsmtpport');

    if ( !strlen( $smtpServerAddress ) ) $smtpServerAddress = 'localhost';
    if ( !strlen( $smtpPort ) ) $smtpPort = 25;

                    $username = $password = '';
                    $authPo = false;
                    $domain = '';
                    /*
    global $send_to, $send_to_cc, $send_to_bcc, $mailprio, $subject, $body,
           $username, $popuser, $usernamedata, $identity, $data_dir,
           $request_mdn, $request_dr, $default_charset, $color, $useSendmail,
           $domain, $action;
    global $imapServerAddress, $imapPort, $sent_folder, $key;
    */
    //$rfc822_header->to = $rfc822_header->parseAddress($userId,true, array(), '', $domain, false);
    //$rfc822_header->cc = $rfc822_header->parseAddress($send_to_cc,true,array(), '',$domain, array(&$abook,'lookup'));
    //$rfc822_header->bcc = $rfc822_header->parseAddress($send_to_bcc,true, array(), '',$domain, array(&$abook,'lookup'));
    //$rfc822_header->priority = $mailprio;
    //$rfc822_header->subject = $mail_data['subject'];
    //$rfc822_header->from = $rfc822_header->parseAddress($user_query->getFields( 'email' ),true);

  //$composeMessage->rfc822_header = $rfc822_header;
  //$composeMessage->reply_rfc822_header = '';
  //$composeMessage->setBody( $mail_data['content'] );

  delivermessage( $composeMessage, false );

  /*
print_r( $composeMessage );

                    $stream = $deliver->InitStream(
                        $composeMessage,
                        $domain,
                        0,
                        $smtpServerAddress,
                        $smtpPort,
                        $user,
                        $pass,
                        $authPo
                        );

    $succes = false;
    if ($stream) {
        $length = $deliver->mail($composeMessage, $stream);
        $succes = $deliver->finalizeStream($stream);
    }
    if (!$succes) {
        $msg  = $deliver->dlv_msg . '<br>' .
                _("Server replied: ") . $deliver->dlv_ret_nr . ' '.
                $deliver->dlv_server_msg;
                echo $msg;
        //plain_error_message($msg, $color);
    }
    */

                }
            }
        }

        return $result;
    }

    function Receive(
        $fromUser,
        $toUser,
        $date,
        $subject,
        $content,
        $headers = ''
        )
    {
        $user_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute(
            'SELECT id '.
            'FROM domain_users '.
            'WHERE username='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText( $toUser )
            );

        $to_user = $user_query->getFields( 'id' );

        $result = $this->Create(
            array(
                'fromuser' => $fromUser,
                'date' => $date,
                'subject' => $subject,
                'content' => $content,
                'foldertype' => INNOWORKINTRAMAIL_FOLDERTYPE_INBOX,
                'headers' => $headers
                ),
            $to_user
            );

        return $result;
    }

    function doTrash( $user )
    {
        $result = false;

        if ( $this->mItemId )
        {
            $result = $this->Edit(
                array(
                    'foldertype' => INNOWORKINTRAMAIL_FOLDERTYPE_TRASH,
                    'folderid' => 0
                    )
                );
        }

        return $result;
    }

    function doRestore()
    {
        $result = false;

        if ( $this->mItemId )
        {
            $result = $this->Edit(
                array(
                    'foldertype' => INNOWORKINTRAMAIL_FOLDERTYPE_INBOX,
                    'folderid' => 0
                    )
                );
        }

        return $result;
    }

    function EmptyTrashcan()
    {
        $trash_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->Execute(
            'SELECT id '.
            'FROM innowork_email_messages '.
            'WHERE ownerid='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId().' '.
            'AND foldertype='.INNOWORKINTRAMAIL_FOLDERTYPE_TRASH
            );

        while ( !$trash_query->eof )
        {
            $tmp_mail = new InnoworkIntraMail(
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
                $trash_query->getFields( 'id' )
                );

            $tmp_mail->Remove();

            $trash_query->moveNext();
        }

        return true;
    }

    function setRead()
    {
        $result = false;

        if ( $this->mItemId )
        {
            $result = $this->Edit(
            array(
                'mailread' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue
                )
            );
        }

        return $result;
    }

    function FetchExternalMail()
    {
        $result = true;

        $accounts = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->Execute(
            'SELECT id '.
            'FROM innowork_email_accounts '.
            'WHERE ownerid='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()
            );

        if ( $accounts->getNumberRows() )
        {
            while ( !$accounts->eof )
            {
                $acc = new InnoworkIntraMailPopAccount( $accounts->getFields( 'id' ) );
                $acc->RetrieveAllMessages();

                $accounts->moveNext();
            }
        }

        return $result;
    }

    function LookupCustomer()
    {
        $result = false;

        if ( $this->mItemId )
        {
            require_once('innowork/groupware/InnoworkCompany.php');

            $data = $this->getItem();

            $innowork_directory = new InnoworkCompany(
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
                );

            $search = $innowork_directory->Search(
                array(
                    'email' => $data['fromuser']
                    )
                );

            if ( count( $search ) )
            {
                list( $id, ) = each( $search );
                $this->Edit( array( 'customerid' => $id ) );
            }
            else
            {
                $innowork_directory = new InnoworkContact(
                    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
                    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
                    );

                $search = $innowork_directory->Search(
                    array(
                        'email' => $data['fromuser']
                        )
                    );

                if ( count( $search ) )
                {
                    list( $id, $values ) = each( $search );

                    $person = new InnoworkContact(
                        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
                        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
                        $id
                        );

                    $contact_data = $person->getItem();

                    if ( $contact_data['companyid'] )
                        $this->Edit( array( 'customerid' => $contact_data['companyid'] ) );
                }
            }
        }

        return $result;
    }
}
?>