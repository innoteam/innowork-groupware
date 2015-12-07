<?php

// ----- Initialization -----
//

require_once('innowork/groupware/InnoworkCompany.php');
require_once('innowork/groupware/InnoworkContact.php');
require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/widgets/WuiWidget.php');
require_once('innomatic/wui/widgets/WuiContainerWidget.php');
require_once('innomatic/wui/dispatch/WuiEventsCall.php');
require_once('innomatic/wui/dispatch/WuiEvent.php');
require_once('innomatic/wui/dispatch/WuiEventRawData.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('innomatic/locale/LocaleCatalog.php'); require_once('innomatic/locale/LocaleCountry.php'); 

    global $gLocale, $gPage_status;
    global $gXml_def, $gLocale, $gPage_title;
    global $gXml_def, $gLocale, $gPage_title, $gToolbars, $gInnowork_core, $gUsers;
        global $gMain_disp;
    
require_once('innowork/core/InnoworkCore.php');
$gInnowork_core = InnoworkCore::instance('innoworkcore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
$gLocale = new LocaleCatalog('innowork-groupware::directory_main', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());
$users_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT username,lname,fname FROM domain_users WHERE username<>'.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText(User::getAdminUsername(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId())).' ORDER BY lname,fname');
$gUsers[''] = $gLocale->getStr('no_account_manager.label');

while (!$users_query->eof) {
    $gUsers[$users_query->getFields('username')] = $users_query->getFields('lname').' '.$users_query->getFields('fname');
    $users_query->MoveNext();
}

$users_query->Free();

$gWui = Wui::instance('wui');
$gWui->LoadWidget('xml');
$gWui->LoadWidget('innomaticpage');
$gWui->LoadWidget('innomatictoolbar');

$disp_type = '';
if (isset(Wui::instance('wui')->parameters['wui']['view']['evn'])) {
    switch (Wui::instance('wui')->parameters['wui']['view']['evn']) {
        case 'showcontact' :
            $disp_type = 'directorycontact';
            break;
        case 'showcompany' :
            $disp_type = 'directorycompany';
            break;
    }
}

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr('directory.title');
$gCore_toolbars = $gInnowork_core->GetMainToolBar('', $disp_type, isset(Wui::instance('wui')->parameters['wui']['view']['evd']['id']) ? Wui::instance('wui')->parameters['wui']['view']['evd']['id'] : '');

$gToolbars['contacts'] = array('contacts' => array('label' => $gLocale->getStr('contacts.toolbar'), 'themeimage' => 'listdetailed', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', '')))), 'newcontact' => array('label' => $gLocale->getStr('newcontact.toolbar'), 'themeimage' => 'filenew', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'newcontact', '')))));

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher('action');

$gAction_disp->addEvent('newcontact', 'action_newcontact');
function action_newcontact($eventData) {
    global $gLocale, $gPage_status;

    $innowork_contact = new InnoworkContact(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

    if ($innowork_contact->Create($eventData, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId())) {
        $GLOBALS['innowork-groupware']['newcontactid'] = $innowork_contact->mItemId;

        $gPage_status = $gLocale->getStr('contact_added.status');
    }
    else
        $gPage_status = $gLocale->getStr('contact_not_added.status');
}

$gAction_disp->addEvent('editcontact', 'action_editcontact');
function action_editcontact($eventData) {
    global $gLocale, $gPage_status;

    $innowork_contact = new InnoworkContact(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($innowork_contact->Edit($eventData, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()))
        $gPage_status = $gLocale->getStr('contact_updated.status');
    else
        $gPage_status = $gLocale->getStr('contact_not_updated.status');
}

$gAction_disp->addEvent('removecontact', 'action_removecontact');
function action_removecontact($eventData) {
    global $gLocale, $gPage_status;

    $innowork_contact = new InnoworkContact(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($innowork_contact->trash(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()))
        $gPage_status = $gLocale->getStr('contact_removed.status');
    else
        $gPage_status = $gLocale->getStr('contact_not_removed.status');
}

$gAction_disp->addEvent('newnote', 'action_newnote');
function action_newnote($eventData) {
    global $gPage_status, $gLocale;

    if ($eventData['itemtype'] == InnoworkCompany::NOTE_ITEM_TYPE) {
        $item = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['itemid']);
    }
    else {
        $item = new InnoworkContact(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['itemid']);
    }

    if ($item->AddNote(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName(), $eventData['content'])) {
        $gPage_status = $gLocale->getStr('note_created.status');
    }
    else
        $gPage_status = $gLocale->getStr('note_not_created.status');
}

$gAction_disp->addEvent('removenote', 'action_removenote');
function action_removenote($eventData) {
    global $gPage_status, $gLocale;

    if ($eventData['itemtype'] == InnoworkCompany::NOTE_ITEM_TYPE) {
        $item = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['itemid']);
    }
    else {
        $item = new InnoworkContact(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['itemid']);
    }

    if ($item->RemoveNote($eventData['noteid']))
        $gPage_status = $gLocale->getStr('note_removed.status');
    else
        $gPage_status = $gLocale->getStr('note_not_removed.status');
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher('view');

function contacts_tab_action_builder($tab) {
    return WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('tabpage' => $tab))));
}

$gMain_disp->addEvent('default', 'main_default');
function main_default($eventData) {
    global $gXml_def, $gLocale, $gPage_title;

    require_once('shared/wui/WuiSessionkey.php');
    
    $headers[0]['label'] = $gLocale->getStr('lastname.header');
    $headers[1]['label'] = $gLocale->getStr('firstname.header');

    $contacts = new InnoworkContact(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
    $search_results = $contacts->Search('', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());

    if (isset($eventData['viewmode'])) {
        $viewmode_sk = new WuiSessionKey('viewmode_pers', array('value' => $eventData['viewmode']));
    }
    else {
        $viewmode_sk = new WuiSessionKey('viewmode_pers');
        $eventData['viewmode'] = $viewmode_sk->mValue;
    }
    if (!strlen($eventData['viewmode'])) $eventData['viewmode'] = 'compact';

    $gXml_def = '
    <vertgroup><name>contacts</name>
      <children>
    
        <label><name>title</name>
          <args>
            <bold>true</bold>
            <label type="encoded">'.urlencode($gLocale->getStr('contacts.label')).'</label>
          </args>
        </label>
    
        
        <horizbar/>

        <horizgroup>
          <children>
            <label>
              <args>
                <label>'.$gLocale->getStr('view.label').'</label>
              </args>
            </label>
            <button>
              <args>
                <label>'.$gLocale->getStr('view_compact.button').'</label>
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString(
                    '',
                    array(
                        array(
                            'view',
                            'default',
                            array(
                                'viewmode' => 'compact'
                                )
                        )
                    )
                    )).'</action>
                <themeimage>listdetailed</themeimage>
                <disabled>'.($eventData['viewmode']=='compact' ? 'true' : 'false').'</disabled>
                <compact>true</compact>
                <horiz>true</horiz>
              </args>
            </button>
            <button>
              <args>
                <label>'.$gLocale->getStr('view_detailed.button').'</label>
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString(
                    '',
                    array(
                        array(
                            'view',
                            'default',
                            array(
                                'viewmode' => 'detailed'
                                )
                        )
                    )
                    )).'</action>
                <themeimage>listdetailed2</themeimage>
                <disabled>'.($eventData['viewmode']=='detailed' ? 'true' : 'false').'</disabled>
                <compact>true</compact>
                <horiz>true</horiz>
              </args>
            </button>
            <button>
              <args>
                <label>'.$gLocale->getStr('view_list.button').'</label>
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString(
                    '',
                    array(
                        array(
                            'view',
                            'default',
                            array(
                                'viewmode' => 'list'
                                )
                        )
                    )
                    )).'</action>
                <themeimage>listbulletleft</themeimage>
                <disabled>'.($eventData['viewmode']=='list' ? 'true' : 'false').'</disabled>
                <compact>true</compact>
                <horiz>true</horiz>
              </args>
            </button>
          </children>
        </horizgroup>

        <innoworkcontactslist><name>contacts</name>
          <args>
            <contacts type="array">'.WuiXml::encode($search_results).'</contacts>
            <type>contacts</type>
            <viewmode>'.$eventData['viewmode'].'</viewmode>
            <tabactionfunction>contacts_tab_action_builder</tabactionfunction>
            <activetab>'. (isset($eventData['tabpage']) ? $eventData['tabpage'] : '').'</activetab>
          </args>
        </innoworkcontactslist>
    
      </children>
    </vertgroup>';
}

$gMain_disp->addEvent('newcontact', 'main_newcontact');
function main_newcontact($eventData) {
    global $gXml_def, $gLocale, $gPage_title, $gUsers;

    $innowork_companies = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
    $search_results = $innowork_companies->Search('', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());

    $companies['0'] = $gLocale->getStr('nocompany.label');

    while (list ($id, $fields) = each($search_results)) {
        $companies[$id] = $fields['companyname'];
    }

    $gXml_def.= '
    <vertgroup><name>newcontact</name>
      <children>
    
        <table><name>contact</name>
          <args>
            <headers type="array">'.WuiXml::encode(array('0' => array('label' => $gLocale->getStr('newcontact.label')))).'</headers>
          </args>
          <children>
        
        <form row="0" col="0"><name>contact</name>
          <args>
            <method>post</method>
            <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'showcontact', ''), array('action', 'newcontact', '')))).'</action>
          </args>
          <children>
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>title</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('title.label')).'</label>
                  </args>
                </label>
                <string><name>title</name>
                  <args>
                    <disp>action</disp>
                    <size>5</size>
                  </args>
                </string>
                <label><name>firstname</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('firstname.label')).'</label>
                  </args>
                </label>
                <string><name>firstname</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                  </args>
                </string>
                <label><name>lastname</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('lastname.label')).'</label>
                  </args>
                </label>
                <string><name>lastname</name>
                  <args>
                    <disp>action</disp>
                    <size>25</size>
                  </args>
                </string>
                <label><name>nickname</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('nickname.label')).'</label>
                  </args>
                </label>
                <string><name>nickname</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizgroup>
              <children>
    
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('accountmanager.label')).'</label>
                  </args>
                </label>
                <combobox><name>accountmanager</name>
                  <args>
                    <disp>action</disp>
                    <elements type="array">'.WuiXml::encode($gUsers).'</elements>
                  </args>
                </combobox>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>
    
            <label><name>job</name>
              <args>
                <bold>true</bold>
                <label type="encoded">'.urlencode($gLocale->getStr('job.label')).'</label>
              </args>
            </label>
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>company</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('company.label')).'</label>
                  </args>
                </label>
                <combobox><name>companyid</name>
                  <args>
                    <disp>action</disp>
                    <elements type="array">'.WuiXml::encode($companies).'</elements>
                  </args>
                </combobox>
    
              </children>
            </horizgroup>
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>jobtitle</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('jobtitle.label')).'</label>
                  </args>
                </label>
                <string><name>jobtitle</name>
                  <args>
                    <disp>action</disp>
                    <size>25</size>
                  </args>
                </string>
                <label><name>jobdescription</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('jobdescription.label')).'</label>
                  </args>
                </label>
                <string><name>jobdescription</name>
                  <args>
                    <disp>action</disp>
                    <size>30</size>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <label><name>address</name>
              <args>
                <bold>true</bold>
                <label type="encoded">'.urlencode($gLocale->getStr('address.label')).'</label>
              </args>
            </label>
    
            <horizgroup><name>company</name>
              <children>
    
                <label><name>street</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('street.label')).'</label>
                  </args>
                </label>
                <string><name>street</name>
                  <args>
                    <disp>action</disp>
                    <size>25</size>
                  </args>
                </string>
                <label><name>city</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('city.label')).'</label>
                  </args>
                </label>
                <string><name>city</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>zip</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('zip.label')).'</label>
                  </args>
                </label>
                <string><name>zip</name>
                  <args>
                    <disp>action</disp>
                    <size>5</size>
                  </args>
                </string>
                <label><name>state</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('state.label')).'</label>
                  </args>
                </label>
                <string><name>state</name>
                  <args>
                    <disp>action</disp>
                    <size>2</size>
                  </args>
                </string>
                <label><name>country</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('country.label')).'</label>
                  </args>
                </label>
                <string><name>country</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>
    
            <label><name>contact</name>
              <args>
                <bold>true</bold>
                <label type="encoded">'.urlencode($gLocale->getStr('contact.label')).'</label>
              </args>
            </label>
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>email</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('email.label')).'</label>
                  </args>
                </label>
                <string><name>email</name>
                  <args>
                    <disp>action</disp>
                    <size>30</size>
                  </args>
                </string>
                <label><name>website</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('website.label')).'</label>
                  </args>
                </label>
                <string><name>url</name>
                  <args>
                    <disp>action</disp>
                    <size>30</size>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>homephone</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('homephone.label')).'</label>
                  </args>
                </label>
                <string><name>homephone</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                  </args>
                </string>
                <label><name>mobile</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('mobile.label')).'</label>
                  </args>
                </label>
                <string><name>mobile</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                  </args>
                </string>
                <label><name>officephone</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('officephone.label')).'</label>
                  </args>
                </label>
                <string><name>phone</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                  </args>
                </string>
                <label><name>fax</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('fax.label')).'</label>
                  </args>
                </label>
                <string><name>fax</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>
    
            <horizgroup>
              <children>
    
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('fiscalcodeb.label')).'</label>
                  </args>
                </label>
                <string><name>fiscalcode</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>

            <label><name>notes</name>
              <args>
                <bold>true</bold>
                <label type="encoded">'.urlencode($gLocale->getStr('notes.label')).'</label>
              </args>
            </label>
    
            <horizgroup><name>contact</name>
              <children>
    
                <text><name>notes</name>
                  <args>
                    <disp>action</disp>
                    <cols>80</cols>
                    <rows>7</rows>
                  </args>
                </text>
    
              </children>
            </horizgroup>
    
            </children>
            </form>
    
            <button row="1" col="0"><name>apply</name>
              <args>
                <themeimage>buttonok</themeimage>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'showcontact', ''), array('action', 'newcontact', '')))).'</action>
                <label type="encoded">'.urlencode($gLocale->getStr('newcontact.submit')).'</label>
                <formsubmit>contact</formsubmit>
              </args>
            </button>
    
          </children>
        </table>
      </children>
    </vertgroup>';
}

