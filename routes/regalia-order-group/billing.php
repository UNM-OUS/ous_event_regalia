<?php

use Digraph\Modules\ous_event_regalia\RegaliaOrder;

$package->cache_noStore();
/** @var Digraph\Modules\ous_event_regalia\RegaliaOrderGroup */
$group = $package->noun();

if ($prices = $group->regaliaPrices()) {
    $cms->helper('notifications')->notice("Using prices from: " . $prices->link());
}

$orders = [
    '_owner' => [],
];
array_map(
    function (RegaliaOrder $order) use (&$orders) {
        $billing = $order->billing();
        $sum = array_sum($billing ?? []);
        if ($billing) {
            foreach ($billing as $id => $share) {
                $orders[$id][] = [$order, [$share, $sum]];
            }
        } else {
            $orders['_owner'][] = [$order, [1, 1]];
        }
    },
    $group->allOrders()
);

$names = [
    '_owner' => 'Site owner'
];
foreach ($orders as $billTo => $eventOrders) {
    if (!$eventOrders) {
        continue;
    }
    if ($event = $cms->read($billTo, false)) {
        echo "<h2>" . $event->link() . " (" . count($eventOrders) . ")</h2>";
    } else {
        echo "<h2>";
        echo @$names[$billTo] ?? $billTo;
        echo " (" . count($eventOrders) . ")</h2>";
    }
    $tableTotal = 0;
    $table = "<table>";
    $table .= "<tr><th>Order</th><th>Total</th><th>Share</th><th>Billed</th></tr>";
    foreach ($eventOrders as list($order, $share)) {
        $bill_total = $group->orderPrice($order);
        $bill = ($share[0] / $share[1]) * $bill_total;
        $table .= "<tr>";
        $table .= '<td>';
        if ($signup = $order->signup()) {
            $table .= $signup->link();
            $table .= "<div class='incidental'>" . $order->link() . "</div>";
        } else {
            $table .= $order->link();
        }
        $table .= '</td>';
        $table .= "<td>$$bill_total</td>";
        $table .= '<td>' . $share[0] . '/' . $share[1] . '</td>';
        $table .= '<td>$' . round($bill * 100) / 100 . '</td>';
        $table .= "</tr>";
        $tableTotal += $bill;
    }
    $table .= "<tr><td colspan='3'></td><td>$$tableTotal</td></tr>";
    $table .= "</table>";
    echo $table;
}
