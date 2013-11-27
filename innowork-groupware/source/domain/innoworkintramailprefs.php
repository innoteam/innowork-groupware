<?php
// ----- Initialization -----
//

    global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gToolbars, $gXml_def;

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
    'innowork-groupware::intramail_prefs',
    InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage()
    );

$gWui = Wui::instance('wui');
$gWui->loadWidget( 'xml' );
$gWui->loadWidget( 'innomaticpage' );
$gWui->loadWidget( 'innomatictoolbar' );

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr( 'intramailprefs.title' );
$gCore_toolbars = $gInnowork_core->getMainToolBar();
$gToolbars['mail'] = array(
    'mailbox' => array(
        'label' => $gLocale->getStr( 'mailbox.toolbar' ),
        'themeimage' => 'mail_generic2',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( 'innoworkintramail', array( array(
            'view',
            'default',
            '' ) ) )
        )
    );
$gToolbars['settings'] = array(
    'settings' => array(
        'label' => $gLocale->getStr( 'settings.toolbar' ),
        'themeimage' => 'settings1',
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
    'setsmtp',
    'action_setsmtp'
    );
function action_setsmtp(
    $eventData
    )
{
    global $gLocale, $gPage_status;

   require_once('innomatic/domain/user/UserSettings.php');

    $sets = new UserSettings(
    	InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
    	InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId());
    $sets->setKey( 'innoworkgroupware-mailsmtpserver', $eventData['smtpserver'] );
    $sets->setKey( 'innoworkgroupware-mailsmtpport', $eventData['smtpport'] );

    $gPage_status = $gLocale->getStr( 'smtp_server_updated.status' );
}

$gAction_disp->addEvent(
    'new_account',
    'action_newaccount'
    );
function action_newaccount(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $pop_acc = new InnoworkIntraMailPopAccount();
    $pop_acc->Create(
        $eventData['name'],
        $eventData['hostname'],
        $eventData['port'],
        $eventData['username'],
        $eventData['password']
        );
    $pop_acc->setDeleteMessages(
        isset($eventData['deletemessages'] ) and $eventData['deletemessages'] == 'on' ? true : false
        );

    $gPage_status = $gLocale->getStr( 'account_created.status' );
}

$gAction_disp->addEvent(
    'remove_account',
    'action_removeaccount'
    );
function action_removeaccount(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $pop_acc = new InnoworkIntraMailPopAccount(
        $eventData['id']
        );
    $pop_acc->Remove();

    $gPage_status = $gLocale->getStr( 'account_removed.status' );
}

$gAction_disp->addEvent(
    'edit_account',
    'action_editaccount'
    );
function action_editaccount(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $pop_acc = new InnoworkIntraMailPopAccount(
        $eventData['id']
        );

    $pop_acc->setName( $eventData['name'] );
    $pop_acc->setUsername( $eventData['username'] );
    $pop_acc->setPassword( $eventData['password'] );
    $pop_acc->setHostname( $eventData['hostname'] );
    $pop_acc->setPort( $eventData['port'] );
    $pop_acc->setDeleteMessages(
        isset($eventData['deletemessages'] ) and $eventData['deletemessages'] == 'on' ?
        true :
        false
        );

    $gPage_status = $gLocale->getStr( 'account_updated.status' );
}
$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher( 'view' );

function tab_action_builder( $tab )
{
    return WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            array( 'tab' => $tab )
        ) ) );
}

$gMain_disp->addEvent(
    'default',
    'main_default' );
function main_default( $eventData )
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gToolbars;

    require_once('innomatic/domain/user/UserSettings.php');
    $sets = new UserSettings(
    	InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
    	InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId());
    $smtp_server = $sets->getKey( 'innoworkgroupware-mailsmtpserver');
    $smtp_port = $sets->getKey( 'innoworkgroupware-mailsmtpport');

    if ( !strlen( $smtp_server ) ) $smtp_server = 'localhost';
    if ( !strlen( $smtp_port ) ) $smtp_port = '25';

    $tabs[0]['label'] = $gLocale->getStr( 'smtp.tab' );
    $tabs[1]['label'] = $gLocale->getStr( 'accounts.tab' );

    $accounts_headers[0]['label'] = $gLocale->getStr( 'account_name.header' );

    $gXml_def =
