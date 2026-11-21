<?php

namespace Tests\Feature;

use App\Http\Middleware\StartDonorSession;
use App\Models\BloodDonor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DonorGuardSeparationTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['patient'])]
    #[TestWith(['doctor'])]
    public function test_hospital_session_is_redirected_from_every_donor_authenticated_route(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        BloodDonor::factory()->independent()->create(['id' => $user->id]);
        $routes = $this->protectedRoutes('auth:donor');
        $this->assertNotEmpty($routes);
        $this->assertContains('blood-connect.donor.dashboard', $routes->map->getName()->all());
        $this->actingAs($user, 'web');

        foreach ($routes as $route) {
            foreach ($route->methods() as $method) {
                $this->call($method, $this->routePath($route), [], $this->prepareCookiesForRequest())
                    ->assertRedirect(route('blood-connect.donor.login'));
            }
        }

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('donor');
    }

    public function test_donor_session_is_redirected_from_every_hospital_authenticated_browser_route(): void
    {
        $donor = $this->signInDonorWithCookie();
        $routes = $this->protectedRoutes('auth')->reject(fn (Route $route): bool => str_starts_with($route->uri(), 'api/'));
        $this->assertNotEmpty($routes);
        foreach (['patient.dashboard', 'doctor.dashboard', 'admin.dashboard', 'receptionist.dashboard', 'lab.dashboard'] as $name) {
            $this->assertContains($name, $routes->map->getName()->all());
        }

        foreach ($routes as $route) {
            foreach ($route->methods() as $method) {
                $this->call($method, $this->routePath($route), [], $this->prepareCookiesForRequest())
                    ->assertRedirect(route('login'));
            }
        }

        $this->get('/blood-connect/donor/dashboard')->assertOk();
        $this->assertAuthenticatedAs($donor, 'donor');
        $this->assertGuest('web');
    }

    public function test_donor_session_receives_401_from_every_hospital_authenticated_api_route(): void
    {
        $donor = $this->signInDonorWithCookie();
        $routes = $this->protectedRoutes('auth')->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/'));
        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            foreach ($route->methods() as $method) {
                $response = $this->call($method, $this->routePath($route), [], $this->prepareCookiesForRequest())
                    ->assertUnauthorized();
                if ($method !== 'HEAD') {
                    $response->assertJsonPath('message', 'Unauthenticated.');
                }
            }
        }

        $this->get('/blood-connect/donor/dashboard')->assertOk();
        $this->assertAuthenticatedAs($donor, 'donor');
        $this->assertGuest('web');
    }

    private function signInDonorWithCookie(): BloodDonor
    {
        $donor = BloodDonor::factory()->independent()->create();
        User::factory()->patient()->create(['id' => $donor->id]);
        $login = $this->post('/blood-connect/donor/login', ['phone' => $donor->phone, 'password' => 'donor-password'])
            ->assertRedirect('/blood-connect/donor/dashboard');
        $this->withCookie(StartDonorSession::COOKIE, $login->getCookie(StartDonorSession::COOKIE)->getValue());
        Auth::forgetGuards();
        Auth::shouldUse('web');
        $this->get('/blood-connect/donor/dashboard')->assertOk();

        return $donor;
    }

    /** @return Collection<int, Route> */
    private function protectedRoutes(string $middleware): Collection
    {
        return collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => in_array($middleware, $route->gatherMiddleware(), true));
    }

    private function routePath(Route $route): string
    {
        return '/'.preg_replace_callback('/\{([^}]+)\}/', fn (array $match): string => match (rtrim($match[1], '?')) {
            'resourceType' => 'lab_report',
            'notification', 'session' => '00000000-0000-4000-8000-000000000001',
            default => '1',
        }, $route->uri());
    }
}
