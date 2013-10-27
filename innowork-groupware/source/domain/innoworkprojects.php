<?php
// ----- Initialization -----
//

require_once('innowork/groupware/InnoworkProject.php');
require_once('innowork/groupware/InnoworkProjectField.php');
require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/widgets/WuiWidget.php');
require_once('innomatic/wui/widgets/WuiContainerWidget.php');
require_once('innomatic/wui/dispatch/WuiEventsCall.php');
require_once('innomatic/wui/dispatch/WuiEvent.php');
require_once('innomatic/wui/dispatch/WuiEventRawData.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('innomatic/locale/LocaleCatalog.php'); require_once('innomatic/locale/LocaleCountry.php'); 
require_once('shared/wui/WuiSessionkey.php');
require_once('innowork/groupware/InnoworkCompany.php');

    global $gLocale, $gPage_title, $gXml_def, $gPage_status;

function project_cdata($data) {
    return '<![CDATA['.$data.']]>';
}

require_once('innowork/core/InnoworkCore.php');
$gInnowork_core = InnoworkCore::instance('innoworkcore', 
    InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
    InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
    );

$gLocale = new LocaleCatalog(
    'innowork-groupware::projects_main',
    InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage()
    );

$gWui = Wui::instance('wui');
$gWui->loadWidget( 'xml' );
$gWui->loadWidget( 'innomaticpage' );
$gWui->loadWidget( 'innomatictoolbar' );
$gWui->loadWidget( 'table' );

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr( 'projects.title' );
$gCore_toolbars = $gInnowork_core->getMainToolBar(
    '',
    'project',
    isset(Wui::instance('wui')->parameters['wui']['view']['evd']['id'] ) ? Wui::instance('wui')->parameters['wui']['view']['evd']['id'] : ''
    );
$gToolbars['projects'] = array(
    'projects' => array(
        'label' => $gLocale->getStr( 'projects.toolbar' ),
        'themeimage' => 'view_icon',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
                array( 'done' => 'false' ) ) ) )
        ),
    'doneprojects' => array(
        'label' => $gLocale->getStr( 'doneprojects.toolbar' ),
        'themeimage' => 'view_icon',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            array( 'done' => 'true' ) ) ) )
        ),
    'newproject' => array(
        'label' => $gLocale->getStr( 'newproject.toolbar' ),
        'themeimage' => 'filenew',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'newproject',
            '' ) ) )
        )
    );

$gToolbars['stats'] = array(
    'stats' => array(
        'label' => $gLocale->getStr( 'statistics.toolbar' ),
        'themeimage' => 'frame_chart',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( 'innoworkprojects', array( array(
            'view',
            'stats',
            '' ) ) )
        )
    );

$gToolbars['prefs'] = array(
    'prefs' => array(
        'label' => $gLocale->getStr( 'preferences.toolbar' ),
        'themeimage' => 'configure',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( 'innoworkprojectsprefs', array( array(
            'view',
            'default',
            '' ) ) )
        )
    );

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher( 'action' );

$gAction_disp->addEvent(
    'newproject',
    'action_newproject'
    );
function action_newproject( $eventData )
{
    global $gLocale, $gPage_status;

    $innowork_project = new InnoworkProject(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );

    if ( $innowork_project->Create(
        $eventData,
        InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
        ) ) $gPage_status = $gLocale->getStr( 'project_added.status' );
    else $gPage_status = $gLocale->getStr( 'project_not_added.status' );
}

$gAction_disp->addEvent(
    'editproject',
    'action_editproject'
    );
function action_editproject( $eventData )
{
    global $gLocale, $gPage_status;

    $innowork_project = new InnoworkProject(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
        $eventData['id']
        );

    if ( $innowork_project->Edit(
        $eventData,
        InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
        ) ) $gPage_status = $gLocale->getStr( 'project_updated.status' );
    else $gPage_status = $gLocale->getStr( 'project_not_updated.status' );
}

$gAction_disp->addEvent(
    'removeproject',
    'action_removeproject'
    );
function action_removeproject( $eventData )
{
    global $gLocale, $gPage_status;

    $innowork_project = new InnoworkProject(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
        $eventData['id']
        );

    if ( $innowork_project->Remove(
        InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
        ) ) $gPage_status = $gLocale->getStr( 'project_removed.status' );
    else $gPage_status = $gLocale->getStr( 'project_not_removed.status' );
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher( 'view' );

function projects_list_action_builder( $pageNumber )
{
    return WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            array( 'pagenumber' => $pageNumber )
        ) ) );
}

$gMain_disp->addEvent(
    'default',
    'main_default'
    );
