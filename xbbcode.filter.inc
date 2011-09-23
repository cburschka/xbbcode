<?php
// $Id$


/**
 * @file
 * The filtering class. This will be instanced for each filter, and then
 * called to process a piece of text.
 */
class XBBCodeFilter {
  var $tags;
  var $weighted_tags;

  /**
   * Construct a filter object from a bundle of tags, and the format ID.
   *
   * @param $tags
   *   Tag array.
   * @param $format
   *   Text format ID.
   */
  function __construct($tags, $filter, $format) {
    $this->tags = $tags;
    $this->format = $format;
    $this->filter = $filter;
    $this->autoclose_tags = $filter->settings['autoclose'];
  }

  /**
   * Execute the filter on a particular text.
   *
   * @param $text
   *   The text to be filtered.
   *
   * @return
   *   HTML code.
   */
  function process($text) {
    // Find all opening and closing tags in the text.
    preg_match_all('/\[(?P<closing>\/)?(?P<name>[a-z0-9]+)(?P<args>[= ][^\[\]]*[^\-])?\]/i', $text, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    if (!$tags) {
      return $text;
    }

    // Initialize the stack with a root tag, and the name tracker.
    $open_tags = array(new XBBCodeTagMatch());
    $open_by_name = array();
    foreach ($tags as $tag) {
      $open_by_name[$tag['name'][0]] = 0;
    }
    $nocode = 0;

    foreach ($tags as $tag) {
      // Make sure the tag is known to the filter.
      if (isset($this->tags[$tag['name'][0]])) {
        // If the tag is opening...
        if (!$tag['closing'][0]) {
          // Add text before the new tag to the parent, then stack the new tag.
          $parent = end($open_tags);
          $parent->content .= substr($text, $parent->offset, $tag[0][1] - $parent->offset);
          $parent->offset = $tag[0][1];

          // Stack the newly opened tag, or render it if it's selfclosing.
          $current = new XBBCodeTagMatch($tag, $this);
          if ($current->selfclosing) {
            $parent->content .= $nocode ? $current->element : $this->render_tag($current);
          }
          else {
            array_push($open_tags, $current);
            $open_by_name[$tag['name'][0]]++;
            if (end($open_tags)->nocode) {
              $nocode++;
            }
          }
        }
        else {
          // If the tag is closing, make sure a matching open tag exists.
          if ($open_by_name[$tag['name'][0]]) {
            // Pop open tags off the stack until we reach it.
            $current = array_pop($open_tags);
            while ($current->name != $tag['name'][0]) {
              $open_by_name[$current->name]--;
              // If a nocode tag breaks, we may have to retroactively render.
              if ($current->nocode) {
                $nocode--;
                if (!$nocode) {
                  $current->content = $this->process($current->content);
                }
              }
              // Append the broken tag and its content to its parent.
              $offset  = $current->offset;
              $content = $current->element . $current->content;
              $current = array_pop($open_tags);
              $current->content .= $content;
              $current->offset = $offset;
            }

            // Append the rest of the tag's content and render it if required.
            $current->content .= substr($text, $current->offset, $tag[0][1] - $current->offset);
            if ($nocode) {
              $output = $current->element . $current->content . "[/{$current->name}]";
            }
            else {
              $output = $this->render_tag($current);
            }

            if ($current->plain) {
              // We will double-encode entities only if non-encoded chars exist.
              if ($current->content != htmlspecialchars($current->content, ENT_QUOTES, 'UTF-8', FALSE)) {
                $current->content = check_plain($current->content);
              }
            }

            // Pop the last parent tag off the stack.
            $open_by_name[$current->name]--;
            if ($current->nocode) {
              $nocode--;
            }
            // Append the rendered HTML to the content of its parent tag.
            $parent = end($open_tags);
            $parent->content .= $output;
            $parent->offset = $tag[0][1] + strlen($tag[0][0]);
          }
        }
      }
    }
    end($open_tags)->content .= substr($text, end($open_tags)->offset);

    if ($this->autoclose_tags) {
      while (count($open_tags) > 1) {
        // Render the unclosed tag and pop it off the stack
        $output = $this->render_tag(array_pop($open_tags));
        end($open_tags)->content .= $output;
      }
    }
    else {
      while (count($open_tags) > 1) {
        $current = array_pop($open_tags);
        $content = $current->element . $current->content;
        end($open_tags)->content .= $content;
      }
    }

    return end($open_tags)->content;
  }

  /**
   * Render a single tag, based on its arguments and content.
   *
   * @param $name
   *   Name of the tag.
   * @param $args
   *   Arguments passed to the tag.
   * @param $content
   *   Content nested in the tag (containing potential other pairs, which must
   *   not be touched in any other way than _xbbcode_revert_tags().
   *
   * @return
   *   HTML code to insert in place of the tag and its content.
   */
  function render_tag(XBBCodeTagMatch $tag) {
    $tag->args = _xbbcode_parse_args($tag->args);
    $tag->option = isset($tag->args['option']) ? $tag->args['option'] : NULL;

    if ($callback = $this->tags[$tag->name]->callback) {
      return $callback($tag, $this);
    }
    else {
      $replace['{content}'] = $tag->content;
      foreach ($tag->args as $name => $value) {
        $replace['{' . $name . '}'] = $value;
      }

      $markup = str_replace(
        array_keys($replace),
        array_values($replace),
        $this->tags[$tag->name]->markup
      );

      // Make sure that unset placeholders are replaced with empty strings.
      $markup = preg_replace('/{[a-z]+}/', '', $markup);

      return $markup;
    }
  }
}

class XBBCodeTagMatch {
  var $name = NULL, $args = NULL, $element = NULL, $offset = 0, $content = '';
  var $nocode = FALSE, $plain = FALSE;
  
  function __construct($regex_set = NULL, $filter = NULL) {
    if ($filter) {
      $this->name    = $regex_set['name'][0];
      $this->args    = isset($regex_set['args']) ? $regex_set['args'][0] : NULL;
      $this->element = $regex_set[0][0];
      $this->offset  = $regex_set[0][1] + strlen($regex_set[0][0]);
      $this->content = '';

      $this->nocode      = $filter->tags[$this->name]->options->nocode;
      $this->plain       = $filter->tags[$this->name]->options->nocode;
      $this->selfclosing = $filter->tags[$this->name]->options->selfclosing;
    }
  }
}

/* change 2007-02-09 9:11:
 * tags are now paired in the order they appear in, not in their order in the array.
 * this will later be adjusted to allow weighting.
 *
 * change 2009-12-10 5:48:
 * Monstrous cleanup.
 *
 * change 2010-04-14 13:34:
 * Completely new algorithm, making all tag weights obsolete.
 */

