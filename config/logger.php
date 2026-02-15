<?php
/**
 * Wrapper de compatibilidade.
 *
 * Muitos endpoints ainda fazem:
 *   require_once __DIR__ . '/../config/logger.php'
 *
 * O logger oficial fica em `php/logger.php`.
 */

require_once __DIR__ . '/../php/logger.php';

