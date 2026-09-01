<?php
/**
 * Sestavení HTML zprávy pro e-mail s poptávkou.
 *
 * Odděleno od poptavka.php, aby se dal obsah e-mailu ověřit bez odesílání pošty.
 */

/** Kolik pozic (řádků specifikace) formulář nabízí. */
if (!defined('POPTAVKA_MAX_POZIC')) {
    define('POPTAVKA_MAX_POZIC', 6);
}

/** Zaškrtávací pole u jedné pozice a jejich popisky v pořadí, v jakém se vypisují. */
function poptavkaDoplnky()
{
    return array(
        'pos-demontaz' => 'Demontáž starého',
        'pos-montaz'   => 'Montáž nového',
        'pos-zednicke' => 'Zednické začištění',
        'pos-parapety' => 'Parapety',
        'pos-zaluzie'  => 'Žaluzie',
        'pos-site'     => 'Síť proti hmyzu',
    );
}

/**
 * Bezpečně vytáhne jednu hodnotu z odeslaných dat.
 *
 * Text se záměrně needituje (žádné strip_tags) – zákazník může napsat
 * třeba "rozměr <150 cm" a strip_tags by mu takový text smazal.
 * Proti HTML se text ošetřuje až při vykreslení pomocí poptavkaEsc().
 */
function poptavkaHodnota(array $post, $pole, $index = null)
{
    $hodnota = isset($post[$pole]) ? $post[$pole] : '';

    if ($index !== null) {
        if (!is_array($hodnota) || !isset($hodnota[$index])) {
            return '';
        }
        $hodnota = $hodnota[$index];
    }

    if (!is_string($hodnota)) {
        return '';
    }

    return trim($hodnota);
}

/** Vrátí jen ty pozice, u kterých klient něco vyplnil. */
function poptavkaPolozky(array $post)
{
    $polozky = array();

    for ($i = 1; $i <= POPTAVKA_MAX_POZIC; $i++) {
        $polozka = array(
            'typ'      => poptavkaHodnota($post, 'pos-typ', $i),
            'sirka'    => poptavkaHodnota($post, 'pos-sirka', $i),
            'vyska'    => poptavkaHodnota($post, 'pos-vyska', $i),
            'barva'    => poptavkaHodnota($post, 'pos-barva', $i),
            'osazeni'  => poptavkaHodnota($post, 'pos-osazeni', $i),
            'poznamka' => poptavkaHodnota($post, 'pos-poznamka', $i),
            'doplnky'  => array(),
        );

        $maDoplnek = false;
        foreach (poptavkaDoplnky() as $pole => $popisek) {
            $zaskrtnuto = poptavkaHodnota($post, $pole, $i) !== '';
            $polozka['doplnky'][$pole] = $zaskrtnuto;
            $maDoplnek = $maDoplnek || $zaskrtnuto;
        }

        $maText = $polozka['typ'] !== '' || $polozka['sirka'] !== '' || $polozka['vyska'] !== ''
            || $polozka['barva'] !== '' || $polozka['osazeni'] !== '' || $polozka['poznamka'] !== '';

        if ($maText || $maDoplnek) {
            $polozky[$i] = $polozka;
        }
    }

    return $polozky;
}

/**
 * Zakóduje tělo e-mailu tak, aby prošlo SMTP.
 *
 * Tady byla chyba, kvůli které přestaly docházet poptávky se vyplněnou
 * specifikací: HTML se skládá do jednoho dlouhého řádku, ale SMTP
 * (RFC 5321) povoluje nejvýše 998 znaků na řádek. Bez tabulky pozic měla
 * zpráva ~690 znaků a prošla, s tabulkou přes 3 500 a poštovní server ji
 * zahodil. Quoted-printable láme řádky na 76 znaků, takže na délce ani
 * obsahu už nezáleží. Zároveň se tím korektně přenesou české znaky, které
 * dosud šly jako 8bit bez deklarovaného kódování.
 *
 * Kdo tuhle funkci použije, MUSÍ do hlaviček přidat:
 *     Content-Transfer-Encoding: quoted-printable
 */
function poptavkaTeloProSmtp($html)
{
    return quoted_printable_encode($html);
}

/**
 * Přeloží kód z výběru produktu na čitelný popis.
 * Musí odpovídat hodnotám v selectu "vyber-produkt" v poptavka.php.
 */
function poptavkaProdukt($kod)
{
    $produkty = array(
        'NO'            => 'Zatím nevybral, potřebuje poradit',
        'PLAST'         => 'Plastová okna nebo dveře',
        'HLINIK'        => 'Hliníková okna nebo dveře',
        'ZAHRADA'       => 'Zimní zahrady',
        'PRISLUSENSTVI' => 'Příslušenství',
    );

    return isset($produkty[$kod]) ? $produkty[$kod] : $kod;
}

