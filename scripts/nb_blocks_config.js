﻿download = function (e)
{
    if (blockeditor != undefined)
    {
        var senddata = { notebook: blockeditor.serialize() };
        var url = "ajax/downloadNotebook.php";
        // Based on https://nehalist.io/downloading-files-from-post-requests/

        var request = new XMLHttpRequest();
        request.open('POST', url, true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        request.responseType = 'blob';

        request.onload = function ()
        {
            // Only handle status code 200
            if (request.status === 200)
            {
                // Try to find out the filename from the content disposition `filename` value
                var disposition = request.getResponseHeader('content-disposition');
                var matches = /"([^"]*)"/.exec(disposition);
                var filename = (matches != null && matches[1] ? matches[1] : 'file.ipynb');

                // The actual download
                var blob = new Blob([request.response], { type: 'application/x-ipynb+json' });
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;

                document.body.appendChild(link);

                link.click();

                document.body.removeChild(link);
            }

            // some error handling should be done here...
        };
        data = new FormData();
        for (key in senddata)
            data.append(key, senddata[key]);
        request.send(data);
    }
    else
    {
        alert("Unable to save and download. (blockeditor is undefined.)");
    }
}
fullreset = function (e)
{
    alert("Fullreset needs implemented.");
}

var config = {};
config.buttons = {
    save: "<img src='ErysIcons/save.png' alt='Save'>",
        down: "<img src='ErysIcons/down.png' alt='Move Down'>",
up: "<img src='ErysIcons/up.png' alt='Move Up' title='Move section up'>",
left: "<img src='ErysIcons/left.png' alt='Move Left'>",
right: "<img src='ErysIcons/Right.png' alt='Move Right'>",
add: "<img src='ErysIcons/addchild.png' alt='Add child block'>",
addsibling: "<img src='ErysIcons/add.png' alt='Add block'>",
remove: "<img src='ErysIcons/delete.png' alt='Delete block'>",
edit: "<img src='ErysIcons/edit.png' alt='Edit'>",
comment: "<img src='ErysIcons/dstop.png' alt='Comment'>",
expand: "<img src='ErysIcons/dstop.png' alt='Expand'>",
collapse: "<img src='ErysIcons/dstop.png' alt='Collapse'>",
cancel: "<img src='ErysIcons/dstop.png' alt='Cancel'>",
done: "<img src='ErysIcons/run.png' alt='Run'>",
download: "<img src='ErysIcons/download.png' alt='Download Jupyter notebook'>",
reset: "<img src='ErysIcons/fullreset.png' alt='Reset notebook, losing all changes'>", 
}
config.custombuttons = {
    download: download,
    reset: fullreset,
}
        config.hidden = {
            add: true,
            addsibling: true,
            left: true,
            right: true,
            comment: true,
            collapse: true,
            expand: true,
            blockSettings: true,
        }
config.multiedit = true;
   
blockeditor = nb_OutlineRoot('blockhost', false, 'blockctrls', config);
blockeditor.enableEdit(true);
blockeditor.addEditor('nb_markdown', new nb_markdownBlock());
blockeditor.addEditor('pythonCode', new nb_codeBlock());

blockeditor.Save = function ()
{
    var senddata = { notebook: this.serialize()};
    var url = "ajax/saveNotebook.php";
    ajaxAction(url, senddata);
}

//blockeditor.contentClasses = contentClasses;
blockeditor.unserialize(content);

