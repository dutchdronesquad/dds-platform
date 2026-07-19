<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\Season;
use App\Models\SeasonTicket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('season index does not expose the public path below the season name', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $season = Season::factory()->create([
        'name' => 'Wintercompetitie',
        'slug' => 'verborgen-seizoenspad',
    ]);

    $this->actingAs($admin);

    visit('/dashboard/seasons')
        ->on()->desktop()
        ->assertNoJavaScriptErrors()
        ->assertSee($season->name)
        ->assertDontSee('/seasons/verborgen-seizoenspad');
});

test('season forms use one main surface without nested section cards', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    visit('/dashboard/seasons/create')
        ->on()->desktop()
        ->resize(1280, 900)
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "document.querySelector('[data-testid=\"admin-form-context\"]')?.textContent?.trim() === 'Nieuw seizoen' && document.querySelector('[data-testid=\"admin-form-save-status\"]')?.dataset.state === 'new'",
        )
        ->assertScript(
            "(() => { const sections = Array.from(document.querySelectorAll('[data-testid=\"admin-form-section\"]')); const disclosure = document.querySelector('[data-testid=\"season-ticket-disclosure\"]'); const toggle = document.querySelector('[data-testid=\"season-ticket-toggle\"]'); const ticketFields = document.querySelector('#season-ticket-fields'); const actions = document.querySelector('[data-testid=\"admin-form-actions\"]'); const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); const content = document.querySelector('[data-testid=\"admin-form-content\"]'); const aside = document.querySelector('[data-testid=\"admin-form-aside\"]'); const currentOutlineItem = document.querySelector('[aria-current=\"location\"]'); if (sections.length !== 2 || disclosure === null || toggle === null || ticketFields === null || actions === null || layout === null || content === null || aside === null) return false; const disclosureStyle = getComputedStyle(disclosure); const toggleStyle = getComputedStyle(toggle); const contentStyle = getComputedStyle(content); const actionsStyle = getComputedStyle(actions); const columns = getComputedStyle(layout).gridTemplateColumns.split(' ').filter(Boolean); const actionsBounds = actions.getBoundingClientRect(); const layoutBounds = layout.getBoundingClientRect(); return toggle.matches('button[role=\"switch\"][aria-checked=\"false\"]') && toggle.getAttribute('aria-controls') === ticketFields.id && ticketFields.hidden && ticketFields.disabled && disclosure.dataset.enabled === 'false' && disclosureStyle.borderRadius === '0px' && disclosureStyle.boxShadow === 'none' && disclosureStyle.backgroundColor === 'rgba(0, 0, 0, 0)' && contentStyle.borderRadius !== '0px' && contentStyle.backgroundColor !== 'rgba(0, 0, 0, 0)' && contentStyle.boxShadow !== 'none' && contentStyle.overflow === 'clip' && actionsStyle.borderTopLeftRadius === '0px' && Math.abs(actionsBounds.bottom - layoutBounds.top) < 1 && Math.abs(actionsBounds.width - layoutBounds.width) < 1 && sections.every((section) => getComputedStyle(section).borderRadius === '0px' && getComputedStyle(section).boxShadow === 'none') && Math.abs(sections[1].getBoundingClientRect().top - sections[0].getBoundingClientRect().bottom) < 1 && toggleStyle.borderRadius === '0px' && toggleStyle.borderLeftWidth === '3px' && toggleStyle.borderLeftColor === 'rgba(0, 0, 0, 0)' && toggleStyle.borderRightWidth === '0px' && currentOutlineItem?.getAttribute('href') === '#season-basics' && getComputedStyle(aside).position === 'sticky' && columns.length === 2 && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->resize(1800, 1000)
        ->assertScript(
            "(() => { const page = document.querySelector('[data-testid=\"admin-resource-page\"]'); const headerContent = document.querySelector('[data-testid=\"admin-resource-header-content\"]'); const resourceContent = document.querySelector('[data-testid=\"admin-resource-content\"]'); const actions = document.querySelector('[data-testid=\"admin-form-actions\"]'); const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); const formContent = document.querySelector('[data-testid=\"admin-form-content\"]'); const aside = document.querySelector('[data-testid=\"admin-form-aside\"]'); if (page === null || headerContent === null || resourceContent === null || actions === null || layout === null || formContent === null || aside === null) return false; return getComputedStyle(headerContent).maxWidth === '1600px' && getComputedStyle(resourceContent).maxWidth === 'none' && getComputedStyle(layout).maxWidth === '1648px' && Math.abs(actions.getBoundingClientRect().width - page.getBoundingClientRect().width) < 1 && Math.abs(formContent.getBoundingClientRect().left - headerContent.getBoundingClientRect().left) < 1 && Math.abs(aside.getBoundingClientRect().right - headerContent.getBoundingClientRect().right) < 1 && Math.abs(aside.getBoundingClientRect().width - 344) < 1; })()",
        )
        ->resize(640, 900)
        ->assertScript(
            "(() => { const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); const toggle = document.querySelector('[data-testid=\"season-ticket-toggle\"]'); const outline = document.querySelector('nav[aria-label=\"Formulieroverzicht\"]'); if (layout === null || toggle === null || outline === null) return false; const columns = getComputedStyle(layout).gridTemplateColumns.split(' ').filter(Boolean); return columns.length === 1 && getComputedStyle(outline.closest('section')).display === 'none' && toggle.getBoundingClientRect().width <= layout.getBoundingClientRect().width && document.documentElement.scrollWidth <= window.innerWidth; })()",
        );
});

