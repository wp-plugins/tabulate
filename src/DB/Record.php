<?php

namespace WordPress\Tabulate\DB;

class Record {

	/** @var Table */
	protected $table;

	/** @var \stdClass */
	protected $data;

	const FKTITLE = 'FKTITLE';

	/**
	 * Create a new Record object.
	 * @param Table $table The table object.
	 * @param array $data The data of this record.
	 */
	public function __construct($table, $data = array()) {
		$this->table = $table;
		$this->data = (object) $data;
	}

	public function __set($name, $value) {
		$this->data->$name = $value;
	}

	public function __call($name, $args) {

		// Foreign key 'title' values.
		$useTitle = substr( $name, -strlen( self::FKTITLE ) ) == self::FKTITLE;
		if ( $useTitle ) {
			$name = substr( $name, 0, -strlen( self::FKTITLE ) );
			if ( $this->table->get_column( $name )->is_foreign_key() && !empty( $this->data->$name ) ) {
				$referencedTable = $this->table->get_column( $name )->get_referenced_table();
				$fkRecord = $referencedTable->get_record( $this->data->$name );
				$fkTitleCol = $referencedTable->get_title_column();
				$fkTitleColName = $fkTitleCol->get_name();
				if ( $fkTitleCol->is_foreign_key() ) {
					// Use title if the FK's title column is also an FK.
					$fkTitleColName .= self::FKTITLE;
				}
				//var_dump( $fkRecord, $fkTitleColName, $fkRecord->$fkTitleColName(), $this->data->$name, $fkTitleCol->is_foreign_key() );
				return $fkRecord->$fkTitleColName();
			}
		}

		// Booleans
		if ( $this->table->get_column( $name )->is_boolean() ) {
			// Numbers are fetched from the DB as strings.
			if ( $this->data->$name === '1' ) {
				return true;
			} elseif ( $this->data->$name === '0' ) {
				return false;
			} else {
				return null;
			}
		}

		// Standard column values.
		if ( isset( $this->data->$name ) ) {
			return $this->data->$name;
		}
	}

	public function __toString() {
		return print_r( $this->data, true );
	}

	/**
	 * Get the value of this record's primary key, or false if it doesn't have
	 * one.
	 *
	 * @return string|false
	 */
	public function get_primary_key() {
		if ($this->table->get_pk_column()) {
			$pk_col_name = $this->table->get_pk_column()->get_name();
			if (isset($this->data->$pk_col_name)) {
				return $this->data->$pk_col_name;
			}
		}
		return false;
	}

	/**
	 * Get the value of this Record's title column.
	 * @return string
	 */
	public function get_title() {
		$title_col_name = $this->table->get_title_column()->get_name();
		return $this->data->$title_col_name;
	}

	public function get_referenced_record($column_name) {
		return $this->table->get_column( $column_name )->get_referenced_table()->get_record( $this->data->$column_name );
	}

	public function get_url($action = 'index', $include_ident = true ) {
		$params = array(
			'page' => 'tabulate',
			'controller' => 'record',
			'action' => $action,
			'table' => $this->table->get_name(),
		);
		if ( $include_ident && $this->get_primary_key() ) {
			$params['ident'] = $this->get_primary_key();
		}
		return admin_url( 'admin.php?' . http_build_query( $params ) );
	}

}
