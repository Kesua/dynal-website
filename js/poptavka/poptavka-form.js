/**
 * Poptavkovy formular - rozbalovani nepovinne specifikace pozic
 * a ceske hlasky k nativni validaci prohlizece.
 *
 * Zamerne bez jQuery a bez jakychkoli pluginu: formular musi fungovat
 * i kdyz nejaky skript na strance spadne. Specifikace je proto v HTML i CSS
 * rozbalena a sbaluje ji az tento skript - kdyz nedojede, zustane cela sekce
 * videt a pouzitelna.
 */
(function () {
    'use strict';

    function pripravSpecifikaci() {
        var toggle = document.getElementById('spec-toggle');
        var obsah = document.getElementById('spec-obsah');

        if (!toggle || !obsah) { return; }

        /* Vychozi stav v HTML je rozbaleno (kvuli fungovani bez JS),
           takze sekci sbalime teprve tady. */
        obsah.style.display = 'none';
        toggle.className = 'spec-zavreno';
        toggle.setAttribute('aria-expanded', 'false');

        toggle.addEventListener('click', function (event) {
            event.preventDefault();

            var otevreno = toggle.className.indexOf('spec-otevreno') !== -1;

            toggle.className = otevreno ? 'spec-zavreno' : 'spec-otevreno';
            toggle.setAttribute('aria-expanded', otevreno ? 'false' : 'true');
            obsah.style.display = otevreno ? 'none' : 'block';
        });

        var pridat = document.getElementById('spec-pridat');
        var pridatBg = document.getElementById('spec-pridat-bg');
        var pocet = document.getElementById('spec-pridat-pocet');

        if (!pridat) { return; }

        pridat.addEventListener('click', function (event) {
            event.preventDefault();

            /* Dalsi pozice jsou uz v HTML, jen skryte - nic neklonujeme,
               takze neni co dodatecne inicializovat. */
            var skryte = obsah.querySelectorAll('.spec-pozice-skryta');

            if (skryte.length) {
                var dalsi = skryte[0];
                dalsi.className = dalsi.className.replace('spec-pozice-skryta', 'spec-pozice-nova');

                var prvniPole = dalsi.querySelector('select, input');
                if (prvniPole) { prvniPole.focus(); }
            }

            var zbyva = obsah.querySelectorAll('.spec-pozice-skryta').length;
            if (pocet) { pocet.textContent = String(zbyva); }
            if (zbyva === 0 && pridatBg) { pridatBg.style.display = 'none'; }
        });
    }

    /**
     * Nativni hlasky prohlizece jsou v jazyce prohlizece, ne webu.
     * U nejdulezitejsiho pole proto poradime cesky a konkretne.
     */
    function ceskeHlasky() {
        var hlasky = [
            {
                selektor: '[name="new-text"]',
                prazdne: 'Napište nám prosím, co poptáváte – okna, dveře nebo doplňky.',
                kratke: 'Popište prosím svůj požadavek podrobněji, ať pro Vás můžeme připravit přesnou nabídku.'
            },
            {
                selektor: '[name="req-email"]',
                prazdne: 'Vyplňte prosím e-mail, ať Vám máme kam poslat nabídku.',
                kratke: 'Zadejte prosím e-mail ve tvaru jmeno@domena.cz.'
            }
        ];

        for (var i = 0; i < hlasky.length; i++) {
            (function (nastaveni) {
                var pole = document.querySelector('#change-form ' + nastaveni.selektor);
                if (!pole || !pole.setCustomValidity) { return; }

                pole.addEventListener('invalid', function () {
                    pole.setCustomValidity('');
                    if (pole.validity.valid) { return; }

                    pole.setCustomValidity(pole.validity.valueMissing ? nastaveni.prazdne : nastaveni.kratke);
                });

                pole.addEventListener('input', function () {
                    pole.setCustomValidity('');
                });
            }(hlasky[i]));
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        pripravSpecifikaci();
        ceskeHlasky();
    });
}());
