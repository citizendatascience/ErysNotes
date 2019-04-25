nb_markdownBlock = function ()
{
    nb_outlineBlocktypeBase.call(this);
    /*this.initEdit = function(id, editnode, source)
    {
        editnode.innerHTML = "md Override</br><textarea id='" + id + "_editarea' rows='12' style='margin:2px; width:95%'>" +source + "</textarea>";
    }*/

    this.render = function (block)
    {
        if (this.editnode != undefined)
        {
            block.source = document.getElementById(block.id + "_editarea").value;
            processMarkdown(block.id + '_editarea', block.contentnode.id);
        }
    }

    this.initialise = function (block)
    {
        //alert("Initialising " + block.id);
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
        alert("Rendering codeBlock " + block.id);
        //block.source = document.getElementById(block.id + "_editarea").value;
        block.outputnode.innerHTML = "<p><b>Not Yet</b></p>";
        block.outputnode.innerHTML += "<pre>" + block.editor.getValue() + "</pre>";
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
        block.outputnode.id = block.id + "_editor";
        block.outputnode.innerHTML = block.content;
        block.contentnode.insertBefore(block.outputnode, null);

        block.contentnode.style.color = '';
        block.outputnode.style.color = 'green';

        block.editor = ace.edit(block.id + "_editor", {
            theme: "ace/theme/tomorrow_night_eighties",
            mode: "ace/mode/python",
            maxLines: 30,
            minLines: 8,
            wrap: true,
            autoScrollEditorIntoView: true
        });
        // block.editor.setTheme("ace/theme/monokai");
        // block.editor.session.setMode("ace/mode/python");
    }
}
