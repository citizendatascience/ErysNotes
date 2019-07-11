///nb_JSSection licence 
/*
Copyright 2018, University of Glasgow.
Written by Niall S F Barr

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/
///nb_JSSection nb_outlineBlocksBase
function nb_outlineBlocksBase()
{
    if (nb_outlineBlocksBase.count === undefined)
    {
        nb_outlineBlocksBase.count = 0;
        nb_outlineBlocksBase.blockLookup = [];
        //nb_outlineBlocksBase.buttons = [];

        //#region default config
        nb_outlineBlocksBase.config = {};
        nb_outlineBlocksBase.config.buttons = {
            save: "<span class='fa fa-save'></span>",
            down: "<span class='fa fa-arrow-down'></span>",
            up: "<span class='fa fa-arrow-up'></span>",
            left: "<span class='fa fa-arrow-left'></span>",
            right: "<span class='fa fa-arrow-right'></span>",
            add: "<span class='fa fa-plus-square'></span><span class='fa fa-level-up-alt'></span>",
            addsibling: "<span class='fa fa-plus-square'></span><span class='fa fa-arrow-down'></span>",
            remove: "<span class='fa fa-trash-alt'></span>",
            edit: "<span class='fa fa-edit'></span>",
            comment: "<span class='fa fa-comment-dots'></span>",
            expand: "<span class='fa fa-expand'></span>",
            collapse: "<span class='fa fa-compress'></span>",
            cancel: "<span class='fa fa-undo'></span>",
            done: "<span class='fa fa-check'></span>"
        }
        nb_outlineBlocksBase.config.hidden = {};

        // adapted from mergeObjects function found at https://stackoverflow.com/questions/30498318/es5-object-assign-equivalent
        // which didn't work - not recursive. This works :-)
        nb_outlineBlocksBase.mergeConfig = function ()
        {
            var resObj = {};
            for (var i = 0; i < arguments.length; i += 1)
            {
                if (typeof arguments[i] == 'object')
                {
                    var obj = arguments[i],
                        keys = Object.keys(obj);
                    for (var j = 0; j < keys.length; j += 1)
                    {
                        if ((typeof resObj[keys[j]] == 'object') && (typeof obj[keys[j]] == 'object'))
                            resObj[keys[j]] = nb_outlineBlocksBase.mergeConfig(resObj[keys[j]], obj[keys[j]]);
                        else
                            resObj[keys[j]] = obj[keys[j]];
                    }
                }
            }
            return resObj;
        }
        //#endregion
        nb_outlineBlocksBase.selectedBlock = null;
        nb_outlineBlocksBase.editingBlock = null;

        // #region Button clicked methods

        //#macroButtons nb_outlineBlocksBase : addsibling add remove edit done cancel up down left right comment collapse expand
        //#macroGenCode
        nb_outlineBlocksBase.addsiblingClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.addsiblingBtn.disabled) && (thisBlk.parentID != undefined))
            {
                //USERCODE-SECTION-buttonclicked-addsibling]
                nb_outlineBlocksBase.blockLookup[thisBlk.parentID].addBlock(thisBlk);
                //ENDUSERCODE-SECTION-buttonclicked-addsibling
            }
        }

        nb_outlineBlocksBase.addClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.addBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-add
                thisBlk.addBlock();
                //ENDUSERCODE-SECTION-buttonclicked-add
            }
        }

        nb_outlineBlocksBase.removeClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.removeBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-remove                
                if (confirm("Remove selected note?"))
                {
                    parentBlk = nb_outlineBlocksBase.blockLookup[thisBlk.parentID];
                    parentBlk.node.removeChild(thisBlk.node);
                    nb_outlineBlocksBase.selectBlock(parentBlk.id);
                }
                //ENDUSERCODE-SECTION-buttonclicked-remove
            }
        }

        nb_outlineBlocksBase.editClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.editBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-edit
                thisBlk.editStart();
                //ENDUSERCODE-SECTION-buttonclicked-edit
            }
        }

        nb_outlineBlocksBase.doneClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.doneBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-done
                if (thisBlk.owner == thisBlk)
                {
                    thisBlk.enableEdit(false);
                }
                else
                {
                    thisBlk.editEnd(true);
                }
                //ENDUSERCODE-SECTION-buttonclicked-done
            }
        }

        nb_outlineBlocksBase.cancelClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.cancelBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-cancel
                thisBlk.editEnd(false);
                thisBlk.controls.enableDone(false);
                thisBlk.controls.enableCancel(false);
                thisBlk.controls.enableEdit(true);
                //ENDUSERCODE-SECTION-buttonclicked-cancel
            }
        }

        nb_outlineBlocksBase.upClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.upBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-up
                parentBlk = nb_outlineBlocksBase.blockLookup[thisBlk.parentID];
                parentBlk.node.removeChild(thisBlk.node);
                parentBlk.insertAt(thisBlk, thisBlk.index - 1);
                nb_outlineBlocksBase.selectBlock(thisBlk.id);
                thisBlk.enableMoveButtons();
                //ENDUSERCODE-SECTION-buttonclicked-up
            }
        }

        nb_outlineBlocksBase.saveClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.saveBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-save
                // alert(thisBlk.owner.serialize());
                thisBlk.owner.Save();
                //ENDUSERCODE-SECTION-buttonclicked-save
            }
        }

        nb_outlineBlocksBase.downClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.downBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-down
                parentBlk = nb_outlineBlocksBase.blockLookup[thisBlk.parentID];
                parentBlk.node.removeChild(thisBlk.node);
                parentBlk.reindex();
                parentBlk.insertAt(thisBlk, thisBlk.index + 1);
                nb_outlineBlocksBase.selectBlock(thisBlk.id);
                thisBlk.enableMoveButtons();
                //ENDUSERCODE-SECTION-buttonclicked-down
            }
        }

        nb_outlineBlocksBase.leftClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.leftBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-left
                parentBlk = nb_outlineBlocksBase.blockLookup[thisBlk.parentID];
                ppBlk = nb_outlineBlocksBase.blockLookup[parentBlk.parentID];
                parentBlk.node.removeChild(thisBlk.node);
                ppBlk.insertAt(thisBlk, parentBlk.index + 1);
                ppBlk.reindex(true);
                thisBlk.enableMoveButtons();
                //ENDUSERCODE-SECTION-buttonclicked-left
            }
        }

        nb_outlineBlocksBase.rightClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.rightBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-right
                parentBlk = nb_outlineBlocksBase.blockLookup[thisBlk.parentID];
                parentBlk.node.removeChild(thisBlk.node);
                parentBlk.reindex();
                parentBlk.children[thisBlk.index - 1].insertAt(thisBlk);
                thisBlk.enableMoveButtons();
                //ENDUSERCODE-SECTION-buttonclicked-right
            }
        }

        nb_outlineBlocksBase.commentClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.commentBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-comment
                // Put code here.
                //ENDUSERCODE-SECTION-buttonclicked-comment
            }
        }

        nb_outlineBlocksBase.collapseClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.collapseBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-collapse
                thisBlk.collapsed = true;
                thisBlk.contentnode.hidden = true;
                thisBlk.summary = thisBlk.contentnode.innerText.substr(0, 60);
                thisBlk.reindex();
                for (c in thisBlk.children)
                {
                    thisBlk.children[c].node.hidden = true;
                }
                if (this.collapsedNode == undefined)
                {
                    thisBlk.collapsedNode = document.createElement('div');
                    thisBlk.collapsedNode.id = this.id + "_collapsed";
                    thisBlk.collapsedNode.classList.add("collapsed_summary");
                    thisBlk.node.insertBefore(thisBlk.collapsedNode, thisBlk.contentnode);
                }
                thisBlk.collapsedNode.hidden = false;
                thisBlk.collapsedNode.innerHTML = thisBlk.summary;
                //ENDUSERCODE-SECTION-buttonclicked-collapse
            }
        }

        nb_outlineBlocksBase.expandClicked = function (e)
        {
            thisBlk = nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock];
            if ((thisBlk != undefined) && (!thisBlk.controls.expandBtn.disabled))
            {
                //USERCODE-SECTION-buttonclicked-expand
                thisBlk.collapsed = false;
                thisBlk.contentnode.hidden = false;
                for (c in thisBlk.children)
                {
                    thisBlk.children[c].node.hidden = false;
                }
                thisBlk.collapsedNode.hidden = true;
                //ENDUSERCODE-SECTION-buttonclicked-expand
            }
        }
        //#endGenCode
        // #endregion

        nb_outlineBlocksBase.selectClickedBlock = function (e)
        {
            nb_outlineBlocksBase.selectBlock(e.currentTarget.id);
            e.stopPropagation();
        }

        nb_outlineBlocksBase.editClickedBlock = function (e)
        {
            if (nb_outlineBlocksBase.blockLookup[e.currentTarget.id].id == nb_outlineBlocksBase.selectedBlock)
            {
                nb_outlineBlocksBase.blockLookup[e.currentTarget.id].editStart();
                nb_outlineBlocksBase.blockLookup[e.currentTarget.id].controls.enableCancel(true);
                nb_outlineBlocksBase.blockLookup[e.currentTarget.id].controls.enableDone(true);
                nb_outlineBlocksBase.blockLookup[e.currentTarget.id].controls.enableEdit(false);

                e.stopPropagation();
            }
        }

        nb_outlineBlocksBase.setBlockClass = function (blockID, toClass)
        {
            //alert('setBlockClass\n' + blockID + ' ' + toClass);
            nb_outlineBlocksBase.blockLookup[blockID].setContentclass(toClass);
        }

        nb_outlineBlocksBase.setBlockType = function (blockID, toType)
        {
            nb_outlineBlocksBase.blockLookup[blockID].setContentType(toType);
        }

        nb_outlineBlocksBase.enableEditOnClickedBlock = function (e)
        {
            if (nb_outlineBlocksBase.blockLookup[e.currentTarget.id] != undefined)
            {
                nb_outlineBlocksBase.blockLookup[e.currentTarget.id].owner.enableEdit(true);
                //nb_outlineBlocksBase.selectBlock(e.currentTarget.id);
                e.stopPropagation();
            }
        }

        nb_outlineBlocksBase.selectRoot = function (e)
        {
            nb_outlineBlocksBase.selectBlock(e.currentTarget.id)
        }

        nb_outlineBlocksBase.selectBlock = function (id)
        {
            if ((this.config.multiedit == undefined) || (this.config.multiedit == false))
            {
                // end editing on any other block
                if ((nb_outlineBlocksBase.editingBlock != null) && (nb_outlineBlocksBase.editingBlock != id))
                {
                    nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.editingBlock].editEnd();
                    nb_outlineBlocksBase.editingBlock = null;
                }
            }
            // Unselect previously selected block   
            if (nb_outlineBlocksBase.selectedBlock != null)
            {
                nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock].node.classList.remove('blockSelected');
                if (nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock].ctrlsNode != null)
                    nb_outlineBlocksBase.blockLookup[nb_outlineBlocksBase.selectedBlock].ctrlsNode.hidden = true;
                nb_outlineBlocksBase.selectedBlock = null;
            }
            if ((nb_outlineBlocksBase.blockLookup[id] != undefined) && (nb_outlineBlocksBase.blockLookup[id].owner.editing))
            {
                thisBlk = nb_outlineBlocksBase.blockLookup[id];
                thisBlk.node.classList.add('blockSelected');
                if (this.ctrlsNode != null)
                    thisBlk.ctrlsNode.hidden = false;
                if (thisBlk.enableMoveButtons != undefined)
                    thisBlk.enableMoveButtons();
                nb_outlineBlocksBase.selectedBlock = id;
                if ((thisBlk.childType == undefined) || (thisBlk.owner.editors[thisBlk.childType] == undefined))
                    thisBlk.controls.enableAdd(false);
                //# This is temporary - should be overridable in the block elsewhere and take teacher settings into account.
                thisBlk.controls.enableRemove(true);
                if ((thisBlk.owner.editors[thisBlk.contentType] != undefined) && (thisBlk.owner.editors[thisBlk.contentType].enableControls != undefined))
                    thisBlk.owner.editors[thisBlk.contentType].enableControls(thisBlk);
                if (thisBlk.collapsed != undefined)
                {
                    thisBlk.controls.enableExpand(thisBlk.collapsed);
                    thisBlk.controls.enableCollapse(!thisBlk.collapsed);
                }
                if (thisBlk.getBlockSettings != undefined)
                    thisBlk.controls.setSettingsSel(thisBlk.getBlockSettings());
                else
                    thisBlk.controls.setSettingsSel(false);
            }
        }
    }

    this.id = 'ID' + nb_outlineBlocksBase.count;
    nb_outlineBlocksBase.count++;
    nb_outlineBlocksBase.blockLookup[this.id] = this;

    this.addBlock = function (after, select, initialize)
    {
        var block = new nb_outlineBlock(this.owner);
        if (this.childType == undefined)
            block.contentType = this.owner.defaultChild;
        else
            block.contentType = this.childType;
        if ((typeof after == 'undefined') || (after == false))
            if (this.ctrlsNode)
                this.node.insertBefore(block.node, this.ctrlsNode);
            else
                this.node.insertBefore(block.node, null);
        else
            this.node.insertBefore(block.node, after.node.nextSibling);
        block.parentID = this.id;
        block.owner = this.owner;
        block.content = '&nbsp;';
        block.source = '';
        if ((initialize == undefined) || (initialize == true))
        {
            if ((block.owner.editors[this.contentType] != undefined) && (block.owner.editors[block.contentType].initialise != undefined))
                block.owner.editors[this.contentType].initialise(block);
        }
        this.reindex(false);
        if ((typeof select == 'undefined') || (select == true))
            nb_outlineBlocksBase.selectBlock(block.id);
        return block;
    }

    this.addControls = function (isRoot, config)
    {
        //# Ideally don't create if not needed, & therefor check not undefined elsewhere
        var ctrlParent = this.owner.ctrlsDisplayNode == null ? this : this.owner.ctrlsDisplayNode;
        if (ctrlParent != this)
        {
            if (ctrlParent.ctrlsNode == undefined)
            {
                ctrlParent.ctrlsNode = document.createElement('div');
                ctrlParent.ctrlsNode.classList.add('blockCtrls');
                ctrlParent.appendChild(ctrlParent.ctrlsNode);
            }
            this.ctrlsNode = null;
        }
        else
        {
            this.ctrlsNode = document.createElement('div');
            this.ctrlsNode.classList.add('blockCtrls');
            this.node.appendChild(this.ctrlsNode);
            this.ctrlsNode.hidden = true;
        }
        if (this.owner.ctrlsDisplayNode == null)
        {
            this.controls = new nb_outlineControls(ctrlParent.ctrlsNode, isRoot, config);
        }
        else
        {
            if (isRoot)
                this.controls = new nb_outlineControls(ctrlParent.ctrlsNode, false, config);
            else
                this.controls = this.owner.controls;
        }
    }
    this.childType = this.contentType;  // default that can be overridden. Ideally also alow blocks to be replaced with a different type

    this.serialize = function (asobj)
    {
        var serialized = Object();
        if (this.contentnode != undefined)
        {
            //# This works for codeblock, but it would be better to allow complicated blocktypes to have their own serialise method
            if (this.outputnode != undefined)
                serialized.content = this.outputnode.innerHTML;
            else
                serialized.content = this.contentnode.innerHTML;
        }
        serialized.source = this.source;
        serialized.contentType = this.contentType;
        serialized.children = Array();
        for (var child = this.node.firstChild; child !== null; child = child.nextSibling)
        {
            if ((child.id != undefined) && (nb_outlineBlocksBase.blockLookup[child.id] != undefined))
            {
                serialized.children.push(nb_outlineBlocksBase.blockLookup[child.id].serialize(true));
            }
        }
        if (asobj)
            return serialized;
        else
            return JSON.stringify(serialized);
    }

    this.getStructure = function (asobj)
    {
        var structure = Object();
        structure.id = this.id;
        structure.contentType = this.contentType;
        structure.children = Array();
        for (var child = this.node.firstChild; child !== null; child = child.nextSibling)
        {
            if ((child.id != undefined) && (nb_outlineBlocksBase.blockLookup[child.id] != undefined))
            {
                structure.children.push(nb_outlineBlocksBase.blockLookup[child.id].getStructure(true));
            }
        }
        if (asobj)
            return structure;
        else
            return JSON.stringify(structure);
    }

    this.unserialize = function (data)
    {
        if ((data.content != undefined) || (data.source != undefined))
        {
            if (data.content != undefined)
                this.content = data.content;
            else
                this.content = data.source;
            this.contentnode.innerHTML = data.content;
            if (data.source != undefined)
                this.source = data.source;
            else
                this.source = data.content;
            this.contentType = data.contentType;
            if ((this.owner.editors[this.contentType] != undefined) && (this.owner.editors[this.contentType].initialise != undefined))
                this.owner.editors[this.contentType].initialise(this);
            else
                if (this.owner.editors[this.contentType] != undefined)
                    alert("Unregistered content type (lacks initialise method) " + this.contentType);
                else
                    alert("Undefined content type");

        }
        else
        {
            this.content = '';
            this.contentType = 'unknown_fix_this';
        }
        if ((this != this.owner) && (nb_outlineBlocksBase.selectedBlock == undefined))
        {
            nb_outlineBlocksBase.selectBlock(this.id);
        }
        for (i in data.children)
        {
            block = this.addBlock(false, false, false);
            block.unserialize(data.children[i]);
        }
    }

    this.reindex = function (recurse)
    {
        var cidx = 0;
        this.children = [];
        for (var child = this.node.firstChild; child !== null; child = child.nextSibling)
        {
            if ((child.id != undefined) && (nb_outlineBlocksBase.blockLookup[child.id] != undefined))
            {
                if (this.depth != undefined)
                {
                    nb_outlineBlocksBase.blockLookup[child.id].depth = this.depth + 1;
                }
                nb_outlineBlocksBase.blockLookup[child.id].index = cidx;
                this.children.push(nb_outlineBlocksBase.blockLookup[child.id]);
                cidx++;
                if (recurse)
                {
                    nb_outlineBlocksBase.blockLookup[child.id].reindex(true);
                }
            }
        }
        this.childCount = cidx;
    }

    this.insertAt = function (block, index)
    {
        if (this.children == undefined)
            this.reindex();
        if ((index == undefined) || (index >= this.children.length))
            this.node.insertBefore(block.node, this.ctrlsNode);
        else
            this.node.insertBefore(block.node, this.children[index].node);
        block.parentID = this.id;
        this.reindex();
    }
}
///nb_JSSection nb_outlineControls
function nb_outlineControls(ctrlsNode, isRoot, config)
{
    this.ctrlsNode = ctrlsNode;
    this.config = config;

    this.createCtrlButton = function (name, callback)
    {
        var html = this.config.buttons[name];
        Btn = document.createElement('span');
        Btn.classList.add('blockBtn');
        Btn.innerHTML = html;
        Btn.addEventListener("click", callback);
        this.ctrlsNode.appendChild(Btn);
        return Btn;
    }

    //#button save
    //#macroGenCode
    this.saveBtn = this.createCtrlButton('save', nb_outlineBlocksBase.saveClicked);
    this.saveBtn.disabled = false;
    this.enableSave = function (enable)
    {
        if ((!enable) && (!this.saveBtn.disabled))
        {
            this.saveBtn.classList.add('blockBtnDisabled');
            this.saveBtn.disabled = true;
        }
        else if ((enable) && (this.saveBtn.disabled))
        {
            this.saveBtn.classList.remove('blockBtnDisabled');
            this.saveBtn.disabled = false;
        }
    }

    this.hideSave = function (hide)
    {
        if (this.config.hidden.save == true)
            this.saveBtn.style.display = 'none';
        else
            this.saveBtn.style.display = hide ? 'none' : 'initial';
    }

    this.enableSave(true);
    this.hideSave(false);
    //#endGenCode

    // #region Add buttons section
    if (!isRoot)
    {
        //#button addsibling
        //#macroGenCode
        this.addsiblingBtn = this.createCtrlButton( 'addsibling', nb_outlineBlocksBase.addsiblingClicked);
        this.addsiblingBtn.disabled = false;
        this.enableAddsibling = function (enable)
        {
            if ((!enable) && (!this.addsiblingBtn.disabled))
            {
                this.addsiblingBtn.classList.add('blockBtnDisabled');
                this.addsiblingBtn.disabled = true;
            }
            else if ((enable) && (this.addsiblingBtn.disabled))
            {
                this.addsiblingBtn.classList.remove('blockBtnDisabled');
                this.addsiblingBtn.disabled = false;
            }
        }

        this.hideAddsibling = function (hide)
        {
            if(this.config.hidden.addsibling == true)
                this.addsiblingBtn.style.display = 'none';
            else
                this.addsiblingBtn.style.display = hide ? 'none' : 'initial';
        }

        this.enableAddsibling(true);
        this.hideAddsibling(false);
        //#endGenCode

    }

    //#button add
    //#macroGenCode
    this.addBtn = this.createCtrlButton( 'add', nb_outlineBlocksBase.addClicked);
    this.addBtn.disabled = false;
    this.enableAdd = function (enable)
    {
        if ((!enable) && (!this.addBtn.disabled))
        {
            this.addBtn.classList.add('blockBtnDisabled');
            this.addBtn.disabled = true;
        }
        else if ((enable) && (this.addBtn.disabled))
        {
            this.addBtn.classList.remove('blockBtnDisabled');
            this.addBtn.disabled = false;
        }
    }

    this.hideAdd = function (hide)
    {
        if (this.config.hidden.add == true)
            this.addBtn.style.display = 'none';
        else
            this.addBtn.style.display = hide ? 'none' : 'initial';
    }

    this.enableAdd(true);
    this.hideAdd(false);
    //#endGenCode


    if (!isRoot) 
    {

        //#button remove
        //#macroGenCode
        this.removeBtn = this.createCtrlButton( 'remove', nb_outlineBlocksBase.removeClicked);
        this.removeBtn.disabled = false;
        this.enableRemove = function (enable)
        {
            if ((!enable) && (!this.removeBtn.disabled))
            {
                this.removeBtn.classList.add('blockBtnDisabled');
                this.removeBtn.disabled = true;
            }
            else if ((enable) && (this.removeBtn.disabled))
            {
                this.removeBtn.classList.remove('blockBtnDisabled');
                this.removeBtn.disabled = false;
            }
        }

        this.hideRemove = function (hide)
        {
            if (this.config.hidden.remove == true)
                this.removeBtn.style.display = 'none';
            else
                this.removeBtn.style.display = hide ? 'none' : 'initial';
        }

        this.enableRemove(false);
        this.hideRemove(false);
        //#endGenCode


        sp = document.createElement('span');
        sp.innerHTML = "&nbsp;&nbsp;";
        this.ctrlsNode.appendChild(sp);

        //#button edit
        //#macroGenCode
        this.editBtn = this.createCtrlButton( 'edit', nb_outlineBlocksBase.editClicked);
        this.editBtn.disabled = false;
        this.enableEdit = function (enable)
        {
            if ((!enable) && (!this.editBtn.disabled))
            {
                this.editBtn.classList.add('blockBtnDisabled');
                this.editBtn.disabled = true;
            }
            else if ((enable) && (this.editBtn.disabled))
            {
                this.editBtn.classList.remove('blockBtnDisabled');
                this.editBtn.disabled = false;
            }
        }

        this.hideEdit = function (hide)
        {
            if (this.config.hidden.edit == true)
                this.editBtn.style.display = 'none';
            else
                this.editBtn.style.display = hide ? 'none' : 'initial';
        }

        this.enableEdit(true);
        this.hideEdit(false);
        //#endGenCode
    }

    //#button done
    //#macroGenCode
    this.doneBtn = this.createCtrlButton( 'done', nb_outlineBlocksBase.doneClicked);
    this.doneBtn.disabled = false;
    this.enableDone = function (enable)
    {
        if ((!enable) && (!this.doneBtn.disabled))
        {
            this.doneBtn.classList.add('blockBtnDisabled');
            this.doneBtn.disabled = true;
        }
        else if ((enable) && (this.doneBtn.disabled))
        {
            this.doneBtn.classList.remove('blockBtnDisabled');
            this.doneBtn.disabled = false;
        }
    }

    this.hideDone = function (hide)
    {
        if (this.config.hidden.done == true)
            this.doneBtn.style.display = 'none';
        else
            this.doneBtn.style.display = hide ? 'none' : 'initial';
    }

    this.enableDone(false);
    this.hideDone(false);
    //#endGenCode

    if (!isRoot) 
    {
        //#button cancel
        //#macroGenCode
        this.cancelBtn = this.createCtrlButton( 'cancel', nb_outlineBlocksBase.cancelClicked);
        this.cancelBtn.disabled = false;
        this.enableCancel = function (enable)
        {
            if ((!enable) && (!this.cancelBtn.disabled))
            {
                this.cancelBtn.classList.add('blockBtnDisabled');
                this.cancelBtn.disabled = true;
            }
            else if ((enable) && (this.cancelBtn.disabled))
            {
                this.cancelBtn.classList.remove('blockBtnDisabled');
                this.cancelBtn.disabled = false;
            }
        }

        this.hideCancel = function (hide)
        {
            if (this.config.hidden.cancel == true)
                this.cancelBtn.style.display = 'none';
            else
                this.cancelBtn.style.display = hide ? 'none' : 'initial';
        }

        this.enableCancel(false);
        this.hideCancel(false);
        //#endGenCode


        sp = document.createElement('span');
        sp.innerHTML = "&nbsp;&nbsp;";
        this.ctrlsNode.appendChild(sp);

        //#button up
        //#macroGenCode
        this.upBtn = this.createCtrlButton( 'up', nb_outlineBlocksBase.upClicked);
        this.upBtn.disabled = false;
        this.enableUp = function (enable)
        {
            if ((!enable) && (!this.upBtn.disabled))
            {
                this.upBtn.classList.add('blockBtnDisabled');
                this.upBtn.disabled = true;
            }
            else if ((enable) && (this.upBtn.disabled))
            {
                this.upBtn.classList.remove('blockBtnDisabled');
                this.upBtn.disabled = false;
            }
        }

        this.hideUp = function (hide)
        {
            if (this.config.hidden.up == true)
                this.upBtn.style.display = 'none';
            else
                this.upBtn.style.display = hide ? 'none' : 'initial';
        }

        this.enableUp(false);
        this.hideUp(false);
        //#endGenCode


        //#button down
        //#macroGenCode
        this.downBtn = this.createCtrlButton( 'down', nb_outlineBlocksBase.downClicked);
        this.downBtn.disabled = false;
        this.enableDown = function (enable)
        {
            if ((!enable) && (!this.downBtn.disabled))
            {
                this.downBtn.classList.add('blockBtnDisabled');
                this.downBtn.disabled = true;
            }
            else if ((enable) && (this.downBtn.disabled))
            {
                this.downBtn.classList.remove('blockBtnDisabled');
                this.downBtn.disabled = false;
            }
        }

        this.hideDown = function (hide)
        {
            if (this.config.hidden.down == true)
                this.downBtn.style.display = 'none';
            else
                this.downBtn.style.display = hide ? 'none' : 'initial';
        }

        this.enableDown(false);
        this.hideDown(false);
        //#endGenCode


        //#button left
        //#macroGenCode
        this.leftBtn = this.createCtrlButton( 'left', nb_outlineBlocksBase.leftClicked);
        this.leftBtn.disabled = false;
        this.enableLeft = function (enable)
        {
            if ((!enable) && (!this.leftBtn.disabled))
            {
                this.leftBtn.classList.add('blockBtnDisabled');
                this.leftBtn.disabled = true;
            }
            else if ((enable) && (this.leftBtn.disabled))
            {
                this.leftBtn.classList.remove('blockBtnDisabled');
                this.leftBtn.disabled = false;
            }
        }

        this.hideLeft = function (hide)
        {
            if (this.config.hidden.left == true)
                this.leftBtn.style.display = 'none';
            else
                this.leftBtn.style.display = hide ? 'none' : 'initial';
        }

        this.enableLeft(false);
        this.hideLeft(false);
        //#endGenCode


        //#button right
        //#macroGenCode
        this.rightBtn = this.createCtrlButton( 'right', nb_outlineBlocksBase.rightClicked);
        this.rightBtn.disabled = false;
        this.enableRight = function (enable)
        {
            if ((!enable) && (!this.rightBtn.disabled))
            {
                this.rightBtn.classList.add('blockBtnDisabled');
                this.rightBtn.disabled = true;
            }
            else if ((enable) && (this.rightBtn.disabled))
            {
                this.rightBtn.classList.remove('blockBtnDisabled');
                this.rightBtn.disabled = false;
            }
        }

        this.hideRight = function (hide)
        {
            if (this.config.hidden.right == true)
                this.rightBtn.style.display = 'none';
            else
                this.rightBtn.style.display = hide ? 'none' : 'initial';
        }

        this.enableRight(false);
        this.hideRight(false);
        //#endGenCode


        sp = document.createElement('span');
        sp.innerHTML = "&nbsp;&nbsp;";
        this.ctrlsNode.appendChild(sp);

        //#button comment
        //#macroGenCode
        this.commentBtn = this.createCtrlButton( 'comment', nb_outlineBlocksBase.commentClicked);
        this.commentBtn.disabled = false;
        this.enableComment = function (enable)
        {
            if ((!enable) && (!this.commentBtn.disabled))
            {
                this.commentBtn.classList.add('blockBtnDisabled');
                this.commentBtn.disabled = true;
            }
            else if ((enable) && (this.commentBtn.disabled))
            {
                this.commentBtn.classList.remove('blockBtnDisabled');
                this.commentBtn.disabled = false;
            }
        }

        this.hideComment = function (hide)
        {
            if (this.config.hidden.comment == true)
                this.commentBtn.style.display = 'none';
            else
                this.commentBtn.style.display = hide ? 'none' : 'initial';
        }

        this.enableComment(false);
        this.hideComment(false);
        //#endGenCode


        sp = document.createElement('span');
        sp.innerHTML = "&nbsp;&nbsp;";
        this.ctrlsNode.appendChild(sp);

        //#button collapse
        //#macroGenCode
        this.collapseBtn = this.createCtrlButton( 'collapse', nb_outlineBlocksBase.collapseClicked);
        this.collapseBtn.disabled = false;
        this.enableCollapse = function (enable)
        {
            if ((!enable) && (!this.collapseBtn.disabled))
            {
                this.collapseBtn.classList.add('blockBtnDisabled');
                this.collapseBtn.disabled = true;
            }
            else if ((enable) && (this.collapseBtn.disabled))
            {
                this.collapseBtn.classList.remove('blockBtnDisabled');
                this.collapseBtn.disabled = false;
            }
        }

        this.hideCollapse = function (hide)
        {
            if (this.config.hidden.collapse == true)
                this.collapseBtn.style.display = 'none';
            else
                this.collapseBtn.style.display = hide ? 'none' : 'initial';
        }

        this.enableCollapse(false);
        this.hideCollapse(false);
        //#endGenCode

        //#button expand
        //#macroGenCode
        this.expandBtn = this.createCtrlButton( 'expand', nb_outlineBlocksBase.expandClicked);
        this.expandBtn.disabled = false;
        this.enableExpand = function (enable)
        {
            if ((!enable) && (!this.expandBtn.disabled))
            {
                this.expandBtn.classList.add('blockBtnDisabled');
                this.expandBtn.disabled = true;
            }
            else if ((enable) && (this.expandBtn.disabled))
            {
                this.expandBtn.classList.remove('blockBtnDisabled');
                this.expandBtn.disabled = false;
            }
        }

        this.hideExpand = function (hide)
        {
            if (this.config.hidden.expand == true)
                this.expandBtn.style.display = 'none';
            else
                this.expandBtn.style.display = hide ? 'none' : 'initial';
        }

        this.enableExpand(false);
        this.hideExpand(false);
        //#endGenCode

        this.enableAdd(true);

    }
    //#endregion
    this.blockSettings = document.createElement('span');
    this.blockSettings.id = '_blockSettings';
    this.ctrlsNode.appendChild(this.blockSettings);

    this.setSettingsSel = function(selectors) {
        this.blockSettings.innerHTML = selectors;
    }
}

///nb_JSSection nb_OutlineRoot
function nb_OutlineRoot(rootid, readonly, ctrlsid, config)
{
    nb_outlineBlocksBase.call(this);
    this.config = nb_outlineBlocksBase.mergeConfig(nb_outlineBlocksBase.config, config);
    this.ctrlsDisplayNode = ctrlsid == undefined ? null : document.getElementById(ctrlsid);
    this.readonly = readonly == undefined ? false : readonly;
    this.node = document.getElementById(rootid);
    nb_outlineBlocksBase.blockLookup[rootid] = this; // to preserve the div ID set in HTML, have a duplicate entry.
    this.owner = this;
    this.depth = 0;
    if (!this.readonly)
    {
        this.node.classList.add('blockEditor');

        this.addControls(true, this.config);
        this.controls.enableDone(true);
        this.node.addEventListener("click", nb_outlineBlocksBase.selectRoot);
        this.node.addEventListener("dblclick", nb_outlineBlocksBase.enableEditOnClickedBlock);

        this.enableEdit = function (enable)
        {
            if(enable)
            {
                this.editing = true;
                this.node.classList.add('blockEditor');
                if (this.ctrlsNode != null)
                    this.ctrlsNode.hidden = false;
            }
            else
            {
                this.editing = false;
                this.node.classList.remove('blockEditor');
                if (this.ctrlsNode != null)
                    this.ctrlsNode.hidden = true;
            }
        }

        this.enableEdit(false);
    }
    this.editors = [];
    this.defaultEditor = new nb_outlineBlocktypeBase();
    this.defaultChild = '';

    this.addEditor = function(name, editor)
    {
        if (Object.keys(this.editors).length == 0)
            this.defaultChild = name;
        this.editors[name] = editor;
    }

    this.Save = function()
    {
        alert("You need to add a replacement Save method to the root.\n\n"+this.serialize());
    }

    return this;
}

//# This needs some thought and fixing before integration.
//# Probably take nodes as parameters.
nb_outlineBlocktypeBase = function()
{
    this.name = "nb_outlineBlocktypeBase";
    this.initEdit = function(block)
    {
        block.contentnode.hidden = true;
        if (block.editnode == undefined)
        {
            block.editnode = document.createElement('div');
            block.editnode.id = block.id + '_edit';
            block.node.insertBefore(block.editnode, block.contentnode);
        }
        else
        {
            block.editnode.hidden = false;
            //block.blockSettings.innerHTML = block.getBlockSettings();
            block.controls.setSettingsSel(block.getBlockSettings());
        }
        block.editnode.innerHTML = "<textarea id='" + block.id + "_editarea' rows='12' style='margin:2px; width:95%'>" + block.source + "</textarea>";
    }

    this.render = function(block)
    {
        block.source = document.getElementById(block.id + "_editarea").value;
        block.contentnode.innerHTML = document.getElementById(block.id + "_editarea").value;
    }
}

///nb_JSSection nb_outlineBlock

function nb_outlineBlock(owner)
{
    this.owner = owner;
    nb_outlineBlocksBase.call(this);
    this.node = document.createElement('div');
    this.node.classList.add('block');
    this.node.id = this.id;
    this.addControls(false, owner.config);
    //this.ctrlsNode.hidden = true;
    this.contentnode = document.createElement('div');
    this.contentnode.id = this.id + "_content";
    this.contentnode.innerHTML = "Content for "+this.id;
    this.node.insertBefore(this.contentnode, this.ctrlsNode);
    this.node.addEventListener("click", nb_outlineBlocksBase.selectClickedBlock);
    this.node.addEventListener("dblclick", nb_outlineBlocksBase.editClickedBlock);
    this.contentclass = '';
    this.collapsed = false;
    this.contentType = '';

    this.editStart = function ()
    {
        if ((this.owner.config.multiedit == undefined) || (this.owner.config.multiedit == false))
        {
            if (nb_outlineBlocksBase.editingBlock != null)
            {
                alert("Only one block can be edited at a time."); // This shouldn't happen anymore - editEnd() is called when a different block is selected now.
                return false;
            }
        }
        nb_outlineBlocksBase.editingBlock = this.id;
        if (this.owner.editors[this.contentType] != undefined)
            this.owner.editors[this.contentType].initEdit(this);
        else if (this.contentType == '')
            this.owner.defaultEditor.initEdit(this);
        else
            alert("I don't know how to edit " + this.contentType + " content.")
        this.blockSettings = document.createElement('span');
        this.blockSettings.id = this.id + '_blockSettings';
        //this.ctrlsNode.appendChild(this.blockSettings);
        //this.blockSettings.innerHTML = this.getBlockSettings();
        this.controls.setSettingsSel(this.getBlockSettings());
        this.controls.enableDone(true);
        this.controls.enableCancel(true);
        this.controls.enableEdit(false);
        return true;
    }

    this.getBlockSettings = function()
    {
        var csel = "&nbsp;&nbsp;Style: <select onchange='nb_outlineBlocksBase.setBlockClass(\"" + this.id + "\", value);'>\n";
           //# Insert selection box for node type here.
         csel += "<option value=''";
        if ('' == this.contentclass)
            csel += " selected='1'";
        csel += ">(No content class/style)</option>\n";
        for (n in this.owner.contentClasses)
        {
            csel += "<option value='" + this.owner.contentClasses[n].class + "'";
            if (this.owner.contentClasses[n].class == this.contentclass)
                csel += " selected='1'";
            csel += ">" + this.owner.contentClasses[n].description + "</option>\n";
        }
        //<option value=''>s0</option><option value='s1'>s1</option><option value='s2'>s2</option>
        csel += "</select>\n";
        if (Object.keys(this.owner.editors).length > 1)
        {
            csel += "&nbsp;&nbsp;Type: <select onchange='nb_outlineBlocksBase.setBlockType(\"" + this.id + "\", value);'>\n";
            for (n in this.owner.editors)
            {
                csel += "<option value='" + n + "'";
                if (n == this.contentType)
                    csel += " selected='1'";
                csel += ">" + n + "</option>\n";
            }
            csel += "</select>\n";
        }

        //document.getElementById(this.id + "_blockSettings").innerHTML += csel;
        return csel;
    }

    this.editEnd = function (saveIt)
    {       
        if (this.editnode != undefined)
            this.editnode.hidden = true;
        this.contentnode.hidden = false;
        if (saveIt)
        {
            if (this.owner.editors[this.contentType] != undefined)
                this.owner.editors[this.contentType].render(this);
            else
                this.owner.defaultEditor.render(this);
            if (this.contentnode.innerHTML.length == 0)
                this.contentnode.innerHTML = "&nbsp;";
        }
        if (this.blockSettings != undefined)
            this.blockSettings.innerHTML = '';
        this.controls.enableDone(false);
        this.controls.enableCancel(false);
        this.controls.enableEdit(true);
        nb_outlineBlocksBase.editingBlock = null;
    }

    this.setContentclass = function (newclass)
    {
        if (this.contentclass != '')
            this.node.classList.remove(this.contentclass);
        this.contentclass = newclass;
        if (this.contentclass != '')
            this.node.classList.add(this.contentclass);
    }

    this.setContentType = function (newType)
    {
        //#There should be a cleanup to get rid of unwanted artifacts of the old type
        if ((this.owner.editors[this.contentType] != undefined)&&(this.owner.editors[this.contentType].cleanup != undefined))
                this.owner.editors[this.contentType].cleanup(this);
        this.contentType = newType;
        this.owner.editors[newType].initialise(this);
    }

    this.enableMoveButtons = function ()
    {
        this.controls.enableLeft(this.depth > 1);
        this.controls.enableUp(this.index > 0);
        this.controls.enableRight(this.index > 0);
        this.controls.enableDown(nb_outlineBlocksBase.blockLookup[this.parentID].childCount > this.index + 1);
    }

    return this;
}

//I might want this for numbering (from http://blog.stevenlevithan.com/archives/javascript-roman-numeral-converter)
function romanize (num) {
    if (!+num)
        return false;
    var	digits = String(+num).split(""),
		key = ["","C","CC","CCC","CD","D","DC","DCC","DCCC","CM",
		       "","X","XX","XXX","XL","L","LX","LXX","LXXX","XC",
		       "","I","II","III","IV","V","VI","VII","VIII","IX"],
		roman = "",
		i = 3;
    while (i--)
        roman = (key[+digits.pop() + (i * 10)] || "") + roman;
    return Array(+digits.join("") + 1).join("M") + roman;
}