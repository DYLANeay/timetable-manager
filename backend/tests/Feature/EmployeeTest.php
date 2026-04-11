<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // List
    // -------------------------------------------------------------------------

    public function test_manager_can_list_active_employees(): void
    {
        $this->asManager();
        $emp1 = $this->createEmployee(['name' => 'Alice']);
        $emp2 = $this->createEmployee(['name' => 'Bob']);
        $this->createEmployee(['name' => 'Charlie', 'is_active' => false]);

        $response = $this->getJson('/api/employees')->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Alice'));
        $this->assertTrue($names->contains('Bob'));
        $this->assertFalse($names->contains('Charlie'));
    }

    public function test_list_employees_does_not_expose_passwords(): void
    {
        $this->asManager();
        $this->createEmployee();

        $this->getJson('/api/employees')
            ->assertOk()
            ->assertJsonMissingPath('data.0.password');
    }

    public function test_employee_cannot_list_employees(): void
    {
        $this->asEmployee();

        $this->getJson('/api/employees')->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_list_employees(): void
    {
        $this->getJson('/api/employees')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function test_manager_can_create_employee(): void
    {
        Mail::fake();
        $this->asManager();

        $response = $this->postJson('/api/employees', [
            'name'  => 'New Employee',
            'email' => 'new@example.com',
            'role'  => 'employee',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Employee', 'email' => 'new@example.com']);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_create_employee_sends_invitation_email(): void
    {
        Mail::fake();
        $this->asManager();

        $this->postJson('/api/employees', [
            'name'  => 'Invited Person',
            'email' => 'invited@example.com',
            'role'  => 'employee',
        ])->assertStatus(201);

        Mail::assertSent(\App\Mail\EmployeeInvitation::class, function ($mail) {
            return $mail->hasTo('invited@example.com');
        });
    }

    public function test_create_employee_requires_unique_email(): void
    {
        Mail::fake();
        $this->asManager();
        $existing = $this->createEmployee();

        $this->postJson('/api/employees', [
            'name'  => 'Duplicate',
            'email' => $existing->email,
            'role'  => 'employee',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_create_employee_validates_role(): void
    {
        Mail::fake();
        $this->asManager();

        $this->postJson('/api/employees', [
            'name'  => 'Test',
            'email' => 'test@example.com',
            'role'  => 'superadmin',
        ])->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    public function test_create_employee_requires_all_fields(): void
    {
        $this->asManager();

        $this->postJson('/api/employees', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'role']);
    }

    public function test_employee_cannot_create_employee(): void
    {
        $this->asEmployee();

        $this->postJson('/api/employees', [
            'name'  => 'Test',
            'email' => 'test@example.com',
            'role'  => 'employee',
        ])->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function test_manager_can_update_employee_name(): void
    {
        $this->asManager();
        $emp = $this->createEmployee(['name' => 'Old Name']);

        $this->putJson("/api/employees/{$emp->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonFragment(['name' => 'New Name']);
    }

    public function test_manager_can_change_employee_role(): void
    {
        $this->asManager();
        $emp = $this->createEmployee();

        $this->putJson("/api/employees/{$emp->id}", ['role' => 'manager'])
            ->assertOk()
            ->assertJsonFragment(['role' => 'manager']);
    }

    public function test_update_email_must_remain_unique(): void
    {
        $this->asManager();
        $emp1 = $this->createEmployee();
        $emp2 = $this->createEmployee();

        $this->putJson("/api/employees/{$emp2->id}", ['email' => $emp1->email])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_employee_cannot_update_another_employee(): void
    {
        $this->asEmployee();
        $target = $this->createEmployee();

        $this->putJson("/api/employees/{$target->id}", ['name' => 'Hacker'])->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Deactivate / destroy
    // -------------------------------------------------------------------------

    public function test_manager_can_deactivate_employee(): void
    {
        $this->asManager();
        $emp = $this->createEmployee();

        $this->deleteJson("/api/employees/{$emp->id}")->assertNoContent();

        $this->assertDatabaseHas('users', ['id' => $emp->id, 'is_active' => false]);
    }

    public function test_deactivated_employee_does_not_appear_in_list(): void
    {
        $manager = $this->asManager();
        $emp = $this->createEmployee(['name' => 'Deactivated']);
        $this->deleteJson("/api/employees/{$emp->id}");

        $response = $this->getJson('/api/employees')->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertFalse($names->contains('Deactivated'));
    }

    public function test_employee_cannot_deactivate_another_employee(): void
    {
        $this->asEmployee();
        $target = $this->createEmployee();

        $this->deleteJson("/api/employees/{$target->id}")->assertForbidden();
    }
}