test('dirty season forms register a compatible browser unload warning', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    visit('/dashboard/seasons/create')
        ->on()->desktop()
        ->fill('#name', 'Wintercompetitie')
        ->assertScript(
            "document.querySelector('[data-testid=\"admin-form-save-status\"]')?.dataset.state === 'dirty'",
        )
        ->assertScript(
            "(() => { const event = new Event('beforeunload', { cancelable: true }); let assignedReturnValue; Object.defineProperty(event, 'returnValue', { configurable: true, get: () => assignedReturnValue, set: (value) => { assignedReturnValue = value; } }); window.dispatchEvent(event); return event.defaultPrevented && assignedReturnValue === ''; })()",
        )
        ->assertNoJavaScriptErrors();
});

test('season ticket fields use a balanced layout and combined date time controls', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $season = Season::factory()->create();

    SeasonTicket::factory()
        ->for($season)
        ->comingSoon()
        ->create([
            'sales_closes_at' => '2027-02-10 20:15:00',
            'sales_opens_at' => '2027-02-03 18:45:00',
        ]);

    $this->actingAs($admin);

    visit("/dashboard/seasons/{$season->slug}/edit")
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->assertMissing('input[type="time"]')
        ->assertScript(
            // The two form sections read top-to-bottom (name/URL, then the ticket
            // section) rather than sitting side by side, so the "Seizoensticket"
            // section starts below the "Naam en URL" section and both span the
            // full width of the form column.
            "(() => { const sections = document.querySelectorAll('[data-testid=\"season-form-sections\"] > section'); if (sections.length !== 2) return false; const [first, second] = sections; const firstBounds = first.getBoundingClientRect(); const secondBounds = second.getBoundingClientRect(); return secondBounds.top >= firstBounds.bottom && Math.abs(firstBounds.width - secondBounds.width) < 1 && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->assertScript(
            // Within the first section, name and URL slug sit side by side once
            // there is enough width, i.e. they share a row.
            "(() => { const name = document.querySelector('#name'); const slug = document.querySelector('#slug'); if (name === null || slug === null) return false; return Math.abs(name.getBoundingClientRect().top - slug.getBoundingClientRect().top) < 1; })()",
        )
        ->resize(640, 900)
        ->assertScript(
            // On a narrow viewport the name and URL slug fields stack instead of
            // sitting side by side, and nothing overflows horizontally.
            "(() => { const name = document.querySelector('#name'); const slug = document.querySelector('#slug'); if (name === null || slug === null) return false; return slug.getBoundingClientRect().top >= name.getBoundingClientRect().bottom && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->resize(1440, 1000)
        ->assertAriaAttribute('#ticket_offered', 'checked', 'true')
        ->assertScript(
            "(() => { const disclosure = document.querySelector('[data-testid=\"season-ticket-disclosure\"]'); const toggle = document.querySelector('[data-testid=\"season-ticket-toggle\"]'); const fields = document.querySelector('#season-ticket-fields'); if (disclosure === null || toggle === null || fields === null) return false; const disclosureStyle = getComputedStyle(disclosure); const toggleStyle = getComputedStyle(toggle); const fieldStyle = getComputedStyle(fields); return disclosure.dataset.enabled === 'true' && disclosureStyle.backgroundColor === 'rgba(0, 0, 0, 0)' && toggleStyle.borderLeftWidth === '3px' && toggleStyle.borderLeftColor !== 'rgba(0, 0, 0, 0)' && toggleStyle.backgroundColor !== 'rgba(0, 0, 0, 0)' && fieldStyle.backgroundColor !== toggleStyle.backgroundColor; })()",
        )
        ->assertSee('Seizoenprijs (optioneel)')
        ->assertSee('Ticketlink (optioneel)')
        ->assertSee('Verkoop sluit (optioneel)')
        ->assertScript(
            "document.querySelector('#season-ticket-fields details')?.open === true",
        )
        ->assertScript(
            "(() => { const trigger = document.querySelector('#ticket_sales_state'); const visibleLabel = document.querySelector('#ticket_sales_state-label'); const hiddenInput = document.querySelector('input[type=\"hidden\"][name=\"ticket_sales_state\"]'); const nativeSelect = document.querySelector('select[name=\"ticket_sales_state\"]'); return trigger?.getAttribute('data-slot') === 'select-trigger' && trigger.getAttribute('role') === 'combobox' && trigger.getAttribute('aria-labelledby') === visibleLabel?.id && visibleLabel?.textContent?.trim() === 'Verkoopstatus' && trigger.textContent?.includes('Binnenkort') && hiddenInput?.value === 'coming_soon' && nativeSelect === null; })()",
        )
        ->assertScript(
            "(() => { const primary = document.querySelector('[data-testid=\"season-ticket-primary-fields\"]'); const status = document.querySelector('[data-field=\"ticket_sales_state\"]'); const price = document.querySelector('[data-field=\"ticket_price_euros\"]'); const link = document.querySelector('[data-field=\"ticket_registration_url\"]'); const capacity = document.querySelector('[data-field=\"ticket_capacity\"]'); if (primary === null || status === null || price === null || link === null || capacity === null) return false; const primaryBounds = primary.getBoundingClientRect(); const statusBounds = status.getBoundingClientRect(); const priceBounds = price.getBoundingClientRect(); const linkBounds = link.getBoundingClientRect(); const capacityBounds = capacity.getBoundingClientRect(); return Math.abs(statusBounds.width - priceBounds.width) < 1 && Math.abs(linkBounds.left - primaryBounds.left) < 1 && Math.abs(linkBounds.right - primaryBounds.right) < 1 && capacityBounds.width >= 240 && capacityBounds.width <= 288 && document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1; })()",
        )
        ->resize(1024, 900)
        ->assertScript(
            "(() => { const primary = document.querySelector('[data-testid=\"season-ticket-primary-fields\"]'); const link = document.querySelector('[data-field=\"ticket_registration_url\"]'); const capacity = document.querySelector('[data-field=\"ticket_capacity\"]'); if (primary === null || link === null || capacity === null) return false; const primaryBounds = primary.getBoundingClientRect(); const linkBounds = link.getBoundingClientRect(); return Math.abs(linkBounds.left - primaryBounds.left) < 1 && Math.abs(linkBounds.right - primaryBounds.right) < 1 && capacity.getBoundingClientRect().width >= 240 && document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1; })()",
        )
        ->resize(1440, 1000)
        ->assertAriaAttribute(
            '#ticket_sales_opens_at_time',
            'label',
            'Tijd kiezen voor Verkoop opent, 18:45',
        )
        ->assertVisible('button[aria-label="Verkoop opent wissen"]')
        ->assertScript(
            "document.querySelector('input[name=\"ticket_sales_opens_at\"]')?.value === '2027-02-03T18:45'",
        )
        ->assertScript(
            "(() => { const trigger = document.querySelector('#ticket_sales_opens_at_time'); const date = document.querySelector('#ticket_sales_opens_at'); return trigger !== null && date !== null && trigger.getBoundingClientRect().height === date.getBoundingClientRect().height; })()",
        )
        ->assertNoJavaScriptErrors();
});