/** Ošetří text pro vložení do HTML e-mailu. */
function poptavkaEsc($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/** Buňka tabulky s pomlčkou místo prázdna, aby byl e-mail čitelný. */
function poptavkaBunka($text, $zarovnani = 'left')
{
    $obsah = $text === '' ? '&ndash;' : poptavkaEsc($text);

    return '<td style="border:1px solid #cccccc; text-align:' . $zarovnani . ';">' . $obsah . '</td>';
}

/** Vykreslí tabulku vyplněných pozic ve stejné podobě, jakou používá obchod. */
function poptavkaTabulka(array $polozky)
{
    if (empty($polozky)) {
        return '';
    }

    $sloupce = array(
        'Č.', 'Otvor', 'Šířka (cm)', 'Výška (cm)', 'Barva', 'Šroubované / špaletové',
    );
    foreach (poptavkaDoplnky() as $popisek) {
        $sloupce[] = $popisek;
    }
    $sloupce[] = 'Poznámka';

    $html = '<table cellpadding="6" cellspacing="0" border="1"'
        . ' style="border-collapse:collapse; border:1px solid #cccccc;'
        . ' font-family:Arial, Helvetica, sans-serif; font-size:11px; color:#222222;">';

    $html .= '<tr style="background-color:#2c3e50;">';
    foreach ($sloupce as $sloupec) {
        $html .= '<td style="border:1px solid #cccccc; color:#ffffff; font-weight:bold; text-align:center;">'
            . poptavkaEsc($sloupec) . '</td>';
    }
    $html .= '</tr>';

    foreach ($polozky as $cislo => $polozka) {
        $html .= '<tr>';
        $html .= '<td style="border:1px solid #cccccc; text-align:center;">' . (int) $cislo . '</td>';
        $html .= poptavkaBunka($polozka['typ']);
        $html .= poptavkaBunka($polozka['sirka'], 'center');
        $html .= poptavkaBunka($polozka['vyska'], 'center');
        $html .= poptavkaBunka($polozka['barva']);
        $html .= poptavkaBunka($polozka['osazeni']);
        foreach (poptavkaDoplnky() as $pole => $popisek) {
            $html .= '<td style="border:1px solid #cccccc; text-align:center;">'
                . ($polozka['doplnky'][$pole] ? '<strong>ANO</strong>' : '&ndash;') . '</td>';
        }
        $html .= poptavkaBunka($polozka['poznamka']);
        $html .= '</tr>';
    }

    $html .= '</table>';

    return $html;
}

/** Sestaví celé tělo e-mailu s poptávkou. */
function poptavkaZprava(array $post)
{
    $radky = array(
        'Jméno'       => poptavkaHodnota($post, 'req-name'),
        'Email'       => poptavkaHodnota($post, 'req-email'),
        'Telefon'     => poptavkaHodnota($post, 'input-tel'),
        'Produkt'     => poptavkaProdukt(poptavkaHodnota($post, 'vyber-produkt')),
        'Místo montáže' => poptavkaHodnota($post, 'misto-montaze'),
    );

    // Bila barva pozadi je tu zamerne: bez ni si e-mailovy klient v tmavem
    // rezimu podlozi zpravu tmave a tmavy text na ni neni videt.
    $message = '<html><body style="font-family:Arial, Helvetica, sans-serif; color:#222222; background-color:#ffffff;">';
    $message .= '<img src="https://www.dynal.cz/images/header/logo-dynal-desktop.png" alt="Dynal" />';
    $message .= '<table rules="all" style="border-color: #666;" cellpadding="10">';

    foreach ($radky as $popisek => $hodnota) {
        if ($hodnota === '') {
            continue;
        }
        $message .= '<tr><td><strong>' . poptavkaEsc($popisek) . ':</strong> </td><td>'
            . poptavkaEsc($hodnota) . '</td></tr>';
    }

    $zprava = poptavkaHodnota($post, 'new-text');
    $message .= '<tr><td><strong>Zpráva pro nás:</strong> </td><td>'
        . nl2br(poptavkaEsc($zprava)) . '</td></tr>';
    $message .= '</table>';

    $polozky = poptavkaPolozky($post);
    if (!empty($polozky)) {
        $message .= '<h3 style="font-family:Arial, Helvetica, sans-serif; font-size:15px; color:#222222;">'
            . 'Specifikace jednotlivých pozic</h3>';
        $message .= poptavkaTabulka($polozky);
        $message .= '<p style="font-family:Arial, Helvetica, sans-serif; font-size:11px; color:#777777;">'
            . 'Rozměry zadal klient jako otvor ve zdi (od zdi ke zdi), údaje je před nabídkou potřeba ověřit zaměřením.</p>';
    }

    $message .= '</body></html>';

    return $message;
}
