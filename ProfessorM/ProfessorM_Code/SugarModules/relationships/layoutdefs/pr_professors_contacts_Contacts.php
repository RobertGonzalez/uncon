<?php
 // created: 2017-08-17 07:43:22
$layout_defs["Contacts"]["subpanel_setup"]['pr_professors_contacts'] = array (
  'order' => 100,
  'module' => 'PR_Professors',
  'subpanel_name' => 'default',
  'sort_order' => 'asc',
  'sort_by' => 'id',
  'title_key' => 'LBL_PR_PROFESSORS_CONTACTS_FROM_PR_PROFESSORS_TITLE',
  'get_subpanel_data' => 'pr_professors_contacts',
  'top_buttons' => 
  array (
    0 => 
    array (
      'widget_class' => 'SubPanelTopButtonQuickCreate',
    ),
    1 => 
    array (
      'widget_class' => 'SubPanelTopSelectButton',
      'mode' => 'MultiSelect',
    ),
  ),
);
