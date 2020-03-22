<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Content-Type: application/json; charset=UTF-8");

class Database{
    private $db_host = 'localhost';
    private $db_name = 'php_api';
    private $db_username = 'root';
    private $db_password = '';
    
    public function dbConnection(){
        try{
            $conn = new PDO('mysql:host='.$this->db_host.';dbname='.$this->db_name,$this->db_username,$this->db_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        }
        catch(PDOException $e){
            echo "Connection error ".$e->getMessage(); 
            exit;
        }
    }
}

$db_connection = new Database();
$conn = $db_connection->dbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

// SET HEADER
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");

// CHECK GET ID PARAMETER OR NOT
if(isset($_GET['id']))
{
    //IF HAS ID PARAMETER
    $post_id = filter_var($_GET['id'], FILTER_VALIDATE_INT,[
        'options' => [
            'default' => 'all_posts',
            'min_range' => 1
        ]
    ]);
}
else{
    $post_id = 'all_posts';
}

// MAKE SQL QUERY
// IF GET POSTS ID, THEN SHOW POSTS BY ID OTHERWISE SHOW ALL POSTS
$sql = is_numeric($post_id) ? "SELECT * FROM `posts` WHERE id='$post_id'" : "SELECT * FROM `posts`";

$stmt = $conn->prepare($sql);

$stmt->execute();

//CHECK WHETHER THERE IS ANY POST IN OUR DATABASE
if($stmt->rowCount() > 0){
    // CREATE POSTS ARRAY
    $posts_array = [];

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        $post_data = [
            'id' => $row['id'],
            'title' => $row['title'],
            'body' => html_entity_decode($row['body']),
            'author' => $row['author']
        ];
        // PUSH POST DATA IN OUR $posts_array ARRAY
        array_push($posts_array, $post_data);
    }
    //SHOW POST/POSTS IN JSON FORMAT
    echo json_encode($posts_array);


}
else{
    //IF THER IS NO POST IN OUR DATABASE
    echo json_encode(['message'=>'No post found']);
}
}else{

  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

  // GET DATA FORM REQUEST
  $data = json_decode(file_get_contents("php://input"));

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // SET HEADER
  header("Access-Control-Allow-Methods: POST");

  //CREATE MESSAGE ARRAY AND SET EMPTY
  $msg['message'] = '';

  // CHECK IF RECEIVED DATA FROM THE REQUEST
  if(isset($data->title) && isset($data->body) && isset($data->author)){
      // CHECK DATA VALUE IS EMPTY OR NOT
      if(!empty($data->title) && !empty($data->body) && !empty($data->title)){

          $insert_query = "INSERT INTO `posts`(title,body,author) VALUES(:title,:body,:author)";

          $insert_stmt = $conn->prepare($insert_query);
          // DATA BINDING
          $insert_stmt->bindValue(':title', htmlspecialchars(strip_tags($data->title)),PDO::PARAM_STR);
          $insert_stmt->bindValue(':body', htmlspecialchars(strip_tags($data->body)),PDO::PARAM_STR);
          $insert_stmt->bindValue(':author', htmlspecialchars(strip_tags($data->author)),PDO::PARAM_STR);

          if($insert_stmt->execute()){
              $msg['message'] = 'Data is Inserted Successfully';
          }else{
              $msg['message'] = 'Data is not Inserted';
          }

      }else{
          $msg['message'] = 'Oops! empty field detected. Please fill all the fields';
      }
  }
  else{
      $msg['message'] = 'Please fill all the fields | title, body, author';
  }


}else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

  // SET HEADER
  header("Access-Control-Allow-Methods: DELETE");

  //CHECKING, IF ID AVAILABLE ON $data
  if(isset($data->id)){
      $msg['message'] = '';

      $post_id = $data->id;

      //GET POST BY ID FROM DATABASE
      // YOU CAN REMOVE THIS QUERY AND PERFORM ONLY DELETE QUERY
      $check_post = "SELECT * FROM `posts` WHERE id=:post_id";
      $check_post_stmt = $conn->prepare($check_post);
      $check_post_stmt->bindValue(':post_id', $post_id,PDO::PARAM_INT);
      $check_post_stmt->execute();

      //CHECK WHETHER THERE IS ANY POST IN OUR DATABASE
      if($check_post_stmt->rowCount() > 0){

          //DELETE POST BY ID FROM DATABASE
          $delete_post = "DELETE FROM `posts` WHERE id=:post_id";
          $delete_post_stmt = $conn->prepare($delete_post);
          $delete_post_stmt->bindValue(':post_id', $post_id,PDO::PARAM_INT);

          if($delete_post_stmt->execute()){
              $msg['message'] = 'Post is Deleted Successfully';
          }else{
              $msg['message'] = 'Post is Not Deleted';
          }

      }else{
          $msg['message'] = 'Invalid ID';
      }

  }

}else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

  // SET HEADER
  header("Access-Control-Allow-Methods: PUT");

  //CHECKING, IF ID AVAILABLE ON $data
  if(isset($data->id)){

      $msg['message'] = '';
      $post_id = $data->id;

      //GET POST BY ID FROM DATABASE
      $get_post = "SELECT * FROM `posts` WHERE id=:post_id";
      $get_stmt = $conn->prepare($get_post);
      $get_stmt->bindValue(':post_id', $post_id,PDO::PARAM_INT);
      $get_stmt->execute();


      //CHECK WHETHER THERE IS ANY POST IN OUR DATABASE
      if($get_stmt->rowCount() > 0){

          // FETCH POST FROM DATBASE
          $row = $get_stmt->fetch(PDO::FETCH_ASSOC);

          // CHECK, IF NEW UPDATE REQUEST DATA IS AVAILABLE THEN SET IT OTHERWISE SET OLD DATA
          $post_title = isset($data->title) ? $data->title : $row['title'];
          $post_body = isset($data->body) ? $data->body : $row['body'];
          $post_author = isset($data->author) ? $data->author : $row['author'];

          $update_query = "UPDATE `posts` SET title = :title, body = :body, author = :author
          WHERE id = :id";

          $update_stmt = $conn->prepare($update_query);

          // DATA BINDING AND REMOVE SPECIAL CHARS AND REMOVE TAGS
          $update_stmt->bindValue(':title', htmlspecialchars(strip_tags($post_title)),PDO::PARAM_STR);
          $update_stmt->bindValue(':body', htmlspecialchars(strip_tags($post_body)),PDO::PARAM_STR);
          $update_stmt->bindValue(':author', htmlspecialchars(strip_tags($post_author)),PDO::PARAM_STR);
          $update_stmt->bindValue(':id', $post_id,PDO::PARAM_INT);


          if($update_stmt->execute()){
              $msg['message'] = 'Data updated successfully';
          }else{
              $msg['message'] = 'Data is not updated';
          }

      }
      else{
          $msg['message'] = 'Invalid ID';
      }

  }

}
    echo  json_encode($msg);
}



?>