function main_default( $eventData )
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status;

    $innowork_customers = new InnoworkCompany(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );
    $search_results = $innowork_customers->Search(
        '',
        InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
        );

    $customers['0'] = $gLocale->getStr( 'nocustomer.label' );
    while ( list( $id, $fields ) = each( $search_results ) )
    {
        $customers[$id] = $fields['companyname'];
    }

    $statuses = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_STATUS );
    $statuses['0'] = $gLocale->getStr( 'nostatus.label' );

    $priorities = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_PRIORITY );
    $priorities['0'] = $gLocale->getStr( 'nopriority.label' );

    $types = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_TYPE );
    $types['0'] = $gLocale->getStr( 'notype.label' );

    // Filtering

    if ( isset($eventData['filter'] ) )
    {
        // Customer

        $customer_filter_sk = new WuiSessionKey(
            'customer_filter',
            array(
                'value' => $eventData['filter_customerid']
                )
            );

        if ( $eventData['filter_customerid'] != 0 ) $search_keys['customerid'] = $eventData['filter_customerid'];

        // Priority

        $priority_filter_sk = new WuiSessionKey(
            'priority_filter',
            array(
                'value' => $eventData['filter_priorityid']
                )
            );

        if ( $eventData['filter_priorityid'] != 0 ) $search_keys['priority'] = $eventData['filter_priorityid'];

        // Status

        $status_filter_sk = new WuiSessionKey(
            'status_filter',
            array(
                'value' => $eventData['filter_statusid']
                )
            );

        if ( $eventData['filter_statusid'] != 0 ) $search_keys['status'] = $eventData['filter_statusid'];

        // Type

        $type_filter_sk = new WuiSessionKey(
            'type_filter',
            array(
                'value' => $eventData['filter_typeid']
                )
            );

        if ( $eventData['filter_typeid'] != 0 ) $search_keys['type'] = $eventData['filter_typeid'];

    }
    else
    {
        // Customer

        $customer_filter_sk = new WuiSessionKey( 'customer_filter' );
        if (
            strlen( $customer_filter_sk->mValue )
            and $customer_filter_sk->mValue != 0
            ) $search_keys['customerid'] = $customer_filter_sk->mValue;
        $eventData['filter_customerid'] = $customer_filter_sk->mValue;

        // Priority

        $priority_filter_sk = new WuiSessionKey( 'priority_filter' );
        if (
            strlen( $priority_filter_sk->mValue )
            and $priority_filter_sk->mValue != 0
            ) $search_keys['priority'] = $priority_filter_sk->mValue;
        $eventData['filter_priorityid'] = $priority_filter_sk->mValue;

        // Status

        $status_filter_sk = new WuiSessionKey( 'status_filter' );
        if (
            strlen( $status_filter_sk->mValue )
            and $status_filter_sk->mValue != 0
            ) $search_keys['status'] = $status_filter_sk->mValue;
        $eventData['filter_statusid'] = $status_filter_sk->mValue;

        // Type

        $type_filter_sk = new WuiSessionKey( 'type_filter' );
        if (
            strlen( $type_filter_sk->mValue )
            and $type_filter_sk->mValue != 0
            ) $search_keys['type'] = $type_filter_sk->mValue;
        $eventData['filter_typeid'] = $type_filter_sk->mValue;
    }

    if ( !isset($search_keys ) or !count( $search_keys ) ) $search_keys = '';

    // Sorting

    $tab_sess = new WuiSessionKey( 'innoworkprojecttab' );

    if ( !isset($eventData['done'] ) ) $eventData['done'] = $tab_sess->mValue;
    if ( !strlen( $eventData['done'] ) ) $eventData['done'] = 'false';

    $tab_sess = new WuiSessionKey(
        'innoworkprojecttab',
        array(
            'value' => $eventData['done']
            )
        );

    $table = new WuiTable( 'projects_done_'.$eventData['done'], array(
        'sessionobjectusername' => $eventData['done'] == 'true' ? 'done' : 'undone'
        ) );
    $sort_by = 0;
    if ( strlen( $table->mSortDirection ) ) $sort_order = $table->mSortDirection;
    else $sort_order = 'down';

    if ( isset($eventData['sortby'] ) )
    {
        if ( $table->mSortBy == $eventData['sortby'] )
        {
            $sort_order = $sort_order == 'down' ? 'up' : 'down';
        }
        else
        {
            $sort_order = 'down';
        }

        $sort_by = $eventData['sortby'];
    }
    else
    {
        if ( strlen( $table->mSortBy ) ) $sort_by = $table->mSortBy;
    }

    $headers[0]['label'] = $gLocale->getStr( 'customer.header' );
    $headers[0]['link'] = WuiEventsCall::buildEventsCallString(
        '',
            array(
                array(
                    'view',
                    'default',
                    array(
                        'sortby' => '0'
                        )
                    )
                )
        );

    $headers[1]['label'] = $gLocale->getStr( 'name.header' );
    $headers[1]['link'] = WuiEventsCall::buildEventsCallString(
        '',
            array(
                array(
                    'view',
                    'default',
                    array(
                        'sortby' => '1'
                        )
                    )
                )
        );
    $headers[2]['label'] = $gLocale->getStr( 'priority.header' );
    $headers[2]['link'] = WuiEventsCall::buildEventsCallString( '',
            array( array(
                    'view',
                    'default',
                    array( 'sortby' => '2' )
                    ) ) );
    $headers[3]['label'] = $gLocale->getStr( 'status.header' );
    $headers[3]['link'] = WuiEventsCall::buildEventsCallString( '',
            array( array(
                    'view',
                    'default',
                    array( 'sortby' => '3' )
                    ) ) );
    $headers[4]['label'] = $gLocale->getStr( 'type.header' );
    $headers[4]['link'] = WuiEventsCall::buildEventsCallString( '',
            array( array(
                    'view',
                    'default',
                    array( 'sortby' => '4' )
                    ) ) );

    $projects = new InnoworkProject(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );

    switch ( $sort_by )
    {
    case '0':
        $projects->mSearchOrderBy = 'customerid'.( $sort_order == 'up' ? ' DESC' : '' ).',name';
        break;
    case '1':
        $projects->mSearchOrderBy = 'name'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '2':
        $projects->mSearchOrderBy = 'priority'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '3':
        $projects->mSearchOrderBy = 'status'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '4':
        $projects->mSearchOrderBy = 'type'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    }

        if (
            isset($eventData['done'] )
            and $eventData['done'] == 'true'
            )
        {
            $done_check = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue;
            $done_icon = 'undo';
            $done_action = 'false';
            $done_label = 'setundone.button';
        }
        else
        {
            $done_check = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse;
            $done_icon = 'redo';
            $done_action = 'true';
            $done_label = 'setdone.button';
        }

    $search_keys['done'] = $done_check;
    
    $search_results = $projects->Search(
        $search_keys,
        InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
        );
        
    $num_projects = count( $search_results );

        $gXml_def =
