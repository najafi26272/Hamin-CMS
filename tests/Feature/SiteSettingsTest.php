<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $this->admin = $this->makeUser('admin_test', 'admin');
    }

    private function makeUser(string $username, ?string $role = null): User
    {
        $user = User::create(['first_name' => 'کاربر', 'last_name' => 'تست', 'username' => $username, 'password' => 'secret123']);
        if ($role) {
            $user->roles()->attach(Role::where('name', $role)->value('id'));
        }

        return $user->fresh();
    }

    public function test_admin_saves_title_description_logo_and_favicon(): void
    {
        Livewire::actingAs($this->admin)->test('admin::settings.index')
            ->set('site_title', 'هامین تست')
            ->set('site_description', 'توضیح آزمایشی')
            ->set('logo', UploadedFile::fake()->image('logo.png', 400, 100))
            ->set('favicon', UploadedFile::fake()->image('icon.png', 64, 64))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('هامین تست', Setting::siteTitle());
        $logo = Setting::get('logo');
        Storage::disk('public')->assertExists($logo);
        Storage::disk('public')->assertExists(Setting::get('favicon'));

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>هامین تست</title>', false)
            ->assertSee('توضیح آزمایشی')
            ->assertSee(Storage::disk('public')->url($logo));

        // برگشت به لوگوی پیش‌فرض: فایل قبلی حذف می‌شود
        Livewire::actingAs($this->admin)->test('admin::settings.index')
            ->call('clearImage', 'logo')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(Setting::get('logo'));
        Storage::disk('public')->assertMissing($logo);
    }

    public function test_settings_validation(): void
    {
        Livewire::actingAs($this->admin)->test('admin::settings.index')
            ->set('site_title', '')
            ->set('logo', UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'))
            ->set('favicon', UploadedFile::fake()->create('icon.php', 10, 'text/plain'))
            ->call('save')
            ->assertHasErrors(['site_title', 'logo', 'favicon']);

        $this->assertNull(Setting::query()->where('key', 'site_title')->first());
    }

    public function test_settings_page_needs_permission(): void
    {
        $this->actingAs($this->makeUser('plain_user'))->get(route('settings.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('settings.index'))->assertOk()->assertSee('تنظیمات سایت');
    }

    public function test_maintenance_mode_shows_page_to_visitors_only(): void
    {
        Setting::set(['maintenance_mode' => true]);

        $this->get('/')->assertStatus(503)->assertSee('در حال توسعه هستیم');
        $this->get(route('site.posts'))->assertStatus(503);
        $this->get(route('register'))->assertStatus(503);
        $this->get(route('login'))->assertOk()->assertSee('فقط مدیران و نویسندگان');

        // مدیر و نویسنده سایت را عادی می‌بینند
        $this->actingAs($this->admin)->get('/')->assertOk()->assertSee('حالت توسعه فعال است');
        $this->actingAs($this->makeUser('author_test', 'author'))->get('/')->assertOk();

        Setting::set(['maintenance_mode' => false]);
        auth()->logout();
        $this->get('/')->assertOk()->assertDontSee('در حال توسعه هستیم');
    }

    public function test_only_admin_and_authors_can_login_in_maintenance(): void
    {
        Setting::set(['maintenance_mode' => true]);
        $this->makeUser('artist_test', 'artist');
        $this->makeUser('author_test', 'author');

        Livewire::test('auth::login')
            ->set('username', 'artist_test')->set('password', 'secret123')
            ->call('login')
            ->assertHasErrors('username');
        $this->assertGuest();

        Livewire::test('auth::login')
            ->set('username', 'author_test')->set('password', 'secret123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_logged_in_regular_user_is_logged_out_when_maintenance_is_on(): void
    {
        $user = $this->makeUser('artist_test', 'artist');
        Setting::set(['maintenance_mode' => true]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
