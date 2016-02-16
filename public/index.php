<?php
/*
This is the landing page. It reads my GitHub account via the public API, gets
and then displays the content in a single page. It uses caching to keep the
request count low and not spam GitHub.
*/
$title = "Jared Ballou's Insurgency Tools";
require_once realpath('./..')."/include/header.php";
//User to pull the data for
$githubuser = 'jaredballou';
/*
$apiuser = '';
$apipass = '';
$apiauth = base64_encode("{$apiuser}:{$apipass}");
*/
$cache_file = $cachepath.'/content.html';
$cache_life = '300'; //caching time, in seconds
$filemtime = @filemtime($cache_file);	 // returns FALSE if file does not exist

startbody();
echo "<h1>{$title}</h1>\n";

echo "This is where I am going to list and document the tools I have been working on for the Insurgency standalone game released in 2014. I release everything I create under the GPLv2, I haven't commented all the code yet since the community is small and we all pretty much know each other, but my intent is to distribute everything free of charge, get feedback and bug fixes back, and build robust and useful tools for players that will help strengthen the community who play it. Everything I do is maintained in <a href='https://github.com/{$githubuser}'>my Github repositories</a> and aren't yet in a state where I have ZIP downloads packaged. <a href='http://steamcommunity.com/id/jballou'>I'm always on Steam, feel free to add me.</a>\n\n";

if ((!file_exists($cache_file)) || !$filemtime || (time() - $filemtime >= $cache_life)) {
	$data = GetGithubURL("users/{$githubuser}/repos");
//var_dump($data);
	$list = json_decode($data,true);
	$data = array();
	foreach ($list as $repo)
	{
		if (startsWith($repo['name'],'insurgency-'))
			$data[] = GetReadme($repo['name']);
	}
	file_put_contents($cache_file,implode("\r\n",$data));
}
readfile($cache_file);

function GetReadme($repo)
{
	$url = "repos/{$GLOBALS['githubuser']}/{$repo}";
	$data = GetGithubURL("{$url}/readme",'application/vnd.github.v3.html+json');
	$data = preg_replace('/href=[\'"]([^#][^\'":]*)[\'"]/',"href='https://github.com/{$GLOBALS['githubuser']}/{$repo}/blob/master/\\1'",$data);
	$data = preg_replace('/<\/a>(.*)<\/h1>/',"</a><a href='https://github.com/{$GLOBALS['githubuser']}/{$repo}'>\\1</a></h1>",$data);
	return $data;
}
function GetGithubURL($url,$accept='application/vnd.github.v3+json')
{
	$header = "Accept: {$accept}\r\n";
	if (isset($GLOBALS['apiauth']))
		$header.="Authorization: Basic {$GLOBALS['apiauth']}\r\n"; 
	$opts = array('http' => array('method' => 'GET', 'user_agent'=> 'jballou-website', 'header' => $header));
//$_SERVER['HTTP_USER_AGENT']

	$context = stream_context_create($opts);
	$url = "https://api.github.com/{$url}";
	return (file_get_contents($url, false, $context));
}
function startsWith($haystack, $needle) {
		// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
require "../include/footer.php";
exit;
?>
