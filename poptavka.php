<?php 
     session_start();
   
   function getRealIp() {
                         if (!empty($_SERVER['HTTP_CLIENT_IP'])) {  //check ip from share internet
                         $ip=$_SERVER['HTTP_CLIENT_IP'];
                         } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {  //to check ip is pass from proxy
                           $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
                         } else {
         $ip=$_SERVER['REMOTE_ADDR'];
       }
       return $ip;
    }

    function writeLog($where) {
    
    	$ip = getRealIp(); // Get the IP from superglobal
    	$host = gethostbyaddr($ip);    // Try to locate the host of the attack
    	$date = date("d M Y");
    	
    	// create a logging message with php heredoc syntax
    	$logging = <<<LOG
    		\n
    		<< Start of Message >>
    		There was a hacking attempt on your form. \n 
    		Date of Attack: {$date}
    		IP-Adress: {$ip} \n
    		Host of Attacker: {$host}
    		Point of Attack: {$where}
    		<< End of Message >>
LOG;
// Awkward but LOG must be flush left
    
            // open log file
    		if($handle = fopen('hacklog.log', 'a')) {
    		
    			fputs($handle, $logging);  // write the Data to file
    			fclose($handle);           // close the file
    			
    		} else {  // if first method is not working, for example because of wrong file permissions, email the data
    		
    			$to = 'info@dynal.cz';  
            	$subject = 'HACK ATTEMPT';
            	$header = 'From: info@zummo.cz';
            	if (mail($to, $subject, $logging, $header)) {
            		echo "Sent notice to admin.";
            	}
    
    		}
    }

    function verifyFormToken($form) {
        
        // check if a session is started and a token is transmitted, if not return an error
    	if(!isset($_SESSION[$form.'_token'])) { 
    		return false;
        }
    	
    	// check if the form is sent with token in it
    	if(!isset($_POST['token'])) {
    		return false;
        }
    	
    	// compare the tokens against each other if they are still the same
    	if ($_SESSION[$form.'_token'] !== $_POST['token']) {
    		return false;
        }
    	
    	return true;
    }
    
    function generateFormToken($form) {
    
        // generate a token from an unique value, took from microtime, you can also use salt-values, other crypting methods...
    	$token = md5(uniqid(microtime(), true));  
    	
    	// Write the generated token to the session variable to check it against the hidden field when the form is sent
    	$_SESSION[$form.'_token'] = $token; 
    	
    	return $token;
    }
    
    $form = 'form1';

    // VERIFY LEGITIMACY OF TOKEN ONLY WHEN THE FORM IS SUBMITTED
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyFormToken($form)) {
    
        // CHECK TO SEE IF THIS IS A MAIL POST
        if (isset($_POST['input-tel'])) {
        
            // Building a whitelist array with keys which will send through the form, no others would be accepted later on
            $whitelist = array('token','req-name','req-email', 'input-tel', 'vyber-produkt', 'new-text', 'URL-main','addURLS', 'curText', 'save-stuff', 'mult');
            
            // Building an array with the $_POST-superglobal 
            foreach ($_POST as $key=>$item) {
                    
                    // Check if the value $key (fieldname from $_POST) can be found in the whitelisting array, if not, die with a short message to the hacker
            		if (!in_array($key, $whitelist)) {
            			
            			writeLog('Unknown form fields');
            			die("Hack-Attempt detected. Please use only the fields in the form");
            			
            		}
            }
            
 
            // SAVE INFO AS COOKIE, if user wants name and email saved
            
            $saveCheck = $_POST['save-stuff'];
            if ($saveCheck == 'on') {
                setcookie("WRCF-Name", $_POST['req-name'], time()+60*60*24*365);
                setcookie("WRCF-Email", $_POST['req-email'], time()+60*60*24*365);
            }
                       
            // PREPARE THE BODY OF THE MESSAGE

			$message = '<html><body>';
			$message .= '<img src="https://www.dynal.cz/images/header/logo-dynal-desktop.png" alt="Dynal" />';
			$message .= '<table rules="all" style="border-color: #666;" cellpadding="10">';
			$message .= "<tr><td><strong>Jméno:</strong> </td><td>" . strip_tags($_POST['req-name']) . "</td></tr>";
			$message .= "<tr><td><strong>Email:</strong> </td><td>" . strip_tags($_POST['req-email']) . "</td></tr>";
			$message .= "<tr><td><strong>Telefon:</strong> </td><td>" . strip_tags($_POST['input-tel']) . "</td></tr>";
			$message .= "<tr><td><strong>Produkt:</strong> </td><td>" . strip_tags($_POST['vyber-produkt']) . "</td></tr>";

		    // $message .= "<tr><td><strong>URL To Change (main):</strong> </td><td>" . $_POST['URL-main'] . "</td></tr>";
			$addURLS = $_POST['addURLS'];
			if (($addURLS) != '') {
			    $message .= "<tr><td><strong>URL To Change (additional):</strong> </td><td>" . strip_tags($addURLS) . "</td></tr>";
			}
			$curText = htmlentities($_POST['curText']);           
			if (($curText) != '') {
			    $message .= "<tr><td><strong>CURRENT Content:</strong> </td><td>" . $curText . "</td></tr>";
			}
			$message .= "<tr><td><strong>Zpráva pro nás:</strong> </td><td>" . htmlentities($_POST['new-text']) . "</td></tr>";
			$message .= "</table>";
			$message .= "</body></html>";
			
			
			
			
			//  MAKE SURE THE "FROM" EMAIL ADDRESS DOESN'T HAVE ANY NASTY STUFF IN IT
			
			$pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i"; 
            if (preg_match($pattern, trim(strip_tags($_POST['req-email'])))) { 
                $cleanedFrom = trim(strip_tags($_POST['req-email'])); 
            } else { 
                return "The email address you entered was invalid. Please try again!"; 
            } 
			
			
            
            
            //   CHANGE THE BELOW VARIABLES TO YOUR NEEDS
             
			$to = "dynal@dynal.cz, sklenar@dynal.cz";
			
			$subject = 'Poptavka DYNAL ';
			
			$headers = "From: " . $cleanedFrom . "\r\n";
			$headers .= "Reply-To: ". strip_tags($_POST['req-email']) . "\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            if (mail($to, $subject, $message, $headers)) {
              echo 'VASE POPTAVKA BYLA V PORADKU ODESLANA. ';
            } else {
              echo 'VASE POPTAVKA NEBYLA ODESLANA.';
            }
            
            // DON'T BOTHER CONTINUING TO THE HTML...
            die();
        
        }
    } else {
        echo "Hack-Attempt detected. Got ya!.";
        writeLog('Formtoken');
        die();
    }
    }

