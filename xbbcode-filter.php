<?php

class XBBCodeFilter {
  var $tags;
  var $weighted_tags;
  
  /*****************************************
   * Constructor. This will make a filter object 
   * from a bundle of tags. 
   *****************************************/
  
  function XBBCodeFilter($tags, $format = -1) 
  {
    $this->tags = $tags;
    $this->format = $format;
    $this->weighted_tags = array();

    foreach ($this->tags as $key => $tag)
    {
      $this->weighted_tags[$tag['weight']][] = $key;
    }
  }
  
  /******************************************
   * main filter function. 
   * This will filter the text and return it.
   ******************************************/
  
  function process($text) 
  {
    /* this function adds forms like these [tag-n-] and later removes them.
     * to avoid unexpected side-effects, if such forms exist already, 
     * we must first hide them, then restore them after removal. 
     */
    $otc = _xbbcode_one_time_code($text); // generate a code that does not occur in the text.
    $text = preg_replace('/\[([^\]]+-[0-9]+-)\]/i', '[$1'. $otc .']', $text); // mask existing forms
    list($text, $pairs) = $this->pair_tags($text);   // pair up the tags
    if ($pairs) ksort($pairs); // sort by key.
    $text = $this->filter_tags($text, $pairs);     // filter the tags we found
    $text = preg_replace('/\[([^\]]+-[0-9]+-)'. $otc .'\]/i', '[$1]', $text); // restore any masked stuff
    return $text;
  }
  
  /**********************************************************************
   * Pairing engine.
   * This function pairs up the nested tags in the text by giving them unique ids in this form:
   * [tag] [tag] [/tag] [/tag] becomes [tag-0-] [tag-1-] [/tag-1-] [/tag-0-]
   ***********************************************************************/
  function pair_tags($text) {
    $this->pair_id=0;
    $this->tagpairs=array();
    $pattern='/\[(\/)?([a-z0-9]+)([= ][^\[\]]*[^\-])?\]/ie';
    $replace='$this->pair_tag(\'$2\',\'$1\',\'$3\');';
    foreach (array_keys($this->weighted_tags) as $weight)
    {
      $this->current_weight = $weight; // tell the pairing function which tags to pair up in this round.
      $text=preg_replace($pattern, $replace, $text); // invoke the pairing function
    }
    if (variable_get('xbbcode_filter_'. $this->format .'_autoclose', false)) {
      $text .= $this->closure(element_children($this->tagpairs));
    }
    $pairs = $this->tagpairs['#complete']; // get the completed pairs
    return array($text,$pairs); // return the text and the pairs.
  }  
  
  /*******************************************************
   * Called from the Regex replacer.
   * Gives each [tag] a pair id: [tag-#-]
   * Stacks up open tags and gives nested tags the proper pair numbers.
   * Automatically adds closers to selfclosing tags.
   * Lists completed pairs in $tagpairs['#complete']. Needed to determine static/dynamic status.
   *******************************************************/
  function pair_tag($tagname,$isclosing,$args) {
    if (!in_array($tagname,$this->weighted_tags[$this->current_weight])) {
      return "[". ($isclosing?'/':'' ) . "$tagname$args]"; // don't pair unregistered tags
    }
    if ($isclosing) {
      if (!$this->tagpairs[$tagname]) return "[/$tagname]"; // never opened? reject the closing tag.
      $last=array_pop($this->tagpairs[$tagname]);  // read last stack entry and delete it.
      $this->tagpairs['#complete'][$last] = $tagname;
      return "[/$tagname-$last-]";
    } else {
      $this->tagpairs[$tagname][]=$this->pair_id;  // add it to the stack
      $return="[$tagname$args-$this->pair_id-]"; // return the transformed opener.
      if ($this->tags[$tagname]['selfclosing']) { // if it's selfclosing...
        $return .= "[/$tagname-$this->pair_id-]"; // also add a closing tag.
        $this->tagpairs['#complete'][$this->pair_id] = $tagname; // and add it to the finished stack.
      }
      $this->pair_id++;
      return $return;
    }
  }
  
  /************************************************************
   * Second part of the filtering process. Tags are now paired up; render them.
   * Unclosed tags are ignored, but still have a dangling pair id that must be removed afterward.
   ************************************************************/
  function filter_tags($text,$pairs) {
    if ($pairs) foreach($pairs as $id=>$name) { // for all pairs...
      $pattern='/\['.$name.'(=([^\]]*))?-'.$id.'-\](.*)\[\/'.$name.'-'.$id.'-\]/ims';
      if ($this->tags[$name]['multiarg']) 
      {  // set the multi-arg pattern
        $pattern=str_replace("=","[= ]",$pattern);
      }
      if ($this->tags[$name]['dynamic'] || $this->tags[$name]['multiarg']) 
      {
        // multi-arg and dynamics are handled by an eval, and this is it.
        $pattern.='e';
        $replace='$this->generate_tag("'.$name.'",\'$1\',\'$3\')';
      }  
      else {
        /* we could get rid of the pairs array entirely if we didn't need the dynamic/static
         * state. But since most tags are static, making the distinction saves performance. */
        $replace=str_replace(array('{content}','{option}'),array('$3','$2'),$this->tags[$name]['replacewith']);
      }
      //var_dump($pattern,$replace);
      $text=preg_replace($pattern,$replace,$text);
    }
    /* now clean up the dangling opening tags */
    $text=preg_replace('/\[([^\]]+)-[0-9]+-\]/i','[$1]',$text);
    return $text;
  }
  
  function generate_tag($tagname,$args,$content) {
    //var_dump(func_get_args());
    $content=stripslashes($content);
    $args=stripslashes($args);
    $args=_xbbcode_parse_args($args);
    if (!is_array($args)) $option=$args;
    if (!$this->tags[$tagname]['dynamic']) {
      $replace=array('{content}'=>$content);
      if ($option) {
        $replace['{option}']=$option;
      }
      else foreach ($args as $name=>$value) {
        $name="args_$name";
        $$name=$value;
        $replace['{'.$name.'}']=$value;
      }
      $code=str_replace(array_keys($replace),array_values($replace),$this->tags[$tagname]['replacewith']);      
      return $code;
    }
    /* we now know it is dynamic, evaluate it. */
    $tag=new stdClass();
    $tag->name=$tagname;
    $tag->content=$content;
    $tag->option=$option;
    $tag->args=$args;
    return module_invoke($this->tags[$tagname]['module'],'xbbcode','render',$tag->name,$tag);
  }

  function closure() {
    $output = array();
    foreach (element_children($this->tagpairs) as $tagname) {
      foreach ($this->tagpairs[$tagname] as $pair_id) {
        $output[$pair_id] = "[/$tagname-$pair_id-]";
	$this->tagpairs['#complete'][$pair_id] = $tagname;
      }
    }
    ksort($output);
    return implode("",$output);
  }
}



/* change 2007-02-09 9:11:
 * tags are now paired in the order they appear in, not in their order in the array.
 * this will later be adjusted to allow weighting.
 */

?>
