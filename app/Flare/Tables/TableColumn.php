<?php

namespace App\Flare\Tables;

use Closure;

class TableColumn
{
    public function __construct(
        public readonly string $label,
        public readonly ?string $field = null,
        public readonly bool $searchable = false,
        public readonly bool $sortable = false,
        public readonly bool $html = false,
        public readonly ?Closure $render = null,
        public readonly ?Closure $sortUsing = null,
    ) {}

    public function value(mixed $row): mixed
    {
        if (! is_null($this->render)) {
            return ($this->render)($row);
        }

        return data_get($row, $this->field);
    }

    public function renderedValue(mixed $row): string
    {
        $value = $this->value($row);

        if ($this->html) {
            return strval($value);
        }

        return e(strval($value));
    }
}
