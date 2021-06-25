<?php

use Digraph\Forms\Fields\Noun;
use Digraph\Forms\Form;
use Digraph\Modules\ous_event_regalia\RegaliaOrder;
use Digraph\Modules\ous_event_regalia\Signup;
use Digraph\Modules\ous_event_regalia\SignupWindow;
use Digraph\Modules\ous_event_management\Event;
use Formward\Fields\CheckboxList;

$package->cache_noStore();
$group = $package->noun();

$targetForm = new Form('Select signup, event, or signup window');
$targetForm->csrf(false);
$targetForm->method('get');
$targetForm['target'] = $target = new Noun('Target', null, null, $cms);
$target->limitTypes(['event-signup', 'event-signupwindow', 'event', 'convocation']);

echo $targetForm;

$target = $cms->read($target->value());
if (!$target) {
    return;
}
$signups = [];

if ($target instanceof Signup) {
    $signups[] = $target;
} elseif ($target instanceof SignupWindow) {
    $signups = $target->allSignups();
} elseif ($target instanceof Event) {
    $signups = $target->allSignups();
}

$signups = array_filter(
    $signups,
    function (Signup $signup) {
        return $signup->complete()
            && !$signup['regalia.order.optout']
            && $signup['regalia.order.parts'];
    }
);

$form = new Form('Select the orders to create or move to this group');
$form['signups'] = new CheckboxList('Signups found');
$options = [];
$default = [];
foreach ($signups as $signup) {
    $info = '<strong>' . $signup->name() . '</strong>';
    if ($signup->regaliaOrder()) {
        if ($signup->regaliaGroup() == $group) {
            continue;
        } else {
            if ($signup->regaliaGroup()['group_locked']) {
                $info .= '<br>Already ordered in ' . $signup->regaliaGroup()->name() . ' (locked)';
                $info .= '<br>Check to create a <strong>new order</strong> in this group';
            } else {
                $info .= '<br>Already ordered in ' . $signup->regaliaGroup()->name();
                $info .= '<br>Check to <strong>transfer order</strong> to this group';
            }
        }
    } else {
        $info .= '<br>No regalia order linked';
        $default[] = $signup['dso.id'];
    }
    $options[$signup['dso.id']] = $info;
}
$form['signups']->options($options);
$form['signups']->default($default);

echo "<hr>";
echo $form;

if ($form->handle()) {
    $signups = array_filter(array_map(
        function ($e) use ($cms) {
            return $cms->read($e);
        },
        $form['signups']->value()
    ));
    $created = 0;
    foreach ($signups as $signup) {
        if ($signup->regaliaGroup() && !$signup->regaliaGroup()['order_locked']) {
            // signup  has a group, but it isn't locked -- transfer it to this group
            $signup->regaliaOrder()->orderGroup($group);
            $created++;
        } else {
            // signup either has no order, or group is locked -- create new order
            /** @var RegaliaOrder */
            $order = $cms->factory()->create([
                'dso.type' => 'regalia-order',
                'parts' => $signup['regalia.order.parts'],
                'size' => $signup['regalia.size'],
                'degree' => $signup['regalia.degree'],
                'digraph.name' => 'order',
                'extra' => false,
            ]);
            if ($order->insert()) {
                $order->signup($signup);
                $order->orderGroup($group);
                $created++;
            }
        }
    }
    $cms->helper('notifications')->flashConfirmation('Added/transferred ' . $created . ' orders');
    $package->redirect($group->url('add-signups'));
}
