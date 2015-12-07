<?php
class InnoworkAccountManager {
	var $id;
	var $userId;
	var $userHandler;

	function InnoworkAccountManager($id = 0) {
		$id = (int)$id;
		if ($id) {
			$this->id = $id;
			$userid_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT userid FROM innowork_accountmanagers WHERE id='.$this->id);
			$this->userId = $userid_query->getFields('userid');
			$this->userHandler = new User(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->domaindata['id'], $this->userId);
		}
	}

	function create($userId) {
		$id = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->getNextSequenceValue('innowork_accountmanagers_id_seq');
		if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('INSERT INTO innowork_accountmanagers VALUES('.$id.','.$userId.')')) {
			$this->id = $id;
			$this->userId = $userId;
			$this->userHandler = new User(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->domaindata['id'], $this->userId);
		}
	}

	function remove() {
		if ($this->id) {
			\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('DELETE FROM innowork_accountmanagers WHERE id='.$this->id);
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