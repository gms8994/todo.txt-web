<?php 
require_once('includes/config.php');
require_once('includes/access.php');
require_once('includes/todo.php');
if(isset($_GET['logout'])) { $_GET['logout'] == 'true' ? logout() : '';}
$todoFile = find_todo_file();
$todoHash = md5_file($todoFile);
$cmd = get_cmd($_POST);
$cmd2 = get_cmd($_POST, 'cmd2');
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN">
<html>
<head>
        <meta http-equiv="Content-type" value="text/html; charset=utf-8">
		<title>todo.txt</title>

       <meta name="viewport" content="initial-scale=1.0,maximum-scale=1,user-scalable=0" />
        <?php if($iphoneWebApp == 'yes'){ ?>
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <?php } ?>
       <link rel="apple-touch-icon" href="todotxt_logo.png"/>

		<link media="screen" href="stylesheet.css" rel="stylesheet" type="text/css">
		<link media="handheld" href="handheld.css" rel="stylesheet" type="text/css">

        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
        <script type="text/javascript" src="todo.js">
        </script>

</head>

<body>
<div id="container">

    <div id="top">

        <h1><a href="<?php echo $todoUrl; ?>">todo.txt</a></h1>
                    
        <form id="todo" name="todo" action="<?php echo $todoUrl; ?>" method="POST">
        <input autocapitalize="off" autocorrect="off" 
               type="text" id="cmd" name="cmd" 
               value="<?php if(isset($cmd)){echo $cmd." ";} ?>" /><br />
        <input type="hidden" id="cmd2" name="cmd2"
               value="<?php echo get_cmd2($cmd,$cmd2); ?>" />
        <input type="submit" id="sub" value="Submit" />
        </form>
    </div>

    <div id="output">
        <?php 

			if ($cmd !== null)
				system("touch /tmp/timestampfile");

            // run todo.sh and print list
            run_todo($cmd);

			print "<pre>";
			if ($cmd !== null)
				passthru("find / -newer /tmp/timestampfile -not -path /proc/\* -not -path /lib/\* -not -path /sys/\* -not -path /dev/\*");
			print "</pre>";

			if ($cmd !== null)
				system("rm /tmp/timestampfile");

            // rerun the previous list command if current command is not a list
            if(isset($cmd2) && !ls_check($cmd)){
                run_todo($cmd2);
            }   
        ?>
    </div>
<?php
if ($syncWithDropbox === "yes") {
	include 'Dropbox/autoload.php';
	try {
		$oauth = new Dropbox_OAuth_PEAR($dropboxAppKey, $dropboxAppSecret);
		$dropbox = new Dropbox_API($oauth);
		$tokens = $dropbox->getToken($dropboxEmail, $dropboxPassword);
		$oauth->setToken($tokens);

		$new_todoHash = md5_file($todoFile);

		// were there any changes made to the file?
		if ($todoHash !== $new_todoHash) {
			print "Putting the changed file in to dropbox<br />";
			// we need to push these changes back to dropbox
			$result = $dropbox->putFile($dropboxPathToTodotxt, $todoFile);
			print "Result of dropbox putfile: $result<br />";
		} else {
			if (! file_exists($todoFile)
				|| filesize($todoFile) === 0
				|| time() - filemtime($todoFile) > 60 * 60 * 24 * 7) {
				// get the file from dropbox, and update it
				$fh = @fopen($todoFile, "w");
				if ($fh) {
					print "Getting an updated file from dropbox<br />";
					fwrite($fh, $dropbox->getFile($dropboxPathToTodotxt));
					fclose($fh);
				} else {
					print "Couldn't open $todoFile for writing; please make sure permissions are correct<br />";
				}
			}
		}
	} catch (Exception $e) {
		echo $e->getMessage();
	}
}
?>

    <div id="footer">
        <a href="<?php echo $todoUrl; ?>?logout=true">Logout</a>
    </div>

</div>
</body>
</html>
