<?php
// ----- Initialization -----
//
global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gToolbars;

require_once('innowork/groupware/InnoworkIntraMail.php');
require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/widgets/WuiWidget.php');
require_once('innomatic/wui/widgets/WuiContainerWidget.php');
require_once('innomatic/wui/dispatch/WuiEventsCall.php');
require_once('innomatic/wui/dispatch/WuiEvent.php');
require_once('innomatic/wui/dispatch/WuiEventRawData.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('innomatic/locale/LocaleCatalog.php'); require_once('innomatic/locale/LocaleCountry.php'); 

require_once('innowork/core/InnoworkCore.php');
$gInnowork_core = InnoworkCore::instance('innoworkcore', 
    InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
    InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
    );

$gLocale = new LocaleCatalog(
    'innowork-groupware::intramail_main',
    InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage()
    );

$gWui = Wui::instance('wui');
$gWui->loadWidget( 'xml' );
$gWui->loadWidget( 'innomaticpage' );
$gWui->loadWidget( 'innomatictoolbar' );

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr( 'intramail.title' );
$gCore_toolbars = $gInnowork_core->getMainToolBar();
$gToolbars['mail'] = array(
    'mailbox' => array(
        'label' => $gLocale->getStr( 'mailbox.toolbar' ),
        'themeimage' => 'mail_generic2',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            '' ) ) )
        ),
    'newmail' => array(
        'label' => $gLocale->getStr( 'newmail.toolbar' ),
        'themeimage' => 'mail_new',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'newmail',
            '' ) ) )
        )
    );
$gToolbars['fetchmail'] = array(
    'mailbox' => array(
        'label' => $gLocale->getStr( 'fetch.toolbar' ),
        'themeimage' => 'mail_get',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            '' ),
            array(
                'action',
                'fetchmail'
                ) ) )
        )
    );
$gToolbars['settings'] = array(
    'settings' => array(
        'label' => $gLocale->getStr( 'settings.toolbar' ),
        'themeimage' => 'configure',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( 'innoworkintramailprefs', array( array(
            'view',
            'default',
            '' ) ) )
        )
    );

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher( 'action' );

$gAction_disp->addEvent(
    'sendmail',
    'action_sendmail'
    );
function action_sendmail( $eventData )
{
    global $gLocale, $gPage_status;

    if (
        isset($eventData['addattachment'] )
        or
        isset($eventData['sendlater'] ) 
        )
    {
		require_once('innomatic/datatransfer/cache/CachedItem.php');
		require_once('innomatic/datatransfer/cache/CacheGarbageCollector.php');
    	
        $email = new CachedItem(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            'innowork-intramail',
            'composing_email',
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->domaindata['id'],
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
            );

        $email->Store( serialize( $eventData ) );
    }
    else
    {
        $innowork_mail = new InnoworkIntraMail(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
            );

        $country = new LocaleCountry( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getCountry() );
        $date_array = array(
            'year' => date( 'Y' ),
            'mon' => date( 'm' ),
            'mday' => date( 'd' ),
            'hours' => date( 'H' ),
            'minutes' => date( 'i' ),
            'seconds' => date( 's' )
            );

        $params['fromuser'] = InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName();
        $params['date'] = $date_array;
        $params['subject'] = $eventData['subject'];
        $params['content'] = $eventData['content'];

        if ( $innowork_mail->Create(
            $params,
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
            ) )
        {
            $toUsers = '';
            $start = true;

            foreach ( $eventData['tousers'] as $user )
            {
                if ( !$start ) $toUsers .= ',';
                $toUsers .= $user;

                $start = false;
            }

            $innowork_mail->Send( $toUsers );

		require_once('innomatic/datatransfer/cache/CachedItem.php');
		require_once('innomatic/datatransfer/cache/CacheGarbageCollector.php');
                    
        $email = new CachedItem(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            'innowork-intramail',
            'composing_email',
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->domaindata['id'],
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
            );
        $email->Destroy();

            $gPage_status = $gLocale->getStr( 'mail_sent.status' );
        }
        else $gPage_status = $gLocale->getStr( 'mail_not_sent.status' );
    }
}

$gAction_disp->addEvent(
    'destroy_composingemail',
    'action_destroy_composingemail'
    );
function action_destroy_composingemail(
    $eventData
    )
{
		require_once('innomatic/datatransfer/cache/CachedItem.php');
		require_once('innomatic/datatransfer/cache/CacheGarbageCollector.php');
	
        $email = new CachedItem(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            'innowork-intramail',
            'composing_email',
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->domaindata['id'],
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
            );

        $email->Destroy();    
}

