<!DOCTYPE html>
<html lang="cs">

<head>
   <meta content="cs" http-equiv="Content-Language">
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

   <title>Blog | Dynal</title>
   <meta name="description" content="Novinky a aktuality z firmy Dynal s.r.o. — výrobce plastových a hliníkových oken, dveří a zimních zahrad.">

   <!-- HEAD -->
   <?php $path = $_SERVER['DOCUMENT_ROOT']; $path = "../frame/head.php"; include_once($path); ?>
</head>

<body>
   <!-- HEADER -->
   <?php $path = $_SERVER['DOCUMENT_ROOT']; $path = "../frame/header.php"; include_once($path); ?>

   <div id="navi-panel">
      <div class="wrapper">
         <div class="odraz-20">
            <div id="navi">
               <div class="navi-step-home"><a href="/"></a></div>
               <div class="navi-jump"></div>
               <div class="navi-step-last"><p>Blog</p></div>
            </div>
         </div>
      </div>
   </div>

   <div id="page">
      <div class="wrapper">
         <div class="odraz-20">
            <div id="page-nazev"><h1>Blog</h1></div>
            <div class="page-popis">
               <!-- Zde doplňte články — každý článek jako samostatný soubor /blog/nazev-clanku.php -->
            </div>
         </div>
      </div>
   </div>

   <!-- FOOTER -->
   <?php $path = $_SERVER['DOCUMENT_ROOT']; $path = "../frame/footer.php"; include_once($path); ?>
</body>
</html>
