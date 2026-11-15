<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SocialLink;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::create(['first_name' => 'مدیر', 'last_name' => 'سایت', 'username' => 'admin_test', 'password' => 'secret123']);
        $this->admin->roles()->attach(Role::where('name', 'admin')->value('id'));
    }

    private function linkId(string $platform): int
    {
        return SocialLink::where('platform', $platform)->value('id');
    }

    public function test_admin_can_upload_and_remove_icon(): void
    {
        $id = $this->linkId('telegram');

        $component = Livewire::actingAs($this->admin)->test('admin::social-links.index');
        $index = collect($component->get('links'))->search(fn ($l) => $l['id'] === $id);

        $component
            ->set("links.$index.url", 'https://t.me/hamin')
            ->set("links.$index.is_active", true)
            ->set("icons.$id", UploadedFile::fake()->image('telegram.png', 96, 96))
            ->call('save')
            ->assertHasNoErrors();

        $link = SocialLink::find($id);
        $this->assertNotNull($link->icon);
        Storage::disk('public')->assertExists($link->icon);

        // آیکن در سایت نمایش داده می‌شود
        $this->get('/')->assertOk()->assertSee($link->icon_url, false);

        // جایگزینی: فایل قبلی پاک می‌شود
        $old = $link->icon;
        $component->set("icons.$id", UploadedFile::fake()->image('new.png', 96, 96))->call('save')->assertHasNoErrors();
        Storage::disk('public')->assertMissing($old);
        $new = SocialLink::find($id)->icon;
        Storage::disk('public')->assertExists($new);

        // حذف: برگشت به آیکن پیش‌فرض
        $component->call('clearIcon', $id)->call('save')->assertHasNoErrors();
        $this->assertNull(SocialLink::find($id)->icon);
        Storage::disk('public')->assertMissing($new);
    }

    public function test_svg_and_large_icons_are_rejected(): void
    {
        $id = $this->linkId('youtube');

        Livewire::actingAs($this->admin)->test('admin::social-links.index')
            ->set("icons.$id", UploadedFile::fake()->create('icon.svg', 2, 'image/svg+xml'))
            ->assertHasErrors(["icons.$id"]);

        Livewire::actingAs($this->admin)->test('admin::social-links.index')
            ->set("icons.$id", UploadedFile::fake()->image('big.png')->size(900))
            ->assertHasErrors(["icons.$id"]);

        $this->assertNull(SocialLink::find($id)->icon);
    }

    public function test_active_link_requires_url_and_only_visible_links_render(): void
    {
        $component = Livewire::actingAs($this->admin)->test('admin::social-links.index');

        $component->set('links.0.is_active', true)->set('links.0.url', '')->call('save')->assertHasErrors(['links.0.url']);

        $component->set('links.0.url', 'https://eitaa.com/hamin')->call('save')->assertHasNoErrors();

        $this->get('/')->assertOk()->assertSee('https://eitaa.com/hamin', false)->assertDontSee('instagram.com', false);
    }

    public function test_normal_user_cannot_open_page(): void
    {
        $user = User::create(['first_name' => 'a', 'last_name' => 'b', 'username' => 'plain_user', 'password' => 'secret123']);

        $this->actingAs($user)->get(route('social-links.index'))->assertForbidden();
    }
}
