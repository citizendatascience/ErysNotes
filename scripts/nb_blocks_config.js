download = function (e)
{
    if (blockeditor != undefined)
    {
        var senddata = { notebook: blockeditor.serialize() };
        var url = "ajax/downloadNotebook.php";
        if (SID != undefined)
        {
                url += '?' + SID;
        }

        //#TODO Refactor to use 
        //ajaxAction(url, senddata);
        //# and add a "download" option to processAjaxResponse.js with the link bit below.
        //# Also needs changes to downloadNotebook.php to fit that (nicer) approach.

        // Based on https://nehalist.io/downloading-files-from-post-requests/

        var request = new XMLHttpRequest();
        request.open('POST', url, true);

        request.onreadystatechange = function ()
        {
            if (request.readyState == 4)
            {
                if(request.status == 200)
                {
                    // Try to find out the filename from the content disposition `filename` value

                    var link = document.createElement('a');
                    link.href = url;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
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
    if(confirm("Reset will delete any changes you have made to this notebook. Are you sure you want to continue?"))
    {
        window.location = "index.php?reset=true";
    }
}

var config = {};
config.buttons = {
    save: "<img src='ErysIcons/save.png' alt='Save' title='Save'>",
    down: "<img src='ErysIcons/down.png' alt='Move Down' title='Move Down'>",
    up: "<img src='ErysIcons/up.png' alt='Move Up' title='Move Up' title='Move section up'>",
    left: "<img src='ErysIcons/left.png' alt='Move Left' title='Move Left'>",
    right: "<img src='ErysIcons/right.png' alt='Move Right' title='Move Right'>",
    add: "<img src='ErysIcons/addchild.png' alt='Add child block' title='Add child block'>",
    addsibling: "<img src='ErysIcons/add.png' alt='Add block' title='Add block'>",
    remove: "<img src='ErysIcons/delete.png' alt='Delete block' title='Delete block'>",
    edit: "<img src='ErysIcons/edit.png' alt='Edit' title='Edit'>",
    comment: "<img src='ErysIcons/dstop.png' alt='Comment' title='Comment'>",
    expand: "<img src='ErysIcons/dstop.png' alt='Expand' title='Expand'>",
    collapse: "<img src='ErysIcons/dstop.png' alt='Collapse' title='Collapse'>",
    cancel: "<img src='ErysIcons/dstop.png' alt='Cancel' title='Cancel'>",
    done: "<img src='ErysIcons/run.png' alt='Run' title='Run'>",
    download: "<img src='ErysIcons/download.png' alt='Save and then download Jupyter notebook' title='Save and then download Jupyter notebook'>",
    reset: "<img src='ErysIcons/fullreset.png' alt='Reset notebook, losing all changes' title='Reset notebook, losing all changes'>",
}
config.custombuttons = {
    download: download,
    reset: fullreset,
}
        config.hidden = {
            add: true,
            addsibling: true,
            up: true,
            down: true,
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

shortcut.add("Shift+Enter", function ()
{
    nb_outlineBlocksBase.doneClicked();
});


shortcut.add("Shift+Down", function ()
{
    nb_outlineBlocksBase.selectNextBlock();
});
shortcut.add("Shift+Up", function ()
{
    nb_outlineBlocksBase.selectPrevBlock();
});
