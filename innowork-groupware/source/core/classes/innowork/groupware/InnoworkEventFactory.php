<?php
class InnoworkEventFactory {
	function RemoveExternalEvent($type, $id, $extdata = '') {
		if (strlen($type) and $type != '%' and (int) $id) {
			$query = 'SELECT id FROM innowork_calendar WHERE exttype='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText($type).' AND extid='. (int) $id;
			if (strlen($extdata))
				$query.= ' AND extdata='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText($extdata);
			$items_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->execute($query);

			while (!$items_query->eof) {
				$event = new InnoworkEvent(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $items_query->getFields('id'));
				$event->Remove();
				$items_query->MoveNext();
			}
		}
	}
}
?>