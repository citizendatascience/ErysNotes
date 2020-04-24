
function processMarkdown(fromID, toID)
{
    var senddata = {markdown: document.getElementById(fromID).value, target: toID };
    var root = document.location.href.substring(0, document.location.href.lastIndexOf('/') + 1);
    var url = root + "ajax/md2html_service.php";
    ajaxAction(url, senddata);
}

function processPython(fromID, pyidx, source)
{
    var senddata = { id: fromID, pyidx: pyidx, source: source };
    var root = document.location.href.substring(0, document.location.href.lastIndexOf('/')+1);
    var url = root + "ajax/callpython_service.php";
    ajaxAction(url, senddata);
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

    this.enableControls = function (block)
    {
        block.controls.enableEdit(true);
        block.controls.enableDone(false);
    }

    this.initialise = function (block)
    {
        block.contentnode.innerHTML = block.content;
        this.enableControls(block);
    }
}

nb_codeBlock = function ()
{
    nb_outlineBlocktypeBase.call(this);

    this.initEdit = function (id, editnode, source)
    {
        alert(".initEdit code block " + block.id);
        //editnode.innerHTML = "Code Override</br><textarea id='" + id + "_editarea' rows='12' style='margin:2px; width:95%'>" +source + "</textarea>";
    }

    this.editEnd = function (block)
    {
        alert("Edit End for code block called");
    }

    this.getSource = function(block)
    {
        return block.editor.getValue();
    }

    this.serializeExtras = function (block, serialized)
    {
        if (block.execution_count != undefined)
            serialized.execution_count = block.execution_count;
        if (document.getElementById(block.id + "_info") != undefined)
            serialized.execution_count = document.getElementById(block.id + "_info").innerText;
    }

    this.unserializeExtras = function (block, data)
    {
        if (data.execution_count != undefined)
            block.execution_count = data.execution_count;
        else
            block.execution_count = '-';
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
        block.infonode.innerHTML = block.execution_count;
        block.contentnode.style.color = '';
        block.outputnode.style.color = '#003300';

        block.editor = ace.edit(block.id + "_editor", {
            theme: "ace/theme/tomorrow_night",
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
