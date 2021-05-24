<?php
$noun = $package->noun();

// regalia deadline display
if ($noun['regalia_deadline']) {
    if (time() < $noun['regalia_deadline']) {
        $cms->helper('notifications')->printNotice('Regalia for signups completed after ' . $cms->helper('strings')->datetime($noun['regalia_deadline']) . ' are not guaranteed. Signups completed after our bulk order is placed with the bookstore will be filled using extra UNM PhD regalia in the closest size available.');
    } else {
        $cms->helper('notifications')->printWarning('Regalia for signups completed after ' . $cms->helper('strings')->datetime($noun['regalia_deadline']) . ' are not guaranteed. Signups completed after our bulk order is placed with the bookstore will be filled using extra UNM PhD regalia in the closest size available.');
    }
}