?>

<!DOCTYPE html>
<html lang="cs">

<head>
   <meta content="cs" http-equiv="Content-Language">
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
      
   <title>Poptávka | Dynal</title>
   <meta name="description" content="Vážení zákazníci, jestliže máte jakýkoliv dotaz nebo zájem o naše produkty Zummo, neváhejte nás kontaktova. Vyplňte krátký formulář a naši proškolení obchodnící se Vám obratem ozvou.">
  
   <!-- HEAD -->
   <?php $path = $_SERVER['DOCUMENT_ROOT']; $path = "frame/head.php"; include_once($path); ?>

   <!-- POPTAVKA -->
   <script type="text/javascript" src="https://www.google.com/jsapi"></script>
   <script type="text/javascript">google.load("jquery", "1.3.2");</script>
   <script type="text/javascript" src="js/poptavka/jquery.jqtransform.js"></script>
   <script type="text/javascript" src="js/poptavka/jquery.validate.js"></script>
   <script type="text/javascript" src="js/poptavka/jquery.form.js"></script>
   <script type="text/javascript" src="js/poptavka/websitechange.js"></script>
</head>

<?php
// generate a new token for the $_SESSION superglobal and put them in a hidden field
$newToken = generateFormToken($form);   
?>

<body>
   <!-- HEADER -->
   <?php $path = $_SERVER['DOCUMENT_ROOT']; $path = "frame/header.php"; include_once($path); ?>
   
   <div id="navi-panel">
      <div class="wrapper">
         <div class="odraz-20">
            <div id="navi">
               <div class="navi-step-home"><a href="/"></a></div>
               <div class="navi-jump"></div>
               <div class="navi-step-last"><p>Poptávka</p></div>
            </div>
         </div>
      </div>
   </div>

   <div id="page">
      <div class="wrapper">
         <div class="odraz-20">
            <div id="page-nazev"><h1>Poptávka</h1></div>
            <div class="page-popis"><p>Vážení zákazníci, jestliže máte jakýkoliv dotaz nebo zájem o naše produkty, neváhejte nás kontaktova. Vyplňte krátký formulář a naši proškolení obchodnící se Vám obratem ozvou.</p></div> 
         </div>
      </div>
      
	  <div id="form-bg">
         <div class="wrapper">         
            <form action="poptavka.php" method="post" id="change-form">
            <input type="hidden" name="token" value="<?php echo $newToken; ?>">
               <div class="form-box-half">
                  <div class="odraz-10">
                     <!-- JMENO A PRIJMENI -->
	                 <div class="rowElem">
                        <div class="lblNazev"><label for="req-name">Jméno a příjmení:</label></div>
                        <input type="text" id="req-name" name="req-name" minlength="2" />
                     </div>
						
				  	 <!-- TELEFON -->
                     <div class="rowElem">
                        <div class="lblNazev"><label for="input-tel">Telefon:</label></div>
                        <input type="text" name="input-tel" />
                     </div>
                   
                     <!-- EMAIL --> 
                     <div class="rowElem">
                        <div class="lblNazev"><label for="req-email">E-mail:</label></div>
                        <input type="text" name="req-email" class="required email" />
                     </div>
                  </div>           
               </div>
                     
               <div class="form-box-half">
                  <div class="odraz-10">
                     <!-- VYBER -->
                     <div class="rowElem">
                        <div class="lblNazev"><label for="vyber-produkt">Vyberte produkt:</label></div>
                        <select name="vyber-produkt">
                           <option value="NO">Zatím jsem nevybral, potřebuji poradit</option>
                           <option value="PLAST">Plastová okna nebo dveře</option>
                           <option value="HLINIK">Hliníková okna nebo dveře</option>
                           <option value="ZAHRADA">Zimní zahrady</option>
                           <option value="PRISLUSENSTVI">Příslušenství</option>
                        </select>
                     </div>
                     
                     <!-- VZKAZ -->
	                 <div class="rowElem">
	                    <div class="lblNazev"><label for="new-text">Zpráva:</label></div>
  		                <textarea rows="12" name="new-text" minlength="4"></textarea>
                     </div>
                     <div class="rowElem">
		                <input type="submit" class="btn-send" value="Odeslat" />
                     </div>
                  </div>
               </div>
       	   	</form>
         </div>
      </div>
   </div>
   
   <!-- FOOTER -->
   <?php $path = $_SERVER['DOCUMENT_ROOT']; $path = "frame/footer.php"; include_once($path); ?>
</body>
</html>
