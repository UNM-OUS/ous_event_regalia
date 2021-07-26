<?php

use Digraph\Modules\ous_event_regalia\RegaliaOrder;
use Digraph\Modules\ous_event_regalia\Signup;

$package->cache_noStore();
$group = $package->noun();

// figure out windows
$windows = $cms->helper('graph')->children($group['dso.id'], 'regalia-group-signupwindow');
if (!$windows) {
    $cms->helper('notifications')->printWarning('Create at least one edge from this order group to a signup window to use this tool for assigning extras to signups from that window.');
    return;
}

// figure out who needs extras, initially limited to non-opted-out-regalia orders
$s = $cms->factory()->search();
$where = [
    '${dso.type} = "event-signup"',
    '${complete.state} = "complete"',
    '${regalia.order.optout} is not null',
    '(not ${regalia.order.optout})',
];
// limit to signups from child windows
$validIDs = [];
foreach ($windows as $window) {
    foreach ($cms->helper('graph')->childIDs($window['dso.id'], 'event-signupwindow-signup') as $id) {
        $validIDs[] = $id;
    }
}
$validIDs = array_unique($validIDs);
$where[] = '${dso.id} in ("' . implode('","', $validIDs) . '")';
// add where to search
$s->where(implode(' AND ', $where));
$s->order('${dso.date.created} ASC');
// filter results for those that don't have an order
$needRegalia = array_filter(
    $s->execute(),
    function (Signup $s) {
        return !$s->regaliaOrder();
    }
);

// list everyone who needs an extra
/** @var \Digraph\Modules\ous_event_regalia\RegaliaHelper */
$regalia = $cms->helper('regalia');
foreach ($needRegalia as $s) {
    echo "<iframe class='embedded-iframe' src='" . $group->url('assign-extras-chunk', ['s' => $s['dso.id']]) . "'></iframe>";
}

// list everyone who already has an extra assigned
$extras = $group->extraOrders();
$extras = array_filter($extras, function (RegaliaOrder $order) {
    return $order->signup();
});
$options = [];
foreach ($extras as $extra) {
    echo "<iframe class='embedded-iframe' src='" . $group->url('assign-extras-chunk', ['s' => $extra->signup()['dso.id']]) . "'></iframe>";
}