$gAction_disp->addEvent(
    'save_mail',
    'action_savemail'
    );
function action_savemail( $eventData )
{
    global $gLocale, $gPage_status;

    $innowork_mail = new InnoworkIntraMail(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
        $eventData['mailid']
        );

    $mail_data = $innowork_mail->getItem();

    $email = $mail_data['headers'].
        "\n\n".
        $mail_data['content'].
        "\n";

    header( 'Content-Type: text/plain' );
    header( 'Content-Length: '.strlen( $email ) );
    header( 'Content-Disposition: attachment; filename='.$mail_data['subject'] );
    header( 'Pragma: no-cache' );
    print( $email );

    InnomaticContainer::instance('innomaticcontainer')->halt();
}

$gAction_disp->addEvent(
    'trashmail',
    'action_trashmail'
    );
function action_trashmail( $eventData )
{
    global $gLocale, $gPage_status;

    $innowork_mail = new InnoworkIntraMail(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
        $eventData['mailid']
        );

    $innowork_mail->Trash();

    $gPage_status = $gLocale->getStr( 'mail_trashed.status' );
}

$gAction_disp->addEvent(
    'restoremail',
    'action_restoremail'
    );
function action_restoremail( $eventData )
{
    global $gLocale, $gPage_status;

    $innowork_mail = new InnoworkIntraMail(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
        $eventData['mailid']
        );

    $innowork_mail->Restore();

    $gPage_status = $gLocale->getStr( 'mail_restored.status' );
}

$gAction_disp->addEvent(
    'emptytrashcan',
    'action_emptytrashcan'
    );
function action_emptytrashcan( $eventData )
{
    global $gLocale, $gPage_status;

    $innowork_mail = new InnoworkIntraMail(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
        $eventData['mailid']
        );

    $innowork_mail->EmptyTrashcan();

    $gPage_status = $gLocale->getStr( 'trashcan_cleaned.status' );
}

$gAction_disp->addEvent(
    'trash_messages',
    'action_trashmessages'
    );
function action_trashmessages( $eventData )
{
    global $gLocale, $gPage_status;

    foreach ( $eventData as $id => $val )
    {
        if (
            substr( $id, 0, 6 ) == 'erase_'
            and
            $val == 'on'
            )
        {
            $innowork_mail = new InnoworkIntraMail(
                InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
                substr( $id, 6 )
                );

            $innowork_mail->Trash();
        }
    }

    $gPage_status = $gLocale->getStr( 'messages_trashed.status' );
}

$gAction_disp->addEvent(
    'fetchmail',
    'action_fetchmail'
    );
function action_fetchmail( $eventData )
{
    global $gLocale, $gPage_status;

    $innowork_mail = new InnoworkIntraMail(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );

    $innowork_mail->FetchExternalMail();

    $gPage_status = $gLocale->getStr( 'mail_get.status' );
}

$gAction_disp->addEvent(
    'getattachment',
    'action_getattachment'
    );
function action_getattachment(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $innowork_mail = new InnoworkIntraMail(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
        $eventData['mailid']
        );
    $mail_data = $innowork_mail->getItem();

    require_once('innowork/groupware/InnoworkIntraMailMime.php');

    $msg = new Mail_MimeDecode(
        $mail_data['headers'].
        "\n\n".
        $mail_data['content'],
        "\n"
        );

    $params = array(
        'include_bodies' => true,
        'decode_headers' => true,
        'decode_bodies' => true
        );
    $structure = $msg->Decode( $params );

    if ( $structure->parts[$eventData['attachment']]->headers['content-transfer-encoding'] == 'base64' )
    {
        $body = base64_decode( $structure->parts[$eventData['attachment']]->body );
    }
    else
    {
        $body = $structure->parts[$eventData['attachment']]->body;
    }

    header(
        'Content-Type: '.
        $structure->parts[$eventData['attachment']]->ctype_primary.
        '/'.
        $structure->parts[$eventData['attachment']]->ctype_secondary
        );
    header( 'Content-Length: '.strlen( $body ) );
    header( 'Content-Disposition: inline; filename='.$structure->parts[$eventData['attachment']]->d_parameters['filename'] );
    header( 'Pragma: no-cache' );
    print( $body );
    InnomaticContainer::instance('innomaticcontainer')->halt();
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher( 'view' );

function mailbox_action_builder( $pageNumber )
{
    return WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            array( 'pagenumber' => $pageNumber )
        ) ) );
}

$gMain_disp->addEvent(
    'default',
    'main_default' );
