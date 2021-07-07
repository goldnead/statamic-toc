[![Latest Version](https://img.shields.io/github/v/release/goldnead/statamic-toc?style=flat-square)](https://github.com/goldnead/statamic-toc/releases)
![Statamic v3](https://img.shields.io/badge/Statamic-3.1+-FF269E)

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
  {{ /toc }}
</ol>
```

If you don't want to display your ToC as a nested list you can pass the parameter `is_flat`:

```html
<ol>
  {{ toc is_flat="true" }}
  <li>
    <a href="#{{ toc_id }}">{{ toc_title }}</a>
  </li>
  {{ /toc }}
</ol>
```

### Variables

Every Item has the following variables at your disposal:

| Variable            | Description                                                 |
| ------------------- | ----------------------------------------------------------- |
| toc_title (string)  | The title of the heading                                    |
|  toc_id (string)    | The slugified title to use as anchor-id                     |
| id (int)            |  The internal id used to assign children and parents        |
|  is_root (bool)     | A flag to determine if the current heading is at root level |
| parent (int/null)   | Id of parent item if current item is a child                |
| has_children (bool) |  Flag if current item has children                          |
|  children (array)   | Contains all the Child-headings                             |

### Parameters

You can control the behaviour with the following tag-parameters:

| Parameter | Description                                                                    | (Type) Default  |
| --------- | ------------------------------------------------------------------------------ | --------------- |
| depth     | Specifies wich heading-depth the list includes                                 | (int) 3         |
| is_flat   | When true the list will be displayed as a flat array without nested `children` | (boolean) false |

## License

This is commercial software. To use it in production you need to purchase a license at the [Statamic-Marketplace](https://statamic.com/addons).
