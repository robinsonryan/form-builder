<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Tests\Feature;

use Packages\FormBuilder\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class MigrationsTest extends TestCase
{
    public function test_core_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('forms'), 'forms table should exist');
        $this->assertTrue(Schema::hasTable('form_versions'), 'form_versions table should exist');
        $this->assertTrue(Schema::hasTable('form_fragments'), 'form_fragments table should exist');
        $this->assertTrue(Schema::hasTable('form_responses'), 'form_responses table should exist');
        $this->assertTrue(Schema::hasTable('form_extensions'), 'form_extensions table should exist');
        $this->assertTrue(Schema::hasTable('form_publications'), 'form_publications table should exist');
    }
}
