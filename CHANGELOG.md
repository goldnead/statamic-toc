# Changelog

All notable changes to `statamic-toc` will be documented in this file.

## Unreleased

- New: a Tailwind-styled starter-kit partial, `{{ partial:statamic-toc::starter-kit }}`. Publish it
  with `php artisan vendor:publish --tag=statamic-toc-views` to change the markup.
- The addon registers its view namespace explicitly, so partials resolve in package test suites too.

## Unreleased (v2)

- Requires PHP 8.2 and Statamic 5 or 6. Statamic 3 and 4 stay on the v1 line.
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
