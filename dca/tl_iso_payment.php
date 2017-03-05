<?php

/**
 * Copyright (C) 2017 Comolo GmbH
 *
 * @author    Hendrik Obermayer
 * @copyright 2017 Comolo GmbH <https://www.comolo.de>
 * @license   MIT
 */

$GLOBALS['TL_DCA']['tl_iso_payment']['palettes']['paymill'] = '{type_legend},name,label,type;{note_legend:hide},note;{paymill_legend},paymill_public_key,paymill_private_key;{config_legend:hide},new_order_status,minimum_total,maximum_total,countries,shipping_modules,product_types;{gateway_legend},trans_type;{price_legend:hide},price,tax_class;{expert_legend:hide},guests,protected;{enabled_legend},enabled';

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['paymill_public_key'] = [
    'label'                 => &$GLOBALS['TL_LANG']['tl_iso_payment']['paymill_public_key'],
    'exclude'               => true,
    'inputType'             => 'text',
    'eval'                  => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
    'sql'                   => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['paymill_private_key'] = [
    'label'                 => &$GLOBALS['TL_LANG']['tl_iso_payment']['paymill_private_key'],
    'exclude'               => true,
    'inputType'             => 'text',
    'eval'                  => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
    'sql'                   => "varchar(255) NOT NULL default ''",
];