function main_default( $eventData )
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gToolbars;

    if ( isset($eventData['id'] ) ) $eventData['mailid'] = $eventData['id'];

    if ( isset($eventData['mailid'] ) )
    {
        $innowork_mail = new InnoworkIntraMail(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
            $eventData['mailid']
            );

        $mail_data = $innowork_mail->getItem();
        if ( $mail_data['mailread'] == InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse )
        {
            $innowork_mail->setRead();
        }
    }

    require_once('innomatic/domain/user/UserSettings.php');
    $sets = new UserSettings(
    	InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
    	InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId());

    $country = new LocaleCountry(
        InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry()
        );

    if ( isset($eventData['foldertype'] ) )
    {
        $folder_type = $eventData['foldertype'];
        $sets->setKey(
            'innoworkgrouware-mailfolder',
            $eventData['foldertype']
            );
    }
    else
    {
        $folder_type = $sets->getKey( 'innoworkgropuware-mailfolder');
    }

    if ( !strlen( $folder_type ) ) $folder_type = INNOWORKINTRAMAIL_FOLDERTYPE_INBOX;

    if ( $folder_type == INNOWORKINTRAMAIL_FOLDERTYPE_TRASH )
    {
        $gToolbars['trashcan'] = array(
            'emptytrashcan' => array(
                'label' => $gLocale->getStr( 'emptytrash.toolbar' ),
                'themeimage' => 'trash',
                'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        '' ),
                    array(
                        'action',
                        'emptytrashcan',
                        ''
                     ) ) )
                )
            );
    }

    switch ( $folder_type )
    {
    case INNOWORKINTRAMAIL_FOLDERTYPE_INBOX:
        $top_header = $gLocale->getStr( 'folder_inbox.label' );
        break;
    case INNOWORKINTRAMAIL_FOLDERTYPE_OUTBOX:
        $top_header = $gLocale->getStr( 'folder_outbox.label' );
        break;
    case INNOWORKINTRAMAIL_FOLDERTYPE_TRASH:
        $top_header = $gLocale->getStr( 'folder_trashcan.label' );
        break;
    }

    $folder_id = 0;

    $mailbox_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
        'SELECT * '.
        'FROM innowork_email_messages '.
        'WHERE ownerid='.InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId().' '.
        'AND foldertype='.$folder_type.(
            $folder_id ? ' AND folderid='.$folder_id : ' '
            ).
        'ORDER BY id'
        );

    $headers[1]['label'] = $gLocale->getStr( 'subject.header' );
    $headers[2]['label'] = $gLocale->getStr(
        $folder_type == INNOWORKINTRAMAIL_FOLDERTYPE_OUTBOX ?
            'to.header' :
            'from.header'
        );
    $headers[3]['label'] = $gLocale->getStr( 'date.header' );

    $folders_headers[0]['label'] = $gLocale->getStr( 'folders.header' );

    $gXml_def =
