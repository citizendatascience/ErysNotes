#!/usr/bin/env python2
# -*- coding: utf-8 -*-
"""
Created on Thu Sep 20 08:34:45 2018

@author: niall
"""
import sys, pickle, dill, pprint

#sys.stderr = sys.stdout

directive = ''
resetpickle = False
notes = []
picklefile = ''

for n in range (1, len(sys.argv)):
    if sys.argv[n][:1] == '-':
        directive = sys.argv[n][1:]
        if directive == 'r':
            resetpickle = True
            directive = ''
        
    else:
        if directive == 'p':
            picklefile = sys.argv[n]
        else:
            notes.append(sys.argv[n])
        directive=''
        
environment = {}
if resetpickle or picklefile == '':
    environment = {}
else:
    try:
        pfile = open(picklefile)
        environment = pickle.load(pfile)
        pfile.close()
    except IOError:
        environment = {}  
    
for filename in notes:
    try:
        cfile = open(filename)
        exec(cfile, environment)
        cfile.close()
    except IOError:
        print('File', filename, ' does not exist')

#pprint.pprint(environment)
        
if picklefile != '':
    pfile = open(picklefile,"w+")
    pickle.dump(environment, pfile)
    pfile.close()

#print "Done I hope"
