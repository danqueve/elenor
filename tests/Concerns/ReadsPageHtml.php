<?php

namespace Tests\Concerns;

/**
 * Look at one part of a rendered page instead of the whole HTML: the header
 * and the footer repeat words like "Ofertas" on every page.
 */
trait ReadsPageHtml
{
    /**
     * The <section> labelled by the heading with this id, or '' if the page has none.
     */
    protected function section(string $html, string $headingId): string
    {
        preg_match('/<section[^>]*aria-labelledby="'.preg_quote($headingId, '/').'".*?<\/section>/s', $html, $match);

        return $match[0] ?? '';
    }

    /**
     * The names on the product cards found in a piece of HTML, in order.
     *
     * @return list<string>
     */
    protected function productNames(string $html): array
    {
        preg_match_all('/<h3[^>]*>\s*<a [^>]*>(.*?)<\/a>/s', $html, $matches);

        return array_map(fn (string $name) => html_entity_decode($name, ENT_QUOTES), $matches[1]);
    }
}