function contact_extras_tab_builder($tab) {
    global $gMain_disp;
    $ev_data = $gMain_disp->GetEventData();

    return WuiEventsCall::buildEventsCallString('', array(array('view', 'showcontact', array('id' => $ev_data['id'], 'extrastab' => $tab))));
}

$gMain_disp->addEvent('showcontact', 'main_showcontact');
function main_showcontact($eventData) {
    global $gXml_def, $gLocale, $gPage_title, $gUsers;

    if (isset($GLOBALS['innowork-groupware']['newcontactid'])) {
        $eventData['id'] = $GLOBALS['innowork-groupware']['newcontactid'];
    }

    $innowork_contact = new InnoworkContact(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    $ps_data = $innowork_contact->GetItem(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());

    switch (strtolower(substr($ps_data['lastname'], 0, 1))) {
        case 'a' :
        case 'b' :
        case 'c' :
            $tab_page = 0;
            break;

        case 'd' :
        case 'e' :
        case 'f' :
            $tab_page = 1;
            break;

        case 'g' :
        case 'h' :
        case 'i' :
            $tab_page = 2;
            break;

        case 'j' :
        case 'k' :
        case 'l' :
            $tab_page = 3;
            break;

        case 'm' :
        case 'n' :
        case 'o' :
            $tab_page = 4;
            break;

        case 'p' :
        case 'q' :
        case 'r' :
            $tab_page = 5;
            break;

        case 's' :
        case 't' :
        case 'u' :
            $tab_page = 6;
            break;

        case 'v' :
        case 'w' :
        case 'x' :
        case 'y' :
        case 'z' :
            $tab_page = 7;
            break;

        default :
            $tab_page = 8;
    }

    $tabs[0]['label'] = $gLocale->getStr('extras_other.tab');
    $tabs[1]['label'] = $gLocale->getStr('extras_note.tab');

    $innowork_companies = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
    $search_results = $innowork_companies->Search('', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());

    $companies['0'] = $gLocale->getStr('nocompany.label');

    while (list ($id, $fields) = each($search_results)) {
        $companies[$id] = $fields['companyname'];
    }

    $gXml_def.= '
    <horizgroup><name>contact</name>
      <children>
    <vertgroup><name>contact</name>
      <children>
    
        <table><name>contact</name>
          <args>
            <headers type="array">'.WuiXml::encode(array('0' => array('label' => $gLocale->getStr('contact.label')))).'</headers>
          </args>
          <children>
    
        <form row="0" col="0"><name>contact</name>
          <args>
            <method>post</method>
            <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'showcontact', array('id' => $eventData['id'])), array('action', 'editcontact', array('id' => $eventData['id']))))).'</action>
          </args>
          <children>
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>title</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('title.label')).'</label>
                  </args>
                </label>
                <string><name>title</name>
                  <args>
                    <disp>action</disp>
                    <size>5</size>
                    <value type="encoded">'.urlencode($ps_data['title']).'</value>
                  </args>
                </string>
                <label><name>firstname</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('firstname.label')).'</label>
                  </args>
                </label>
                <string><name>firstname</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode($ps_data['firstname']).'</value>
                  </args>
                </string>
                <label><name>lastname</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('lastname.label')).'</label>
                  </args>
                </label>
                <string><name>lastname</name>
                  <args>
                    <disp>action</disp>
                    <size>25</size>
                    <value type="encoded">'.urlencode($ps_data['lastname']).'</value>
                  </args>
                </string>
                <label><name>nickname</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('nickname.label')).'</label>
                  </args>
                </label>
                <string><name>nickname</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                    <value type="encoded">'.urlencode($ps_data['nickname']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizgroup>
              <children>
    
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('accountmanager.label')).'</label>
                  </args>
                </label>
                <combobox><name>accountmanager</name>
                  <args>
                    <disp>action</disp>
                    <elements type="array">'.WuiXml::encode($gUsers).'</elements>
                    <default type="encoded">'.$ps_data['accountmanager'].'</default>
                  </args>
                </combobox>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>
    
            <label><name>job</name>
              <args>
                <bold>true</bold>
                <label type="encoded">'.urlencode($gLocale->getStr('job.label')).'</label>
              </args>
            </label>
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>company</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('company.label')).'</label>
                  </args>
                </label>
                <combobox><name>companyid</name>
                  <args>
                    <disp>action</disp>
                    <elements type="array">'.WuiXml::encode($companies).'</elements>
                    <default>'.$ps_data['companyid'].'</default>
                  </args>
                </combobox>
    
              </children>
            </horizgroup>
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>jobtitle</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('jobtitle.label')).'</label>
                  </args>
                </label>
                <string><name>jobtitle</name>
                  <args>
                    <disp>action</disp>
                    <size>25</size>
                    <value type="encoded">'.urlencode($ps_data['jobtitle']).'</value>
                  </args>
                </string>
                <label><name>jobdescription</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('jobdescription.label')).'</label>
                  </args>
                </label>
                <string><name>jobdescription</name>
                  <args>
                    <disp>action</disp>
                    <size>30</size>
                    <value type="encoded">'.urlencode($ps_data['jobdescription']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <label><name>address</name>
              <args>
                <bold>true</bold>
                <label type="encoded">'.urlencode($gLocale->getStr('address.label')).'</label>
              </args>
            </label>
    
            <horizgroup><name>company</name>
              <children>
    
                <label><name>street</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('street.label')).'</label>
                  </args>
                </label>
                <string><name>street</name>
                  <args>
                    <disp>action</disp>
                    <size>25</size>
                    <value type="encoded">'.urlencode($ps_data['street']).'</value>
                  </args>
                </string>
                <label><name>city</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('city.label')).'</label>
                  </args>
                </label>
                <string><name>city</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode($ps_data['city']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>zip</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('zip.label')).'</label>
                  </args>
                </label>
                <string><name>zip</name>
                  <args>
                    <disp>action</disp>
                    <size>5</size>
                    <value type="encoded">'.urlencode($ps_data['zip']).'</value>
                  </args>
                </string>
                <label><name>state</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('state.label')).'</label>
                  </args>
                </label>
                <string><name>state</name>
                  <args>
                    <disp>action</disp>
                    <size>2</size>
                    <value type="encoded">'.urlencode($ps_data['state']).'</value>
                  </args>
                </string>
                <label><name>country</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('country.label')).'</label>
                  </args>
                </label>
                <string><name>country</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode($ps_data['country']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>
    
            <label><name>contact</name>
              <args>
                <bold>true</bold>
                <label type="encoded">'.urlencode($gLocale->getStr('contact.label')).'</label>
              </args>
            </label>
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>email</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('email.label')).'</label>
                  </args>
                </label>
                <string><name>email</name>
                  <args>
                    <disp>action</disp>
                    <size>30</size>
                    <value type="encoded">'.urlencode($ps_data['email']).'</value>
                  </args>
                </string>
                <label><name>website</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('website.label')).'</label>
                  </args>
                </label>
                <string><name>url</name>
                  <args>
                    <disp>action</disp>
                    <size>30</size>
                    <value type="encoded">'.urlencode($ps_data['url']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizgroup><name>contact</name>
              <children>
    
                <label><name>homephone</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('homephone.label')).'</label>
                  </args>
                </label>
                <string><name>homephone</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                    <value type="encoded">'.urlencode($ps_data['homephone']).'</value>
                  </args>
                </string>
                <label><name>mobile</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('mobile.label')).'</label>
                  </args>
                </label>
                <string><name>mobile</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                    <value type="encoded">'.urlencode($ps_data['mobile']).'</value>
                  </args>
                </string>
                <label><name>officephone</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('officephone.label')).'</label>
                  </args>
                </label>
                <string><name>phone</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                    <value type="encoded">'.urlencode($ps_data['phone']).'</value>
                  </args>
                </string>
                <label><name>fax</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('fax.label')).'</label>
                  </args>
                </label>
                <string><name>fax</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                    <value type="encoded">'.urlencode($ps_data['fax']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>
    
            <horizgroup>
              <children>
    
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('fiscalcodeb.label')).'</label>
                  </args>
                </label>
                <string><name>fiscalcode</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode($ps_data['fiscalcode']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>

            <horizgroup><name>contact</name>
              <children>
    
                <tab><name>extras</name>
                  <args>
                    <tabs type="array">'.WuiXml::encode($tabs).'</tabs>
                    <tabactionfunction>contact_extras_tab_builder</tabactionfunction>
                    <activetab>'. (isset($eventData['extrastab']) ? $eventData['extrastab'] : '').'</activetab>
                  </args>
                  <children>
    
                    <vertgroup>
                      <children>
            <label>
              <args>
                <bold>true</bold>
                <label type="encoded">'.urlencode($gLocale->getStr('other.label')).'</label>
              </args>
            </label>
    
                <text><name>notes</name>
                  <args>
                    <disp>action</disp>
                    <cols>80</cols>
                    <rows>7</rows>
                    <value type="encoded">'.urlencode($ps_data['notes']).'</value>
                  </args>
                </text>
                      </children>
                    </vertgroup>
    
                    <vertgroup>
                      <children>
            <label>
              <args>
                <bold>true</bold>
                <label type="encoded">'.urlencode($gLocale->getStr('notes.label')).'</label>
              </args>
            </label>
    
            <iframe row="2" col="0"><name>notes</name>
              <args>
                <width>450</width>
                <height>200</height>
                <source type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'itemnotes', array('itemid' => $eventData['id'], 'itemtype' => InnoworkContact::NOTE_ITEM_TYPE))))).'</source>
                <scrolling>auto</scrolling>
              </args>
            </iframe>
    
            <horizbar/>
    
                <button>
                  <args>
                    <themeimage>attach</themeimage>
                    <label type="encoded">'.urlencode($gLocale->getStr('new_note.button')).'</label>
                    <frame>false</frame>
                    <horiz>true</horiz>
                    <target>notes</target>
                    <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'addnote', array('itemid' => $eventData['id'], 'itemtype' => InnoworkContact::NOTE_ITEM_TYPE))))).'</action>
                  </args>
                </button>
    
                      </children>
                    </vertgroup>
    
                  </children>
                </tab>
    
    
              </children>
            </horizgroup>
    
            </children>
            </form>
    
            <horizgroup row="1" col="0"><name>actions</name>
            <children>
    
            <button><name>apply</name>
              <args>
                <themeimage>buttonok</themeimage>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'showcontact', array('id' => $eventData['id'])), array('action', 'editcontact', array('id' => $eventData['id']))))).'</action>
                <label type="encoded">'.urlencode($gLocale->getStr('editcontact.submit')).'</label>
                <formsubmit>contact</formsubmit>
              </args>
            </button>
    
            <button><name>apply</name>
              <args>
                <themeimage>fileclose</themeimage>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('tabpage' => $tab_page))))).'</action>
                <label type="encoded">'.urlencode($gLocale->getStr('close.button')).'</label>
              </args>
            </button>
    
            <button><name>remove</name>
              <args>
                <themeimage>trash</themeimage>
                <horiz>true</horiz>
                <frame>false</frame>
                <label type="encoded">'.urlencode($gLocale->getStr('removecontact.button')).'</label>
                <needconfirm>true</needconfirm>
                <confirmmessage type="encoded">'.urlencode(sprintf($gLocale->getStr('removecontact.confirm'), $ps_data['lastname'].' '.$ps_data['firstname'])).'</confirmmessage>
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('tabpage' => $tab_page)), array('action', 'removecontact', array('id' => $eventData['id']))))).'</action>
                </args>
              </button>
    
              </children>
              </horizgroup>
    
          </children>
        </table>
      </children>
    </vertgroup>
    
      <innoworkitemacl><name>itemacl</name>
        <args>
          <itemtype>directorycontact</itemtype>
          <itemid>'.$eventData['id'].'</itemid>
          <itemownerid>'.$ps_data['ownerid'].'</itemownerid>
          <defaultaction type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'showcontact', array('id' => $eventData['id']))))).'</defaultaction>
        </args>
      </innoworkitemacl>
    
      </children>
    </horizgroup>';
}

