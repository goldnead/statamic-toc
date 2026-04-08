[![Latest Version](https://img.shields.io/github/v/release/goldnead/statamic-toc?style=flat-square)](https://github.com/goldnead/statamic-toc/releases)
![Statamic v3+](https://img.shields.io/badge/Statamic-3.0+-FF269E)
![Laravel v8.0+](https://img.shields.io/badge/Laravel-8.0+-FF2D20)
![License](https://img.shields.io/badge/license-Commercial-brightgreen)

# Statamic ToC

**Automatic Table Of Contents for Statamic Bard, Markdown, or HTML content.**

This addon extracts headings from your content fields and generates a structured, hierarchical array that you can loop through in your Antlers templates. It handles nested headings, generates unique anchor IDs, and even scans recursively through your Bard sets.

---

## 🚀 Quick Start

1. **Install** the addon via Composer:
   ```bash
   composer require goldnead/statamic-toc
   ```
2. **Inject IDs** into your content field in your template so anchor links work:
   ```antlers
   <article>
     {{ article | toc }}
   </article>
   ```
   > The `toc` modifier adds `id="..."` attributes to every heading tag in the rendered HTML output. Apply it to whichever field holds your content (`article`, `content`, etc.).
3. **Display the ToC** anywhere on the page:
   ```antlers
   <ul>
     {{ toc field="article" }}
       <li><a href="#{{ toc_id }}">{{ toc_title }}</a></li>
     {{ /toc }}
   </ul>
   ```

---

## ✨ Tailwind CSS Starter Kit

A pre-styled Tailwind CSS partial is included as a ready-to-use starting point. It supports nested levels and sensible defaults, and is designed to be published and customised.

**Basic usage** (reads the default `article` Bard field):
```antlers
{{ partial:goldnead/statamic-toc::starter-kit }}
```

**With configuration:**
```antlers
{{ partial:goldnead/statamic-toc::starter-kit
    field="article"
    depth="3"
    from="h2"
    title="In this article"
    exclude="Introduction, Conclusion"
}}
```

To customise the markup, publish the view to your project:
```bash
php artisan vendor:publish --tag=statamic-toc
```
This copies the partial to `resources/views/vendor/statamic-toc/starter-kit.antlers.html`.

> **Tailwind note:** add the addon's view path to your `tailwind.config.js` content sources so Tailwind picks up the utility classes:
> ```js
> './addons/goldnead/statamic-toc/resources/views/**/*.html'
> ```

---

## 🛠 Usage

### The `{{ toc }}` Tag

The `{{ toc }}` tag is a recursive tag (similar to `{{ nav }}`) that loops over your headings.

| Parameter  | Description                                                                                       | Default     |
| ---------- | ------------------------------------------------------------------------------------------------- | ----------- |
| `field`    | The handle of the field to parse (Bard, Markdown, or HTML string).                                | `article`   |
| `content`  | Pass raw content directly (useful for variables or scoped data).                                  | `null`      |
| `depth`    | How many heading levels deep to include. Combined with `from`: `from="h2" depth="3"` → H2, H3, H4. | `3`         |
| `from`     | The starting heading level (e.g., `h2` skips H1 entirely).                                       | `h1`        |
| `is_flat`  | If `true`, returns a flat array instead of a nested tree.                                         | `false`     |
| `exclude`  | Comma-separated heading titles or a regex pattern to omit from the ToC.                           | `null`      |
| `when`     | Conditionally disable the ToC (accepts boolean values).                                           | `true`      |

#### Tag Variables (Inside the loop)

| Variable         | Type     | Description                                                                                    |
| ---------------- | -------- | ---------------------------------------------------------------------------------------------- |
| `toc_title`      | `string` | The normalized text content of the heading.                                                    |
| `toc_id`         | `string` | The unique slugified ID for anchor links.                                                      |
| `level`          | `int`    | The absolute heading level (1 = H1, 2 = H2, etc.).                                            |
| `children`       | `array`  | Nested array of child headings. Loop with `{{ *recursive children* }}`.                        |
| `has_children`   | `bool`   | Whether the current item has nested children.                                                  |
| `is_root`        | `bool`   | Whether the item is at the top level of the ToC.                                               |
| `total_children` | `int`    | Number of immediate children. Only present when `has_children` is `true`.                      |

---

### The `{{ toc:count }}` Tag

Returns the total number of headings found. Useful for conditionally showing or hiding the ToC. Pass the same `field`, `depth`, and `from` params as your `{{ toc }}` loop so the count matches the items displayed.

```antlers
{{ if {toc:count field="article" depth="3" from="h2"} > 0 }}
    <div class="sidebar">
        <h4>Navigation</h4>
        {{ toc field="article" depth="3" from="h2" }}
            ...
        {{ /toc }}
    </div>
{{ /if }}
```

---

### The `toc` Modifier

Injects `id="..."` attributes into heading tags in the rendered HTML so anchor links work. Apply it to the field output in your template.

**Basic usage:**
```antlers
{{ article | toc }}
```

**Advanced: Custom Attributes**

Pass a string to add extra attributes to every heading tag. Use `[id]` as a placeholder for the generated ID:
```antlers
{{ article | toc('x-on:click="activeId = \'[id]\'"') }}
```
*Result: `<h2 id="my-heading" x-on:click="activeId = 'my-heading'">My Heading</h2>`*

---

## 🌟 Advanced Features

### Deep Bard Scan
Statamic ToC recursively walks your entire Bard structure. Headings inside nested sets (Columns, Grid, Replicator, etc.) are detected automatically — no configuration required.

### Heading Normalization
Headings containing Bard inline marks (bold, italic, links) are normalized to plain text. A heading composed of `"Our "` + bold `"Vision"` becomes `Our Vision` in the ToC title.

### Flexible Exclusion
Exclude headings by partial string match or regex:
```antlers
{{# Comma-separated strings (case-insensitive partial match) #}}
{{ toc exclude="Contact, Footer" }}

{{# Regex pattern #}}
{{ toc exclude="/^Appendix/i" }}
```

---

## License

This is commercial software. To use it in production, you must purchase a license at the [Statamic Marketplace](https://statamic.com/addons/goldnead/toc-for-bard-and-markdown).
