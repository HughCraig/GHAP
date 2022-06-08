<div class="disclaimer-div" name="disclaimer-div">
    <h4>Note:</h4>
    <p class="mb-2" name="dropdown-arrow" onclick="disclaimerDrop()">⮟ hide</p>
    <div class="" name="disclaimer-dropdown">
        <p>The focus of TLCMap is helping make open cultural information about places and times public.</p>
        <ul>
        <li>If a dataset is set to 'public' anyone will be able to see the data.
        </li><li>Do not upload sensitive or secret information.
        </li><li>The person contributing information or using TLCMap systems to process it retains responsibility for it, including its validity, maintenance, copyright and permissions. 
        </li><li>TLCMap information may be hidden or removed without notice. Retain your own copies and deposit research data in an official repository. 
        </li><li>By uploading your data to TLCMap you accept the <a href="/cou/" target="_blank">TLCMap Conditions Of Use</a>.
        </li>
        </ul>
    </div>
</div>
<script>
    function disclaimerDrop() {
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
</script>