'<vertgroup><name>settings</name>
  <children>

    <label>
      <args>
        <bold>true</bold>
        <label type="encoded">'.urlencode( $gLocale->getStr( 'settings.label' ) ).'</label>
      </args>
    </label>

    <tab><name>intramail</name>
      <args>
        <tabs type="array">'.WuiXml::encode( $tabs ).'</tabs>
        <tabactionfunction>tab_action_builder</tabactionfunction>
        <activetab>'.( isset($eventData['tab'] ) ? $eventData['tab'] : '' ).'</activetab>
      </args>
      <children>

        <vertgroup>
          <children>

    <label>
      <args>
        <bold>true</bold>
        <label type="encoded">'.urlencode( $gLocale->getStr( 'smtp.label' ) ).'</label>
      </args>
    </label>

        <form><name>smtp</name>
          <args>
            <action type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString(
                    '',
                    array(
                        array(
                            'view',
                            'default'
                            ),
                        array(
                            'action',
                            'setsmtp'
                            )
                        )
                ) ).'</action>
          </args>
          <children>

            <grid>
              <children>

                <label row="0" col="0">
                  <args>
                    <label type="encoded">'.urlencode( $gLocale->getStr( 'smtp_server.label' ) ).'</label>
                  </args>
                </label>

                <string row="0" col="1"><name>smtpserver</name>
                  <args>
                    <disp>action</disp>
                    <size>20</size>
                    <required>true</required>
                    <checkmessage type="encoded">'.urlencode( $gLocale->getStr( 'smtp_server.required' ) ).'</checkmessage>
                    <value type="encoded">'.urlencode( $smtp_server ).'</value>
                  </args>
                </string>

                <label row="1" col="0">
                  <args>
                    <label type="encoded">'.urlencode( $gLocale->getStr( 'smtp_port.label' ) ).'</label>
                  </args>
                </label>

                <string row="1" col="1"><name>smtpport</name>
                  <args>
                    <disp>action</disp>
                    <size>4</size>
                    <required>true</required>
                    <checkmessage type="encoded">'.urlencode( $gLocale->getStr( 'smtp_port.required' ) ).'</checkmessage>
                    <value type="encoded">'.urlencode( $smtp_port ).'</value>
                  </args>
                </string>

              </children>
            </grid>

          </children>
        </form>

        <horizbar/>

        <button>
          <args>
            <themeimage>buttonok</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <label type="encoded">'.urlencode( $gLocale->getStr( 'apply.button' ) ).'</label>
            <formsubmit>smtp</formsubmit>
            <action type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString(
                    '',
                    array(
                        array(
                            'view',
                            'default'
                            ),
                        array(
                            'action',
                            'setsmtp'
                            )
                        )
                ) ).'</action>
          </args>
        </button>

          </children>
        </vertgroup>

        <vertgroup>
          <children>

    <label>
      <args>
        <bold>true</bold>
        <label type="encoded">'.urlencode( $gLocale->getStr( 'accounts.label' ) ).'</label>
      </args>
    </label>

            <table><name>accounts</name>
              <args>
                <headers type="array">'.WuiXml::encode( $accounts_headers ).'</headers>
              </args>
              <children>';

    $accounts_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
        'SELECT * '.
        'FROM innowork_email_accounts '.
        'WHERE ownerid='.InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId().' '.
        'ORDER BY accountname'
        );

    $row = 0;
    while ( !$accounts_query->eof )
    {
        $gXml_def .=
'<label row="'.$row.'" col="0">
  <args>
    <label type="encoded">'.urlencode( $accounts_query->getFields( 'accountname' ) ).'</label>
  </args>
</label>
<innomatictoolbar row="'.$row.'" col="1">
  <args>
    <frame>false</frame>
    <toolbars type="array">'.WuiXml::encode( array(
        'view' => array(
            'show' => array(
                'label' => $gLocale->getStr( 'edit_account.button' ),
                'themeimage' => 'pencil',
                'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array( array(
                    'view',
                    'edit_account',
                    array( 'id' => $accounts_query->getFields( 'id' ) ) ) ) )
                ),
            'remove' => array(
                'label' => $gLocale->getStr( 'remove_account.button' ),
                'themeimage' => 'trash',
                'horiz' => 'true',
                'needconfirm' => 'true',
                'confirmmessage' => $gLocale->getStr( 'remove_account.confirm' ),
                'action' => WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default'
                    ),
                    array(
                        'action',
                        'remove_account',
                        array(
                            'id' => $accounts_query->getFields( 'id' )
                            ) ) ) )
        ) ) ) ).'</toolbars>
  </args>
</innomatictoolbar>';
        $row++;
        $accounts_query->moveNext();
    }

    $gXml_def .=
'              </children>
            </table>

            <horizbar/>

            <button>
              <args>
                <horiz>true</horiz>
                <frame>false</frame>
                <themeimage>filenew</themeimage>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'new_account.button' ) ).'</label>
                <action type="encoded">'.urlencode(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'new_account'
                                )
                            )
                        ) ).'</action>
              </args>
            </button>

          </children>
        </vertgroup>

      </children>
    </tab>
  </children>
</vertgroup>';
}

$gMain_disp->addEvent(
    'new_account',
    'main_newaccount'
    );
