<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(storage_path('framework/testing/vite.hot'));
});

test('authentication screens use accessible DDS branding without mobile overflow', function () {
    $brandIsAccessible = <<<'JS'
        (() => {
            const link = document.querySelector('a[aria-label="Dutch Drone Squad home"]');
            const brand = link?.querySelector('[data-brand-variant="full"]');
            const logo = brand?.querySelector('[data-testid="dds-brand-logo"]');
            const bounds = logo?.getBoundingClientRect();
            const layout = document.querySelector('[data-testid="auth-layout"]');

            return link !== null
                && layout?.getAttribute('data-auth-layout') === 'split'
                && logo?.getAttribute('src') === '/brand/dds-logo.svg'
                && logo?.getAttribute('alt') === ''
                && logo?.getAttribute('aria-hidden') === 'true'
                && logo?.complete === true
                && (logo?.naturalWidth ?? 0) > 0
                && (bounds?.width ?? 0) > 0
                && document.documentElement.scrollWidth <= window.innerWidth;
        })()
        JS;

    $paths = [
        route('login'),
        route('password.request'),
        route('password.reset', [
            'token' => 'branding-test-token',
            'email' => 'pilot@example.com',
        ]),
    ];

    foreach ($paths as $path) {
        visit($path)
            ->on()->desktop()
            ->assertNoJavaScriptErrors()
            ->assertScript($brandIsAccessible)
            ->assertVisible('[data-testid="auth-visual"]')
            ->assertVisible('[data-testid="auth-panel"]')
            ->assertScript(<<<'JS'
                (() => {
                    const visualBounds = document.querySelector('[data-testid="auth-visual"]')?.getBoundingClientRect();
                    const sideBounds = document.querySelector('[data-testid="auth-form-side"]')?.getBoundingClientRect();

                    return (visualBounds?.width ?? 0) >= (sideBounds?.width ?? 0) * 1.2
                        && Math.abs((sideBounds?.right ?? 0) - window.innerWidth) < 1;
                })()
                JS);

        visit($path)
            ->on()->iPhone14Pro()
            ->inDarkMode()
            ->assertNoJavaScriptErrors()
            ->assertScript($brandIsAccessible)
            ->assertVisible('[data-testid="auth-panel"]')
            ->assertScript(
                "getComputedStyle(document.querySelector('[data-testid=\"auth-visual\"]')).display === 'none'",
            );
    }

    visit(route('login'))
        ->on()->desktop()
        ->assertNoAccessibilityIssues()
        ->assertScript(<<<'JS'
                (() => {
                    const photoRotation = document.querySelector('[data-testid="auth-photo-rotation"]');
                    const photos = [...document.querySelectorAll('[data-auth-photo]')];
                    const passkeyButton = document.querySelector('button[type="button"][data-slot="button"]');
                    const submitButton = document.querySelector('button[type="submit"][data-slot="button"]');
                    const passwordToggle = document.querySelector('button[aria-label="Show password"]');
                    const heading = document.querySelector('[data-testid="auth-heading"]');

                    return photos.some((photo) => photo.getAttribute('src') === photoRotation?.getAttribute('data-active-photo'))
                        && photoRotation?.getAttribute('data-rotation-interval') === '250'
                        && photos.length === 2
                        && photos.some((photo) => photo.getAttribute('src') === '/images/dds/racing/pilot-preparing-drone.jpg')
                        && photos.some((photo) => photo.getAttribute('src') === '/images/dds/racing/homepage-hero.jpg')
                        && passkeyButton !== null
                        && submitButton !== null
                        && getComputedStyle(passkeyButton).backgroundColor !== getComputedStyle(submitButton).backgroundColor
                    && passwordToggle?.getAttribute('tabindex') !== '-1'
                    && getComputedStyle(heading).textAlign === 'center';
            })()
            JS);
});

test('the desktop auth visual rotates between DDS photos', function () {
    visit(route('login'))
        ->on()->desktop()
        ->assertNoJavaScriptErrors()
        ->wait(1)
        ->assertScript(<<<'JS'
            (() => {
                const photoRotation = document.querySelector('[data-testid="auth-photo-rotation"]');
                const activePhoto = document.querySelector('[data-auth-photo][data-active="true"]');

                return Number(photoRotation?.getAttribute('data-rotation-count')) > 0
                    && activePhoto?.getAttribute('src') === photoRotation?.getAttribute('data-active-photo');
            })()
            JS);
});