$gMain_disp->addEvent('itemnotes', 'main_itemnotes');
function main_itemnotes($eventData) {
    global $gXml_def, $gLocale;

    if ($eventData['itemtype'] == InnoworkCompany::NOTE_ITEM_TYPE) {
        $item = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['itemid']);
    }
    else {
        $item = new InnoworkContact(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['itemid']);
    }

    $notes = $item->GetNotes();

    $headers[0]['label'] = $gLocale->getStr('date.header');
    $headers[1]['label'] = $gLocale->getStr('note.header');

    $gXml_def = '
    <page>
      <args>
        <border>false</border>
      </args>
      <children>
    <table><name>itemnotes</name>
      <args>
        <headers type="array">'.WuiXml::encode($headers).'</headers>
      </args>
      <children>';

    $row = 0;

    $country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

    foreach ($notes as $note) {
        $gXml_def.= '<vertgroup row="'.$row.'" col="0" halign="" valign="top">
          <args>
          </args>
          <children>
            <label>
              <args>
                <label type="encoded">'.urlencode($country->FormatShortArrayDate($note['creationdate'])).'</label>
                <compact>true</compact>
              </args>
            </label>
            <label>
              <args>
                <label type="encoded">'.urlencode($country->FormatArrayTime($note['creationdate'])).'</label>
                <compact>true</compact>
              </args>
            </label>
            <label>
              <args>
                <label type="encoded">'.urlencode('('.$note['username'].')').'</label>
                <compact>true</compact>
              </args>
            </label>
          </children>
        </vertgroup>
        <vertgroup row="'.$row.'" col="1" halign="" valign="top">
          <children>
        <label>
          <args>
            <label type="encoded">'.urlencode(nl2br($note['content'])).'</label>
            <nowrap>false</nowrap>
          </args>
        </label>
        
          <button>
            <args>
              <horiz>true</horiz>
              <frame>false</frame>
              <themeimage>editdelete</themeimage>
              <themeimagetype>mini</themeimagetype>
              <label type="encoded">'.urlencode($gLocale->getStr('remove_note.button')).'</label>
              <needconfirm>true</needconfirm>
              <confirmmessage type="encoded">'.urlencode($gLocale->getStr('remove_note.confirm')).'</confirmmessage>
              <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'itemnotes', array('itemid' => $eventData['itemid'], 'itemtype' => $eventData['itemtype'])), array('action', 'removenote', array('itemid' => $eventData['itemid'], 'itemtype' => $eventData['itemtype'], 'noteid' => $note['id']))))).'</action>
            </args>
          </button>
        
          </children>
        </vertgroup>';
        $row ++;
    }

    $gXml_def.= '  </children>
    </table>
      </children>
    </page>';

    $wui = new WuiXml('', array('definition' => $gXml_def));
    $wui->Build(new WuiDispatcher('wui'));
    echo $wui->render();

    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->halt();
}

