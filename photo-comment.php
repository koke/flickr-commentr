    <?php
    require_once "RESTclient.php";
    
    $photo_url = $_GET["photo_url"];
    if (preg_match("#^http://(www.)?flickr.com/photos/([^/]+)/([^/]+)/?#", $photo_url, $matches)) {
      $photo_id = $matches[3];

      $params = array(
        'api_key'  => 'f234decfe77159e9ea36eac463cb9524',
      	'method'	=> 'flickr.photos.getInfo',
      	'photo_id'	=> $photo_id,
      	'format'	=> 'php_serial',
      );
    
      $rest = new RESTclient();
      
      $url = "http://api.flickr.com/services/rest/";
      $rest->createRequest("$url","POST",$params);
      $rest->sendRequest();
      $output = $rest->getResponse();
      // echo $output;


      $rsp_obj = unserialize($output);
      
      // echo "<pre>";var_dump($rsp_obj);die();
      // echo "<pre>";var_dump($_COOKIE);die();
      $secret = $rsp_obj["photo"]["secret"];
      $farm   = $rsp_obj["photo"]["farm"];
      $server = $rsp_obj["photo"]["server"];
      
      $photo_info = array(
        "id"      => $photo_id,
        "secret"  => $rsp_obj["photo"]["secret"],
        "farm"    => $rsp_obj["photo"]["farm"],
        "server"  => $rsp_obj["photo"]["server"],
        "owner"   => $rsp_obj["photo"]["owner"]["username"],
        "nsid"    => $rsp_obj["photo"]["owner"]["nsid"],
        "title"   => $rsp_obj["photo"]["title"]["_content"],
      );
      $photo_links = array(
        "s" => "http://farm$farm.static.flickr.com/${server}/${photo_id}_${secret}_s.jpg",
        "t" => "http://farm$farm.static.flickr.com/${server}/${photo_id}_${secret}_t.jpg",
        "m" => "http://farm$farm.static.flickr.com/${server}/${photo_id}_${secret}_m.jpg",
        "n" => "http://farm$farm.static.flickr.com/${server}/${photo_id}_${secret}.jpg",
      );
      // var_dump($photo_links);
      
      // var_dump($photo_info);
      
      if ($_COOKIE["size"]) {
        $default_size = $_COOKIE["size"];
      } else {
        $default_size = "m";
      }
      $include_title = ($_COOKIE["title"] == "true");
      $include_name = ($_COOKIE["owner"] == "true");
      
    } else {
      die("Not a flickr photo :(");
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Flickr commentr</title>
    <script src="prototype.js" type="text/javascript"></script>  
    <script type="text/javascript" charset="utf-8">
      var photo_links = {
        <?php
          foreach ($photo_links as $key => $value) {
            echo "$key: '".addslashes($value)."',\n";
          }
        ?>
      }
      var photo_info = {
        <?php
          foreach ($photo_info as $key => $value) {
            echo "$key: '".addslashes($value)."',\n";
          }
        ?>
      }
      
      var copy_text = '<a href="<?php echo $photo_url ?>"><img src="<?php echo $photo_links["m"] ?>" /></a>';
      var current_size = '<?php echo $default_size; ?>';
      
      function setCookie(c_name, value, expiredays) {
      	var exdate = new Date();
      	exdate.setDate(exdate.getDate() + expiredays);
      	document.cookie = c_name + "=" + escape(value) + ((expiredays == null) ? "" : ";expires=" + exdate.toGMTString());
      }
      
      function getCookie(c_name) {
      	if (document.cookie.length > 0) {
      		c_start = document.cookie.indexOf(c_name + "=");
      		if (c_start != -1) {
      			c_start = c_start + c_name.length + 1;
      			c_end = document.cookie.indexOf(";", c_start);
      			if (c_end == -1)
      				c_end = document.cookie.length;
      			return unescape(document.cookie.substring(c_start, c_end));
      		}
      	}
      	return "";
    	}
      
      function updatePic (size) {
        current_size = size;
        copy_text = '';
        if ($('include_title').checked) {
          copy_text += '<strong>' + photo_info["title"] + '</strong>\n<br />';
        }
        
        copy_text += '<a href="<?php echo $photo_url ?>"><img src="' + photo_links[size] + '" /></a>';
        
        if ($('include_name').checked) {
          copy_text += '\n<br />by <a href="http://www.flickr.com/photos/' + photo_info['nsid'] +'">' + photo_info['owner'] +'</a>';
        }
        $('copy').value = copy_text;
        $('image').innerHTML = copy_text;
        $('copy').focus();
        $('copy').select();
        setCookie('size', size, 365);
        setCookie('title', $('include_title').checked, 365);
        setCookie('owner', $('include_name').checked, 365);
        // $('image_pic').src = photo_links[size];
        // alert(photo_links[size]);
      }
      
      function loadDefaults() {
        $('include_name').checked = getCookie('owner');
        $('include_title').checked = getCookie('title');
        var size = getCookie("size");
        if (null == size) {
          updatePic("m");
        } else {
          var options = $A($('selector').getElementsByTagName('input'));
          options.each(function(opt) {
            if (opt.value == size) {
              opt.checked = true;
            }
          });
          updatePic(size);
        }
      }
      // window.onload = loadDefaults;
    </script>
    <link rel="stylesheet" href="flickr.css" type="text/css" media="screen" charset="utf-8" />
  </head>
  <body>
    <h1>flick<span class="pink">r</span> comment<span class="pink">r</span></h1>
    <p>Use this tool to paste a picture from flickr</p>
    <form action="photo-comment_submit" method="get" accept-charset="utf-8">
      <textarea name="copy" rows="3" cols="40" id="copy">
<a href="<?php echo $photo_url ?>"><img src="<?php echo $photo_links[$default_size] ?>" /></a>
      </textarea>
      <div id="options">
        <label><input type="checkbox" name="include_name" value="1" id="include_name" onClick="updatePic(current_size);" <?php if($include_name) {echo 'checked="checked"';} ?>>Include author</label>
        <label><input type="checkbox" name="include_title" value="1" id="include_title" onClick="updatePic(current_size);" <?php if($include_title) {echo 'checked="checked"';} ?>>Include title</label>
      </div>
    <div id="selector">
      <label><input type="radio" name="size" value="s" onClick="updatePic('s');" <?php if ("s" == $default_size) {echo "checked=\"checked\"";}?>/> Thumbnail</label>
      <label><input type="radio" name="size" value="t" onClick="updatePic('t');" <?php if ("t" == $default_size) {echo "checked=\"checked\"";}?>/> Tiny</label>
      <label><input type="radio" name="size" value="m" onClick="updatePic('m');" <?php if ("m" == $default_size) {echo "checked=\"checked\"";}?> /> Small</label>
      <label><input type="radio" name="size" value="n" onClick="updatePic('n');" <?php if ("n" == $default_size) {echo "checked=\"checked\"";}?>/> Medium</label>
    </div>
    </form>
    <div id="image">
      <?php 
      if ($include_title) { 
        echo "<strong>".$photo_info["title"]."</strong>\n<br />";
      } 
      ?>
<a href="<?php echo $photo_url ?>"><img src="<?php echo $photo_links[$default_size] ?>" /></a>
      <?php 
      if ($include_name) { 
        echo "\n<br />by <a href=\"http://www.flickr.com/photos/".$photo_info['nsid']."\">".$photo_info['owner']."</a>";
      } 
      ?>
    </div>
  </body>
</html>
