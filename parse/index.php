<?php

// generic jimdo site parser
// work in progress

header('Content-Type: application/json');

$cache = @json_decode(file_get_contents('pcache.json'));
if ($cache && time() - $cache[0] < 600){
  echo json_encode($cache[1]);
  die();
}

$home = trim(file_get_contents('pconfig.txt'));
$homehtml = file_get_contents($home);

function parseToc($html){
  $res = array();

  $nav = findTag($html, 'cc-nav-level-0');
  foreach (findTags($nav, 'a href') as $i){
    $res[] = array( getTagAttribute($i, 'href'), getTagBody($i) );
  }

  return $res;
}

function parsePage($home, $relative_url, $title){
  $res = array(
    'absoluteUrl' => $home . $relative_url,
    'relativeUrl' => $relative_url,
    'siteTitle' => null,
    'title' => trim($title),
    'logo' => null,
    'content' => array(),
  );

  $html = file_get_contents($home . $relative_url);

  $title_area = findTag($html, 'cc-website-title');
  $h1 = findTag($title_area, 'h1');
  $res['siteTitle'] = trim(getTagBody($h1));

  $logo_area = findTag($html, 'cc-website-logo');
  $img = findTag($logo_area, '<img');
  $res['logo'] = getTagAttribute($img, 'src');

  $content_area = findTag($html, 'content_area');
  $ccm = findTag($content_area, 'cc-matrix-');
  foreach (findTags($ccm, 'cc-m-') as $i)
    $res['content'][] = parseContentItem($i);

  return $res;
}

function parseContentItem($item){
  // gallery
  if ($el = findTag($item, 'cc-m-gallery')){
    $pics = array();
    foreach (findTags($el, 'thumb') as $i){
      $atag = findTag($i, '<a ');
      $imgtag = findTag($atag, '<img ');
      $pics[] = array(
        'thumb' => getTagAttribute($imgtag, 'src'),
        'src' => getTagAttribute($atag, 'data-href'),
      );
    }
    return array(
      'type' => 'gallery',
      'pics' => $pics,
    );
  }
  // columns
  if ($el = findTag($item, 'j-hgrid')){
    $columns = array();
    foreach (findTags($el, 'cc-m-hgrid-column') as $i)
      $columns[] = parseContentItem($i);
    return array(
      'type' => 'columns',
      'columns' => $columns,
    );
  }
  // image item
  if ($img = findTag($item, '<img')){
    return array(
      'type' => 'image',
      'src' => getTagAttribute($img, 'src')
    );
  }
  // header item
  if ($el = findTag($item, 'cc-m-header')){
    return array(
      'type' => 'header',
      'text' => getTagBody($el),
    );
  }
  // hr
  if ($el = findTag($item, '<hr')){
    return array(
      'type' => 'hr',
    );
  }
  // default -> html
  return array(
    'type' => 'html',
    'html' => $item
  );
}

// find first tag that matches $match, return html string
function findTags($html, $match){
  $l = strlen($html);
  $intag = false;
  $start = null;
  $closer = null;
  $found = false;
  $depth = null;

  for ($i = 0; $i < $l; $i++){
    if ($html[$i] == '<' && $html[$i+1] != '!' ){
      $intag = true;
      $start = $i;
      $closer = ($html[$i + 1] == '/');
    }

    if ($intag && $html[$i] == '>'){
      $intag = false;
      // <br /> -- see if it matches, but do not set $found
      if ($html[$i - 1] == '/' && $found === false){
        $tag = substr($html, $start, $i - $start + 1);
        if (strpos($tag, $match) !== false)
          yield $tag;
      // <br /> after found -- do nothing
      } else if ($html[$i - 1] == '/')
        ;
      // see if tag is a match
      else if ($found === false){
        $tag = substr($html, $start, $i - $start + 1);
        if (strpos($tag, $match) !== false){
          $found = $start;
          $depth = 1;
        }
      // find closing tag for found tag
      } else {
        if ($closer)
          $depth--;
        else
          $depth++;
        if (!$depth){
          yield substr($html, $found, $i - $found + 1);
          $found = false;
        }
      }
    }
  }
}

function findTag($html, $match){
  foreach (findTags($html, $match) as $i)
    return $i;
}

function getTagAttribute($tag, $attr){
  $a = strpos($tag, $attr . '=') + strlen($attr) + 1;
  if ($tag[$a] == '"'){
    $b = strpos($tag, '"', $a + 1);
    return substr($tag, $a + 1, $b - $a - 1);
  }
  return 'not_good';
}

function getTagBody($tag){
  $a = strpos($tag, '>');
  $b = strrpos($tag, '<');
  return substr($tag, $a + 1, $b - $a - 1);
}

$toc = parseToc($homehtml);

$res = array();
foreach ($toc as $i)
  $res[] = parsePage($home, $i[0], $i[1]);

echo json_encode($res);
file_put_contents('pcache.json', json_encode(array(time(), $res)));

