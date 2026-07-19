<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('the main admin navigation uses the orange brand accent', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    $page = visit('/dashboard/events/create')
        ->on()->desktop()
        ->resize(1440, 900)
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "(() => { const buttons = Array.from(document.querySelectorAll('[data-sidebar=\"sidebar\"] nav [data-sidebar=\"menu-button\"]')); const active = buttons.find((button) => button.getAttribute('aria-current') === 'page'); const inactive = buttons.filter((button) => button !== active); if (buttons.length !== 4 || active?.textContent?.trim() !== 'Events' || inactive.length !== 3) return false; const root = getComputedStyle(document.documentElement); const activeStyle = getComputedStyle(active); return active.dataset.active === 'true' && activeStyle.backgroundColor === root.getPropertyValue('--color-flight-50').trim() && activeStyle.color === root.getPropertyValue('--color-flight-700').trim() && activeStyle.borderLeftColor === root.getPropertyValue('--color-flight-500').trim() && inactive.every((button) => { const icon = button.querySelector('svg'); return icon !== null && getComputedStyle(icon).color === root.getPropertyValue('--color-flight-500').trim(); }); })()",
        );

    $page->script("document.documentElement.classList.add('dark')");

    $page->assertScript(
        "(() => { const active = document.querySelector('[data-sidebar=\"sidebar\"] nav [aria-current=\"page\"]'); if (active === null) return false; const root = getComputedStyle(document.documentElement); const style = getComputedStyle(active); const icon = active.querySelector('svg'); return style.backgroundColor !== 'rgba(0, 0, 0, 0)' && style.color === root.getPropertyValue('--color-flight-300').trim() && style.borderLeftColor === root.getPropertyValue('--color-flight-400').trim() && icon !== null && getComputedStyle(icon).color === root.getPropertyValue('--color-flight-300').trim(); })()",
    );

    $page->script("document.documentElement.classList.remove('dark')");
});

