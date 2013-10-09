<?php

require_once('shared/wui/WuiXml.php');

function dir_cdata($data) {
    return '<![CDATA['.$data.']]>';
}

    /*!
     @class WuiInnoworkContactsList
    
     @abstract Contacts list widget.
     */
    class WuiInnoworkContactsList extends WuiXml {
        var $mContacts;
        var $mType = 'companies';
        var $mTabActionFunction;
        var $mTypeTabActionFunction;
        var $mActiveTab;
        var $mActiveTypeTab;
        var $mViewMode;

        function __construct($elemName, $elemArgs = '', $elemTheme = '', $dispEvents = '') {
            parent::__construct($elemName, $elemArgs, $elemTheme, $dispEvents);

            if (is_array($this->mArgs['contacts']))
                $this->mContacts = $this->mArgs['contacts'];
            if (isset($this->mArgs['type']) and ($this->mArgs['type'] == 'companies' or $this->mArgs['type'] == 'contacts'))
                $this->mType = $this->mArgs['type'];

            if (isset($this->mArgs['tabactionfunction']))
                $this->mTabActionFunction = $this->mArgs['tabactionfunction'];
            if (isset($this->mArgs['typetabactionfunction']))
                $this->mTypeTabActionFunction = $this->mArgs['typetabactionfunction'];
            if (isset($this->mArgs['activetab']) and strlen($this->mArgs['activetab']))
                $this->mActiveTab = $this->mArgs['activetab'];
            if (isset($this->mArgs['activetypetab']) and strlen($this->mArgs['activetypetab']))
                $this->mActiveTypeTab = $this->mArgs['activetypetab'];
        if ( isset($this->mArgs['viewmode'] )) {
            switch ($this->mArgs['viewmode']) {
                case 'compact':
                case 'detailed':
                case 'list':
                    $this->mViewMode = $this->mArgs['viewmode'];
                    break;
                default:
                    $this->mViewMode = 'compact';
            }
        }

            $this->_FillDefinition();
        }

        function _FillDefinition() {
            $result = false;

            

            require_once('innomatic/locale/LocaleCatalog.php');
            $locale = new LocaleCatalog(
				'innowork-groupware::directory_misc',
				InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage());

            for ($pos = 97; $pos <= 122; $pos++) $contact[chr($pos)] = array();
            $contacts['numbers'] = array();

            $types[0]['label'] = $locale->getStr('type_all.tab');
            $types[1]['label'] = $locale->getStr('type_customers.tab');
            $types[2]['label'] = $locale->getStr('type_suppliers.tab');
            $types[3]['label'] = $locale->getStr('type_both.tab');
            $types[4]['label'] = $locale->getStr('type_consultants.tab');
            $types[5]['label'] = $locale->getStr('type_government.tab');
            $types[6]['label'] = $locale->getStr('type_internals.tab');
            $types[7]['label'] = $locale->getStr('type_others.tab');

            $tabs[0]['label'] = $locale->getStr('a.tab');
            $tabs[1]['label'] = $locale->getStr('b.tab');
            $tabs[2]['label'] = $locale->getStr('c.tab');
            $tabs[3]['label'] = $locale->getStr('d.tab');
            $tabs[4]['label'] = $locale->getStr('e.tab');
            $tabs[5]['label'] = $locale->getStr('f.tab');
            $tabs[6]['label'] = $locale->getStr('g.tab');
            $tabs[7]['label'] = $locale->getStr('h.tab');
            $tabs[8]['label'] = $locale->getStr('i.tab');
            $tabs[9]['label'] = $locale->getStr('j.tab');
            $tabs[10]['label'] = $locale->getStr('k.tab');
            $tabs[11]['label'] = $locale->getStr('l.tab');
            $tabs[12]['label'] = $locale->getStr('m.tab');
            $tabs[13]['label'] = $locale->getStr('n.tab');
            $tabs[14]['label'] = $locale->getStr('o.tab');
            $tabs[15]['label'] = $locale->getStr('p.tab');
            $tabs[16]['label'] = $locale->getStr('q.tab');
            $tabs[17]['label'] = $locale->getStr('r.tab');
            $tabs[18]['label'] = $locale->getStr('s.tab');
            $tabs[19]['label'] = $locale->getStr('t.tab');
            $tabs[20]['label'] = $locale->getStr('u.tab');
            $tabs[21]['label'] = $locale->getStr('v.tab');
            $tabs[22]['label'] = $locale->getStr('w.tab');
            $tabs[23]['label'] = $locale->getStr('x.tab');
            $tabs[24]['label'] = $locale->getStr('y.tab');
            $tabs[25]['label'] = $locale->getStr('z.tab');
            $tabs[26]['label'] = $locale->getStr('numbers.tab');

                if (!strlen($this->mActiveTypeTab)) {
                $wui = Wui::instance('wui');
                $wui->loadWidget('tab');
                $tab = new WuiTab('contactstypes',array('sessionobjectusername'=>$this->mType.'type'));
                $this->mActiveTypeTab = isset($tab->mActiveTab) ? $tab->mActiveTab : $tab->mArgs['activetab'];
                }

                if (!strlen($this->mActiveTab)) {
                $wui = Wui::instance('wui');
                $wui->loadWidget('tab');
                $tab = new WuiTab('contactsvertgroup',array('sessionobjectusername'=>$this->mType));
                $this->mActiveTab = isset($tab->mActiveTab) ? $tab->mActiveTab : $tab->mArgs['activetab'];
                }

            while (list ($id, $fields) = each($this->mContacts)) {
                if ($this->mType != 'companies' or $this->mActiveTypeTab == '0'
                or ( $this->mActiveTypeTab == '1' and ($fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_CUSTOMER or $fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_BOTH))
                or ($this->mActiveTypeTab == '2' and ($fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_SUPPLIER or $fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_BOTH ))
                or $this->mActiveTypeTab == '3' and $fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_BOTH
                or $this->mActiveTypeTab == '4' and $fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_CONSULTANT or $this->mActiveTypeTab == '5' and $fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_GOVERNMENT or $this->mActiveTypeTab == '6' and $fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_INTERNAL or $this->mActiveTypeTab == '7' and $fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_NONE) {

                    switch (strtolower(substr($this->mType == 'companies' ? $fields['companyname'] : $fields['lastname'], 0, 1))) {
                        case 'a' :
                        case 'b' :
                        case 'c' :
                        case 'd' :
                        case 'e' :
                        case 'f' :
                        case 'g' :
                        case 'h' :
                        case 'i' :
                        case 'j' :
                        case 'k' :
                        case 'l' :
                        case 'm' :
                        case 'n' :
                        case 'o' :
                        case 'p' :
                        case 'q' :
                        case 'r' :
                        case 's' :
                        case 't' :
                        case 'u' :
                        case 'v' :
                        case 'w' :
                        case 'x' :
                        case 'y' :
                        case 'z' :
                            $contacts[strtolower(substr($this->mType == 'companies' ? $fields['companyname'] : $fields['lastname'], 0, 1))][$id] = $fields;
                            break;

                        default :
                            $contacts['numbers'][$id] = $fields;
                            break;
                    }

                }
            }
            reset($this->mContacts);

            $this->mDefinition = '';

            if ($this->mType == 'companies') {

                $this->mDefinition.= '<tab>
                                          <name>contactstypes</name>
                                          <args>
                                            <tabs type="array">'.WuiXml::encode($types).'</tabs>
                                            <tabactionfunction>'.$this->mTypeTabActionFunction.'</tabactionfunction>
                                            <activetab>'. (isset($this->mActiveTypeTab) ? $this->mActiveTypeTab : '').'</activetab>
                                            <sessionobjectusername>'.$this->mType.'type</sessionobjectusername>
                                          </args>
                                          <children>';

                                          for($i=0; $i<$this->mActiveTypeTab;$i++) $this->mDefinition .= '<empty/>';
            }


            $this->mDefinition.= '<tab>
                                      <name>contactsvertgroup</name>
                                      <args>
                                        <tabs type="array">'.WuiXml::encode($tabs).'</tabs>
                                        <tabactionfunction>'.$this->mTabActionFunction.'</tabactionfunction>
                                        <activetab>'. (isset($this->mActiveTab) ? $this->mActiveTab : '').'</activetab>
                                        <sessionobjectusername>'.$this->mType.'</sessionobjectusername>
                                        <tabrows>2</tabrows>
                                      </args>
                                      <children>
                                    ';


            for ( $pos = 97; $pos <= 122; $pos++ )
            {
                if ( !isset($this->mActiveTab ) or ( $this->mActiveTab == ( $pos - 97 ) ) ) $this->AddContactsTable($contacts[chr($pos)],$locale);
                else $this->mDefinition .= '<empty/>';
            }

            if ( $this->mActiveTab == '26' ) $this->AddContactsTable($contacts['numbers'],$locale);
            else $this->mDefinition .= '<empty/>';

            $this->mDefinition.= '  </children>
                                    </tab>
                                    ';

                                          if ($this->mType == 'companies')
                                          {
                                          for($i=$this->mActiveTypeTab; $i<8;$i++) $this->mDefinition .= '<empty/>';

                $this->mDefinition.= '</children>
                                        </tab>';
                                          }

            //echo $this->mDefinition;
        }

        function AddContactsTable($contacts,&$locale) {

            if (is_array($contacts)) {
                
                $contacts_num = count($contacts);

if ($this->mViewMode=='list') {

$row = 0;

$this->mDefinition .=
'<table>
  <name>contacts_'.$this->mType.'</name>
  <args>
  </args>
  <children>';
                while (list ($id, $contact) = each($contacts)) {
                    $contact_edit_action = new WuiEventsCall('innoworkdirectory');
                    $contact_edit_action->addEvent(new WuiEvent('view', $this->mType == 'companies' ? 'showcompany' : 'showcontact', array('id' => $id)));

                    switch ($contact['_acl']['type']) {
                        case InnoworkAcl::TYPE_PRIVATE :
                            $image = 'personal';
                            break;

                        case InnoworkAcl::TYPE_PUBLIC :
                        case InnoworkAcl::TYPE_ACL :
                            $image = 'kuser';
                            break;
                    }
                    
                    $this->mDefinition .=
'                                                                <button row="'.$row.'" col="0"><name>acl</name>
                                                                  <args>
                                                                    <compact>true</compact>
                                                                    <themeimage>'.$image.'</themeimage>
                                                                    <themeimagetype>mini</themeimagetype>
                                                                  </args>
                                                                </button>
<link row="'.$row.'" col="1">
  <args>
    <label>'.dir_cdata($this->mType == 'companies' ? $contact['companyname'] : $contact['lastname'].' '.$contact['firstname']).'</label>
    <compact>true</compact>
    <link>'.dir_cdata($contact_edit_action->GetEventsCallString()).'</link>
  </args>
</link>
                                                                <link row="'.$row.'" col="2"><name>email</name>
                                                                  <args>
                                                                    <link>'.dir_cdata(strlen($contact['email']) ? 'mailto:'.$contact['email'] : '').'</link>
                                                                    <label>'.dir_cdata($contact['email']).'</label>
                                                                  </args>
                                                                </link>
                                                                <label row="'.$row.'" col="3"><name>phone</name>
                                                                  <args>
                                                                    <label>'.dir_cdata($contact['phone']).'</label>
                                                                  </args>
                                                                </label>
';
                    $row++;
                }
$this->mDefinition .=
'  </children>
</table>';
} else {
                $max_cols = 3;

                $rows = floor($contacts_num / $max_cols);
                if ($rows == 0)
                    $rows = 1;
                $lastrow_contacts = $contacts_num % $max_cols;

                $row = $row_contact = 0;

                $this->mDefinition.= '<grid><name>contactsgrid</name><args><rows>'.$rows.'</rows><cols>'.$max_cols.'</cols></args><children>';

                while (list ($id, $contact) = each($contacts)) {
                    $contact_edit_action = new WuiEventsCall('innoworkdirectory');
                    $contact_edit_action->addEvent(new WuiEvent('view', $this->mType == 'companies' ? 'showcompany' : 'showcontact', array('id' => $id)));

                    switch ($contact['_acl']['type']) {
                        case InnoworkAcl::TYPE_PRIVATE :
                            $image = 'personal';
                            break;

                        case InnoworkAcl::TYPE_PUBLIC :
                        case InnoworkAcl::TYPE_ACL :
                            $image = 'kuser';
                            break;
                    }

                    $this->mDefinition.= '<table row="'.$row.'" col="'.$row_contact.'" halign="left" valign="top"><name>contact</name>
                                                              <args>
                                                                <width>100%</width>
                                                                <headers type="array">'.WuiXml::encode(array('0' => array('label' => $this->mType == 'companies' ? $contact['companyname'] : $contact['lastname'].' '.$contact['firstname'], 'link' => $contact_edit_action->GetEventsCallString()))).'</headers>
                                                              </args>
                                                              <children>
                                                            <grid row="0" col="0"><name>contact</name>
                                                              <children>
                                                                <button row="0" col="0"><name>acl</name>
                                                                  <args>
                                                                    <compact>true</compact>
                                                                    <themeimage>'.$image.'</themeimage>
                                                                    <themeimagetype>mini</themeimagetype>
                                                                  </args>
                                                                </button>';

                                                                if ($this->mViewMode == 'detailed') $this->mDefinition .= '
                                                                <label row="0" col="1"><name>email</name>
                                                                  <args>
                                                                    <label>'.($locale->getStr('email.label')).'</label>
                                                                  </args>
                                                                </label>
                                                                <link row="0" col="2"><name>email</name>
                                                                  <args>
                                                                    <link>'.dir_cdata(strlen($contact['email']) ? 'mailto:'.$contact['email'] : '').'</link>
                                                                    <label>'.dir_cdata($contact['email']).'</label>
                                                                  </args>
                                                                </link>
                                                                <label row="1" col="1"><name>website</name>
                                                                  <args>
                                                                    <label>'.dir_cdata($locale->getStr('website.label')).'</label>
                                                                  </args>
                                                                </label>
                                                                <link row="1" col="2"><name>website</name>
                                                                  <args>
                                                                    <link>'.dir_cdata($contact['url']).'</link>
                                                                    <target>_blank</target>
                                                                  </args>
                                                                </link>
                                                                <label row="2" col="1"><name>phone</name>
                                                                  <args>
                                                                    <label>'.($locale->getStr('phone.label')).'</label>
                                                                  </args>
                                                                </label>
                                                                <label row="2" col="2"><name>phone</name>
                                                                  <args>
                                                                    <label>'.dir_cdata($contact['phone']).'</label>
                                                                  </args>
                                                                </label>
                                                                <label row="3" col="1" halign="left" valign="top"><name>address</name>
                                                                  <args>
                                                                    <label>'.($locale->getStr('address.label')).'</label>
                                                                  </args>
                                                                </label>
                                                                <label row="3" col="2" halign="left" valign="top"><name>address</name>
                                                                  <args>
                                                                    <label>'.dir_cdata($contact['street'].'<br>'.$contact['zip'].' '.$contact['city'].' '.$contact['state'].'<br>'. (strlen($contact['country']) ? $contact['country'] : '&nbsp;')).'</label>
                                                                  </args>
                                                                </label>';
                                                                elseif ($this->mViewMode == 'compact') $this->mDefinition .= '
                                                                <label row="0" col="1"><name>phone</name>
                                                                  <args>
                                                                    <label>'.($locale->getStr('phone.label')).'</label>
                                                                    <compact>true</compact>
                                                                  </args>
                                                                </label>
                                                                <label row="0" col="2"><name>phone</name>
                                                                  <args>
                                                                    <label>'.dir_cdata($contact['phone']).'</label>
                                                                    <compact>true</compact>
                                                                  </args>
                                                                </label>';
                                                                
                                                                $this->mDefinition .= '
                                                              </children>
                                                            </grid>
                                                              </children>
                                                            </table>';

                    if ($row == $rows and $row_contact == $lastrow_contacts) {
                        while ($row_contact < ($max_cols -1)) {
                            $row_contact ++;
                            //$this->mDefinition .= '<raw row="'.$row.'" col="'.$row_contact.'"><name>raw</name></raw>';
                        }
                    }

                    if ($row_contact == ($max_cols -1)) {
                        $row ++;
                        $row_contact = 0;
                    }
                    else
                        $row_contact ++;
                }
                $this->mDefinition.= '</children></grid>';
}
            }
        }
    }

?>
