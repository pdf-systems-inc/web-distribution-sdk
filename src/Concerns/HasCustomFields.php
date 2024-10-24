<?php

namespace Pdfsystems\WebDistributionSdk\Concerns;

use Pdfsystems\WebDistributionSdk\Dtos\CustomField;

trait HasCustomFields
{
    /**
     * @return CustomField[]
     */
    public function getAllCustomFields(): array
    {
        if (property_exists($this, 'custom_fields')) {
            return $this->custom_fields;
        }

        return [];
    }
}