'<vertgroup>
  <children>

  <horizgroup>
    <children>

      <table><name>folders'.$folder_type.'-'.$folder_id.'</name>
        <args>
          <headers type="array">'.WuiXml::encode( $folders_headers ).'</headers>
        </args>
        <children>

          <vertgroup row="0" col="0">
            <children>

              <button><name>folder</name>
                <args>
                  <themeimage>mail_get</themeimage>
                  <themeimagetype>mini</themeimagetype>
                  <frame>false</frame>
                  <horiz>true</horiz>
                  <disabled>'.( $folder_type == INNOWORKINTRAMAIL_FOLDERTYPE_INBOX ? 'true' : 'false' ).'</disabled>
                  <action type="encoded">'.urlencode(
                    WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        array( 'foldertype' => INNOWORKINTRAMAIL_FOLDERTYPE_INBOX )
                    ) ) )
                    ).'</action>
                  <label type="encoded">'.urlencode( $gLocale->getStr( 'folder_inbox.label' ) ).'</label>
                </args>
              </button>

              <button><name>folder</name>
                <args>
                  <themeimage>mail_send</themeimage>
                  <themeimagetype>mini</themeimagetype>
                  <frame>false</frame>
                  <horiz>true</horiz>
                  <disabled>'.( $folder_type == INNOWORKINTRAMAIL_FOLDERTYPE_OUTBOX ? 'true' : 'false' ).'</disabled>
                  <action type="encoded">'.urlencode(
                    WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        array( 'foldertype' => INNOWORKINTRAMAIL_FOLDERTYPE_OUTBOX )
                    ) ) )
                    ).'</action>
                  <label type="encoded">'.urlencode( $gLocale->getStr( 'folder_outbox.label' ) ).'</label>
                </args>
              </button>

              <button><name>folder</name>
                <args>
                  <themeimage>trash</themeimage>
                  <themeimagetype>mini</themeimagetype>
                  <frame>false</frame>
                  <horiz>true</horiz>
                  <disabled>'.( $folder_type == INNOWORKINTRAMAIL_FOLDERTYPE_TRASH ? 'true' : 'false' ).'</disabled>
                  <action type="encoded">'.urlencode(
                    WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        array( 'foldertype' => INNOWORKINTRAMAIL_FOLDERTYPE_TRASH )
                    ) ) )
                    ).'</action>
                  <label type="encoded">'.urlencode( $gLocale->getStr( 'folder_trashcan.label' ) ).'</label>
                </args>
              </button>

            </children>
          </vertgroup>

        </children>
      </table>

      <vertgroup>
        <children>

        <form><name>folder</name>
          <args>
            <action type="encoded">'.urlencode(
                WuiEventsCall::buildEventsCallString(
                    '',
                    array(
                        array(
                            'view',
                            'default'
                            ),
                        array(
                            'action',
                            'trash_messages'
                            )
                        )
                    )
                ).'</action>
          </args>
          <children>

      <table><name>folder</name>
        <args>
          <topheader type="encoded">'.urlencode( $top_header ).'</topheader>
          <headers type="array">'.WuiXml::encode( $headers ).'</headers>
          <rowsperpage>10</rowsperpage>
          <width>100%</width>
          <pagesactionfunction>mailbox_action_builder</pagesactionfunction>
          <pagenumber>'.( isset($eventData['pagenumber'] ) ? $eventData['pagenumber'] : '' ).'</pagenumber>
        </args>
        <children>';

    $row = 0;

    $last_email = 0;

    while ( !$mailbox_query->eof )
    {
        $users = '';

        $mail_read = $mailbox_query->getFields( 'mailread' );

        if ( $row == $mailbox_query->getNumberRows() - 1 )
        {
            $mail_read = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue;
        }

            if ( $folder_type == INNOWORKINTRAMAIL_FOLDERTYPE_OUTBOX )
            {
                $to_users_array = explode( ',', $mailbox_query->getFields( 'touser' ) );

                $start = true;

                foreach ( $to_users_array as $id )
                {
                    if ( $id )
                    {
                        if ( !$start ) $users .= ', ';

                        $users .= $id;

                        $start = false;
                    }
                }
            }
            else
            {
                $users = $mailbox_query->getFields( 'fromuser' );
            }

        $date_array = $country->getDateArrayFromSafeTimestamp(
            $mailbox_query->getFields( 'date' )
            );

        if ( $folder_type != INNOWORKINTRAMAIL_FOLDERTYPE_TRASH ) $gXml_def .=
'<checkbox row="'.$row.'" col="0"><name>erase_'.$mailbox_query->getFields( 'id' ).'</name>
  <args>
    <disp>action</disp>
  </args>
</checkbox>';

$gXml_def .=
'<link row="'.$row.'" col="1">
  <args>
    <bold>'.( $mail_read == InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse ? 'true' : 'false' ).'</bold>
    <link type="encoded">'.urlencode(
                WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        array( 'mailid' => $mailbox_query->getFields( 'id' ) )
                    ) ) )
        ).'</link>
    <compact>true</compact>
    <label type="encoded">'.urlencode(
        strlen( $mailbox_query->getFields( 'subject' ) ) > 45 ?
        substr( $mailbox_query->getFields( 'subject' ), 0, 42 ).'...' :
        $mailbox_query->getFields( 'subject' )
        ).'</label>
    <title type="encoded">'.urlencode( $mailbox_query->getFields( 'subject' ) ).'</title>
  </args>
</link>
<label row="'.$row.'" col="2">
  <args>
    <bold>'.( $mail_read == InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse ? 'true' : 'false' ).'</bold>
    <label type="encoded">'.urlencode(
        strlen( $users ) > 30 ?
        substr( $users, 0, 27 ).'...' :
        $users
        ).'</label>
    <compact>true</compact>
  </args>
</label>
<label row="'.$row.'" col="3">
  <args>
    <bold>'.( $mail_read == InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse ? 'true' : 'false' ).'</bold>
    <label type="encoded">'.urlencode(
        $country->FormatShortArrayDate( $date_array ).' '.
        $country->FormatArrayTime( $date_array )
        ).'</label>
    <compact>true</compact>
  </args>
