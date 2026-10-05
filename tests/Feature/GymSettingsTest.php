<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GymSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->gymUser();
    }

    public function test_every_settings_page_renders(): void
    {
        foreach (['settings.profile.edit', 'settings.branding.edit', 'settings.hours.edit', 'settings.localization.edit'] as $route) {
            $this->actingAs($this->owner)->get(route($route))->assertOk();
        }
    }

    public function test_profile_can_be_updated_and_is_audited(): void
    {
        $this->actingAs($this->owner)->put(route('settings.profile.update'), [
            'name' => 'Forge Athletics',
            'phone' => '+1 555 0100',
            'country' => 'gb',
            'website' => 'https://forge.example',
            'about' => 'Strength and conditioning.',
            'public_profile_enabled' => '1',
        ])->assertRedirect(route('settings.profile.edit'))->assertSessionHas('toast');

        $tenant = $this->owner->tenant->fresh();
        $this->assertSame('Forge Athletics', $tenant->name);
        $this->assertSame('GB', $tenant->country);
        $this->assertTrue($tenant->public_profile_enabled);
        $this->assertSame('Strength and conditioning.', $tenant->setting('profile.about'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'tenant.updated', 'user_id' => $this->owner->id]);
    }

    public function test_branding_upload_and_color(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner)->put(route('settings.branding.update'), [
            'primary_color' => '#DC2626',
            'logo' => UploadedFile::fake()->image('logo.png', 256, 256),
        ])->assertRedirect(route('settings.branding.edit'));

        $tenant = $this->owner->tenant->fresh();
        $this->assertSame('#dc2626', $tenant->primary_color);
        $this->assertNotNull($tenant->logo_path);
        $this->assertStringStartsWith('tenants/'.$tenant->id.'/', $tenant->logo_path);
        Storage::disk('public')->assertExists($tenant->logo_path);

        $this->actingAs($this->owner)->put(route('settings.branding.update'), ['primary_color' => '#dc2626', 'remove_logo' => '1']);
        Storage::disk('public')->assertMissing($tenant->logo_path);
        $this->assertNull($tenant->fresh()->logo_path);
    }

    public function test_branding_rejects_unsafe_input(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner)->put(route('settings.branding.update'), [
            'primary_color' => 'red;}</style><script>',
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors(['primary_color', 'logo']);
    }

    public function test_business_hours_validation_and_save(): void
    {
        $hours = collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->mapWithKeys(fn ($day) => [$day => ['open' => '06:00', 'close' => '21:00', 'closed' => '0']])->all();
        $hours['sunday'] = ['closed' => '1'];
        $hours['monday'] = ['open' => '22:00', 'close' => '06:00', 'closed' => '0'];

        $this->actingAs($this->owner)->put(route('settings.hours.update'), ['hours' => $hours])
            ->assertSessionHasErrors('hours.monday.close');

        $hours['monday'] = ['open' => '05:30', 'close' => '23:00', 'closed' => '0'];
        $this->actingAs($this->owner)->put(route('settings.hours.update'), ['hours' => $hours])->assertSessionHasNoErrors();

        $saved = $this->owner->tenant->fresh()->business_hours;
        $this->assertSame(['open' => '05:30', 'close' => '23:00', 'closed' => false], $saved['monday']);
        $this->assertTrue($saved['sunday']['closed']);
    }

    public function test_localization_tax_and_invoice_settings(): void
    {
        $this->actingAs($this->owner)->put(route('settings.localization.update'), [
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'locale' => 'en',
            'date_format' => 'd/m/Y',
            'tax_enabled' => '1',
            'tax_label' => 'GST',
            'tax_rate' => '17',
            'invoice_prefix' => 'IPF-',
            'invoice_footer' => 'See you at the gym.',
        ])->assertSessionHasNoErrors();

        $tenant = $this->owner->tenant->fresh();
        $this->assertSame('PKR', $tenant->currency);
        $this->assertSame('Asia/Karachi', $tenant->timezone);
        $this->assertTrue($tenant->setting('tax.enabled'));
        $this->assertEquals(17, $tenant->setting('tax.rate'));
        $this->assertSame('IPF-', $tenant->setting('invoice.prefix'));
    }
}