'<vertgroup><name>projects</name>
  <children>

    <label><name>filter</name>
      <args>
        <bold>true</bold>
        <label>'.$gLocale->getStr( 'filter.label' ).'</label>
      </args>
    </label>

    <form><name>filter</name>
      <args>
            <action>'.project_cdata( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    array(
                        'filter' => 'true'
                        )
                    )
            ) ) ).'</action>
      </args>
      <children>

        <grid>
          <children>

        <button row="0" col="4"><name>filter</name>
          <args>
            <themeimage>filter</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <formsubmit>filter</formsubmit>
            <label>'.$gLocale->getStr( 'filter.button' ).'</label>
            <action>'.project_cdata( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    array(
                        'filter' => 'true'
                        )
                    )
            ) ) ).'</action>
          </args>
        </button>

    <label row="0" col="0"><name>customer</name>
      <args>
        <label>'.$gLocale->getStr( 'filter_customer.label' ).'</label>
      </args>
    </label>
    <combobox row="0" col="1"><name>filter_customerid</name>
      <args>
        <disp>view</disp>
        <elements type="array">'.WuiXml::encode( $customers ).'</elements>
        <default>'.( isset($eventData['filter_customerid'] ) ? $eventData['filter_customerid'] : '' ).'</default>
      </args>
    </combobox>

    <label row="0" col="2">
      <args>
        <label>'.$gLocale->getStr( 'filter_priority.label' ).'</label>
      </args>
    </label>
    <combobox row="0" col="3"><name>filter_priorityid</name>
      <args>
        <disp>view</disp>
        <elements type="array">'.WuiXml::encode( $priorities ).'</elements>
        <default>'.(isset($eventData['filter_priorityid'] ) ? $eventData['filter_priorityid'] : '' ).'</default>
      </args>
    </combobox>

    <label row="1" col="2">
      <args>
        <label>'.$gLocale->getStr( 'filter_status.label' ).'</label>
      </args>
    </label>
    <combobox row="1" col="3"><name>filter_statusid</name>
      <args>
        <disp>view</disp>
        <elements type="array">'.WuiXml::encode( $statuses ).'</elements>
        <default>'.(isset($eventData['filter_statusid'] ) ? $eventData['filter_statusid'] : '' ).'</default>
      </args>
    </combobox>

    <label row="2" col="2">
      <args>
        <label>'.$gLocale->getStr( 'filter_type.label' ).'</label>
      </args>
    </label>
    <combobox row="2" col="3"><name>filter_typeid</name>
      <args>
        <disp>view</disp>
        <elements type="array">'.WuiXml::encode( $types ).'</elements>
        <default>'.(isset($eventData['filter_typeid'] ) ? $eventData['filter_typeid'] : '' ).'</default>
      </args>
    </combobox>

          </children>
        </grid>

      </children>
    </form>

    <horizbar/>

    <label><name>title</name>
      <args>
        <bold>true</bold>
        <label>'.( $gLocale->getStr(
            ( isset($eventData['done'] )
            and $eventData['done'] == 'true' ) ? 'doneprojects.label' : 'projects.label' ) ).'</label>
      </args>
    </label>

    <table><name>projects_done_'.$eventData['done'].'</name>
      <args>
        <headers type="array">'.WuiXml::encode( $headers ).'</headers>
        <rowsperpage>15</rowsperpage>
        <pagesactionfunction>projects_list_action_builder</pagesactionfunction>
        <pagenumber>'.( isset($eventData['pagenumber'] ) ? $eventData['pagenumber'] : '' ).'</pagenumber>
        <sessionobjectusername>'.( $eventData['done'] == 'true' ? 'done' : 'undone' ).'</sessionobjectusername>
        <sortby>'.$sort_by.'</sortby>
        <sortdirection>'.$sort_order.'</sortdirection>
        <rows>'.$num_projects.'</rows>
      </args>
      <children>
