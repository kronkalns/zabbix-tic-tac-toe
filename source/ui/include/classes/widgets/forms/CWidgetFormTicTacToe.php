<?php

class CWidgetFormTicTacToe extends CWidgetForm {

	public function __construct($data, $templateid) {
		parent::__construct($data, $templateid, WIDGET_TIC_TAC_TOE);

		// Nickname field.
		$field_nickname = (new CWidgetFieldTextBox('nickname', _('Nickname')))
			->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK);

		if (array_key_exists('nickname', $this->data)) {
			$field_nickname->setValue($this->data['nickname']);
		}

		$this->fields[$field_nickname->getName()] = $field_nickname;

		// Host field.
		$field_host = (new CWidgetFieldMsHost('hostid', _('Editable host')))
			->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			->setMultiple(false);

		if (array_key_exists('hostid', $this->data)) {
			$field_host->setValue($this->data['hostid']);
		}

		$this->fields[$field_host->getName()] = $field_host;

		// Widget reference field.
		$field_reference = (new CWidgetFieldReference())->setDefault('');

		if (array_key_exists($field_reference->getName(), $this->data)) {
			$field_reference->setValue($this->data[$field_reference->getName()]);
		}

		$this->fields[$field_reference->getName()] = $field_reference;
	}
}
