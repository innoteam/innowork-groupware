<?php

// ----- Initialization -----
//

require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/widgets/WuiWidget.php');
require_once('innomatic/wui/widgets/WuiContainerWidget.php');
require_once('innomatic/wui/dispatch/WuiEventsCall.php');
require_once('innomatic/wui/dispatch/WuiEvent.php');
require_once('innomatic/wui/dispatch/WuiEventRawData.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('innomatic/locale/LocaleCatalog.php');
require_once('innomatic/locale/LocaleCountry.php'); 
require_once('innowork/groupware/InnoworkActivity.php');

global $gXml_def, $gLocale, $gPage_title, $gPage_status, $priorities;

function activity_cdata($data) {
    return '<![CDATA['.$data.']]>';
}

require_once('innowork/core/InnoworkCore.php');
$gInnowork_core = InnoworkCore::instance('innoworkcore', InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());
$gLocale = new LocaleCatalog('innowork-groupware::activity_main', InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage());

$gWui = Wui::instance('wui');
$gWui->LoadWidget('xml');
$gWui->LoadWidget('innomaticpage');
$gWui->LoadWidget('innomatictoolbar');

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr('activity.title');
$gCore_toolbars = $gInnowork_core->GetMainToolBar();
$gToolbars['activity'] = array('activitylist' => array('label' => $gLocale->getStr('activitylist.toolbar'), 'themeimage' => 'todo', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('done' => 'false'))))), 'doneactivitylist' => array('label' => $gLocale->getStr('doneactivitylist.toolbar'), 'themeimage' => 'todo', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('done' => 'true'))))), 'newactivity' => array('label' => $gLocale->getStr('newactivity.toolbar'), 'themeimage' => 'newtodo', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'newactivity', '')))));

$priorities[1] = '#ffe5e5';
$priorities[2] = '#ffcbcb';
$priorities[3] = '#ffb2b2';
$priorities[4] = '#ff9898';
$priorities[5] = '#ff7f7f';
$priorities[6] = '#ff6565';
$priorities[7] = '#ff4c4c';
$priorities[8] = '#ff3232';
$priorities[9] = '#ff1919';
$priorities[10] = '#ff0000';

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher('action');

$gAction_disp->addEvent('newactivity', 'action_newactivity');
function action_newactivity($eventData) {
    global $gLocale, $gPage_status;

    $innowork_activity = new InnoworkActivity(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());

    if ($innowork_activity->Create($eventData, InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId())) {
        $GLOBALS['innowork-activity']['newactivityid'] = $innowork_activity->mItemId;
        $gPage_status = $gLocale->getStr('activity_added.status');
    }
    else
        $gPage_status = $gLocale->getStr('activity_not_added.status');
}

$gAction_disp->addEvent('editactivity', 'action_editactivity');
function action_editactivity($eventData) {
    global $gLocale, $gPage_status;

    $innowork_activity = new InnoworkActivity(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($innowork_activity->Edit($eventData, InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()))
        $gPage_status = $gLocale->getStr('activity_updated.status');
    else
        $gPage_status = $gLocale->getStr('activity_not_updated.status');
}

$gAction_disp->addEvent('removeactivity', 'action_removeactivity');
function action_removeactivity($eventData) {
    global $gLocale, $gPage_status;

    $innowork_activity = new InnoworkActivity(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($innowork_activity->trash(InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()))
        $gPage_status = $gLocale->getStr('activity_removed.status');
    else
        $gPage_status = $gLocale->getStr('activity_not_removed.status');
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher('view');

function activity_list_action_builder($pageNumber) {
    return WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('pagenumber' => $pageNumber))));
}

