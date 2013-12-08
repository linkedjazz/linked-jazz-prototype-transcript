<?


 

  	
	$transcriptDir = '/home/dropbox/Dropbox/*LinkedJazz_Team/1. Phase 1 - 2011/Sources/Transcripts_New_Sample/';		//the location of the transcripts on the server
	$transcriptTextDir = '/home/dbpedia/transcript/data/';
	
	
	
	
	//Load the list of possible PDFs available to process
	if ($handle = opendir($transcriptDir)) {
		$pdfFilenames=array();
		
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
				if (strpos(strtolower($entry),'.pdf')!==false){
					$pdfFilenames[]=$entry;
				}
 	
			}
			
			
		}
		

		if (file_exists('data/publishedFileNames.txt')){
		
		
			$text = file_get_contents('data/publishedFileNames.txt');
			$published = explode("\n",$text);
			 
			
		}
		
		$pdfFilesizes=array();
		$pdfFileLastProcessed=array();
		$pdfWorkingOn=array();
		$pdfPublished=array();		
		asort($pdfFilenames);
		
		$pdfFilesizesAvg = 0;
				
		for ($i = 0; $i < count($pdfFilenames); ++$i) {

			$pdfFilesizes[]=filesize($transcriptDir . $pdfFilenames[$i]) / 1024;
 			
			$pdfFilesizesAvg = $pdfFilesizesAvg + filesize($transcriptDir . $pdfFilenames[$i]) / 1024;
			
			//see if there is a process file for this pdf
			if (file_exists($transcriptTextDir . $pdfFilenames[$i] . '.txt')){				
				$date1 = new DateTime();
				$date1->setTimestamp(filemtime($transcriptTextDir . $pdfFilenames[$i] . '.txt'));				
				$date2 = new DateTime();								
				$interval = $date1->diff($date2);				 
				$pdfFileLastProcessed[]=$interval->format('%a');
				$pdfWorkingOn[]=$date2->getTimestamp() - $date1->getTimestamp();
				
 											
			}else{				
				$pdfFileLastProcessed[]='Never';
				$pdfWorkingOn[]=90000000;				
			}
			
			if (in_array($pdfFilenames[$i], $published)) {
				$pdfPublished[]=TRUE;	
 
			}else{
				$pdfPublished[]=FALSE;				
			}
			
			
	    }
		 
		  
		$pdfFilesizesAvg = $pdfFilesizesAvg / count($pdfFilesizes); 
		 
		$pdfFilesizesShort = ($pdfFilesizesAvg*2)/4;
		$pdfFilesizesMid = ($pdfFilesizesAvg*2)/4 * 2;
		$pdfFilesizesLong = ($pdfFilesizesAvg*2)/4 * 3;
		$pdfFilesizesVLong = ($pdfFilesizesAvg*2);
		 
		closedir($handle);
	}	
	 
 
	
	if (isset($_REQUEST['json'])){
		
		
		header("content-type: application/json");		
		
		
		if ($_REQUEST['json']=='removeGlobalSetting'){
			
			$filename == '';
			if ($_REQUEST['rule'] == 'ignore'){
				$filename = 'globalIgnore.txt';
			}
			if ($_REQUEST['rule'] == 'sameas'){
				$filename = 'globalSameAs.txt';
			}	
			if ($_REQUEST['rule'] == 'authority'){
				$filename = 'globalAuthority.txt';
			}						
			
			if ($filename==''||$_REQUEST['value']==''){
				die("error");	
			}
			
			$text = file_get_contents($transcriptTextDir . $filename);
			$text = explode("\n", $text);
			
 			$output='';
			
 			foreach ($text as $value) {
 				if ($value != $_REQUEST['value'] && $value != ''){
					$output = $output . $value . "\n"; 	
				}
			}		
				
 
			file_put_contents($transcriptTextDir . $filename, $output);
			print "{}";
		}
		
		
		if (isset($_REQUEST['statusUpdate'])){
		
			$filename =$_REQUEST['statusUpdate'];
			
			if (strpos($filename,"..")!==false || strpos($filename,"/")!==false){
				die("error");
			
			}			
			
			$filename = $transcriptTextDir . $_REQUEST['statusUpdate'] . ".txt_status.json";
			
			if (file_exists($filename)) {
				
				
				die (file_get_contents($filename));
				
				
			}
		
			die();
		
		}
		
		
		
		if (isset($_REQUEST['sources'])){
			
			$filename = 'transcriptSources.json';
			
			$jsonStr = json_encode($_POST);	
			 
			file_put_contents($transcriptTextDir . $filename, $jsonStr);
			
			die('{"results":true}');
		}		
		
		if (isset($_REQUEST['reid'])){
			
			$filename = $_REQUEST['reid'] . '_userRules.json';
			
			if (strpos($filename,"..")!==false || strpos($filename,"/")!==false){
				die("error");	
			
			}				
			
			$jsonStr = json_encode($_POST);	
			 
			file_put_contents($transcriptTextDir . $filename, $jsonStr);
			
			die('{"results":true}');
		}
		
		
		
		
		
		if (isset($_REQUEST['processTranscript'])){
			
			$filename=$_REQUEST['processTranscript'];				
 				
			if (strpos($filename,"..")!==false || strpos($filename,"/")!==false){
				die("error");	
			
			}		
			
			
			//die('{"results": {"error": false,"id": "28c7e27b63a73c4426771be57b736187"}}');
			
			
			//first thing is to convert the pdf to text
			$results = shell_exec('pdftotext -layout -enc ASCII7 "' . $transcriptDir . $filename . '" "' . $transcriptTextDir . $filename .'.txt"');
			
			
			if ($results==''){				
				
				
				//run the 
				$results = shell_exec('python ner.py "' . $filename . '.txt" 2>&1');
				 
				shell_exec('touch "' . $transcriptTextDir . $filename  . '.txt"');
				
			}else{
			
				die('{"results": {"error": true,"msg": "' . json_encode($results) . '"}}');	
				
			}
			
			
			die($results);	
			
			
		}
		
		
		if (isset($_REQUEST['loadTranscriptText'])){
			
			$filename = $_REQUEST['loadTranscriptText'];
			
		
			if (strpos($filename,"..")!==false || strpos($filename,"/")!==false){
				die("error");	
			}			
			$filename = 'data/' . $filename . ".txt";
			
			
			if (file_exists($filename)){
			
			
				$text = file_get_contents($filename);
				$text = str_ireplace("\n","<br>",$text);
				
					
				die('{"results": {"error": false,"text": ' . json_encode($text) . '}}');	
				
				
				
			}else{
			
				die('{"results": {"error": true,"msg": "Could not locate text file."}}');	
				
			}
			
			
		
		}
		
		
		die();
		
	}
	
	
	
	
	//the user
	if (isset($_SERVER['PHP_AUTH_USER'])){
		$userName = $_SERVER['PHP_AUTH_USER'];	
	}else{
		$userName = 'none';
	}
	
	
	
	
	
	//loads a list of images available
	if ($handle = opendir('../network/img/')) {
		$jsFileNames='';
		$jsMetaNames='';
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
				if (strpos($entry,'.png')!==false){
					$jsFileNames .= "'" . $entry . "',";
				}
 		
			}
			
			
		}
		$jsFileNames = substr($jsFileNames,0,strlen($jsFileNames)-1);
		$jsFileNames = "var imageNames = [" . $jsFileNames . "];";
 
		
		closedir($handle);
	}	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	if (isset($_REQUEST['askWhy'])){
	
		header("content-type: text/plain");
		
		$_REQUEST['askWhy'] = str_replace("..",'',$_REQUEST['askWhy']);
		
 
		
		$_REQUEST['askWhy'] = urlencode($_REQUEST['askWhy']);
		
		$results = shell_exec('grep "' . $_REQUEST['askWhy'] . '" /var/www/data/allJazz.nt');
		echo $results;
		
		die();
		
		
	}
	
	
	if (isset($_REQUEST['dbpedia'])){
		
		$dbp = $_REQUEST['dbpedia'];
		$loc = $_REQUEST['loc'];		
		
		
		$fh = fopen("/var/www/data/verified.nt", 'a') or die('error opening file');
		fwrite($fh, "<$dbp> <http://www.w3.org/2002/07/owl#sameAs> <$loc> .\n");		
		fclose($fh);
		
		
		
		echo $dbp;
		echo $loc;
		
		
		
		
		die();
	}
	
	if (isset($_REQUEST['deldbpedia'])){
		
		$dbp = $_REQUEST['deldbpedia'];
 
		
		$fh = fopen("/var/www/data/deleted.nt", 'a') or die('error opening file');
		fwrite($fh, "<$dbp> <http://www.w3.org/2002/07/owl#sameAs> <none> .\n");		
		fclose($fh);
		 
		
		die();
	}	
	
	if (isset($_REQUEST['undeldbpedia'])){
		
		$dbp = $_REQUEST['undeldbpedia'];
 
		
		
		$removeLine = "<$dbp> <http://www.w3.org/2002/07/owl#sameAs> <none> .";		
		$results = shell_exec('grep -v "' . $removeLine . '" /var/www/data/deleted.nt> /var/www/data/deleted.tmp.nt');
		$results = shell_exec('mv /var/www/data/deleted.tmp.nt /var/www/data/deleted.nt');
		 
		  
		die();
	}		
	
	if (isset($_REQUEST['undbpedia'])){
		
		$dbp = $_REQUEST['undbpedia'];
		$loc = $_REQUEST['unloc'];		
		
		$removeLine = "<$dbp> <http://www.w3.org/2002/07/owl#sameAs> <$loc> .";		
		$results = shell_exec('grep -v "' . $removeLine . '" /var/www/data/verified.nt > /var/www/data/verified.tmp.nt');
		$results = shell_exec('mv /var/www/data/verified.tmp.nt /var/www/data/verified.nt');
		
		
		//echo 'grep -v "' . $removeLine . '" data/verified.txt > data/verified.tmp.txt';
		//echo 'mv data/verified.tmp.txt data/verified.txt';
		
		
		die();
	}	
	
	
	 