';

        $innowork_core = InnoworkCore::instance('innoworkcore', 
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
            );

        $summaries = $innowork_core->getSummaries();

        $row = 0;
        $page = 1;

                    if ( isset($eventData['pagenumber'] ) )
                    {
                        $page = $eventData['pagenumber'];
                    }
                    else
                    {
						require_once('shared/wui/WuiTable.php');
                    	
                        $table = new WuiTable(
                            'projects_done_'.$eventData['done'],
                            array(
                                'sessionobjectusername' => $eventData['done'] == 'true' ? 'done' : 'undone'
                                )
                            );

                        $page = $table->mPageNumber;
                    }

                    if ( $page > ceil( $num_projects / 15 ) ) $page = ceil( $num_projects / 15 );

                    $from = ( $page * 15 ) - 15;
                    $to = $from + 15 - 1;
        

        while ( list( $id, $fields ) = each( $search_results ) )
        {
if ( $row >= $from and $row <= $to )
{

            $innowork_customer = new InnoworkCompany(
                InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
                InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
                $fields['customerid']
                );

            $cust_data = $innowork_customer->getItem();

            if ( $fields['done'] == $done_check )
            {
                $gXml_def .=
'<link row="'.$row.'" col="0"><name>customer</name>
  <args>
    <link>'.project_cdata( WuiEventsCall::buildEventsCallString(
        $summaries['directorycompany']['domainpanel'],
        array(
            array(
                $summaries['directorycompany']['showdispatcher'],
                $summaries['directorycompany']['showevent'],
                array( 'id' => $fields['customerid'] )
                )
            )
        ) ).'</link>
    <label>'.project_cdata( $cust_data['companyname'] ).'</label>
  </args>
</link>
<vertgroup row="'.$row.'" col="1">
  <args>
    <compact>true</compact>
  </args>
  <children>

<link>
  <args>
    <label>'.project_cdata( $fields['name'] ).'</label>
    <bold>true</bold>
    <nowrap>true</nowrap>
    <compact>true</compact>
    <link>'.WuiEventsCall::buildEventsCallString( '', array( array(
                    'view',
                    'showproject',
                    array( 'id' => $id ) ) ) ).'</link>
    </args>
</link>
<label>
  <args>
    <label>'.project_cdata( $fields['description'] ).'</label>
    <nowrap>false</nowrap>
    <compact>true</compact>
  </args>
</label>
  </children>
</vertgroup>

<label row="'.$row.'" col="2">
  <args>
    <label>'.project_cdata( $priorities[$fields['priority']] ).'</label>
  </args>
</label>
<label row="'.$row.'" col="3">
  <args>
    <label>'.project_cdata( $statuses[$fields['status']] ).'</label>
  </args>
</label>
<label row="'.$row.'" col="4">
  <args>
    <label>'.project_cdata( $types[$fields['type']] ).'</label>
  </args>
</label>

<innomatictoolbar row="'.$row.'" col="5"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">'.WuiXml::encode( array(
        'view' => array(
            'show' => array(
                'label' => $gLocale->getStr( 'showproject.button' ),
                'themeimage' => 'zoom',
                'themeimagetype' => 'mini',
                'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array( array(
                    'view',
                    'showproject',
                    array( 'id' => $id ) ) ) )
                ),
            'done' => array(
                'label' => $gLocale->getStr( $done_label ),
                'themeimage' => $done_icon,
                'themeimagetype' => 'mini',
                'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    ''
                ),
                array(
                    'action',
                    'editproject',
                    array( 'id' => $id, 'done' => $done_action ) ) ) )
                ),
            'remove' => array(
                'label' => $gLocale->getStr( 'removeproject.button' ),
                'themeimage' => 'trash',
                'themeimagetype' => 'mini',
                'horiz' => 'true',
                'needconfirm' => 'true',
                'confirmmessage' => $gLocale->getStr( 'removeproject.confirm' ),
                'action' => WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                    ),
                    array(
                        'action',
                        'removeproject',
                        array( 'id' => $id ) ) ) )
        ) ) ) ).'</toolbars>
  </args>
</innomatictoolbar>';
                }
            }
            $row++;
        }

        $gXml_def .=
'      </children>
    </table>
  </children>
</vertgroup>';

}

$gMain_disp->addEvent(
    'newproject',
    'main_newproject'
    );
function main_newproject( $eventData )
{
    global $gXml_def, $gLocale, $gPage_title;

        $innowork_companies = new InnoworkCompany(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
            );
        $search_results = $innowork_companies->Search(
            '',
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
            );

        $companies['0'] = $gLocale->getStr( 'nocompany.label' );

        while ( list( $id, $fields ) = each( $search_results ) )
        {
            $companies[$id] = $fields['companyname'];
        }

    $users_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
        'SELECT id,fname,lname,username '.
        'FROM domain_users '.
        'WHERE username<>'.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( User::getAdminUsername(InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDomainId())).' '.
        'ORDER BY lname,fname' );

    $users = array();

    while ( !$users_query->eof )
    {
        $users[$users_query->getFields( 'id' )] = $users_query->getFields( 'lname' ).
            ' '.$users_query->getFields( 'fname' ).
            ' ('.$users_query->getFields( 'username' ).')';

        $users_query->moveNext();
    }

    $statuses = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_STATUS );
    $statuses['0'] = $gLocale->getStr( 'nostatus.label' );

    $priorities = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_PRIORITY );
    $priorities['0'] = $gLocale->getStr( 'nopriority.label' );

    $types = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_TYPE );
    $types['0'] = $gLocale->getStr( 'notype.label' );

    $gXml_def .=
