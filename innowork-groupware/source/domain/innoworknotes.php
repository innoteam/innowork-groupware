<?php
global $gPage_status, $gLocale, $gPage_content;

require_once('innowork/groupware/InnoworkNote.php');
require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/widgets/WuiWidget.php');
require_once('innomatic/wui/widgets/WuiContainerWidget.php');
require_once('innomatic/wui/dispatch/WuiEventsCall.php');
require_once('innomatic/wui/dispatch/WuiEvent.php');
require_once('innomatic/wui/dispatch/WuiEventRawData.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('innomatic/locale/LocaleCatalog.php');
require_once('innomatic/locale/LocaleCountry.php'); 

require_once('innowork/core/InnoworkCore.php');
$innowork_core = InnoworkCore::instance('innoworkcore', 
    InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
    InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
    );

$gLocale = new LocaleCatalog(
    'innowork-groupware::notes_misc',
    InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage()
    );

$wui = Wui::instance('wui');
$wui->loadWidget( 'xml' );
$wui->loadWidget( 'innomaticpage' );
$wui->loadWidget( 'innomatictoolbar' );
$wui->loadWidget( 'innoworknote' );
$wui->loadWidget( 'innoworknoteslist' );

$gPage_content = $gPage_status = '';
$gPage_title = $gLocale->getStr( 'notes.title' );

// Action dispatcher

$action_disp = new WuiDispatcher( 'action' );

$action_disp->addEvent( 'savenote', 'action_savenote' );
function action_savenote( $eventData )
{
    global $gPage_status, $gLocale;

    $innowork_note = new InnoworkNote( InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $eventData['id'] );
    if ( isset($eventData['id'] ) and $eventData['id'] ) $result = $innowork_note->Edit( $eventData, InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId() );
    else $result = $innowork_note->Create( $eventData, InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId() );

    if ( $result ) $gPage_status = $gLocale->getStr( 'notesaved.status' );
    else $gPage_status = $gLocale->getStr( 'notenotsaved.status' );
}

$action_disp->addEvent( 'deletenote', 'action_deletenote' );
function action_deletenote( $eventData )
{
    global $gPage_status, $gLocale;

    $innowork_note = new InnoworkNote( InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $eventData['id'] );
    if ( isset($eventData['id'] ) and $eventData['id'] ) $result = $innowork_note->trash( InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId() );

    if ( $result ) $gPage_Status = $gLocale->getStr( 'notedeleted.status' );
    else $gPage_status = $gLocale->getStr( 'notenotdeleted.status' );
}

$action_disp->Dispatch();

// Main dispatcher

$main_disp = new WuiDispatcher( 'view' );

$main_disp->addEvent( 'default', 'main_default' );
function main_default( $eventData )
{
    global $gPage_content, $gLocale;

    $notes = new InnoworkNote(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()
        );
    $search_result = $notes->Search(
        '',
        InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
        );

    $notes = array();

    while ( list( $id, $fields ) = each( $search_result ) )
    {
        $notes[$id]['title'] = $fields['title'];
        $notes[$id]['text'] = $fields['content'];
    }

    $gPage_content = new WuiInnoworkNotesList( 'innoworknoteslist', array(
        'notes' => $notes,
        'viewmode' => isset($eventData['viewmode'] ) ? $eventData['viewmode'] : '',
        'disp' => 'action',
        'saveaction' => WuiEventsCall::buildEventsCallString( '', array( array( 'view', 'default', array( 'viewmode' => isset($eventData['viewmode'] ) ? $eventData['viewmode'] : '' ) ), array( 'action', 'savenote', '' ) ) ),
        'closeaction' => WuiEventsCall::buildEventsCallString( '', array( array( 'view', 'default', array( 'viewmode' => isset($eventData['viewmode'] ) ? $eventData['viewmode'] : '' ) ) ) ),
        'deleteaction' => WuiEventsCall::buildEventsCallString( '', array( array( 'view', 'default', array( 'viewmode' => isset($eventData['viewmode'] ) ? $eventData['viewmode'] : '' ) ), array( 'action', 'deletenote', '' ) ) )
        ) );
}

$main_disp->addEvent( 'editnote', 'main_editnote' );
function main_editnote( $eventData )
{
    global $gPage_content;

    $notes_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
        'SELECT * '.
        'FROM innowork_notes '.
        'WHERE id='.$eventData['id'] );

    require_once('innowork/core/InnoworkAcl.php');
    $tmp_acl = new InnoworkAcl(
        InnomaticContainer::instance('innomaticcontainer')->getDataAccess(),
        InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(),
        InnoworkNote::ITEM_TYPE,
        $eventData['id']
        );
    if (
        $notes_query->getFields( 'ownerid' ) == InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
        or $tmp_acl->checkPermission(
            '',
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()
            ) >= InnoworkAcl::PERMS_SEARCH
        )
    {
        if ( $notes_query->getNumberRows() )
        {
            $xml_def =
'<horizgroup><name>hg</name><children>
  <innoworknote><name>innoworknote</name>
    <args>
      <noteid>'.$eventData['id'].'</noteid>
      <disp>action</disp>
      <text type="encoded">'.urlencode( $notes_query->getFields( 'content' ) ).'</text>
      <edit>true</edit>
      <title type="encoded">'.urlencode( $notes_query->getFields( 'title' ) ).'</title>
      <saveaction type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
        array( 'view', 'editnote', array( 'viewmode' => isset($eventData['viewmode'] ) ? $eventData['viewmode'] : '', 'id' => $eventData['id'] ) ),
        array( 'action', 'savenote', '' ) ) ) ).'</saveaction>
      <deleteaction type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
        array( 'view', 'default', array( 'viewmode' => isset($eventData['viewmode'] ) ? $eventData['viewmode'] : '' ) ),
        array( 'action', 'deletenote', '' ) ) ) ).'</deleteaction>
      <closeaction type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
        array( 'view', 'default', array( 'viewmode' => isset($eventData['viewmode'] ) ? $eventData['viewmode'] : '' ) )
        ) ) ).'</closeaction>
    </args>
  </innoworknote>
  <innoworkitemacl><name>itemacl</name>
    <args>
      <itemtype>note</itemtype>
      <itemid>'.$eventData['id'].'</itemid>
      <itemownerid>'.$notes_query->getFields( 'ownerid' ).'</itemownerid>
      <defaultaction type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
        array( 'view', 'editnote', array( 'id' => $eventData['id'] ) ) ) ) ).'</defaultaction>
    </args>
  </innoworkitemacl>
