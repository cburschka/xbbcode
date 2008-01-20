Here's a brief description of the API hook that XBBCode provides. It will eventually become a proper
documentation page.

To provide custom BBCode tags, your module should implement a function similar to this:

---------

function hook_xbbcode($op,$delta=NULL,$tag=NULL) 
{
  switch($op) 
  {
  case 'list': return array('i','url','wordcount');
  case 'info':
    switch($delta)
    {
      case 'i': return array('replacewith'=>'<em>{content}</em>');
      case 'url': return array('replacewith'=>l('{content}','{option}'));
      case 'wordcount': return array('dynamic'=>'true');
    )
    return array();
  case 'render': 
    switch ($delta) 
    {
      case 'wordcount':
      return $tag-content . "<hr />".str_word_count($tag->content) . " words.";
    }
    return $tag->content;
  }
}