$gMain_disp->addEvent('default', 'main_default');
function main_default($eventData) {
    global $gXml_def, $gLocale, $gPage_title;
    
    require_once('shared/wui/WuiSessionkey.php');

    $tab_sess = new WuiSessionKey('innoworkactivitiestab');

    if (!strlen($eventData['done']))
        $eventData['done'] = $tab_sess->mValue;
    if (!strlen($eventData['done']))
        $eventData['done'] = 'false';

    $tab_sess = new WuiSessionKey('innoworkactivitiestab', array('value' => isset($eventData['done']) ? $eventData['done'] : ''));

    if (isset($eventData['done']) and $eventData['done'] == 'true') {
        $done_check = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue;
        $done_icon = 'undo';
        $done_action = 'false';
        $done_label = 'setundone.button';
    }
    else {
        $done_check = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse;
        $done_icon = 'redo';
        $done_action = 'true';
        $done_label = 'setdone.button';
    }

    if (isset($eventData['filter_restrictto'])) {
        // Restrict

        $restrictto_filter_sk = new WuiSessionKey('restrictto_filter', array('value' => $eventData['filter_restrictto']));
    }
    else {
        // Restrict

        $restrictto_filter_sk = new WuiSessionKey('restrictto_filter');

        $eventData['filter_restrictto'] = $restrictto_filter_sk->mValue;
    }

    $country = new LocaleCountry(InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry());

    $headers[2]['label'] = $gLocale->getStr('activitydate.header');
    $headers[3]['label'] = $gLocale->getStr('activity.header');

    $activity = new InnoworkActivity(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());
    $search_results = $activity->Search(array('done' => $done_check), InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId(), false, false, 0, 0, $eventData['filter_restrictto']);

    $num_activity = count($search_results);

    /*
    $search_results = $activity->Search(
        array( 'done' => $eventData['done'] == 'true' ?
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue :
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse
            ),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
        );
        */

    $restrictto_array[InnoworkItem::SEARCH_RESTRICT_NONE] = $gLocale->getStr('restrictto_none.label');
    $restrictto_array[InnoworkItem::SEARCH_RESTRICT_TO_OWNER] = $gLocale->getStr('restrictto_owner.label');
    $restrictto_array[InnoworkItem::SEARCH_RESTRICT_TO_RESPONSIBLE] = $gLocale->getStr('restrictto_responsible.label');
    $restrictto_array[InnoworkItem::SEARCH_RESTRICT_TO_PARTICIPANT] = $gLocale->getStr('restrictto_participants.label');

    $activity_list = array();

    $gXml_def = '
    <vertgroup><name>activitylist</name>
              <children>
            
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('filter.label')).'</label>
                    <bold>true</bold>
                  </args>
                </label>
            
                <form><name>filter</name>
                  <args>
                        <action>'.activity_cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('filter' => 'true'))))).'</action>
                  </args>
                  <children>
                <grid>
                  <children>
            
                    <label row="0" col="0">
                      <args>
                        <label type="encoded">'.urlencode($gLocale->getStr('restrictto.label')).'</label>
                      </args>
                    </label>
            
                    <combobox row="0" col="1"><name>filter_restrictto</name>
                      <args>
                        <disp>view</disp>
                        <elements type="array">'.WuiXml::encode($restrictto_array).'</elements>
                        <default>'.$eventData['filter_restrictto'].'</default>
                      </args>
                    </combobox>
            
                    <button row="0" col="2"><name>filter</name>
                      <args>
                        <themeimage>filter</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <formsubmit>filter</formsubmit>
                        <label type="encoded">'.urlencode($gLocale->getStr('filter.button')).'</label>
                        <action>'.activity_cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('filter' => 'true'))))).'</action>
                      </args>
                    </button>
            
                  </children>
                </grid>
                  </children>
                </form>
            
                <horizbar/>
            
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr(($eventData['done'] == 'true' ? 'done' : '').'activitylist.label')).'</label>
                    <bold>true</bold>
                  </args>
                </label>
                <table><name>activity</name>
                  <args>
                    <rowsperpage>15</rowsperpage>
                    <pagesactionfunction>activity_list_action_builder</pagesactionfunction>
                    <pagenumber>'. (isset($eventData['pagenumber']) ? $eventData['pagenumber'] : '').'</pagenumber>
                    <headers type="array">'.WuiXml::encode($headers).'</headers>
                    <rows>'.$num_activity.'</rows>
                  </args>
                  <children>';

    $row = 0;

    $page = 1;

    if (isset($eventData['pagenumber'])) {
        $page = $eventData['pagenumber'];
    }
    else {
		require_once('shared/wui/WuiTable.php');
    	
        $table = new WuiTable('activity');

        $page = $table->mPageNumber;
    }
    if ($page > ceil($num_activity / 15))
        $page = ceil($num_activity / 15);

    global $priorities;

    $from = ($page * 15) - 15;
    $to = $from +15 - 1;
    foreach ($search_results as $id => $fields) {
        if ($row >= $from and $row <= $to) {
            switch ($fields['_acl']['type']) {
                case InnoworkAcl::TYPE_PRIVATE :
                    $image = 'personal';
                    break;

                case InnoworkAcl::TYPE_PUBLIC :
                case InnoworkAcl::TYPE_ACL :
                    $image = 'kuser';
                    break;
            }

            if (!strlen($fields['priority']))
                $fields['priority'] = '1';

            if (strlen($fields['activitydate'])) {
                $date_array = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->GetDateArrayFromTimestamp($fields['activitydate']);
                $date = $country->FormatShortArrayDate($date_array);
            }
            else
                $date = '';

            $gXml_def.= '<button row="'.$row.'" col="0"><name>acl</name>
                                      <args>
                                        <themeimage>'.$image.'</themeimage>
                                        <themeimagetype>mini</themeimagetype>
                                        <compact>true</compact>
                                      </args>
                                    </button>
                                    <vertframe row="'.$row.'" col="1">
                                      <args>
                                        <bgcolor>'.$priorities[$fields['priority']].'</bgcolor>
                                      </args>
                                      <children>
                                        <horizgroup>
                                          <children>
                                            <label>
                                              <args>
                                                <label>     </label>
                                              </args>
                                            </label>
                                          </children>
                                        </horizgroup>
                                      </children>
                                    </vertframe>
                                    <label row="'.$row.'" col="2"><name>comp</name>
                                      <args>
                                        <label>'.activity_cdata($date).'</label>
                                        <compact>true</compact>
                                      </args>
                                    </label>
                                    <link row="'.$row.'" col="3"><name>comp</name>
                                      <args>
                                        <label type="encoded">'.activity_cdata(urlencode(strlen($fields['activity']) > 50 ? substr($fields['activity'], 0, 47).'...' : $fields['activity'])).'</label>
                                        <title type="encoded">'.activity_cdata(urlencode($fields['activity'])).'</title>
                                        <link>'.activity_cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'showactivity', array('id' => $id))))).'</link>
                                        <compact>true</compact>
                                      </args>
                                    </link>
                                    <innomatictoolbar row="'.$row.'" col="4"><name>tb</name>
                                      <args>
                                        <frame>false</frame>
                                        <toolbars type="array">'.WuiXml::encode(array('view' => array('show' => array('label' => $gLocale->getStr('showactivity.button'), 'themeimage' => 'viewmag', 'themeimagetype' => 'mini', 'compact' => 'true', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'showactivity', array('id' => $id))))), 'done' => array('label' => $gLocale->getStr($done_label), 'themeimage' => $done_icon, 'themeimagetype' => 'mini', 'compact' => 'true', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'editactivity', array('id' => $id, 'done' => $done_action))))), 'remove' => array('label' => $gLocale->getStr('removeactivity.button'), 'needconfirm' => 'true', 'confirmmessage' => $gLocale->getStr('removeactivity.confirm'), 'horiz' => 'true', 'compact' => 'true', 'themeimage' => 'edittrash', 'themeimagetype' => 'mini', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'removeactivity', array('id' => $id)))))))).'</toolbars>
                                      </args>
                                    </innomatictoolbar>';

        }
        $row ++;
    }

    $gXml_def.= '      </children>
                </table>
              </children>
            </vertgroup>';
}

