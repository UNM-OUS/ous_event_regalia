<?php

use Digraph\Modules\ous_event_regalia\RegaliaOrder;
use Digraph\Modules\ous_event_regalia\Signup;
use Digraph\Templates\NotificationsHelper;
use Formward\Fields\Select;

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
echo "<h2>Needs extra assigned</h2>";
/** @var \Digraph\Modules\ous_event_regalia\RegaliaHelper */
$regalia = $cms->helper('regalia');
foreach ($needRegalia as $s) {
    printChunk($s, $cms, $package);
}

// list everyone who already has an extra assigned
echo "<h2>Has extra assigned</h2>";
$extras = $group->extraOrders();
$extras = array_filter($extras, function (RegaliaOrder $order) {
    return $order->signup();
});
$options = [];
foreach ($extras as $extra) {
    printChunk($extra->signup(), $cms, $package);
}

function printChunk(Signup $signup, $cms, $package)
{
    /** @var Digraph\Modules\ous_event_regalia\RegaliaHelper */
    $helper = $cms->helper('regalia');
    echo "<div class='digraph-card'>";
    echo "<p><strong><a href='" . $signup->url() . "' target='_blank'>" . $signup->name() . "</a></strong>";
    echo '<br>order: ' . $helper->orderSizeString($signup);
    if ($signup->regaliaOrder()) {
        echo "<br>extra: " . $helper->orderSizeString($signup->regaliaOrder());
    }
    echo '</p>';

    $form = $cms->helper('forms')->form('', $signup['dso.id']);
    $form->addClass('autosubmit');
    $form['extra'] = new Select('');
    $form['extra']->required(true);
    // get all available extras
    $extras = $package->noun()->extraOrders();
    $extras = array_filter($extras, function (RegaliaOrder $order) {
        return !$order->signup();
    });
    $options = [];
    $prev = null;
    foreach ($extras as $extra) {
        $name = $helper->orderSizeString($extra);
        if ($name != $prev) {
            $options[md5($name)] = $name;
            $prev = $name;
        }
    }
    $form['extra']->options($options);
    $form['extra']->addTip('Generally you should choose the largest size available that is <em>smaller</em> than the requested size. Height is the primary concern for robe sizing.');
    $form->action($package->url());
    echo $form;
    if ($form->handle()) {
        // try to find an extra with a hash equal to the form value
        foreach ($extras as $extra) {
            $name = $helper->orderSizeString($extra);
            if (md5($name) == $form['extra']->value()) {
                $signup->regaliaOrder($extra);
                $package->redirect($package->url());
                return;
            }
        }
        // no extra found
        $cms->helper('notifications')->error('No matching extra found. It may have already been assigned.');
    }
    echo "</div>";
}