</children></horizgroup>';

            $gPage_content = new WuiXml( 'page', array( 'definition' => $xml_def ) );
        }
    }
}

$main_disp->addEvent( 'newnote', 'main_newnote' );
function main_newnote( $eventData )
{
    global $gPage_content;

    $gPage_content = new WuiInnoworkNote( 'innoworknote', array(
                                                      'new' => 'true',
                                                      'disp' => 'action',
                                                      'saveaction' => WuiEventsCall::buildEventsCallString( '', array( array( 'view', 'default', array( 'viewmode' => $eventData['viewmode'] ) ), array( 'action', 'savenote', '' ) ) )
                                                     ) );
}

$main_disp->Dispatch();

$toolbars = $innowork_core->getMainToolBar();

$notes_toolbars['notes'] = array(
    'notesbyicon' => array(
        'label' => $gLocale->getStr( 'noteslist.button' ),
        'themeimage' => 'view_icon',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            array( 'viewmode' => 'byicon' ) ) ) )
        ),
    'notesbynote' => array(
        'label' => $gLocale->getStr( 'allnotes.button' ),
        'themeimage' => 'edit',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default', array( 'viewmode' => 'bynote' ) ) ) )
        ),
    'newnote' => array(
        'label' => $gLocale->getStr( 'newnote.button' ),
        'themeimage' => 'filenew',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'newnote',
            '' ) ) )
    ) );

$innomatictoolbars = array(
                     new WuiInnomaticToolBar( 'view', array( 'toolbars' => $toolbars, 'toolbar' => 'true' ) ),
                     new WuiInnomaticToolBar( 'notes', array( 'toolbars' => $notes_toolbars, 'toolbar' => 'true' ) )
                    );

$wui->addChild(
    new WuiInnomaticPage(
        'page',
        array(
            'pagetitle' => $gPage_title,
            'icon' => 'txt',
            'toolbars' => $innomatictoolbars,
            'maincontent' => $gPage_content,
            'status' => $gPage_status  ) )
    );
$wui->render();

?>
