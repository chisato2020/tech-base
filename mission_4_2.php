<?php
/*
mission4:ミッション２で作成した、削除・編集機能を持ち、投稿ごとにパスワードロックのある掲示板をテキストファイル保存ではなく、MySQLのテーブルに連携させよう。
作成したものは、チームメンバーに実際に使ってみてもらうこと。
OKならGitHubへコードをアップロードしておこう。
*/
?>

<?php

header('Content-Type: text/html; charset=UTF-8');

$name_data=$_POST["name_input"];//名前入力データ
$comment_data=$_POST["comment_input"];//コメント入力データ
$input_password=$_POST["password_input"];//パスワード入力データ
$edit_id_hidden=$_POST["edit_id_hidden"];//編集モード時、投稿番号取得

$delete_number=$_POST["delete_number"];//削除対象番号
$delete_password=$_POST["password_delete"];//削除用パスワード
$edit_number=$_POST["edit_number"];//編集対象番号
$edit_password=$_POST["password_edit"];//編集用パスワード

$dsn="データベース名";
$user="ユーザー名";
$dbpassword="パスワード";
$dbname="tbtest_B";

$edit_flag=0;
$last_id_num=0;//投稿番号

try{
	//PDOオブジェクトの作成
	$pdo=new PDO($dsn,$user,$dbpassword);	
	
}
catch(PDOException $e){//例外が発生した場合に行う処理
	echo "データベースエラー（PDOエラー）";
	//var_dump($e->getMessage());//エラー詳細
}

$sql="SHOW TABLES FROM データベース名 LIKE 'tbtest_B'";
$result=$pdo->query($sql);
//テーブルの確認＆なければ作成
if(!$result){
	//テーブル作成
	$sql="CREATE TABLE IF NOT EXISTS 'tbtest_B'"
	."("
	."id INT ,"
	."name char(32),"
	."comment TEXT,"
	."password varchar(255)"
	.");";
	$stmt=$pdo->prepare($sql);
	$stmt->execute();
}
else{
	//テーブル確認
	//foreach($result as $row){
	//	print_r($row);
	//}
}

//編集対象idのレコードを取得
if($_POST["edit_send"]){
	if(!empty($edit_number)&&!empty($edit_password)){
		if(preg_match("/^[0-9]+$/",$edit_number)){
			//編集対象番号のレコード取得
			$result=record_get_id($edit_number);
			
			if(!result){
				echo "DB Error,could not list tables\n";
				echo "MySQL Error:".mysql_error();
				exit;
			}
			else{
				//パスワードのハッシュ化
				$edit_hash=pass_hash($edit_password);
				
				foreach($result as $row){
					if($edit_hash==$row["password"]){
						//編集対象のid,名前、コメントデータを取得
						$edit_id=$row["id"];
						$edit_name=$row["name"];
						$edit_comment=$row["comment"];
						$edit_flag=1;
					}
				}
			}
		}
	}
}

//編集モードで入力時、編集フラグを立てる
if(!empty($edit_id_hidden)){
	$edit_flag=2;
}

//入力保存機能
if($_POST["input_send"]&&($edit_flag==0)){
	if(!empty($name_data)&&!empty($comment_data)&&!empty($input_password)){
	//投稿番号(idの最大値を取得)
	$sql="select MAX(id) as max from tbtest_B";
	$result=$pdo->query($sql);
	$row=array();
	if(!result){
		$last_id_num=1;
	}
	else{
		while($row=$result->fetch(PDO::FETCH_ASSOC)){
			$last_id_num=$row["max"]+1;//最後の投稿番号+１
		}
	}
	
	//パスワードのハッシュ化
	$input_hash=pass_hash($input_password);
	//データベースへ保存
	insert_new($last_id_num,$name_data,$comment_data,$input_hash);
		
	}//名前、コメント、パスワード入力確認
}//input_send

