<?php
require_once('innowork/core/InnoworkItem.php');

define('INNOWORKDIRECTORY_COMPANY_TYPE_NONE', 0);
define('INNOWORKDIRECTORY_COMPANY_TYPE_CUSTOMER', 1);
define('INNOWORKDIRECTORY_COMPANY_TYPE_SUPPLIER', 2);
define('INNOWORKDIRECTORY_COMPANY_TYPE_BOTH', 3);
define('INNOWORKDIRECTORY_COMPANY_TYPE_CONSULTANT', 4);
define('INNOWORKDIRECTORY_COMPANY_TYPE_GOVERNMENT', 5);
define('INNOWORKDIRECTORY_COMPANY_TYPE_INTERNAL', 6);

/*!
 @class InnoworkCompany

 @abstract directorycompany item type handler.
 */
class InnoworkCompany extends InnoworkItem {
	var $mTable = 'innowork_directory_companies';
	var $mNoTrash = false;
	var $mNewEvent = 'newcompany';
	const ITEM_TYPE = 'directorycompany';
	const NOTE_ITEM_TYPE = 1;

	public function __construct($rrootDb, $rdomainDA, $itemId = 0) {
		parent::__construct($rrootDb, $rdomainDA, InnoworkCompany::ITEM_TYPE, $itemId);

		$this->mKeys['companyname'] = 'text';
		$this->mKeys['code'] = 'text';
		$this->mKeys['street'] = 'text';
		$this->mKeys['city'] = 'text';
		$this->mKeys['zip'] = 'text';
		$this->mKeys['state'] = 'text';
		$this->mKeys['country'] = 'text';
		$this->mKeys['email'] = 'text';
		$this->mKeys['url'] = 'text';
		$this->mKeys['phone'] = 'text';
		$this->mKeys['fax'] = 'text';
		$this->mKeys['fiscalcode'] = 'text';
		$this->mKeys['fiscalcodeb'] = 'text';
		$this->mKeys['notes'] = 'text';
		$this->mKeys['accountmanager'] = 'text';
		$this->mKeys['companytype'] = 'integer';
		$this->mKeys['legalerappresentante'] = 'text';
		$this->mKeys['lrfiscalcode'] = 'text';
		$this->mKeys['defaultvatid'] = 'integer';
		$this->mKeys['defaultpaymentid'] = 'integer';

		$this->mSearchResultKeys[] = 'companyname';
		$this->mSearchResultKeys[] = 'code';
		$this->mSearchResultKeys[] = 'street';
		$this->mSearchResultKeys[] = 'city';
		$this->mSearchResultKeys[] = 'zip';
		$this->mSearchResultKeys[] = 'state';
		$this->mSearchResultKeys[] = 'country';
		$this->mSearchResultKeys[] = 'email';
		$this->mSearchResultKeys[] = 'url';
		$this->mSearchResultKeys[] = 'phone';
		$this->mSearchResultKeys[] = 'fax';
		$this->mSearchResultKeys[] = 'notes';
		$this->mSearchResultKeys[] = 'accountmanager';
		$this->mSearchResultKeys[] = 'companytype';
		$this->mSearchResultKeys[] = 'legalerappresentante';
		$this->mSearchResultKeys[] = 'lrfiscalcode';

		$this->mViewableSearchResultKeys[] = 'companyname';
		$this->mViewableSearchResultKeys[] = 'code';
		$this->mViewableSearchResultKeys[] = 'street';
		$this->mViewableSearchResultKeys[] = 'city';
		$this->mViewableSearchResultKeys[] = 'zip';
		$this->mViewableSearchResultKeys[] = 'state';
		$this->mViewableSearchResultKeys[] = 'country';
		$this->mViewableSearchResultKeys[] = 'email';
		$this->mViewableSearchResultKeys[] = 'url';
		$this->mViewableSearchResultKeys[] = 'phone';
		$this->mViewableSearchResultKeys[] = 'accountmanager';

		$this->mSearchOrderBy = 'companyname';
		$this->mShowDispatcher = 'view';
		$this->mShowEvent = 'showcompany';

		$this->mRelatedItemsFields[] = 'companyid';
		$this->mRelatedItemsFields[] = 'customerid';
	}

