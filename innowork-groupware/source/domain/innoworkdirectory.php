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
$gToolbars['companies'] = array('companies' => array('label' => $gLocale->getStr('companies.toolbar'), 'themeimage' => 'listdetailed', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', '')))), 'newcompany' => array('label' => $gLocale->getStr('newcompany.toolbar'), 'themeimage' => 'filenew', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'newcompany', '')))));

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher('action');

$gAction_disp->addEvent('newcompany', 'action_newcompany');
function action_newcompany($eventData) {
    global $gLocale, $gPage_status;

    $innowork_company = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

    if ($innowork_company->Create($eventData, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId())) {
        $GLOBALS['innowork-groupware']['newcompanyid'] = $innowork_company->mItemId;

        $gPage_status = $gLocale->getStr('company_added.status');
    }
    else
        $gPage_status = $gLocale->getStr('company_not_added.status');
}

$gAction_disp->addEvent('editcompany', 'action_editcompany');
function action_editcompany($eventData) {
    global $gLocale, $gPage_status;

    $innowork_company = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($innowork_company->Edit($eventData, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()))
        $gPage_status = $gLocale->getStr('company_updated.status');
    else
        $gPage_status = $gLocale->getStr('company_not_updated.status');
}

$gAction_disp->addEvent('removecompany', 'action_removecompany');
function action_removecompany($eventData) {
    global $gLocale, $gPage_status;

    $innowork_company = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($innowork_company->trash(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()))
        $gPage_status = $gLocale->getStr('company_removed.status');
    else
        $gPage_status = $gLocale->getStr('company_not_removed.status');
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

function companies_tab_action_builder($tab) {
    return WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('tabpage' => $tab))));
}

function companies_typetab_action_builder($tab) {
    return WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('typetabpage' => $tab))));
}

