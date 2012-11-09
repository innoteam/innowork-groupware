<?php

require_once('innowork/core/InnoworkItem.php');

require_once('innomatic/application/ApplicationDependencies.php');
$app_dep = new ApplicationDependencies(InnomaticContainer::instance('innomaticcontainer')->getDataAccess());

/*!
 @class InnoworkActivity

 @abstract activityitem type handler.
 */
class InnoworkActivity extends InnoworkItem {
	var $mTable = 'innowork_activities';
	var $mNoTrash = false;
	var $mNewDispatcher = 'view';
	var $mNewEvent = 'newactivity';
	var $mConvertible = true;
	const ITEM_TYPE = 'activity';

	public function __construct($rrootDb, $rdomainDA, $itemId = 0) {
		parent::__construct($rrootDb, $rdomainDA, InnoworkActivity::ITEM_TYPE, $itemId);

		$this->mKeys['activity'] = 'text';
		$this->mKeys['description'] = 'text';
		$this->mKeys['projectid'] = 'table:innowork_projects:name:integer';
		$this->mKeys['activitydate'] = 'timestamp';
		$this->mKeys['done'] = 'boolean';
		$this->mKeys['priority'] = 'integer';
		$this->mKeys['spenttime'] = 'integer';

		$this->mSearchResultKeys[] = 'activity';
		$this->mSearchResultKeys[] = 'description';
		$this->mSearchResultKeys[] = 'projectid';
		$this->mSearchResultKeys[] = 'activitydate';
		$this->mSearchResultKeys[] = 'done';
		$this->mSearchResultKeys[] = 'priority';
		$this->mSearchResultKeys[] = 'spenttime';

		$this->mViewableSearchResultKeys[] = 'activity';
		$this->mViewableSearchResultKeys[] = 'description';
		$this->mViewableSearchResultKeys[] = 'projectid';
		$this->mViewableSearchResultKeys[] = 'activitydate';
		$this->mViewableSearchResultKeys[] = 'priority';
		$this->mViewableSearchResultKeys[] = 'spenttime';

		$this->mSearchOrderBy = 'activitydate ASC,priority DESC,activity';
		$this->mShowDispatcher = 'view';
		$this->mShowEvent = 'showactivity';

		$this->mGenericFields['companyid'] = 'customerid';
		$this->mGenericFields['projectid'] = 'projectid';
		$this->mGenericFields['title'] = 'activity';
		$this->mGenericFields['content'] = 'description';
		$this->mGenericFields['binarycontent'] = '';
	}

	function doCreate($params, $userId) {
		$result = FALSE;

		if (count($params)) {
			if ($params['done'] == 'true')
			$params['done'] = $this->mrDomainDA->fmttrue;
			else
			$params['done'] = $this->mrDomainDA->fmtfalse;

			$params['trashed'] = $this->mrDomainDA->fmtfalse;

			$item_id = $this->mrDomainDA->getNextSequenceValue($this->mTable.'_id_seq');

			$key_pre = $value_pre = $keys = $values = '';

			require_once('innomatic/locale/LocaleCountry.php');
			$country = new LocaleCountry(InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry());

			while (list ($key, $val) = each($params)) {
				$key_pre = ',';
				$value_pre = ',';

				switch ($key) {
					case 'activity' :
					case 'done' :
					case 'trashed' :
					case 'description' :
						$keys.= $key_pre.$key;
						$values.= $value_pre.$this->mrDomainDA->formatText($val);
						break;

					case 'projectid' :
					case 'priority' :
					case 'spenttime' :
						if (!strlen($val))
						$val = 0;
						$keys.= $key_pre.$key;
						$values.= $value_pre.$val;
						break;

					case 'activitydate' :
						$date_array = $country->GetDateArrayFromShortDateStamp($val);
						$val = $this->mrDomainDA->GetTimestampFromDateArray($date_array);

						$keys.= $key_pre.$key;
						$values.= $value_pre.$this->mrDomainDA->formatText($val);
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

	function doEdit($params, $userId) {
		$result = FALSE;

		if ($this->mItemId) {
			if (count($params)) {
				$start = 1;
				$update_str = '';

				require_once('innomatic/locale/LocaleCountry.php');
				$country = new LocaleCountry(InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry());

				if (isset($params['done'])) {
					if ($params['done'] == 'true')
					$params['done'] = $this->mrDomainDA->fmttrue;
					else
					$params['done'] = $this->mrDomainDA->fmtfalse;
				}

				while (list ($field, $value) = each($params)) {
					if ($field != 'id') {
						if (!$start)
						$update_str.= ',';

						switch ($field) {
							case 'activity' :
							case 'done' :
							case 'trashed' :
							case 'description' :
								$update_str.= $field.'='.$this->mrDomainDA->formatText($value);
								break;

							case 'activitydate' :
								$date_array = $country->GetDateArrayFromShortDateStamp($value);
								$value = $this->mrDomainDA->GetTimestampFromDateArray($date_array);

								$update_str.= $field.'='.$this->mrDomainDA->formatText($value);
								break;

							case 'projectid' :
							case 'priority' :
							case 'spenttime' :
								if (!strlen($value))
								$value = 0;
								$update_str.= $field.'='.$value;
								break;

							default :
								break;
						}

						$start = 0;
					}
				}

				$query = $this->mrDomainDA->execute('UPDATE '.$this->mTable.' SET '.$update_str.' WHERE id='.$this->mItemId);

				if ($query)
				$result = TRUE;
			}
		}

		return $result;
	}

	function doRemove($userId) {
		$result = FALSE;

		$result = $this->mrDomainDA->execute('DELETE FROM '.$this->mTable.' WHERE id='.$this->mItemId);

		return $result;
	}

	function doGetItem($userId) {
		$result = FALSE;

		$item_query = $this->mrDomainDA->execute('SELECT * FROM '.$this->mTable.' WHERE id='.$this->mItemId);

		if (is_object($item_query) and $item_query->getNumberRows()) {
			$result = $item_query->getFields();
		}

		return $result;
	}

	function doTrash() {
		return true;
	}

	function doGetSummary() {
		$result = false;

		$search_result = $this->Search(array('done' => InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse,), InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId(), false, false, 0, 0, InnoworkItem::SEARCH_RESTRICT_TO_RESPONSIBLE);

		if (is_array($search_result)) {
			$definition = '';

			while (list ($id, $fields) = each($search_result)) {
				if (strlen($fields['activity']) > 25)
				$activity = substr($fields['activity'], 0, 22).'...';
				else
				$activity = $fields['activity'];

				require_once('innomatic/wui/dispatch/WuiEventsCall.php');
				require_once('innomatic/wui/dispatch/WuiEvent.php');
				$activity_action = new WuiEventsCall('innoworkactivities');
				$activity_action->addEvent(new WuiEvent('view', 'showactivity', array('id' => $id)));
				$definition.= '<horizgroup><name>activityhgroup</name><args></args><children>';
				$definition.= '<label><name>activitylabel</name><args><compact>true</compact><label>- </label></args></label>';
				$definition.= '<link><name>activitylink</name>
                                                      <args>
                                                        <compact>true</compact>
                                                        <label type="encoded">'.urlencode($activity).'</label>
                                                        <title type="encoded">'.urlencode($fields['activity']).'</title>
                                                        <link type="encoded">'.urlencode($activity_action->GetEventsCallString()).'</link>
                                                      </args>
                                                    </link>';
				$definition.= '</children></horizgroup>';
			}

			$definition = '<vertgroup><name>activitygroup</name><children>'.$definition.'</children></vertgroup>';

			$result = $definition;
		}

		return $result;

	}
}
?>