?>
<? header('Content-type: text/html; charset=utf-8'); ?>
<!doctype html>
<html>
<head>

  <meta charset="utf-8">

  <!-- Use the .htaccess and remove these lines to avoid edge case issues.  More info: h5bp.com/i/378 -->
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title></title>
  <meta name="description" content="">

  <!-- Mobile viewport optimized: h5bp.com/viewport -->
  <meta name="viewport" content="width=device-width">

  <!-- Place favicon.ico and apple-touch-icon.png in the root directory: mathiasbynens.be/notes/touch-icons -->

  <link rel="stylesheet" href="css/bootstrap.css">
  <link rel="stylesheet" href="css/font-awesome.css">
    
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script> 
    <script src="js/bootstrap.min.js"></script> 



	<script>
	
	var userName = "<?=$userName?>";
	
	<?=$jsFileNames?>
    
    </script>
 
 
	<style type="text/css">
	
		body{
			background-image:url(img/gplaypattern.png);
			background-repeat:repeat;
		}
	
	
		.popover-title{
			display:none;
			
			
		}
	
		#logo{
			height:40px; width:auto; margin:2px; float:left;
			padding-right:25px;
			
		}
	 
		
		#overlay{
			height:1000px;
			width:100%;
			position:absolute;
			display:none;
			left:0px;
			top:0px;
			background-color:#333;
			opacity:0.5;
			z-index:999999;
			color:#FFF;
			font-size:76px;
			padding-top:100px;
			text-align:center;
			text-shadow: 5px 5px 3px #000;


			
		}
			
		.menuItem{
			height:40px;
			line-height:60px;
			font-size:18px;
			float:left;
			margin-right:15px;
			
			
		}	
		
		
		.modal{
			width:660px;
			
			
		}
		
		#contentDetailDisplay{
			background-color:#F7F7F7;
			border:1px solid #999;
			overflow:auto;
			overflow-y: auto;
			font-family:"Courier New", Courier, monospace;
			font-size:12px;
			visibility:hidden; 
		}
		#contentDetailDisplay span{
			border-radius:3px;
			
		}
		
		
		#contentList{
			overflow:auto;
			overflow-y: auto;
			
			
		}
				
		#contentList::-webkit-scrollbar, #contentDetailDisplay::-webkit-scrollbar{
			-webkit-appearance: none;
			width: 10px;
		}		
		#contentList::-webkit-scrollbar-thumb , #contentDetailDisplay::-webkit-scrollbar-thumb{
			border-radius: 4px;
			background-color: rgba(0,0,0,.5);
			-webkit-box-shadow: 0 0 1px rgba(255,255,255,.5);
		}​			
		
		
		.partialMatch{
			background-color:#fdd0a2;
		}
		.partialMany{
			background-color:#fdae6b;			
		}		
		
		.transcriptHeader{
			opacity: .5;
		}
		 

				
		.fullMatch{
			background-color:#c6dbef;
		}
			
			
		.personListItem{

			width:89%;
 			-webkit-border-radius: 10px;
			-moz-border-radius: 10px;
			margin:5px;
			padding:5px;
			border-radius: 10px;
			border: solid 1px #CCC;
			background-image:url(img/trans.png);
			background-repeat:repeat;
			position:relative;
			padding-left:30px;
			
			
			-webkit-transition: all 1s ease-in-out;
			-moz-transition: all 1s ease-in-out;
			-o-transition: all 1s ease-in-out;
			-ms-transition: all 1s ease-in-out;
			transition: all 1s ease-in-out;
 		 	
			
		}
		
		.personListItem:hover{
			border-color:#9e0b0f;
		}			
		
		.personListItemControls{
			float:right;

			
		}
		
		.personListItemControls i{
			font-size:22px;
			padding:0 5px 0 5px;
			cursor:pointer;
			color:#999;
			
		}
		.personListItemControls i:hover{		
			color: #000;
		}
		
		.personListItem img{
			height:30px;
			width:auto;
			position:absolute;
			left:-4px;
			top:-2px;

			
		}
		
		
		b{
			font-size:14px;
		}
		 
		 
		 
		 #removeModalContent{
			 
			 
			 
		 }
		 
		 .removeModalContentItem{
			 border-bottom: solid 1px #666;
			 text-align:left;
			 padding-bottom:10px;
			 height:75px;
			 line-height:75px;
			 
		 }
		 .removeModalContentItem button{
			 margin-bottom:40px;
			 margin-left:20px;
			 
			 
		 }
		 
		 #removeModalContent i, #authModalContent i{
			 font-size:72px;
			 
			 border-right: solid 1px #666;
			 padding:5px;

			 line-height:72px;			 
		 }
		 
		 #authModalContent input{
					 
			font-size: 28px;
			height: 28px;
			margin-bottom: 38px;
			margin-left:5px;
			
			
			line-height: 34px;			 
			 
		 }
		 
		 #authModalCreateHolder input{
			margin-bottom: 5px;
		 }


		 
		 #dirtyWarning{
			 position:fixed;
			 top:43px;
			 right:0px;
			 font-size:14px;
			 width:65%;
			 background-color:#FCC;
			 text-align:center;
			 display:none;

			 
		 }
				 
		.alertGlow {
			-webkit-transition: all 10s linear;
			-moz-transition: all 10s linear;
			-ms-transition: all 10s linear;
			-o-transition: all 10s linear;
			transition: all 10s linear;
		}		 
		.alertGlow.glow {
			box-shadow:0 0 10px #F00;			
		}		 
		   
		 
		#contentListRules label{
			font-size:14px;
			font-weight:bold;
		}
		#contentListRules i{
			font-size:20px;
			cursor:pointer;
			padding:0 2px 0 2px;
			color:#900;
		}
		#contentListRules input{
			width:320px;
		}
		 
		.workingOn{
			background-color:#FFF3E0 !important;
		}
		.publishedPDF{
			background-color:#B2E0B2 !important;
			
		}

		 
	</style>
 
 
 
 
 
 
 	<script type="text/javascript">
	
	 
	
		var tr = {};
		
		
		

		
		
		
		tr.resetUserRules = function(){
	
			//the variable that holds all the decisions the user makes while processing the transcript
			tr.userRules =
				{
					ignoreLocal : [],
					ignoreGlobal : [],
					otherName : [],
					sameAs : [],
					manualNames : [],
					intervieweesNames : [],
					intervieweesSplits : [],
					interviewersNames : [],
					interviewersSplits : [],
					structureRegexPattern : '',
					structureIgnoreCountTest : false,
					partialAprovals : [],
					authorityControl : [],
					publish : {'publish' : 0},
					userName : userName
					
					
				};	
			
		}
	
		tr.timer = null;
		tr.timerObj = [];
		tr.sources = {};
		
		tr.globalSameAs = null;
		tr.globalAuthority = null;
		
	
		tr.dirty = function(){
				$("#dirtyWarning").css("display","block");
				tr.isDirty = true; 
				if (tr.undo != ""){$("#dirtyWarningUndo").css("display","inline")}
				tr.updateUserRulesCount();
		}
				
		tr.clean = function(){$("#dirtyWarning").css("display","none");tr.isDirty = false;tr.updateUserRulesCount();}		
	
		tr.binds = function(){
			
			//remove them, we call this funtion more than once when the dom is changed
			$('.bindLinkClass, .bindButtonClass, .bindMissClass, .partialMatch, #contentDetailDisplay').unbind();
			
			 
			 
			 //when the mouse leaves the partial item set a timer to close the popup
			 $('.partialMatch').bind('mouseleave', function(event) {

				tr.timer =  setTimeout(function(){
				
					for (x in tr.timerObj){
						tr.timerObj[x].popover('hide');
					}
					tr.timerObj = [];					
					clearTimeout(tr.timer);					
					
				},100);	
		
			 });
			
			$('.partialMatch').bind('mouseover', function(event) {
				

				
				var loc = ($(this).position().left > 900) ? 'left' : 'right';
				
				$(this).popover({
					
					html : true,
					placement: loc,
					title : "hi",
					trigger : 'manual',
					content : tr.partialPopupContent($(this))	
					
				});
				
				$(this).popover('show');
				
				
				//a hack. The dom returned through the function of the popup handelr is not binding.
				tr.partialPopupContentBind();
				
				
				tr.timerObj.push($(this));	
						
				var link  = $(this);
				$('.popover').mouseleave(function () {
					link.popover('hide');
					clearTimeout(tr.timer);
				}); 		
				
				$('.popover').mouseenter(function () {
 					clearTimeout(tr.timer);
				}); 				 	
				
			})
			
			
			
			
			

			$('.bindMissClass').bind('click', function(event) {
				
				
				
			});
			
			//
			//	LINKS
			//
			$('.bindLinkClass').bind('click', function(event) {
			
			
				//undo link
				if ($(this).attr("id") == 'undoLink'){
					
					if (typeof tr.undo != "undefined" && tr.undo != ""){
					
						eval(tr.undo);
						$("#dirtyWarningUndo").css("display","none");
						tr.undo="";

					}
					
				}
				
				
				
				//search for other names
				if ($(this).hasClass('icon-search') == true && $(this).hasClass('partialSearch') == true ){
					
					
					$("#contentDetailDisplay span").css("border","none");					
					
					var ids = $(this).data("name");

					
					if ($(this).data("activeId") == ""){
						var useId = ids[0];						
					}else{
						
						varIdIndex = ids.indexOf($(this).data("activeId"));
						
						if (varIdIndex+1 > ids.length-1){
							var useId = ids[0];	
						}else{
							var useId = ids[varIdIndex+1];	
						}
						
					}
					
					$(this).data("activeId", useId);
					
					//scroll to the element + a little bit of room to see the context
					var topPos = document.getElementById(useId).offsetTop - 220;
					document.getElementById('contentDetailDisplay').scrollTop = topPos;							 
					$("#" + useId).css("border","solid 2px red"); 							
					
				}				
				
				//search for other names
				if ($(this).hasClass('icon-search') == true && $(this).hasClass('partialSearch') == false ){
					
					$("#contentDetailDisplay span").css("border","none");					
					
					var ids = tr.others[$(this).data("name")].ids;
					
					if ($(this).data("activeId") == ""){
						var useId = ids[0];						
					}else{
						
						varIdIndex = ids.indexOf($(this).data("activeId"));
						
						if (varIdIndex+1 > ids.length-1){
							var useId = ids[0];	
						}else{
							var useId = ids[varIdIndex+1];	
						}
						
					}
					
					$(this).data("activeId", useId);
					
					//scroll to the element + a little bit of room to see the context
					var topPos = document.getElementById(useId).offsetTop - 220;
					document.getElementById('contentDetailDisplay').scrollTop = topPos;							 
					$("#" + useId).css("border","solid 2px red"); 							
					
				}
			
				if ($(this).hasClass('icon-map-marker')){									
					if (tr.userRules.otherName.indexOf($(this).data("name")) == -1){
						tr.userRules.otherName.push($(this).data("name"));
						$(this).parent().parent().css("opacity",0.1);
						tr.undo = "tr.userRules.otherName.pop()";
						tr.dirty();
						
					}
				}
				if ($(this).hasClass('otherToUser')){									
					if (tr.userRules.manualNames.indexOf($(this).data("name")) == -1){
						tr.userRules.manualNames.push($(this).data("name"));
						$(this).parent().parent().css("opacity",0.1);
						tr.undo = "tr.userRules.manualNames.pop()";
						tr.dirty();
					}
				}				
				
				
				//auth modal
				
				if ($(this).hasClass('icon-globe')){
					
					$("#authModalCreateText, #authModalCreate").addClass("disabled");
					$("#authModalCreateText, #authModalCreate").attr("disabled", "disabled");	
					
					
					var data = $(this).data("data");
					
					if (data.authority==''){ 
						$("#authModalCreateText, #authModalCreate").removeClass("disabled");
						$("#authModalCreateText, #authModalCreate").removeAttr("disabled");	
					
						
						$("#authModalCreateText").val(data.name.replace(" ","_"));
					}
					
					
					$("#authTitle").text(data.name);
					
					$("#authModalCreate").data("name",data.name);
					$("#authModalAdd").data("name",data.name);					
					
					data.authority = data.authority.replace("<",'').replace(">",'');
					$("#authModalAddText").val(data.authority);
						 
					 
					$("#authModalAddGlobe").unbind();
					$("#authModalAddGlobe").click(function(){						
						if (data.authority!= ''){
							window.open(data.authority,'_blank');
						}	 
					 });
					 
					 
						
					$("#authModalSearchLinks")
						.empty()
						.append(
							$("<a>")
								.attr('href',"https://www.google.com/search?q=" + data.name)	//google
								.attr('target',"_blank")
								.text("Google")
						)
						.append($("<span>").text(" | "))
						.append(
							$("<a>")
								.attr('href',"http://en.wikipedia.org/w/index.php?search=" + data.name)	//wiki
								.attr('target',"_blank")
								.text("Wikipeida")
						)	
						.append($("<span>").text(" | "))						
						.append(
							$("<a>")
								.attr('href',"http://musicbrainz.org/search?query=" + data.name + "&type=artist")	//musicbrainz
								.attr('target',"_blank")
								.text("Musicbrainz")
						)
						.append($("<span>").text(" | "))				
						.append(
							$("<a>") 
								.attr('href',"http://id.loc.gov/search/?q=" + data.name + "&q=cs%3Ahttp%3A%2F%2Fid.loc.gov%2Fauthorities%2Fnames")	//LOC
								.attr('target',"_blank")
								.text("LOC")
						)												
					
					$('#authModal').modal('show');
					
				}
				
				//The remove name modal
				if ($(this).hasClass('icon-question-sign')){
					
					tr.activePersonListId =  $(this).data("listId");
					//set the title
					$("#removeNameTitle").text('"' + $(this).data("name") + '"');
					
					tr.activeName = $(this).data("name");
					
					//prepare the modal elements
					//build the search links that appear at the bottom
					$("#removeModalSearchLinks")
						.empty()
						.append(
							$("<a>")
								.attr('href',"https://www.google.com/search?q=" + $(this).data("name"))	//google
								.attr('target',"_blank")
								.text("Google")
						)
						.append($("<span>").text(" | "))
						.append(
							$("<a>")
								.attr('href',"http://en.wikipedia.org/w/index.php?search=" + $(this).data("name"))	//wiki
								.attr('target',"_blank")
								.text("Wikipeida")
						)	
						.append($("<span>").text(" | "))						
						.append(
							$("<a>")
								.attr('href',"http://musicbrainz.org/search?query=" + $(this).data("name") + "&type=artist")	//musicbrainz
								.attr('target',"_blank")
								.text("Musicbrainz")
						)		
						.append($("<span>").text(" | "))				
						.append(
							$("<a>") 
								.attr('href',"http://id.loc.gov/search/?q=" + $(this).data("name") + "&q=cs%3Ahttp%3A%2F%2Fid.loc.gov%2Fauthorities%2Fnames")	//LOC
								.attr('target',"_blank")
								.text("LOC")
						)							
						
						$("#removeModalSameAs").css("display","inline");
						$("#removeModalSameAsText").css("display","none").val('');						
						
						//populate the Same As select box
						$("#removeModalSameAs").empty();
						//first two defult options
						$("#removeModalSameAs").append($("<option>").val("Select Name").text("Select Name"));
						$("#removeModalSameAs").append($("<option>").val("Enter Name Manually").text("Enter Name Manually"));						
						
						for (x in tr.allNames){
							$("#removeModalSameAs").append($("<option>").val(tr.allNames[x]).text(tr.allNames[x]));
						}
							
							
					$('#removeModal').modal('show')	
					
				}
			


				//The left < and right > find names in the text 
				if ($(this).hasClass('icon-chevron-left')==true||$(this).hasClass('icon-chevron-right')==true){
					
					var arrayOfIds = []
					//first get an array of the possible match ids to cycle through
					for (x in tr.matches){							
						for (y in tr.matches[x].name){
							if (tr.matches[x].name[y] == $(this).data("name")){
								arrayOfIds.push(tr.matches[x].id);
							}
						}
					}
					
					$("#contentDetailDisplay span").css("border","none");
					
					if (arrayOfIds.length==0){return false;}
					
					var useElement = ''
					//going into the doc
					if ($(this).hasClass('icon-chevron-right')){
						
						if ($(this).data("activeId")==""){
							useElement = arrayOfIds[0];
							
						}else{
						
							//find the index of the active id
							var index = arrayOfIds.indexOf($(this).data("activeId"));
							
							//+1
							index=index+1
							
							if (index<arrayOfIds.length){
								useElement = arrayOfIds[index];
							}else{
								$(this).data("activeId", "");
								$(this).click();
								return false;
							}
							
							
						}
						 
						
					}else{
						
						//if it is blank and they want to go backwards, go to the last occurance
						if ($(this).data("activeId")==""){
							useElement = arrayOfIds[arrayOfIds.length-1];							
						}else{
						
							//find the index of the active id
							var index = arrayOfIds.indexOf($(this).data("activeId"));
							
							//-1
							index=index-1;
							
							if (index>=0){
								useElement = arrayOfIds[index];
							}else{
								$(this).data("activeId", "");
								$(this).click();
								return false;
							}
							
							
						}						
						
						
						
					}
					

					
					//scroll to the element + a little bit of room to see the context
					var topPos = document.getElementById(useElement).offsetTop - 220;
					document.getElementById('contentDetailDisplay').scrollTop = topPos;							 
					$("#" + useElement).css("border","solid 2px red");
					$(this).data("activeId", useElement);							
					
					
					
				}


				
				
			
				event.preventDefault();
				return false;									

			
			})
			
			
			//
			//	BUTTONS
			//			
			$('.bindButtonClass').bind('click', function(event) {
			
			
				if($(this).attr("id") == 'publishFinal'){ 
				
					tr.userRules['publish'].publish = 1;
					$('#publishModal').modal('hide');
					$('#reprocess').trigger('click');
				
				}
			
				if($(this).attr("id") == 'publishAddSource'){ 
				
					var sourceId = prompt("Enter the name of the source, such as 'Smithsonian'.");

					if (sourceId=='' || sourceId == null){return false;}
					var sourceURL = prompt("Enter the full URL of where to find the source.");
					if (sourceURL=='' || sourceURL == null){return false;}					
					tr.sources[sourceId] = sourceURL;
					
					$.post("?json=true&sources=add", tr.sources, function(data){
						
						
						$('#publishModal').modal('hide');
						$('#publish').trigger('click');
						
						 
					});
										
				
				}
			
			
				//the publish modal
				if($(this).attr("id") == 'publish'){ 
				

					
					$("#publishModalContentInterviewee button").remove();
					
					for (x in tr.allNamesObject){
					
						if 	(tr.allNamesObject[x].interviewee){
							
							if (tr.allNamesObject[x].authority.search('dbpedia') != -1 || tr.allNamesObject[x].authority.search('linkedjazz') != -1){
								var imageName = tr.allNamesObject[x].authority.split('/resource/')[1];
								imageName = imageName.substring(0,imageName.length-1) + '.png';
								
								if (imageNames.indexOf(imageName) != -1){
									imageName = imageNames[imageNames.indexOf(imageName)];
									imageName = '/network/img/' + imageName;
									
								}else{
									var imageName = 'img/no_image.png';	
								} 
							}else{
								var imageName = 'img/no_image.png';
							}
						
							if (tr.allNamesObject[x].authority == ""){
								imageName = "img/no_authority.png";
							}							
							if (imageName == 'img/no_image.png' && tr.allNamesObject[x].authority.search('linkedjazz') != -1){
								imageName = "img/lj_image.png";	
							}	
								
							if (imageName == 'img/no_image.png' && tr.allNamesObject[x].authority.search('id.loc.gov') != -1){
								imageName = "img/loc_image.png";	
							}													
							
							$("#publishModalContentInterviewee")
								.append(
									$("<button>")
										.data("data",tr.allNamesObject[x])
										.html(
											$("<div>")
												.append(
												$("<img>")
													.attr("src",imageName)
													.css("height","75px")
													.css("width","auto")
												)
												.append($("<br>"))
												.append($("<span>").text(tr.allNamesObject[x].name))
													
										)
										.click(function(){
											
												if ($(this).data("data").authority==''){
													alert("You cannot use an interviewee who has no authority mapping!");	
													return false;
												}
												if ($(this).data("data").authority.search('dbpedia')==-1 && $(this).data("data").authority.search('linkedjazz')==-1){
													alert("You cannot use an interviewee who has a non dbpedia authority mapping.");	
													return false;
												}												
											
												tr.userRules.publish['interviewee'] = $(this).data("data").name;
												tr.userRules.publish['intervieweeAuth'] = $(this).data("data").authority;												
												$("#publishModalContentIntervieweeInput").text($(this).data("data").name);
												
												if (typeof tr.userRules.publish['sourceName'] != 'undefined'){
														$("#publishFinal").removeClass("disabled");
														$("#publishFinal").removeAttr("disabled");														
												}
												
										})
										
								);
							
						}
						
					}
					
					//add in the sources
					
					//first load it
					$("#publishModalContentSource button").remove();
  					for (x in tr.sources){
						
							$("#publishModalContentSource")
								.append(
									$("<button>")
										.data("data-name",x)
										.data("data-url",tr.sources[x])										
										.addClass("btn")
										.text(x)
										.click(function(){
											
											 
											
												tr.userRules.publish['sourceName'] = $(this).data("data-name");
												tr.userRules.publish['sourceURL'] = $(this).data("data-url");												
												$("#publishModalContentSourceInput").text($(this).data("data-name"));
												
												if (typeof tr.userRules.publish['interviewee'] != 'undefined'){
														$("#publishFinal").removeClass("disabled");
														$("#publishFinal").removeAttr("disabled");														
												}
												
										})
										
								);
						
						
						
					}
					
					$('#publishModal').modal('show');
				
				}
			
				//the auth control modal
				if($(this).attr("id") == 'authModalCreate'){ 
				
					var newAuth = $("#authModalCreateText").val();
					if (newAuth==''){return false;}
					for (x in tr.userRules.authorityControl){						
						if (tr.userRules.authorityControl[x].name == $(this).data("name")){
							alert("This name is already mapped to :" +	tr.userRules.authorityControl[x].value + "\n remove it from the Rules tab if you want to change it");
							return false;

						}						
					}
					
					if (newAuth.search(" ")!=-1){
						alert("No spaces alowed, it should be a URI friendly text");
						return false;	
					}
					
					newAuth = '<http://linkedjazz.org/resource/' + newAuth + '>'
					
					 
					var newAuthSource = $("#authModalCreateTextAuth").val();
					var newAuthSourceNotes = $("#authModalCreateTextFree").val();
					
					tr.userRules.authorityControl.push({'name' : $(this).data("name"), 'type' : 'new', 'value' : newAuth, 'sourceUrl' : newAuthSource, 'sourceNotes' : newAuthSourceNotes  });
					
					
					tr.undo = "tr.userRules.authorityControl.pop()";					
					tr.dirty();						
					tr.buildUserRulesForm();
					$('#authModal').modal('hide');
				
				}
			
				//the auth control modal
				if($(this).attr("id") == 'authModalAdd'){ 
				
					var newAuth = $("#authModalAddText").val();
					if (newAuth==''){return false;}
					
					if (newAuth.search('wikipedia') != -1){
						
						var brokenAuth = newAuth.split('/wiki/')
						newAuth = 'http://dbpedia.org/resource/' + brokenAuth[brokenAuth.length-1];
							
					}
					
					
					newAuth = '<' + newAuth + '>';
					
					for (x in tr.userRules.authorityControl){						
						if (tr.userRules.authorityControl[x].name == $(this).data("name")){
							alert("This name is already mapped to :" +	tr.userRules.authorityControl[x].value + "\n remove it from the Rules tab if you want to change it");							
							return false;	
						}						
					}
					
					tr.userRules.authorityControl.push({'name' : $(this).data("name"), 'type' : 'add', 'value' : newAuth});
					
					tr.undo = "tr.userRules.authorityControl.pop()";					
					tr.dirty();						
					tr.buildUserRulesForm();
					$('#authModal').modal('hide');
				}			
		 
				
				
				//the menu functions				
				if ($(this).hasClass("menuFunctions")){		
				
				
				
					$(".contentListItem").css("display","none");
					$(".contentListStructure").css("display","none");					
					$("#contentListRules, #contentListOthers").css("display","none");
					
					if ($(this).attr("id") == 'menuFunctionsNameControl'){
						
						$("#contentListNames").css("display","block");
						return true;	
					}
					
					if ($(this).attr("id") == 'menuFunctionsPartialMatches'){
						
						$("#contentListPartials").css("display","block");
						return true;	
					}					
					
					
					
					
					if ($(this).attr("id") == 'menuFunctionsStructure'){
						
						$("#contentListStructure").css("display","block");
						return true;	
					}					
					if ($(this).attr("id") == 'menuFunctionsRules'){
						
						
						tr.buildUserRulesForm();
						$("#contentListRules").css("display","block");
						return true;	
					}	
					
					if ($(this).attr("id") == 'menuFunctionsOtherNames'){						
						$("#contentListOthers").css("display","block");
						return true;	
					}									
				
					
				
				
					 
				
				
				
				
				}
				
				
				
				//The reporocess button				
				if ($(this).attr("id") == 'reprocess'){
					 
					 
					 
					 //make sure the structure data is loaded into the object 
					 tr.userRules.intervieweesNames = $("#structureInterviewees").val().split(',');
					 tr.userRules.intervieweesSplits = $("#structureIntervieweesSplit").val().split(',');
					 tr.userRules.interviewersNames = $("#structureInterviewers").val().split(',');
					 tr.userRules.interviewersSplits = $("#structureInterviewersSplit").val().split(',');
					 tr.userRules.structureRegexPattern = $("#structureRegexPattern").val();
				      
 					 if ($('#structureIgnoreCountTest').is(':checked')) {
						 tr.userRules.structureIgnoreCountTest = true;
					 }else{
						 tr.userRules.structureIgnoreCountTest = false;						 
					 }
					 
					 
					 		 
				
					 
					
					//write the current user rules to the server
					$.post("?json=true&reid="+tr.id, tr.userRules, function(data){
						
						
						//run again
						tr.loadTranscriptControl(tr.activeFileName);						
						 
					});
					
					
					
				}
			
			
			
			
				//add name maualy to the name list				
				if ($(this).attr("id") == 'contentListAddName'){
					
					var manualName=prompt("Please enter name to add:","");
					if (typeof manualName != "string" || manualName == ""){return false;}			
					tr.userRules.manualNames.push(manualName);
					tr.undo = "tr.userRules.manualNames.pop()";					
					tr.dirty();		
					
					
				}
				
				//add name maualy to the other name list				
				if ($(this).attr("id") == 'contentListAddOther'){
					
					var manualName=prompt("Please enter Other name to add:","");
					if (typeof manualName != "string" || manualName == ""){return false;}			
					
					if (tr.userRules.otherName.indexOf(manualName) == -1){
						tr.userRules.otherName.push(manualName);
						tr.undo = "tr.userRules.otherName.pop()";					
						tr.dirty();		
					}
					
					
				}				
				
				
			
			
				//The remove name modal, local ignore
				if ($(this).attr("id") == 'removeModalLocalIgnore'){
					
					
					
					if (tr.userRules.ignoreLocal.indexOf(tr.activeName) == -1){
						tr.userRules.ignoreLocal.push(tr.activeName);
						tr.undo = "tr.userRules.ignoreLocal.pop()";
						
						tr.dirty();
						
						//modify the list id item to mark that is has been worked on
						$("#" + tr.activePersonListId).css("opacity",0.1);
						
					}
					
					$('#removeModal').modal('hide')	
 				}

				//The remove name modal, global ignore
				if ($(this).attr("id") == 'removeModalGlobalIgnore'){					
					if (tr.userRules.ignoreGlobal.indexOf(tr.activeName) == -1){
						tr.userRules.ignoreGlobal.push(tr.activeName);
						tr.undo = "tr.userRules.ignoreGlobal.pop()";						
						tr.dirty();
						//modify the list id item to mark that is has been worked on
						$("#" + tr.activePersonListId).css("opacity",0.1);
						
					}					
					$('#removeModal').modal('hide')	 
				}	
				
				//The remove name modal, other name
				if ($(this).attr("id") == 'removeModalOtherName'){					
					if (tr.userRules.otherName.indexOf(tr.activeName) == -1){
						tr.userRules.otherName.push(tr.activeName);
						tr.undo = "tr.userRules.otherName.pop()";						
						tr.dirty();
						//modify the list id item to mark that is has been worked on
						$("#" + tr.activePersonListId).css("opacity",0.1);
						
					}					
					$('#removeModal').modal('hide')	 
				}			
				//The remove name modal, same as
				
				if ($(this).attr("id") == 'removeModalSameAsButton'){					
				
					var sameAsName = '';
					//figure out who is selected
					if ($("#removeModalSameAs").val()=="Enter Name Manually"){
						
						if ($("#removeModalSameAsText").val()!="Enter Name Manually"){
							
							sameAsName = $("#removeModalSameAsText").val();

						}
					
						
					}else{
					
						if ($("#removeModalSameAs").val()!="Select Name"){
						
							sameAsName = $("#removeModalSameAs").val();
							
						}
					
					}
					
					if (sameAsName==''){return false;}
					
					
					var sameAsObj = {"org" : tr.activeName, "sameAs": sameAsName};
					
				
					if (tr.userRules.sameAs.indexOf(sameAsObj) == -1){
						tr.userRules.sameAs.push(sameAsObj);
						tr.undo = "tr.userRules.sameAs.pop()";						
						tr.dirty();
						//modify the list id item to mark that is has been worked on
						$("#" + tr.activePersonListId).css("opacity",0.1);
						
					}					
					$('#removeModal').modal('hide')	 
				}								
			
			
			
			
				//Load transccript, show the popup modal
				if ($(this).attr("id") == 'loadTranscript'){
					
					$('#loadTranscriptModal').modal('show')			
					
				}			
			
			
			
				//Process transccript buttons
				if ($(this).hasClass("loadTranscript")){					
				
					tr.resetUserRules();
					$("#structureInterviewees, #structureInterviewers, #structureRegexPattern, #structureIntervieweesSplit, #structureInterviewersSplit").val('');
					
					$('#structureIgnoreCountTest').attr('checked', false);
					
					tr.loadTranscriptControl($(this).val());
				}
				
				
				 
			
			})			
			
			///Misc binds
			
			//The drop down select in remove name modal
			$('#removeModalSameAs').bind('change', function(event) {		
				if ($(this).val() == 'Enter Name Manually'){
					$(this).css('display','none');
					$("#removeModalSameAsText").css('display','inline');					
					$("#removeModalSameAsText").focus();
				}
			})
			
			
			
			
			
			
			
			
		}
		
		
		
		
 		/////////////////////////////////////////////////////////////////////////
		//	Bind the dom made brlow, a workaround for some problems passing the dom though the popup handerls function
		//	
		tr.partialPopupContentBind = function(){
			
			$(".partialAprovalPopup").unbind();
			
			
			tr.partialAprovalAllFlag = false;
			
			$("#" + tr.partialAprovalDom[0]).change(function(){
				if ($(this).is(':checked')) {
					tr.partialAprovalAllFlag = true;					
				} else {
					tr.partialAprovalAllFlag = false;
				} 					
 			});
			
			for (x in tr.partialAprovalDom){
			
				if (x != 0){
				
					$("#" + tr.partialAprovalDom[x]).click(function(){						
 						//they assigned a role to this partial
						
						if (tr.partialAprovalAllFlag){
							
							//they want to change all of the occurances of this	
							for (i in tr.matches){
								
								if (tr.matches[i].type == 'partial'){
								 
									if (tr.matches[i].partial == tr.partialAprovalData[x].partial){
									
										 
										var use = $($(this).children()[1]).text();
										//i need to plan things out better...
										use = use.replace(' [Interviewee]','');
										use = use.replace(' [Interviewer]','');										
										
										//add it to the aproval obj
										var obj = {'id': tr.matches[i].id, 'partial' : tr.matches[i].partial, 'sentenceNumber' :  tr.matches[i].sentenceNumber, 'use' : use};
										
										//dupecheck
										var add = true;
										for (n in tr.userRules.partialAprovals){
											if (tr.userRules.partialAprovals[n].partial == tr.matches[i].partial && tr.userRules.partialAprovals[n].sentenceNumber == tr.matches[i].sentenceNumber){
												add = false;	
											}											
										}
										
										if (add){
											tr.userRules.partialAprovals.push(obj);
										}
										
										//update the text
										$("#" + tr.matches[i].id).text(use + "[reprocess to see change]");
										$("#" + tr.matches[i].id).css("background-color","#C6DBEF");
										$("#" + tr.matches[i].id).removeClass("partialMany").removeClass("partialMatch");
										$("#" + tr.matches[i].id).unbind();
										for (q in tr.timerObj){
											tr.timerObj[q].popover('hide');
										}										
										
										
										
									}
									
								}
								
							}
							
							
							
							
						}else{
							
							//single change

							var use = $($(this).children()[1]).text();
							//i need to plan things out better...
							use = use.replace(' [Interviewee]','');
							use = use.replace(' [Interviewer]','');								
							var obj = {'id': tr.partialAprovalData[x].id, 'partial' : tr.partialAprovalData[x].partial, 'sentenceNumber' :  tr.partialAprovalData[x].sentenceNumber, 'use' : use};
							//dupecheck
							var add = true;
							for (n in tr.userRules.partialAprovals){
								if (tr.userRules.partialAprovals[n].partial == tr.partialAprovalData[x].partial && tr.userRules.partialAprovals[n].sentenceNumber == tr.partialAprovalData[x].sentenceNumber){
									add = false;	
								}											
							}		
							if (add){
								tr.userRules.partialAprovals.push(obj);
							}
							
							//update the text
							$("#" + tr.partialAprovalData[x].id).text(use + "[reprocess to see change]");
							$("#" + tr.partialAprovalData[x].id).css("background-color","#C6DBEF");
							$("#" + tr.partialAprovalData[x].id).removeClass("partialMany").removeClass("partialMatch");
							$("#" + tr.partialAprovalData[x].id).unbind();
							for (q in tr.timerObj){
								tr.timerObj[q].popover('hide');
							}									
												
							
						}
						
						
						//remove them from the matches array so it does not come up again
						for (i in tr.userRules.partialAprovals){
							
							for (x in tr.matches){
							
								if (tr.userRules.partialAprovals[i].id == tr.matches[x].id){
									tr.matches.splice(x,1);
									break;
								}
								
							}
								
							
						}
						tr.buildPartialSidebar();
						tr.buildUserRulesForm();
						
 					});
					
				}
				
			}
			
			
			//the ignore this partial option
			
			
			$("#partialAprovalPopupIgnore").click(function(){	
			
				if (tr.partialAprovalAllFlag){
					
					for (i in tr.matches){
						
						if (tr.matches[i].type == 'partial'){
						 
							if (tr.matches[i].partial == tr.partialAprovalData[1].partial){
							
								 
								var use ='ignore'; 								
								
								//add it to the aproval obj
								var obj = {'id': tr.matches[i].id, 'partial' : tr.matches[i].partial, 'sentenceNumber' :  tr.matches[i].sentenceNumber, 'use' : use};
								
								//dupecheck
								var add = true;
								for (n in tr.userRules.partialAprovals){
									if (tr.userRules.partialAprovals[n].partial == tr.matches[i].partial && tr.userRules.partialAprovals[n].sentenceNumber == tr.matches[i].sentenceNumber){
										add = false;	
									}											
								}
								
								if (add){
									tr.userRules.partialAprovals.push(obj);
 								}
								
								//update the text
								$("#" + tr.matches[i].id).css("background-color","transparent");
								$("#" + tr.matches[i].id).removeClass("partialMany").removeClass("partialMatch");
								$("#" + tr.matches[i].id).unbind();
								for (q in tr.timerObj){
									tr.timerObj[q].popover('hide');
								}										
								
								
								
							}
							
						}
						
					}
					
						
					
				}else{
		
					//single change

					var use = 'ignore';
						
					var obj = {'id': tr.partialAprovalData[1].id, 'partial' : tr.partialAprovalData[1].partial, 'sentenceNumber' :  tr.partialAprovalData[1].sentenceNumber, 'use' : use};
					//dupecheck
					var add = true;
					for (n in tr.userRules.partialAprovals){
						if (tr.userRules.partialAprovals[n].partial == tr.partialAprovalData[1].partial && tr.userRules.partialAprovals[n].sentenceNumber == tr.partialAprovalData[1].sentenceNumber){
							add = false;	
						}											
					}		
					if (add){
						tr.userRules.partialAprovals.push(obj);
					}
					
					//update the text
 					$("#" + tr.partialAprovalData[1].id).css("background-color","transparent");
					$("#" + tr.partialAprovalData[1].id).removeClass("partialMany").removeClass("partialMatch");
					$("#" + tr.partialAprovalData[1].id).unbind();
					for (q in tr.timerObj){
						tr.timerObj[q].popover('hide');
					}						
					
				}
				
				//remove them from the matches array so it does not come up again
				for (i in tr.userRules.partialAprovals){
					
					for (x in tr.matches){
					
						if (tr.userRules.partialAprovals[i].id == tr.matches[x].id){
							tr.matches.splice(x,1);
							break;
						}
						
					}
						
					
				}
 				tr.buildPartialSidebar();
				tr.buildUserRulesForm();
			
			});
			
		}
		
 		/////////////////////////////////////////////////////////////////////////
		//	remove a rule from the JS object
		//	
		tr.partialPopupContent = function(domEle){
			
			
			for (x in tr.matches){
				if (tr.matches[x].id == domEle.attr("id")){
					var matchData = tr.matches[x];
				}
			}
			

			var allNames = tr.allNamesObject;

			content = $("<div>");
 
			tr.partialAprovalDom = [];
			tr.partialAprovalData = [];

			content.append(
				$("<input>")							
					.attr("type","checkbox")
					.addClass("partialAprovalPopup")
					.attr("id", matchData.id + "_checkbox")
					.css("margin-left","10px")

			); 
			
 
 			content.append(
				$("<label>")
					.text("Apply this choice to all '" + matchData.partial + "'")
					.attr("for", matchData.id + "_checkbox")
					.css("display","inline")
					.css("padding-left","5px")
			); 
			
			tr.partialAprovalDom.push(matchData.id + "_checkbox");
			tr.partialAprovalData.push("");
			
			for (x in allNames){
				
				tr.partialAprovalDom.push("personListItemSmall_" + x);
				tr.partialAprovalData.push(matchData);
				
				if (matchData.name.indexOf(allNames[x].name) != -1){
					
					
					if (allNames[x].authority.search('dbpedia') != -1 || allNames[x].authority.search('linkedjazz') != -1){
						var imageName = allNames[x].authority.split('/resource/')[1];
						imageName = imageName.substring(0,imageName.length-1) + '.png';
						
						if (imageNames.indexOf(imageName) != -1){
							imageName = imageNames[imageNames.indexOf(imageName)];
							imageName = '/network/img/' + imageName;
							
						}else{
							var imageName = 'img/no_image.png';	
						} 
					}else{
						var imageName = 'img/no_image.png';
					}
				
					if (allNames[x].authority == ""){
						imageName = "img/no_authority.png";
					}
					
					if (imageName == 'img/no_image.png' && allNames[x].authority.search('linkedjazz') != -1){
						imageName = "img/lj_image.png";	
					}
					if (imageName == 'img/no_image.png' && allNames[x].authority.search('id.loc.gov') != -1){
						imageName = "img/loc_image.png";	
					}						
			
					var useName  = allNames[x].name;
					 
					
					if (allNames[x].interviewer){
						useName = useName + ' [Interviewer]';
					}
					if (allNames[x].interviewee){
						useName = useName + ' [Interviewee]';						
					}					
							

					content.append(
						$("<div>")
							.addClass("personListItem")
							.addClass("partialAprovalPopup")
							.css("cursor","pointer")
							.attr("id","personListItemSmall_" + x)
							.css("width","200px")
							.data("test","Test")
							.data("info", allNames[x])
							//Add the image								
							.append(
								$("<img>")
									.attr("src",imageName)
							)				
							
							//add the name titile
							.append(
								$("<span>")
									.text(useName)
							)	
							
						 																	
						
					);

							
				}
				
			}
	
			//add the ignore option
			content.append(
				$("<div>")
					.addClass("personListItem")
					.addClass("partialAprovalPopup")
					.attr("id","partialAprovalPopupIgnore")
					.css("cursor","pointer")
					.css("width","226px")
					.css("padding-left","4px")
					.data("info", allNames[x])						
					.append(
						$("<i>")										
							.addClass("icon-remove")
							.css("font-size","18px") 
					)							
					.append(
						$("<span>")
							.text("Ignore this partial")
							.css("padding-left","5px")
					)	
			
			);
							
			
			
			return content;
			
			
		}
		
		/////////////////////////////////////////////////////////////////////////
		//	remove a rule from the JS object
		//	
		tr.removeUserRule = function(rule){

			for (var key in tr.userRules) {
				
 				if (typeof tr.userRules[key] == "object"){
					
					for (x in tr.userRules[key]){
					
 							if (tr.userRules[key][x] == rule){
								tr.userRules[key].splice(x,1);
								break;
							}
						
					}
						
				}
				
			}
			
			tr.buildUserRulesForm();

		}		
		
		
		/////////////////////////////////////////////////////////////////////////
		//	Builds the sidebar for partials
		//	
		tr.buildPartialSidebar = function(){
			
			
			function sortByCount(A, B) {
				return B.count - A.count;
			}	
						
			var countObj = {};
			
			for (x in tr.matches){
			
				if 	(tr.matches[x].type=='partial' && tr.matches[x].singlematch == false){
					if (countObj.hasOwnProperty(tr.matches[x].partial)){
						countObj[tr.matches[x].partial].count = countObj[tr.matches[x].partial].count + 1;
						countObj[tr.matches[x].partial].ids.push(tr.matches[x].id);
					}else{					
						countObj[tr.matches[x].partial] = {'count' : 1, 'ids' : [tr.matches[x].id], 'partial' :  tr.matches[x].partial};
					}
					
				}
				
			} 
			var countAry = [];
			
			for (var key in countObj) {				 
				countAry.push(countObj[key]);
			}
			
			countAry.sort(sortByCount);
			

		
			
			$("#contentListPartials").empty();
		
		
			for (x in countAry){
		 
				
					$("#contentListPartials")
						.append(
							$("<div>")
								.addClass("personListItem")
								.attr("id","partialListItem_" + x)
								.data("info", countAry[x])
 
								
								//add the name titile
								.append(
									$("<span>")
										.text(countAry[x].partial)
								)	
								
								.append(
									$("<div>")
										.addClass("personListItemControls")
										.append(
											$("<span>")										
												.text('[' + countAry[x].count + ']') 
										)												
										.append(
											$("<i>")										
												.addClass("icon-search") 
												.addClass("bindLinkClass")
												.addClass("partialSearch")
												.data("name", countAry[x].ids)												
												.data("activeId", "")
												.attr("title","Locate the partials in the document")
										)
								
	 																			
										
								)																						
							
						)
						 
				
					
					
				}			
			
			 
			tr.binds();
		}
		
		/////////////////////////////////////////////////////////////////////////
		//	Updates rule count
		//	
		tr.updateUserRulesCount = function(){
			$("#ruleCount").text(
				tr.userRules['ignoreLocal'].length +
				tr.userRules['otherName'].length + 
				tr.userRules['ignoreGlobal'].length +
				tr.userRules['sameAs'].length +
				tr.userRules['partialAprovals'].length +
				tr.userRules['authorityControl'].length				
				
			)
		}
		
		/////////////////////////////////////////////////////////////////////////
		//	Builds the user interface for the active rules
		//	
		tr.buildUserRulesForm = function(){
	 
	 
	 		$("#contentListRules label").unbind();
	 
	 		$("#contentListRules form").empty();
			
			if (tr.userRules['ignoreLocal'].length>0){$("#contentListRules form").append($("<label>").text("Ignore Names"))}
			
			for (x in tr.userRules['ignoreLocal']){
 
 				  $("#contentListRules form")
				  	.append(
						$("<input>")
							.val(tr.userRules['ignoreLocal'][x])
							.attr("type","text")
							.attr("disabled","disabled")							
					)
					.append(
						$("<i>")
							.addClass("icon-remove")
							.data("rule",tr.userRules['ignoreLocal'][x])
							.click(function(){tr.removeUserRule($(this).data("rule"))})				  
					)
  
			}
			for (x in tr.userRules['ignoreGlobal']){
 
 				  $("#contentListRules form")
				  	.append(
						$("<input>")
							.val(tr.userRules['ignoreGlobal'][x])
							.attr("type","text")
							.attr("disabled","disabled")							
					)
					.append(
						$("<i>")
							.addClass("icon-remove")
							.data("rule",tr.userRules['ignoreGlobal'][x])
							.click(function(){tr.removeUserRule($(this).data("rule"))})				  
					)
  
			}			
			if (tr.userRules['otherName'].length>0){$("#contentListRules form").append($("<label>").text("Other Names"))}
			
			for (x in tr.userRules['otherName']){
 
 				  $("#contentListRules form")
				  	.append(
						$("<input>")
							.val(tr.userRules['otherName'][x])
							.attr("type","text")
							.attr("disabled","disabled")							
					)
					.append(
						$("<i>")
							.addClass("icon-remove")
							.data("rule",tr.userRules['otherName'][x])
							.click(function(){tr.removeUserRule($(this).data("rule"))})				  
					)
  
			}	
			if (tr.userRules['manualNames'].length>0){$("#contentListRules form").append($("<label>").text("Manualy Added Names"))}
			
			for (x in tr.userRules['manualNames']){
 
 				  $("#contentListRules form")
				  	.append(
						$("<input>")
							.val(tr.userRules['manualNames'][x])
							.attr("type","text")
							.attr("disabled","disabled")							
					)
					.append(
						$("<i>")
							.addClass("icon-remove")
							.data("rule",tr.userRules['manualNames'][x])
							.click(function(){tr.removeUserRule($(this).data("rule"))})				  
					)
  
			}						
			if (tr.userRules['sameAs'].length>0){$("#contentListRules form").append($("<label>").text("Same As Rules"))}
			
			for (x in tr.userRules['sameAs']){
 
 				  $("#contentListRules form")
				  	.append(
						$("<input>")
							.val(tr.userRules['sameAs'][x].org + " == " + tr.userRules['sameAs'][x].sameAs)
							.attr("type","text")
							.attr("disabled","disabled")							
					)
					.append(
						$("<i>")
							.addClass("icon-remove")
							.data("rule",tr.userRules['sameAs'][x])
							.click(function(){tr.removeUserRule($(this).data("rule"))})				  
					)
  
			}
			
			if (tr.userRules['authorityControl'].length>0){$("#contentListRules form").append($("<label>").text("Authority Control"))}
			
			for (x in tr.userRules['authorityControl']){
 
 				  $("#contentListRules form")
				  	.append(
						$("<input>")
							.val(tr.userRules['authorityControl'][x].name + " == " + tr.userRules['authorityControl'][x].value)
							.attr("type","text")
							.attr("disabled","disabled")							
					)
					.append(
						$("<i>")
							.addClass("icon-remove")
							.data("rule",tr.userRules['authorityControl'][x])
							.click(function(){tr.removeUserRule($(this).data("rule"))})				  
					)
  
			}						
			
			$("#contentListRules form").append($("<hr>"));
			$("#contentListRules form").append($("<label>").text("Partial Name Mappings (click to expand)").css("cursor","pointer").attr("id","contentListRulesPartialNameTitle"))
			
			
			
			$("#contentListRules form").append($("<div>").attr("id",'contentListRulesPartialName'));
				
			for (x in tr.userRules.partialAprovals){
 
 				  $("#contentListRulesPartialName")
				  	.append(
						$("<input>")
							.val(tr.userRules.partialAprovals[x].sentenceNumber  + ": " + tr.userRules.partialAprovals[x].partial + " == " + tr.userRules.partialAprovals[x].use)
							.attr("type","text")
							.attr("disabled","disabled")							
					)
					.append(
						$("<i>")
							.addClass("icon-remove")
							.data("rule",tr.userRules.partialAprovals[x])
							.click(function(){tr.removeUserRule($(this).data("rule"))})	
					)
  
			}					
			$('#contentListRulesPartialName').collapse(); 
			$('#contentListRulesPartialNameTitle').click(function(){$('#contentListRulesPartialName').collapse('toggle')});			
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			$("#contentListRules form").append($("<hr>"));
			if (tr.globalIgnore != null){
				if (tr.globalIgnore.length>0){$("#contentListRules form").append($("<label>").text("GLOBAL Ignore Names (click to expand)").css("cursor","pointer").attr("id","contentListRulesGlobalIgnoreTitle"))}
			}
			
			
			$("#contentListRules form").append($("<div>").attr("id",'contentListRulesGlobalIgnore'));
				
			for (x in tr.globalIgnore){
 
 				  if (tr.globalIgnore[x]==''){continue;}
 				  $("#contentListRulesGlobalIgnore")
				  	.append(
						$("<input>")
							.val(tr.globalIgnore[x])
							.attr("type","text")
							.attr("disabled","disabled")							
					)
					.append(
						$("<i>")
							.addClass("icon-remove")
							.data("rule",tr.globalIgnore[x])
							.click(function(){tr.removeGlobalSetting('ignore',$(this).data("rule"))})				  
					)
  
			}					
			$('#contentListRulesGlobalIgnore').collapse(); 
			$('#contentListRulesGlobalIgnoreTitle').click(function(){$('#contentListRulesGlobalIgnore').collapse('toggle')});
	
	
	
			$("#contentListRules form").append($("<hr>"));
			if (tr.globalSameAs != null){
				if (tr.globalSameAs.length>0){$("#contentListRules form").append($("<label>").text("GLOBAL SameAs(click to expand)").css("cursor","pointer").attr("id","contentListRulesGlobalSameAsTitle"))}
			}
			
			
			$("#contentListRules form").append($("<div>").attr("id",'contentListRulesGlobalSameAs'));
				
			for (x in tr.globalSameAs){
 
 				  if (tr.globalSameAs[x]==''){continue;}
 				  $("#contentListRulesGlobalSameAs")
				  	.append(
						$("<input>")
							.val(tr.globalSameAs[x].split(',')[0] + '==' + tr.globalSameAs[x].split(',')[1])
							.attr("type","text")
							.attr("disabled","disabled")							
					)
					.append(
						$("<i>")
							.addClass("icon-remove")
							.data("rule",tr.globalSameAs[x])
							.click(function(){
								//remove it from the local var if it is there
								var value = $(this).data("rule");
								
								for (m in tr.userRules.sameAs){
									if (tr.userRules.sameAs[m].org == value.split(',')[0] && tr.userRules.sameAs[m].org == value.split(',')[0]){
										tr.userRules.sameAs.splice(m,1);	
									}
								}							
							
								tr.removeGlobalSetting('sameas',$(this).data("rule"))
							
							})				  
					)
  
			}					
			$('#contentListRulesGlobalSameAs').collapse(); 
			$('#contentListRulesGlobalSameAsTitle').click(function(){$('#contentListRulesGlobalSameAs').collapse('toggle')});	
	 
	
	
	
			$("#contentListRules form").append($("<hr>"));
			if (tr.globalAuthority != null){
				if (tr.globalAuthority.length>0){$("#contentListRules form").append($("<label>").text("GLOBAL Authority(click to expand)").css("cursor","pointer").attr("id","contentListRulesGlobalAuthorityTitle"))}
			}
			
			
			$("#contentListRules form").append($("<div>").attr("id",'contentListRulesGlobalAuthority'));
				
			for (x in tr.globalAuthority){
 
 				  if (tr.globalAuthority[x]==''){continue;}
 				  $("#contentListRulesGlobalAuthority")
				  	.append(
						$("<input>")
							.val(tr.globalAuthority[x].split(',')[0] + '==' + tr.globalAuthority[x].split(',')[1])
							.attr("type","text")
							.attr("disabled","disabled")							
					)
					.append(
						$("<i>")
							.addClass("icon-remove")
							.data("rule",tr.globalAuthority[x])
							.click(function(){
								//remove it from the local var if it is there
								var value = $(this).data("rule");
								
								for (m in tr.userRules.authorityControl){
									if (tr.userRules.authorityControl[m].name == value.split(',')[0] && tr.userRules.authorityControl[m].value == value.split(',')[1]){
										tr.userRules.authorityControl.splice(m,1);	
									}
								}							
							
								tr.removeGlobalSetting('authority',$(this).data("rule"))
							
							})				  
					)
  
			}					
			$('#contentListRulesGlobalAuthority').collapse(); 
			$('#contentListRulesGlobalAuthorityTitle').click(function(){$('#contentListRulesGlobalAuthority').collapse('toggle')});	
	 
	
	
	
	
	
	
		
	
	
	
	
	
	
	
			 //meta-structure
			 
			 $("#structureRegexPattern").val('');
			 $("#structureInterviewersSplit").val('');
			 $("#structureIntervieweesSplit").val('');
			 $("#structureInterviewers").val('');
			 $("#structureInterviewees").val('');
			 $('#structureIgnoreCountTest').attr('checked', false);
			 
			 if (tr.userRules['structureRegexPattern']!= ''){
				 $("#structureRegexPattern").val(tr.userRules['structureRegexPattern']);
			 }
			 if (tr.userRules['interviewersSplits'].length !=0){
				 for (x in tr.userRules['interviewersSplits']){
					$("#structureInterviewersSplit").val($("#structureInterviewersSplit").val() + tr.userRules['interviewersSplits'][x] + ',');
				 }
				 $("#structureInterviewersSplit").val($("#structureInterviewersSplit").val().substr(0,$("#structureInterviewersSplit").val().length-1));
			 }						 
			 if (tr.userRules['interviewersNames'].length !=0){
				 for (x in tr.userRules['interviewersNames']){
					$("#structureInterviewers").val($("#structureInterviewers").val() + tr.userRules['interviewersNames'][x] + ',');
				 }
				 $("#structureInterviewers").val($("#structureInterviewers").val().substr(0,$("#structureInterviewers").val().length-1));
			 }					 
			 if (tr.userRules['intervieweesSplits'].length !=0){
				 for (x in tr.userRules['intervieweesSplits']){
					$("#structureIntervieweesSplit").val($("#structureIntervieweesSplit").val() + tr.userRules['intervieweesSplits'][x] + ',');
				 }
				 $("#structureIntervieweesSplit").val($("#structureIntervieweesSplit").val().substr(0,$("#structureIntervieweesSplit").val().length-1));
			 }
			 if (tr.userRules['intervieweesNames'].length !=0){
				 for (x in tr.userRules['intervieweesNames']){
					$("#structureInterviewees").val($("#structureInterviewees").val() + tr.userRules['intervieweesNames'][x] + ',');
				 }
				 $("#structureInterviewees").val($("#structureInterviewees").val().substr(0,$("#structureInterviewees").val().length-1));
			 }					
			
			 if (tr.userRules['structureIgnoreCountTest'] != ''){
				
				if (tr.userRules['structureIgnoreCountTest']){
					$('#structureIgnoreCountTest').attr('checked', true);
				}else{
					$('#structureIgnoreCountTest').attr('checked', false);
				}
				 
			 }
			 
			
			
			tr.updateUserRulesCount();
		
		}
	 


		/////////////////////////////////////////////////////////////////////////
		//	remove a global setting
		//	
		tr.removeGlobalSetting = function(rule,value){			
			$.get('?json=removeGlobalSetting&rule=' + rule + '&value=' + value, function(data) {
				
				//also remove it from the js obj if it is stored locally as well
				tr.removeUserRule(value);
 				
				tr.loadGlobalIgnore();
				tr.loadGlobalSameAs(); 
				tr.loadGlobalAuthority();
			})
		}
	 
		/////////////////////////////////////////////////////////////////////////
		//	Load the list of global 
		//	
		tr.loadGlobalAuthority = function(){			
			$.get('data/globalAuthority.txt', function(data) {
				tr.globalAuthority = data.split("\n");
				tr.buildUserRulesForm();
			})
		}	
		/////////////////////////////////////////////////////////////////////////
		//	Load the list of global 
		//	
		tr.loadGlobalSameAs = function(){			
			$.get('data/globalSameAs.txt', function(data) {
				tr.globalSameAs = data.split("\n");
				tr.buildUserRulesForm();
			})
		}			 
		/////////////////////////////////////////////////////////////////////////
		//	Load the list of global ingore values to make them available in the rules editor
		//	
		tr.loadGlobalIgnore = function(){			
			$.get('data/globalIgnore.txt', function(data) {
				

				tr.globalIgnore = data.split("\n");
				tr.buildUserRulesForm();
				
			})
		}
		

		/////////////////////////////////////////////////////////////////////////
		//	Loads the sources of the transcript stored as an json obj on the server
		//	
		tr.loadSources = function(){

			$.get('data/transcriptSources.json', function(data) {
				
				tr.sources = data;
				
			})	
			
			
		}
		
		/////////////////////////////////////////////////////////////////////////
		//	loads the userrules back into the system, can happen if we are reprocessing a transcript that was previously being worked on
		//	also to allow the user to edit the rules after they have been added
		tr.loadUserRules = function(){
			
			if (tr.id){
			
				$.get('data/' +tr.id + '_userRules.json', function(data) {
					 
					 
					 
					 tr.resetUserRules();
					 
					 if (data.hasOwnProperty('ignoreLocal')){
						tr.userRules['ignoreLocal'] = data.ignoreLocal;	 
					 }

					 if (data.hasOwnProperty('ignoreGlobal')){
						tr.userRules['ignoreGlobal'] = data.ignoreGlobal;	 
					 }
					 if (data.hasOwnProperty('otherName')){
						tr.userRules['otherName'] = data.otherName;	 
					 }					 
					 if (data.hasOwnProperty('sameAs')){
						tr.userRules['sameAs'] = data.sameAs;	 
					 }	
					 if (data.hasOwnProperty('manualNames')){
						tr.userRules['manualNames'] = data.manualNames;	 
					 }						 					 
					 if (data.hasOwnProperty('intervieweesNames')){
						tr.userRules['intervieweesNames'] = data.intervieweesNames;	 
					 }	
					 
					 if (data.hasOwnProperty('intervieweesSplits')){
						tr.userRules['intervieweesSplits'] = data.intervieweesSplits;	 
					 }	
					 
					 if (data.hasOwnProperty('interviewersNames')){
						tr.userRules['interviewersNames'] = data.interviewersNames;	 
					 }	
					 
					 if (data.hasOwnProperty('interviewersSplits')){
						tr.userRules['interviewersSplits'] = data.interviewersSplits;	 
					 }
					 if (data.hasOwnProperty('structureRegexPattern')){
						tr.userRules['structureRegexPattern'] = data.structureRegexPattern;	 
					 }		
					 
					 if (data.hasOwnProperty('authorityControl')){
						tr.userRules['authorityControl'] = data.authorityControl;	 
					 }						 
					 
					 
					 
					 if (data.hasOwnProperty('structureIgnoreCountTest')){
						 if (data.structureIgnoreCountTest == 'true'){
							tr.userRules['structureIgnoreCountTest'] = true;	 	 
						 }else{
							tr.userRules['structureIgnoreCountTest'] = false;							 
						 }
						
					 }						 				 	
					 			
					 if (data.hasOwnProperty('partialAprovals')){
						tr.userRules['partialAprovals'] = data.partialAprovals;	 
					 }											 		 

					tr.buildUserRulesForm();
					
					 if (data.hasOwnProperty('publish')){
						 if (parseInt(data.publish['publish']) == 1){
							 
							$("#publishModalContentIntervieweeInput").text('Select Above');
							$("#publishFinal").addClass("disabled");
							$("#publishFinal").attr("disabled","disabled");
							$("#publishModalContentSourceInput").text('Select Above');
							
							var url = data.publish['intervieweeAuth'];
							url = url.split('/resource/')[1];
							url = url.replace('>','');
							
							$("#doneURL").attr("href",'http://linkedjazz.org/analyzer/' + url);
							
							$('#doneModal').modal('show');
							
							//post again to remove the publish settings so it doesnt auto-publish next time around
							$.post("?json=true&reid="+tr.id, tr.userRules, function(data){});							
							
							
						 }
					 }
					
					 
 			  
				});

			}
			
		}		
		
		/////////////////////////////////////////////////////////////////////////
		//	loads the status file and updates the progress bar
		//		
		tr.loadTranscriptStatusUpdate = function(){
			
			$.get('?json=true&statusUpdate=' + tr.activeFileName, function(data) {
				 
				 
				 
				if (typeof data == 'undefined' || typeof data.results == 'undefined'){
					return false;
				}
				 
				$("#statusBar").text(data.results.msg);
				$("#statusBar").css("width", Math.round(data.results.step / data.results.total * 100) + "%");
				
			});
			
			
		}
		
		
	
		/////////////////////////////////////////////////////////////////////////
		//	Kicks off all the requests to pull in the nessicary files 
		//		
		tr.loadTranscriptControl = function(fileName){
	
			$('#loadTranscriptModal').modal('hide')		
			$('#spinnerModal').modal('show')				
			
			$(".menuControl, .menuFunctions").addClass("disabled");
			$(".menuControl, .menuFunctions").attr("disabled", "disabled");			
			
			$(".alertGlow").removeClass("glow");
			 
			 
			tr.activeFileName = fileName;
			
			
			$(".workingFileName").text(fileName);
			
			$("#statusBar").text("");
			$("#statusBar").css("width", "2%");			
			var satusUpdateTimer = setInterval(tr.loadTranscriptStatusUpdate, 1000);

			
			var lockModalTimer = setInterval(function(){
				
				if (!$("#spinnerModal").hasClass('show')){
					$('#spinnerModal').modal('show')
				}
				
			}, 200);
			
			$.get('?json=true&processTranscript=' + fileName, function(data) {
				
				$('#spinnerModal').modal('hide')
				clearInterval(satusUpdateTimer);
				clearInterval(lockModalTimer);
				
				tr.id = data.results.id; 
				tr.loadGlobalIgnore();
				tr.loadGlobalSameAs(); 
				tr.loadGlobalAuthority();
				tr.loadSources();
				
				if (!data.results.error){

					tr.loadTranscriptText(); 						
					tr.loadTranscriptNames();
	
					
					$(".menuControl, .menuFunctions").removeClass("disabled");
					$(".menuControl, .menuFunctions").removeAttr("disabled");
					//$("#contentListNames").css("display","block");							
					$("#menuFunctionsNameControl").click();
					 
					tr.loadUserRules(); 
					
					tr.clean();
					
					
				}else{
					

					//the structure error means it could not detect some aspect of how the transcript is divided
					if (data.results.type == "Structure"){						

	
						$("#menuFunctionsStructure").removeClass("disabled");
						$("#menuFunctionsStructure").removeAttr("disabled");						
						$("#menuFunctionsStructure").click();
						$("#reprocess").removeClass("disabled");
						$("#reprocess").removeAttr("disabled");
						
						//we need to load the orginal text so the user can figure out the formating
						$.get('data/' + tr.activeFileName + '.txt', function(data) {
							
							
							
							data = data.replace(/\n/g, '<br/>');
							data = data.replace(/\s/g, '&nbsp;');
 							$("#contentDetailDisplay").css("visibility","visible");
							$("#contentDetailDisplay").html(data);
							
						});											
						
						$("#contentListStructure").css("display","block");
						

						alert("ERROR: Could not detect the meta-structure of the transcript.\n\n\n " + data.results.msg);
						
						if (data.results.msg =="Need Interviewers names"){
							$("#structureInterviewers").toggleClass('glow');							
						}
						if (data.results.msg =="Need Interviewee names"){
							$("#structureInterviewees").toggleClass('glow');							
						}												
						if (data.results.msg =="Need interviwee/interviewer Split text, could not assign roles to text."){
							$("#structureIntervieweesSplit,#structureInterviewersSplit").toggleClass('glow');							
						}						
						if (data.results.msg =="Need the regular expression split pattern"){
							$("#structureRegexPattern").toggleClass('glow');							
						}												
						
						
						
						
					}
						
				}
				
				
				
				
			})
			.error(function(data) { 
				alert("There was an error in processing the file.\n\n" + data.responseText); 
				$('#spinnerModal').modal('hide');
		
				$("#reprocess").removeClass("disabled");
				$("#reprocess").removeAttr("disabled");				
				clearInterval(satusUpdateTimer);
				clearInterval(lockModalTimer);
			});			
	
	
		}
		 
	
	
		/////////////////////////////////////////////////////////////////////////
		//	Loads the text of the transcript output by the python script
		//
		tr.loadTranscriptText = function(){
			
 			
			$.get('?json=true&loadTranscriptText=' + this.id, function(data) {
			

				$("#contentDetailDisplay").html(data.results.text);
				
				tr.binds();
				
				//loads the matches after the dom is there to work on
				tr.loadTranscriptMatches();
				tr.loadTranscriptOthers();
				
				
			});
			
			
			$("#contentDetailDisplay").css("visibility","visible");
			
			
			
		}
	
		/////////////////////////////////////////////////////////////////////////
		//	Loads the full and partial matches output as json file from the python script
		//
		tr.loadTranscriptMatches = function(){
			
			
			tr.matches = []
			
 			
			$.get('data/' + this.id + '_partials.json', function(data) {
			

				
				for (x in data){				
					tr.matches.push({ id: x, 'singlematch' :data[x].singleMatch,  name :  data[x].full, 'partial' : data[x].partial, sentenceNumber :data[x].sentenceNumber, type: 'partial'  });						
					
					if (data[x].full.length > 1){
						$("#" + x).addClass("partialMany");	
					}
 					
					if (!data[x].singleMatch){
						$("#" + x).addClass("partialMany");	
					}
					
					//check for empty partials. TODO fix the root of this...
					if (typeof tr.allNames != "undefined"){
					 
					 	if (tr.allNames.indexOf(data[x].full[0]) == -1){
							$("#" + x).removeClass("partialMany");	 	
							$("#" + x).removeClass("partialMatch");
							$("#" + x).removeAttr("rel");												
						}
					 	
					}
					
				}
				tr.buildPartialSidebar();
				
				
			});
			$.get('data/' + this.id + '_matches.json', function(data) {
			

				for (x in data){				
					tr.matches.push({ id: x, name :  [data[x].name], sentenceNumber : data[x].sentenceNumber, type: 'full'  });						
				}				
				 
				
			});			
			
			
			
		}	
		
		/////////////////////////////////////////////////////////////////////////
		//	Loads the Others, 
		//
		tr.loadTranscriptOthers = function(){
			
 			
			$.get('data/' + this.id + '_others.json', function(data) {
			  

				function sortByTF(A, B) {
					return B.confirmed - A.confirmed;
				}	

				tr.others = {};
				allNames = [];
				
				for (x in data){		
				 
				 
				 	if (tr.others.hasOwnProperty(data[x].name)){										
						tr.others[data[x].name].ids.push(x);												
					}else{					
						tr.others[data[x].name] = {'name': data[x].name, 'ids' : [x], 'confirmed' : data[x].confirmed};						
						
						allNames.push({'name': data[x].name,  'confirmed' : data[x].confirmed});
						
					}
				 	 				
										  
				} 
				 
				
				
				allNames.sort(sortByTF);
				
				$("#contentListOthers button").first().attr("title",tr.allNames.length + " possble names");
				

		 
				
				$("#listOthers").empty();
				 
				
				for (x in allNames){
		
					if (allNames[x].confirmed){	
						for (m in tr.others[allNames[x].name].ids){
							$("#" + tr.others[allNames[x].name].ids[m]).css("background-color","#e7cb94");							
						}		
					}
									
					if (allNames[x].confirmed == false && x != 0 && allNames[x-1].confirmed == true){
						$("#listOthers")
							.append(
								$("<hr>")													
							);
 							 
					}
				
					$("#listOthers")
						.append(
							$("<div>")
								.addClass("personListItem")
								.attr("id","personListItem_" + x)
								.data("info", allNames[x])
 
								
								//add the name titile
								.append(
									$("<span>")
										.text(allNames[x].name)
								)	
								
								.append(
									$("<div>")
										.addClass("personListItemControls")
										.append(
											$("<i>")										
												.addClass("icon-search") 
												.addClass("bindLinkClass")
												.data("name", allNames[x].name)												
												.data("activeId", "")
												.attr("title","Locate the other name in the document")
										)
										.append(
											$("<i>")										
												.addClass("icon-map-marker")											
												.addClass("bindLinkClass") 											
												.data("name", allNames[x].name)
												.data("activeId", "")
												.css("display", (allNames[x].confirmed) ? "none" : "inline")												
												.attr("title","Add this name to the Other names rule list")												
										) 														
										.append(
											$("<i>")										
												.addClass("icon-user")
												.addClass("otherToUser")	
												.addClass("bindLinkClass")
												.data("name", allNames[x].name)							
												.css("display", (allNames[x].confirmed) ? "none" : "inline")																											
												.attr("title","This is a person's name, add it to the manually added name list")												
										)																				
										
								)																						
							
						)
						 
				
					
					
				}
				 
				
				
				//rebind
				tr.binds();
				
			});
			
			
			
			
		}
				
		/////////////////////////////////////////////////////////////////////////
		//	Loads the names, 
		//
		tr.loadTranscriptNames = function(){
			
 			
			$.get('data/' + this.id + '_names.json', function(data) {
			 
				
				function sortByCount(personA, personB) {
					return personA.count - personB.count;
				}	
 
				var noAuthNames = [];
				var authNames = [];
				tr.allNames = [];
				
				for (x in data){		
				
					var interviewer = false;
					var interviewee = false;
				
					if (data[x].hasOwnProperty('interviewer')){
						interviewer = true;
					}
					if (data[x].hasOwnProperty('interviewee')){
						interviewee = true;
					}					
						
					if (data[x].authority == ''){
						noAuthNames.push({'name': x, 'authority': data[x].authority, 'count' : data[x].count, 'interviewee': interviewee, 'interviewer' : interviewer});
					}else{
						authNames.push({'name': x, 'authority': data[x].authority, 'count' : data[x].count, 'interviewee': interviewee, 'interviewer' : interviewer});						
					}
					
					tr.allNames.push(x);
										  
				}
				
				tr.allNames.sort();
				
				$("#contentListNames button").first().attr("title",tr.allNames.length + " possble names");
				
				//authNames.sort(sortByCount);
				
				
				var allNames = noAuthNames.concat(authNames);
				
				tr.allNamesObject = allNames;
				
				allNames.sort(function(a, b) {
					var textA = a.name.toUpperCase();
					var textB = b.name.toUpperCase();
					return (textA < textB) ? -1 : (textA > textB) ? 1 : 0;
				});
				
				
				
				$("#listNames").empty();
				 
				
				for (x in allNames){
					if (allNames[x].authority.search('dbpedia') != -1 || allNames[x].authority.search('linkedjazz') != -1){
						var imageName = allNames[x].authority.split('/resource/')[1];
						imageName = imageName.substring(0,imageName.length-1) + '.png';
						
						if (imageNames.indexOf(imageName) != -1){
							imageName = imageNames[imageNames.indexOf(imageName)];
							imageName = '/network/img/' + imageName;
							
						}else{
							var imageName = 'img/no_image.png';	
						} 
					}else{
						var imageName = 'img/no_image.png';
					}
				
					if (allNames[x].authority == ""){
						imageName = "img/no_authority.png";
					}
					
					if (imageName == 'img/no_image.png' && allNames[x].authority.search('linkedjazz') != -1){
						imageName = "img/lj_image.png";	
					}					
					if (imageName == 'img/no_image.png' && allNames[x].authority.search('id.loc.gov') != -1){
						imageName = "img/loc_image.png";	
					}						
					var useName  = allNames[x].name;
					 
					
					if (allNames[x].interviewer){
						useName = useName + ' [Interviewer]';
					}
					if (allNames[x].interviewee){
						useName = useName + ' [Interviewee]';						
					}					
					
				
					$("#listNames")
						.append(
							$("<div>")
								.addClass("personListItem")
								.attr("id","personListItem_" + x)
								.data("info", allNames[x])

								//Add the image								
								.append(
									$("<img>")
										.attr("src",imageName)
								)				
								
								//add the name titile
								.append(
									$("<span>")
										.text(useName)
								)	
								
								.append(
									$("<div>")
										.addClass("personListItemControls")
										.append(
											$("<i>")										
												.addClass("icon-chevron-left")
												.attr("title","Next Occurance  (" + allNames[x].count + " exact matches)")
												.addClass("bindLinkClass")
												.data("name", allNames[x].name)												
												.data("activeId", "")
												
											
										)
										.append(
											$("<i>")										
												.addClass("icon-chevron-right")											
												.addClass("bindLinkClass")
												.attr("title","Previous Occurance (" + allNames[x].count + " exact matches)")												
												.data("name", allNames[x].name)
												.data("activeId", "")												
										)				
										.append(
											$("<i>")										
												.addClass("icon-question-sign")	
												.addClass("bindLinkClass")
												.data("listId", "personListItem_" + x)
												.data("name", allNames[x].name)																						
										)																	
										.append(
											$("<i>")										
												.addClass("icon-globe")
												.data("listId", "personListItem_" + x)
												.data("data", allNames[x])
												
												.addClass("bindLinkClass")											
										)															
																			
										
								)																						
							
						)
						 
				
					
					
				}
				 
				
				
				//rebind
				tr.binds();
				
			});
			
			
			
			
		}	
		
		 
	
		$(document).ready(function($) {
			
			$.ajaxSetup({
			  cache: false
			});
			
			
			tr.binds();
			tr.resetUserRules();
			
			
			//set the height on the text display to the max
			$("#contentDetailDisplay, #contentList").css("height",$(window).height() - 60 + 'px');
			
			
			$(window).resize(function () { 				
				 $("#contentDetailDisplay, #contentList").css("height",$(window).height() - 60 + 'px');
			});
			
			
 			
		})
	
	
	 
	</script>
 
 
 
 
 