$gMain_disp->addEvent('default', 'main_default');
function main_default($eventData) {
    global $gXml_def, $gLocale, $gPage_title;

    require_once('shared/wui/WuiSessionkey.php');
    
    $headers[0]['label'] = $gLocale->getStr('code.header');
    $headers[1]['label'] = $gLocale->getStr('company.header');

    $companies = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
    $search_results = $companies->Search('', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());

    $company_types['%']= $gLocale->getStr('type_all.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_CUSTOMER]= $gLocale->getStr('type_customer.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_SUPPLIER]= $gLocale->getStr('type_supplier.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_BOTH]= $gLocale->getStr('type_both.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_CONSULTANT]= $gLocale->getStr('type_consultant.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_GOVERNMENT]= $gLocale->getStr('type_government.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_INTERNAL]= $gLocale->getStr('type_internal.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_NONE]= $gLocale->getStr('type_none.label');

    if (isset($eventData['viewmode'])) {
        $viewmode_sk = new WuiSessionKey('viewmode_comp', array('value' => $eventData['viewmode']));
    }
    else {
        $viewmode_sk = new WuiSessionKey('viewmode_comp');
        $eventData['viewmode'] = $viewmode_sk->mValue;
    }
    if (!strlen($eventData['viewmode'])) $eventData['viewmode'] = 'compact';

    $gXml_def = '
    <vertgroup><name>companies</name>
      <children>
        
        <label><name>title</name>
          <args>
            <bold>true</bold>
            <label>'.$gLocale->getStr('companies.label').'</label>
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

        <innoworkcontactslist><name>companies</name>
          <args>
            <contacts type="array">'.WuiXml::encode($search_results).'</contacts>
            <type>companies</type>
            <viewmode>'.$eventData['viewmode'].'</viewmode>
            <tabactionfunction>companies_tab_action_builder</tabactionfunction>
            <typetabactionfunction>companies_typetab_action_builder</typetabactionfunction>
            <activetab>'. (isset($eventData['tabpage']) ? $eventData['tabpage'] : '').'</activetab>
            <activetypetab>'. (isset($eventData['typetabpage']) ? $eventData['typetabpage'] : '').'</activetypetab>
          </args>
        </innoworkcontactslist>
    
      </children>
    </vertgroup>';
}

$gMain_disp->addEvent('newcompany', 'main_newcompany');
function main_newcompany($eventData) {
    global $gXml_def, $gLocale, $gPage_title, $gUsers;

    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_CUSTOMER]= $gLocale->getStr('type_customer.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_SUPPLIER]= $gLocale->getStr('type_supplier.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_BOTH]= $gLocale->getStr('type_both.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_CONSULTANT]= $gLocale->getStr('type_consultant.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_GOVERNMENT]= $gLocale->getStr('type_government.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_INTERNAL]= $gLocale->getStr('type_internal.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_NONE]= $gLocale->getStr('type_none.label');
    
    $gXml_def.= '
    <vertgroup><name>newcompany</name>
      <children>
    
        <table><name>company</name>
          <args>
            <headers type="array">'.WuiXml::encode(array('0' => array('label' => $gLocale->getStr('newcompany.label')))).'</headers>
          </args>
          <children>
        <form row="0" col="0"><name>company</name>
          <args>
            <method>post</method>
            <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'showcompany', ''), array('action', 'newcompany', '')))).'</action>
          </args>
          <children>
    
            <horizgroup><name>company</name>
              <children>
    
                <label><name>code</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('code.label')).'</label>
                  </args>
                </label>
                <string><name>code</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode(isset($eventData['code']) ? $eventData['code'] : '').'</value>
                  </args>
                </string>
                <label><name>name</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('companyname.label')).'</label>
                  </args>
                </label>
                <string><name>companyname</name>
                  <args>
                    <disp>action</disp>
                    <size>25</size>
                    <value type="encoded">'.urlencode(isset($eventData['companyname']) ? $eventData['companyname'] : '').'</value>
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
                    <default type="encoded">'.urlencode(isset($eventData['accountmanager']) ? $eventData['accountmanager'] : '').'</default>
                  </args>
                </combobox>
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('companytype.label')).'</label>
                  </args>
                </label>
                <combobox><name>companytype</name>
                  <args>
                    <disp>action</disp>
                    <elements type="array">'.WuiXml::encode($company_types).'</elements>
                    <default type="encoded">'.urlencode(isset($eventData['companytype']) ? $eventData['companytype'] : '').'</default>
                  </args>
                </combobox>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>
    
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
                    <value type="encoded">'.urlencode(isset($eventData['street']) ? $eventData['street'] : '').'</value>
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
                    <value type="encoded">'.urlencode(isset($eventData['city']) ? $eventData['city'] : '').'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizgroup><name>company</name>
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
                    <value type="encoded">'.urlencode(isset($eventData['zip']) ? $eventData['zip'] : '').'</value>
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
                    <value type="encoded">'.urlencode(isset($eventData['state']) ? $eventData['state'] : '').'</value>
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
                    <value type="encoded">'.urlencode(isset($eventData['country']) ? $eventData['country'] : '').'</value>
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
    
            <horizgroup><name>company</name>
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
                    <value type="encoded">'.urlencode(isset($eventData['email']) ? $eventData['email'] : '').'</value>
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
                    <value type="encoded">'.urlencode(isset($eventData['url']) ? $eventData['url'] : '').'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizgroup><name>company</name>
              <children>
    
                <label><name>phone</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('phone.label')).'</label>
                  </args>
                </label>
                <string><name>phone</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                    <value type="encoded">'.urlencode(isset($eventData['phone']) ? $eventData['phone'] : '').'</value>
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
                    <value type="encoded">'.urlencode(isset($eventData['fax']) ? $eventData['fax'] : '').'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>
    
            <horizgroup><name>company</name>
              <children>
    
                <label><name>fiscalcode</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('fiscalcode.label')).'</label>
                  </args>
                </label>
                <string><name>fiscalcode</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode(isset($eventData['fiscalcode']) ? $eventData['fiscalcode'] : '').'</value>
                  </args>
                </string>
    
                <label><name>fiscalcodeb</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('fiscalcodeb.label')).'</label>
                  </args>
                </label>
                <string><name>fiscalcodeb</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode(isset($eventData['fiscalcodeb']) ? $eventData['fiscalcodeb'] : '').'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>

            <horizgroup>
              <children>
    
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('legalerappresentante.label')).'</label>
                  </args>
                </label>
                <string><name>legalerappresentante</name>
                  <args>
                    <disp>action</disp>
                    <size>20</size>
                    <value type="encoded">'.urlencode(isset($eventData['legalerappresentante']) ? $eventData['legalerappresentante'] : '').'</value>
                  </args>
                </string>
    
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('lrfiscalcode.label')).'</label>
                  </args>
                </label>
                <string><name>lrfiscalcode</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode(isset($eventData['lrfiscalcode']) ? $eventData['lrfiscalcode'] : '').'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>    
            <horizbar><name>hb</name></horizbar>
    
            <label><name>notes</name>
              <args>
                <bold>true</bold>
                <label type="encoded">'.urlencode($gLocale->getStr('other.label')).'</label>
              </args>
            </label>
    
            <horizgroup>
              <children>
    
                <text><name>notes</name>
                  <args>
                    <disp>action</disp>
                    <cols>80</cols>
                    <rows>7</rows>
                    <value type="encoded">'.urlencode(isset($eventData['notes']) ? $eventData['notes'] : '').'</value>
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
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'showcompany', ''), array('action', 'newcompany', '')))).'</action>
                <label type="encoded">'.urlencode($gLocale->getStr('newcompany.submit')).'</label>
                <formsubmit>company</formsubmit>
              </args>
            </button>
    
            </children>
            </table>
      </children>
    </vertgroup>';
}

