Extensible BBCode
-----------------

This is a BBCode parser for Drupal that can be extended with custom tag macros.
If you install it on your Drupal site, it will create a text format 
named "BBCode" that generates HTML out of text markup such as this:

    This is [b]bold[/b] and [url=http://drupal.org/]this is a link[/url].

Custom tags use the [Twig](http://twig.sensiolabs.org/) template engine
included in Drupal's core.

Developing
----------

You can create your own tag plugins, which are not limited to templates but can
make full use of PHP, by writing your own module.

BBCode tags are [Annotations-based plugins](https://www.drupal.org/node/1882526).
To provide one, your module needs to contain a class like this (in the appropriate
PSR-4 path `src/Plugin/XBBCode/`).

```php
namespace Drupal\{module}\Plugin\XBBCode;

use Drupal\xbbcode\Plugin\XBBCodeTagBase;

/**
 * @XBBCodeTag(
 *   id = "xbbcode.url",
 *   title = @Translation("Link"),
 *   description = @Translation("This creates a hyperlink."),
 *   name = "url",
 *   sample = @Translation("[{{ name }}=http://www.drupal.org/]Drupal[/{{ name }}]")
 * )
 */
class YourTagPlugin extends XBBCodeTagBase {
  /**
   * {@inheritdoc}
   */
  public function process(XBBCodeTagElement $tag) {
    return '<em>' . $tag->content() . '</em>';
  }
}
```

The `{{ name }}` placeholder is required as the tag name is configurable.

The required function `XBBcodeTagInterface::process(XBBCodeTagElement $tag)`
receives a tag occurrence as encountered in text, and must return HTML code.

The `XBBCodeTagElement` object provides the following methods:

- `content()` returns the rendered content of the tag.
- `option()` returns the string following "=" in the opening tag, such as
  [url=URL]...[/url]
- `attr($name)` returns the value of a named attribute in the opening tag,
  such as [quote author=AUTHOR date=DATE]...[/quote]
- `source()` returns the unrendered content of the tag. This can be used when
  your tag's content should not be rendered, such as [code]...[/code].
- `outerSource()` returns the full source, including opening and closing tags.
  This can be returned if you want to leave the tag unrendered.