</head>
<body>

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
  		
        
            <div class="container-fluid">
     
                  <img src="img/logo.png" id="logo">   
    
                 <button class="btn btn-primary bindButtonClass" id="loadTranscript"><i class="icon-book"> </i>Load Transcript</button>
                
                                
             	
                
                
                <div style="float:right">
                	<button class="btn btn btn-inverse bindButtonClass menuControl disabled" id="reprocess" disabled="disabled"><i class="icon-repeat"> </i>Reprocess Transcript</button>
                    <button class="btn btn-success bindButtonClass menuControl disabled" id="publish" disabled="disabled"><i class="icon-ok-sign"> </i>Publish</button>
                </div>
                
                <div class="btn-group" data-toggle="buttons-radio" style="float:right; margin-right:150px;">
                  <button class="btn bindButtonClass menuFunctions" id="menuFunctionsNameControl" disabled><i class="icon-user"> </i>Names</button>
                  <button class="btn bindButtonClass menuFunctions" id="menuFunctionsPartialMatches" disabled><i class="icon-align-center"> </i>Partials</button>
                  <button class="btn bindButtonClass menuFunctions" id="menuFunctionsOtherNames" disabled><i class="icon-map-marker"> </i>Others</button>                  
                  <button class="btn bindButtonClass menuFunctions" id="menuFunctionsStructure" disabled><i class="icon-th-list"> </i>Meta-Structure</button>
                  <button class="btn bindButtonClass menuFunctions" id="menuFunctionsRules" disabled><i class="icon-wrench"> </i>Rules (<span id="ruleCount">0</span>)</button>                  
                </div>   
                                
            </div>        
        
        
      </div>
    </div>
 