function company_extras_tab_builder($tab) {
    global $gMain_disp;
    $ev_data = $gMain_disp->GetEventData();

    return WuiEventsCall::buildEventsCallString('', array(array('view', 'showcompany', array('id' => $ev_data['id'], 'extrastab' => $tab))));
}

$gMain_disp->addEvent('showcompany', 'main_showcompany');
function main_showcompany($eventData) {
    global $gXml_def, $gLocale, $gPage_title, $gToolbars, $gInnowork_core, $gUsers;

    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_CUSTOMER]= $gLocale->getStr('type_customer.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_SUPPLIER]= $gLocale->getStr('type_supplier.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_BOTH]= $gLocale->getStr('type_both.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_CONSULTANT]= $gLocale->getStr('type_consultant.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_GOVERNMENT]= $gLocale->getStr('type_government.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_INTERNAL]= $gLocale->getStr('type_internal.label');
    $company_types[INNOWORKDIRECTORY_COMPANY_TYPE_NONE]= $gLocale->getStr('type_none.label');

    $graph_ok = false;

    if (isset($GLOBALS['innowork-groupware']['newcompanyid'])) {
        $eventData['id'] = $GLOBALS['innowork-groupware']['newcompanyid'];
    }

    $innowork_company = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    $cp_data = $innowork_company->GetItem(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());

    switch (strtolower(substr($cp_data['companyname'], 0, 1))) {
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

    $summ = $gInnowork_core->GetSummaries();

    $innowork_bill_installed = false;
    if (isset($summ['billing'])) {
        $innowork_bill_installed = true;
    }

    if ($innowork_bill_installed) {
        $vat_list = \Innowork\Billing\InnoworkBillingVat::getVatList();
        $payment_list = \Innowork\Billing\InnoworkBillingPayment::getPaymentList();
        
        $tabs[2]['label'] = $gLocale->getStr('extras_invoices.tab');
        $locale_country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

        $credit = 0;
        $due_credit = 0;
        $invoices_amount = 0;

        $invoices_handler = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        $invoices_handler->mSearchOrderBy = 'emissiondate,number';

        $invoices = $invoices_handler->search(array('customerid' => $eventData['id']));

        if (count($invoices)) {
            $cycle_start = true;

            foreach ($invoices as $id => $fields) {
                $expired = false;

                if ($cycle_start) {
                    $from_date = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetDateArrayFromTimestamp($fields['emissiondate']);
                    //$from_ts = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->getTimestampFromDateArray( $from_date );
                    $from_secs = mktime(0, 0, 0, $from_date['mon'], $from_date['mday'], $from_date['year']);
                }
                $cycle_start = false;

                // Due date

                $due_date_array = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetDateArrayFromTimestamp($fields['duedate']);
                $due_date = $locale_country->FormatShortArrayDate($due_date_array);

                $emission_date_array = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetDateArrayFromTimestamp($fields['emissiondate']);

                if (($fields['total'] - $fields['paidamount']) > 0) {
                    if ($due_date_array['year'] < date('Y') or ($due_date_array['year'] == date('Y') and $due_date_array['mon'] < date('m')) or ($due_date_array['year'] == date('Y') and $due_date_array['mon'] == date('m') and $due_date_array['mday'] < date('d'))) {
                        $expired = true;
                    }

                    $credit += $fields['total'] - $fields['paidamount'];
                    if ($expired)
                        $due_credit += $fields['total'] - $fields['paidamount'];
                }

                $invoices_amount += $fields['amount'];
                $_graph_data[$emission_date_array['year'].$emission_date_array['mon']]['amount'] += $fields['amount'];
                $_graph_data[$emission_date_array['year'].$emission_date_array['mon']]['month'] = $emission_date_array['mon'];
            }

            //$to_date = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->getDateArrayFromTimestamp( $fields['emissiondate'] );
            $to_secs = mktime(23, 59, 59, $emission_date_array['mon'], $emission_date_array['mday'], $emission_date_array['year']);

            for ($i = $from_secs; $i <= $to_secs; $i += 60 * 60 * 24) {
                $tmp_date_array = $locale_country->GetDateArrayFromUnixTimestamp($i);

                if (!isset($_graph_data[$tmp_date_array['year'].$tmp_date_array['mon']])) {
                    $_graph_data[$tmp_date_array['year'].$tmp_date_array['mon']]['amount'] = 0;
                    $_graph_data[$tmp_date_array['year'].$tmp_date_array['mon']]['month'] = $tmp_date_array['mon'];
                }
            }

            ksort($_graph_data);

            $x_array = $y_array = array();
            $cont = 1;

            foreach ($_graph_data as $id => $value) {
                $x_array[] = $cont ++;
                $y_array[] = $value['amount'];

            }
            reset($_graph_data);

            require_once('phplot/PHPlot.php');
            $regression_data = phplot_regression($x_array, $y_array);

            $cont = 0;

            foreach ($_graph_data as $date => $values) {
                $graph_data[] = array($values['month'], $values['amount'], $regression_data[$cont ++][2]);
            }

            $graph_ok = true;
        }
    }

    $gXml_def.= '
    <horizgroup><name>company</name>
      <children>
    <vertgroup><name>company</name>
      <children>
    
        <table><name>company</name>
          <args>
            <headers type="array">'.WuiXml::encode(array('0' => array('label' => $gLocale->getStr('company.label')))).'</headers>
          </args>
          <children>
        <form row="0" col="0"><name>company</name>
          <args>
            <method>post</method>
            <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'showcompany', array('id' => $eventData['id'])), array('action', 'editcompany', array('id' => $eventData['id']))))).'</action>
          </args>
          <children>
    
            <horizgroup><name>company</name>
              <children>
    
                <label><name>code</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('code.label')).'</label>
                  </args>
                </label>
                <string><name>code</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode($cp_data['code']).'</value>
                  </args>
                </string>
                <label><name>name</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('companyname.label')).'</label>
                  </args>
                </label>
                <string><name>companyname</name>
                  <args>
                    <disp>action</disp>
                    <size>25</size>
                    <value type="encoded">'.urlencode($cp_data['companyname']).'</value>
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
                    <default type="encoded">'.$cp_data['accountmanager'].'</default>
                  </args>
                </combobox>
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('companytype.label')).'</label>
                  </args>
                </label>
                <combobox><name>companytype</name>
                  <args>
                    <disp>action</disp>
                    <elements type="array">'.WuiXml::encode($company_types).'</elements>
                    <default type="encoded">'.$cp_data['companytype'].'</default>
                  </args>
                </combobox>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>
    
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
                    <value type="encoded">'.urlencode($cp_data['street']).'</value>
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
                    <value type="encoded">'.urlencode($cp_data['city']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizgroup><name>company</name>
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
                    <value type="encoded">'.urlencode($cp_data['zip']).'</value>
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
                    <value type="encoded">'.urlencode($cp_data['state']).'</value>
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
                    <value type="encoded">'.urlencode($cp_data['country']).'</value>
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
    
            <horizgroup><name>company</name>
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
                    <value type="encoded">'.urlencode($cp_data['email']).'</value>
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
                    <value type="encoded">'.urlencode($cp_data['url']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizgroup><name>company</name>
              <children>
    
                <label><name>phone</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('phone.label')).'</label>
                  </args>
                </label>
                <string><name>phone</name>
                  <args>
                    <disp>action</disp>
                    <size>10</size>
                    <value type="encoded">'.urlencode($cp_data['phone']).'</value>
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
                    <value type="encoded">'.urlencode($cp_data['fax']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>
    
            <horizgroup><name>company</name>
              <children>
    
                <label><name>fiscalcode</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('fiscalcode.label')).'</label>
                  </args>
                </label>
                <string><name>fiscalcode</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode($cp_data['fiscalcode']).'</value>
                  </args>
                </string>
    
                <label><name>fiscalcodeb</name>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('fiscalcodeb.label')).'</label>
                  </args>
                </label>
                <string><name>fiscalcodeb</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode($cp_data['fiscalcodeb']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizgroup>
              <children>
    
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('legalerappresentante.label')).'</label>
                  </args>
                </label>
                <string><name>legalerappresentante</name>
                  <args>
                    <disp>action</disp>
                    <size>20</size>
                    <value type="encoded">'.urlencode($cp_data['legalerappresentante']).'</value>
                  </args>
                </string>
    
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('lrfiscalcode.label')).'</label>
                  </args>
                </label>
                <string><name>lrfiscalcode</name>
                  <args>
                    <disp>action</disp>
                    <size>15</size>
                    <value type="encoded">'.urlencode($cp_data['lrfiscalcode']).'</value>
                  </args>
                </string>
    
              </children>
            </horizgroup>
    
            <horizbar><name>hb</name></horizbar>
    
            <horizgroup><name>company</name>
              <children>
    
                <tab><name>extras</name>
                  <args>
                    <tabs type="array">'.WuiXml::encode($tabs).'</tabs>
                    <tabactionfunction>company_extras_tab_builder</tabactionfunction>
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
                    <value type="encoded">'.urlencode($cp_data['notes']).'</value>
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
                <source type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'itemnotes', array('itemid' => $eventData['id'], 'itemtype' => InnoworkCompany::NOTE_ITEM_TYPE))))).'</source>
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
                    <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'addnote', array('itemid' => $eventData['id'], 'itemtype' => InnoworkCompany::NOTE_ITEM_TYPE))))).'</action>
                  </args>
                </button>
    
                      </children>
                    </vertgroup>';

    if ($innowork_bill_installed) {
        $gXml_def.= '<vertgroup>
          <children>
            <grid><args><width>0%</width></args><children>
              <label row="0" col="0"><args><label>'.WuiXml::cdata($gLocale->getStr('default_vat.label')).'</label></args></label>
                <combobox row="0" col="1"><name>defaultvatid</name>
                  <args>
                    <disp>action</disp>
                    <elements type="array">'.WuiXml::encode($vat_list).'</elements>
                    <default>'.(strlen($cp_data['defaultvatid']) ? $cp_data['defaultvatid'] : \Innowork\Billing\InnoworkBillingSettingsHandler::getDefaultVat()).'</default>
                  </args>
                </combobox>
                  
              <label row="1" col="0"><args><label>'.WuiXml::cdata($gLocale->getStr('default_payment.label')).'</label></args></label>
                <combobox row="1" col="1"><name>defaultpaymentid</name>
                  <args>
                    <disp>action</disp>
                    <elements type="array">'.WuiXml::encode($payment_list).'</elements>
                    <default>'.(strlen($cp_data['defaultpaymentid']) ? $cp_data['defaultpaymentid'] : \Innowork\Billing\InnoworkBillingSettingsHandler::getDefaultPayment()).'</default>
                  </args>
                </combobox>
                        
            </children></grid>
            
                <label>
                  <args>
                    <bold>true</bold>
                    <label type="encoded">'.urlencode($gLocale->getStr('invoices.label')).'</label>
                  </args>
                </label>
        
                <grid>
                  <children>
        
                <label row="0" col="0">
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('invoices_amount.label')).'</label>
                  </args>
                </label>
                <string row="0" col="1">
                  <args>
                    <readonly>true</readonly>
                    <size>15</size>
                    <value type="encoded">'.urlencode(number_format($invoices_amount, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator())).'</value>
                  </args>
                </string>
                <label row="1" col="0">
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('credit.label')).'</label>
                  </args>
                </label>
                <string row="1" col="1">
                  <args>
                    <readonly>true</readonly>
                    <size>15</size>
                    <value type="encoded">'.urlencode(number_format($credit, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator())).'</value>
                  </args>
                </string>
                <label row="2" col="0">
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('due_credit.label')).'</label>
                  </args>
                </label>
                <string row="2" col="1">
                  <args>
                    <readonly>true</readonly>
                    <size>15</size>
                    <value type="encoded">'.urlencode(number_format($due_credit, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator())).'</value>
                  </args>
                </string>
        
                  </children>
                </grid>';

        if ($graph_ok)
            $gXml_def.= '
                        <vertgroup>
                          <children>
                        <phplot>
                          <args>
                            <data type="array">'.WuiXml::encode($graph_data).'</data>
                            <width>400</width>
                            <height>250</height>
                            <title type="encoded">'.urlencode($gLocale->getStr('invoices_graph.label')).'</title>
                          </args>
                        </phplot>
                        
                          <label>
                            <args>
                              <label>Coeff. '.(innoworkdirectory_regression_coeff($x_array, $y_array)).'</label>
                            </args>
                          </label>
                          </children>
                        </vertgroup>';

        $gXml_def.= '  </children>
        </vertgroup>';
    }

    $gXml_def.= '              </children>
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
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'showcompany', array('id' => $eventData['id'])), array('action', 'editcompany', array('id' => $eventData['id']))))).'</action>
                <label type="encoded">'.urlencode($gLocale->getStr('editcompany.submit')).'</label>
                <formsubmit>company</formsubmit>
              </args>
            </button>
    
            <button>
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
                <label type="encoded">'.urlencode($gLocale->getStr('removecompany.button')).'</label>
                <needconfirm>true</needconfirm>
                <confirmmessage type="encoded">'.urlencode(sprintf($gLocale->getStr('removecompany.confirm'), $cp_data['companyname'])).'</confirmmessage>
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('tabpage' => $tab_page)), array('action', 'removecompany', array('id' => $eventData['id']))))).'</action>
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
          <itemtype>directorycompany</itemtype>
          <itemid>'.$eventData['id'].'</itemid>
          <itemownerid>'.$cp_data['ownerid'].'</itemownerid>
          <defaultaction type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'showcompany', array('id' => $eventData['id']))))).'</defaultaction>
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
