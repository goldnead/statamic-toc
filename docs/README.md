[![Latest Version](https://img.shields.io/github/v/release/goldnead/statamic-toc?style=flat-square)](https://github.com/goldnead/statamic-toc/releases)
![Statamic v3](https://img.shields.io/badge/Statamic-3+-FF269E)
![workflow](https://github.com/goldnead/statamic-toc/actions/workflows/tests.yaml/badge.svg)

# Statamic ToC

?> Automatic Table Of Contents for Statamic Bard or Markdown fields or other HTML content

This addon generates a Table-Of-Contents (ToC) for any Bard- or Markdown-Field in Statamic. Just like any Antlers-Tag you can use this addon in your templates with the usual Statamic-Magic Sugar:

```html
<div class="max-w-md mx-auto">
  <div class="text-2xl font-bold">Table Of Contents</div>
  <div class="py-4 text-base text-gray-700 sm:text-lg">
    <ol class="list-decimal list-inside space-y-2">
      {{ toc depth="3" }}
      <li>
        <a class="font-bold text-cyan-800" href="#{{ toc_id }}"
          >{{ toc_title }}</a
        >
        {{ if children }}
        <ol>
          {{ *recursive children* }}
        </ol>
        {{ /if }}
      </li>
      {{ /toc }}
    </ol>
  </div>
</div>
```

Sweet, isn't it?

## Installation

Install via composer:

```bash
composer require goldnead/statamic-toc
```

No further Vendor-Publishing or config files are needed.

## Usage

This Addon provides the functionality to automatically generate an array of headings from your bard or markdown field you can iterate over in your antlers templates.
Additionally, it ships with a modifier to automatically generate IDs for anchor-links.

### Blueprint setup

Ideally, this addon works out-of-the-box with any bard setup. Behind the scenes it parses the given
content for headlines and generates an associative nested (or unnested, see options below) array that you can iterate through.
So, no special headline-sets are needed, just the plain ol' default Bard-field can be used:

```yaml
title: test
sections:
  main:
    display: Main
    fields:
      ...
      -
        handle: bard
        field:
          always_show_set_button: false
          buttons:
            - h2
            - h3
            - bold
            - italic
            - unorderedlist
            - orderedlist
            - removeformat
            - quote
            - anchor
            - image
            - table
          toolbar_mode: fixed
          link_noopener: false
          link_noreferrer: false
          target_blank: false
          reading_time: false
          fullscreen: true
          allow_source: true
          enable_input_rules: true
          enable_paste_rules: true
          display: Bard
          type: bard
          icon: bard
          listable: hidden

```

Of course, you can use as many heading-buttons as you like.
If you prefer to save your bard-content as HTML, you can safely turn on `save_html: true` in your bard-settings.
You can also use this addon with your markdown-fields. Just pass it along to the tag like this:

```
{{ toc content="{markdown} }}
  ...
{{ /toc }}
```

or

```
{{ toc field="{markdown_fieldname} }}
  ...
{{ /toc }}
```

### The `toc` Modifier

Use the modifier in your templates to add IDs to your headings:

```
{{ text | toc }}
```

Then you get something like this:

```html
<h2 id="this-is-an-example-heading">This is an example heading</h2>
<p>
  Voluptate do ad anim do mollit proident incididunt culpa ex quis aliquip et
  irure Lorem. Voluptate enim cillum do nostrud eiusmod deserunt.
</p>
```

You can also pass parameters to the modifier like so:

```antlers
{{ text | toc('x-bind:id="#[id]"') }}
```

This adds additional attributes to the heading nodes where `[id]` will be replaced with the ID of the heading:

```html
<h2 id="this-is-an-example-heading" x-bind:id="#this-is-an-example-heading">
  This is an example heading
</h2>
<p>
  Voluptate do ad anim do mollit proident incididunt culpa ex quis aliquip et
  irure Lorem. Voluptate enim cillum do nostrud eiusmod deserunt.
</p>
```

!> Note: When headings are duplicated, the ID is suffixed with a number preventing duplicated IDs which would be semantially wrong in HTML.

### The `toc` Tag

You can use the `toc`-Tag like you would use any recursive tag (like the `nav` Tag) in your Antler-Templates:

```html
<ol>
  {{ toc }}
  <li>
    <a href="#{{ toc_id }}">{{ toc_title }}</a>

    {{ if children }}
    <ol>
      {{ *recursive children* }}
    </ol>
    {{ /if }}
  </li>
  {{ /toc }}
</ol>
```

By default, this addon assumes your bard-content lives inside a content-field
named `article`. To change that behaviour you can assign the name of the bard field with the parameter `field`:

`{{ toc field="bard" }}`

or alternatively you can pass the bard-content directly to the `content` parameter:

`{{ toc :content="bard" }} or {{ toc content="{bard}" }}`

If you don't want to display your ToC as a nested list you can pass the parameter `is_flat` which flattens your list to one level:

```html
<ol>
  {{ toc is_flat="true" }}
  <li>
    <a href="#{{ toc_id }}">{{ toc_title }}</a>
  </li>
  {{ /toc }}
</ol>
```

### The `toc:count` Tag

You can use this tag to check how many headings are present in the content section.

Use it like the main `toc` Tag described above.

Example:

```
{{ if {toc:count} > 0 }}
  {{# Show stuff, if headings are present #}}
  ...
{{ /if }}
```

### Starter Kit

A Tailwind-styled partial ships with the addon as a starting point. It reads the `article` field
from the current context:

```
{{ partial:statamic-toc::starter-kit }}
```

It takes the same parameters as the tag, plus a `title` for the label above the list:

```
{{ partial:statamic-toc::starter-kit
    field="content"
    depth="3"
    from="h2"
    title="On this page"
    exclude="Introduction, Footnotes"
}}
```

When the field holds no headings, the partial renders nothing at all, so you can drop it into a
template without wrapping it in a condition.

To change the markup, publish it into your project:

```bash
php artisan vendor:publish --tag=statamic-toc-views
```

That copies the file to `resources/views/vendor/statamic-toc/starter-kit.antlers.html`.

Tailwind only sees classes in the files it scans. If you use the partial straight from the addon
instead of publishing it, add the addon path to the content sources in your Tailwind config:

```js
'./vendor/goldnead/statamic-toc/resources/views/**/*.html'
```

The partial only renders the list. The anchors point at the IDs the modifier injects, so the content
field itself still needs `{{ your_field | toc }}`.

### Heading levels

`from` is the level the list starts at, `to` the level it stops at, both absolute:

```
{{ toc from="h2" to="h4" }}
```

`depth` says the same thing relative to `from`, and is what most templates use. `from="h2"` with
`depth="3"` covers h2 to h4. When both `to` and `depth` are given, `to` wins.

### Excluding headings

Pass `exclude` to leave individual headings out of the list. A comma-separated string matches
case-insensitively on any part of the heading text:

```
{{ toc exclude="Introduction, Footnotes" }}
```

A delimited pattern is treated as a regular expression:

```
{{ toc exclude="/^Appendix/i" }}
```

The excluded headings still receive their IDs from the modifier, they are only omitted from the list.

### Conditional output

`when` switches the tag off without removing it from the template:

```
{{ toc :when="show_toc" }}
```

When it evaluates to `false`, the tag returns an empty list and `no_results` is true.

### Variables

Every Item has the following variables at your disposal:

| Variable                 | Description                                                                                                 |
| ------------------------ | ----------------------------------------------------------------------------------------------------------- |
| `toc_title` _(string)_   | The title of the heading (Note: `title` would be more obvious, but this lead to some weird cascade issues.) |
| ` toc_id` _(string)_     | The slugified title to use as anchor-id                                                                     |
| `id` _(int)_             | The internal id used to assign children and parents                                                         |
| ` is_root` _(bool)_      | A flag to determine if the current heading is at root level                                                 |
| `parent` _(int/null)_    | Id of parent item if current item is a child                                                                |
| `has_children` _(bool)_  | Flag if current item has children                                                                           |
| `children` _(array)_     | Contains all the Child-headings                                                                             |
| `total_children` _(int)_ | Number of children (only if `has_children` is true)                                                         |

Also, there are the following global variables present inside the `toc` tag:

| Variable                | Description                                      |
| ----------------------- | ------------------------------------------------ |
| `total_results` _(int)_ | The number of total headings including children. |
| `no_results` _(bool)_   | True if no results are present                   |

### Parameters

You can control the behaviour with the following tag-parameters:

| Parameter | Description                                                                    | (Type) Default               |
| --------- | ------------------------------------------------------------------------------ | ---------------------------- |
| `depth`   | Specifies wich heading-depth the list includes                                 | _(int)_ `3`                  |
| `is_flat` | When true the list will be displayed as a flat array without nested `children` | _(boolean)_ `false`          |
| `field`   | The name of the bard-field.                                                    | _(string)_ `"article"`       |
| `content` | Content of the bard-structure or HTML String                                   | _(string/array/null)_ `null` |
| `from`    | The level the list starts at                                                    | _(string)_ `h1`              |
| `to`      | The level the list stops at, absolute. Wins over `depth`                       | _(string/null)_ `null`       |
| `exclude` | Comma-separated headings or a regex pattern to omit from the list              | _(string/null)_ `null`       |
| `when`    | Returns an empty list when this evaluates to false                             | _(bool)_ `true`              |

## License

This is commercial software. To use it in production you need to purchase a license at the [Statamic-Marketplace](https://statamic.com/addons).
