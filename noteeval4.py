import sys
import pickle
import dill
import pprint
from io import StringIO
import cgi
import json
import os

def app(environ, start_response):
    html = b''
    if environ['REQUEST_METHOD'] == 'POST':
        post_env = environ.copy()
        post_env['QUERY_STRING'] = ''
        post = cgi.FieldStorage(
            fp=environ['wsgi.input'],
            environ=post_env,
            keep_blank_values=True
        )
        for fieldname, fielddata in enumerate(post):
            html += bytes(fielddata + '<br/>' + post[fielddata].value + '<p/>', 'utf-8')

        if 'resetpickle' in post.keys():
            resetpickle = int(post['resetpickle'].value)
        else:
            resetpickle = 0
        if 'picklefile' in post.keys():
            picklefile = post['picklefile'].value
        else:
            picklefile = ''
        html = bytes(noteeval(post['code'].value, resetpickle, picklefile, post['workingdir'].value), 'utf-8')

    start_response('200 OK', [('Content-Type', 'application/json')])
    
    return [html]

def noteeval(code, resetpickle, picklefile, workingdir):
    # save current dir and change to this notebook's dir
    oldwd = os.getcwd()
    os.chdir(workingdir)

    # Load the environment to be used (this allows persistence, so note 2 gets note 1's vars etc.
    environment = {}
    if resetpickle or picklefile == '':
        environment = {}
    else:
        try:
            with open(picklefile, "rb") as pfile:
                environment = dill.load(pfile)
            pfile.close()
        except IOError:
            environment = {}  

    # redirect stdout and stderr
    try:
        old_stdout = sys.stdout
        redir_out = sys.stdout = StringIO()
        old_stderr = sys.stderr
        redir_err = sys.stderr = StringIO()

        exec(code, environment)
        sys.stdout = old_stdout
        sys.stderr = old_stderr

    except Exception as e:
        # tidy up if there's a problem
        sys.stdout = old_stdout
        sys.stderr = old_stderr
        output = "Error: " + str(e)
        os.chdir(oldwd)
        return output

    #get the note's output (and errors)
    output = str(redir_out.getvalue() + redir_err.getvalue())
        
    # save the environment
    if picklefile != '':
        with open(picklefile,"wb") as pfile:
            dill.dump(environment, pfile)
        pfile.close()

    # restore the directory and return the output (or error message.)
    os.chdir(oldwd)
    return output

# This makes this into a tiny web server - remove to use with WAPI
if __name__ == '__main__':
    try:
        from wsgiref.simple_server import make_server
        httpd = make_server('', 8080, app)
        print('Serving on port 8080...')
        httpd.serve_forever()
    except KeyboardInterrupt:
        print('Goodbye.')