$gMain_disp->addEvent('newactivity', 'main_newactivity');
function main_newactivity($eventData) {
    global $gXml_def, $gLocale, $gPage_title;

    $core = InnoworkCore::instance('innoworkcore', InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());
    $summ = $core->getSummaries();

    if (isset($summ['project'])) {
        $innowork_projects = new InnoworkProject(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());
        $search_results = $innowork_projects->Search(array('done' => InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse), InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId());

        $projects['0'] = $gLocale->getStr('noproject.label');

        while (list ($id, $fields) = each($search_results)) {
            $projects[$id] = $fields['name'];
        }
    }

    $locale_country = new LocaleCountry(InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry());

    $curr_date = $locale_country->getDateArrayFromSafeTimestamp($locale_country->SafeFormatTimestamp());

    $priorities_desc[10] = '10';
    $priorities_desc[9] = '9';
    $priorities_desc[8] = '8';
    $priorities_desc[7] = '7';
    $priorities_desc[6] = '6';
    $priorities_desc[5] = '5';
    $priorities_desc[4] = '4';
    $priorities_desc[3] = '3';
    $priorities_desc[2] = '2';
    $priorities_desc[1] = '1';

    $gXml_def.= '
    <vertgroup><name>newactivity</name>
              <children>
                <table><name>company</name>
                  <args>
                    <headers type="array">'.WuiXml::encode(array('0' => array('label' => $gLocale->getStr('newactivity.label')))).'</headers>
                  </args>
                  <children>
                <form row="0" col="0"><name>activity</name>
                  <args>
                    <method>post</method>
                    <action>'.activity_cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'showactivity', ''), array('action', 'newactivity', '')))).'</action>
                  </args>
                  <children>
            
                    <horizgroup>
                      <children>
            
                        <text><name>activity</name>
                          <args>
                            <disp>action</disp>
                            <cols>80</cols>
                            <rows>3</rows>
                          </args>
                        </text>
            
                      </children>
                    </horizgroup>
            
                    <vertgroup>
                      <children>
            
                        <label><name>activitydate</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale->getStr('description.label')).'</label>
                          </args>
                        </label>
                        <text><name>description</name>
                          <args>
                            <disp>action</disp>
                            <cols>80</cols>
                            <rows>7</rows>
                          </args>
                        </text>
            
                      </children>
                    </vertgroup>
            
                    <horizgroup>
                      <args>
                        <align>middle</align>
                      </args>
                      <children>
            
                        <label><name>activitydate</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale->getStr('activitydate.label')).'</label>
                          </args>
                        </label>
                        <date><name>activitydate</name>
                          <args>
                            <disp>action</disp>
                            <value type="array">'.WuiXml::encode($curr_date).'</value>
                          </args>
                        </date>';

    if (isset($summ['project'])) {
        $gXml_def.= '            <label><name>project</name>
                                      <args>
                                        <label type="encoded">'.urlencode($gLocale->getStr('project.label')).'</label>
                                      </args>
                                    </label>
                                    <combobox><name>projectid</name>
                                      <args>
                                        <disp>action</disp>
                                        <elements type="array">'.WuiXml::encode($projects).'</elements>
                                      </args>
                                    </combobox>';
    }

    $gXml_def.= '
                  </children>
                    </horizgroup>
    
                    <horizgroup>
                      <children>
                    <label>
                                  <args>
                                    <label type="encoded">'.urlencode($gLocale->getStr('priority.label')).'</label>
                                  </args>
                                </label>
                                <combobox><name>priority</name>
                                  <args>
                                    <disp>action</disp>
                                    <elements type="array">'.WuiXml::encode($priorities_desc).'</elements>
                                    <default>5</default>
                                  </args>
                                </combobox>
                                
                      </children>
                    </horizgroup>
    
                    </children>
                    </form>
            
                    <button row="1" col="0"><name>apply</name>
                      <args>
                        <themeimage>button_ok</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <action>'.activity_cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'showactivity', ''), array('action', 'newactivity', '')))).'</action>
                        <label type="encoded">'.urlencode($gLocale->getStr('newactivity.submit')).'</label>
                        <formsubmit>activity</formsubmit>
                      </args>
                    </button>
            
                  </children>
                </table>
              </children>
            </vertgroup>';
}

