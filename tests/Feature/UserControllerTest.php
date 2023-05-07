<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function createUser($overrides = [])
    {
        return User::factory()->create($overrides);
    }

    protected function createAuthenticatedUser($overrides = [])
    {
        $password = $this->faker->password;

        return User::factory()
            ->create(array_merge($overrides, ['password' => Hash::make($password)]))
            ->createToken('api-token')
            ->plainTextToken;
    }

    /**
     * Test user registration.
     *
     * @return void
     */
    public function testUserRegistration()
    {
        $password = $this->faker->password(10);
        $payload = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $response = $this->json('POST', '/api/v1/register', $payload);

        $response
            ->assertStatus(200)
            ->assertJson([
                'user',
                'access_token'
            ]);
    }

    /**
     * Test user authentication.
     *
     * @return void
     */
    public function testUserAuthentication()
    {
        $user = $this->createUser(['password' => Hash::make('password')]);

        $payload = [
            'email' => $user->email,
            'password' => 'password',
        ];

        $response = $this->json('POST', '/api/v1/login', $payload);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'access_token'
            ]);
    }

    /**
     * Test user logout.
     *
     * @return void
     */
    public function testUserLogout()
    {
        $token = $this->createAuthenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->json('POST', '/api/v1/logout');

        $response->assertStatus(200);
    }

    /**
     * Test getting all users.
     *
     * @return void
     */
    public function testGetAllUsers()
    {
        $user = $this->createAuthenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $user")
            ->json('GET', '/api/v1/users');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function testGetListOfUsers()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->get('/api/v1/users');

        $response->assertStatus(200);
    }

    /**
     * Test if can create a user
     *
     * @return void
     */
    public function testCreateUser()
    {
        $user = User::factory()->create();

        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => bcrypt('secret'),
        ];

        $response = $this->actingAs($user, 'sanctum')->post('/api/v1/users', $data);

        $response->assertStatus(201);
    }

    /**
     * Test if can get a user by id
     *
     * @return void
     */
    public function testGetUserById()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->get('/api/v1/users/' . $user->id);

        $response->assertStatus(200);
    }

    /**
     * Test if can update a user
     *
     * @return void
     */
    public function testUpdateUser()
    {
        $user = User::factory()->create();

        $data = [
            'name' => 'John Doe',
        ];

        $response = $this->actingAs($user, 'sanctum')->put('/api/v1/users/' . $user->id, $data);

        $response->assertStatus(200);
    }

    /**
     * Test if can delete a user
     *
     * @return void
     */
    public function testDeleteUser()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->delete('/api/v1/users/' . $user->id);

        $response->assertStatus(204);
    }

    /**
     * Test if can follow a user
     *
     * @return void
     */
    public function testFollowUser()
    {
        $user = User::factory()->create();
        $userToFollow = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->post('/api/v1/users/' . $userToFollow->id . '/follow');

        $response->assertStatus(200);
    }

    /**
     * Test if can unfollow a user
     *
     * @return void
     */
    public function testUnfollowUser()
    {
        $user = User::factory()->create();
        $userToFollow = User::factory()->create();
        $user->following()->attach($userToFollow->id);

        $response = $this->actingAs($user, 'sanctum')->post('/api/v1/users/' . $userToFollow->id . '/unfollow');

        $response->assertStatus(200);
    }
}