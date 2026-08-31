<?php
     session_start();

    require_once __DIR__ . '/frame/poptavka-message.php';

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
    
    /**
     * Vypise zakaznikovi srozumitelnou chybu a ukonci beh.
     * Vzdy nabidne i telefon, at o poptavku neprijdeme kvuli technicke chybe.
     */
    function poptavkaChybovaStranka($nadpis, $popis, $kodOdpovedi = 400) {

        http_response_code($kodOdpovedi);

        echo '<!DOCTYPE html><html lang="cs"><head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . htmlspecialchars($nadpis, ENT_QUOTES, 'UTF-8') . ' | Dynal</title></head>';
        echo '<body style="font-family:Arial, Helvetica, sans-serif; margin:40px; color:#222222; background-color:#ffffff;">';
        echo '<h1 style="color:#c62c37; font-size:22px;">' . htmlspecialchars($nadpis, ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '<p>' . $popis . '</p>';
        echo '<p>Můžete nám také zavolat zdarma na <strong>800 888 848</strong> nebo napsat na ';
        echo '<a href="mailto:rakovnik@dynal.cz">rakovnik@dynal.cz</a> &ndash; rádi Vám nabídku připravíme.</p>';
        echo '<p><a href="/poptavka">Zpět na formulář</a></p>';
        echo '</body></html>';

        die();
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
            $whitelist = array('token','req-name','req-email', 'input-tel', 'vyber-produkt', 'new-text', 'URL-main','addURLS', 'curText', 'save-stuff', 'mult',
                               'misto-montaze', 'pos-typ', 'pos-sirka', 'pos-vyska', 'pos-barva', 'pos-osazeni', 'pos-poznamka',
                               'pos-demontaz', 'pos-montaz', 'pos-zednicke', 'pos-parapety', 'pos-zaluzie', 'pos-site');
            
            // Building an array with the $_POST-superglobal 
            foreach ($_POST as $key=>$item) {
                    
                    // Check if the value $key (fieldname from $_POST) can be found in the whitelisting array, if not, die with a short message to the hacker
            		if (!in_array($key, $whitelist)) {
            			
            			writeLog('Unknown form fields');
            			die("Hack-Attempt detected. Please use only the fields in the form");
            			
            		}
            }
            
 
            // SAVE INFO AS COOKIE, if user wants name and email saved
            
            $saveCheck = isset($_POST['save-stuff']) ? $_POST['save-stuff'] : '';
            if ($saveCheck == 'on') {
                setcookie("WRCF-Name", $_POST['req-name'], time()+60*60*24*365);
                setcookie("WRCF-Email", $_POST['req-email'], time()+60*60*24*365);
            }
                       
            // POVINNE UDAJE
            // Prohlizec je hlida nativne (required), ale na to se nelze spolehnout -
            // bez teto kontroly by odesla prazdna poptavka, se kterou obchod nic neudela.
            if (poptavkaHodnota($_POST, 'new-text') === '') {
                poptavkaChybovaStranka(
                    'Chybí popis poptávky',
                    'Napište nám prosím do formuláře, co poptáváte &ndash; okna, dveře nebo doplňky. Bez toho pro Vás nedokážeme připravit nabídku.'
                );
            }

            // PREPARE THE BODY OF THE MESSAGE

			$message = poptavkaZprava($_POST);


			//  MAKE SURE THE "FROM" EMAIL ADDRESS DOESN'T HAVE ANY NASTY STUFF IN IT

			$pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i";
            if (preg_match($pattern, trim(strip_tags($_POST['req-email'])))) {
                $cleanedFrom = trim(strip_tags($_POST['req-email']));
            } else {
                // Puvodne tu bylo "return", ktere ve hlavnim skriptu jen ukoncilo
                // beh a zakaznik uvidel prazdnou stranku.
                poptavkaChybovaStranka(
                    'Neplatná e-mailová adresa',
                    'Zadanou e-mailovou adresu se nám nepodařilo přečíst. Vraťte se prosím do formuláře a zkontrolujte ji.'
                );
            }
			
			
            
            
            //   CHANGE THE BELOW VARIABLES TO YOUR NEEDS
             
			$to = "rakovnik@dynal.cz, sklenar@dynal.cz";
			
			$subject = 'Poptavka DYNAL ';
			
			$headers = "From: " . $cleanedFrom . "\r\n";
			$headers .= "Reply-To: ". strip_tags($_POST['req-email']) . "\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            // Zavinac je tu zamerne: pripadne varovani z mail() by se vypsalo
            // do vystupu a znemoznilo by presmerovani nize. Rozhoduje navratova hodnota.
            $odeslano = @mail($to, $subject, $message, $headers);

            if ($odeslano) {
              header('Location: /odeslano.php');
              die();
            }

            // Poste se zpravu odeslat nepodarilo - dej to zakaznikovi vedet
            // a nabidni mu telefon, at o poptavku neprijdeme.
            poptavkaChybovaStranka(
                'Poptávku se nepodařilo odeslat',
                'Omlouváme se, došlo k technické chybě a Vaše poptávka k nám nedorazila.',
                500
            );
        
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
   <!-- Formular validuje prohlizec nativne (required / minlength) a odesila se
        klasickym POSTem. Puvodni pluginy jqTransform + jquery.validate byly
        odstraneny: obe verze z roku 2009 pouzivaji $.browser, ktere jQuery od
        verze 1.9 nema, takze na webu uz nefungovaly a formular nemel zadnou
        validaci ani presmerovani na odeslano.php. -->
   <script type="text/javascript" charset="utf-8" src="js/poptavka/poptavka-form.js?v=3108202601"></script>
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
                        <input type="text" id="req-name" name="req-name" minlength="2" maxlength="100" autocomplete="name" />
                     </div>
						
				  	 <!-- TELEFON -->
                     <div class="rowElem">
                        <div class="lblNazev"><label for="input-tel">Telefon:</label></div>
                        <input type="tel" id="input-tel" name="input-tel" maxlength="30" autocomplete="tel" />
                     </div>
                   
                     <!-- EMAIL --> 
                     <div class="rowElem">
                        <div class="lblNazev"><label for="req-email">E-mail: <span class="lbl-povinne">*</span></label></div>
                        <input type="email" id="req-email" name="req-email" class="required" required maxlength="150" autocomplete="email" />
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
	                    <div class="lblNazev"><label for="new-text">Co poptáváte? <span class="lbl-povinne">*</span></label></div>
	                    <div class="lblNapoveda"><p>Popište prosím co nejpodrobněji, o co máte zájem &ndash; okna, dveře, doplňky (parapety, žaluzie, sítě proti hmyzu), jejich počet, přibližné rozměry a barvu. Čím konkrétnější popis, tím rychleji Vám připravíme přesnou nabídku.</p></div>
  		                <textarea rows="12" id="new-text" name="new-text" class="required" required minlength="20" maxlength="3000" placeholder="Např.: Do rodinného domu potřebuji vyměnit 5 plastových oken a vchodové dveře. Okna cca 150 x 120 cm, bílá, včetně demontáže starých, montáže, parapetů a sítí proti hmyzu."></textarea>
                     </div>
                  </div>
               </div>

               <div class="clear"></div>

               <!-- SPECIFIKACE POZIC (nepovinná) -->
               <!-- Vychozi stav je rozbaleno, sbali to az JS - bez JS tak sekce zustane pouzitelna. -->
                  <div id="spec-panel">
                     <div class="odraz-10">
                        <a href="#" id="spec-toggle" class="spec-otevreno" role="button" aria-controls="spec-obsah" aria-expanded="true">
                           <span class="spec-toggle-ikona"></span>
                           <span class="spec-toggle-text">
                              <span class="spec-toggle-nadpis">Chcete nabídku rychleji a přesněji? Vyplňte rozměry jednotlivých pozic</span>
                              <span class="spec-toggle-popis">Nepovinné. Když nám rovnou napíšete rozměry a požadavky u každého okna či dveří, připravíme Vám konkrétní cenovou nabídku bez dalšího dotazování.</span>
                           </span>
                        </a>

                        <div id="spec-obsah">
                           <div class="spec-pozn"><p>Rozměr stačí změřit jako otvor ve zdi (od zdi ke zdi) &ndash; přesné zaměření provedeme my. Pokud si něčím nejste jistí, pole nechte prázdné.</p></div>
<?php for ($pozice = 1; $pozice <= POPTAVKA_MAX_POZIC; $pozice++) { ?>
                           <div class="spec-pozice<?php echo $pozice > 1 ? ' spec-pozice-skryta' : ''; ?>" id="spec-pozice-<?php echo $pozice; ?>">
                              <div class="spec-pozice-nadpis"><p>Pozice <?php echo $pozice; ?></p></div>

                              <div class="spec-radek">
                                 <div class="spec-pole spec-pole-typ">
                                    <label for="pos-typ-<?php echo $pozice; ?>">Otvor</label>
                                    <select id="pos-typ-<?php echo $pozice; ?>" name="pos-typ[<?php echo $pozice; ?>]">
                                       <option value="">&mdash; vyberte &mdash;</option>
                                       <option value="Okno">Okno</option>
                                       <option value="Balkonové dveře">Balkonové dveře</option>
                                       <option value="Vchodové dveře">Vchodové dveře</option>
                                       <option value="Jiné">Jiné</option>
                                    </select>
                                 </div>
                                 <div class="spec-pole spec-pole-rozmer">
                                    <label for="pos-sirka-<?php echo $pozice; ?>">Šířka (cm)</label>
                                    <input type="text" inputmode="numeric" id="pos-sirka-<?php echo $pozice; ?>" name="pos-sirka[<?php echo $pozice; ?>]" maxlength="10" />
                                 </div>
                                 <div class="spec-pole spec-pole-rozmer">
                                    <label for="pos-vyska-<?php echo $pozice; ?>">Výška (cm)</label>
                                    <input type="text" inputmode="numeric" id="pos-vyska-<?php echo $pozice; ?>" name="pos-vyska[<?php echo $pozice; ?>]" maxlength="10" />
                                 </div>
                                 <div class="spec-pole spec-pole-barva">
                                    <label for="pos-barva-<?php echo $pozice; ?>">Barva</label>
                                    <select id="pos-barva-<?php echo $pozice; ?>" name="pos-barva[<?php echo $pozice; ?>]">
                                       <option value="">&mdash; vyberte &mdash;</option>
                                       <option value="Bílá">Bílá</option>
                                       <option value="Jednostranná imitace dřeva">Jednostranná imitace dřeva</option>
                                       <option value="Oboustranná imitace dřeva">Oboustranná imitace dřeva</option>
                                       <option value="Jiná (uvedu v poznámce)">Jiná (uvedu v poznámce)</option>
                                    </select>
                                 </div>
                                 <div class="spec-pole spec-pole-osazeni">
                                    <label for="pos-osazeni-<?php echo $pozice; ?>">Šroubované / špaletové</label>
                                    <select id="pos-osazeni-<?php echo $pozice; ?>" name="pos-osazeni[<?php echo $pozice; ?>]">
                                       <option value="">&mdash; vyberte &mdash;</option>
                                       <option value="Šroubované">Šroubované</option>
                                       <option value="Špaletové">Špaletové</option>
                                       <option value="Nevím">Nevím</option>
                                    </select>
                                 </div>
                              </div>

                              <div class="spec-radek spec-radek-doplnky">
                                 <span class="spec-doplnky-nadpis">K této pozici požaduji:</span>
<?php foreach (poptavkaDoplnky() as $pole => $popisek) { ?>
                                 <label class="spec-doplnek"><input type="checkbox" name="<?php echo $pole; ?>[<?php echo $pozice; ?>]" value="ano" /> <span><?php echo $popisek; ?></span></label>
<?php } ?>
                              </div>

                              <div class="spec-radek">
                                 <div class="spec-pole spec-pole-poznamka">
                                    <label for="pos-poznamka-<?php echo $pozice; ?>">Poznámka</label>
                                    <input type="text" id="pos-poznamka-<?php echo $pozice; ?>" name="pos-poznamka[<?php echo $pozice; ?>]" maxlength="250" />
                                 </div>
                              </div>
                           </div>
<?php } ?>

                           <div id="spec-pridat-bg">
                              <a href="#" id="spec-pridat">+ Přidat další pozici</a>
                              <span id="spec-pridat-info">Zbývá pozic: <span id="spec-pridat-pocet"><?php echo POPTAVKA_MAX_POZIC - 1; ?></span></span>
                           </div>

                           <div class="spec-pole spec-pole-misto">
                              <label for="misto-montaze">Místo montáže (adresa / obec)</label>
                              <input type="text" id="misto-montaze" name="misto-montaze" maxlength="150" />
                           </div>

                        </div>
                     </div>
                  </div>

               <div class="clear"></div>

               <div id="form-odeslat">
                  <div class="odraz-10">
                     <input type="submit" class="btn-send" value="Odeslat poptávku" />
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