test('season ticket date fields reflow before they become cramped', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $season = Season::factory()->withTicketOffer()->create();

    $this->actingAs($admin);

    visit("/dashboard/seasons/{$season->slug}/edit")
        ->on()->desktop()
        ->resize(1024, 900)
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "(() => { const opens = document.querySelector('[data-field=\"ticket_sales_opens_at\"]'); const closes = document.querySelector('[data-field=\"ticket_sales_closes_at\"]'); const time = document.querySelector('#ticket_sales_opens_at_time'); if (opens === null || closes === null || time === null) return false; return opens.getBoundingClientRect().top < closes.getBoundingClientRect().top && Math.abs(time.getBoundingClientRect().width - 88) < 1 && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->resize(1440, 1000)
        ->assertScript(
            "(() => { const opens = document.querySelector('[data-field=\"ticket_sales_opens_at\"]'); const closes = document.querySelector('[data-field=\"ticket_sales_closes_at\"]'); if (opens === null || closes === null) return false; return Math.abs(opens.getBoundingClientRect().top - closes.getBoundingClientRect().top) < 1 && document.documentElement.scrollWidth <= window.innerWidth; })()",
        );
});

test('the public season page opens in a new tab', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $season = Season::factory()->create([
        'name' => 'Winterseizoen 2027',
    ]);

    Event::factory()->create(['season_id' => $season->id]);

    $this->actingAs($admin);

    visit("/dashboard/seasons/{$season->slug}/edit")
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "document.querySelector('[data-testid=\"admin-form-context\"]')?.textContent?.trim() === 'Winterseizoen 2027' && document.querySelector('[data-testid=\"admin-form-save-status\"]')?.dataset.state === 'unchanged'",
        )
        ->assertScript(
            "(() => { const publicLink = document.querySelector('a[href^=\"/seasons/\"][target=\"_blank\"]'); if (publicLink === null) return false; const rel = new Set((publicLink.getAttribute('rel') ?? '').split(/\\s+/)); const height = publicLink.getBoundingClientRect().height; return rel.has('noopener') && rel.has('noreferrer') && publicLink.dataset.sidebarAction === 'public' && height >= 43 && height <= 45 && getComputedStyle(publicLink).borderRadius !== '0px' && publicLink.querySelectorAll('svg').length === 2; })()",
        );
});

