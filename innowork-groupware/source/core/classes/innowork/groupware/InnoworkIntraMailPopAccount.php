<?php

class InnoworkIntraMailPopAccount
{
    var $mId = 0;
    var $mName;
    var $mHostname;
    var $mPort;
    var $mUsername;
    var $mPassword;
    var $mMailboxHandler;
    var $mDeleteMessages;

    function InnoworkIntraMailPopAccount(
        $accountId = 0
        )
    {
    	require_once('pop3/POP3.php');
        require_once('innowork/groupware/InnoworkIntraMailMime.php');
        $accountId = (int)$accountId;

        if ( $accountId )
        {
            $query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                'SELECT * '.
                'FROM innowork_email_accounts '.
                'WHERE id='.$accountId
                );

            if ( $query->getNumberRows() )
            {
                $this->mId = $accountId;

                $this->mName = $query->getFields( 'accountname' );
                $this->mHostname = $query->getFields( 'hostname' );
                $this->mPort = $query->getFields( 'port' );
                $this->mUsername = $query->getFields( 'username' );
                $this->mPassword = $query->getFields( 'password' );
                $this->mDeleteMessages = $query->getFields( 'deletemessages' ) == InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue ? true : false;
            }
        }
    }

    function Create(
        $name,
        $hostname,
        $port,
        $username,
        $password
        )
    {
        $result = false;

        $port = (int)$port;
        if ( !strlen( $port ) ) $port = 110;

        $id = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getNextSequenceValue( 'innowork_email_accounts_id_seq' );

        if ( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                'INSERT INTO innowork_email_accounts VALUES ('.
                $id.','.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId().','.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $name ).','.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $username ).','.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $password ).','.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $hostname ).','.
                $port.','.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue ).
                ')'
            ) )
        {
            $this->mId = $id;
            $this->mName = $name;
            $this->mHostname = $hostname;
            $this->mPort = $port;
            $this->mUsername = $username;
            $this->mPassword = $password;

            $result = true;
        }

        return $result;
    }

    function Remove()
    {
        $result = false;

        if ( $this->mId )
        {
            if ( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                    'DELETE FROM innowork_email_accounts '.
                    'WHERE id='.$this->mId
                    ) )
            {
                $this->mId = 0;

                $this->mName = $this->mHostname = $this->mPort = $this->mUsername = $this->mPassword = '';
            }
        }

        return $result;
    }

    function getName()
    {
        return $this->mName;
    }

    function setName(
        $name
        )
    {
        $result = false;

        if ( $this->mId and strlen( $name ) )
        {
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                'UPDATE innowork_email_accounts '.
                'SET accountname='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $name ).' '.
                'WHERE id='.$this->mId
                );

            $this->mName = $name;
            $result = true;
        }

        return $result;
    }

    function getHostname()
    {
        return $this->mHostname;
    }

    function setHostname(
        $name
        )
    {
        $result = false;

        if ( $this->mId and strlen( $name ) )
        {
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                'UPDATE innowork_email_accounts '.
                'SET hostname='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $name ).' '.
                'WHERE id='.$this->mId
                );

            $this->mHostame = $name;
            $result = true;
        }

        return $result;
    }

    function getPort()
    {
        return $this->mPort;
    }

    function setPort(
        $port
        )
    {
        $result = false;

        $port = (int)$port;

        if ( $this->mId and strlen( $port ) )
        {
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                'UPDATE innowork_email_accounts '.
                'SET port='.$port.' '.
                'WHERE id='.$this->mId
                );

            $this->mPort = $port;
            $result = true;
        }

        return $result;
    }

    function getUsername()
    {
        return $this->mUsername;
    }

    function setUsername(
        $name
        )
    {
        $result = false;

        if ( $this->mId and strlen( $name ) )
        {
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                'UPDATE innowork_email_accounts '.
                'SET username='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $name ).' '.
                'WHERE id='.$this->mId
                );

            $this->mUsername = $name;
            $result = true;
        }

        return $result;
    }

    function getPassword()
    {
        return $this->mPassword;
    }

    function setPassword(
        $password
        )
    {
        $result = false;

        if ( $this->mId and strlen( $password ) )
        {
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                'UPDATE innowork_email_accounts '.
                'SET password='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $password ).' '.
                'WHERE id='.$this->mId
                );

            $this->mPassword = $password;
            $result = true;
        }

        return $result;
    }

    function getDeleteMessages()
    {
        return $this->mDeleteMessages;
    }

    function setDeleteMessages(
        $delete
        )
    {
        $result = false;

        if ( $this->mId )
        {
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                'UPDATE innowork_email_accounts '.
                'SET deletemessages='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(
                    $delete ?
                    InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue :
                    InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse
                    ).' '.
                'WHERE id='.$this->mId
                );

            $this->mDeleteMessages = $delete;
            $result = true;
        }

        return $result;
    }

    function Open()
    {
        $result = false;

        if ( is_object( $this->mMailboxHandler ) )
        {
            $this->mMailboxHandler->Close();
        }

        $this->mMailboxHandler = new POP3();
        $this->mMailboxHandler->hostname = $this->mHostname;
        $this->mMailboxHandler->port = $this->mPort;

        if ( $this->mMailboxHandler->Open() == '' ) $result = true;

        return true;
    }

    function Close()
    {
        $result = false;

        if ( is_object( $this->mMailboxHandler ) )
        {
            $this->mMailboxHandler->Close();
            unset( $this->mMailboxHandler );
            $this->mMailboxHandler = '';

            $result = true;
        }

        return $result;
    }

    function Login()
    {
        $result = false;

        if ( is_object( $this->mMailboxHandler ) )
        {
            if ( $this->mMailboxHandler->Login( $this->mUsername, $this->mPassword, 0 ) == '' )
            {
                $result = true;
            }
        }

        return $result;
    }

    function Statistics()
    {
        $result = array();
        $result['messages'] = $result['size'] = 0;

        if ( is_object( $this->mMailboxHandler ) )
        {
            $messages = $size = 0;

            $this->mMailboxHandler->Statistics(
                $messages,
                $size
                );

            $result['messages'] = $messages;
            $result['size'] = $size;
        }

        return $result;
    }

    function ListMessages()
    {
        $result = array();

        if ( is_object( $this->mMailboxHandler ) )
        {
            $result = $this->mMailboxHandler->ListMessages( '', 1 );
        }

        return $result;
    }

    function RetrieveMessage(
        $number,
        $lines = -1,
        $uniqId = ''
        )
    {
        $result = array();

        if ( is_object( $this->mMailboxHandler ) )
        {
            $headers = $body = '';
            $headers_array = $body_array = array();

            $this->mMailboxHandler->RetrieveMessage(
                $number,
                $headers_array,
                $body_array,
                $lines
                );

            if ( $this->getDeleteMessages() ) $this->mMailboxHandler->DeleteMessage( $number );

            for ( $line = 0; $line < count( $headers_array ); $line++ )
                $headers .= $headers_array[$line]."\n";

            for ( $line = 0; $line < count( $body_array ); $line++ )
                $body .= $body_array[$line]."\n";

            $date = '';
            $subject = '';
            $from = '';

            for ( $line = 0; $line < count( $headers_array ); $line++ )
            {
                $line_text = $headers_array[$line];

                switch ( strtolower( $line_text{0} ) )
                {
                case 'f':
                    if ( strtolower( substr( $line_text, 0, 5 ) ) == 'from:' )
                    {
                        require_once( SM_PATH.'functions/imap_parse.php' );
                        $from_obj = sqimap_parse_address( trim( substr( $line_text, 5 ) ) );
                        $from = $from_obj->mailbox.'@'.$from_obj->host;
                    }
                    break;

                case 'd':
                    if ( strtolower( substr( $line_text, 0, 5 ) ) == 'date:' )
                    {
                        require_once('innomatic/locale/LocaleCatalog.php'); require_once('innomatic/locale/LocaleCountry.php'); 
                        require_once( SM_PATH.'functions/date.php' );

                        $country = new LocaleCountry(
                            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry()
                            );

                        $date_raw = strtr( trim( substr( $line_text, 5 ) ), array( '  ' => ' ' ) );

                        $date = $country->getDateArrayFromUnixTimestamp(
                            gettimestamp( explode( ' ', $date_raw ) )
                            );
                    }

                case 's':
                    if ( strtolower( substr( $line_text, 0, 8 ) ) == 'subject:' )
                    {
                        $subject = trim( substr( $line_text, 8 ) );
                    }
                    break;
                }
            }

            $innowork_mail = new InnoworkIntraMail(
                InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
                );
            $result = $innowork_mail->Receive(
                $from,
                InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName(),
                $date,
                $subject,
                $body,
                $headers
                );
            $innowork_mail->LookupCustomer();

            if ( $uniqId )
            {
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                    'INSERT INTO innowork_email_uids VALUES('.
                    InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId().','.
                    InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $uniqId ).','.
                    $this->mId.')'
                    );
            }
        }

        return $result;
    }

    function DeleteMessage(
        $number
        )
    {
        $result = false;

        if ( is_object( $this->mMailboxHandler ) )
        {
            $result = $this->mMailboxHandler->DeleteMessage(
                $number
                );
        }

        return $result;
    }

    function RetrieveAllMessages()
    {
        $result = false;

        if ( $this->Open() )
        {
            if ( $this->Login() )
            {
                $stats = $this->Statistics();
                $messages = $this->ListMessages();

                if ( $stats['messages'] )
                {
                    for ( $i = 1; $i <= $stats['messages']; $i++ )
                    {
                        $uniqid_check = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                            'SELECT uniqid '.
                            'FROM innowork_email_uids '.
                            'WHERE uniqid='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $messages[$i] ).' '.
                            'AND ownerid='.InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId().' '.
                            'AND accountid='.$this->mId
                            );

                        if ( !$uniqid_check->getNumberRows() ) $message = $this->RetrieveMessage( $i, -1, $messages[$i] );
                    }
                }

                // Clear old uniq ids

                $uniqids = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                    'SELECT uniqid '.
                    'FROM innowork_email_uids '.
                    'WHERE ownerid='.InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId().' '.
                    'AND accountid='.$this->mId
                    );

                while ( !$uniqids->eof )
                {
                    if ( !in_array( $uniqids->getFields( 'uniqid' ), $messages ) )
                    {
                        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                            'DELETE FROM innowork_email_uids '.
                            'WHERE ownerid='.InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId().' '.
                            'AND accountid='.$this->mId.' '.
                            'AND uniqid='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $uniqids->getFields( 'uniqid' ) )
                            );
                    }

                    $uniqids->moveNext();
                }

                $result = true;
            }

            $this->Close();
        }

        return $result;
    }
}

?>
