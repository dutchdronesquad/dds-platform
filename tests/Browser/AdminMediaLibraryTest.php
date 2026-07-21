<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('the media library renders previews and filters without browser errors', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    MediaAsset::factory()->named('community-race.jpg')->create();
    MediaAsset::factory()->pdf('wedstrijdreglement.pdf')->create();
    MediaAsset::factory()->archived()->named('oude-poster.jpg')->create();

    $this->actingAs($admin);

    visit('/dashboard/media')
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->assertSee('Mediabibliotheek')
        ->assertSee('community-race.jpg')
        ->assertSee('wedstrijdreglement.pdf')
        ->assertDontSee('oude-poster.jpg')
        ->assertSee('Afbeeldingen')
        ->assertSee('Documenten')
        ->assertSee('Ongebruikt')
        ->assertSee('2 media-items gevonden')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->click('internal:role=button[name="Filter op type"s]')
        ->click('Pdf’s')
        ->wait(1)
        ->assertSee('wedstrijdreglement.pdf')
        ->assertDontSee('community-race.jpg')
        ->assertScript("window.location.search.includes('category=document')")
        ->click('Filters wissen')
        ->wait(1)
        ->assertSee('community-race.jpg')
        ->assertNoAccessibilityIssues()
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('the media library stays usable on a mobile viewport', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    MediaAsset::factory()->named('mobiele-racefoto.jpg')->create([
        'alt_text' => null,
    ]);
    MediaAsset::factory()->pdf('mobiel-reglement.pdf')->create();

    $this->actingAs($admin);

    visit('/dashboard/media')
        ->on()->mobile()
        ->resize(390, 844)
        ->assertNoJavaScriptErrors()
        ->assertSee('Mediabibliotheek')
        ->assertSee('mobiele-racefoto.jpg')
        ->assertSee('mobiel-reglement.pdf')
        ->assertSee('Alt-tekst ontbreekt')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoConsoleLogs();
});

test('the media library opens file details in a desktop dialog without duplicate usage copy', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $mediaAsset = MediaAsset::factory()->named('dialog-racefoto.jpg')->create();

    foreach (range(1, 5) as $number) {
        Event::factory()->create([
            'title' => "Gekoppeld event {$number}",
            'cover_image_id' => $mediaAsset->id,
        ]);
    }

    $this->actingAs($admin);

    $page = visit('/dashboard/media')
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->assertSee('5 koppelingen')
        ->assertScript(
            "document.querySelector('[data-test=\"media-usage-trigger-{$mediaAsset->id}\"]')?.textContent?.trim() === '5 koppelingen'",
        )
        ->click("@media-details-table-preview-{$mediaAsset->id}")
        ->wait(1)
        ->assertAttribute('@media-details-overlay', 'data-mode', 'dialog')
        ->assertSee('Mediadetails')
        ->assertSee('dialog-racefoto.jpg')
        ->assertSee('JPEG-afbeelding')
        ->assertSee('Bestandsgegevens')
        ->assertSee('Beschrijving voor toegankelijkheid')
        ->assertButtonDisabled('Opslaan')
        ->assertSee('Gekoppeld event 1')
        ->assertSee('Gekoppeld event 5')
        ->fill('#alt_text_nl', 'Dronepiloot op het startveld')
        ->resize(390, 844)
        ->wait(1)
        ->assertAttribute('@media-details-overlay', 'data-mode', 'drawer')
        ->assertValue('#alt_text_nl', 'Dronepiloot op het startveld')
        ->resize(1440, 1000)
        ->wait(1)
        ->assertAttribute('@media-details-overlay', 'data-mode', 'dialog')
        ->assertValue('#alt_text_nl', 'Dronepiloot op het startveld')
        ->assertButtonEnabled('Opslaan');

    $page->script('window.confirm = () => false');

    $page
        ->click('@media-details-close')
        ->assertPresent('@media-details-overlay')
        ->click('@media-details-submit')
        ->wait(1)
        ->assertSee('Opgeslagen')
        ->assertPresent('@media-details-overlay')
        ->assertButtonDisabled('Opslaan')
        ->assertScript("window.location.pathname === '/dashboard/media'")
        ->assertNoAccessibilityIssues()
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect($mediaAsset->refresh()->alt_text['nl'])->toBe('Dronepiloot op het startveld');
});