'<vertgroup><name>newproject</name>
  <children>

    <table><name>project</name>
      <args>
        <headers type="array">'.WuiXml::encode(
            array( '0' => array(
                'label' => $gLocale->getStr( 'newproject.label' )
                ) ) ).'</headers>
      </args>
      <children>
    
    <form row="0" col="0"><name>project</name>
      <args>
        <method>post</method>
        <action>'.project_cdata( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    ''
                    ),
                array(
                    'action',
                    'newproject',
                    '' )
            ) ) ).'</action>
      </args>
      <children>

        <horizgroup><name>project</name>
          <children>

            <label><name>name</name>
              <args>
                <label>'.$gLocale->getStr( 'name.label' ).'</label>
              </args>
            </label>
            <string><name>name</name>
              <args>
                <disp>action</disp>
                <size>30</size>
              </args>
            </string>

          </children>
        </horizgroup>

        <horizgroup><name>project</name>
          <children>

            <label><name>description</name>
              <args>
                <label>'.( $gLocale->getStr( 'description.label' ) ).'</label>
              </args>
            </label>
          
          </children>
        </horizgroup>

        <horizgroup><name>project</name>
          <children>

            <text><name>description</name>
              <args>
                <disp>action</disp>
                <cols>80</cols>
                <rows>7</rows>
              </args>
            </text>

          </children>
        </horizgroup>

        <horizgroup><name>project</name>
          <children>';


                $gXml_def .=
'            <label><name>company</name>
              <args>
                <label>'.( $gLocale->getStr( 'customer.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>customerid</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $companies ).'</elements>
              </args>
            </combobox>';

            $gXml_def .=
'            <label><name>responsible</name>
              <args>
                <label>'.( $gLocale->getStr( 'responsible.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>responsible</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $users ).'</elements>
              </args>
            </combobox>

          </children>
        </horizgroup>

        <horizbar><name>hb</name></horizbar>

        <label><name>contact</name>
          <args>
            <bold>true</bold>
            <label>'.( $gLocale->getStr( 'parameters.label' ) ).'</label>
          </args>
        </label>

        <horizgroup><name>project</name>
          <children>

            <label><name>status</name>
              <args>
                <label>'.( $gLocale->getStr( 'status.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>status</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $statuses ).'</elements>
              </args>
            </combobox>

            <label><name>priority</name>
              <args>
                <label>'.( $gLocale->getStr( 'priority.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>priority</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $priorities ).'</elements>
              </args>
            </combobox>

            <label><name>type</name>
              <args>
                <label>'.( $gLocale->getStr( 'type.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>type</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $types ).'</elements>
              </args>
            </combobox>

          </children>
        </horizgroup>

        <horizbar><name>hb</name></horizbar>

        <label><name>estimated</name>
          <args>
            <bold>true</bold>
            <label>'.( $gLocale->getStr( 'estimated.label' ) ).'</label>
          </args>
        </label>

        <horizgroup><name>estimated</name>
          <children>

            <label><name>estimatedstartdate</name>
              <args>
                <label>'.( $gLocale->getStr( 'estimatedstartdate.label' ) ).'</label>
              </args>
            </label>
            <date><name>estimatedstartdate</name>
              <args>
                <disp>action</disp>
              </args>
            </date>

            <label><name>estimatedenddate</name>
              <args>
                <label>'.( $gLocale->getStr( 'estimatedenddate.label' ) ).'</label>
              </args>
            </label>
            <date><name>estimatedenddate</name>
              <args>
                <disp>action</disp>
                <size>25</size>
              </args>
            </date>

            <label><name>estimatedtime</name>
              <args>
                <label>'.( $gLocale->getStr( 'estimatedtime.label' ) ).'</label>
              </args>
            </label>
            <string><name>estimatedtime</name>
              <args>
                <disp>action</disp>
                <size>5</size>
              </args>
            </string>

            <label><name>estimatedcost</name>
              <args>
                <label>'.( $gLocale->getStr( 'estimatedcost.label' ) ).'</label>
              </args>
            </label>
            <string><name>estimatedcost</name>
              <args>
                <disp>action</disp>
                <size>7</size>
              </args>
            </string>

            <label><name>estimatedrevenue</name>
              <args>
                <label>'.( $gLocale->getStr( 'estimatedrevenue.label' ) ).'</label>
              </args>
            </label>
            <string><name>estimatedrevenue</name>
              <args>
                <disp>action</disp>
                <size>7</size>
              </args>
            </string>

          </children>
        </horizgroup>

        <horizbar><name>hb</name></horizbar>

        <label><name>real</name>
          <args>
            <bold>true</bold>
            <label>'.( $gLocale->getStr( 'real.label' ) ).'</label>
          </args>
        </label>

        <horizgroup><name>real</name>
          <children>

            <label><name>realstartdate</name>
              <args>
                <label>'.( $gLocale->getStr( 'realstartdate.label' ) ).'</label>
              </args>
            </label>
            <date><name>realstartdate</name>
              <args>
                <disp>action</disp>
                <size>25</size>
              </args>
            </date>

            <label><name>realenddate</name>
              <args>
                <label>'.( $gLocale->getStr( 'realenddate.label' ) ).'</label>
              </args>
            </label>
            <date><name>realenddate</name>
              <args>
                <disp>action</disp>
                <size>25</size>
              </args>
            </date>

            <label><name>realtime</name>
              <args>
                <label>'.( $gLocale->getStr( 'realtime.label' ) ).'</label>
              </args>
            </label>
            <string><name>realtime</name>
              <args>
                <disp>action</disp>
                <size>5</size>
              </args>
            </string>

            <label><name>realcost</name>
              <args>
                <label>'.( $gLocale->getStr( 'realcost.label' ) ).'</label>
              </args>
            </label>
            <string><name>realcost</name>
              <args>
                <disp>action</disp>
                <size>7</size>
              </args>
            </string>

            <label><name>realrevenue</name>
              <args>
                <label>'.( $gLocale->getStr( 'realrevenue.label' ) ).'</label>
              </args>
            </label>
            <string><name>realrevenue</name>
              <args>
                <disp>action</disp>
                <size>7</size>
              </args>
            </string>

          </children>
        </horizgroup>

        </children>
        </form>

        <button row="1" col="0"><name>apply</name>
          <args>
            <themeimage>buttonok</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action>'.project_cdata( WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'newproject',
                        '' )
                ) ) ).'</action>
            <label>'.( $gLocale->getStr( 'newproject.submit' ) ).'</label>
            <formsubmit>project</formsubmit>
          </args>
        </button>

      </children>
    </table>
  </children>
