<?php
namespace Digraph\Modules\ous_event_regalia\Regalia;

use Digraph\CMS;
use Digraph\Forms\Fields\AbstractAutocomplete;
use Formward\FieldInterface;

class AlmaMaterField extends AbstractAutocomplete
{
    const SOURCE = 'jostensalmamater';

    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms = null)
    {
        parent::__construct($label, $name, $parent, $cms);
        $this->addTip('Options are limited by Jostens\' selection. If your alma mater is not listed, search and select "NOT FOUND" and someone will contact you to find the closest color match available from Jostens.');
    }

    protected function validateValue(string $value): bool
    {
        return $value == "NOT FOUND" || !!$this->cms->helper('jostens')->locateInstitution($value);
    }
}
