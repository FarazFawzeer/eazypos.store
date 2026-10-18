<?php

namespace App\Support;

/**
 * Public asset URLs with a file-modified version, so browsers pick up new
 * CSS/JS right after a deployment without a build step on the server.
 */
final class Asset
{
    /** POS scripts, in load order (they share one global scope). */
    public const POS_SCRIPTS = [
        'js/pos/00-icons.js',
        'js/pos/00-api.js',
        'js/pos/01-core.js',
        'js/pos/02-products.js',
        'js/pos/03-purchases.js',
        'js/pos/04-sales.js',
        'js/pos/05-registers-reports.js',
        'js/pos/06-expenses.js',
        'js/pos/07-stock.js',
        'js/pos/08-contacts.js',
        'js/pos/09-admin.js',
        'js/pos/10-shipments.js',
        'js/pos/11-routes.js',
        'js/pos/12-ui.js',
    ];

    public static function url(string $path): string
    {
        $file = public_path($path);
        $version = is_file($file) ? filemtime($file) : null;

        return asset($path).($version ? '?v='.$version : '');
    }
}
