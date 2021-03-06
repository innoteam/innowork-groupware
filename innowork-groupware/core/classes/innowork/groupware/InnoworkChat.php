<?php

require_once('innowork/core/InnoworkItem.php');

class InnoworkChat extends InnoworkItem {
    var $mNewDispatcher = '';
    var $mNewEvent = '';
    var $mNoAcl = true;
    
    function InnoworkChat(
        $rrootDb,
        $rdomainDA,
        $chatId = 0
        )
    {
        parent::__construct(
            $rrootDb,
            $rdomainDA,
            'chat',
            $chatId
            );
    }

    function getChannelsList()
    {
        $result = array();

        $chan_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
            'SELECT channel '.
            'FROM innowork_chat_logins '.
            'GROUP BY channel'
            );

        while ( !$chan_query->eof )
        {
            $result[$chan_query->getFields( 'channel' )] = $chan_query->getFields( 'channel' );

            $chan_query->moveNext();
        }

        return $result;
    }

    function getLoggedChannelsList()
    {
        $result = array();

        $chan_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
            'SELECT channel '.
            'FROM innowork_chat_logins '.
            'WHERE user='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName() )
            );

        while ( !$chan_query->eof )
        {
            $result[$chan_query->getFields( 'channel' )] = $chan_query->getFields( 'channel' );

            $chan_query->moveNext();
        }

        return $result;
    }

    function Login(
        $channel
        )
    {
        $check_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
            'SELECT * '.
            'FROM innowork_chat_logins '.
            'WHERE user='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(
                InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName()
                ).' '.
            'AND channel='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(
                $channel
                )
            );

        if ( !$check_query->getNumberRows() )
        {
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                'INSERT INTO innowork_chat_logins '.
                'VALUES ('.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName() ).','.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $channel ).')'
                );

            require_once('innomatic/locale/LocaleCatalog.php');
            $locale = new LocaleCatalog(
                 'innowork-groupware::chat_main',
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getLanguage()
                );

            $this->SendMessage(
                $channel,
                $locale->getStr( 'user_entered_channel.label' )
                );
        }

        return true;
    }

    function Logout(
        $channel
        )
    {
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
            'DELETE FROM innowork_chat_logins '.
            'WHERE user='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(
                InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName()
                ).' '.
            'AND channel='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(
                $channel
                )
            );

        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
            'DELETE FROM innowork_chat_messages '.
            'WHERE touser='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(
                InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName()
                ).' '.
            'AND channel='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(
                $channel
                )
            );

            require_once('innomatic/locale/LocaleCatalog.php');
            $locale = new LocaleCatalog(
                 'innowork-groupware::chat_main',
                 InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getLanguage()
                 );

            $this->SendMessage(
                $channel,
                $locale->getStr( 'user_quit_channel.label' )
                );

        return true;
    }

    function getChannelMessages(
        $channel
        )
    {
        $result = array();

        $messages_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
            'SELECT * '.
            'FROM innowork_chat_messages '.
            'WHERE touser='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(
                InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName()
                ).' '.
            'AND channel='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(
                $channel
                ).' '.
            'ORDER BY msgdate'
            );


        while ( !$messages_query->eof )
        {
            $result[] = array(
                'fromuser' => $messages_query->getFields( 'fromuser' ),
                'message' => $messages_query->getFields( 'message' )
                );

            $messages_query->moveNext();
        }

        return $result;
    }

    function getChannelUsers(
        $channel
        )
    {
        $result = array();

        $users_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
            'SELECT user '.
            'FROM innowork_chat_logins '.
            'WHERE channel='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $channel )
            );

        while ( !$users_query->eof )
        {
            $result[$users_query->getFields( 'user' )] = $users_query->getFields( 'user' );
            $users_query->moveNext();
        }

        return $result;
    }

    function SendMessage(
        $channel,
        $message
        )
    {
        $result = true;

        $users = $this->getChannelUsers( $channel );

		require_once('innomatic/locale/LocaleCountry.php');
        $locale_country = new LocaleCountry( InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry() );

        $time_array = $locale_country->getDateArrayFromUnixTimestamp( time() );

        foreach ( $users as $user )
        {
            $id = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getNextSequenceValue( 'innowork_chat_messages_id_seq' );
            
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                'INSERT INTO innowork_chat_messages VALUES('.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName() ).','.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $channel ).','.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $user ).','.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $message ).','.
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getTimestampFromDateArray( $time_array ) ).','.
                $id.
                ')'
                );

            $this->CleanMessages(
                $channel,
                $user
                );
        }

        return $result;
    }

    function CleanMessages(
        $channel,
        $user
        )
    {
        $count_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
            'SELECT id '.
            'FROM innowork_chat_messages '.
            'WHERE touser='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $user ).' '.
            'AND channel='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $channel ).' '.
            'ORDER BY id'
            );

        $count = $count_query->getNumberRows();
        
        if ( $count > 10 )
        {
            $row = 0;
            
            while ( 
                ( $count - $row > 10 )
                and
                !$count_query->eof 
                )
            {
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
                    'DELETE FROM innowork_chat_messages '.
                    'WHERE id='.$count_query->getFields( 'id' )
                    );
                    
                $count_query->moveNext();                
                $row++;
            }
        }

        return true;
    }

    function doGetSummary()
    {
        $xml_def = '';

        $locale = new LocaleCatalog(
            'innowork-groupware::chat_main',
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage()
            );

        $chans = $this->getChannelsList();

        if ( count( $chans ) )
        {
            $xml_def =
'<vertgroup>
  <children>

    <label>
      <args>
        <label type="encoded">'.urlencode( $locale->getStr( 'opened_chans.label' ) ).'</label>
        <compact>true</compact>
      </args>
    </label>';

			require_once('innomatic/wui/dispatch/WuiEventsCall.php');

            foreach ( $chans as $chan ) {
                $xml_def .=
'    <link>
      <args>
        <link type="encoded">'.urlencode(
            WuiEventsCall::buildEventsCallString(
                'innoworkchat',
                array(
                    array(
                        'view',
                        'default'
                        ),
                    array(
                        'action',
                        'join',
                        array(
                            'channel' => $chan
                            )
                        )
                    )
                )
            ).'</link>
        <compact>true</compact>
        <label type="encoded">'.urlencode( $chan ).'</label>
      </args>
    </link>';
            }

            $xml_def .=
'  </children>
</vertgroup>';
        }
        else
        {
            $xml_def =
'    <label>
      <args>
        <label type="encoded">'.urlencode( $locale->getStr( 'no_channels.label' ) ).'</label>
        <compact>true</compact>
      </args>
    </label>';
        }

        return $xml_def;
    }
}

?>