	function doCreate($params, $userId) {
		$result = FALSE;

		if (count($params)) {
			$item_id = $this->mrDomainDA->getNextSequenceValue($this->mTable.'_id_seq');
			$params['trashed'] = $this->mrDomainDA->fmtfalse;
			$key_pre = $value_pre = $keys = $values = '';

			if (!isset($params['code']) or !strlen($params['code'])) {
				$params['code'] = strtoupper($params['companyname']);
				$params['code'] = str_replace(' ', '', $params['code']);
				$params['code'] = str_replace('.', '', $params['code']);
				$params['code'] = str_replace('-', '', $params['code']);
			}

			if (!isset($params['defaultvatid']) or !isset($params['defaultpaymentid'])) {
			    $innowork_core = \Innowork\Core\InnoworkCore::instance('\Innowork\Core\InnoworkCore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
			    $summ = $innowork_core->getSummaries();
			    
			    if (isset($summ['invoice'])) {
			        if (!isset($params['defaultvatid'])) {
			            $params['defaultvatid'] = InnoworkBillingSettingsHandler::getDefaultVat();
			        }
			        
			        if (!isset($params['defaultpaymentid'])) {
			            $params['defaultpaymentid'] = InnoworkBillingSettingsHandler::getDefaultVat();
			        }
			    }
			}
			
			while (list ($key, $val) = each($params)) {
				$key_pre = ',';
				$value_pre = ',';

				switch ($key) {
					case 'code' :
					case 'companyname' :
					case 'street' :
					case 'city' :
					case 'zip' :
					case 'state' :
					case 'country' :
					case 'phone' :
					case 'fax' :
					case 'email' :
					case 'url' :
					case 'fiscalcode' :
					case 'fiscalcodeb' :
					case 'notes' :
					case 'accountmanager' :
					case 'legalerappresentante' :
					case 'lrfiscalcode' :
					case 'trashed':
						$keys.= $key_pre.$key;
						$values.= $value_pre.$this->mrDomainDA->formatText($val);
						break;
					case 'companytype' :
					case 'defaultvatid':
					case 'defaultpaymentid':
						$keys.= $key_pre.$key;
						$values.= $value_pre.$val;
						break;
				}
			}

			if (strlen($values)) {
				if ($this->mrDomainDA->execute('INSERT INTO '.$this->mTable.' '.'(id,ownerid'.$keys.') '.'VALUES ('.$item_id.','.$userId.$values.')'))
				$result = $item_id;
			}
		}

		return $result;
	}

	function doRemove($userId) {
		$result = FALSE;

		$result = $this->mrDomainDA->execute('DELETE FROM '.$this->mTable.' '.'WHERE id='.$this->mItemId);

		return $result;
	}

	function doGetItem($userId) {
		$result = FALSE;

		$item_query = & $this->mrDomainDA->execute('SELECT * '.'FROM '.$this->mTable.' '.'WHERE id='.$this->mItemId);

		if (is_object($item_query) and $item_query->getNumberRows()) {
			$result = $item_query->getFields();
		}

		return $result;
	}

	public function doTrash() {
		return true;
	}

	function doGetSummary() {
		return FALSE;
	}

	public function addNote($username, $content) {
		$result = false;

		if ($this->mItemId) {
			$date['year'] = date('Y');
			$date['mon'] = date('n');
			$date['mday'] = date('d');
			$date['hours'] = date('H');
			$date['minutes'] = date('i');
			$date['seconds'] = date('s');

			$timestamp = $this->mrDomainDA->GetTimestampFromDateArray($date);

			if (strlen($username)) {
				$result = $this->mrDomainDA->execute('INSERT INTO innowork_directory_notes VALUES('.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->getNextSequenceValue('innowork_directory_notes_id_seq').','.$this->mItemId.','.InnoworkCompany::NOTE_ITEM_TYPE.','.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($username).','.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($content).','.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($timestamp).')');

				if ($result) {
					require_once('innowork/core/InnoworkItemLog.php');
					$log = new InnoworkItemLog($this->mItemType, $this->mItemId);

					$log->LogChange(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName());
				}
			}
		}

		return $result;
	}

	public function removeNote($noteId) {
		$result = false;
		$noteId = (int) $noteId;

		if ($noteId) {
			$result = $this->mrDomainDA->execute('DELETE FROM innowork_directory_notes '.'WHERE id='.$noteId.' '.'AND itemtype='.InnoworkCompany::NOTE_ITEM_TYPE.' '.'AND itemid='.$this->mItemId);

			if ($result) {
				require_once('innowork/core/InnoworkItemLog.php');
				$log = new InnoworkItemLog($this->mItemType, $this->mItemId);

				$log->LogChange(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName());
			}
		}

		return $result;
	}

	function getNotes() {
		$result = array();

		if ($this->mItemId) {
			$notes_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT id,username,content,creationdate '.'FROM innowork_directory_notes '.'WHERE itemid='.$this->mItemId.' '.'AND itemtype='.InnoworkCompany::NOTE_ITEM_TYPE.' '.'ORDER BY creationdate');

			while (!$notes_query->eof) {
				$result[] = array('id' => $notes_query->getFields('id'), 'username' => $notes_query->getFields('username'), 'content' => $notes_query->getFields('content'), 'creationdate' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetDateArrayFromTimestamp($notes_query->getFields('creationdate')));

				$notes_query->MoveNext();
			}
		}

		return $result;
	}
}
?>