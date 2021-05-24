<?php

use Digraph\Modules\event_regalia\RegaliaOrder;
use Digraph\Modules\event_regalia\Signup;

$package->cache_noStore();
$group = $package->noun();

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
foreach ($cms->helper('graph')->children($group['dso.id'], 'regalia-group-signupwindow') as $window) {
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

// get all available extras
// $extras = $group->extraOrders();
// $extras = array_filter($extras, function (RegaliaOrder $order) {
//     return !$order->signup();
// });
// foreach ($extras as $extra) {
//     echo "<div>" . $extra->link() . "</div>";
// }

// list everyone who needs an extra
/** @var \Digraph\Modules\event_regalia\RegaliaHelper */
$regalia = $cms->helper('regalia');
foreach ($needRegalia as $s) {
    echo "<h2>" . $s['dso.id'] . " " . $s->link() . "</h2>";
    echo $regalia->orderDisplay($s);
}
