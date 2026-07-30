# Changelog

All notable changes to `statamic-toc` will be documented in this file.

## 2026-07-30 v1.10

### Fixed — three ways an anchor pointed at nothing

All three end the same way for a reader: they click an entry in the table of contents and the
page does not move. They had three separate causes, and `tests/Unit/AnchorIntegrityTest.php`
pins each one.

**Headings below `h3` never got an id.** Id injection ran over `h1`–`h3`, because it reused the
same `maxLevel` the list uses and that defaults to `3`. A list configured deeper than three
levels linked to `h4`s that carried no id at all. Injection now always spans `h1`–`h6`,
independently of the list: a heading outside the list is harmless with an id on it, while one
inside it without an id is a dead link.

**The tag's `depth` leaked into the modifier.** The facade hands out one parser for the whole
request and `make()` reset only the content and the slugs, so `{{ toc depth="1" }}` above an
article silently removed the ids from every `h2` and `h3` in the article below it. The narrower
the list, the more anchors broke — the exact opposite of what the parameter reads like it does.
`make()` now resets `depth`, `from`, `flatten` and `exclude` to their defaults as well.

**A heading that already had an id got a second one.** The check for an existing id required a
character after the closing quote, so an id written in the last attribute position — which is
where people write it — was never seen:

```html
<!-- in  --> <h2 id="mine">Kept</h2>
<!-- out --> <h2 id="mine" id="kept">Kept</h2>
```

Two `id` attributes on one element is invalid HTML, a browser keeps the first, and the list
linked to the second. The pattern no longer requires the trailing character, and ignores
`data-id` and `aria-id` through a lookbehind rather than by accident. The list now also links to
a hand-written id instead of slugging past it, so both halves name the same anchor.

### Added

- A Tailwind-styled starter-kit partial, `{{ partial:statamic-toc::starter-kit }}`. Publish it
  with `php artisan vendor:publish --tag=statamic-toc-views` to change the markup.
- The addon registers its view namespace explicitly, so partials resolve in package test suites too.

### Notes

Still on PHP 7.4 and Statamic 3 through 6, unchanged. Nothing here alters the output for a
document whose headings had no hand-written ids and whose list stayed at the default depth,
which is the common case.

The `mb_convert_encoding(..., 'HTML-ENTITIES', ...)` deprecation notice on PHP 8.2 and up is
**not** fixed here. Removing it means dropping PHP 7.4, so it waits for the v2 line.

## 2026-07-30 v1.9

- Headings inside nested Bard sets (columns, grids, replicators) are now found. Previously only
  top-level nodes were scanned.
- Headings with inline formatting (bold, italic, links) keep their full text. Before, a heading
  starting with a mark was dropped entirely and a heading containing one was cut short (#26).
- Malformed Bard nodes no longer cause fatal errors: missing `attrs`, missing, empty or
  non-array `content`, and non-numeric levels are skipped instead.
- `from()` no longer expands the depth when called after `depth()` or called twice.
- New `exclude` parameter on the tag: comma-separated headings or a regex pattern.
- Headings that normalize to an empty string are left out of the list.
- Test suite: three test classes were silently skipped on PHPUnit 12 because doc-comment
  annotations are no longer read. Method names now carry the `test_` prefix, which works on
  every supported PHPUnit version.

## 2026-04-08 v1.8

- Support for Statamic v6

## 2021-07-08 v1.0.4

- Support Level-Start
- Refactor & document code.

## 2021-07-08 v1.03

- Fix ToC not displaying in some situations.

## 2021-07-07 v1.02

- Added support for HTML-Mode in Bard
- Minor fixes

## 2021-07-06 v1.0.1

- Remove Debugging-Function

## 2021-07-06 v1.0.0

- Implemented all required features.