test('event forms use one main surface with flat sections and a context sidebar', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    Location::factory()->create();

    $this->actingAs($admin);

    visit('/dashboard/events/create')
        ->on()->desktop()
        ->resize(1280, 900)
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "document.querySelector('[data-testid=\"admin-form-context\"]')?.textContent?.trim() === 'Nieuw event' && document.querySelector('[data-testid=\"admin-form-save-status\"]')?.dataset.state === 'new'",
        )
        ->assertScript(
            "(() => { const sections = Array.from(document.querySelectorAll('[data-testid=\"admin-form-section\"]')); const actions = document.querySelector('[data-testid=\"admin-form-actions\"]'); const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); const content = document.querySelector('[data-testid=\"admin-form-content\"]'); const aside = document.querySelector('[data-testid=\"admin-form-aside\"]'); const capacityGrid = document.querySelector('[data-testid=\"event-capacity-fields\"]'); const currentOutlineItem = document.querySelector('[aria-current=\"location\"]'); const price = document.querySelector('[data-field=\"price_euros\"]'); const capacity = document.querySelector('[data-field=\"capacity\"]'); if (sections.length !== 5 || actions === null || layout === null || content === null || aside === null || capacityGrid === null || price === null || capacity === null) return false; const columns = getComputedStyle(layout).gridTemplateColumns.split(' ').filter(Boolean); const contentStyle = getComputedStyle(content); const actionsStyle = getComputedStyle(actions); const capacityGridBounds = capacityGrid.getBoundingClientRect(); const priceBounds = price.getBoundingClientRect(); const capacityBounds = capacity.getBoundingClientRect(); const actionsBounds = actions.getBoundingClientRect(); const layoutBounds = layout.getBoundingClientRect(); return contentStyle.borderRadius !== '0px' && contentStyle.backgroundColor !== 'rgba(0, 0, 0, 0)' && contentStyle.boxShadow !== 'none' && contentStyle.overflow === 'clip' && actionsStyle.borderTopLeftRadius === '0px' && Math.abs(actionsBounds.bottom - layoutBounds.top) < 1 && Math.abs(actionsBounds.width - layoutBounds.width) < 1 && sections.every((section) => { const style = getComputedStyle(section); return style.borderRadius === '0px' && style.boxShadow === 'none' && style.borderLeftWidth === '0px' && style.borderRightWidth === '0px'; }) && currentOutlineItem?.getAttribute('href') === '#event-basics' && getComputedStyle(aside).position === 'sticky' && Math.abs(priceBounds.left - capacityGridBounds.left) < 1 && Math.abs(capacityBounds.right - capacityGridBounds.right) < 1 && Math.abs(priceBounds.width - capacityBounds.width) < 1 && Math.abs(priceBounds.height - capacityBounds.height) < 1 && columns.length === 2 && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->assertScript(
            "(() => { const start = document.querySelector('[data-field=\"starts_at\"]'); const end = document.querySelector('[data-field=\"ends_at\"]'); const registrationOpen = document.querySelector('[data-field=\"registration_opens_at\"]'); const registrationDeadline = document.querySelector('[data-field=\"registration_deadline_at\"]'); const picker = document.querySelector('[data-slot=\"date-time-picker\"]'); const date = document.querySelector('#starts_at'); const time = document.querySelector('#starts_at_time'); const clear = document.querySelector('button[aria-label=\"Start wissen\"]'); const hidden = document.querySelector('input[name=\"starts_at\"]'); if (start === null || end === null || registrationOpen === null || registrationDeadline === null || picker === null || date === null || time === null || clear === null || hidden === null) return false; const pickerStyle = getComputedStyle(picker); const pickerHeight = picker.getBoundingClientRect().height; const segmentHeights = [date, time, clear].map((segment) => segment.getBoundingClientRect().height); return start.getBoundingClientRect().top < end.getBoundingClientRect().top && registrationOpen.getBoundingClientRect().top < registrationDeadline.getBoundingClientRect().top && Math.abs(time.getBoundingClientRect().width - 88) < 1 && time.textContent?.trim() === '--:--' && time.getAttribute('aria-label') === 'Tijd kiezen voor Start, nog niet ingesteld' && hidden.value === '' && pickerStyle.borderRadius !== '0px' && pickerStyle.borderWidth === '1px' && ['normal', '0px'].includes(pickerStyle.columnGap) && getComputedStyle(date).borderWidth === '0px' && getComputedStyle(time).borderLeftWidth === '1px' && getComputedStyle(clear).borderLeftWidth === '1px' && segmentHeights.every((height) => Math.abs(height - pickerHeight) < 1); })()",
        )
        ->resize(1800, 1000)
        ->assertScript(
            "(() => { const page = document.querySelector('[data-testid=\"admin-resource-page\"]'); const headerContent = document.querySelector('[data-testid=\"admin-resource-header-content\"]'); const resourceContent = document.querySelector('[data-testid=\"admin-resource-content\"]'); const actions = document.querySelector('[data-testid=\"admin-form-actions\"]'); const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); const formContent = document.querySelector('[data-testid=\"admin-form-content\"]'); const aside = document.querySelector('[data-testid=\"admin-form-aside\"]'); if (page === null || headerContent === null || resourceContent === null || actions === null || layout === null || formContent === null || aside === null) return false; return getComputedStyle(headerContent).maxWidth === '1600px' && getComputedStyle(resourceContent).maxWidth === 'none' && getComputedStyle(layout).maxWidth === '1648px' && Math.abs(actions.getBoundingClientRect().width - page.getBoundingClientRect().width) < 1 && Math.abs(formContent.getBoundingClientRect().left - headerContent.getBoundingClientRect().left) < 1 && Math.abs(aside.getBoundingClientRect().right - headerContent.getBoundingClientRect().right) < 1 && Math.abs(aside.getBoundingClientRect().width - 344) < 1; })()",
        )
        ->assertScript(
            "(() => { const start = document.querySelector('[data-field=\"starts_at\"]'); const end = document.querySelector('[data-field=\"ends_at\"]'); const registrationOpen = document.querySelector('[data-field=\"registration_opens_at\"]'); const registrationDeadline = document.querySelector('[data-field=\"registration_deadline_at\"]'); if (start === null || end === null || registrationOpen === null || registrationDeadline === null) return false; const fields = [start, end, registrationOpen, registrationDeadline]; const widths = fields.map((field) => field.getBoundingClientRect().width); return Math.abs(start.getBoundingClientRect().top - end.getBoundingClientRect().top) < 1 && Math.abs(registrationOpen.getBoundingClientRect().top - registrationDeadline.getBoundingClientRect().top) < 1 && Math.max(...widths) - Math.min(...widths) < 1 && widths.every((width) => width > 400); })()",
        )
        ->assertScript(
            "(() => { const ids = ['price_euros', 'capacity', 'starts_at', 'ends_at']; const fields = ids.map((id) => document.querySelector('[data-field=\"' + id + '\"]')); if (fields.some((field) => field === null)) return false; const widths = fields.map((field) => field.getBoundingClientRect().width); return Math.max(...widths) - Math.min(...widths) < 1; })()",
        )
        ->assertScript(
            "(() => { const slug = document.querySelector('#slug'); const content = document.querySelector('#content'); if (slug === null || content === null) return false; return Math.abs(slug.getBoundingClientRect().width - content.getBoundingClientRect().width) < 1; })()",
        )
        ->resize(640, 900)
        ->assertScript(
            "(() => { const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); const sections = Array.from(document.querySelectorAll('[data-testid=\"admin-form-section\"]')); const outline = document.querySelector('nav[aria-label=\"Formulieroverzicht\"]'); if (layout === null || sections.length !== 5 || outline === null) return false; const columns = getComputedStyle(layout).gridTemplateColumns.split(' ').filter(Boolean); return columns.length === 1 && getComputedStyle(outline.closest('section')).display === 'none' && sections.every((section) => section.getBoundingClientRect().width <= layout.getBoundingClientRect().width) && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->resize(390, 844)
        ->assertScript(
            "(() => { const actions = document.querySelector('[data-testid=\"admin-form-actions\"]'); const cancel = actions?.querySelector('a'); const save = actions?.querySelector('button[type=\"submit\"]'); if (actions === null || cancel === null || save === null) return false; const cancelBounds = cancel.getBoundingClientRect(); const saveBounds = save.getBoundingClientRect(); return Math.abs(cancelBounds.width - saveBounds.width) < 1 && Math.abs(cancelBounds.top - saveBounds.top) < 1 && Math.abs(cancelBounds.height - saveBounds.height) < 1 && document.documentElement.scrollWidth <= window.innerWidth; })()",
        );
});