<div class="container-fluid" style="margin-top:54px;">
    <div class="row-fluid">      
      <div class="span4" id="contentList">
      	
        <div id="contentListNames" class="contentListItem" style="display:none;">        				
            <div id="listNames"></div>            
			<button class="btn btn-mini bindButtonClass" style="margin-left:5px;" id="contentListAddName"><i class="icon-user"></i> Add Name Manually</button>                   
      	</div>
        
        <div id="contentListPartials" class="contentListItem" style="display:none;">        				
       	</div>        
        
        <div id="contentListOthers" class="contentListItem" style="display:none;">        				
            <div id="listOthers"></div>            
			<button class="btn btn-mini bindButtonClass" style="margin-left:5px;" id="contentListAddOther"><i class="icon-map-marker"></i> Add Other Name Manually</button>                   
      	</div>        
        
        <div id="contentListRules" class="contentListItem" style="display:none;">        				
        
        	<div>
                <form class="well">
                                
                     
                </form>      
            </div>        
        
        </div>
        
        
        
        <div id="contentListStructure" class="contentListItem" style="display:none;">        				
        
        	<div>
                <form class="well">
                  <label>Interviewees Names</label>
                  <input type="text" id="structureInterviewees" class="span12 alertGlow" placeholder="Name1,Name2,Name3">
      			   <hr>
                  <label>Interviewers Names</label>
                  <input type="text" id="structureInterviewers" class="span12 alertGlow" placeholder="Name1,Name2,Name3">      
   					<hr>
 
                  <label>Regular Expression Split Pattern</label>
                  <input type="text" id="structureRegexPattern" class="span12 alertGlow" placeholder="Example: \n[A|Dave:|MR.BLAH]\s"> 
                  <label class="checkbox">
                  <input type="checkbox" id="structureIgnoreCountTest"> Ignore count test.</label>
                  <hr>                    
                  <label>Interviewees Split</label>
                  <input type="text" id="structureIntervieweesSplit" class="span12 alertGlow" placeholder="Example: 'Smith:,Jones:,Dave:'">                          
   					<hr>
                  <label>Interviewers Split</label>
                  <input type="text" id="structureInterviewersSplit" class="span12 alertGlow" placeholder="Example: 'Smith:,Jones:,Dave:'">                                              
                    
   
                </form>      
            
            
            </div>
        
        </div>
        
      </div>
      <div class="span8" id="contentDetail" >
      	
        <div id="contentDetailDisplay"></div>
  
  
  
  
  
      
      </div>
    </div>
