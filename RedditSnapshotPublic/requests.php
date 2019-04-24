<?php
//***FEATURES***\\
//IMAGE SIZE ADJUSTER
//HYPER LINKS TO SOURCE
//IMAGE FITTING
//INFINITE SCROLLING

//TODO: SLIDER VARIABLE FOR HEIGHT AND WIDTH OF DIVS ON LINE 10 And 11
//TODO: SKIP NSFW (PARTIAL)
//TODO: INFINITE SCROLL DONE
//TODO: INFINE SCROLL TOGGLE
//TODO: GIF AND/OR VIDEO INDICATION (transparent banner under pic?)
//TODO: ENDPOINT SEARCH
//TODO: NSFW TOGGLE SWITCH (In progress)

//**==============================================================================**\\
//        NEW USERS OF THIS FILE UPDATE THESE FILEDS WITH YOUR INFORMATION          \\
//==================================================================================\\
date_default_timezone_set ('America/Toronto');
$username = '';
$password = '';
$clientId = '';
$clientSecret = '';  
$redirect_uri = 'https://google.com';
$filePath  = $_SERVER['DOCUMENT_ROOT']; //Where your snapshots will be saved to
$postLimit = '100';
$textPosts = false;
$nsfw      = true;
$t = 'year'; //Time to filter on top subreddits
$apiEndpoint = "/r/aww/top?limit=$postLimit&t=$t";
$url = "https://oauth.reddit.com/.json?limit=$postLimit"; //URL we want to retrieve postings from
//**===============================================================================**\\

$after = array_key_exists('after', $_GET) ? $_GET['after'] : null;

if($after){
	$url .= "&after=$after";
}

$snapshotDate = date("Y-m-d H:i:s"); 
$title = "Your Reddit Snapshot for $snapshotDate  &nbsp; &nbsp; | &nbsp; &nbsp;  Endpoint: $url";
$accessTokenUrl ='https://ssl.reddit.com/api/v1/access_token';
$baseUrl = 'https://oauth.reddit.com';

//Client Credentials grant is used when applications request an access token to access their own resources, not on behalf of a user. The client needs to authenticate themselves for this request. 
$fields = array (
    'grant_type' => 'password',
    'username' => $username,
    'password' => $password
);

$userAgent = 'RedditSnapshot v0.1';
$field_string = http_build_query($fields);


//Get our access token with oauth
$curl = curl_init($accessTokenUrl);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
curl_setopt($curl,CURLOPT_POST, 1);
curl_setopt($curl,CURLOPT_POSTFIELDS, $field_string);
$response = curl_exec($curl);
$response = json_decode($response, true);
$access_token = $response['access_token'];

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: bearer ' . $response['access_token'] ));
curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl,CURLOPT_USERAGENT, $userAgent);
$response = curl_exec($curl);
curl_close($curl);

$response = json_decode($response, true);
$postings = $response['data']['children'];
$after = $response['data']['after'];

//Filter and format postings
$countIndex = count($postings) - 1;
$after = $response['data']['after'];
$baseUrl = 'https://reddit.com';
$i=0;
$links = [];
foreach ($postings as $posting) {
    $i++;
	$preview = null;
	$isNsfw = $posting['data']['over_18'];
	$postHint = isset($posting['data']['post_hint']) ? $posting['data']['post_hint'] : '';
    $numComments = $posting['data']['num_comments'];
	if (isset($posting['data']['preview'])){
		if($nsfw == true){
			array_push($links, ['imgData' => $posting['data']['preview']['images']['0']['source'], 'url' => $baseUrl. $posting['data']['permalink'], 'postHint' => $postHint] );
		}else{
			if($isNsfw == false){
				array_push($links, ['imgData' => $posting['data']['preview']['images']['0']['source'], 'url' => $baseUrl. $posting['data']['permalink']]);
			}
		}
	}
}

$html = '';

for( $i=0; $i < count($links); $i++ ){

	$class = '';
	$src = $links[$i]['imgData']['url'];
	$postHint = $links[$i]['postHint'];
	$width = $links[$i]['imgData']['width'];
	$height = $links[$i]['imgData']['height'];
	$permalink = $links[$i]['url'];
	
	if($height > ((($width*2) / 5) * 4)){
		$class .= 'class="vertical"';
	}elseif($width > ((($height*2) / 5) * 4)){
		$class .= 'class="horizontal"';
	}else{
		$class = '';
	}
	
	//big statement boi
	if( ($height > 2000 && $width > 2000) && !($height > ((($width*2) / 5) * 4)) && !($width > ((($height*2) / 5) * 4)) ){
		$class = 'class="big"';
	}

	$html .= '<div '.$class.'>';

	//Banner for picture
	if($postHint && $postHint == 'rich:video'){
		$html .= '<div class="postHint">Animated</div>';
	}

	$html .= '<a target="_blank" href="'.$permalink.'">
			<img  src="'.$src.'"/>
		</a>
	</div>';
}

$payload = ['html' => $html, 'after' => $after, 'title' => $title];

print_r(json_encode($payload));
?>