$gMain_disp->addEvent('addnote', 'main_addnote');
function main_addnote($eventData) {
    global $gXml_def, $gLocale;

    if ($eventData['itemtype'] == InnoworkCompany::NOTE_ITEM_TYPE) {
        $item = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['itemid']);
    }
    else {
        $item = new InnoworkContact(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['itemid']);
    }

    $headers[0]['label'] = $gLocale->getStr('note.header');

    $gXml_def = '
    <page>
      <args>
        <border>false</border>
      </args>
      <children>
    <table><name>ntoe</name>
      <args>
        <headers type="array">'.WuiXml::encode($headers).'</headers>
      </args>
      <children>
        <form row="0" col="0"><name>note</name>
          <args>
                    <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'itemnotes', array('itemid' => $eventData['itemid'], 'itemtype' => $eventData['itemtype'])), array('action', 'newnote', array('itemid' => $eventData['itemid'], 'itemtype' => $eventData['itemtype']))))).'</action>
          </args>
          <children>
    
            <text><name>content</name>
              <args>
                <disp>action</disp>
                <rows>5</rows>
                <cols>55</cols>
              </args>
            </text>
    
          </children>
        </form>
    
            <horizgroup row="1" col="0">
              <children>
    
                <button>
                  <args>
                    <themeimage>buttonok</themeimage>
                    <label type="encoded">'.urlencode($gLocale->getStr('new_note.button')).'</label>
                    <formsubmit>note</formsubmit>
                    <frame>false</frame>
                    <horiz>true</horiz>
                    <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'itemnotes', array('itemid' => $eventData['itemid'], 'itemtype' => $eventData['itemtype'])), array('action', 'newnote', array('itemid' => $eventData['itemid'], 'itemtype' => $eventData['itemtype']))))).'</action>
                  </args>
                </button>
    
              </children>
            </horizgroup>
    
      </children>
    </table>
      </children>
    </page>';

    $wui = new WuiXml('', array('definition' => $gXml_def));
    $wui->Build();
    echo $wui->render();

    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->halt();
}

