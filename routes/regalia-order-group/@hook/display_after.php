<?php
$package->cache_noStore();
$group = $package->noun();

echo "<p>Highlighted rows: Red indicates incomplete information. Blue indicates an override of user-entered order. Green indicates an override that is being saved/retrieved automatically.<p>";

if ($orders = $group->assignedOrders()) {
    echo "<h2>Assigned orders</h2>";
    echo $cms->helper('regalia')->orderTable($orders);
}

if ($orders = $group->extraOrders()) {
    echo "<h2>Extra/Unassigned orders</h2>";
    echo $cms->helper('regalia')->orderTable($orders);
}