</label>';

        $last_email = $mailbox_query->getFields( 'id' );
        $row++;
        $mailbox_query->moveNext();
        if ( isset($user_query ) and is_object( $user_query ) ) $user_query->free();
    }

    if (
        !isset($eventData['mailid'] )
        and
        $last_email
        )
    {
        $eventData['mailid'] = $last_email;
    }

    if ( !$mailbox_query->getNumberRows() )
    {
        $gPage_status = $gLocale->getStr( 'noemails.label' );
    }

    $gXml_def .=
'        </children>
      </table>

        </children>
      </form>';

    if ( $mailbox_query->getNumberRows() && $folder_type != INNOWORKINTRAMAIL_FOLDERTYPE_TRASH )
    {
        $gXml_def .=
'              <button>
                <args>
                  <themeimage>trash</themeimage>
                  <themeimagetype>mini</themeimagetype>
                  <frame>false</frame>
                  <horiz>true</horiz>
                  <formsubmit>folder</formsubmit>
            <action type="encoded">'.urlencode(
                WuiEventsCall::buildEventsCallString(
                    '',
                    array(
                        array(
                            'view',
                            'default'
                            ),
                        array(
                            'action',
                            'trash_messages'
                            )
                        )
                    )
                ).'</action>
                  <label type="encoded">'.urlencode( $gLocale->getStr( 'trash_marked_messages.label' ) ).'</label>
                </args>
              </button>';
    }

    $mailbox_query->free();

    if ( isset($eventData['mailid'] ) )
    {
        $innowork_mail = new InnoworkIntraMail(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
            $eventData['mailid']
            );

        $mail_data = $innowork_mail->getItem();
        if ( $mail_data['mailread'] == InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse )
        {
            $innowork_mail->setRead();
        }

        $from_user = $mail_data['fromuser'];

        $date_array = $country->getDateArrayFromSafeTimestamp(
            $mail_data['date']
            );

        require_once('innowork/groupware/InnoworkIntraMailMime.php');
        $msg = new Mail_MimeDecode(
            $mail_data['headers'].
            "\n\n".
            $mail_data['content'],
            "\n"
            );

        $params = array(
            'include_bodies' => true,
            'decode_headers' => true,
            'decode_bodies' => true
            );
        $structure = $msg->Decode( $params );
/*
        echo '<pre>';
        print_r( $structure );
        echo '</pre>';
*/
/*
        require_once( SM_PATH.'class/mime/Message.class.php' );
        require_once( SM_PATH.'class/mime/Rfc822Header.class.php' );
        require_once( SM_PATH.'functions/mime.php' );
        //$msg = new Message();
        $bodystructure = 'bodystructure'.$mail_data['content'];

    //$bodystructure = implode('',$read);
    $msg =  mime_structure($bodystructure, array() );
    //$read = sqimap_run_command ($imap_stream, "FETCH $id BODY[HEADER]", true, $response, $message, $uid_support);
    $rfc822_header = new Rfc822Header();
    $rfc822_header->parseHeader( $mail_data['headers'] );
    $msg->rfc822_header = $rfc822_header;
    */

        //$i = 0;
        //$msg->parsestructure( $mail_data['headers']."\n".$mail_data['body'], $i );

        $m_row = 0;

        //print_r( $msg );
        $gXml_def .=
'  <horizbar/>

    <table><name>mail</name>
      <args>
        <headers type="array">'.WuiXml::encode(
            array( '0' => array(
                'label' => $mail_data['subject']
                ) ) ).'</headers>
      </args>
      <children>

   <vertgroup row="'.$m_row++.'" col="0">
     <children>

       <grid>
         <children>

            <label row="0" col="0" halign="left" valign="top">
              <args>
                <label type="encoded">'.urlencode(
                    $folder_type == INNOWORKINTRAMAIL_FOLDERTYPE_OUTBOX ?
                    $gLocale->getStr( 'touser.label' ) :
                    $gLocale->getStr( 'fromuser.label' )
                    ).'</label>
              </args>
            </label>
            <string row="0" col="1" halign="left" valign="top"><name>from</name>
              <args>
                <size>60</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode(
                    $folder_type == INNOWORKINTRAMAIL_FOLDERTYPE_OUTBOX ?
                    $mail_data['touser']:
                    $from_user
                    ).'</value>
              </args>
            </string>

            <label row="1" col="0" halign="left" valign="top">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'date.label' ) ).'</label>
              </args>
            </label>
            <string row="1" col="1" halign="left" valign="top"><name>date</name>
              <args>
                <size>60</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode(
                    $country->FormatShortArrayDate( $date_array ).' '.
                    $country->FormatArrayTime( $date_array ) ).'</value>
              </args>
            </string>

            <label row="2" col="0" halign="left" valign="top">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'subject.label' ) ).'</label>
              </args>
            </label>
            <string row="2" col="1" halign="left" valign="top"><name>subject</name>
              <args>
                <size>60</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode( $mail_data['subject'] ).'</value>
              </args>
            </string>

         </children>
       </grid>

            <text><name>content</name>
              <args>
                <rows>15</rows>
                <cols>80</cols>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode( isset($structure->body ) ? $structure->body : $structure->parts[0]->body ).'</value>
              </args>
            </text>

     </children>
   </vertgroup>';

   if ( isset($structure->parts ) and count( $structure->parts ) > 1 )
   {
        $att_headers[1]['label'] = $gLocale->getStr( 'filename.header' );
        $att_headers[2]['label'] = $gLocale->getStr( 'type.header' );
        $att_headers[3]['label'] = $gLocale->getStr( 'encoding.header' );
        $att_headers[4]['label'] = $gLocale->getStr( 'size.header' );

        $gXml_def .=
'<table row="'.$m_row++.'" col="0">
  <args>
    <width>100%</width>
    <headers type="array">'.WuiXml::encode( $att_headers ).'</headers>
  </args>
  <children>';

        for ( $i = 1; $i < count( $structure->parts ); $i++ )
        {
            $gXml_def .=
'<button row="'.( $i - 1 ).'" col="0">
  <args>
    <themeimage>attach</themeimage>
    <themeimagetype>mini</themeimagetype>
    <action type="encoded">'.urlencode(
        WuiEventsCall::buildEventsCallString(
            '',
            array(
                array(
                    'view',
                    'default',
                    array(
                        'mailid' => $eventData['mailid']
                        )
                    ),
                array(
                    'action',
                    'getattachment',
                    array(
                        'mailid' => $eventData['mailid'],
                        'attachment' => $i
                        )
                    )
                )
            )
        ).'</action>
  </args>
</button>
<link row="'.( $i - 1 ).'" col="1">
  <args>
    <label type="encoded">'.urlencode( $structure->parts[$i]->d_parameters['filename'] ).'</label>
    <link type="encoded">'.urlencode(
        WuiEventsCall::buildEventsCallString(
            '',
            array(
                array(
                    'view',
                    'default',
                    array(
                        'mailid' => $eventData['mailid']
                        )
                    ),
                array(
                    'action',
                    'getattachment',
                    array(
                        'mailid' => $eventData['mailid'],
                        'attachment' => $i
                        )
                    )
                )
            )
        ).'</link>
  </args>
