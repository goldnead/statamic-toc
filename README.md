[![Latest Version](https://img.shields.io/github/v/release/goldnead/statamic-toc?style=flat-square)](https://github.com/goldnead/statamic-toc/releases)
![Statamic v3](https://img.shields.io/badge/Statamic-3+-FF269E)

# Automatic Table Of Contents for Statamic Bard-Fields

This addon generates a Table-Of-Contents (ToC) for any Bard-Field in Statamic. Just like any Antlers-Tag you can use this addon in your templates with some Statamic-Magic Sugar:

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

## Installation

Install via composer:

```bash
composer require goldnead/statamic-toc
```

No further Vendor-Publishing or config files are needed.

## Usage

This Addon provides functionality to automatically generate an array of the headings from your bard-field so you can iterate through them in your template. It also ships with a modifier to automatically generate IDs for anchor-links.

### Bard setup

The idea behind this addon is that it should work out of the box with your existing Bard-Setup. Behind the scenes it will parse its
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
If you prefer to save your bard-content as HTML, you can safely do so. This addon works best with structured bard-data
but HTML is also supported and works as intended. So you can safely turn on `save_html: true` in your bard-settings.

### `toc` Modifier

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
...
```

### `toc` Tag

You can use the `toc`-Tag as you would use any recursive tag (like the `nav` Tag) in your Antler-Templates:

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

As default this addon assumes your bard-content lives inside a content-field
named `article`. To change that behaviour you can assign the name of the field
bard field with the parameter `field`:

`{{ toc field="bard" }}`

or alternatively you can pass the bard-content directly to the `content` parameter:

`{{ toc :content="bard" }}`

If you don't want to display your ToC as a nested list you can pass the parameter `is_flat`:

```html
<ol>
  {{ toc is_flat="true" }}
  <li>
    <a href="#{{ toc_id }}">{{ toc_title }}</a>
  </li>
  {{ /toc }}
</ol>
```

### Variables

Every Item has the following variables at your disposal:

| Variable                | Description                                                 |
| ----------------------- | ----------------------------------------------------------- |
| `toc_title` _(string)_  | The title of the heading                                    |
| ` toc_id` _(string)_    | The slugified title to use as anchor-id                     |
| `id` (int)              |  The internal id used to assign children and parents        |
| ` is_root` _(bool)_     | A flag to determine if the current heading is at root level |
| `parent` _(int/null)_   | Id of parent item if current item is a child                |
| `has_children` _(bool)_ |  Flag if current item has children                          |
| `children` _(array)_    | Contains all the Child-headings                             |
| `total_children` _(int)_| Number of children (only if `has_children` is true)         |

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
| `from`    | The starting point from where the list shohuld be outputted                    | _(string)_ `h1`              |

## License

This is commercial software. To use it in production you need to purchase a license at the [Statamic-Marketplace](https://statamic.com/addons).
