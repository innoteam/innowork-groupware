<?php
/*!
@class WuiInnoworkContacts

@abstract Contacts widget.
*/
class WuiInnoworkContacts extends WuiWidget
{
    /*! @var mElements array - Array of the treemenu elements. */
    var $mElements;
    /*! @var mWidth int - Width of the treemenu. */
    var $mWidth;
    /*! @var mActiveLetter string - Id of the active group. */
    var $mActiveLetter;
    /*! @var mTarget string - Target frame. */
    var $mTarget;
    /*! @var mAllGroupsActive - Set to "true" if all groups should be showed as active. */
    var $mAllGroupsActive;
    var $mCompact;

    function __construct( $elemName, $elemArgs = "", $elemTheme = "", $dispEvents = "" )
    {
        parent::__construct( $elemName, $elemArgs, $elemTheme, $dispEvents );
        $this->mElements = $this->mArgs["elements"];
        $this->mWidth = $this->mArgs["width"];
        $this->mActiveGroup = $this->mArgs["activegroup"];
        $this->mTarget = $this->mArgs["target"];
        $this->mAllGroupsActive = $this->mArgs["allgroupsactive"];
    }

    protected function generateSource()
    {
        if ( $this->mrWuiDisp->getEventName() == "treemenu-".$this->mName )
        {
            $disp_data = $this->mrWuiDisp->getEventData();
            if ( isset($disp_data["activegroup"] ) ) $this->mActiveGroup = $disp_data["activegroup"];
        }

        $this->mLayout = ( $this->mComments ? "<!-- begin ".$this->mName." treemenu -->" : "" ).
            "<table border=\"0\"".( strlen( $this->mWidth ) ? " width=\"".$this->mWidth."\"" : "" ).">\n";

        reset( $this->mElements );
        while ( list( $key, $val ) = each( $this->mElements ) )
        {
            // Set default group
            //
            if ( !$this->mActiveGroup ) $this->mActiveGroup = $key;

            if ( ( $this->mAllGroupsActive == "true" ) or ( $key == $this->mActiveGroup ) )
            {
                $this->mLayout .= "<tr><td align=\"center\" class=\"boldbig\"><center>".
                    "           <table width=\"100%\" border=\"0\" bgcolor=\"#DFDFDF\" cellspacing=\"0\" cellpadding=\"3\">
                    <tr>
                    <td><img src=\"".\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getBaseUrl(false).'/shared/'."arrowright.gif\" WIDTH=\"15\" HEIGHT=\"11\"></td>
                    <td valign=\"middle\" align=\"center\" width=\"100%\" class=\"boldbig\"><center>".$val["groupname"]."</center></td>
                    </tr>
                    </table>".
                    "</center></td></tr>";

                while ( list( $keyitem, $valitem ) = each( $val["groupelements"] ) )
                {
                    $target = "";

                    if ( strlen( $valitem["target"] ) ) $target = $valitem["target"];
                    else
                    {
                        if ( strlen( $this->mTarget ) ) $target = $this->mTarget;
                    }

                    $this->mLayout .= "<tr><td align=\"center\" class=\"normal\"><center><a href=\"".$valitem["action"]."\" class=\"normal\"".( strlen( $target ) ? " target=\"".$target."\"" : "" )."><img src=\"".$valitem["image"]."\" border=\"0\"><br>".$valitem["name"]."</a></center></td></tr>";
                }
            }
            else
            {
                $events_call = new WuiEventsCall();
                $events_call->addEvent( new WuiEvent( "wui", "treemenu-".$this->mName, array( "activegroup" => $key ) ) );

                reset( $this->mDispEvents );
				while ( list( , $event ) = each( $this->mDispEvents ) )
				{
					$events_call->addEvent( $event );
				}
// TODO remove /shared/
				
				$this->mLayout .= "<tr><td align=\"center\" class=\"boldbig\"><center>".
"           <table width=\"100%\" border=\"0\" bgcolor=\"#DFDFDF\" cellspacing=\"0\" cellpadding=\"3\">
              <tr>
                <td><img src=\"/shared/arrowdown.gif\" WIDTH=\"15\" HEIGHT=\"11\"></td>
                <td valign=\"middle\" align=\"center\" width=\"100%\" class=\"boldbig\"><center><a href=\"".$events_call->getEventsCallString()."\">".$val["groupname"]."</center></td>
              </tr>
            </table>".
				"</center></td></tr>";

				unset( $events_call );
			}
		}

		$this->mLayout .= "</table>\n".( $this->mComments ? "<!-- end ".$this->mName." treemenu -->" : "" );

		return true;
	}
}

?>