//編集対象idレコードを編集
if($_POST["input_send"]&&($edit_flag==2)){
	if(!empty($name_data)&&!empty($comment_data)&&!empty($input_password)){
		//パスワードのハッシュ化
		$edit_hash=pass_hash($input_password);
		
		//編集実行
		edit_id($edit_id_hidden,$name_data,$comment_data,$edit_hash);
		$edit_flag=0;
	}
}

//削除対象idのレコードを削除
if($_POST["delete_send"]){
	if(!empty($delete_number)&&!empty($delete_password)){
		if(preg_match("/^[0-9]+$/",$delete_number)){
			
			//削除対象のレコード取得
			$result=record_get_id($delete_number);
			
			if(!$result){
				echo "DB Error, could not list tables\n";
 				echo 'MySQL Error: ' . mysql_error();
 		 		exit;
			}
			else{
				//パスワードのハッシュ化
				$delete_hash=pass_hash($delete_password);
				
				foreach($result as $row){
					//削除対象のパスワード判定
					if($row["password"]==$delete_hash){
						//削除実行
						delete_id($delete_number);
					}
				}
			}
		}
	}
}



/*
//テーブル内全データ削除
if($_POST["DELETE_ALL"]){
	$sql="TRUNCATE TABLE tbtest_B";
	$pdo->query($sql);
}
*/
/*
//テーブルにカラムを指定したカラムの後に追加
	$sql="ALTER TABLE tbtest_B ADD date char(255) AFTER comment";
	$pdo->query($sql);
*/

/*
//テーブルのカラムの確認
$sql="SHOW COLUMNS FROM tbtest_B";
$result=$pdo->query($sql);
foreach($result as $row){
	print_r($row);
}
*/
?>



<html>
<head>
<title>ミッション４</title>

<!--文字コード-->
<meta http-equiv="content-type" charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<h1>コメント入力フォーム</h1>

<div class="box">
<div class="frame1">
<ul>
<form action="mission_4_2.php" method="post">
	<!--INPUT & EDIT_INPUT-->
	<li class="form">
	<p>コメントを投稿する際には、名前、コメント、パスワードを入力して投稿してください。</p>
	<p>名前：<br><input type="text" value="<?php if($edit_flag==1){echo $edit_name;}?>" placeholder="名前" name="name_input"></p>
	<p>コメント：<br><input type="text" value="<?php if($edit_flag==1){echo $edit_comment;}?>" placeholder="コメント" name="comment_input"></p>
	<p>パスワード：<br><input type="password" value="" placeholder="パスワード" name="password_input"></p>
	<p><!--編集対象番号（隠し記憶）--><input type="hidden" value="<?php echo $edit_id;?>"  name="edit_id_hidden"></p>
	<p><input type="submit" value='<?php if($edit_flag==1){echo "編集";}else{echo "送信";}?>' name="input_send"></p>
	<br>
</li>

<li class="form">
	<!--DELETE_NUMBER-->
	<p>削除したい投稿番号を半角数字で入力してください。</p>
	<p>削除対象番号：<br><input type="text" value="" placeholder="削除対象番号" name="delete_number"></p>
	<p>パスワード：<br><input type="password" value="" placeholder="パスワード" name="password_delete"></p>
	<p><input type="submit" value="送信" name="delete_send"?></p>
	<br>
</li>

<li class="form">
	<!--EDIT_NUMBER-->
	<p>編集したい投稿番号を半角数字で入力してください。</p>
	<p>編集対象番号：<br><input type="text" value="" placeholder="編集対象番号" name="edit_number"></p>
	<p>パスワード：<br><input type="password" value="" placeholder="パスワード" name="password_edit"></p>
	<p><input type="submit" value="送信" name="edit_send"></p>
	<br>
