<?php
if(!isset($_REQUEST['id'])){
    header('location:/pingtai');
}
?>
<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <title>跳转中...</title>
 </head>
 <body>
  跳转中...
  <script>
  setTimeout(function(){
       location.href = 'https://www.cxyun.com/api/wechat/platformauth/<?php echo $_REQUEST['id'];?>';
  },500);
  </script>
 </body>
</html>
