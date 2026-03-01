<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Policies;

use Packages\FormBuilder\Models\Form;

/**
 * Policy enforcing owner_scope and tenant_visible for forms.
 *
 * - Tenant-scoped forms (owner_scope === 'tenant') are visible/editable only to the owning account.
 * - Global-scoped forms (owner_scope === 'global') are visible to tenants when tenant_visible === true.
 * - Tenants may clone global forms; clones become tenant-owned with parent_form_id set.
 *
 * NOTE: methods accept a loosely-typed $user to avoid coupling to the host application's User class.
 * The policy expects $user->account_id when a user/tenant context is provided.
 */
final class FormPolicy
{
    /**
     * Determine whether the user can view the form.
     */
    public function view(mixed $user = null, Form $form): bool
    {
        if ($form->owner_scope === 'tenant') {
            if ($user === null) {
                return false;
            }

            $accountId = $this->resolveAccountId($user);
            return $accountId !== null && (string) $accountId === (string) $form->account_id;
        }

        // owner_scope === 'global'
        if (!empty($form->tenant_visible)) {
            return true;
        }

        // non-visible global forms can be viewed by their owning account (if any)
        if ($user === null) {
            return false;
        }

        $accountId = $this->resolveAccountId($user);
        return $accountId !== null && (string) $accountId === (string) $form->account_id;
    }

    /**
     * Determine whether the user can create a tenant-scoped form.
     * Creating global forms is considered an administrative action and not allowed here.
     */
    public function create(mixed $user = null): bool
    {
        if ($user === null) {
            return false;
        }

        $accountId = $this->resolveAccountId($user);
        return $accountId !== null;
    }

    /**
     * Determine whether the user can update the form.
     */
    public function update(mixed $user = null, Form $form): bool
    {
        // Only owning tenant may update tenant-scoped forms.
        if ($form->owner_scope === 'tenant') {
            if ($user === null) {
                return false;
            }

            $accountId = $this->resolveAccountId($user);
            return $accountId !== null && (string) $accountId === (string) $form->account_id;
        }

        // Global forms are not editable by arbitrary tenants.
        if ($user === null) {
            return false;
        }

        $accountId = $this->resolveAccountId($user);
        return $accountId !== null && (string) $accountId === (string) $form->account_id;
    }

    /**
     * Determine whether the user can delete the form.
     */
    public function delete(mixed $user = null, Form $form): bool
    {
        return $this->update($user, $form);
    }

    /**
     * Determine whether the user may clone the given form.
     * Tenants may clone global forms (visible or not), which creates a tenant-owned copy.
     */
    public function canClone(mixed $user = null, Form $form): bool
    {
        if ($form->owner_scope === 'tenant') {
            // Cloning tenant-owned forms is allowed only for the owning tenant.
            if ($user === null) {
                return false;
            }

            $accountId = $this->resolveAccountId($user);
            return $accountId !== null && (string) $accountId === (string) $form->account_id;
        }

        // Global forms can be cloned by any tenant user (tenant context required).
        if ($user === null) {
            return false;
        }

        return $this->resolveAccountId($user) !== null;
    }

    /**
     * Create a tenant-scoped clone of a form. Caller should verify canClone() first.
     * The returned Form instance is unsaved.
     */
    public function createClone(mixed $user, Form $form): Form
    {
        $clone = new Form();

        // Copy public attributes. Keep minimal set; callers can adjust additional fields.
        $clone->key = $form->key;
        $clone->title = $form->title;
        $clone->owner_scope = 'tenant';
        $clone->parent_form_id = $form->id;
        $clone->status = $form->status ?? 'active';
        $clone->tenant_visible = true;

        $accountId = $this->resolveAccountId($user);
        $clone->account_id = $accountId;

        // Do not set id - let persistence layer generate it.
        return $clone;
    }

    /**
     * Resolve an account id from the provided user-like object.
     */
    private function resolveAccountId(mixed $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if (is_object($user) && property_exists($user, 'account_id')) {
            return (string) $user->account_id;
        }

        if (is_array($user) && array_key_exists('account_id', $user)) {
            return (string) $user['account_id'];
        }

        // Fallback for common User implementations.
        if (is_object($user) && method_exists($user, 'getAttribute')) {
            $val = $user->getAttribute('account_id');
            return $val === null ? null : (string) $val;
        }

        return null;
    }
}