test('the media library opens file details in a mobile drawer', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $mediaAsset = MediaAsset::factory()->named('drawer-racefoto.jpg')->create();

    $this->actingAs($admin);

    visit('/dashboard/media')
        ->on()->mobile()
        ->resize(390, 844)
        ->assertNoJavaScriptErrors()
        ->click("@media-details-card-preview-{$mediaAsset->id}")
        ->wait(1)
        ->assertAttribute('@media-details-overlay', 'data-mode', 'drawer')
        ->assertSee('Mediadetails')
        ->assertSee('Bestandsgegevens')
        ->click('Technische gegevens')
        ->assertSee('Opslagpad')
        ->assertScript(
            "document.querySelector('[data-test=\"media-details-close\"]')?.getBoundingClientRect().bottom <= window.innerHeight",
        )
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertScript("window.location.pathname === '/dashboard/media'")
        ->assertNoAccessibilityIssues()
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('the empty media library offers a mobile upload action without overflow', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    visit('/dashboard/media')
        ->on()->mobile()
        ->resize(390, 844)
        ->assertNoJavaScriptErrors()
        ->assertSee('Geen media gevonden')
        ->assertSee('Eerste bestand uploaden')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoConsoleLogs();
});

test('the media library opens a desktop upload dialog with image preview', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    $page = visit('/dashboard/media')
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors();

    $page->script(
        "() => [...document.querySelectorAll('button')].find((button) => button.textContent?.includes('Bestand uploaden'))?.click()",
    );

    $page
        ->assertSee('Media toevoegen')
        ->assertAttribute('@media-upload-overlay', 'data-mode', 'dialog')
        ->assertSee('Sleep één bestand hierheen')
        ->assertButtonDisabled('Toevoegen')
        ->assertMissing('@media-upload-preview');

    selectMediaUploadImage($page);

    $page
        ->assertPresent('@media-upload-preview')
        ->assertPresent('@media-upload-preview-image')
        ->assertSee('race-control.jpg')
        ->assertSee('JPEG-afbeelding')
        ->assertSee('Beschrijving voor toegankelijkheid')
        ->assertButtonEnabled('Toevoegen')
        ->click('@media-upload-remove')
        ->assertMissing('@media-upload-preview')
        ->assertDontSee('Beschrijving voor toegankelijkheid')
        ->assertButtonDisabled('Toevoegen')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues()
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('the media library opens a mobile upload drawer with a usable preview', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    $page = visit('/dashboard/media')
        ->on()->mobile()
        ->resize(390, 844)
        ->assertNoJavaScriptErrors();

    $page->script(
        "() => [...document.querySelectorAll('button')].find((button) => button.textContent?.includes('Bestand uploaden'))?.click()",
    );

    $page
        ->assertSee('Media toevoegen')
        ->assertAttribute('@media-upload-overlay', 'data-mode', 'drawer');

    selectMediaUploadImage($page);

    $page
        ->assertPresent('@media-upload-preview')
        ->assertSee('race-control.jpg')
        ->assertSee('Ander bestand')
        ->assertSee('Verwijderen')
        ->assertSee('Beschrijving voor toegankelijkheid')
        ->assertButtonEnabled('Toevoegen')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoConsoleLogs();
});

test('event forms search the media library and store the selected cover id', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    Location::factory()->create();
    $mediaAsset = MediaAsset::factory()->named('picker-race-cover.jpg')->create();

    $this->actingAs($admin);

    visit('/dashboard/events/create')
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->click('Kies uit de mediabibliotheek')
        ->assertSee('Afbeelding kiezen')
        ->wait(1)
        ->assertSee('picker-race-cover.jpg')
        ->click('internal:role=button[name="picker-race-cover.jpg"s]')
        ->assertScript(
            "document.querySelector('input[name=\"cover_image_id\"]')?.value === '{$mediaAsset->id}'",
        )
        ->assertSee('picker-race-cover.jpg')
        ->assertNoJavaScriptErrors();
});

function selectMediaUploadImage($page): void
{
    $page->script(<<<'JS'
        async () => {
            const response = await fetch('/images/dds/racing/race-control.jpg');
            const blob = await response.blob();
            const file = new File([blob], 'race-control.jpg', { type: 'image/jpeg' });
            const transfer = new DataTransfer();
            const input = document.querySelector('[data-test="media-upload-input"]');

            transfer.items.add(file);
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        JS);
}