</vertgroup>';
}

$gMain_disp->addEvent(
    'showproject',
    'main_showproject'
    );
function main_showproject( $eventData )
{
    global $gXml_def, $gLocale, $gPage_title;

    $innowork_project = new InnoworkProject(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
        $eventData['id']
        );

    $pj_data = $innowork_project->getItem( InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId() );

        $innowork_companies = new InnoworkCompany(
            InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
            InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
            );
        $search_results = $innowork_companies->Search(
            '',
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
            );

        $companies['0'] = $gLocale->getStr( 'nocompany.label' );

        while ( list( $id, $fields ) = each( $search_results ) )
        {
            $companies[$id] = $fields['companyname'];
        }

    $users_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
        'SELECT id,fname,lname,username '.
        'FROM domain_users '.
        'WHERE username<>'.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(User::getAdminUsername(InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDomainId())).' '.
        'ORDER BY lname,fname' );

    $users = array();

    while ( !$users_query->eof )
    {
        $users[$users_query->getFields( 'id' )] = $users_query->getFields( 'lname' ).
            ' '.$users_query->getFields( 'fname' ).
            ' ('.$users_query->getFields( 'username' ).')';

        $users_query->moveNext();
    }

    $statuses = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_STATUS );
    $statuses['0'] = $gLocale->getStr( 'nostatus.label' );

    $priorities = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_PRIORITY );
    $priorities['0'] = $gLocale->getStr( 'nopriority.label' );

    $types = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_TYPE );
    $types['0'] = $gLocale->getStr( 'notype.label' );

    $country = new LocaleCountry( InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry() );
    $empty_date_array = $country->getDateArrayFromShortDateStamp( '' );
    $empty_date_text = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getTimestampFromDateArray( $empty_date_array );

        if ( $pj_data['done'] == InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue )
        {
            $done_icon = 'undo';
            $done_action = 'false';
            $done_label = 'setundone.button';
        }
        else
        {
            $done_icon = 'redo';
            $done_action = 'true';
            $done_label = 'setdone.button';
        }

    $gXml_def .=
'<horizgroup>
  <children>

<vertgroup><name>editproject</name>
  <children>

    <table><name>project</name>
      <args>
        <headers type="array">'.WuiXml::encode(
            array( '0' => array(
                'label' => $gLocale->getStr( 'editproject.label' )
                ) ) ).'</headers>
      </args>
      <children>

    <form row="0" col="0"><name>project</name>
      <args>
        <method>post</method>
        <action>'.project_cdata( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    ''
                    ),
                array(
                    'action',
                    'editproject',
                    array( 'id' => $eventData['id'] ) )
            ) ) ).'</action>
      </args>
      <children>

        <horizgroup><name>project</name>
          <children>

            <label><name>name</name>
              <args>
                <label>'.( $gLocale->getStr( 'name.label' ) ).'</label>
              </args>
            </label>
            <string><name>name</name>
              <args>
                <disp>action</disp>
                <size>30</size>
                <value>'.project_cdata( $pj_data['name'] ).'</value>
              </args>
            </string>

          </children>
        </horizgroup>

        <horizgroup><name>project</name>
          <children>

            <label><name>description</name>
              <args>
                <label>'.( $gLocale->getStr( 'description.label' ) ).'</label>
              </args>
            </label>

          </children>
        </horizgroup>

        <horizgroup><name>project</name>
          <children>

            <text><name>description</name>
              <args>
                <disp>action</disp>
                <cols>80</cols>
                <rows>7</rows>
                <value>'.project_cdata( $pj_data['description'] ).'</value>
              </args>
            </text>

          </children>
        </horizgroup>

        <horizgroup><name>project</name>
          <children>';

                $gXml_def .=
'            <label><name>company</name>
              <args>
                <label>'.( $gLocale->getStr( 'customer.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>customerid</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $companies ).'</elements>
                <default>'.$pj_data['customerid'].'</default>
              </args>
            </combobox>';


            $gXml_def .=
