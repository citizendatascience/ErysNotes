function processMarkdown(fromID, toID)
{
    var url = "ajax/md2html_service.php";
    document.body.style.cursor = 'wait';
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = function ()
    {
        if (xmlHttp.readyState == 4)
        {
            if (xmlHttp.status == 200)
            {
                try
                {
                    var response = JSON.parse(xmlHttp.responseText);
                    processAjaxResponse(response);
                } catch (e)
                {
                    alert("JSON parse error in processMarkdown\nURL: " + url + "\n" + e + "\n\n" + xmlHttp.responseText);
                }
            }
            else
            {
                alert("Http response code " + xmlHttp.status + " when retrieving " + url);
            }
            document.body.style.cursor = '';
        }
    }
    xmlHttp.open("POST", url, true);
    data = new FormData();
    data.append("markdown", document.getElementById(fromID).value);
    data.append("target", toID);
    xmlHttp.send(data);
}

function processPython(fromID, pyidx, source)
{
    var url = "ajax/callpython_service.php";
    document.body.style.cursor = 'wait';
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = function ()
    {
        if (xmlHttp.readyState == 4)
        {
            if (xmlHttp.status == 200)
            {
                try
                {
                    var response = JSON.parse(xmlHttp.responseText);
                    processAjaxResponse(response);
                } catch (e)
                {
                    alert("JSON parse error in processPython\nURL: " + url + "\n" + e + "\n\n" + xmlHttp.responseText);
                    document.write(xmlHttp.responseText);
                }
            }
            else
            {
                alert("Http response code " + xmlHttp.status + " when retrieving " + url);
            }
            document.body.style.cursor = '';
        }
    }
    xmlHttp.open("POST", url, true);
    data = new FormData();
    data.append("id", fromID);
    data.append("source", source);
    data.append("pyidx", pyidx);
    xmlHttp.send(data);
}

nb_markdownBlock = function ()
{
    nb_outlineBlocktypeBase.call(this);
    /*this.initEdit = function(id, editnode, source)
    {
        editnode.innerHTML = "md Override</br><textarea id='" + id + "_editarea' rows='12' style='margin:2px; width:95%'>" +source + "</textarea>";
    }*/

    this.render = function (block)
    {
        if (block.editnode != undefined)
        {
            block.source = document.getElementById(block.id + "_editarea").value;
            processMarkdown(block.id + '_editarea', block.contentnode.id);
        }
    }

    this.initialise = function (block)
    {
        block.contentnode.innerHTML = block.content;
    }
}

nb_codeBlock = function ()
{
    nb_outlineBlocktypeBase.call(this);

    this.initEdit = function (id, editnode, source)
    {
        //editnode.innerHTML = "Code Override</br><textarea id='" + id + "_editarea' rows='12' style='margin:2px; width:95%'>" +source + "</textarea>";
    }

    this.editEnd = function (block)
    {
        alert("Edit End for code block called");
    }

    this.render = function (block)
    {
        var pyidx = 0;
        for (var child = block.owner.node.firstChild; child !== null; child = child.nextSibling)
        {
            if ((child.id != undefined) && (nb_outlineBlocksBase.blockLookup[child.id] != undefined)
                && (nb_outlineBlocksBase.blockLookup[child.id].contentType != undefined) && (nb_outlineBlocksBase.blockLookup[child.id].contentType == block.contentType))
            {
                nb_outlineBlocksBase.blockLookup[child.id].pyidx = pyidx;
                pyidx++;
            }
        }

        //block.source = document.getElementById(block.id + "_editarea").value;
        block.outputnode.innerHTML = "<p><b>Running, please wait.</b></p>";
        processPython(block.id, block.pyidx, block.editor.getValue());
    }

    this.enableControls = function (block)
    {
        block.controls.enableRemove(false);
        block.controls.enableEdit(false);
        block.controls.enableDone(true);
    }


    this.initialise = function (block)
    {
        //alert("Initialising " + block.id);
        block.contentnode.innerHTML = '';
        block.editornode = document.createElement('textarea');
        block.editornode.id = block.id + "_editor";
        //block.editornode.className = "aceeditor";
        block.editornode.innerHTML = block.source;
        block.contentnode.insertBefore(block.editornode, null);
        block.outputnode = document.createElement('div');
        block.outputnode.id = block.id + "_output";
        block.outputnode.innerHTML = block.content;
        block.contentnode.insertBefore(block.outputnode, null);

        block.contentnode.style.color = '';
        block.outputnode.style.color = '#003300';

        block.editor = ace.edit(block.id + "_editor", {
            theme: "ace/theme/tomorrow_night_eighties",
            mode: "ace/mode/python",
            maxLines: 30,
            minLines: 8,
            wrap: true,
            autoScrollEditorIntoView: true
        });
        this.enableControls(block);
    }

    this.cleanup = function (block)
    {
        block.contentnode.removeChild(block.outputnode);
        block.contentnode.innerHTML = "&nbsp;";
        block.editornode = undefined;
        block.editor = undefined;
        block.outputnode = undefined;
    }
}
