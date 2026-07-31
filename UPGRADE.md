# Upgrading from v1 to v2

v2 is free for existing licences. Most sites can bump the version and be done: every template
variable and every tag parameter from v1 still works, and the tag's output has the same shape.

What follows is the complete list of things that behave differently.

## Requirements

- PHP 8.2 or newer
- Statamic 5 or 6

Statamic 3 and 4 stay on the v1 line. If you are on either, keep `goldnead/statamic-toc:^1.10`,
which carries the anchor fixes described below.

## Anchors are decided in one place now

In v1 the tag and the modifier each computed their own IDs and agreed only by coincidence. Where
the coincidence broke, the table of contents linked into the void. That is fixed, and the fix
changes some IDs.

> **Coming from 1.10?** The first two items below already landed there. They are listed for
> anyone upgrading from 1.9 or earlier, for whom they are still new.

**Headings below the third level get IDs.** *(also in 1.10)* Before that the modifier only ever
injected into `h1` to `h3`, whatever `from` and `depth` said. If you added the missing IDs
yourself with JavaScript, remove that code, or you will end up with two elements carrying the
same ID.

**Headings that already have an ID keep it, and the list links to it.** *(also in 1.10)* Bard's
anchor button and hand-written HTML both produce these. Before, the list slugified the title
instead and the modifier appended a *second* `id` attribute to the same element, which is invalid
HTML. Any anchor you set by hand now works.

**Collision suffixes can shift.** Two headings with the same text still get `titel` and `titel-2`,
but v1 counted them separately per side and per field, so the numbers were not always what you
would expect. If you have external deep links pointing at a `#titel-2` style anchor, check them.

**Excluding a heading no longer renumbers the others.** In v1 `exclude` shifted the suffixes of
everything after it.

## `{{ toc:count }}` counts what the list shows

This is the one change likely to need an edit.

v1 forced the depth to 6 inside `count()`, so it reported every heading in the document while the
list underneath showed three levels. v2 counts what the list would show, using the parameters you
give it.

If you used the count to ask "does this page have any headings at all", say so explicitly:

```antlers
{{ if {toc:count depth="6"} > 0 }}
```

If you used it to decide whether to render the list, pass it the same parameters as the list, and
the two now agree:

```antlers
{{ if {toc:count field="article" from="h2" depth="3"} > 0 }}
    {{ toc field="article" from="h2" depth="3" }} ... {{ /toc }}
{{ /if }}
```

## Headings with formatting show their full text <small>(already in 1.9)</small>

Nothing changes here if you are on 1.9 or newer.

A heading built from a bold word plus plain text, like **Stütze** followed by ` im Chor`, appeared
before 1.9 as either nothing at all or as a fragment. It now reads `Stütze im Chor`. Lists get longer
where headings were previously dropped.

## Headings inside nested Bard sets are found <small>(already in 1.9)</small>

Nothing changes here if you are on 1.9 or newer.

Two-column sets, grids and replicators were skipped before 1.9. Their headings now appear in the list.
If you relied on that omission, exclude them by title:

```antlers
{{ toc exclude="Titel aus dem Set" }}
```

## Removed

- `Parser::flattenFrom()`, an empty stub that never did anything.
- `package.json`. The addon has no JavaScript and no build step. It declared an `index.js` that
  does not exist, stub scripts that exit with an error, and a licence that contradicted the
  actual one.

## New, nothing to do

- `to` as an absolute level: `{{ toc from="h2" to="h4" }}`. `depth` keeps working.
- An optional config file: `php artisan vendor:publish --tag=statamic-toc-config`. Without it the
  same defaults apply as before.
- A Tailwind starter-kit partial: `{{ partial:statamic-toc::starter-kit }}`.
