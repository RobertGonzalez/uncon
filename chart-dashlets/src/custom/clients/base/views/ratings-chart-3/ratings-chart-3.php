<?php

/*
 * Your installation or use of this SugarCRM file is subject to the applicable
 * terms available at
 * http://support.sugarcrm.com/Resources/Master_Subscription_Agreements/.
 * If you do not agree to all of the applicable terms or do not have the
 * authority to bind the entity as an authorized representative, then do not
 * install or use this SugarCRM file.
 *
 * Copyright (C) SugarCRM Inc. All rights reserved.
 */

$viewdefs['base']['view']['ratings-chart-3'] = array(
    'dashlets' => array(
        array(
            'label' => 'Ratings Distribution Chart 3',
            'description' => 'Uses the Report Api as datasource',
            'filter' => array(
                'module' => array(
                    'Home',
                    'Accounts',
                ),
                'view' => array(
                    'record',
                    'records',
                )
            ),
            'config' => array(
                'status' => 'Active',
                'report_id' => '',
            ),
            'config' => array(),
            'preview' => array(),
        ),
    ),
    'panels' => array(
        array(
            'name' => 'panel_body',
            'columns' => 2,
            'labelsOnTop' => true,
            'placeholders' => true,
            'fields' => array(
                array(
                    'name' => 'status',
                    'label' => 'Default status',
                    'default' => 'Active',
                    'type' => 'enum',
                    'options' => 'account_status_list',
                    'enum_width' => 'auto',
                ),
                array(
                    'name' => 'report_id',
                    'label' => 'Dashlet report id',
                    'default' => '',
                    'type' => 'base',
                ),
            ),
        ),
    ),
);
