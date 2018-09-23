import matplotlib
matplotlib.use('Agg')

import pandas as pd

import numpy as np

import matplotlib.pyplot as plt

import warnings; warnings.simplefilter('ignore')

dataframe = pd.read_csv("data/protected_areas.csv", sep=',')

print (dataframe.head())

print (dataframe['On land'][2:4])


#CODE GOES HERE

dataframe.plot(x='Year',y=['At sea','On land','Total'])

plt.title("Protected territories in the UK")

plt.ylabel("Million Hectares")

plt.savefig('out1.png', dpi=150)
plt.clf()


yearRange = dataframe['Year']>2006

x_pos = np.arange(len(dataframe[yearRange]['Year']))

plt.bar(x_pos,dataframe[yearRange]['At sea'])

plt.xticks(x_pos, dataframe[yearRange]['Year'])

plt.title('Protected territories at sea')

#LABELS CODE HERE

plt.savefig('out2.png', dpi=150)
plt.clf()






