<?php
/**
 * Junxtion App - Main Entry Point
 *
 * Redirects to customer webapp
 */

// Redirect to the customer app
header('Location: /app/', true, 301);
exit;
