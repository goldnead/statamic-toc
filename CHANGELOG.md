# Changelog

All notable changes to `statamic-toc` will be documented in this file.

## 2026-07-30 v2.0.0

A rewrite of the internals behind an unchanged template API. Every tag parameter and every
template variable from v1 still works and the output has the same shape; `UPGRADE.md` lists
every behaviour that differs, and `{{ toc:count }}` is the one likely to need an edit.


- Requires PHP 8.2 and Statamic 5 or 6. Statamic 3 and 4 stay on the v1 line.
  *(Corrected 2026-07-31: that line is not maintained further. It ends at `v1.10`,
  which stays installable and receives no more fixes.)*
- `league/commonmark` is a declared dependency instead of something the addon
  hoped Statamic would bring along.
- Heading extraction moved out of `Parser` into `Extractors/{Bard,Html,Markdown}`
  behind one interface, chosen by an explicit `Detector`. Content is no longer
  taken for markdown because it contains a `#`, and HTML without headings no
  longer falls through the markdown branch.
- Extractors report every heading at every level plus any id already on it.
  Filtering by level moved to the caller, which is what lets the tag and the
  modifier eventually share one set of headings.
- Dropped `mb_convert_encoding(..., 'HTML-ENTITIES', ...)`, deprecated since
  PHP 8.2, and `CommonMarkConverter::convertToHtml()`, deprecated in
  league/commonmark 2.
- The tag and the modifier now read their anchors from one shared registry
  instead of each running its own slug pass. Four defects go away with it:
  headings below the third level get ids; a `from` above a repeated heading no
  longer shifts the anchors under it; a hand-written id is used by the list
  instead of being slugged past; and two modifier calls on one page stop
  renumbering each other.
- A heading that already carries an id keeps exactly that one. The old check
  missed an id in the last attribute position and appended a second, which is
  invalid HTML and left the anchor pointing nowhere.
- Excluding a heading from the list no longer changes the anchors of the
  others.
- New `to` parameter on the tag, an absolute level: `{{ toc from="h2" to="h4" }}`.
  `depth` keeps working and says the same thing relative to `from`; when both
  are given, `to` wins.
- **Breaking:** `{{ toc:count }}` counts what the list shows. It used to force
  the depth to 6 and report a different number than the list right underneath
  it. Templates that relied on the old number to ask "are there any headings at
  all" want `{{ toc:count depth="6" }}`.
- Options live in one immutable `Options` object instead of two mutable level
  fields recomputed from each other. `Parser::flattenFrom()`, an empty stub
  marked TODO since 2021, is gone.
- An optional config file for the defaults, `field`, `from`, `depth`, `to` and
  `flat`. Publish it with `--tag=statamic-toc-config`. Without it the same
  defaults apply as before. Closes the oldest open issue, #6 from 2021.
- Removed `package.json`. The addon has no JavaScript and no build step; the
  file declared an index.js that does not exist, stub scripts that exit with an
  error, and MIT, which contradicted the actual licence.
- `UPGRADE.md` documents every behaviour change from v1.
- CI runs PHP 8.2, 8.3 and 8.4 across Statamic 5 and 6.
- Carries everything from v1.10, including the three anchor fixes and the guard against a
  malformed Bard node whose `attrs` arrive as an object. The v1.10 regression tests run
  unchanged against the rewrite, which is what says the two lines agree.

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

### Fixed — a malformed Bard node could fatal instead of being skipped

`collectHeadingsRecursively()` read `$items['attrs']['level']`, which throws
`Cannot use object of type stdClass as array` when a node's `attrs` arrives as an object rather
than an array. The null-coalesce does not protect against array access on an object. Both reads
of `attrs` are guarded now (#39).

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