test('an empty event time uses a neutral placeholder and empty value', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    visit('/dashboard/events/create')
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "document.querySelector('#starts_at_time')?.textContent?.trim() === '--:--' && document.querySelector('#starts_at_time')?.getAttribute('aria-label') === 'Tijd kiezen voor Start, nog niet ingesteld' && document.querySelector('#starts_at_time')?.disabled === true && document.querySelector('input[name=\"starts_at\"]')?.value === ''",
        );
});

test('event create responds to the available form width instead of the viewport', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    Location::factory()->create();

    $this->actingAs($admin);

    visit('/dashboard/events/create')
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->wait(1)
        ->assertScript(
            "(() => { const names = ['starts_at', 'ends_at', 'registration_opens_at', 'registration_deadline_at']; return document.querySelectorAll('input[type=\"datetime-local\"]').length === 0 && names.every((name) => document.querySelector('input[type=\"hidden\"][name=\"' + name + '\"]') !== null && document.querySelector('#' + name) !== null && document.querySelector('#' + name + '_time') !== null); })()",
        )
        ->assertScript(
            "(() => { const names = ['type', 'location_id', 'season_id', 'registration_status']; return document.querySelectorAll('select:not([aria-hidden=\"true\"])').length === 0 && names.every((name) => document.querySelector('input[type=\"hidden\"][name=\"' + name + '\"]') !== null && document.querySelector('#' + name)?.dataset.slot === 'select-trigger'); })()",
        )
        // The five sections appear in a deliberate narrative order: identity, timing,
        // practical limits, registration, then public copy — verify that order holds.
        ->assertScript(
            "(() => { const headings = Array.from(document.querySelectorAll('[data-testid=\"admin-form-layout\"] h2')).map((heading) => heading.textContent?.trim()); return JSON.stringify(headings) === JSON.stringify(['Basisinformatie', 'Wanneer', 'Capaciteit en prijs', 'Inschrijving', 'Publieke pagina']); })()",
        )
        ->fill('#title', 'Indoor Training Éindhoven')
        ->assertScript(
            "document.querySelector('input[name=\"slug\"]')?.value === 'indoor-training-eindhoven'",
        )
        ->assertVisible('#slug')
        ->assertSee('Publieke URL: /events/indoor-training-eindhoven')
        ->fill('#slug', 'eigen-event-url')
        ->fill('#title', 'Nieuwe eventtitel')
        ->assertScript(
            "document.querySelector('input[name=\"slug\"]')?.value === 'eigen-event-url'",
        )
        ->assertSee('Deelnameprijs (optioneel)')
        ->assertScript(
            "document.querySelector('[data-testid=\"admin-form-layout\"]')?.getBoundingClientRect().width > 1024",
        )
        ->assertScript(
            "(() => { const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); const page = document.querySelector('[data-testid=\"admin-resource-page\"]'); return layout !== null && page !== null && layout.getBoundingClientRect().width <= page.getBoundingClientRect().width; })()",
        )
        ->assertScript(
            "(() => { const content = document.querySelector('[data-testid=\"admin-form-content\"]'); const heading = document.querySelector('h1'); return content !== null && heading !== null && Math.abs(content.getBoundingClientRect().left - heading.getBoundingClientRect().left) < 1; })()",
        )
        ->assertScript(
            'document.documentElement.scrollWidth <= window.innerWidth',
        )
        // Price and capacity live together in their own "Capaciteit en prijs" section and
        // must sit side by side at desktop widths.
        ->assertScript(
            "(() => { const price = document.querySelector('#price_euros'); const capacity = document.querySelector('#capacity'); if (price === null || capacity === null) return false; return Math.abs(price.getBoundingClientRect().top - capacity.getBoundingClientRect().top) < 1; })()",
        )
        ->assertScript(
            "(() => { const fields = document.querySelectorAll('[data-testid=\"admin-form-layout\"] input:not([type=\"hidden\"]), [data-testid=\"admin-form-layout\"] select:not([aria-hidden=\"true\"]), [data-testid=\"admin-form-layout\"] textarea'); return fields.length > 0 && Array.from(fields).every((field) => field.getBoundingClientRect().width >= 180) && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->resize(1280, 900)
        ->assertScript(
            'document.documentElement.scrollWidth <= window.innerWidth',
        )
        ->assertScript(
            "(() => { const price = document.querySelector('#price_euros'); const capacity = document.querySelector('#capacity'); if (price === null || capacity === null) return false; return Math.abs(price.getBoundingClientRect().top - capacity.getBoundingClientRect().top) < 1; })()",
        )
        ->resize(1024, 900)
        ->assertScript(
            'document.documentElement.scrollWidth <= window.innerWidth',
        )
        ->assertScript(
            "(() => { const title = document.querySelector('#title'); const type = document.querySelector('#type'); if (title === null || type === null) return false; return title.getBoundingClientRect().top < type.getBoundingClientRect().top; })()",
        )
        ->resize(1440, 1000)
        ->click('#starts_at')
        ->click('button[aria-label^="Vandaag,"]')
        ->assertAriaAttribute(
            '#starts_at_time',
            'label',
            'Tijd kiezen voor Start, nog niet ingesteld',
        )
        ->click('#starts_at_time')
        ->assertSee('Tijd kiezen')
        ->click('[aria-labelledby="starts_at_time_hour_label"]')
        ->click('internal:role=option[name="09"s]')
        ->click('[aria-labelledby="starts_at_time_minute_label"]')
        ->click('internal:role=option[name="30"s]')
        ->click('Gereed')
        ->assertScript(
            'document.querySelector(\'input[name="starts_at"]\')?.value.endsWith(\'T09:30\')',
        )
        ->assertNoJavaScriptErrors();
});

