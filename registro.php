<?php
require_once 'conexion.php';
require_once 'jwt.php';

/********BLOQUE DE ACCESO DE SEGURIDAD */
$headers = apache_request_headers();
$tmp = $headers['Authorization'];
$jwt = str_replace("Bearer ", "", $tmp);
if(JWT::verify($jwt, Config::SECRET) > 0){
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

$user = JWT::get_data($jwt, Config::SECRET)['user'];
/*** BLOQUE WEB SERVICE REST */
$metodo = $_SERVER["REQUEST_METHOD"];
switch($metodo){
    case 'GET':
            $c = conexion();
            if(isset($_GET['id'])){
                $s = $c->prepare("SELECT * FROM registro WHERE id = :id");
                $s->bindValue(":id", $_GET['id']);
            }else{
                $s = $c->prepare("SELECT * FROM registro");
            }
            $s->execute();
            $s->setFetchMode(PDO::FETCH_ASSOC);
            $r = $s->fetchAll();
            header("http/1.1 200 ok");
            echo json_encode($r);
        break;
    case 'POST':
        if(isset($_POST['sensor']) && isset($_POST['valor'])){
            $c = conexion();
            $s = $c->prepare("INSERT INTO registro (user, sensor, valor, fecha) VALUES (:u, :s, :v, NOW())");
            $s->bindValue(":u", $user);
            $s->bindValue(":s", $_POST['sensor']);
            $s->bindValue(":v", $_POST['valor']);
            $s->execute();
            if($s->rowCount()>0){
                header("http/1.1 201 created");
                echo json_encode(array("add" => "y", "id" => $c->lastInsertId()));
            }else{
                header("http/1.1 400 bad request");
                echo json_encode(array("add" => "n"));
            }
        }else{
            header("HTTP/1.1 400 Bad Request");
            echo "Faltan datos";
        }
        break;
    case 'PUT':
        break;
    case 'DELETE':
        break;
    default:
        header("HTTP/1.1 405 Method Not Allowed");
}