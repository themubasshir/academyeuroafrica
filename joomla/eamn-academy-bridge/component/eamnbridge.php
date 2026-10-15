<?php

/**
 * com_eamnbridge — site entry point.
 *
 * Handles two tasks:
 *  - ?task=exchange  (server-to-server, HMAC-verified) — swaps a single-use
 *    authorisation code for identity claims. Called by the academy only.
 *  - ?task=go        (browser) — mints a code for the current Joomla user and
 *    redirects them to the academy consume endpoint. Purchase buttons use this.
 */
\defined('_JEXEC') or exit;

use EamnBridge\BridgeHelper;
use Joomla\CMS\Factory;

$app = Factory::getApplication();
$task = $app->input->getCmd('task', 'go');

require_once __DIR__.'/src/BridgeHelper.php';

$params = $app->getParams('com_eamnbridge');
$helper = new BridgeHelper($params);

try {
    switch ($task) {
        case 'exchange':
            $helper->handleExchange($app->input);
            break;

        case 'go':
            $helper->handleGo($app->input, $app->getIdentity());
            break;

        default:
            throw new InvalidArgumentException('Unknown task', 400);
    }
} catch (Throwable $e) {
    $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    if ($task === 'exchange') {
        $app->setHeader('Status', (string) $code);
        echo json_encode(['error' => $e->getMessage()]);
    } else {
        $app->enqueueMessage('Academy link error: '.$e->getMessage(), 'error');
        $app->redirect('index.php');
    }
}
