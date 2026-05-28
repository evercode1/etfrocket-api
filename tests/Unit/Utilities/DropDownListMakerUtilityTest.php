<?php

namespace Tests\Unit\Utilities;

use App\Models\Status;
use App\Utilities\DropDownListMaker;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DropDownListMakerUtilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('statuses')->truncate();

        $this->seed(StatusSeeder::class);
    }

    protected function tearDown(): void
    {
        DB::table('statuses')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_select_list_from_label_name()
    {
        $list = (new DropDownListMaker)->getSelectsFromLabelName('Status Name');

        $this->assertEquals('Active', $list[Status::ACTIVE]);
        $this->assertEquals('Closed', $list[Status::CLOSED]);
        $this->assertEquals('Pending Email Verification', $list[Status::PENDING_EMAIL_VERIFICATION]);
    }

    public function test_it_orders_select_list_by_name_ascending()
    {
        $list = (new DropDownListMaker)->getSelectsFromLabelName('Status Name');

        $values = $list->values()->toArray();

        $sortedValues = $values;

        sort($sortedValues);

        $this->assertEquals($sortedValues, $values);
    }

    public function test_it_sets_column_name_from_label_name()
    {
        $maker = new DropDownListMaker;

        $maker->getSelectsFromLabelName('Status Name');

        $this->assertEquals('status_name', $maker->column_name);
    }
}
