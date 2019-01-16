
//tourniquet sur certaine les icones admin
(function () {
    let cont = document.querySelectorAll('.tourniquet');

    let spinin = function () {
        let i = this.querySelector('.img-thumbnail');

        let faspin = i.classList.contains('fa-spin');
        if ( !faspin) {
            i.classList.add('fa-spin');
        }
    };

    let spinout = function () {
        let i = this.querySelector('.img-thumbnail');

        let faspin = i.classList.contains('fa-spin');
        if (faspin) {
            i.classList.remove('fa-spin');
        }
    };
    for (let p = 0; p < cont.length; p++) {

        let c = cont[p];

        c.addEventListener('mouseenter', spinin);
        c.addEventListener('mouseleave', spinout);
    }
})();
// fermeture automatiqur des flash alert en fadeout

(function () {
    let cont = document.querySelector('div.alert.accueil');
    cont.classList.add('alertFade');
    setTimeout(function () {
        cont.classList.add('alertFadeIn');

        setTimeout(function () {
            cont.classList.remove('alertFadeIn')
            setTimeout(function () {
                cont.remove()
            }, 500);
        }, 2500);
    }, 500);

})();
