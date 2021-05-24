<?php
$package->cache_noStore();

$url = $cms->helper('urls')->parse('_regalia-management/add');
echo "<p><a href='$url'>Create new order group</a></p>";

$search = $cms->factory()->search();
$search->where('${dso.type} = "regalia-order-group"');
$search->order('${dso.created.date} desc');

echo '<ul>';
foreach ($search->execute() as $group) {
    echo "<li>" . $group->link() . "</li>";
}
echo '</ul>';