test('event registration section reacts visibly to the chosen registration status', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    Location::factory()->create();

    $this->actingAs($admin);

    visit('/dashboard/events/create')
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->wait(1)
        // Closed by default: the link is presented as optional, no emphasis.
        ->assertSee('optioneel')
        ->assertDontSee('Verplicht bij een open inschrijving of wachtlijst.')
        ->click('#registration_status')
        ->click('internal:role=option[name="Open"s]')
        ->assertScript(
            "document.querySelector('input[name=\"registration_status\"]')?.value === 'open'",
        )
        ->assertScript(
            "document.querySelector('input[name=\"registration_url\"]')?.required === true",
        )
        ->assertSee('Verplicht')
        ->assertSee('Verplicht bij een open inschrijving of wachtlijst.')
        ->assertNoJavaScriptErrors();
});

test('event edit only places the publication panel beside a sufficiently wide form', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $event = Event::factory()->create([
        'title' => 'Najaarstraining',
    ]);

    $this->actingAs($admin);

    visit("/dashboard/events/{$event->id}/edit")
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->assertSee('Wijzigingen opslaan')
        ->assertSee('Event verwijderen')
        ->assertScript(
            "document.querySelector('[data-testid=\"admin-form-context\"]')?.textContent?.trim() === 'Najaarstraining' && document.querySelector('[data-testid=\"admin-form-save-status\"]')?.dataset.state === 'unchanged'",
        )
        ->assertScript(
            "(() => { const dangerZone = document.querySelector('#event-danger-zone'); const dangerContent = dangerZone?.querySelector('[data-danger-content]'); const deleteButton = dangerZone?.querySelector('button'); if (dangerZone === null || dangerContent === null || deleteButton === null) return false; const zoneBounds = dangerZone.getBoundingClientRect(); const buttonBounds = deleteButton.getBoundingClientRect(); return zoneBounds.height <= 130 && getComputedStyle(dangerZone).backgroundColor !== 'rgba(0, 0, 0, 0)' && getComputedStyle(dangerContent).borderTopWidth === '0px' && getComputedStyle(deleteButton).backgroundColor !== 'rgba(0, 0, 0, 0)' && Math.abs((buttonBounds.top + buttonBounds.height / 2) - (zoneBounds.top + zoneBounds.height / 2)) <= 1; })()",
        )
        ->assertScript(
            "(() => { const names = ['starts_at', 'ends_at', 'registration_opens_at', 'registration_deadline_at']; return document.querySelectorAll('input[type=\"datetime-local\"]').length === 0 && names.every((name) => document.querySelector('input[type=\"hidden\"][name=\"' + name + '\"]') !== null && document.querySelector('#' + name) !== null && document.querySelector('#' + name + '_time') !== null); })()",
        )
        ->assertScript(
            "(() => { const names = ['type', 'location_id', 'season_id', 'registration_status']; return document.querySelectorAll('select:not([aria-hidden=\"true\"])').length === 0 && names.every((name) => document.querySelector('input[type=\"hidden\"][name=\"' + name + '\"]') !== null && document.querySelector('#' + name)?.dataset.slot === 'select-trigger'); })()",
        )
        ->assertScript(
            "(() => { const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); if (layout === null) return false; const columns = getComputedStyle(layout).gridTemplateColumns.split(' ').filter(Boolean).map(Number.parseFloat); return columns.length === 2 && columns[0] >= 720 && columns[1] >= 280 && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->resize(1024, 900)
        ->assertScript(
            "(() => { const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); if (layout === null || layout.children.length < 2) return false; const columns = getComputedStyle(layout).gridTemplateColumns.split(' ').filter(Boolean); return columns.length === 1 && layout.children[0].getBoundingClientRect().top < layout.children[1].getBoundingClientRect().top && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->assertNoJavaScriptErrors();
});

test('the public event page opens in a new tab', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $event = Event::factory()->published()->create();

    $this->actingAs($admin);

    visit("/dashboard/events/{$event->id}/edit")
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "(() => { const publicLink = document.querySelector('a[href^=\"/events/\"][target=\"_blank\"]'); const unpublish = document.querySelector('[data-sidebar-action=\"unpublish\"]'); const cancel = document.querySelector('[data-sidebar-action=\"cancel\"]'); if (publicLink === null || unpublish === null || cancel === null) return false; const rel = new Set((publicLink.getAttribute('rel') ?? '').split(/\\s+/)); const publicHeight = publicLink.getBoundingClientRect().height; const secondaryActions = [unpublish, cancel]; return rel.has('noopener') && rel.has('noreferrer') && publicHeight >= 43 && publicHeight <= 45 && publicLink.getBoundingClientRect().width > unpublish.getBoundingClientRect().width * 1.9 && secondaryActions.every((action) => { const height = action.getBoundingClientRect().height; return height >= 39 && height <= 41 && getComputedStyle(action).borderRadius !== '0px' && action.querySelector('svg') !== null; }) && Math.abs(unpublish.getBoundingClientRect().top - cancel.getBoundingClientRect().top) < 1 && Math.abs(unpublish.getBoundingClientRect().width - cancel.getBoundingClientRect().width) < 1; })()",
        );
});

