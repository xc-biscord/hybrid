<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validators\AccountUpdateValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de la normalisation des données de mise à jour de compte.
 * Logique pure (aucune base, aucune session) : un comportement par test.
 */
final class AccountUpdateValidatorTest extends TestCase
{
    private AccountUpdateValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AccountUpdateValidator();
    }

    public function test_normalize_returns_all_null_for_null_payload(): void
    {
        $this->assertSame(
            ['username' => null, 'email' => null, 'new_password' => null, 'current_password' => null],
            $this->validator->normalize(null)
        );
    }

    public function test_normalize_trims_username_and_email(): void
    {
        $result = $this->validator->normalize(['username' => '  alice  ', 'email' => '  a@b.test ']);

        $this->assertSame('alice', $result['username']);
        $this->assertSame('a@b.test', $result['email']);
    }

    public function test_normalize_converts_empty_strings_to_null(): void
    {
        $result = $this->validator->normalize(['username' => '   ', 'email' => '', 'password' => '']);

        $this->assertNull($result['username']);
        $this->assertNull($result['email']);
        $this->assertNull($result['new_password']);
    }

    public function test_normalize_maps_password_key_to_new_password(): void
    {
        $result = $this->validator->normalize(['password' => 'secret']);

        $this->assertSame('secret', $result['new_password']);
    }

    public function test_normalize_preserves_current_password_when_present(): void
    {
        $result = $this->validator->normalize(['current_password' => 'old']);

        $this->assertSame('old', $result['current_password']);
    }

    public function test_has_any_updatable_field_is_true_when_username_set(): void
    {
        $data = ['username' => 'alice', 'email' => null, 'new_password' => null, 'current_password' => null];

        $this->assertTrue($this->validator->hasAnyUpdatableField($data));
    }

    public function test_has_any_updatable_field_is_false_when_only_current_password(): void
    {
        $data = ['username' => null, 'email' => null, 'new_password' => null, 'current_password' => 'old'];

        $this->assertFalse($this->validator->hasAnyUpdatableField($data));
    }
}
