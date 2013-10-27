<?php
// ----- Initialization -----
//
global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gInnowork_core, $gChannel;
    
require_once('innowork/groupware/InnoworkChat.php');
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
    'innowork-groupware::chat_main',
    InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage()
    );

$gWui = Wui::instance('wui');
$gWui->loadWidget( 'xml' );
$gWui->loadWidget( 'innomaticpage' );
$gWui->loadWidget( 'innomatictoolbar' );

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr( 'chat.title' );
$gCore_toolbars = $gInnowork_core->getMainToolBar();
$gToolbars['chat'] = array(
    'chat' => array(
        'label' => $gLocale->getStr( 'chat.toolbar' ),
        'themeimage' => 'kdmconfig',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            '' ) ) )
        )
    );

require_once('shared/wui/WuiSessionkey.php');

$channel_sk = new WuiSessionKey( 'chat_channel' );
$gChannel = $channel_sk->mValue;

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher( 'action' );

$gAction_disp->addEvent(
    'join',
    'action_join'
    );
function action_join(
    $eventData
    )
{
    global $gChannel;

    if ( strlen( $eventData['channel'] ) )
    {
        $chat = new InnoworkChat(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
            );

        $chat->Login( $eventData['channel'] );

        $channel_sk = new WuiSessionKey(
            'chat_channel',
            array(
                'value' => $eventData['channel']
                )
            );

        $gChannel = $eventData['channel'];
    }
}

$gAction_disp->addEvent(
    'exitchan',
    'action_exitchan'
    );
function action_exitchan(
    $eventData
    )
{
    global $gChannel;
    
    require_once('shared/wui/WuiSessionkey.php');

    if ( strlen( $gChannel ) )
    {
        $chat = new InnoworkChat(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
            );

        $chat->Logout( $gChannel );

        $gChannel = '';

        $chans = $chat->getLoggedChannelsList();

        if ( count( $chans ) )
        {
            list( $gChannel ) = each( $chans );
        }

        $channel_sk = new WuiSessionKey(
            'chat_channel',
            array(
                'value' => $gChannel
                )
            );
    }
}

$gAction_disp->addEvent(
    'sendmessage',
    'action_sendmessage'
    );
function action_sendmessage(
    $eventData
    )
{
    global $gChannel;

    $chat = new InnoworkChat(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );
    $chat->SendMessage(
        $gChannel,
        $eventData['message']
        );
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher( 'view' );


$gMain_disp->addEvent(
    'default',
    'main_default'
    );
function main_default( $eventData )
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gInnowork_core, $gChannel;

    $chat = new InnoworkChat(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );

    $channels_list = $chat->getChannelsList();

    $users_list = $chat->getChannelUsers(
        $gChannel
        );

    $gXml_def =
'<vertgroup>
  <children>

    <horizgroup>
      <children>

        <vertframe>
          <children>

            <form><name>channel</name>
              <args>
              </args>
              <children>

                <label>
                  <args>
                    <label type="encoded">'.urlencode( $gLocale->getStr( 'chan_list.label' ) ).'</label>
                  </args>
                </label>

                <listbox><name>channel</name>
                  <args>
                    <disp>action</disp>
                    <elements type="array">'.WuiXml::encode( $channels_list ).'</elements>
                    <size>5</size>
                  </args>
                </listbox>

              </children>
            </form>

            <button>
              <args>
                <themeimage>buttonok</themeimage>
                <themeimagetype>mini</themeimagetype>
                <horiz>true</horiz>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'enter_chan.button' ) ).'</label>
                <formsubmit>channel</formsubmit>
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
                                'join'
                                )
                            )
                        )
                    ).'</action>
              </args>
            </button>

            <horizbar/>

            <form><name>newchannel</name>
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
                                'join'
                                )
                            )
                        )
                    ).'</action>
              </args>
              <children>

                <string><name>channel</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                  </args>
                </string>

              </children>
            </form>

            <button>
              <args>
                <themeimage>buttonok</themeimage>
                <themeimagetype>mini</themeimagetype>
                <horiz>true</horiz>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'create_chan.button' ) ).'</label>
                <formsubmit>newchannel</formsubmit>
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
                                'join'
                                )
                            )
                        )
                    ).'</action>
              </args>
            </button>';

    if ( strlen( $gChannel ) )
    {
        $gXml_def .=
'   <horizbar/>

               <label>
                  <args>
                    <label type="encoded">'.urlencode( $gLocale->getStr( 'users_list.label' ) ).'</label>
                  </args>
                </label>

                <listbox><name>users</name>
                  <args>
                    <disp>action</disp>
                    <elements type="array">'.WuiXml::encode( $users_list ).'</elements>
                    <size>5</size>
                  </args>
                </listbox>';
    }

    $gXml_def .=
