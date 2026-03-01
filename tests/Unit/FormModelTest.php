<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Tests\Unit;

use Packages\FormBuilder\Tests\TestCase;
use Packages\FormBuilder\Models\Form;
use Illuminate\Support\Str;

class FormModelTest extends TestCase
{
    public function test_factory_creates_a_form(): void
    {
        $before = Form::count();
        $form = Form::factory()->create();
        $this->assertIsString($form->id);
        $this->assertEquals($before + 1, Form::count());
    }

    public function test_tenant_visible_is_cast_to_boolean(): void
    {
        $form = Form::factory()->create(['tenant_visible' => false]);
        $this->assertFalse($form->fresh()->tenant_visible);

        $form = Form::factory()->create(['tenant_visible' => true]);
        $this->assertTrue($form->fresh()->tenant_visible);
    }

    public function test_scope_by_account_filters_correctly(): void
    {
        $accountId = (string) Str::uuid7();

        Form::factory()->create(['account_id' => $accountId]);
        Form::factory()->create(['account_id' => null]);

        $this->assertCount(1, Form::byAccount($accountId)->get());
        $this->assertCount(1, Form::byAccount(null)->get());
    }
}
