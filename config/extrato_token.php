<?php
/**
 * Chave secreta para assinar links públicos do extrato (WhatsApp).
 *
 * IMPORTANTE:
 * - Troque esta chave em produção.
 * - Se você trocar a chave, links antigos deixam de funcionar.
 */

// 32 bytes em Base64 (exemplo). Troque por um valor seu.
// Você pode gerar um novo valor com PHP: base64_encode(random_bytes(32))
define('EXTRATO_TOKEN_SECRET', 'Linda1607*');

// Tempo de validade do link público (em segundos). Ex.: 24 horas.
define('EXTRATO_TOKEN_TTL_SECONDS', 86400);

