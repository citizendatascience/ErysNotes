
// See https://www.brython.info/static_doc/en/options.html
function runScript(aceEditor)
{
    var source = aceEditor.getValue();
    document.getElementById("output").innerHTML = "";
    __BRYTHON__.builtins.exec(source, __BRYTHON__.builtins.dict.$factory([]));
}

