<?php

require_once('innowork/core/InnoworkItem.php');

/*!
 @class InnoworkContact

 @abstract directorycontact item type handler.
 */
class InnoworkContact extends InnoworkItem {
	var $mTable = 'innowork_directory_contacts';
	var $mNoTrash = false;
	var $mNewEvent = 'newcontact';
	const ITEM_TYPE = 'directorycontact';
	const NOTE_ITEM_TYPE = 2;

	function InnoworkContact($rrootDb, $rdomainDA, $itemId = 0) {
		parent::__construct($rrootDb, $rdomainDA, InnoworkContact::ITEM_TYPE, $itemId);

		$this->mKeys['firstname'] = 'text';
		$this->mKeys['lastname'] = 'text';
		$this->mKeys['street'] = 'text';
		$this->mKeys['nickname'] = 'text';
		$this->mKeys['jobdescription'] = 'text';
		$this->mKeys['jobtitle'] = 'text';
		$this->mKeys['city'] = 'text';
		$this->mKeys['email'] = 'text';
		$this->mKeys['phone'] = 'text';
		$this->mKeys['fax'] = 'text';
		$this->mKeys['mobile'] = 'text';
		$this->mKeys['homephone'] = 'text';
		$this->mKeys['url'] = 'text';
		$this->mKeys['fiscalcode'] = 'text';
		$this->mKeys['notes'] = 'text';
		$this->mKeys['companyid'] = 'table:innowork_directory_companies:companyname:integer';
		$this->mKeys['accountmanager'] = 'text';

		$this->mSearchResultKeys[] = 'lastname';
		$this->mSearchResultKeys[] = 'firstname';
		$this->mSearchResultKeys[] = 'street';
		$this->mSearchResultKeys[] = 'city';
		$this->mSearchResultKeys[] = 'zip';
		$this->mSearchResultKeys[] = 'state';
		$this->mSearchResultKeys[] = 'country';
		$this->mSearchResultKeys[] = 'email';
		$this->mSearchResultKeys[] = 'url';
		$this->mSearchResultKeys[] = 'phone';
		$this->mSearchResultKeys[] = 'fax';
		$this->mSearchResultKeys[] = 'mobile';
		$this->mSearchResultKeys[] = 'accountmanager';

		$this->mViewableSearchResultKeys[] = 'lastname';
		$this->mViewableSearchResultKeys[] = 'firstname';
		$this->mViewableSearchResultKeys[] = 'street';
		$this->mViewableSearchResultKeys[] = 'city';
		$this->mViewableSearchResultKeys[] = 'zip';
		$this->mViewableSearchResultKeys[] = 'state';
		$this->mViewableSearchResultKeys[] = 'country';
		$this->mViewableSearchResultKeys[] = 'email';
		$this->mViewableSearchResultKeys[] = 'url';
		$this->mViewableSearchResultKeys[] = 'phone';
		$this->mViewableSearchResultKeys[] = 'fax';
		$this->mViewableSearchResultKeys[] = 'mobile';
		$this->mViewableSearchResultKeys[] = 'accountmanager';

		$this->mSearchOrderBy = 'lastname,firstname';
		$this->mShowDispatcher = 'view';
		$this->mShowEvent = 'showcontact';

		$this->mRelatedItemsFields[] = 'personid';
	}

	function doCreate($params, $userId) {
		$result = FALSE;

		if (count($params)) {
			$item_id = $this->mrDomainDA->getNextSequenceValue($this->mTable.'_id_seq');
			$params['trashed'] = $this->mrDomainDA->fmtfalse;
			$key_pre = $value_pre = $keys = $values = '';

			while (list ($key, $val) = each($params)) {
				$key_pre = ',';
				$value_pre = ',';

				switch ($key) {
					case 'lastname' :
					case 'firstname' :
					case 'title' :
					case 'nickname' :
					case 'jobtitle' :
					case 'jobdescription' :
					case 'street' :
					case 'city' :
					case 'zip' :
					case 'state' :
					case 'country' :
					case 'phone' :
					case 'fax' :
					case 'mobile' :
					case 'homephone' :
					case 'email' :
					case 'url' :
					case 'notes' :
					case 'accountmanager' :
					case 'fiscalcode' :
					case 'trashed':
						$keys.= $key_pre.$key;
						$values.= $value_pre.$this->mrDomainDA->formatText($val);
						break;
					case 'companyid' :
						$keys.= $key_pre.$key;
						$values.= $value_pre.$val;
						break;
				}

				$key_pre = ',';
				$value_pre = ',';
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

	function AddNote($username, $content) {
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
				$result = $this->mrDomainDA->execute('INSERT INTO innowork_directory_notes VALUES('.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->getNextSequenceValue('innowork_directory_notes_id_seq').','.$this->mItemId.','.InnoworkContact::NOTE_ITEM_TYPE.','.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($username).','.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($content).','.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($timestamp).')');

				if ($result) {
					$log = new InnoworkItemLog($this->mItemType, $this->mItemId);

					$log->LogChange(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName());
				}
			}
		}

		return $result;
	}

	function RemoveNote($noteId) {
		$result = false;
		$noteId = (int) $noteId;

		if ($noteId) {
			$result = $this->mrDomainDA->execute('DELETE FROM innowork_directory_notes '.'WHERE id='.$noteId.' '.'AND itemtype='.InnoworkContact::NOTE_ITEM_TYPE.' '.'AND itemid='.$this->mItemId);

			if ($result) {
				$log = new InnoworkItemLog($this->mItemType, $this->mItemId);

				$log->LogChange(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName());
			}
		}

		return $result;
	}

	function getNotes() {
		$result = array();

		if ($this->mItemId) {
			$notes_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT id,username,content,creationdate '.'FROM innowork_directory_notes '.'WHERE itemid='.$this->mItemId.' '.'AND itemtype='.InnoworkContact::NOTE_ITEM_TYPE.' '.'ORDER BY creationdate');

			while (!$notes_query->eof) {
				$result[] = array('id' => $notes_query->getFields('id'), 'username' => $notes_query->getFields('username'), 'content' => $notes_query->getFields('content'), 'creationdate' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetDateArrayFromTimestamp($notes_query->getFields('creationdate')));

				$notes_query->MoveNext();
			}
		}

		return $result;
	}
}

?>