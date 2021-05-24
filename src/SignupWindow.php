<?php
namespace Digraph\Modules\ous_event_regalia;

use Digraph\Modules\ous_event_management\SignupWindow as Ous_event_managementSignupWindow;

class SignupWindow extends Ous_event_managementSignupWindow
{
    function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['regalia_deadline'] = [
            'label' => 'Regalia deadline',
            'class' => 'datetime',
            'field' => 'regalia_deadline',
            'weight' => 300,
            'tips' => [
                'If entered, notices about the regalia waitlist will be automatically generated and change after this deadline.'
            ]
        ];
        return $map;
    }
}