$gMain_disp->addEvent('showactivity', 'main_showactivity');
function main_showactivity($eventData) {
    global $gXml_def, $gLocale, $gPage_title;

    if (isset($GLOBALS['innowork-activity']['newactivityid'])) {
        $eventData['id'] = $GLOBALS['innowork-activity']['newactivityid'];
    }

    $innowork_activity = new InnoworkActivity(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    $activity_data = $innowork_activity->GetItem(InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId());

    if ($activity_data['done'] == InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue) {
        $done_icon = 'undo';
        $done_action = 'false';
        $done_label = 'setundone.button';
    }
    else {
        $done_icon = 'redo';
        $done_action = 'true';
        $done_label = 'setdone.button';
    }

    $priorities_desc[10] = '10';
    $priorities_desc[9] = '9';
    $priorities_desc[8] = '8';
    $priorities_desc[7] = '7';
    $priorities_desc[6] = '6';
    $priorities_desc[5] = '5';
    $priorities_desc[4] = '4';
    $priorities_desc[3] = '3';
    $priorities_desc[2] = '2';
    $priorities_desc[1] = '1';

    $core = InnoworkCore::instance('innoworkcore', InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());
    $summ = $core->getSummaries();

    if (isset($summ['project'])) {
        $innowork_projects = new InnoworkProject(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());
        $search_results = $innowork_projects->Search('', InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId());

        $projects['0'] = $gLocale->getStr('noproject.label');

        while (list ($id, $fields) = each($search_results)) {
            $projects[$id] = $fields['name'];
        }
    }

    $country = new LocaleCountry(InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry());
    $empty_date_array = $country->GetDateArrayFromShortDateStamp('');
    $empty_date_text = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->GetTimestampFromDateArray($empty_date_array);

    if (!strlen($activity_data['priority']))
        $activity_data['priority'] = '1';

    $gXml_def.= '<horizgroup><name>activity</name>
              <children>
            <vertgroup><name>activity</name>
              <children>
                <table><name>company</name>
                  <args>
                    <headers type="array">'.WuiXml::encode(array('0' => array('label' => $gLocale->getStr('activity.label')))).'</headers>
                  </args>
                  <children>
            
                <form row="0" col="0"><name>activity</name>
                  <args>
                    <method>post</method>
                    <action>'.activity_cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'editactivity', array('id' => $eventData['id']))))).'</action>
                  </args>
                  <children>
            
                    <horizgroup><name>activity</name>
                      <children>
            
                        <text><name>activity</name>
                          <args>
                            <disp>action</disp>
                            <cols>80</cols>
                            <rows>3</rows>
                            <value>'.activity_cdata($activity_data['activity']).'</value>
                          </args>
                        </text>
            
                      </children>
                    </horizgroup>
            
                    <vertgroup>
                      <children>
            
                        <label><name>activitydate</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale->getStr('description.label')).'</label>
                          </args>
                        </label>
                        <text><name>description</name>
                          <args>
                            <disp>action</disp>
                            <cols>80</cols>
                            <rows>7</rows>
                            <value type="encoded">'.activity_cdata(urlencode($activity_data['description'])).'</value>
                          </args>
                        </text>
            
                      </children>
                    </vertgroup>
            
                    <horizgroup>
                      <args>
                        <align>middle</align>
                      </args>
                      <children>
            
                        <label><name>activitydate</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale->getStr('activitydate.label')).'</label>
                          </args>
                        </label>
                        <date><name>activitydate</name>
                          <args>
                            <disp>action</disp>
                            <value type="array">'.WuiXml::encode(InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->GetDateArrayFromTimestamp($activity_data['activitydate'])).'</value>
                          </args>
                        </date>';

    if (isset($summ['project'])) {
        $gXml_def.= '            <label><name>project</name>
                                      <args>
                                        <label type="encoded">'.urlencode($gLocale->getStr('project.label')).'</label>
                                      </args>
                                    </label>
                                    <combobox><name>projectid</name>
                                      <args>
                                        <disp>action</disp>
                                        <elements type="array">'.WuiXml::encode($projects).'</elements>
                                        <default>'.$activity_data['projectid'].'</default>
                                      </args>
                                    </combobox>';
    }

    $gXml_def.= '
                  </children>
                    </horizgroup>
    
                    <horizgroup>
                      <children>
                    <label>
                                  <args>
                                    <label type="encoded">'.urlencode($gLocale->getStr('priority.label')).'</label>
                                  </args>
                                </label>
                                <combobox><name>priority</name>
                                  <args>
                                    <disp>action</disp>
                                    <elements type="array">'.WuiXml::encode($priorities_desc).'</elements>
                                    <default>'.$activity_data['priority'].'</default>
                                  </args>
                                </combobox>

                    <label>
                                  <args>
                                    <label type="encoded">'.urlencode($gLocale->getStr('spenttime.label')).'</label>
                                  </args>
                                </label>
                                <string>
                                  <name>spenttime</name>
                                  <args>
                                    <disp>action</disp>
                                    <size>5</size>
                                    <value>'.$activity_data['spenttime'].'</value>
                                  </args>
                                </string>
                                
                      </children>
                    </horizgroup>
    
                    </children>
                    </form>
            
                    <horizgroup row="1" col="0">
                      <children>
            
                    <button><name>apply</name>
                      <args>
                        <themeimage>button_ok</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <action>'.activity_cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'editactivity', array('id' => $eventData['id']))))).'</action>
                        <label type="encoded">'.urlencode($gLocale->getStr('editactivity.submit')).'</label>
                        <formsubmit>activity</formsubmit>
                      </args>
                    </button>
            
                    <button>
                      <args>
                        <themeimage>fileclose</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <action>'.activity_cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', '')))).'</action>
                        <label type="encoded">'.urlencode($gLocale->getStr('close.button')).'</label>
                      </args>
                    </button>
            
                    <button>
                      <args>
                        <themeimage>'.$done_icon.'</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <action>'.activity_cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'editactivity', array('id' => $eventData['id'], 'done' => $done_action))))).'</action>
                        <label type="encoded">'.urlencode($gLocale->getStr($done_label)).'</label>
                        <formsubmit>activity</formsubmit>
                      </args>
                    </button>
            
                    <button><name>remove</name>
                      <args>
                        <themeimage>edittrash</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <confirmmessage>'.$gLocale->getStr('removeactivity.confirm').'</confirmmessage>
                        <action>'.activity_cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'removeactivity', array('id' => $eventData['id']))))).'</action>
                        <label type="encoded">'.urlencode($gLocale->getStr('removeactivity.button')).'</label>
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
                  <itemtype>activity</itemtype>
                  <itemid>'.$eventData['id'].'</itemid>
                  <itemownerid>'.$activity_data['ownerid'].'</itemownerid>
                  <defaultaction>'.activity_cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'showactivity', array('id' => $eventData['id']))))).'</defaultaction>
                </args>
              </innoworkitemacl>
            
              </children>
            </horizgroup>';
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//

$gWui->addChild(new WuiInnomaticPage('page', array('pagetitle' => $gPage_title, 'icon' => 'klipper', 'menu' => $gInnowork_core->GetMainMenu(), 'toolbars' => array(new WuiInnomaticToolBar('core', array('toolbars' => $gCore_toolbars)), new WuiInnomaticToolbar('view', array('toolbars' => $gToolbars))), 'maincontent' => new WuiXml('page', array('definition' => $gXml_def)), 'status' => $gPage_status)));

$gWui->render();

?>
