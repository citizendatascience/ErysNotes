from pyvis import network as net
import networkx as nx
g = net.Network(notebook=True)
g.add_nodes([1,2,3], value=[10, 100, 400], title=["I am node 1", "node 2 here", "and im node 3"], x=[21.4, 54.2, 11.2], y=[100.2, 23.54, 32.1], label=["NODE 1", "NODE 2", "NODE 3"], color=["#00ff1e", "#162347", "#dd4b39"])
nxg = nx.complete_graph(5)
g.from_nx(nxg)
g.show("example.html")