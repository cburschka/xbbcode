# Extensible BBCode

This is a BBCode parser for Drupal that can be extended with custom tag macros.
If you install it on your Drupal site, it will create a text format named
"BBCode" that generates HTML out of text markup such as this:

    This is [b]bold[/b] and [url=http://drupal.org/]this is a link[/url].

# Usage

The **Extensible BBCode** module allows users with the *administer custom BBCode
tags* permission to manually create BBCode tags via the web interface. These
tags use the [Twig](http://twig.sensiolabs.org/) template engine included
in Drupal's core. They become part of the site configuration.

The module on its own provides no defaults, but contains a separate module
named **Standard tags**, which contains a set of the most common BBCode tags.

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
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Plugin\TagPluginBase;
use Drupal\xbbcode\TagProcessResult;
use InvalidArgumentException;

/**
 * Sample plugin that renders a [url=http://www.example.com]Link[/url] tag.
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
 *   sample = @Translation("[{{ name }}=http://www.drupal.org/]Drupal[/{{ name }}]")
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
    return new TagProcessResult(
      Markup::create('<a href="' . $url . '">' . $tag->getContent() . '</a>')
    );
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
- `getOuterSource()` returns the full source, including opening and closing
  tags. This can be returned if you want to leave the tag unrendered.

**Note:** The option and attributes are provided as they were entered without
filtering, regardless of other filters that may be enabled in the format.
They must be [properly escaped](https://www.drupal.org/node/2489544).

# License

Extensible BBCode may be redistributed and/or modified under the terms of the
GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