'            <label><name>responsible</name>
              <args>
                <label>'.( $gLocale->getStr( 'responsible.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>responsible</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $users ).'</elements>
                <default>'.$pj_data['responsible'].'</default>
              </args>
            </combobox>

          </children>
        </horizgroup>

        <horizbar><name>hb</name></horizbar>

        <label><name>contact</name>
          <args>
            <bold>true</bold>
            <label>'.( $gLocale->getStr( 'parameters.label' ) ).'</label>
          </args>
        </label>

        <horizgroup><name>project</name>
          <children>

            <label><name>status</name>
              <args>
                <label>'.( $gLocale->getStr( 'status.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>status</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $statuses ).'</elements>
                <default>'.$pj_data['status'].'</default>
              </args>
            </combobox>

            <label><name>priority</name>
              <args>
                <label>'.( $gLocale->getStr( 'priority.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>priority</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $priorities ).'</elements>
                <default>'.$pj_data['priority'].'</default>
              </args>
            </combobox>

            <label><name>type</name>
              <args>
                <label>'.( $gLocale->getStr( 'type.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>type</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $types ).'</elements>
                <default>'.$pj_data['type'].'</default>
              </args>
            </combobox>

          </children>
        </horizgroup>

        <horizbar><name>hb</name></horizbar>

        <label><name>estimated</name>
          <args>
            <bold>true</bold>
            <label>'.( $gLocale->getStr( 'estimated.label' ) ).'</label>
          </args>
        </label>

        <horizgroup><name>estimated</name>
          <children>

            <label><name>estimatedstartdate</name>
              <args>
                <label>'.( $gLocale->getStr( 'estimatedstartdate.label' ) ).'</label>
              </args>
            </label>
            <date><name>estimatedstartdate</name>
              <args>
                <disp>action</disp>
                '.( $pj_data['estimatedstartdate'] != $empty_date_text ? '<value type="array">'.WuiXml::encode( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getDateArrayFromTimestamp( $pj_data['estimatedstartdate'] ) ).'</value>' : '' ).'
              </args>
            </date>

            <label><name>estimatedenddate</name>
              <args>
                <label>'.( $gLocale->getStr( 'estimatedenddate.label' ) ).'</label>
              </args>
            </label>
            <date><name>estimatedenddate</name>
              <args>
                <disp>action</disp>
                <size>25</size>
                '.( $pj_data['estimatedenddate'] != $empty_date_text ? '<value type="array">'.WuiXml::encode( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getDateArrayFromTimestamp( $pj_data['estimatedenddate'] ) ).'</value>' : '' ).'
              </args>
            </date>

            <label><name>estimatedtime</name>
              <args>
                <label>'.( $gLocale->getStr( 'estimatedtime.label' ) ).'</label>
              </args>
            </label>
            <string><name>estimatedtime</name>
              <args>
                <disp>action</disp>
                <size>5</size>
                <value>'.project_cdata( $pj_data['estimatedtime'] ).'</value>
              </args>
            </string>

            <label><name>estimatedcost</name>
              <args>
                <label>'.( $gLocale->getStr( 'estimatedcost.label' ) ).'</label>
              </args>
            </label>
            <string><name>estimatedcost</name>
              <args>
                <disp>action</disp>
                <size>7</size>
                <value>'.project_cdata( $pj_data['estimatedcost'] ).'</value>
              </args>
            </string>

            <label><name>estimatedrevenue</name>
              <args>
                <label>'.( $gLocale->getStr( 'estimatedrevenue.label' ) ).'</label>
              </args>
            </label>
            <string><name>estimatedrevenue</name>
              <args>
                <disp>action</disp>
                <size>7</size>
                <value>'.project_cdata( $pj_data['estimatedrevenue'] ).'</value>
              </args>
            </string>

          </children>
        </horizgroup>

        <horizbar><name>hb</name></horizbar>

        <label><name>real</name>
          <args>
            <bold>true</bold>
            <label>'.( $gLocale->getStr( 'real.label' ) ).'</label>
          </args>
        </label>

        <horizgroup><name>real</name>
          <children>

            <label><name>realstartdate</name>
              <args>
                <label>'.( $gLocale->getStr( 'realstartdate.label' ) ).'</label>
              </args>
            </label>
            <date><name>realstartdate</name>
              <args>
                <disp>action</disp>
                <size>25</size>
                '.( $pj_data['realstartdate'] != $empty_date_text ? '<value type="array">'.WuiXml::encode( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getDateArrayFromTimestamp( $pj_data['realstartdate'] ) ).'</value>' : '' ).'
              </args>
            </date>

            <label><name>realenddate</name>
              <args>
                <label>'.( $gLocale->getStr( 'realenddate.label' ) ).'</label>
              </args>
            </label>
            <date><name>realenddate</name>
              <args>
                <disp>action</disp>
                <size>25</size>
                '.( $pj_data['realenddate'] != $empty_date_text ? '<value type="array">'.WuiXml::encode( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getDateArrayFromTimestamp( $pj_data['realenddate'] ) ).'</value>' : '' ).'
              </args>
            </date>

            <label><name>realtime</name>
              <args>
                <label>'.( $gLocale->getStr( 'realtime.label' ) ).'</label>
              </args>
            </label>
            <string><name>realtime</name>
              <args>
                <disp>action</disp>
                <size>5</size>
                <value>'.project_cdata( $pj_data['realtime'] ).'</value>
              </args>
            </string>

            <label><name>realcost</name>
              <args>
                <label>'.( $gLocale->getStr( 'realcost.label' ) ).'</label>
              </args>
            </label>
            <string><name>realcost</name>
              <args>
                <disp>action</disp>
                <size>7</size>
                <value>'.project_cdata( $pj_data['realcost'] ).'</value>
              </args>
            </string>

            <label><name>realrevenue</name>
              <args>
                <label>'.( $gLocale->getStr( 'realrevenue.label' ) ).'</label>
              </args>
            </label>
            <string><name>realrevenue</name>
              <args>
                <disp>action</disp>
                <size>7</size>
                <value>'.project_cdata( $pj_data['realrevenue'] ).'</value>
              </args>
            </string>

          </children>
        </horizgroup>

        </children>
        </form>

        <horizgroup row="1" col="0"><name>tools</name>
          <children>

        <button><name>apply</name>
          <args>
            <themeimage>buttonok</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action>'.project_cdata( WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'editproject',
                        array( 'id' => $eventData['id'] ) )
                ) ) ).'</action>
            <label>'.( $gLocale->getStr( 'editproject.submit' ) ).'</label>
            <formsubmit>project</formsubmit>
          </args>
        </button>

        <button><name>setdone</name>
          <args>
            <themeimage>'.$done_icon.'</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action>'.project_cdata( WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'editproject',
                        array(
                            'id' => $eventData['id'],
                            'done' => $done_action
                            ) )
                ) ) ).'</action>
            <label>'.project_cdata( $gLocale->getStr( $done_label ) ).'</label>
            <formsubmit>project</formsubmit>
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
      <itemtype>project</itemtype>
      <itemid>'.$eventData['id'].'</itemid>
      <itemownerid>'.$pj_data['ownerid'].'</itemownerid>
      <defaultaction>'.project_cdata( WuiEventsCall::buildEventsCallString( '', array(
        array( 'view', 'showproject', array( 'id' => $eventData['id'] ) ) ) ) ).'</defaultaction>
    </args>
  </innoworkitemacl>

  </children>
