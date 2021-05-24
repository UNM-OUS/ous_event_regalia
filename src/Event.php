<?php
namespace Digraph\Modules\ous_event_regalia;

use Digraph\Modules\ous_event_management\Event as Ous_event_managementEvent;

class Event extends Ous_event_managementEvent
{
    public function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['event_regalia_faculty'] = [
            'label' => 'Faculty regalia options',
            'field' => 'regalia.faculty',
            'weight' => 200,
            'class' => 'select',
            'required' => true,
            'call' => [
                'options' => [[
                    'required' => 'Regalia required',
                    'optional' => 'Regalia optional',
                    'none' => 'Informal/none',
                ]],
            ],
            'tips' => [
                'Online events should select "Informal/none"',
            ],
        ];
        $map['event_regalia_student'] = [
            'label' => 'Student regalia options',
            'field' => 'regalia.student',
            'weight' => 200,
            'class' => 'select',
            'required' => true,
            'call' => [
                'options' => [[
                    'required' => 'Regalia required',
                    'optional' => 'Regalia optional',
                    'none' => 'Informal/none',
                ]],
            ],
            'tips' => [
                'Online events should select "Informal/none"',
            ],
        ];
        return $map;
    }
}
