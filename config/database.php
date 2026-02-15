<?php
/**
 * Wrapper de compatibilidade.
 *
 * Muitos endpoints ainda fazem:
 *   require_once __DIR__ . '/../config/database.php'
 *
 * A conexão oficial fica em `php/database.php`.
 */

require_once __DIR__ . '/../php/database.php';

