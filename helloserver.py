
def app(environ, start_fn):
    start_fn('200 OK', [('Content-Type', 'text/plain')])
    return [b'Hello World!\n']


# This makes this into a tiny web server - remove to use with WAPI
if __name__ == '__main__':
    try:
        from wsgiref.simple_server import make_server
        httpd = make_server('', 8080, app)
        print('Serving on port 8080...')
        httpd.serve_forever()
    except KeyboardInterrupt:
        print('Goodbye.')