<?php

declare(strict_types=1);

uses(Packages\FormBuilder\Tests\TestCase::class);

use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Policies\FormPolicy;

it('allows tenant to view their tenant-owned form', function () {
    $user = (object) ['account_id' => 'acct-1'];

    $form = new Form();
    $form->id = 'form-1';
    $form->owner_scope = 'tenant';
    $form->account_id = 'acct-1';
    $form->tenant_visible = true;

    $policy = new FormPolicy();
    expect($policy->view($user, $form))->toBeTrue();
});

it('denies tenant from viewing a tenant-owned form they do not own', function () {
    $user = (object) ['account_id' => 'acct-2'];

    $form = new Form();
    $form->id = 'form-2';
    $form->owner_scope = 'tenant';
    $form->account_id = 'acct-1';
    $form->tenant_visible = true;

    $policy = new FormPolicy();
    expect($policy->view($user, $form))->toBeFalse();
});

it('allows anyone to view a tenant_visible global form', function () {
    $user = null;

    $form = new Form();
    $form->id = 'form-3';
    $form->owner_scope = 'global';
    $form->account_id = null;
    $form->tenant_visible = true;

    $policy = new FormPolicy();
    expect($policy->view($user, $form))->toBeTrue();
});

it('denies viewing of non-tenant_visible global form when not owner', function () {
    $user = (object) ['account_id' => 'acct-3'];

    $form = new Form();
    $form->id = 'form-4';
    $form->owner_scope = 'global';
    $form->account_id = null;
    $form->tenant_visible = false;

    $policy = new FormPolicy();
    expect($policy->view($user, $form))->toBeFalse();
});

it('creates a tenant clone with parent_form_id set', function () {
    $user = (object) ['account_id' => 'tenant-42'];

    $form = new Form();
    $form->id = 'form-5';
    $form->owner_scope = 'global';
    $form->account_id = null;
    $form->tenant_visible = true;
    $form->key = 'base-key';
    $form->title = 'Base Form';
    $form->status = 'active';

    $policy = new FormPolicy();
    $clone = $policy->createClone($user, $form);

    expect($clone)->toBeInstanceOf(Form::class);
    expect($clone->parent_form_id)->toBe('form-5');
    expect($clone->owner_scope)->toBe('tenant');
    expect($clone->account_id)->toBe('tenant-42');
    expect($clone->key)->toBe('base-key');
    expect($clone->title)->toBe('Base Form');
});
