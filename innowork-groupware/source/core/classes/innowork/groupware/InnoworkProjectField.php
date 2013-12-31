<?php

define( 'INNOWORKPROJECTS_FIELDTYPE_STATUS',	1 );
define( 'INNOWORKPROJECTS_FIELDTYPE_PRIORITY',	2 );
define( 'INNOWORKPROJECTS_FIELDTYPE_TYPE',		3 );
define( 'INNOWORKPROJECTS_FIELDTYPE_SOURCE',		4 );
define( 'INNOWORKPROJECTS_FIELDTYPE_CHANNEL',     5 );

class InnoworkProjectField {
	var $mLog;
	var $mrDomainDA;
	var $mFieldType;
	var $mId;

	public function __construct(
	$rdb,
	$fieldType = '',
	$id = ''
	)
	{
		$this->mLog = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getLogger();

		if ( is_object( $rdb ) ) $this->mrDomainDA = $rdb;
		else $this->mLog->LogDie(
            'innoworkprojects.innoworkprojects.projectfield.projectfield',
            'Invalid domain database handler'
            );

            if ( $id )
            {
            	$query = &$this->mrDomainDA->Execute(
                'SELECT fieldid,fieldvalue '.
                'FROM innowork_projects_fields_values '.
                'WHERE id='.$id );

            	if ( $query->getNumberRows() )
            	{
            		$this->mId = $id;

            		$this->mFieldValue = $query->getFields( 'fieldvalue' );
            		$this->mFieldType = $query->getFields( 'fieldid' );
            	}
            }
            else
            {
            	$this->mFieldType = $fieldType;
            }


            if ( empty( $this->mFieldType ) ) $this->mLog->LogDie(
            'innoworkprojects.innoworkprojects.innoworkprojectfield.projectfield',
            'No field type supplied'
            );

	}

	function NewValue(
	$value
	)
	{
		$result = false;

		if ( $this->mrDomainDA and !$this->mId )
		{
			$result = $this->mrDomainDA->Execute(
                'INSERT INTO innowork_projects_fields_values '.
                'VALUES ('.
			$this->mrDomainDA->getNextSequenceValue( 'innowork_projects_fields_values_id_seq' ).','.
			$this->mFieldType.','.
			$this->mrDomainDA->formatText( $value ).')'
			);

			if ( $result ) $this->mFieldValue = $value;
		}

		return $result;
	}

	function EditValue(
	$newValue,
	$newType = ''
	)
	{
		$result = false;

		if ( $this->mrDomainDA and $this->mId )
		{
			$result = $this->mrDomainDA->Execute(
                'UPDATE innowork_projects_fields_values '.
                'SET fieldvalue='.$this->mrDomainDA->formatText( $newValue ).
			( strlen( $newType ) ? ',fieldid='.$newType : '' ).
                ' WHERE id='.$this->mId
			);

			if ( $result ) $this->mFieldValue = $newValue;
		}

		return $result;
	}

	function RemoveValue() {
		$result = false;

		if ( $this->mrDomainDA and $this->mId )
		{
			$result = $this->mrDomainDA->Execute(
                'DELETE FROM innowork_projects_fields_values '.
                'WHERE id='.$this->mId
			);

			if ( $result )
			{
				$update_projects = false;

				switch ( $this->mFieldType )
				{
					case INNOWORKPROJECTS_FIELDTYPE_STATUS:
						$field = 'status';
						$update_projects = true;
						break;

					case INNOWORKPROJECTS_FIELDTYPE_PRIORITY:
						$fields = 'priority';
						$update_projects = true;
						break;

					case INNOWORKPROJECTS_FIELDTYPE_TYPE:
						$fields = 'type';
						$update_projects = true;
						break;

					case INNOWORKPROJECTS_FIELDTYPE_SOURCE:
					case INNOWORKPROJECTS_FIELDYTPE_CHANNEL:
						break;
				}

				if ($update_projects) {
					$this->mrDomainDA->Execute(
                        'UPDATE innowork_projects '.
                        'SET '.$field.'=0 '.
                        'WHERE '.$field.'='.$this->mId );
				}

				$this->mId = 0;
				$this->mFieldValue = '';
			}
		}

		return $result;
	}

	public static function getFields($type) {
		$query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->Execute(
        'SELECT
        	id,fieldvalue
        FROM 
        	innowork_projects_fields_values 
        WHERE
        	fieldid='.$type.' 
        ORDER BY
        	fieldvalue' );

		$fields = array();

		while (!$query->eof) {
			$fields[$query->getFields( 'id' )] = $query->getFields( 'fieldvalue' );
			$query->moveNext();
		}

		return $fields;
	}
}

?>