function main_newaccount(
    $eventData
    )
{
    global $gLocale, $gXml_def;

    $gXml_def =
'<vertgroup>
  <children>
    <form><name>account</name>
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
                        'new_account'
                        )
                    )
                )
            ).'</action>
      </args>
      <children>

        <vertgroup>
          <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'account_name.label' ) ).'</label>
              </args>
            </label>

            <string row="0" col="1"><name>name</name>
              <args>
                <disp>action</disp>
                <size>15</size>
              </args>
            </string>

            <label row="1" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'account_username.label' ) ).'</label>
              </args>
            </label>

            <string row="1" col="1"><name>username</name>
              <args>
                <disp>action</disp>
                <size>15</size>
              </args>
            </string>

            <label row="1" col="2">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'account_password.label' ) ).'</label>
              </args>
            </label>

            <string row="1" col="3"><name>password</name>
              <args>
                <password>true</password>
                <disp>action</disp>
                <size>15</size>
              </args>
            </string>

            <label row="2" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'account_hostname.label' ) ).'</label>
              </args>
            </label>

            <string row="2" col="1"><name>hostname</name>
              <args>
                <disp>action</disp>
                <size>15</size>
              </args>
            </string>

            <label row="2" col="2">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'account_port.label' ) ).'</label>
              </args>
            </label>

            <string row="2" col="3"><name>port</name>
              <args>
                <disp>action</disp>
                <size>3</size>
                <value>110</value>
              </args>
            </string>

          </children>
        </grid>

        <horizbar/>

        <grid>
          <children>

            <checkbox row="0" col="0"><name>deletemessages</name>
              <args>
                <disp>action</disp>
                <checked>true</checked>
              </args>
            </checkbox>

            <label row="0" col="1">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'delete_messages.label' ) ).'</label>
              </args>
            </label>

          </children>
        </grid>

          </children>
        </vertgroup>

      </children>
    </form>

    <horizbar/>

    <button>
      <args>
        <horiz>true</horiz>
        <frame>false</frame>
        <themeimage>buttonok</themeimage>
        <label type="encoded">'.urlencode( $gLocale->getStr( 'create_account.button' ) ).'</label>
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
                        'new_account'
                        )
                    )
                )
            ).'</action>
        <formsubmit>account</formsubmit>
      </args>
    </button>

  </children>
</vertgroup>';
}

$gMain_disp->addEvent(
    'edit_account',
    'main_editaccount'
    );
function main_editaccount(
    $eventData
    )
{
    global $gLocale, $gXml_def;

    $pop_acc = new InnoworkIntraMailPopAccount( $eventData['id'] );

    $gXml_def =
'<vertgroup>
  <children>
    <form><name>account</name>
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
                        'edit_account',
                        array(
                            'id' => $eventData['id']
                            )
                        )
                    )
                )
            ).'</action>
      </args>
      <children>

        <vertgroup>
          <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'account_name.label' ) ).'</label>
              </args>
            </label>

            <string row="0" col="1"><name>name</name>
              <args>
                <disp>action</disp>
                <size>15</size>
                <value type="encoded">'.urlencode( $pop_acc->getName() ).'</value>
              </args>
            </string>

            <label row="1" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'account_username.label' ) ).'</label>
              </args>
            </label>

            <string row="1" col="1"><name>username</name>
              <args>
                <disp>action</disp>
                <size>15</size>
                <value type="encoded">'.urlencode( $pop_acc->getUsername() ).'</value>
              </args>
            </string>

            <label row="1" col="2">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'account_password.label' ) ).'</label>
              </args>
            </label>

            <string row="1" col="3"><name>password</name>
              <args>
                <password>true</password>
                <disp>action</disp>
                <size>15</size>
                <value type="encoded">'.urlencode( $pop_acc->getPassword() ).'</value>
              </args>
            </string>

            <label row="2" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'account_hostname.label' ) ).'</label>
              </args>
            </label>

            <string row="2" col="1"><name>hostname</name>
              <args>
                <disp>action</disp>
                <size>15</size>
                <value type="encoded">'.urlencode( $pop_acc->getHostname() ).'</value>
              </args>
            </string>

            <label row="2" col="2">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'account_port.label' ) ).'</label>
              </args>
            </label>

            <string row="2" col="3"><name>port</name>
              <args>
                <disp>action</disp>
                <size>3</size>
                <value type="encoded">'.urlencode( $pop_acc->getPort() ).'</value>
              </args>
            </string>

          </children>
        </grid>

        <horizbar/>

        <grid>
          <children>

            <checkbox row="0" col="0"><name>deletemessages</name>
              <args>
                <disp>action</disp>
                <checked>'.( $pop_acc->getDeleteMessages() ? 'true' : 'false' ).'</checked>
              </args>
            </checkbox>

            <label row="0" col="1">
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'delete_messages.label' ) ).'</label>
              </args>
            </label>

          </children>
        </grid>

          </children>
        </vertgroup>

      </children>
    </form>

    <horizbar/>

    <button>
      <args>
        <horiz>true</horiz>
        <frame>false</frame>
        <themeimage>buttonok</themeimage>
        <label type="encoded">'.urlencode( $gLocale->getStr( 'edit_account.button' ) ).'</label>
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
                        'edit_account',
                        array(
                            'id' => $eventData['id']
                            )
                        )
                    )
                )
            ).'</action>
        <formsubmit>account</formsubmit>
      </args>
    </button>

  </children>
</vertgroup>';
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//
$gWui->addChild( new WuiInnomaticPage( 'page', array(
    'pagetitle' => $gPage_title,
    'icon' => 'settings1',
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
