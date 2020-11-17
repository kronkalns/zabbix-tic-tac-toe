<?php
$fields = $data['dialogue']['fields'];
$form = CWidgetHelper::createForm();
$rf_rate_field = ($data['templateid'] === null) ? $fields['rf_rate'] : null;

$form_list = CWidgetHelper::createFormList($data['dialogue']['name'], $data['dialogue']['type'],
	$data['dialogue']['view_mode'], $data['known_widget_types'], $rf_rate_field
);

// Nickname.
$form_list->addRow(CWidgetHelper::getLabel($fields['nickname']), CWidgetHelper::getTextBox($fields['nickname']));

// Host.
$field_hostid = CWidgetHelper::getHost($fields['hostid'], $data['captions']['ms']['hosts']['hostid'], $form->getName());
$form_list->addRow(CWidgetHelper::getMultiselectLabel($fields['hostid']), $field_hostid);
$form->addItem($form_list);
$scripts[] = $field_hostid->getPostJS();

// Reference field.
$field_reference = $fields[CWidgetFieldReference::FIELD_NAME];
$form->addItem((new CVar($field_reference->getName(), $field_reference->getValue()))->removeId());

if ($field_reference->getValue() === '') {
	$scripts[] = $field_reference->getJavascript('#'.$form->getAttribute('id'));
}

return [
	'form' => $form,
	'scripts' => $scripts
];
