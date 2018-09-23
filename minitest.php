<!DOCTYPE html>
<html>
<head>

<script type="text/javascript" src="/src/brython.js"></script>
</head>

<body onload="brython(1)">

<script type="text/python">
from browser import document, alert

def echo(ev):
    alert("Hello {} !".format(document["zone"].value))

document["test"].bind("click", echo)
</script>
<p>Your name is : <input id="zone" autocomplete="off">
<button id="test">clic !</button>
</body>

</html>