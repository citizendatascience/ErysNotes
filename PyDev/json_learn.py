# -*- coding: utf-8 -*-
"""
Created on Fri Jan 25 10:10:12 2019

@author: nsb2x
"""
import json

jsontxt =  '[{"class":"objectives","description":"Learning objectives and/or outcomes"},{"class":"teacherNotes","description":"Notes for teacher, development notes etc."},{"class":"additionalMaterial","description":"Additional or suppliementary material"}]'

print('JSON is ' + jsontxt)
data = json.loads(jsontxt)
for i, dpart in enumerate(data):
    print('Data[' + str(i) + '] contains ' + dpart['class'] + ': ' + dpart['description'])
print('done')
"""

with open('data.txt') as json_file:  
    data = json.load(json_file)
    for p in data['people']:
        print('Name: ' + p['name'])
        print('Website: ' + p['website'])
        print('From: ' + p['from'])
        print('')
"""