test('long admin forms use the document as their only vertical scroll container', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $event = Event::factory()->create([
        'content' => str_repeat('Uitgebreide eventinformatie. ', 200),
    ]);

    $this->actingAs($admin);

    $page = visit("/dashboard/events/{$event->id}/edit")
        ->on()->desktop()
        ->resize(1440, 900)
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "(() => { const inset = document.querySelector('[data-slot=\"sidebar-inset\"]'); const resourcePage = document.querySelector('[data-testid=\"admin-resource-page\"]'); const header = inset?.querySelector(':scope > header'); if (inset === null || resourcePage === null || header === null) return false; const insetStyle = getComputedStyle(inset); const resourceStyle = getComputedStyle(resourcePage); const headerStyle = getComputedStyle(header); return insetStyle.overflowY === 'clip' && insetStyle.borderTopLeftRadius !== '0px' && resourceStyle.overflowY === 'visible' && document.scrollingElement === document.documentElement && document.documentElement.scrollHeight > document.documentElement.clientHeight && headerStyle.position === 'sticky' && headerStyle.backgroundColor !== 'rgba(0, 0, 0, 0)'; })()",
        );

    $page->script('window.scrollTo(0, 600)');

    $page->assertScript(
        "(() => { const inset = document.querySelector('[data-slot=\"sidebar-inset\"]'); const resourcePage = document.querySelector('[data-testid=\"admin-resource-page\"]'); const header = document.querySelector('[data-slot=\"sidebar-inset\"] > header'); const actions = document.querySelector('[data-testid=\"admin-form-actions\"]'); const aside = document.querySelector('[data-testid=\"admin-form-aside\"]'); if (inset === null || resourcePage === null || header === null || actions === null || aside === null) return false; const asideStyle = getComputedStyle(aside); const headerBounds = header.getBoundingClientRect(); const topEdgeSamples = [headerBounds.left + 2, headerBounds.left + (headerBounds.width / 2), headerBounds.right - 2]; const headerCoversTopEdge = topEdgeSamples.every((x) => header.contains(document.elementFromPoint(x, 2))); return headerBounds.top === 0 && headerCoversTopEdge && Math.abs(actions.getBoundingClientRect().top - headerBounds.bottom) <= 1 && Math.abs(aside.getBoundingClientRect().top - 128) <= 1 && asideStyle.position === 'sticky' && asideStyle.top === '128px' && inset.scrollTop === 0 && resourcePage.scrollTop === 0 && window.scrollY >= 500; })()",
    );
});
