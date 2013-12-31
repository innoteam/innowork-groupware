<?php

require_once('innowork/core/InnoworkItem.php');

/*!
 @class InnoworkNote

 @abstract Note item type handler.
 */
class InnoworkNote extends InnoworkItem {
    var $mTable = 'innowork_notes';
    var $mNewDispatcher = 'view';
    var $mNewEvent = 'newnote';
    var $mNoTrash = false;
    var $mConvertible = true;
    const ITEM_TYPE = 'note';

    public function __construct( $rrootDb, $rdomainDA, $itemId = 0 ) {
        parent::__construct( $rrootDb, $rdomainDA, InnoworkNote::ITEM_TYPE, $itemId );

        $this->mKeys['title'] = 'text';
        $this->mKeys['content'] = 'text';

        $this->mSearchResultKeys[] = 'title';
        $this->mSearchResultKeys[] = 'content';

        $this->mViewableSearchResultKeys[] = 'title';
        $this->mViewableSearchResultKeys[] = 'content';

        $this->mSearchOrderBy = 'title';
        $this->mShowDispatcher = 'view';
        $this->mShowEvent = 'editnote';

        $this->mGenericFields['companyid'] = '';
        $this->mGenericFields['projectid'] = '';
        $this->mGenericFields['title'] = 'title';
        $this->mGenericFields['content'] = 'content';
        $this->mGenericFields['binarycontent'] = '';
    }

    function doCreate( 
        $params, 
        $userId
        )
    {
        $result = FALSE;

        if ( count( $params ) )
        {
            $item_id = $this->mrDomainDA->getNextSequenceValue( 'innowork_notes_id_seq' );

            $key_pre = $value_pre = $keys = $values = '';

            while ( list( $key, $val ) = each( $params ) )
            {
                $key_pre = ',';
                $value_pre = ',';

                switch ( $key )
                {
                case 'title':
                case 'content':
                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$this->mrDomainDA->formatText( $val );
                    break;
                }
            }

            if ( strlen( $values ) )
            {
                if ( $this->mrDomainDA->Execute( 'INSERT INTO innowork_notes '.
                                               '(id,ownerid'.$keys.') '.
                                               'VALUES ('.$item_id.','.
                                               $userId.
                                               $values.')' ) ) $result = $item_id;
            }
        }

        return $result;
    }

    function doRemove( $userId )
    {
        $result = FALSE;

        $result = $this->mrDomainDA->Execute( 'DELETE FROM innowork_notes '.
                                           'WHERE id='.$this->mItemId );

        return $result;
    }

    function doGetItem( $userId )
    {
        $result = FALSE;

        $item_query = &$this->mrDomainDA->Execute( 'SELECT * '.
                                                'FROM innowork_notes '.
                                                'WHERE id='.$this->mItemId );

        if ( is_object( $item_query ) and $item_query->getNumberRows() )
        {
            $result = $item_query->getFields();
        }

        return $result;
    }

    function doGetSummary()
    {
        $result = FALSE;
        $search_result = $this->Search( '', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId() );

        if ( is_array( $search_result ) )
        {
            $definition = '';
            require_once('innomatic/wui/dispatch/WuiEventsCall.php');
			require_once('innomatic/wui/dispatch/WuiEvent.php');
            while ( list( $id, $fields ) = each( $search_result ) )
            {
                if ( strlen( $fields['title'] ) > 25 ) $title = substr( $fields['title'], 0, 22 ).'...';
                else $title = $fields['title'];

                $note_action = new WuiEventsCall( 'innoworknotes' );
                $note_action->addEvent( new WuiEvent( 'view', 'editnote', array( 'id' => $id ) ) );
                $definition .= '<horizgroup><name>notehgroup</name><args></args><children>';
                $definition .= '<label><name>notelabel</name><args><compact>true</compact><label>- </label></args></label>';
                $definition .= '<link><name>notelink</name><args><compact>true</compact><title type="encoded">'.urlencode( $fields['title'] ).'</title><label type="encoded">'.urlencode( $title ).'</label><link type="encoded">'.urlencode( $note_action->getEventsCallString() ).'</link></args></link>';
                $definition .= '</children></horizgroup>';
            }

            $definition = '<vertgroup><name>notesgroup</name><children>'.$definition.'</children></vertgroup>';

            $result = $definition;
        }

        return $result;
    }

    function doTrash( $arg )
    {
        return true;
    }
}

?>
