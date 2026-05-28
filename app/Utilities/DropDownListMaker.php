<?php

namespace App\Utilities;

use Illuminate\Support\Str;

class DropDownListMaker
{
    public string $column_name;

    public function getSelectsFromLabelName(string $label_name)
    {

        $list = $this->formatModel($label_name);

        $list = $this->callModel($list);

        return $list;

    }

    private function formatModel(string $label_name)
    {

        // convert to model name

        $list = Str::remove('Name', $label_name);

        $list = ucwords($list);

        $this->setColumnName($list);

        $list = Str::replace(' ', '', $list);

        $list = Str::singular($list);

        return $list;

    }

    private function callModel(string $list)
    {

        $class = '\\App\\Models\\'.$list;

        $instance = new $class;

        $list = $instance->orderBy($this->column_name, 'asc')->pluck($this->column_name, 'id');

        return $list;

    }

    private function setColumnName(string $list)
    {

        $list = strtolower($list);

        $list = Str::singular($list);

        $this->column_name = Str::snake($list).'_name';

    }
}