</div>
  
  
  
  
<div id="overlay">Processing Data</div>


<div class="modal hide" id="loadTranscriptModal">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3>Load a transcript</h3>
    
  </div>
  <div class="modal-body">
        
    <table class="table table-condensed table-striped">
      <thead>
        <tr>
          <th>Name</th>
          <th>Size</th>
          <th>Last Processed</th>      
          <th></th>      
        </tr>
      </thead>    
        <tbody>
    <?
	
		for ($i = 0; $i < count($pdfFilenames); ++$i) {
			
			 
			$transcriptLength = 'Unkown';

			if ($pdfFilesizes[$i] > 0){
				$transcriptLength = 'Short';
			}
			if ($pdfFilesizes[$i] > $pdfFilesizesMid){
				$transcriptLength = 'Medium';
			}			
			if ($pdfFilesizes[$i] > $pdfFilesizesLong){
				$transcriptLength = 'Long';
			}						
			if ($pdfFilesizes[$i] > $pdfFilesizesVLong){
				$transcriptLength = 'Very Long';
			}									
			
			$daysAgo = $pdfFileLastProcessed[$i];
			  
			//echo $pdfFilenames[$i] . " | " . $pdfWorkingOn[$i] . "<br>";
			$secAgo = '';	
			
			if ($pdfPublished[$i]){
				$secAgo = 'class="publishedPDF"';	
			}
						
			if ($pdfWorkingOn[$i] <= 800){
				$secAgo = 'class="workingOn"';	
	
			}
			

			
			
			if ($pdfFileLastProcessed[$i]!='Never'){
				$daysAgo = $daysAgo . " days ago";	
			}
			
			
			
        	echo "<tr $secAgo>" . "<td $secAgo>" . $pdfFilenames[$i] . "</td>" . "<td $secAgo>" . $transcriptLength . "</td>" . "<td $secAgo>" . $daysAgo . "</td>" . "<td $secAgo><button value=\"" . $pdfFilenames[$i] . "\" class=\"btn btn-mini loadTranscript bindButtonClass\">Load</button></td></tr>";
			
			
	    }
	
	
	?>
 
      </tbody>
    </table>

  </div>
  <div class="modal-footer">
    <a href="#" class="btn" data-dismiss="modal">Close</a>
   </div>
