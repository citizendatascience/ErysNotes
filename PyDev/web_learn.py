import sys
import pprint
from io import StringIO
import cgi
import json
import os
import shutil
import urllib.request

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

        if 'urlupload' in post:
            fname = post['urlupload'].value.split('/')[-1]
            html += bytes('Found: ' + fname + '</br>', 'utf-8') 
            try:
                with urllib.request.urlopen(post['urlupload'].value) as response:
                    fp = open(fname, 'wb')
                    shutil.copyfileobj(response, fp)
            except Exception as e:
                print('File '+fname+'not found')    


    start_response('200 OK', [('Content-Type', 'application/json')])
    
    return [bytes('{"fskfgaj":"akjdhfk"}', 'utf-8')]

# This makes this into a tiny web server - remove to use with WAPI
if __name__ == '__main__':
    try:
        from wsgiref.simple_server import make_server
        httpd = make_server('', 8080, app)
        print('Serving on port 8080...')
        httpd.serve_forever()
    except KeyboardInterrupt:
        print('Goodbye.')