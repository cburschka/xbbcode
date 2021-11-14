# Extensible BBCode

This is a BBCode parser for Drupal that can be extended with custom tag macros.
If you install it on your Drupal site, it will create a text format named
"BBCode" that generates HTML out of text markup such as this:

    This is [b]bold[/b] and [url=https://drupal.org/]this is a link[/url].

# Usage

The **Extensible BBCode** module allows users with the *administer custom BBCode
tags* permission to manually create BBCode tags via the web interface. These
tags use the [Twig](https://twig.symfony.com/) template engine included
in Drupal's core. They become part of the site configuration.

The module on its own provides no defaults, but contains a separate module
named **Standard tags**, which contains a set of the most common BBCode tags.

# Syntax

The BBCode style used by this module is as follows. "Tags" may consist of one
of these three forms:

- `[{tag}]...[/{tag}]`
- `[{tag}={option}]...[/{tag}]`
- `[{tag} {key}={value}]...[/{tag}]`

The value of `{tag}` is the *name* of the tag. The value of `{option}` in the
second form is an *option argument*. The pairs of `{key}={value}` in the third
form (of which there can be any number) are *attribute arguments*. A tag cannot
have both option and attribute arguments.

The values of `{tag}` and `{key}` consist of alphanumeric characters, hyphen
and underscore characters. The value of `{tag}` is always lower-case.

The values of `{option}` and `{value}` may contain any characters, but those
characters that would terminate them (spaces for `{value}`, or `]` for
`{option}` and `{value}`) must be prefixed with a single backslash character
 (eg. `\]`) to be used literally.

Alternatively, `{option}` or `{value}` may be encased in `""` or `''`, in which
case only `"` or `'` must be prefixed with a backslash to be used literally.

The value of `...` is the *content* of the tag. The content may consist of any
text, as well as further tags, provided that they are correctly nested. For
example, in the input `[url=https://www.example.com][b]Link[/url][/b]`, the
strings `[b]` and `[/b]` would not be read as tags, as `[b]` is inside the
link tag and `[/b]` is not.

# Extending

For tags that require full PHP (and for packaging and easily reusing tags on 
different sites), you will need to create a custom module.

There are two ways a module can provide Extensible BBCode tags.

1. Define a template tag using a configuration file.
2. Implement a full-featured tag plugin class.

## Template

For most use cases, a template is sufficient. Twig templates support simple
control structures that mean the tag output can be dynamic without needing PHP.

A template tag is defined in a file named `config/install/xbbcode.tag.{id}.yml`
that must contain the following:

```yaml
id: {id}
label: "An administrative label for your tag."
description: "Describes the tag's function for users (used for filter tips)."

# A default name for the tag (as in [name]...[/name]).
name: {name}

# A sample use case for the tag (for the filter tips).
sample: "[{{ name }}]...[/{{ name }}]"
```

It must also contain exactly one of the following:

```yaml
# An inline Twig template, equivalent to a tag created through the site:
template_code: "<span>{{ tag.content }}</span>"
```

OR

```yaml
# A template file that must be placed in "templates/"
template_file: "@{modulename}/{template}.html.twig"
```

Optionally, you may declare [CSS/JS libraries](https://www.drupal.org/developing/api/8/assets)
defined in `*.libraries.yml` that will be added whenever the tag is rendered:

```yaml
attached:
  library:
    - module/library
```

## Plugin class

A plugin class can use PHP while processing a tag, and is therefore more
powerful.

BBCode tags are [Annotations-based plugins](https://www.drupal.org/node/1882526).
To provide one, your module needs to contain a class like this (in the
appropriate PSR-4 path `src/Plugin/XBBCode/`).

```php
namespace Drupal\mymodule\Plugin\XBBCode;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Plugin\TagPluginBase;
use Drupal\xbbcode\TagProcessResult;
use InvalidArgumentException;

/**
 * Sample plugin that renders a [url=https://www.example.com]Link[/url] tag.
 *
 * Note the attention given to markup safety. The plugin is responsible for
 * ensuring that all user input (eg. $tag->getOption()) is sanitized correctly
 * before being included in the output.
 *
 * @XBBCodeTag(
 *   id = "mymodule.url",
 *   title = @Translation("Link"),
 *   description = @Translation("This creates a hyperlink."),
 *   name = "url",
 *   sample = @Translation("[{{ name }}=https://www.drupal.org/]Drupal[/{{ name }}]")
 *   attached = {
 *     "libraries" = {
 *       "module/library"
 *     }
 *   }
 * )
 */
class YourTagPlugin extends TagPluginBase {
  /**
   * {@inheritdoc}
   */
  public function doProcess(TagElementInterface $tag): TagProcessResult {
    // Read it as an absolute URL.
    try {
      $url = Url::fromUri($tag->getOption())->toString();
    }
    // Or as a relative URL.
    catch (InvalidArgumentException $exception) {
      try {
        $url = Url::fromUserInput($tag->getOption())->toString();
      }
      // If neither succeeds, filter out all HTML.
      // The link is probably broken, but it won't break anything else.
      catch (InvalidArgumentException $exception) {
        $url = Html::escape($tag->getOption());
      }
    }
    return new TagProcessResult("<a href=\"{$url}\">{$tag->getContent()}</a>");
  }
}
```

The `{{ name }}` placeholder is required as the tag name is configurable.

The required function `TagPluginInterface::process(TagElementInterface $tag)`
receives a tag occurrence as encountered in text, and must return HTML code.

The `TagElementInterface` object provides the following methods:

- `getContent()` returns the rendered content of the tag.
- `getOption()` returns the string following "=" in the opening tag, such as
  [url=URL]...[/url]
- `getAttribute($name)` returns the value of a named attribute in the opening
  tag, such as [quote author=AUTHOR date=DATE]...[/quote]
- `getSource()` returns the unrendered content of the tag. This can be used when
  your tag's content should not be rendered, such as [code]...[/code].
- `getOuterSource()` returns the content including opening and closing tags.
  This can be used if you want to leave the tag unrendered. Nested tags are
  still rendered normally.

# Safety

As always when user input is printed in an HTML document, it is important to
maintain [markup safety](https://www.drupal.org/node/2489544).

* The return values of `getContent()` and `getSource()` can be considered safe.
  They have run through all filters in the format (blocking HTML by default)
  and have additionally been filtered by the BBCode module itself to restrict
  unsafe HTML (in case BBCode is used without other filters).

* The return value of `getOuterSource()` can be considered safe. It consists of
  the tag name, any arguments, and the tag content. The name and content are
  already safe, and the arguments will automatically be sanitized.

* The return values of `getOption()` and `getAttribute()` are **not** safe.
  They are provided as raw input; the module will in fact attempt to undo HTML
  restrictions other filters have applied to them. If your plugin prints any
  of these values in the output, you must filter them.

# License

Extensible BBCode may be redistributed and/or modified under the terms of the
GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
