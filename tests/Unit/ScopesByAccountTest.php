<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Tests\Unit;

use Packages\FormBuilder\Tests\TestCase;
use Packages\FormBuilder\Models\Form;
use Illuminate\Support\Str;

class ScopesByAccountTest extends TestCase
{
    public function test_scope_by_account_returns_null_account_records_when_null_passed(): void
    {
        Form::factory()->create(['account_id' => null]);
        Form::factory()->create(['account_id' => (string) Str::uuid7()]);

        $nullResults = Form::byAccount(null)->get();
        $this->assertGreaterThanOrEqual(1, $nullResults->count());
        foreach ($nullResults as $r) {
            $this->assertNull($r->account_id);
        }
    }
}
