<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::create(['first_name' => 'مدیر', 'last_name' => 'سایت', 'username' => 'admin_test', 'password' => 'secret123']);
        $this->admin->roles()->attach(Role::where('name', 'admin')->value('id'));
    }

    private function event(array $attributes = []): Event
    {
        return Event::create($attributes + [
            'title' => 'کارگاه عکاسی', 'slug' => 'کارگاه_عکاسی', 'is_active' => true,
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2),
        ]);
    }

    public function test_admin_saves_link_and_label(): void
    {
        $event = $this->event();

        Livewire::actingAs($this->admin)->test('admin::events.form', ['event' => $event])
            ->set('link', 'javascript:alert(1)')
            ->call('save')
            ->assertHasErrors('link')
            ->set('link', ' https://example.com/register ')
            ->set('link_label', 'ثبت‌نام در کارگاه')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('events.index'));

        $event->refresh();
        $this->assertSame('https://example.com/register', $event->link);
        $this->assertSame('ثبت‌نام در کارگاه', $event->link_label);

        // خالی کردن لینک
        Livewire::actingAs($this->admin)->test('admin::events.form', ['event' => $event])
            ->set('link', '')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertNull($event->refresh()->link);
    }

    public function test_join_button_on_event_page_and_card(): void
    {
        $event = $this->event(['link' => 'https://example.com/join']);

        $this->get(route('site.event', $event))
            ->assertOk()
            ->assertSee('href="https://example.com/join"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('شرکت در رویداد');

        $this->get(route('site.events'))->assertOk()->assertSee('https://example.com/join');
    }

    public function test_no_button_without_link_or_after_event_finished(): void
    {
        $noLink = $this->event();
        $this->get(route('site.event', $noLink))->assertOk()->assertDontSee('شرکت در رویداد');

        $finished = $this->event([
            'slug' => 'تمام_شده', 'link' => 'https://example.com/old',
            'starts_at' => now()->subDays(3), 'ends_at' => now()->subDay(),
        ]);
        $this->get(route('site.event', $finished))->assertOk()->assertDontSee('https://example.com/old');
    }
}
