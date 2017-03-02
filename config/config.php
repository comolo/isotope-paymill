<?php

/**
 * Copyright (C) 2017 Comolo GmbH
 *
 * @author    Hendrik Obermayer
 * @copyright 2017 Comolo GmbH <https://www.comolo.de>
 * @license   MIT
 */

/**
 * Payment methods
 */
\Isotope\Model\Payment::registerModelType('sepa_direct_deposit', 'ComoloIsotope\Model\Payment\SepaDirectDeposit');