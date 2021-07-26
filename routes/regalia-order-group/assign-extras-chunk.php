<?php

use Digraph\Modules\ous_event_regalia\RegaliaOrder;
use Formward\Fields\Select;

$package->cache_noStore();
$package['fields.page_title'] = '';
$package['response.template'] = 'iframe.twig';

/** @var Digraph\Modules\ous_event_regalia\Signup */
$signup = $cms->read($package['url.args.s']);
/** @var Digraph\Modules\ous_event_regalia\RegaliaHelper */
$helper = $cms->helper('regalia');

echo "<div class='digraph-card'>";
echo "<p><strong><a href='" . $signup->url() . "' target='_blank'>" . $signup->name() . "</a></strong>";
echo '<br>order: ' . $helper->orderSizeString($signup);
if ($signup->regaliaOrder()) {
    echo "<br>extra: " . $helper->orderSizeString($signup->regaliaOrder());
}
echo '</p>';
if (!$package['url.args.assign']) {
    $url = $package->url();
    $url['args.assign'] = true;
    echo "<p><a href='$url'>Set extra assignment</a></p>";
} else {
    $form = $cms->helper('forms')->form('');
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
                $url = $package->url();
                unset($url['args.assign']);
                $package->redirect($url);
                return;
            }
        }
        // no extra found
        $cms->helper('notifications')->error('No matching extra found. It may have already been assigned.');
    }
}
echo "</div>";