</link>
<label row="'.( $i - 1 ).'" col="2">
  <args>
    <label type="encoded">'.urlencode( $structure->parts[$i]->ctype_primary.'/'.$structure->parts[$i]->ctype_secondary ).'</label>
  </args>
</label>
<label row="'.( $i - 1 ).'" col="3">
  <args>
    <label type="encoded">'.urlencode( $structure->parts[$i]->headers['content-transfer-encoding'] ).'</label>
  </args>
</label>
<label row="'.( $i - 1 ).'" col="4" halign="right">
  <args>
    <label type="encoded">'.urlencode( $country->FormatNumber( strlen( $structure->parts[$i]->body ), 0 ) ).'</label>
  </args>
</label>';
        }

        $gXml_def .= '</children></table>';
   }

    $gXml_def .=
'   <horizgroup row="'.$m_row++.'" col="0">
     <children>

              <button><name>reply</name>
                <args>
                  <themeimage>mail_reply</themeimage>
                  <frame>false</frame>
                  <horiz>true</horiz>
                  <action type="encoded">'.urlencode(
                    WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'newmail',
                        array( 'mailid' => $eventData['mailid'] )
                        )
                      ) )
                    ).'</action>
                  <label type="encoded">'.urlencode( $gLocale->getStr( 'reply.button' ) ).'</label>
                </args>
              </button>

              <button><name>save</name>
                <args>
                  <themeimage>filesave</themeimage>
                  <frame>false</frame>
                  <horiz>true</horiz>
                  <action type="encoded">'.urlencode(
                    WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'action',
                        'save_mail',
                        array( 'mailid' => $eventData['mailid'] )
                        )
                      ) )
                    ).'</action>
                  <label type="encoded">'.urlencode( $gLocale->getStr( 'save.button' ) ).'</label>
                </args>
              </button>

              <button><name>trash</name>
                <args>
                  <themeimage>trash</themeimage>
                  <frame>false</frame>
                  <horiz>true</horiz>
                  <action type="encoded">'.urlencode(
                    WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        $folder_type == INNOWORKINTRAMAIL_FOLDERTYPE_TRASH ?
                        'restoremail' :
                        'trashmail',
                        array( 'mailid' => $eventData['mailid'] )
                    ) ) )
                    ).'</action>
                  <label type="encoded">'.urlencode( $gLocale->getStr(
                    $folder_type == INNOWORKINTRAMAIL_FOLDERTYPE_TRASH ?
                    'restore.button' :
                    'trash.button'
                    ) ).'</label>
                </args>
              </button>

     </children>
   </horizgroup>

     </children>
   </table>';
    }

    $gXml_def .=