$gMain_disp->Dispatch();

function innoworkdirectory_regression_coeff(
    $x,
    $y
    )
{
    $result = array();

    $myarray = $x;
    $otherarray = $y;

        $sumX=0;
        $sumY=0;
        $sumXX=0;
        $sumXY=0;
                $n=count( $myarray );   //number of items
            for ($i=0; $i<$n; $i++) {
                $sumX +=$myarray[$i];
            $sumY +=$otherarray[$i];
            $meanX = $sumX/$n;
            $meanY = $sumY/$n;
            $sumXX += pow($myarray[$i],2);
            $sumXY += ($myarray[$i])*($otherarray[$i]);
            $m =($sumXY - $meanY*$sumX)/($sumXX-$meanX*$sumX);
            $b =$meanY-$meanX*$m;

                }

$val_a = $x[0] * $m + $b;
$val_b = $x[count($x)-1] * $m + $b;

        return $val_b / $val_a;
}

// ----- Rendering -----
//
/*
$gToolbars['help'] = array(
    'help' => array(
        'label' => $gLocale->getStr( 'help.toolbar' ),
        'themeimage' => 'help',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'help',
            ''
            ) ) )
        ) );
*/

$gWui->addChild(new WuiInnomaticPage('page', array('pagetitle' => $gPage_title, 'icon' => 'card', 'toolbars' => array(new WuiInnomaticToolBar('core', array('toolbars' => $gToolbars, 'toolbar' => 'true')), new WuiInnomaticToolbar('view', array('toolbars' => $gCore_toolbars, 'toolbar' => 'true'))), 'maincontent' => new WuiXml('page', array('definition' => $gXml_def)), 'status' => $gPage_status)));

$gWui->render();

?>
