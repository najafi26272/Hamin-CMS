<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Hamin\FormBuilder\Livewire\Admin\FormEditor;
use Hamin\FormBuilder\Livewire\Admin\Submissions;
use Hamin\FormBuilder\Livewire\FormRenderer;
use Hamin\FormBuilder\Models\Form;
use Hamin\FormBuilder\Models\FormSubmission;
use Hamin\FormBuilder\Support\NationalCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('form-builder.storage.disk'));
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::create(['first_name' => 'مدیر', 'last_name' => 'سایت', 'username' => 'fb_admin', 'password' => 'secret123']);
        $this->admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        $this->member = User::create([
            'first_name' => 'سارا', 'last_name' => 'محمدی', 'username' => 'sara', 'password' => 'secret123',
            'mobile' => '09121234567', 'email' => 'sara@example.com',
        ]);
    }

    // ساخت فرم کامل از طریق ویرایشگر پنل
    private function buildForm(): Form
    {
        $editor = Livewire::actingAs($this->admin)->test(FormEditor::class)
            ->set('info.title', 'فراخوان جشنواره عکس')
            ->set('info.is_active', true)
            ->call('addProfileField', 'first_name')
            ->call('addProfileField', 'mobile')
            ->call('addProfileField', 'email')
            ->call('addField', 'text')
            ->call('addField', 'number')
            ->call('addField', 'textarea')
            ->call('addField', 'select')
            ->call('addField', 'select')
            ->call('addField', 'checkbox')
            ->call('addField', 'checkbox')
            ->call('addField', 'radio')
            ->call('addField', 'file');

        // email قابل ویرایش، بقیه پروفایل‌ها قفل
        $editor->set('fields.2.is_readonly', false)
            ->set('fields.3.label', 'کد ملی')->set('fields.3.settings.format', 'national_code')->set('fields.3.is_required', true)
            ->set('fields.4.label', 'تعداد اثر')->set('fields.4.settings.min', 1)->set('fields.4.settings.max', 5)->set('fields.4.settings.integer', true)
            ->set('fields.5.label', 'معرفی')
            ->set('fields.6.label', 'رشته')->set('fields.6.options', "عکاسی\nنقاشی\nخوشنویسی")->set('fields.6.is_required', true)
            ->set('fields.7.label', 'زمینه‌ها')->set('fields.7.options', "پرتره\nطبیعت\nشهری")->set('fields.7.settings.multiple', true)
            ->set('fields.8.label', 'قوانین را می‌پذیرم')->set('fields.8.options', '')->set('fields.8.is_required', true)
            ->set('fields.9.label', 'روزهای آزاد')->set('fields.9.options', "شنبه\nیکشنبه")
            ->set('fields.10.label', 'نحوه آشنایی')->set('fields.10.options', "دوستان\nشبکه اجتماعی")
            ->set('fields.11.label', 'فایل اثر')->set('fields.11.is_required', true)
            ->set('fields.11.settings.extensions', ['jpg', 'png'])->set('fields.11.settings.max_size_kb', 500)
            ->set('fields.11.settings.multiple', true)->set('fields.11.settings.max_files', 2)
            ->call('save')
            ->assertHasNoErrors();

        return Form::with('fields')->where('title', 'فراخوان جشنواره عکس')->firstOrFail();
    }

    private function names(Form $form): array
    {
        return $form->fields->pluck('name', 'label')->all();
    }

    public function test_editor_saves_form_with_all_field_types(): void
    {
        $form = $this->buildForm();

        $this->assertSame(12, $form->fields->count());
        $this->assertSame(['profile', 'profile', 'profile'], $form->fields->take(3)->pluck('kind')->all());
        $this->assertTrue($form->fields[0]->isLocked());
        $this->assertFalse($form->fields[2]->isLocked());
        $this->assertTrue($form->fields->firstWhere('label', 'زمینه‌ها')->isMultiple());
        $this->assertTrue($form->fields->firstWhere('label', 'قوانین را می‌پذیرم')->isSingleCheckbox());
        $this->assertSame(['jpg', 'png'], $form->fields->firstWhere('label', 'فایل اثر')->settings['extensions']);
        $this->assertTrue($form->needsLogin());
    }

    public function test_editor_validates_options_and_duplicate_profile_fields(): void
    {
        Livewire::actingAs($this->admin)->test(FormEditor::class)
            ->set('info.title', 'فرم')
            ->call('addField', 'radio')->set('fields.0.options', 'فقط یکی')
            ->call('save')
            ->assertHasErrors(['fields.0.options']);

        Livewire::actingAs($this->admin)->test(FormEditor::class)
            ->call('addProfileField', 'mobile')
            ->call('addProfileField', 'mobile')
            ->assertCount('fields', 1);
    }

    public function test_profile_fields_are_prefilled_and_locked_values_cannot_be_tampered(): void
    {
        $form = $this->buildForm();
        $n = $this->names($form);

        $component = Livewire::actingAs($this->member)->test(FormRenderer::class, ['slug' => $form->slug])
            ->assertSet('values.'.$n['نام'], 'سارا')
            ->assertSet('values.'.$n['شماره موبایل'], '09121234567');

        $component
            ->set('values.'.$n['نام'], 'نام جعلی')             // قفل: باید نادیده گرفته شود
            ->set('values.'.$n['ایمیل'], 'new@example.com')      // قابل ویرایش
            ->set('values.'.$n['کد ملی'], '۰۰۸۴۵۷۵۹۴۸')        // ارقام فارسی، کد معتبر
            ->set('values.'.$n['تعداد اثر'], '۳')
            ->set('values.'.$n['رشته'], 'عکاسی')
            ->set('values.'.$n['زمینه‌ها'], ['پرتره', 'شهری'])
            ->set('values.'.$n['قوانین را می‌پذیرم'], true)
            ->set('values.'.$n['روزهای آزاد'], ['شنبه'])
            ->set('values.'.$n['نحوه آشنایی'], 'دوستان')
            ->set('files.'.$n['فایل اثر'], [UploadedFile::fake()->image('a.jpg', 200, 200), UploadedFile::fake()->image('b.png', 200, 200)])
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $submission = FormSubmission::firstOrFail();
        $this->assertSame($this->member->id, $submission->user_id);
        $this->assertSame('سارا', $submission->data[$n['نام']]);
        $this->assertSame('new@example.com', $submission->data[$n['ایمیل']]);
        $this->assertSame('0084575948', $submission->data[$n['کد ملی']]);
        $this->assertSame('3', $submission->data[$n['تعداد اثر']]);
        $this->assertSame(['پرتره', 'شهری'], $submission->data[$n['زمینه‌ها']]);
        $this->assertTrue($submission->data[$n['قوانین را می‌پذیرم']]);
        $this->assertCount(2, $submission->data[$n['فایل اثر']]);
        Storage::disk(config('form-builder.storage.disk'))->assertExists($submission->data[$n['فایل اثر']][0]['path']);

        // پروفایل کاربر تغییر نکرده است
        $this->assertSame('sara@example.com', $this->member->fresh()->email);
    }

    public function test_validation_rules_are_enforced(): void
    {
        $form = $this->buildForm();
        $n = $this->names($form);

        Livewire::actingAs($this->member)->test(FormRenderer::class, ['slug' => $form->slug])
            ->set('values.'.$n['کد ملی'], '1234567890')                 // رقم کنترل نادرست
            ->set('values.'.$n['تعداد اثر'], '9')                        // بیشتر از حداکثر
            ->set('values.'.$n['رشته'], 'گزینه ساختگی')                  // خارج از گزینه‌ها
            ->set('values.'.$n['ایمیل'], 'not-an-email')
            ->call('submit')
            ->assertHasErrors([
                'values.'.$n['کد ملی'], 'values.'.$n['تعداد اثر'], 'values.'.$n['رشته'], 'values.'.$n['ایمیل'],
                'values.'.$n['قوانین را می‌پذیرم'], 'files.'.$n['فایل اثر'],
            ]);

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_file_type_size_and_count_limits(): void
    {
        $form = $this->buildForm();
        $file = 'files.'.$this->names($form)['فایل اثر'];

        Livewire::actingAs($this->member)->test(FormRenderer::class, ['slug' => $form->slug])
            ->set($file, [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])
            ->assertHasErrors([$file.'.0']);

        Livewire::actingAs($this->member)->test(FormRenderer::class, ['slug' => $form->slug])
            ->set($file, [UploadedFile::fake()->image('big.jpg')->size(900)])
            ->assertHasErrors([$file.'.0']);

        Livewire::actingAs($this->member)->test(FormRenderer::class, ['slug' => $form->slug])
            ->set($file, [UploadedFile::fake()->image('1.jpg'), UploadedFile::fake()->image('2.jpg'), UploadedFile::fake()->image('3.jpg')])
            ->assertHasErrors([$file]);
    }

    public function test_guest_sees_login_notice_and_inactive_form_is_hidden(): void
    {
        $form = $this->buildForm();
        auth()->logout(); // buildForm با کاربر مدیر وارد شده است

        $this->get(route('form-builder.show', $form->slug))->assertOk()->assertSee('ابتدا وارد حساب کاربری خود شوید');

        $form->update(['is_active' => false]);
        $this->get(route('form-builder.show', $form->slug))->assertNotFound();
        $this->actingAs($this->admin)->get(route('form-builder.show', $form->slug))->assertOk()->assertSee('پیش‌نمایش');
    }

    public function test_single_submission_per_user(): void
    {
        $form = Form::create(['title' => 'نظرسنجی', 'slug' => 'poll', 'is_active' => true, 'allow_multiple' => false, 'requires_auth' => true]);
        $field = $form->fields()->create(['kind' => 'input', 'type' => 'radio', 'name' => 'q', 'label' => 'نظر', 'is_required' => true, 'options' => ['خوب', 'بد']]);

        Livewire::actingAs($this->member)->test(FormRenderer::class, ['slug' => 'poll'])
            ->set('values.q', 'خوب')->call('submit')->assertHasNoErrors();

        Livewire::actingAs($this->member)->test(FormRenderer::class, ['slug' => 'poll'])
            ->assertSee('قبلا این فرم را ثبت کرده‌اید')
            ->set('values.q', 'بد')->call('submit')->assertHasErrors(['form']);

        $this->assertSame(1, FormSubmission::count());
    }

    public function test_public_form_without_profile_fields_accepts_guests_and_api(): void
    {
        $form = Form::create(['title' => 'تماس با ما', 'slug' => 'contact', 'is_active' => true]);
        $form->fields()->create(['kind' => 'input', 'type' => 'text', 'name' => 'name', 'label' => 'نام', 'is_required' => true]);
        $form->fields()->create(['kind' => 'input', 'type' => 'text', 'name' => 'mobile', 'label' => 'موبایل', 'is_required' => true, 'settings' => ['format' => 'mobile']]);

        $this->get(route('form-builder.show', 'contact'))->assertOk()->assertSee('تماس با ما');

        $this->getJson(route('form-builder.api.show', 'contact'))
            ->assertOk()
            ->assertJsonPath('form.can_submit', true)
            ->assertJsonCount(2, 'fields');

        $this->postJson(route('form-builder.api.submit', 'contact'), ['values' => ['name' => 'علی', 'mobile' => '0912']])
            ->assertStatus(422)->assertJsonValidationErrors(['values.mobile']);

        $this->postJson(route('form-builder.api.submit', 'contact'), ['values' => ['name' => 'علی', 'mobile' => '۰۹۱۲۱۱۱۲۲۳۳']])
            ->assertCreated();

        $this->assertSame('09121112233', FormSubmission::first()->data['mobile']);
        $this->assertNull(FormSubmission::first()->user_id);
    }

    public function test_admin_pages_export_download_and_access_control(): void
    {
        $form = $this->buildForm();
        $n = $this->names($form);
        $file = UploadedFile::fake()->image('art.jpg');
        $submission = $form->submissions()->create([
            'user_id' => $this->member->id,
            'data' => [$n['نام'] => 'سارا', $n['فایل اثر'] => [['path' => $file->store('form-builder/test', config('form-builder.storage.disk')), 'name' => 'art.jpg', 'size' => 100]]],
            'labels' => [$n['نام'] => 'نام', $n['فایل اثر'] => 'فایل اثر'],
        ]);

        $this->actingAs($this->admin)->get(route('form-builder.admin.forms.index'))->assertOk()->assertSee('فراخوان جشنواره عکس');
        $this->actingAs($this->admin)->get(route('form-builder.admin.forms.edit', $form))->assertOk();
        $this->actingAs($this->admin)->get(route('form-builder.admin.submissions.index', $form))->assertOk()->assertSee('سارا');

        $csv = $this->actingAs($this->admin)->get(route('form-builder.admin.submissions.export', $form));
        $csv->assertOk();
        $this->assertStringContainsString('سارا', $csv->streamedContent());

        $this->actingAs($this->admin)->get(route('form-builder.admin.submissions.file', [$submission, $n['فایل اثر'], 0]))->assertOk();

        // کاربر عادی به پنل و فایل‌ها دسترسی ندارد
        $this->actingAs($this->member)->get(route('form-builder.admin.forms.index'))->assertForbidden();
        $this->actingAs($this->member)->get(route('form-builder.admin.submissions.file', [$submission, $n['فایل اثر'], 0]))->assertForbidden();

        // حذف پاسخ، فایلش را هم پاک می‌کند
        $path = $submission->data[$n['فایل اثر']][0]['path'];
        Livewire::actingAs($this->admin)->test(Submissions::class, ['form' => $form])->call('delete', $submission->id);
        Storage::disk(config('form-builder.storage.disk'))->assertMissing($path);
    }

    public function test_national_code_algorithm(): void
    {
        $this->assertTrue(NationalCode::isValid('0084575948'));
        $this->assertFalse(NationalCode::isValid('1111111111'));
        $this->assertFalse(NationalCode::isValid('0084575940'));
        $this->assertFalse(NationalCode::isValid('12345'));
    }
}
