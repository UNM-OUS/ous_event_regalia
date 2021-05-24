<?php
namespace Digraph\Modules\ous_event_regalia;

use Digraph\DSO\Noun;
use Digraph\Modules\ous_event_regalia\Regalia\DegreeInfoField;
use Digraph\Modules\ous_event_regalia\Regalia\RegaliaSizeField;
use Digraph\Modules\ous_event_management\Signup;
use Formward\Fields\CheckboxList;

class RegaliaOrder extends Noun
{
    protected $jostens;

    public function title($verb = null)
    {
        $title = parent::title($verb);
        if ($this->signup()) {
            $title .= ' (' . $this->signup()['signup.for'] . ')';
        }
        return $title;
    }

    public function overridesSignup(): ?bool
    {
        // return null if no signup
        if (!$this->signup()) {
            return null;
        }
        // otherwise look for differences
        $signup = $this->signup();
        $comparisons = [
            'parts' => 'regalia.order.parts',
            'size' => 'regalia.size',
            'degree' => 'regalia.degree',
        ];
        foreach ($comparisons as $a => $b) {
            if ($this[$a] != $signup[$b]) {
                return true;
            }
        }
        // no differences found
        return false;
    }

    public function hook_postAddUrl()
    {
        return $this->orderGroup()->url();
    }

    public function hook_postEditUrl()
    {
        return $this->url();
    }

    public function hatType(): string
    {
        if ($this['hattype']) {
            return $this['hattype'];
        }
        if ($this->orderGroup() && $this->orderGroup()['hattype']) {
            return $this->orderGroup()['hattype'];
        }
        return 'cap';
    }

    public function orders(): array
    {
        $orders = [];
        $hat = @in_array('hat', $this['parts']);
        $hood = @in_array('hood', $this['parts']);
        $robe = @in_array('robe', $this['parts']);
        if ($hood) {
            $orders[] = 'HOOD';
        }
        if ($hat && $robe) {
            if ($this->hatType() == 'cap') {
                $orders[] = 'PACKAGE&nbsp;C/G';
            } else {
                $orders[] = 'PACKAGE&nbsp;T/G';
            }
        } else {
            if ($hat) {
                if ($this->hatType() == 'cap') {
                    $orders[] = 'CAP';
                } else {
                    $orders[] = 'TAM';
                }
            }
            if ($robe) {
                $orders[] = 'GOWN';
            }
        }
        return $orders;
    }

    public function bandColor(): ?string
    {
        if ($j = $this->jostens()) {
            return @$j['field']['color_desc'] ?? '?';
        }
        return null;
    }

    public function liningColor(): ?string
    {
        if ($j = $this->jostens()) {
            return @$j['institution']['color_lining1'] ?? '?';
        }
        return null;
    }

    public function chevronColor(): ?string
    {
        if ($j = $this->jostens()) {
            return @$j['institution']['color_chevron1'] ?? '?';
        }
        return null;
    }

    protected function jostens()
    {
        if ($this->jostens === null) {
            $helper = $this->cms()->helper('jostens');
            $this->jostens = [
                'institution' => $this['degree.institution'] ? $helper->locateInstitution($this['degree.institution']) : [],
                'field' => $this['degree.field'] ? $helper->locateDegree($this['degree.field']) : [],
            ];
        }
        return $this->jostens;
    }

    public function firstName(): ?string
    {
        if ($this['firstname']) {
            return $this['firstname'];
        }
        if ($signup = $this->signup()) {
            if ($signup->contactInfo()) {
                return $signup->contactInfo()->firstName();
            }
        }
        return null;
    }

    public function lastName(): ?string
    {
        if ($this['lastname']) {
            return $this['lastname'];
        }
        if ($signup = $this->signup()) {
            if ($signup->contactInfo()) {
                return $signup->contactInfo()->lastName();
            }
        }
        return null;
    }

    public function signup(Signup $set = null): ?Signup
    {
        if ($set) {
            $this->cms()->helper('edges')->deleteChildren($this['dso.id'], 'signup-regalia-order');
            $this->cms()->helper('edges')->create($this['dso.id'], $set['dso.id']);
        }
        $children = $this->cms()->helper('graph')->children($this['dso.id'], 'signup-regalia-order');
        return $children ? reset($children) : null;
    }

    public function orderGroup(RegaliaOrderGroup $set = null): ?RegaliaOrderGroup
    {
        if ($set) {
            $this->cms()->helper('edges')->deleteParents($this['dso.id'], 'regalia-group-order');
            $this->cms()->helper('edges')->create($set['dso.id'], $this['dso.id']);
        }
        $parents = $this->cms()->helper('graph')->parents($this['dso.id'], 'regalia-group-order');
        return $parents ? reset($parents) : null;
    }

    public function parentEdgeType(Noun $parent): ?string
    {
        if ($parent instanceof RegaliaOrderGroup) {
            return 'regalia-group-order';
        }
        return parent::parentEdgeType($parent);
    }

    public function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['digraph_body'] = false;
        $map['digraph_title'] = false;
        $map['digraph_name']['label'] = 'Order type/name';
        $map['digraph_name']['tips']['ordertype'] = 'Displayed in the first column of order groups -- can be used to distinguish special orders, and is used to sort if no first/last names are available.';
        $map['regalia_hattype'] = [
            'label' => 'Hat type (if different from order group default)',
            'class' => 'select',
            'field' => 'hattype',
            'call' => [
                'options' => [[
                    'cap' => 'Cap (mortarboard)',
                    'tam' => 'Tam',
                ]],

            ],
            'weight' => 300,
        ];
        $map['regalia_extra'] = [
            'label' => 'This order is an extra, check to keep it in the extra list once assigned',
            'class' => 'checkbox',
            'field' => 'extra',
            'weight' => 200,
            'default' => true,
        ];
        if ($this->signup()) {
            $map['regalia_save_override'] = [
                'label' => 'Save these settings and apply them automatically to signups for <code>' . $this->signup()['signup.for'] . '</code> in the future',
                'class' => 'checkbox',
                'field' => 'save_override',
                'weight' => 200,
                'default' => true,
            ];
        }
        if (!$this->signup()) {
            $map['regalia_firstname'] = [
                'label' => 'First name',
                'class' => 'text',
                'field' => 'firstname',
                'weight' => 200,
                'tips' => ['Should be left blank for extras, or if this order will be associated with a signup'],
            ];
            $map['regalia_lastname'] = [
                'label' => 'Last name',
                'class' => 'text',
                'field' => 'lastname',
                'weight' => 200,
                'tips' => ['Should be left blank for extras, or if this order will be associated with a signup'],
            ];
        }
        $map['regalia_parts'] = [
            'label' => 'Regalia pieces needed',
            'field' => 'parts',
            'class' => CheckboxList::class,
            'call' => [
                'options' => [[
                    'hat' => 'Cap or tam',
                    'hood' => 'Hood',
                    'robe' => 'Robe',
                ]],
            ],
            'weight' => 201,
        ];
        $map['regalia_size'] = [
            'label' => 'Regalia size',
            'field' => 'size',
            'class' => RegaliaSizeField::class,
            'required' => true,
            'weight' => 202,
        ];
        $map['regalia_degree'] = [
            'label' => 'Degree info',
            'field' => 'degree',
            'class' => DegreeInfoField::class,
            'required' => true,
            'weight' => 203,
            'default' => [
                'institution' => 'UNIVERSITY OF NEW MEXICO, ALBUQUERQUE, NM',
                'level' => 'DOCTOR: PHD',
                'field' => 'PHD',
            ],
        ];
        return $map;
    }
}
