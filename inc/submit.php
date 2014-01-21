<?php session_start();
if (!isset($_SESSION['user_id'])){
	//require_once("oj-header.php");
	echo "<a href='loginpage.php'>Please Login First!</a>";
	//require_once("oj-footer.php");
	exit(0);
}
if($_SESSION['freshman_contest'])
$_POST['cid'] = "1";
else
$_POST['cid'] = "0";


require_once("en.php");
require_once("../include/db_info.inc.php");
require_once("../include/const.inc.php");
$user_id=$_SESSION['user_id'];

if (isset($_POST['cid'])){
	$cid=intval($_POST['cid']);
	$sql="SELECT `problem_id` from `schoolcontest_problem` 
				where contest_id=$cid";
}else{
	$id=intval($_POST['id']);
	$sql="SELECT `problem_id` from `problem` where `problem_id`='$id' and problem_id not in (select distinct problem_id from schoolcontest_problem where `contest_id` IN (
			SELECT `contest_id` FROM `contest` WHERE 
			(`end_time`>NOW() or private=1)and `defunct`='N'
			))";
	if(!isset($_SESSION['administrator']))
		$sql.=" and defunct='N'";
}
//echo $sql;	

$res=mysql_query($sql);
if ($res&&mysql_num_rows($res)<1){
		mysql_free_result($res);
		//require_once('oj-header.php');
		echo "Where do find this link? No such problem.<br>";
		//require_once('oj-footer.php');
		exit(0);
}
mysql_free_result($res);



if (isset($_POST['id'])) {
	$id=intval($_POST['id']);
	
}else if (isset($_POST['cid'])){
	$cid=intval($_POST['cid']);
	// check user if private
	$sql="SELECT `private` FROM `contest` WHERE `contest_id`='$cid' AND `start_time`<=NOW() AND `end_time`>NOW()";
	$result=mysql_query($sql);
	$rows_cnt=mysql_num_rows($result);
	if ($rows_cnt!=1){
		echo "You Can't Submit Now Because Your are not invited by the contest or the contest is not running!!";
		mysql_free_result($result);
		//require_once("oj-footer.php");
		exit(0);
	}else{
		$row=mysql_fetch_array($result);
		$isprivate=intval($row[0]);
		mysql_free_result($result);
		if ($isprivate==1){
			$sql="SELECT count(*) FROM `privilege` WHERE `user_id`='$user_id' AND `rightstr`='c$cid'";
			$result=mysql_query($sql) or die (mysql_error()); 
			$row=mysql_fetch_array($result);
			$ccnt=intval($row[0]);
			mysql_free_result($result);
			if ($ccnt==0){
				require_once("contest-header.php");
				echo "You are not invited!\n";
				//require_once("oj-footer.php");
				exit(0);
			}
		}
	}
	$sql="SELECT `problem_id` FROM `schoolcontest_problem` WHERE `contest_id`='$cid' AND `num`='$pid'";
	$result=mysql_query($sql);
	$rows_cnt=mysql_num_rows($result);
	if ($rows_cnt!=1){
		require_once("contest-header.php");
		echo "<h2>No Such Problem!</h2>";
		//require_once("oj-footer.php");
		mysql_free_result($result);
		exit(0);
	}else{
		$row=mysql_fetch_object($result);
		$id=intval($row->problem_id);
		mysql_free_result($result);
	}
}else{
	echo "<h2>No Such Problem!</h2>";
	exit(0);
}

$language=intval($_POST['language']);
if ($language>9 || $language<0) $language=0;
$language=strval($language);


$source=$_POST['source'];
if(get_magic_quotes_gpc())
	$source=stripslashes($source);
$source=mysql_real_escape_string($source);
//$source=trim($source);
//use append Main code
$append_file="$OJ_DATA/$id/append.$language_ext[$language]";
if(isset($OJ_APPENDCODE)&&$OJ_APPENDCODE&&file_exists($append_file)){
     $source.=mysql_real_escape_string("\n".file_get_contents($append_file));
}
//end of append 


$len=strlen($source);
//echo $source;




setcookie('lastlang',$language,time()+360000);

$ip=$_SERVER['REMOTE_ADDR'];

if ($len<2){
	//require_once("oj-header.php");
	echo "Source Code Too Short!";
	//require_once("oj-footer.php");
	exit(0);
}
if ($len>65536){
	require_once("oj-header.php");
	echo "Source Code Too Long!";
	//require_once("oj-footer.php");
	exit(0);
}

// last submit

$sql="SELECT `in_date` from `solution` where `user_id`='$user_id' and in_date>now()-10 order by `in_date` desc limit 1";
$res=mysql_query($sql);
if (mysql_num_rows($res)==1){
	//$row=mysql_fetch_row($res);
	//$last=strtotime($row[0]);
	//$cur=time();
	//if ($cur-$last<10){
		echo "You should not submit more than twice in 10 seconds.....<br>";
		//require_once('oj-footer.php');
		exit(0);
	//}
}
switch($id)
{
	case 1001: case 1009: case 1017: case 1022: $pid = 0; break;
	case 1002: case 1010: case 1018: case 1023: $pid = 1; break;
	case 1003: case 1011: case 1019: case 1024: $pid = 2; break;
	case 1004: case 1012: case 1020: case 1025: $pid = 3; break;
	case 1005: case 1013: case 1021: case 1026: $pid = 4; break;
	case 1006: case 1014: $pid = 5; break;
	case 1007: case 1015: $pid = 6; break;
	case 1008: case 1016: $pid = 7; break;

}


//if (!isset($pid)){
//$sql="INSERT INTO solution(problem_id,user_id,in_date,language,ip,code_length)
//	VALUES('$id','$user_id',NOW(),'$language','$ip','$len')";
//}else{
$sql="INSERT INTO solution(problem_id,user_id,in_date,language,ip,code_length,contest_id,num)
	VALUES('$id','$user_id',NOW(),'$language','$ip','$len','$cid','$pid')";
//}
mysql_query($sql);
$insert_id=mysql_insert_id();

$sql="INSERT INTO `source_code`(`solution_id`,`source`)VALUES('$insert_id','$source')";
mysql_query($sql);
//echo $sql;


	 $statusURI=strstr($_SERVER['REQUEST_URI'],"submit",1)."../status.php";
	 if (isset($cid)) 
	    $statusURI.="?cid=$cid";
	    
        $sid="";
        if (isset($_SESSION['user_id'])){
                $sid.=session_id().$_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER["REQUEST_URI"])){
                $sid.=$statusURI;
        }
   // echo $statusURI."<br>";
  
        $sid=md5($sid);
        $file = "cache/cache_$sid.html";
    //echo $file;  
    if($OJ_MEMCACHE){
		$mem = new Memcache;
                if($OJ_SAE)
                        $mem=memcache_init();
                else{
                        $mem->connect($OJ_MEMSERVER,  $OJ_MEMPORT);
                }
        $mem->delete($file,0);
    }
	else if(file_exists($file)) 
	     unlink($file);
    //echo $file;
    

	header("Location: $statusURI");

	
?>