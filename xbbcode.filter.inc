<?php
// $Id$

/**
 * @file
 * The filtering class. This will be instantiated for each filter, and then 
 * called to process a piece of text.
 */


class XBBCodeFilter {
  var $tags;
  var $weighted_tags;
  
  /**
   * Construct a filter object from a bundle of tags, and the format ID.
   * @param $tags
   *   Tag array (not yet weighted).
   * @param $format
   *   Text format ID.
   */
  function _construct($tags, $format = 0) {
    $this->tags = $tags;
    $this->format = $format;
    $this->autoclose_tags = variable_get('xbbcode_filter_' . $this->format . '_autoclose', FALSE);
    $this->weighted_tags = array();

    foreach ($this->tags as $name => $tag) {
      $this->weighted_tags[$tag->weight][] = $name;
    }
  }
  
  /**
   * Execute the filter on a particular text.
   * @param $text
   *   The text to be filtered.
   * @return
   *   HTML code.
   */
  function process($text) {
    // Generate a code that does not occur in the text.
    $code = _xbbcode_unique_code($text);
    
    // Pair up the tags.
    list($text, $pairs) = $this->pair_tags($text, $code);
    if ($pairs) {
      // If pairs were found, render them.
      ksort($pairs);
      $text = $this->render_tags($text, $pairs, $code);
    }

    return $text;
  }
  
  /**
   * This is the pairing engine. It pairs up the nested tags in the text by 
   * giving them unique ids in this form:
   * [tag] [tag] [/tag] [/tag] becomes [tag-0-CODE] [tag-1-CODE] [/tag-1-CODE] [/tag-0-CODE]
   * @param $text
   *   The text containing tags.
   * @param $code
   *   The unique code to enter.
   * @return
   *   An array containing the text and the discovered pairs.
   */
  function pair_tags($text, $code) {
    $pattern = '/\[(\/)?([a-z0-9]+)([= ][^\[\]]*[^\-])?\]/ie';
    $replace = '$this->pair_tag(\'$2\',\'$1\',\'$3\', $code, $state);';
    
    $state = (object) array(
      'pair_id' => 0,
      'open' => array(),
      'pairs' => array(),
      'weight' => 0
    );
    
    foreach (array_keys($this->weighted_tags) as $weight) {
      // Tell the pairing function which tags to pair up in this round.
      $state->weight = $weight;
      $text=preg_replace($pattern, $replace, $text); // invoke the pairing function
    }

    if ($this->autoclose_tags) {
      $text .= $this->closure($state, $code);
    }

    return array($text, $state->pairs); // return the text and the pairs.
  }  
  
  /**
   * Regular Expression callback for pairing up one tag. 
   * When called with a tag instance and the current state, it will stack up 
   * open tags and give every pair its own ID.
   * @param $name
   *   Name of the tag that was found.
   * @param $isclosing
   *   Whether it was an opening or closing tag (Value is either "/" or "")
   * @param $args
   *   Argument string following the opening tag. 
   * @param $code
   *   A unique code to insert non-colliding identifiers.
   * @param $state
   * @return
   *   The enhanced form of the tag, containing pair ID and the unique code.
   */
  function pair_tag($name, $closing, $args, $code, $state) {
    if (!in_array($name, $this->weighted_tags[$state->weight])) {
      return "[$closing$name$args]";
    }
    
    if ($closing) {
      // If this closing tag does not match an open tag, drop it.
      if (empty($state->open[$name])) {
        return "[/$name]";
      }

      // Read last stack entry and delete it.
      $last = array_pop($state->open[$name]);
      $state->pairs[$last] = $name;
      return "[/$name-$last-$code]";
    }
    else {
      // Stack the open tag.
      $state->open[$name][] = $state->pair_id;
      // Prepair to return it.
      $return = "[$name$args-$state->pair_id-$code]"; 
      
      // If the tag is selfclosing, automatically generate the closing tag.
      if ($this->tags[$name]->info->selfclosing) {
        $return .= "[/$name-$state->pair_id-$code]";
        $state->pairs[$state->pair_id] = $name; 
      }
      $state->pair_id++;
      return $return;
    }
  }
  
  /**
   * Render the paired-up tags. 
   * @param $text
   *   The text containing the tags.
   * @param $pairs
   *   An array of pairs contained in the text.
   * @param $code
   *   The uniquely identifying code to watch for. 
   * @return
   *   The rendered HTML code.
   */
  function render_tags($text, $pairs, $code) {
    foreach($pairs as $id => $name) {
      $arg_delim = $this->tags[$name]->info->multiarg ? '[= ]' : '=';
      $pattern = "/\[$name($arg_delim([^\]]*))?-$id-$code\](.*)\[\/$name-$id-$code\]/ims";
      
      // Multi-argument and dynamic tags must be handled by evaluation.
      if ($this->tags[$name]->info->dynamic || $this->tags[$name]->info->multiarg) {
        $text = preg_replace("{$pattern}e", '$this->render_tag("' . $name . '", "$1", "$3")', $text);
      }  
      else {
        $replace = str_replace(array('{option}', '{content}'), array('$2','$3'), $this->tags[$name]->info->replacewith);
        $text = preg_replace($pattern, $replace, $text);
      }
    }

    // Clean the dangling tags and return.
    return _xbbcode_revert_tags($text, $code);
  }

  /**
   * Render a single tag, based on its arguments and content.
   * @param $name
   *   Name of the tag.
   * @param $args
   *   Arguments passed to the tag.
   * @param $content
   *   Content nested in the tag (containing potential other pairs, which must
   *   not be touched in any other way than _xbbcode_revert_tags().
   * @return
   *   HTML code to insert in place of the tag and its content.
   */
  function render_tag($name, $args, $content) {
    $args = _xbbcode_parse_args($args);
    
    if ($this->tags[$name]->info->dynamic) {
      $tag = (object) array(
        'name'    => $name,
        'content' => $content,
        'option'  => isset($args['option']) ? $args['option'] : NULL,
        'args'    => $args,
      );
      return module_invoke($this->tags[$name]->module, 'xbbcode', 'render', $name, $tag);
    }
    else {
      // This branch is entered for non-dynamic multi-argument tags.
      // The arguments must be passed dynamically, but the tag is rendered statically.
      $replace = array('{content}' => $content);
      foreach ($args as $name => $value) {
        $replace['{'.$name.'}'] = $value;
      }

      $code = str_replace(
        array_keys($replace),
        array_values($replace),
        $this->tags[$name]->info->replacewith
      );
      
      // Make sure that unset placeholders are replaced with empty strings.
      $code = preg_replace('/{[a-z]+}/', '', $code);

      return $code;
    }
  }

  /**
   * Close any remaining open tags, putting the closing tags at the end of the text.
   * @param $state
   *   State to track the open and closed pairs.
   * @param $code
   *   Code to insert with every closing tag.
   * @return
   *   Suffix to append to the text before filtering.
   */
  function closure($state, $code) {
    $suffix = array();
    foreach ($state->open as $name => $open) {
      foreach ($open as $pair_id) {
        $suffix[$pair_id] .= "[/$tagname-$pair_id-]";
	      $state->pairs[$pair_id] = $tagname;
      }
    }
    ksort($suffix);
    return implode("", $suffix);
  }
}



/* change 2007-02-09 9:11:
 * tags are now paired in the order they appear in, not in their order in the array.
 * this will later be adjusted to allow weighting.
 */

/*
 * change 2009-12-10 5:48:
 * Monstrous cleanup.
 */

