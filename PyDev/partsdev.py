import sys
import pickle
import dill
import pprint
from io import StringIO
import cgi
import json
import os
import configparser
import urllib.request

def app(environ, start_response):
    # I'm hoping this next line isn't needed by apache. 
    os.chdir(os.path.dirname(os.path.realpath(__file__)))
    
    html = b''
    jsonData = {}
    jsonString = b''
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
            jsonData[fielddata] = post[fielddata].value
            
        if('message') in post.keys():
            if(post['message'].value == 'initialise'):
                initialise(post)
            if(post['message'].value == 'runblock'):
                resetpickle = 0
                if 'resetpickle' in post.keys():
                    resetpickle = int(post['resetpickle'].value)
                source = ''
                if 'source' in post.keys():
                    source = post['source'].value
                workingdir = post['activityID'].value + '/' + post['userID'].value
                picklefile = post['userID'].value + '.pickle'
                checkUserReady(resetpickle, picklefile, workingdir)
                jsonData['output'] = noteeval(source, resetpickle, picklefile, workingdir)

                        
        jsonString = bytes(json.dumps(jsonData), 'utf-8')

    start_response('200 OK', [('Content-Type', 'application/json')])
    return [jsonString]

    #start_response('200 OK', [('Content-Type', 'text/html')])
    #return [html]
    
def initialise(post):
    #print('Initialising activity')
    if(not os.path.exists(post['activityID'].value)):
        os.mkdir(post['activityID'].value)
    os.chdir(post['activityID'].value)
    if('filename') in post.keys():
        try:
            if('urlupload') in post.keys():
                urllib.request.urlretrieve(post['urlupload'].value, post['filename'].value)  
            else:
                print("Need to retrieve " + post['filename'].value)
        except Exception as e:
            output = "Error: " + str(e)
            return output
        
            
def checkUserReady(resetpickle, picklefile, workingdir):
    if(not os.path.exists(workingdir)):
        os.mkdir(workingdir)
    #os.chdir(workingdir)
    
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
    
#config = configparser.ConfigParser()
#config.sections()
#[]
#config.read('eryspy.ini')


# This makes this into a tiny web server - remove to use with WAPI
if __name__ == '__main__':
    try:
        from wsgiref.simple_server import make_server
        httpd = make_server('', 8080, app)
        print('Serving on port 8080...')
        httpd.serve_forever()
    except KeyboardInterrupt:
        print('Goodbye.')
