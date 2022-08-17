/**
 * Check whether the function is already defined as mulitple disclaimers can be loaded in one single page.
 *
 * This eventually can be improved by the 'once' directive if the laravel version is upgraded to 7.2+.
 */
if (typeof disclaimerDrop !== 'function') {
    window.disclaimerDrop = function () {
        var ds = document.getElementsByName("disclaimer-div");
        for (var i=0; i<ds.length; i++) {
            var x = ds[i].getElementsByTagName("div")[0];
            var arrow = ds[i].getElementsByTagName("p")[0];
            if (x.className.indexOf(" hidden") == -1) {
                x.className += " hidden";
                arrow.innerHTML = "⮞ show"
            }
            else {
                x.className = x.className.replace(" hidden", "");
                arrow.innerHTML = "⮟ hide"
            }
        }
    }
}
