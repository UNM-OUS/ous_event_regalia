<?php

use Digraph\Modules\event_regalia\Regalia\RegaliaOrderChunk;
use Digraph\Modules\ous_event_management\Chunks\Contact\AbstractContactInfo;

$package->cache_private();
$package['fields.page_title'] = '';

echo "<h1>Regalia order</h1>";
/** @var /Digraph\Modules\event_regalia\RegaliaOrder */
$order = $package->noun();
/** @var /Digraph\Modules\event_regalia\Signup */
$signup = $order->signup();

echo $cms->helper('regalia')->orderDisplay($order);

if ($signup) {
    echo "<h2>Linked signup</h2>";
    foreach ($signup->chunks() as $chunk) {
        if ($chunk instanceof RegaliaOrderChunk || $chunk instanceof AbstractContactInfo) {
            echo $chunk->body();
        }
    }
}