test('the edit-season layout stacks sections above a sticky usage aside without overflow', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    $season = Season::factory()->create();

    $this->actingAs($admin);

    visit("/dashboard/seasons/{$season->slug}/edit")
        ->on()->desktop()
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "(() => { const dangerZone = document.querySelector('#season-danger-zone'); const dangerContent = dangerZone?.querySelector('[data-danger-content]'); const deleteButton = dangerZone?.querySelector('button'); if (dangerZone === null || dangerContent === null || deleteButton === null) return false; const zoneBounds = dangerZone.getBoundingClientRect(); const buttonBounds = deleteButton.getBoundingClientRect(); return zoneBounds.height <= 130 && getComputedStyle(dangerZone).backgroundColor !== 'rgba(0, 0, 0, 0)' && getComputedStyle(dangerContent).borderTopWidth === '0px' && getComputedStyle(deleteButton).backgroundColor !== 'rgba(0, 0, 0, 0)' && Math.abs((buttonBounds.top + buttonBounds.height / 2) - (zoneBounds.top + zoneBounds.height / 2)) <= 1; })()",
        )
        ->assertScript(
            // The usage aside sits to the right of the form sections at desktop
            // width and is sticky, rather than being squeezed under a hardcoded
            // max width.
            "(() => { const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); const sections = document.querySelector('[data-testid=\"season-form-sections\"]'); if (layout === null || sections === null) return false; const columns = getComputedStyle(layout).gridTemplateColumns.split(' ').filter(Boolean); return columns.length === 2 && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->resize(1024, 900)
        ->assertScript(
            "(() => { const layout = document.querySelector('[data-testid=\"admin-form-layout\"]'); if (layout === null) return false; const columns = getComputedStyle(layout).gridTemplateColumns.split(' ').filter(Boolean); return columns.length === 1 && document.documentElement.scrollWidth <= window.innerWidth; })()",
        )
        ->assertNoJavaScriptErrors();
});
