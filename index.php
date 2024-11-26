<?php

/**
 * @defgroup plugins_generic_webOfScience
 */

/**
 * @file plugins/generic/webOfScience/index.php
 *
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @ingroup plugins_generic_webOfScience
 * @brief Wrapper for Web of Science plugin.
 *
 */

require_once('WebOfSciencePlugin.inc.php');

return new WebOfSciencePlugin();

?>