'<horizbar/>

            <button>
              <args>
                <themeimage>reload</themeimage>
                <themeimagetype>mini</themeimagetype>
                <horiz>true</horiz>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'refresh.button' ) ).'</label>
                <formsubmit>newchannel</formsubmit>
                <action type="encoded">'.urlencode(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'default'
                                )
                            )
                        )
                    ).'</action>
              </args>
            </button>
          </children>
        </vertframe>

            <vertframe>
              <children>';

    if ( strlen( $gChannel ) )
    {
        $gXml_def .=
'<label>
  <args>
    <bold>true</bold>
    <label type="encoded">'.urlencode( $gChannel ).'</label>
  </args>
</label>

<horizbar/>';
    }

    $gXml_def .=
'            <iframe><name>chat</name>
              <args>
                <width>500</width>
                <height>250</height>
                <source type="encoded">'.urlencode(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'chat',
                                array(
                                    'iframe' => '1'
                                    )
                                )
                            )
                        )
                    ).'</source>
              </args>
            </iframe>

  <horizbar/>

  <horizgroup>
    <children>';

    $chat = new InnoworkChat(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );

    $chans = $chat->getLoggedChannelsList();

    foreach ( $chans as $chan )
    {
        $gXml_def .=
'<button>
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
                                'join',
                                array(
                                    'channel' => $chan
                                    )
                                )
                            )
                        )
                    ).'</action>
                        <horiz>true</horiz>
                        <label type="encoded">'.urlencode( $chan ).'</label>';

    if ( $gChannel == $chan ) $gXml_def .= '<disabled>true</disabled>';

    $gXml_def .=
'                      </args>
</button>';
    }

    $gXml_def .=
'</children>
</horizgroup>

            <form><name>message</name>
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
                                'sendmessage'
                                )
                            )
                        )
                    ).'</action>

              </args>
              <children>

                <horizgroup>
                  <args>
                    <align>middle</align>
                  </args>
                  <children>

                    <string><name>message</name>
                      <args>
                        <size>60</size>
                        <disp>action</disp>
                        <taborder>1</taborder>
                      </args>
                    </string>

                    <button>
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
                                'sendmessage'
                                )
                            )
                        )
                    ).'</action>
                        <themeimage>buttonok</themeimage>
                        <horiz>true</horiz>
                        <label type="encoded">'.urlencode( $gLocale->getStr( 'send_message.button' ) ).'</label>
                        <formsubmit>message</formsubmit>
                        <themeimagetype>mini</themeimagetype>
                      </args>
                    </button>

                    <button>
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
                                'exitchan'
                                )
                            )
                        )
                    ).'</action>
                        <themeimage>fileclose</themeimage>
                        <themeimagetype>mini</themeimagetype>
                        <horiz>true</horiz>
                        <label type="encoded">'.urlencode( $gLocale->getStr( 'exit_chan.button' ) ).'</label>
                      </args>
                    </button>

                  </children>
                </horizgroup>

              </children>
            </form>

              </children>
            </vertframe>

      </children>
    </horizgroup>

  </children>
</vertgroup>';
}

$gMain_disp->addEvent(
    'chat',
    'main_chat'
    );
function main_chat(
    $eventData
    )
{
    global $gXml_def, $gChannel;

    $row = 0;

    if ( isset($gChannel ) )
    {
        $chat = new InnoworkChat(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
            );

        $messages = $chat->getChannelMessages(
            $gChannel
            );
    }
    else $messages = array();

    $gXml_def =
'<vertgroup>
  <children>

  <grid>
  <children>';

    foreach ( $messages as $message )
    {
        $user = $message['fromuser'];
        if ( strpos( $user, '@' ) )
        {
            $user = substr( $user, 0, strpos( $user, '@' ) );
        }

        $gXml_def .=
'<label row="'.$row.'" col="0" halign="" valign="top">
  <args>
    <label type="encoded">'.urlencode( $user ).': </label>
    <bold>'.( $message['fromuser'] == InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName() ? 'true' : 'false' ).'</bold>
    <compact>true</compact>
  </args>
</label>
<label row="'.$row.'" col="1" halign="" valign="top">
  <args>
    <label type="encoded">'.urlencode( $message['message'] ).'</label>
    <bold>'.( $message['fromuser'] == InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName() ? 'true' : 'false' ).'</bold>
    <compact>true</compact>
  </args>
</label>';

        $row++;
    }

    $gXml_def .=
'</children>
</grid>

  </children>
</vertgroup>';
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//
$main_event_data = $gMain_disp->getEventData();

if (
    isset($main_event_data['iframe'] )
    and
    $main_event_data['iframe'] == '1'
    )
{
    $gXml_def =
'<page>
  <args>
    <border>false</border>
    <refresh>5</refresh>
  </args>
  <children>
    <vertgroup>
      <children>'.$gXml_def;

    if ( strlen( $gPage_status ) )
        $gXml_def .=
'        <statusbar>
          <args>
            <status type="encoded">'.urlencode( $gPage_status ).'</status>
          </args>
        </statusbar>';

    $gXml_def .=
'      </children>
    </vertgroup>
  </children>
</page>';

    $gWui->addChild( new WuiXml( '', array( 'definition' => $gXml_def ) ) );
}
else
{
    $gWui->addChild( new WuiInnomaticPage( 'page', array(
        'pagetitle' => $gPage_title,
        'icon' => 'document',
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
}

$gWui->render();

?>