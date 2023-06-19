function checkAll(checkname, exby, boxname) {
    for (i = 0; i < checkname.elements.length; i++)
        if (checkname.elements[i].type == 'checkbox' &&
            boxname == checkname.elements[i].name) {
            checkname.elements[i].checked = exby.checked ? true : false
        }
}

var counter = 1;

function moreFields() {
// if multifields is present
    if (document.getElementById('multifields') == null)
        return false;
    counter++;
    var newFields = document.getElementById('multifields').cloneNode(true);
    newFields.id = '';
    newFields.style.display = 'block';
    var newField = newFields.childNodes;
    for (var i = 0; i < newField.length; i++) {
        var theName = newField[i].name;
        if (theName)
            newField[i].name = theName + counter;
    }
    var insertHere = document.getElementById('multifieldshere');
    insertHere.parentNode.insertBefore(newFields, insertHere);
}

function showRssJquery(feedUrl) {
    const maxEntries = 3;

    let result = "";

    $.ajax(feedUrl, {
        accepts: {
            xml: "application/rss+xml"
        },

        dataType: "xml",

        success: function (data) {
            $(data)
                .find("item")
                .slice(0, maxEntries)
                .each(function () {
                    const el = $(this);

                    const template = `
          <li class="block"><strong>${el.find("title").text()}</strong> [${el.find("pubDate").text()}]<br>
          <p>${el.find("description").text()}</p>
          <a href="${el.find("link").text()}" title="External Link: ${el.find("link").text()}" target="_blank">Read More &#187;</a></li>
        `;

                    result += template;
                });

            if (result !== '') {
                $("rss-feed").html(result);
            }
        }
    });
}

function showRss(feedUrl) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', feedUrl);

    xhr.onreadystatechange = function () {
        const DONE = 4;
        const OK = 200;
        let parser;
        let xmlDoc;
        if (xhr.readyState === DONE) {
            if (xhr.status === OK) {
                var result = "";
                if (window.DOMParser) {
                    parser = new DOMParser();
                    xmlDoc = parser.parseFromString(xhr.responseText, "text/xml");
                } else // Internet Explorer
                {
                    xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
                    xmlDoc.async = false;
                    xmlDoc.loadXML(xhr.responseText);
                }

                var maxCount = xmlDoc.getElementsByTagName("item") ? xmlDoc.getElementsByTagName("item").length : 0 ;
                if(maxCount === 0) {
                    return;
                }

                var items = xmlDoc.getElementsByTagName("item");

                for (var number = 0; number < Math.min(3, maxCount); ++number) {
                    const template = `
<h2>${items[number].getElementsByTagName("title")[0].childNodes[0].nodeValue}<br />
                <small>${items[number].getElementsByTagName("pubDate")[0].childNodes[0].nodeValue} &bull; <a href="${items[number].getElementsByTagName("link")[0].childNodes[0].nodeValue}" target="_blank">permalink</a></small></h2>
                <blockquote>${items[number].getElementsByTagName("description")[0].childNodes[0].nodeValue}</blockquote>`;
                    result += template;
                }

                document.getElementById("rss-feed").innerHTML = result;
            } else {
                console.log('Error: ' + xhr.status); // An error occurred during the request.
            }
        }
    };

    xhr.send(null);
}