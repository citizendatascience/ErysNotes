
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
done: "<img src='ErysIcons/run.png' alt='Run'>" 
}
        config.hidden = {
            add: true,
            left: true,
            right: true,
            comment: true,
            collapse: true,
            expand: true,
        }
config.multiedit = true;
   
var blockeditor = nb_OutlineRoot('blockhost', false, 'blockctrls', config);
blockeditor.enableEdit(true);
blockeditor.editors['nb_markdown'] = new nb_markdownBlock();
blockeditor.editors['pythonCode'] = new nb_codeBlock();
blockeditor.editors['basic'] = new nb_outlineBlocktypeBase();
//blockeditor.contentClasses = contentClasses;
blockeditor.unserialize(content);