'    </children>
  </vertgroup>

    </children>
  </horizgroup>

  </children>
</vertgroup>';
}

$gMain_disp->addEvent(
    'newmail',
    'main_newmail'
    );
function main_newmail( $eventData )
{
    global $gXml_def, $gLocale, $gPage_title;

	require_once('innomatic/datatransfer/cache/CachedItem.php');
	require_once('innomatic/datatransfer/cache/CacheGarbageCollector.php');
    
        $cached_data = new CachedItem(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            'innowork-intramail',
            'composing_email',
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->domaindata['id'],
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
            );


    // Intranet users

    $users_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
        'SELECT id,username '.
        'FROM domain_users '.
        'WHERE username<>'.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(User::getAdminUsername(InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDomainId())).' '.
        'ORDER BY username'
        );

    $users_array = array();

    while ( !$users_query->eof )
    {
        $users_array[$users_query->getFields( 'username' )] = $users_query->getFields( 'username' );
        $users_query->moveNext();
    }

    $users_query->free();

    // Companies

    $innowork_companies = new InnoworkCompany(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );

    $companies_search = $innowork_companies->Search( '' );

    foreach ( $companies_search as $company )
    {
        if ( strlen( $company['email'] ) )
        {
            $users_array[$company['email']] = $company['companyname'].' ('.$company['email'].')';
        }
    }

    unset( $companies_search );
    unset( $innowork_companies );

    // Personal contacts

    $innowork_people = new InnoworkContact(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );

    $people_search = $innowork_people->Search( '' );

    foreach ( $people_search as $person )
    {
        if ( strlen( $person['email'] ) )
        {
            $users_array[$person['email']] = $person['lastname'].' '.$person['firstname'].' ('.$person['email'].')';
        }
    }

    unset( $companies_search );
    unset( $innowork_companies );

    $composing = false;

    if ( isset($eventData['mailid'] ) )
    {
        $innowork_mail = new InnoworkIntraMail(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
            $eventData['mailid']
            );

        $mail_data = $innowork_mail->getItem();
        if ( !( strtolower( substr( $mail_data['subject'], 0, 4 ) ) == 're: ' ) )
        {
            $mail_data['subject'] = 'Re: '.$mail_data['subject'];
        }

        $content = explode( "\n", wordwrap( $mail_data['content'], 72 ) );
        $mail_data['content'] = '';

        foreach ( $content as $line )
        {
            $mail_data['content'] .= '> '.$line."\n";
        }

        $mail_data['sendto'][] = $mail_data['fromuser'];
    }
    else
    {
        $cached_data_content = $cached_data->Retrieve();
        
        if ( $cached_data->mResult != CachedItem::ITEM_NOT_FOUND )
        {
            $composing = true;
            $cached_data_array = unserialize( $cached_data_content );
            
            print_r( $cached_data_array );
            
            $mail_data['subject'] = $cached_data_array['subject'];
            $mail_data['content'] = $cached_data_array['content'];
            $mail_data['sendto'] = $cached_data_array['tousers'];
        }
        else
        {
            $mail_data['subject'] = '';
            $mail_data['content'] = '';
            $mail_data['sendto'] = '';
        }
    }

    /*
    // Projects list

    $innowork_projects = new InnoworkProject(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );
    $search_results = $innowork_projects->Search(
        '',
        InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
        );

    $projects['0'] = $gLocale->getStr( 'noproject.label' );

    while ( list( $id, $fields ) = each( $search_results ) )
    {
        $projects[$id] = $fields['name'];
    }
    */

    // Emission date

    $locale_country = new LocaleCountry(
        InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry()
        );

    $curr_date = $locale_country->getDateArrayFromSafeTimestamp(
        $locale_country->SafeFormatTimestamp()
        );

    // Attachments

    $att_headers[1]['label'] = $gLocale->getStr( 'filename.header' );
    $att_headers[2]['label'] = $gLocale->getStr( 'type.header' );
    $att_headers[3]['label'] = $gLocale->getStr( 'encoding.header' );
    $att_headers[4]['label'] = $gLocale->getStr( 'size.header' );

    // Defaults

    $gXml_def .=