test('authentication errors use assertive alert semantics', function () {
    $user = User::factory()->create();

    visit(route('login'))
        ->on()->desktop()
        ->fill('email', $user->email)
        ->fill('password', 'wrong-password')
        ->click('Log in')
        ->assertVisible('[data-slot="auth-error"]')
        ->assertScript(<<<'JS'
            (() => {
                const error = document.querySelector('[data-slot="auth-error"]');

                return error?.getAttribute('role') === 'alert'
                    && !error.hasAttribute('aria-live');
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

test('the verification screen shares the same DDS branding', function () {
    $brandIsShared = <<<'JS'
        (() => {
            const link = document.querySelector('a[aria-label="Dutch Drone Squad home"]');
            const linkedLogos = link?.querySelectorAll('[data-testid="dds-brand-logo"]');
            const visual = document.querySelector('[data-testid="auth-visual"]');
            const decorativeLogo = visual?.querySelector('[data-testid="dds-brand-logo"]');

            return link !== null
                && linkedLogos?.length === 1
                && linkedLogos[0].getAttribute('src') === '/brand/dds-logo.svg'
                && visual?.getAttribute('aria-hidden') === 'true'
                && decorativeLogo !== null;
        })()
        JS;

    $unverifiedUser = User::factory()->unverified()->create();

    $this->actingAs($unverifiedUser);

    visit(route('verification.notice'))
        ->assertNoJavaScriptErrors()
        ->assertScript($brandIsShared);

    visit(route('verification.notice'))
        ->on()->iPhone14Pro()
        ->inDarkMode()
        ->assertNoJavaScriptErrors()
        ->assertScript($brandIsShared)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth');
});

test('the two factor screen shares the same DDS branding', function () {
    $brandIsShared = <<<'JS'
        (() => {
            const link = document.querySelector('a[aria-label="Dutch Drone Squad home"]');
            const linkedLogos = link?.querySelectorAll('[data-testid="dds-brand-logo"]');
            const visual = document.querySelector('[data-testid="auth-visual"]');
            const decorativeLogo = visual?.querySelector('[data-testid="dds-brand-logo"]');

            return link !== null
                && linkedLogos?.length === 1
                && linkedLogos[0].getAttribute('src') === '/brand/dds-logo.svg'
                && visual?.getAttribute('aria-hidden') === 'true'
                && decorativeLogo !== null;
        })()
        JS;

    $twoFactorUser = User::factory()->withTwoFactor()->create();

    $page = visit(route('login'))
        ->on()->iPhone14Pro()
        ->inDarkMode()
        ->fill('email', $twoFactorUser->email)
        ->fill('password', 'password')
        ->click('Log in')
        ->assertPathIs('/two-factor-challenge')
        ->assertNoJavaScriptErrors()
        ->assertScript($brandIsShared)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertScript(
            "document.querySelector('h1')?.textContent?.trim()",
            'Authentication code',
        );

    $page->click('[data-test="toggle-recovery-mode"]')
        ->assertScript(
            "document.querySelector('h1')?.textContent?.trim()",
            'Recovery code',
        )
        ->assertVisible('[name="recovery_code"]')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth');

    $page->click('[data-test="toggle-recovery-mode"]')
        ->assertScript(
            "document.querySelector('h1')?.textContent?.trim()",
            'Authentication code',
        );
});

test('the password confirmation screen uses the shared accessible auth treatment', function () {
    $this->actingAs(User::factory()->create());

    visit(route('password.confirm'))
        ->on()->desktop()
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues()
        ->assertVisible('[data-testid="auth-panel"]')
        ->assertVisible('[name="password"]')
        ->assertVisible('button[aria-label="Show password"]')
        ->assertScript(
            "document.querySelector('button[aria-label=\"Show password\"]')?.getAttribute('tabindex') !== '-1'",
        );
});

test('dashboard branding switches between full and compact variants', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $this->actingAs($user);

    $page = visit(route('dashboard'))
        ->on()->desktop()
        ->assertNoJavaScriptErrors()
        ->assertVisible(
            'a[data-testid="sidebar-brand"]',
        )
        ->assertScript(
            "document.querySelector('a[data-testid=\"sidebar-brand\"] [data-brand-variant=\"logo\"] [data-testid=\"dds-brand-logo\"]')?.getAttribute('src')",
            '/brand/dds-logo.svg',
        )
        ->assertScript(
            "(() => { const brand = document.querySelector('a[data-testid=\"sidebar-brand\"]'); const logo = brand?.querySelector('[data-testid=\"dds-brand-logo\"]'); const brandBounds = brand?.getBoundingClientRect(); const logoBounds = logo?.getBoundingClientRect(); return brand?.textContent?.trim() === '' && !brand?.classList.contains('bg-night-950') && getComputedStyle(brand).backgroundImage.includes('linear-gradient') && (brandBounds?.width ?? 0) > 180 && (brandBounds?.height ?? 0) >= 56 && (logoBounds?.width ?? 0) >= 112; })()",
        )
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth');

    $page->click('button[data-sidebar="trigger"]')
        ->assertScript(
            "document.querySelector('a[aria-label=\"Dutch Drone Squad dashboard\"] [data-brand-variant=\"compact\"] [data-testid=\"dds-brand-logo\"]') !== null",
        )
        ->assertScript(
            "(() => { const brandBounds = document.querySelector('a[data-testid=\"sidebar-brand\"]')?.getBoundingClientRect(); return (brandBounds?.width ?? 100) <= 36 && (brandBounds?.height ?? 100) <= 36; })()",
        )
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth');

    visit(route('dashboard'))
        ->on()->iPhone14Pro()
        ->inDarkMode()
        ->assertNoJavaScriptErrors()
        ->assertVisible(
            'header a[aria-label="Dutch Drone Squad dashboard"]',
        )
        ->assertScript(
            "document.querySelector('header a[aria-label=\"Dutch Drone Squad dashboard\"] [data-brand-variant=\"compact\"] [data-testid=\"dds-brand-logo\"]') !== null",
        )
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth');
});
