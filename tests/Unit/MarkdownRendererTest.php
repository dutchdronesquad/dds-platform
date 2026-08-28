<?php

use App\Support\MarkdownRenderer;

test('it renders github flavored markdown as html', function () {
    $markdown = <<<'MARKDOWN'
## Briefing

- Neem **accu's** mee
- Lees de [huisregels](https://example.com/huisregels)

| Onderdeel | Tijd |
| :--- | ---: |
| Briefing | 09:00 |
MARKDOWN;

    $html = (new MarkdownRenderer)->toHtml($markdown);

    expect($html)
        ->toContain('<h2>Briefing</h2>')
        ->toContain("<strong>accu's</strong>")
        ->toContain('<ul>')
        ->toContain('<a href="https://example.com/huisregels">huisregels</a>')
        ->toContain('<table>')
        ->toContain('<th align="left">Onderdeel</th>')
        ->toContain('<td align="right">09:00</td>');
});

test('it strips raw html and blocks unsafe links', function () {
    $html = (new MarkdownRenderer)->toHtml(
        "Inject: <script>alert(\"xss\")</script>\n\n[klik](javascript:alert)",
    );

    expect($html)
        ->not->toContain('<script')
        ->not->toContain('href="javascript:')
        ->toContain('klik');
});

test('it returns null for empty markdown and creates plain text for metadata', function () {
    $renderer = new MarkdownRenderer;

    expect($renderer->toHtml(null))->toBeNull()
        ->and($renderer->toHtml('   '))->toBeNull()
        ->and($renderer->toPlainText('Welkom bij **DDS** &amp; vrienden.'))
        ->toBe('Welkom bij DDS & vrienden.');
});
