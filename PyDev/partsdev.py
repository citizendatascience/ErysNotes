import sys
import pickle
import dill
import pprint
from io import StringIO
import cgi
import json
import os
import configparser
#import urllib.request
import requests
import base64
from func_timeout import func_timeout, FunctionTimedOut

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
                output, errors, runcount = noteeval(source, resetpickle, picklefile, workingdir)
                jsonData['output'] = output
                jsonData['errors'] = errors
                jsonData['runcount'] = runcount
                
                if 'imgretrieve' in post.keys():
                    oldwd = os.getcwd()
                    os.chdir(workingdir)

                    imgfiles = post['imgretrieve'].value.split(' ')
                    for filename in imgfiles:
                        if(os.path.exists(filename)):
                            with open(filename, "rb") as image_file:
                                jsonData[filename] = base64.b64encode(image_file.read()).decode("utf-8")
                        else:
                            print('File '+filename+' not found');
                    os.chdir(oldwd)

                        
        jsonString = bytes(json.dumps(jsonData), 'utf-8')

        start_response('200 OK', [('Content-Type', 'application/json')])
        return [jsonString]
    else:
        status = '200 OK'
        output = b'Erys Python service is running, but needs post data to do anything useful.\n'
        response_headers = [('Content-type', 'text/plain'), ('Content-Length', str(len(output)))]
        start_response(status, response_headers)
        return [output]
        
def initialise(post):
    #print('Initialising activity')
    oldwd = os.getcwd()
    if(not os.path.exists(post['activityID'].value)):
        os.mkdir(post['activityID'].value)
    os.chdir(post['activityID'].value)
    if(not os.path.exists(post['userID'].value)):
        os.mkdir(post['userID'].value)
    os.chdir(post['userID'].value)
    
    if('filelist') in post.keys():
        try:
            filenames = post['filelist'].value.split(' ')
            for filename in filenames:
                dirpart = os.path.dirname(filename)
                if(dirpart != ''):
                    os.makedirs(dirpart, exist_ok=True)
                if not os.path.exists(filename):
                    urltoget = post['urlupload'].value + filename
                    result = requests.get(urltoget)
                    open(filename, 'wb').write(result.content)

        except Exception as e:
            print("Error: " + str(e))
            output = "Error: " + str(e)
            os.chdir(oldwd)
            return output
    os.chdir(oldwd)  
            
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
    environment['__runcount']  = 0
    if resetpickle or picklefile == '':
        environment = {}
        environment['__runcount']  = 0
    else:
        try:
            with open(picklefile, "rb") as pfile:
                environment = dill.load(pfile)
            pfile.close()
        except IOError:
            environment = {}  
            environment['__runcount']  = 0

    # redirect stdout and stderr
    try:
        old_stdout = sys.stdout
        redir_out = sys.stdout = StringIO()
        old_stderr = sys.stderr
        redir_err = sys.stderr = StringIO()
        
        # Using https://pypi.org/project/func-timeout/ to timeut infinite loops
        # Look at os.setuid(uid) for using a different user (needs a pool, and copying files...) 
        # https://docs.python.org/3/library/os.html#os.setuid
        # Probably will take a bit of testing...
        if environment['__runcount']  == 0:
            exec("import matplotlib\nmatplotlib.use('Agg')\n", environment)

        try:
            func_timeout(5, exec, args=(code, environment))
        except FunctionTimedOut:
            output = ""
            errors = "Error: Timed out. (Do you have an infinite loop in your code?)"
            return  (output, errors, environment['__runcount'])
            
       # exec(code, environment)

        sys.stdout = old_stdout
        sys.stderr = old_stderr

    except Exception as e:
        # tidy up if there's a problem
        sys.stdout = old_stdout
        sys.stderr = old_stderr
        output = ""
        errors = "Error: " + str(e)
        os.chdir(oldwd)
        return  (output, errors, environment['__runcount'])

    #get the note's output (and errors)
    output = str(redir_out.getvalue())
    errors = str(redir_err.getvalue())
    #print(output)
    environment['__runcount']  = environment['__runcount']+1
        
    # save the environment
    if picklefile != '':
        with open(picklefile,"wb") as pfile:
            dill.dump(environment, pfile)
        pfile.close()

    # restore the directory and return the output (or error message.)
    os.chdir(oldwd)
    return (output, errors, environment['__runcount'])
    
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
