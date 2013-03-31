
/*

Take this segment to use these AJAX functions
on the page.
<script language="javascript" src="ajax.js">
/*
This is your function documentation for reference:
query(name of element, url to query, add = true)
add(name of element, string to add)
set(name of element, string to set)
clear(name of element)
</script>

*/

function show(elName)
{
	var textSpot = id(elName);
	textSpot.style.visibility = "visible";
}

function hide(elName)
{
	var textSpot = id(elName);
	textSpot.style.visibility = "hidden";
}

function id(nameId)
{
	return document.getElementById(nameId);
}

function query(elName, url, addSet, clearSet) {
        var http_request = false;
		var textSpot = id(elName);

		

        if (window.XMLHttpRequest) { // Mozilla, Safari, ...
            http_request = new XMLHttpRequest();
            if (http_request.overrideMimeType) {
                http_request.overrideMimeType('text/xml');
                // See note below about this line
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_request = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_request = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }

        if (!http_request) {
            alert('Giving up :( Cannot create an XMLHTTP instance');
            return false;
        }
        http_request.onreadystatechange = function() { alertContents(http_request, elName, addSet, clearSet); };
        http_request.open('GET', url, true);
        http_request.send(null);

    }

function alertContents(http_request, elName, addSet, clearSet) {

        if (http_request.readyState == 4) {
            if (http_request.status == 200) {

				if (addSet)
				{
					add(elName, http_request.responseText);
				}
				else
				{
					set(elName, http_request.responseText);
					
					if (clearSet)
						setTimeout("clear('" + elName + "');", 5000);
				}
				
            } else {
                alert('There was a problem with the request.');
            }
        }
    }


function queryHTML(elName, url) {
        var http_request = false;
		var textSpot = id(elName);

		
        if (window.XMLHttpRequest) { // Mozilla, Safari, ...
            http_request = new XMLHttpRequest();
            if (http_request.overrideMimeType) {
                http_request.overrideMimeType('text/xml');
                // See note below about this line
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_request = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_request = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }

        if (!http_request) {
            alert('Giving up :( Cannot create an XMLHTTP instance');
            return false;
        }
        http_request.onreadystatechange = function() { alertContentsHTML(http_request, elName); };
        http_request.open('GET', url, true);
        http_request.send(null);

    }

function alertContentsHTML(http_request, elName) {

        if (http_request.readyState == 4) {
            if (http_request.status == 200) {

				setHTML(elName, http_request.responseText);
				
				if (null != clearSet)
						setTimeout("clear('" + elName + "');", 5000);
				
            } else {
                alert('There was a problem with the request.');
            }
        }
    }

function setHTML(elName, responseText)
{
	var textSpot = id('' + elName);
	textSpot.innerHTML = responseText;
}

function add(elName, newText)
{
	var textSpot = id('' + elName);
	textSpot.appendChild(document.createTextNode(newText));
}

function set(elName, newText)
{
	var textSpot = id('' + elName);
	clear(elName);
	add(elName, newText);
	
}

function clear(elName)
{
	var textSpot = id('' + elName);
	while (textSpot.firstChild != null)
	{
		textSpot.removeChild(textSpot.firstChild);
	}
}

function setPicture(picurl, linkurl)
{
	setHTML('loadindicator', 'Loading...');
	var image = id('mainpic');
	image.src = picurl;
	var link = id('imagelink');
	link.href = linkurl;
}