</div>    


<div class="modal hide" id="spinnerModal" style="width:550px">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3>Working...<span class="workingFileName"></span></h3>
  </div>
  <div class="modal-body" style="text-align:center">
	<img src="img/ljspinner.gif"><br><br>

    <span>If you are processing a transcript for the first time it may take a few minutes.</span>
  </div>
  <div class="modal-footer"> 
    <div class="progress progress-striped active">
      <div class="bar" id="statusBar" style="width: 0%;"></div>
    </div>  
   </div>
</div>   


<div class="modal hide" id="doneModal" style="width:550px">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3>Success!</h3>
  </div>
  <div class="modal-body" style="text-align:center">
 	
    <span>Sucessfuly processed the file and stored the relationship triples!</span>
    <a id="doneURL" href="#" target="_blank">View the results</a><br>
    <span>If this person does not yet have an image assoicated with them please <a href="/image/imagetool.php" target="_blank">select it.</a></span>
    
    
 
  </div>
  <div class="modal-footer"> 
  	<div style="float:right"><a href="#" class="btn btn-mini" data-dismiss="modal">Close</a></div>
  </div>
</div>   





<div class="modal hide" id="publishModal" style="width:550px">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3>Publish: <span class="workingFileName"></span></h3>
  </div>
  <div class="modal-body" style="text-align:left">
	
     
    
	<div id="publishModalContentInterviewee">
    <span>Select primary interviewee:</span>
	</div>
	<br>
	<br>
    <span>What is the source of this interview:</span>
	<div id="publishModalContentSource">
 	</div>    
    <br>
    <button class="btn btn-inverse btn-mini bindButtonClass" id="publishAddSource">Add New Source</button>
    <br>
	<hr>	
	<div style="text-align:left">
	    Interviewee: <span class="input-xlarge uneditable-input" id="publishModalContentIntervieweeInput">Select Above</span><br>
	    Source: <span class="input-xlarge uneditable-input" id="publishModalContentSourceInput">Select Above</span>     <br>
        <button class="btn btn-success bindButtonClass disabled" id="publishFinal" disabled="disabled"><i class="icon-ok-sign"> </i>Publish</button>   
    </div>
    
    
  </div>
  <div class="modal-footer"> 
  	<div style="float:right"><a href="#" class="btn btn-mini" data-dismiss="modal">Close</a></div>
   </div>
