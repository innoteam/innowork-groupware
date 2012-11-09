<?php

require_once('shared/wui/WuiXml.php');

/*!
 @class WuiInnoworkNote

 @abstract Note widget.
 */
class WuiInnoworkNote extends WuiXml {
    var $mNoteId;
    var $mSaveAction;
    var $mCloseAction;
    var $mDeleteAction;
    var $mTitle;
    var $mText;
    var $mNew;
    var $mEdit;
    var $mDisp;

    public function __construct( $elemName, $elemArgs = '', $elemTheme = '', $dispEvents = '' ) {
        parent::__construct( $elemName, $elemArgs, $elemTheme, $dispEvents );

        $this->mNoteId = isset($this->mArgs['noteid'] ) ? $this->mArgs['noteid'] : '';
        $this->mTitle = isset($this->mArgs['title'] ) ? $this->mArgs['title'] : '';
        $this->mText = isset($this->mArgs['text'] ) ? $this->mArgs['text'] : '';
        $this->mSaveAction = $this->mArgs['saveaction'];
        $this->mCloseAction = $this->mArgs['closeaction'];
        $this->mDeleteAction = $this->mArgs['deleteaction'];
        $this->mDisp = $this->mArgs['disp'];

        if (
            isset($this->mArgs['new'] )
            and
            (
                $this->mArgs['new'] == 'true'
                or
                $this->mArgs['new'] == 'false'
            )
           )
            $this->mNew = $this->mArgs['new'];
        else $this->mNew = 'false';

        if (
            isset($this->mArgs['edit'] )
            and
            (
                $this->mArgs['edit'] == 'true'
                or
                $this->mArgs['edit'] == 'false'
            )
           )
            $this->mEdit = $this->mArgs['edit'];
        else $this->mEdit = 'false';

        $this->_FillDefinition();
    }

    function _FillDefinition() {
        $result = FALSE;

        require_once('innomatic/locale/LocaleCatalog.php'); require_once('innomatic/locale/LocaleCountry.php'); 

        

        $locale = new LocaleCatalog( 'innowork-groupware::notes_widget', InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage() );

        $noteform = 'noteform'.md5( microtime() );

        $this->mDefinition =
'<?xml version="1.0"?>
<vertframe>
  <name>note</name>
            <args><bgcolor>#ffff66</bgcolor></args>
  <children>

  <form><name>'.$noteform.'</name>
    <args>
      <method>POST</method>
      <action type="encoded">'.urlencode( $this->mSaveAction ).'</action>
    </args>
    <children>

    <vertgroup>
      <children>

      <formarg><name>id</name>
        <args>
          <value>'.$this->mNoteId.'</value>
          <disp>'.$this->mDisp.'</disp>
        </args>
      </formarg>';

        if ( $this->mNew == 'true' or $this->mEdit == 'true' ) $this->mDefinition .=
'<string><name>title</name>
  <args>
    <disp>'.$this->mDisp.'</disp>
    <size>40</size>
    <value type="encoded">'.urlencode( $this->mTitle ).'</value>
    <bgcolor>#ffff66</bgcolor>
  </args>
</string>';
        else $this->mDefinition .=
'<label><name>title</name>
  <args>
    <label type="encoded">'.urlencode( '<strong>'.$this->mTitle.'</strong>' ).'</label>
  </args>
</label>';

        $this->mDefinition .=
'<text><name>content</name>
  <args>
    <disp>'.$this->mDisp.'</disp>
    <bgcolor>#ffff66</bgcolor>
    <cols>40</cols>
    <rows>20</rows>'.( $this->mNew == 'false' or $this->mEdit == 'true' ? '<readonly>true</readonly>' : '' ).
    '<value type="encoded">'.urlencode( $this->mText ).'</value>
  </args>
</text>
<toolbar>
      <name>notetoolbar</name>
            <args><bgcolor>#ffff66</bgcolor></args>
      <children>
        <button>
          <name>savenote</name>
          <args>
            <label>'.$locale->getStr( 'savenote.button' ).'</label>
            <themeimage>filesave</themeimage>
             <themeimagetype>mini</themeimagetype>
            <action type="encoded">'.urlencode( $this->mSaveAction ).'</action>
            <horiz>true</horiz>
            <formsubmit>'.$noteform.'</formsubmit>
            <highlight>false</highlight>
          </args>
        </button>
        <button>
          <args>
            <label>'.$locale->getStr( 'closenote.button' ).'</label>
            <themeimage>fileclose</themeimage>
             <themeimagetype>mini</themeimagetype>
            <action type="encoded">'.urlencode( $this->mCloseAction ).'</action>
            <horiz>true</horiz>
            <highlight>false</highlight>
          </args>
        </button>';

        if ( $this->mNew == 'false' or $this->mEdit == 'true' ) $this->mDefinition .=
'        <button><name>deletenote</name>
           <args>
             <label>'.$locale->getStr( 'deletenote.button' ).'</label>
             <themeimage>editdelete</themeimage>
             <themeimagetype>mini</themeimagetype>
             <action type="encoded">'.urlencode( $this->mDeleteAction ).'</action>
             <horiz>true</horiz>
             <formsubmit>'.$noteform.'</formsubmit>
             <needconfirm>true</needconfirm>
             <highlight>false</highlight>
             <confirmmessage type="encoded">'.urlencode( $locale->getStr( 'deletenote.confirm' ) ).'</confirmmessage>
           </args>
         </button>';

        $this->mDefinition .=
'      </children>
    </toolbar>

      </children>
    </vertgroup>
    </children>
    </form>
  </children>
</vertframe>
';
    }
}

?>
