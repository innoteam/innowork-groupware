<?php
class InnoworkAccountManager {
	var $id;
	var $userId;
	var $userHandler;

	function InnoworkAccountManager($id = 0) {
		$id = (int)$id;
		if ($id) {
			$this->id = $id;
			$userid_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->execute('SELECT userid FROM innowork_accountmanagers WHERE id='.$this->id);
			$this->userId = $userid_query->getFields('userid');
			$this->userHandler = new User(InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->domaindata['id'], $this->userId);
		}
	}

	function create($userId) {
		$id = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getNextSequenceValue('innowork_accountmanagers_id_seq');
		if (InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->execute('INSERT INTO innowork_accountmanagers VALUES('.$id.','.$userId.')')) {
			$this->id = $id;
			$this->userId = $userId;
			$this->userHandler = new User(InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->domaindata['id'], $this->userId);
		}
	}

	function remove() {
		if ($this->id) {
			InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->execute('DELETE FROM innowork_accountmanagers WHERE id='.$this->id);
			$this->id = 0;
			$this->userId = 0;
			$this->userHandler = NULL;
		}
	}

	function getUserId() {
		return $this->userId;
	}

	function getUserHandler() {
		return $this->userHandler;
	}
}

?>