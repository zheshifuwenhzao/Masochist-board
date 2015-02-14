<?php

require_once('../config.php');

require_once('../libs/medoo.php');

$database = new medoo([
  'database_type'  =>    'mysql',
  'database_name'  =>    DB_NAME,
  'server'         =>    DB_HOST,
  'username'       =>    DB_USER,
  'password'       =>    DB_PASSWORD,
  'charset'        =>    'utf8',
  'port'           =>    DB_PORT,

  'option'         =>    [
                            PDO::ATTR_CASE => PDO::CASE_NATURAL
                         ]
]);

if (isset ($_GET['new'])){

  $_POST['upid']  = isset($_POST['upid'])  ? $_POST['upid']  : 0;
  $_POST['title'] = isset($_POST['title']) ? $_POST['title'] : '';

  $retype = explode('.', $_FILES['image']['name']);

  rename($_FILES['image']['name'],
    md5(md5_file($_FILES['image']['name']) . date('Y-m-d H:i:s') . $retype[count($retype) - 1]));

  if ((($_FILES['image']['type'] == 'image/gif')
    || ($_FILES['image']['type'] == 'image/jpeg')
    || ($_FILES['image']['type'] == 'image/pjpeg')
    || ($_FILES['image']['type'] == 'image/png'))
    && ($_FILES['image']['size'] < 50000000)){
    if ($_FILES['image']['error']) {
      echo 'Error: ' . $_FILES['image']['error'] . '<br>';
    }else{
      move_uploaded_file($_FILES['image']['tmp_name'],
        'upload/' . $_FILES['image']['name']);
      }
    }

  if(($_POST['upid'] == 0) && ($_POST['title'] == '')){
    print_r('false');
    exit();
  }

  $result = $database->insert('content',[
    'author'   =>    $_POST['author'],
    'title'    =>    $_POST['title'],
    'content'  =>    htmlspecialchars($_POST['content']),
    'upid'     =>    $_POST['upid'],
    'img'      =>    $_FILES['image']
  ]);

  if (isset($_POST['upid']) && ($_POST['upid'] != 0)){
    $database->update('content',[
      'active_time'    =>    date('Y-m-d H:i:s')
    ],[
      'id'             =>    $_POST['upid']
    ]);
  }

  print_r($result);
  exit();
}

elseif (isset ($_GET['list'])){

  $_GET['page'] = isset($_GET['page']) ? $_GET['page'] : 1;

  $data = $database->select('content',[
        'id',
        'title',
        'author',
        'time'
      ],[
        'ORDER'    =>    ['active_time DESC','time DESC'],
        'upid[=]'  =>    0,
        'LIMIT'    =>    [($_GET['page']-1)*10, $_GET['page']*10]
      ]);

  echo json_encode($data);
  exit();
}

elseif (isset ($_GET['post'])){

  require_once('../libs/parsedown.php');

  $Parsedown = new Parsedown();

  $_GET['page'] = isset($_GET['page']) ? $_GET['page'] : 1;

  $data = $database->select('content',[
      'id',
      'title',
      'content',
      'author',
      'time'
    ],[
      'OR'       =>    [
                     'upid[=]'      =>    $_GET['id'],
                     'AND'          =>    [
                                               'upid[=]'    =>    0,
                                               'id[=]'      =>    $_GET['id']
                                          ]
                ],
      'ORDER'    =>    ['upid','id'],
      'LIMIT'    =>    [($_GET['page']-1)*10, $_GET['page']*10]
    ]);

  $data_length = count($data);
  for ($i = 0; $i < $data_length; $i++){
    $data[$i]['content'] = $Parsedown->text($data[$i]['content']);
  }

  echo json_encode($data);
  exit();
}
