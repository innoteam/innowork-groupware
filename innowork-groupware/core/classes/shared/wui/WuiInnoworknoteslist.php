<?php

require_once('shared/wui/WuiXml.php');
require_once('shared/wui/WuiInnoworknote.php');

/*!
 @class WuiInnoworkNotesList

 @abstract Notes list widget.
 */
class WuiInnoworkNotesList extends WuiXml
{
    var $mNotes;
    var $mViewMode;
    var $mSaveAction;
    var $mDeleteAction;
    var $mDisp;

    function __construct( $elemName, $elemArgs = '', $elemTheme = '', $dispEvents = '' )
    {
        parent::__construct( $elemName, $elemArgs, $elemTheme, $dispEvents );

        $this->mSaveAction = $this->mArgs['saveaction'];
        $this->mDeleteAction = $this->mArgs['deleteaction'];
        $this->mDisp = $this->mArgs['disp'];

        if ( is_array( $this->mArgs['notes'] ) ) $this->mNotes = $this->mArgs['notes'];

        if ( $this->mArgs['viewmode'] == 'byicon'
            or
            $this->mArgs['viewmode'] == 'bynote' )
            $this->mViewMode = $this->mArgs['viewmode'];
        else $this->mViewMode = 'byicon';

        $this->_FillDefinition();
    }

    function _FillDefinition()
    {
        $result = FALSE;

        $this->mDefinition =
'<?xml version="1.0"?>
<vertgroup>
  <name>notesvertgroup</name>
  <children>
';

	if ( is_array( $this->mNotes ) )
        {
            $notes_num = count( $this->mNotes );

            if ( $this->mViewMode == 'bynote' ) $max_cols = 3;
            if ( $this->mViewMode == 'byicon' ) $max_cols = 5;

            $rows = floor( $notes_num / $max_cols );
            if ( $rows == 0 ) $rows = 1;
            $lastrow_notes = $notes_num % $max_cols;

            $row = $row_note = 0;

            $this->mDefinition .= '<grid><name>notesgrid</name><args><rows>'.$rows.'</rows><cols>'.$max_cols.'</cols></args><children>';

            while ( list( $id, $note ) = each( $this->mNotes ) )
            {
                $note_edit_action = new WuiEventsCall( 'innoworknotes' );
                $note_edit_action->addEvent( new WuiEvent( 'view', 'editnote', array( 'id' => $id ) ) );

                if ( $this->mViewMode == 'bynote' ) $this->mDefinition .=
'<innoworknote row="'.$row.'" col="'.$row_note.'" halign="center" valign="top">
  <name>innoworknote</name>
  <args>
    <noteid>'.$id.'</noteid>
    <edit>true</edit>
    <disp>'.$this->mDisp.'</disp>
    <title type="encoded">'.urlencode( $note['title'] ).'</title>
    <text type="encoded">'.urlencode( $note['text'] ).'</text>
    <saveaction type="encoded">'.urlencode( $this->mSaveAction ).'</saveaction>
    <deleteaction type="encoded">'.urlencode( $this->mDeleteAction ).'</deleteaction>
  </args>
</innoworknote>';
                if ( $this->mViewMode == 'byicon' ) $this->mDefinition .=
'<button row="'.$row.'" col="'.$row_note.'" halign="center" valign="top">
  <name>notebutton</name>
  <args>
    <nowrap>true</nowrap>
    <label type="encoded">'.urlencode( $note['title'] ).'</label>
    <themeimage>edit</themeimage>
    <action type="encoded">'.urlencode( $note_edit_action->getEventsCallString() ).'</action>
  </args>
</button>';

                if ( $row == $rows and $row_note == $lastrow_notes )
                {
                    while ( $row_note < ( $max_cols - 1 ) )
                    {
                        $row_note++;
                        //$this->mDefinition .= '<raw row="'.$row.'" col="'.$row_note.'"><name>raw</name></raw>';
                    }
                }

                if ( $row_note == ( $max_cols - 1 ) )
                {
                    $row++;
                    $row_note = 0;
                }
                else $row_note++;
            }
            $this->mDefinition .= '</children></grid>';
        }

        $this->mDefinition .=
'
  </children>
</vertgroup>
';
    //echo $this->mDefinition;
    }
}

?>