</div>   



<div class="modal hide" id="authModal" style="width:600px">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3>Authority For: <span id="authTitle"></span></h3>
  </div>
  <div class="modal-body" style="text-align:center" id="authModalContent"> 

	<div class="removeModalContentItem">    	
    	<i class="icon-globe" id="authModalAddGlobe" style="color:#1f77b4; cursor:pointer;" title="Enter the URI of a name authority to use such as wikipedia, musicbrainz, etc. Click globe to goto current authority URI"></i> 	        
	        <input class="span4" type="text" id="authModalAddText" placeholder="Enter Authority URI http://...">
    	    <button class="btn btn-large btn-primary bindButtonClass" id="authModalAdd"  title="Enter the URI of a name authority to use such as wikipedia, musicbrainz, etc">Ok</button>     
            
    </div>
	<div class="removeModalContentItem" style="height:200px;">   
    		<div class="pull-left" style="border-right: solid 1px; height:210px;"> 	
    	    <i class="icon-star-empty" style="color:#2ca02c; font-size:70px; padding-left:4px; padding-right:4px;" title="Create a authority for this name"></i> 
	        </div>
            <div class="pull-left" style="width:400px;" id="authModalCreateHolder"> 
            <input class="span5" type="text" placeholder="New URI Stem (eg: 'John_Smith')" id="authModalCreateText" title="The last part of the URI to be created">
            <input  class="span5" type="text" placeholder="URL to information source" id="authModalCreateTextAuth" title="">
            <textarea style="width:300px; margin-left:5px;" type="text"   placeholder="Source Notes" id="authModalCreateTextFree" title=""></textarea>
            <button class="btn btn-large btn-success bindButtonClass" id="authModalCreate"  title="Create a authority for this name">Ok</button>     
            
            
            </div>
            <br class="clear-fix">
             


    </div>    
 
  
  </div>
  <div class="modal-footer" style="text-align:left;"> 
  	<span><strong>Search</strong>:</span><span id="authModalSearchLinks"></span>
  	<div style="float:right"><a href="#" class="btn btn-mini" data-dismiss="modal">Close</a></div>
   </div>