</horizgroup>';
}

function fields_tab_action_builder( $tab )
{
    return WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'stats',
            array( 'tab' => $tab )
        ) ) );
}

$gMain_disp->addEvent(
    'stats',
    'main_stats'
    );
function main_stats(
    $eventData
    )
{
    global $gXml_def, $gLocale;

    $projects = new InnoworkProject(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );
    $projects_search = $projects->Search(
        '',
        InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
        );

    $tabs[0]['label'] = $gLocale->getStr( 'status.tab' );
    $tabs[1]['label'] = $gLocale->getStr( 'type.tab' );
    $tabs[2]['label'] = $gLocale->getStr( 'priority.tab' );

    foreach ( $projects_search as $id => $project_data )
    {
        $_status_data[$project_data['status']]++;
        $_type_data[$project_data['type']]++;
        $_priority_data[$project_data['priority']]++;
    }

    $statuses_list = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_STATUS );
    $status_data[1][] = 0;

    foreach ( $_status_data as $status => $number )
    {
        $status_data[1][] = $number;
        $status_legend[] = $statuses_list[$status];
    }

    $types_list = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_TYPE );
    $type_data[1][] = 0;

    foreach ( $_type_data as $type => $number )
    {
        $type_data[1][] = $number;
        $type_legend[] = $types_list[$type];
    }

    $priorities_list = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_PRIORITY );
    $priority_data[1][] = 0;

    foreach ( $_priority_data as $priority => $number )
    {
        $priority_data[1][] = $number;
        $priority_legend[] = $priorities_list[$priority];
    }

    $gXml_def =
'<vertgroup>
  <children>

        <label row="0" col="1">
          <args>
            <label>'.( $gLocale->getStr( 'statistics.label' ) ).'</label>
            <bold>true</bold>
          </args>
        </label>

    <tab><name>fieldsvalues</name>
      <args>
        <tabs type="array">'.WuiXml::encode( $tabs ).'</tabs>
        <tabactionfunction>fields_tab_action_builder</tabactionfunction>
        <activetab>'.( isset($eventData['tab'] ) ? $eventData['tab'] : '' ).'</activetab>
      </args>
      <children>

        <vertgroup>
          <args>
            <align>center</align>
          </args>
          <children>

        <label row="0" col="0">
          <args>
            <label>'.( $gLocale->getStr( 'status_stats.label' ) ).'</label>
            <bold>true</bold>
          </args>
        </label>

        <phplot row="1" col="0">
          <args>
            <data type="array">'.WuiXml::encode( $status_data ).'</data>
            <width>500</width>
            <height>300</height>
            <linewidth>0</linewidth>
            <plottype>pie</plottype>
            <legend type="array">'.WuiXml::encode( $status_legend ).'</legend>
          </args>
        </phplot>

          </children>
        </vertgroup>

        <vertgroup>
          <args>
            <align>center</align>
          </args>
          <children>

        <label row="0" col="1">
          <args>
            <label>'.( $gLocale->getStr( 'type_stats.label' ) ).'</label>
            <bold>true</bold>
          </args>
        </label>

        <phplot row="1" col="1">
          <args>
            <data type="array">'.WuiXml::encode( $type_data ).'</data>
            <width>500</width>
            <height>300</height>
            <linewidth>0</linewidth>
            <plottype>pie</plottype>
            <legend type="array">'.WuiXml::encode( $type_legend ).'</legend>
          </args>
        </phplot>

          </children>
        </vertgroup>

        <vertgroup>
          <args>
            <align>center</align>
          </args>
          <children>

        <label row="0" col="2">
          <args>
            <label>'.( $gLocale->getStr( 'priority_stats.label' ) ).'</label>
            <bold>true</bold>
          </args>
        </label>

        <phplot row="1" col="2">
          <args>
            <data type="array">'.WuiXml::encode( $priority_data ).'</data>
            <width>500</width>
            <height>300</height>
            <linewidth>0</linewidth>
            <plottype>pie</plottype>
            <legend type="array">'.WuiXml::encode( $priority_legend ).'</legend>
          </args>
        </phplot>

          </children>
        </vertgroup>

      </children>
    </tab>

  </children>
</vertgroup>';
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//
$gWui->addChild( new WuiInnomaticPage( 'page', array(
    'pagetitle' => $gPage_title,
    'icon' => 'tar',
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