</li>
<?/*
<li class="form">
<!--DLETE_ALL-->
<p>テーブル内のすべてのデータを削除します。</p>
<p><input type="submit" value="送信" name="DELETE_ALL"></p>
<br>
</li>
*/?>
</form>
</ul>
</div>
<div class="frame2">
<?php 
//テーブル内データ表示
display();
?>
</div>
</div>
</body>
</html>

<?php


//入力機能
function insert_new($id,$name,$comment,$password)
{
	global $pdo;
	//日時取得
	date_default_timezone_set("Asia/Tokyo");
	$date=date("Y/m/d G:i:s");
	
	//INSERT
	//$stmt=$pdo->prepare("INSERT INTO tbtest_B(id, name, comment,password,salt) VALUES (?,?,?,?)");error?
	$stmt = $pdo->prepare("INSERT INTO tbtest_B(id, name, comment,date,password) VALUES (:id, :name, :comment,:date,:password)");
	
	$stmt->bindValue(":id",$id,PDO::PARAM_INT);
	$stmt->bindParam(":name",$name,PDO::PARAM_STR);
	$stmt->bindParam(":comment",$comment,PDO::PARAM_STR);
	$stmt->bindParam(":date",$date,PDO::PARAM_STR);
	$stmt->bindParam(":password",$password,PDO::PARAM_STR);
	$stmt->execute();
	//$stmt->execute(array($last_id_num,$name,$comment,$encrypted_passwrod));//error?
}

//パスワードのハッシュ化
function pass_hash($password)
{
	//$passwordからハッシュを生成
	$password=sha1($password);
	
	$// ソルト＆ペッパー
	$salt = "abcdef123456abcdef1234";
	$pepper = "pepperpepperpepperpepp";
	$passwrod=sha1($password.$salt.$pepper);
	//$encrypted_passwrod=passwprd_hash($password,PASSWORD_DEFAULT);
	
	return $password;
}

//特定idのレコードの取得
function record_get_id($id){
	global $pdo;
	$sql="select * from tbtest_B where id = :id";
	$stmt=$pdo->prepare($sql);
	$stmt->bindValue(":id",$id,PDO::PARAM_INT);
	$result=$stmt->execute();
	if(!$result){
		$stmt=false;
	}
	return $stmt;
}

//特定idのレコードを削除
function delete_id($id)
{
	global $pdo;
	$sql="DELETE FROM tbtest_B WHERE id = :id";
	$stmt=$pdo->prepare($sql);
	$stmt->bindValue(":id",$id,PDO::PARAM_INT);
	$stmt->execute();
}

//特定idのレコードを編集
function edit_id($id,$name,$comment,$password)
{
	global $pdo;
	//日時取得
	date_default_timezone_set("Asia/Tokyo");
	$date=date("Y/m/d G/i/s");
	
	$sql="UPDATE tbtest_B set name = :name, comment = :comment, date = :date, password = :password where id = :id";
	$stmt=$pdo->prepare($sql);
	$stmt->bindValue(":id",$id,PDO::PARAM_INT);
	$stmt->bindParam(":name",$name,PDO::PARAM_STR);
	$stmt->bindParam(":comment",$comment,PDO::PARAM_STR);
	$stmt->bindParam("date",$date,PDO::PARAM_STR);
	$stmt->bindParam(":password",$password,PDO::PARAM_STR);
	$stmt->execute();
	
}

//テーブル内のデータ表示
function display()
{
	global $pdo;
	//idカラムの値を昇順に並べ替えてからデータ表示
	$sql="select * from tbtest_B order by id";
	$result=$pdo->query($sql);
	if(!$result){
		echo "DB Error, could not list tables\n";
 		echo 'MySQL Error: ' . mysql_error();
 	 	exit;
	}
	else{
		while($row=$result->fetch(PDO::FETCH_ASSOC)){
			echo $row['id'].",";
			echo $row["name"].",";
			echo $row["comment"].",";
			echo $row["date"];
			echo "<br>";
			echo "<hr>";
		}	
	}
}

?>