</div>   











<div class="modal hide" id="removeModal" style="width:550px">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3>Remove Name: <span id="removeNameTitle"></span></h3>
  </div>
  <div class="modal-body" style="text-align:center" id="removeModalContent"> 

	<div class="removeModalContentItem">    	
    	<i class="icon-remove-sign" style="color:#900;" title="Ignore this name in the processing of this or all transcripts:"></i> 
	        <button class="btn btn-large bindButtonClass"  id="removeModalLocalIgnore" title="Ignore this name when processing this transcript">Ignore in this transcript</button>
    	    <button class="btn btn-large btn-warning bindButtonClass" id="removeModalGlobalIgnore"  title="Ignore this name when processing all transcripts, it should never be considered a name">Ignore in ALL transcripts</button>     
    </div>
    <br>
	<div class="removeModalContentItem">    	
    	<i class="icon-map-marker" style="color:#06C; padding-left:13px; padding-right:13px;" title="This is the name of a other name such as a Club or School, Record label that may be important:"></i> 
	        <button class="btn btn-large bindButtonClass" id="removeModalOtherName" title="This is the name of a other name such as a Club or School, Record label that may be important:">This is an Other Name</button>
    </div>    
 	<br>
	<div class="removeModalContentItem">    	
    	<i class="icon-random" style="color:#390; font-size:62px;" title=""></i> 
			  <select id="removeModalSameAs" class="bindMissClass" style="margin-bottom:35px; margin-left:40px;">
                <option>Select Name</option>
                <option>Enter Name Manually</option>
              </select>
             <input type="text"  style="margin-bottom:35px; margin-left:40px; display:none;" class="input-xlarge bindMissClass" placeholder="Manually Type Name" id="removeModalSameAsText">        
	        <button class="btn btn-large bindButtonClass" id="removeModalSameAsButton">Same As</button>
    </div>   

    
  </div>
  <div class="modal-footer" style="text-align:left;"> 
  	<span><strong>Search</strong>:</span><span id="removeModalSearchLinks"></span>
  	<div style="float:right"><a href="#" class="btn btn-mini" data-dismiss="modal">Close</a></div>
   </div>
</div>   
    
    
<div id="dirtyWarning">You must reprocess the transcript before your changes will be seen.   <span id="dirtyWarningUndo"><a href="#" id="undoLink" class="bindLinkClass">Click to Undo Last Action</a></span></div>
    
</body>
</html>