'<vertgroup><name>newmail</name>
  <children>

    <table><name>invoice</name>
      <args>
        <headers type="array">'.WuiXml::encode(
            array( '0' => array(
                'label' => $gLocale->getStr( 'newmail.header' )
                ) ) ).'</headers>
      </args>
      <children>

    <form row="0" col="0"><name>mail</name>
      <args>
        <method>post</method>
        <action type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    ''
                    ),
                array(
                    'action',
                    'sendmail',
                    '' )
            ) ) ).'</action>
      </args>
      <children>

        <vertgroup>
          <children>

          <grid>
            <children>

            <label row="0" col="0" halign="left" valign="top"><name>tousers</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'tousers.label' ) ).'</label>
              </args>
            </label>
            <listbox row="0" col="1" halign="left" valign="top"><name>tousers</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $users_array ).'</elements>
                <multiselect>true</multiselect>
                <size>5</size>
                <default type="array">'.WuiXml::encode( $mail_data['sendto'] ).'</default>
              </args>
            </listbox>

            <label row="1" col="0" halign="left" valign="top"><name>subject</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'subject.label' ) ).'</label>
              </args>
            </label>
            <string row="1" col="1" halign="left" valign="top"><name>subject</name>
              <args>
                <disp>action</disp>
                <size>60</size>
                <value type="encoded">'.urlencode( $mail_data['subject'] ).'</value>
              </args>
            </string>

            </children>
          </grid>

            <text><name>content</name>
              <args>
                <rows>15</rows>
                <cols>80</cols>
                <disp>action</disp>
                <value type="encoded">'.urlencode( $mail_data['content'] ).'</value>
              </args>
            </text>

            <horizgroup>
              <children>
              
                <file>
                  <name>attachment</name>
                  <args>
                    <disp>action</disp>
                  </args>
                </file>

                <button>
          <args>
            <themeimage>attach</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <formsubmit>mail</formsubmit>
            <label type="encoded">'.urlencode( $gLocale->getStr( 'attach.button' ) ).'</label>
            <action type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'newmail',
                    ''
                    ),
                array(
                    'action',
                    'sendmail',
                    array(
                        'addattachment' => '1'
                        ) )
            ) ) ).'</action>
          </args>
                </button>

              </children>
            </horizgroup>

        <table>
          <args>
            <headers type="array">'.WuiXml::encode( $att_headers ).'</headers>
            <width>100%</width>
          </args>
          <children>';
          
    $gXml_def .=
';          </children>
        </table>

          </children>
        </vertgroup>
        
        </children>
        </form>

        <horizbar/>

        <horizgroup row="1" col="0">
          <children>
          
        <button><name>send</name>
          <args>
            <themeimage>mail_send</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <formsubmit>mail</formsubmit>
            <label type="encoded">'.urlencode( $gLocale->getStr( 'sendmail.submit' ) ).'</label>
            <action type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    ''
                    ),
                array(
                    'action',
                    'sendmail',
                    '' )
            ) ) ).'</action>
          </args>
        </button>

        <button>
          <args>
            <themeimage>mail_forward</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <formsubmit>mail</formsubmit>
            <label type="encoded">'.urlencode( $gLocale->getStr( 'sendlater.button' ) ).'</label>
            <action type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    ''
                    ),
                array(
                    'action',
                    'sendmail',
                    array(
                        'sendlater' => '1'
                        ) )
            ) ) ).'</action>
          </args>
        </button>

        <button>
          <args>
            <themeimage>mail_new</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <label type="encoded">'.urlencode( $gLocale->getStr( 'destroy_composing.button' ) ).'</label>
            <action type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'newmail',
                    ''
                    ),
                array(
                    'action',
                    'destroy_composingemail',
                    '' )
            ) ) ).'</action>
          </args>
        </button>

            </children>
          </horizgroup>
          
      </children>
    </table>
  </children>
</vertgroup>';
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//
$gWui->addChild( new WuiInnomaticPage( 'page', array(
    'pagetitle' => $gPage_title,
    'icon' => 'message',
    'toolbars' => array(
        new WuiInnomaticToolbar(
            'view',
            array(
                'toolbars' => $gToolbars, 'toolbar' => 'true'
                ) ),
        new WuiInnomaticToolBar(
            'core',
            array(
                'toolbars' => $gCore_toolbars, 'toolbar' => 'true'
                ) ),
            ),
    'maincontent' => new WuiXml(
        'page', array(
            'definition' => $gXml_def
            ) ),
    'status' => $gPage_status
    ) ) );

$gWui->render();

?>
